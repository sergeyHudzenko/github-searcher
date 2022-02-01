<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\GitUser;
use App\Models\GitUserRepository;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{   
    /**
     * 
     * Found listed users and filtering it by total_repos, followers, popularity fields
     * @param Request $req set page for pagination
     * @return Response json
     */
    public function userList(Request $req) {
        // if you set page to body, you will be paginate by response
        $page = $req->input('page');

        $users = GitUser::orderBy('total_repos', 'DESC')
                ->orderBy('followers', 'DESC')
                ->orderBy('popularity', 'DESC')
                ->take(3)
                ->skip(($page && $page > 0) ? ($page - 1) * 3 : 0);

        return response()->json(['message' => 'Success', 'response' => $users->get()->toArray()], 200);
    }

    /**
     * 
     * Found popular users
     * @param Request $req set popularity_by_date like 1, for returning users by popularity_by_date
     * @return Response json
     */
    public function userPopular(Request $req) {
        // Put param to 1 for getting users by popularity_by_date field, else you'll get users by popularity field
        $popularity_by_date = $req->input('popularity_by_date');

        if (isset($popularity_by_date) && $popularity_by_date === 1) {
            $users = GitUser::orderBy('popularity_by_date', 'DESC')->take(3);
            return response()->json(['message' => 'Success', 'response' => $users->get()->toArray()], 200);
        }

        $users = GitUser::orderBy('popularity', 'DESC')->take(3);
        return response()->json(['message' => 'Success', 'response' => $users->get()->toArray()], 200);
    }

    /**
     * 
     * Search users by login
     * @param string login
     * @return Response json
     */
    public function searchUser(string $login) {
        $user = GitUser::firstWhere('login' , $login);
        
        // if user found -> return response
        if (isset($user)) {
            return response()->json(['message' => 'Success', 'response' => $user->toArray()], 200);
        } else {
            return response()->json(['message' => 'Not found'], 404);
        }
    }
}