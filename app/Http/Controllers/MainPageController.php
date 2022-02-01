<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\GitUser;
use App\Models\GitUserRepository;
use Carbon\Carbon;

class MainPageController extends Controller
{    
    private $urls = [
        'SEARCH_USER' => 'https://api.github.com/search/users',
        'TOTAL_REPOS' => 'https://api.github.com/users/%s',
        'USER_REPOS' => 'https://api.github.com/users/%s/repos',
        'GET_USERS' => 'https://api.github.com/users',
        'GET_USER' => 'https://api.github.com/users/%s',
        
    ];
    private $config = [
        'ALL_USERS_PER_PAGE_COUNT' => 10,
        'SEARCHED_USERS_PER_PAGE_COUNT' => 10,
        'USER_REPOS_PER_PAGE_COUNT' => 10
    ];

    /**
     * Add total_repos properties to $users array
     * 
     * @param array $users
     * @return array $users
     */
    private function addTotalRepos(array &$users) {
        foreach($users as &$user){  
            // Make request to github for getting total repos count
            $singleUser = Http::get(sprintf($this->urls['TOTAL_REPOS'], $user['login']));
            
            if ($singleUser->successful()) { 
                $totalRepos = $singleUser['public_repos'];
                // Set new property to user object
                $user['total_repos'] = $totalRepos;
            } else {
                $user['total_repos'] = 0;
            }
        } 

        return $users;
    }

    private function getUsersRepos(string $login) { 
        $dbGitUser = GitUser::firstWhere('login', $login);
        
        if(isset($dbGitUser)){
            $dbGitUserRepos = $dbGitUser->repos();  

            if ($dbGitUserRepos->count()) { 
                return $dbGitUserRepos->get(); 
            }
        }

        // Make request to github for getting a special user repos
        $repos = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3.star+json'
        ])->get(sprintf($this->urls['USER_REPOS'], $login), ['per_page' => $this->config['USER_REPOS_PER_PAGE_COUNT']]);
        if ($repos->successful()){
            // insert user repos to DB
            $this->insertGitUserRepos($login, $repos->json());
            return $this->convertArrayToObject($repos->json());
        }

        return null;
    }

    /**
     * Convert array to object
     * 
     * @param array $arr Array with array inside
     * @return array Array of objects
     */
    private function convertArrayToObject(array $arr) {
        return json_decode(json_encode($arr), FALSE);
    }

    /**
     * Inserting users to DB
     * 
     * @param array $arr Array with users
     */
    private function insertGitUsers(array $users) {
        foreach ($users as $user) {
            $login  = $user['login']; 
            if (is_null(GitUser::firstWhere('login', $login))){
                GitUser::create($user);
            }
        }
    }

    /**
     * Update users to DB
     * 
     * @param array $arr Array with user info
     */
    private function updateGitUsers(array $user) { 
        $login  = $user['login']; 
        $getUser = GitUser::firstWhere('login', $login);

        if (isset($getUser)) {
            $user['full_loaded'] = true; 
            $getUser->update($user);
            $getUser->save(); 

            // increement popularity field
            $this->incrementPopularity($getUser);
        } else {
            $user['popularity'] = 1; 
            GitUser::create($user);
        }
    }

    private function incrementPopularity (GitUser $user) {

        if (isset($user->popularity_date) && $user->popularity_date->eq(Carbon::now()->startOfDay())) { 
            $user->increment('popularity_by_date');
        } else {
            $user->update(['popularity_by_date' => 1, 'popularity_date' => Carbon::now()->startOfDay()]);
        }

        $user->increment('popularity');
        $user->save();
    }

    /**
     * Insert users repos in DB
     * 
     * @param string $login login of user
     * @param array $repos Array with user repos
     */
    private function insertGitUserRepos(string $login, array $repos) { 
        $getUser = GitUser::firstWhere('login', $login);

        if (isset($getUser)) { 
            foreach ($repos as $repo) {
                // Update repo if found in DB
                $dbRepo = GitUserRepository::firstWhere(['name' => $repo['name'], 'user_id' => $getUser->id]);
                if(isset($dbRepo)) {
                    $dbRepo->update($repo);
                    $dbRepo->save();
                }

                $repo['user_id'] = $getUser->id;
                GitUserRepository::create($repo);
            }
        } 
    }

    /**
     * Display a listing of the github users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Make request to github for getting users
        $users = Http::get($this->urls['GET_USERS'], ['since' => rand(0, 9999), 'per_page' => $this->config['ALL_USERS_PER_PAGE_COUNT']])->json();
        // Adding total_repos properties to each users
        $this->addTotalRepos($users);

        $this->insertGitUsers($users);

        return view('main', ['users' => collect($this->convertArrayToObject($users))->sortBy('total_repos')->reverse()->toArray() ]);
    }

    /**
     * Display the single user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($login)
    { 
        $dbGitUser = GitUser::firstWhere('login', $login); 
        if(isset($dbGitUser) && $dbGitUser->full_loaded) {
            $repos = $this->getUsersRepos($login); 
            // increement popularity field
            $this->incrementPopularity($dbGitUser);
            return view('single', ['user' => $dbGitUser, 'repos' => $repos]);
        }

        // Make request to github for getting a special user
        $user = Http::get(sprintf($this->urls['GET_USER'], $login));
        if ($user->successful()) { 
            // Getting repos for current user
            $repos = $this->getUsersRepos($login);  
            $this->updateGitUsers($user->json()); 

            return view('single', ['user' => $this->convertArrayToObject($user->json()), 'repos' => $repos]);
        } 

        $users->throw();
    } 

    /**
     * Search repos.
     *
     * @param  Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchRepos(Request $request) {
        // Search query
        $searchRow = $request->search;
        // Login from path
        $login = $request->login;

        if (isset($searchRow) && $searchRow !== '') {
            $users = Http::get(sprintf($this->urls['GET_USER'], $login));
            if ($users->successful()) { 
                $repos = $this->getUsersRepos($login);  
                // Filter repos array by search string or substring
                $foundRepos = collect($repos)->filter(function($item) use ($searchRow) {
                    return stripos($item->name, $searchRow) !== false;
                });
    
                return view('single', ['user' => $this->convertArrayToObject($users->json()), 'repos' => $foundRepos, 'searchRow' => $searchRow  ]);
            }
        }

        return redirect()->route('users.single', ['login' => $login]);
    }

    /**
     * Search user.
     *
     * @param  Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchUsers(Request $request)
    {   
        $searchRow = $request->search;
        $page = $request->page;
 

        if (isset($searchRow) && $searchRow !== '') {
 
           
            // Make request to github for searching users
            $users = Http::get($this->urls['SEARCH_USER'], ['q' => $searchRow, 'per_page' => $this->config['SEARCHED_USERS_PER_PAGE_COUNT'], 'page' => $page ?? 1]);
            
            if ($users->successful()) {
                // parse json from github responce
                $parsedUsers = $users->json();
                // Take items array
                $items = $parsedUsers['items'];
                // Add total_repos param to $users
                $this->addTotalRepos($items);
                // Add to DB
                $this->insertGitUsers($items);
                 
                return view('main', ['users' => collect($this->convertArrayToObject($items))->sortBy('total_repos')->reverse()->toArray(), 'searchRow' => $searchRow, 'page' => $page ? ((int) $page + 1) : 2 ]);
            } else {
                $users->throw();
            }
        }

        return view('main');
    } 
}
