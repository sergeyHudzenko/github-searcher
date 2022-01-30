@extends('layouts.app')

@section('content')
<h1 class="col-start-2 col-span-4 text-2xl text-center  w-full">Github Searcher</h1>
<div class="col-start-2 col-span-4 rounded-lg w-full p-10 grid grid-row-4 grid-cols-6 gap-4">
    <div class="row-span-3 col-span-2 rounded-lg bg-slate-400 bg-cover bg-[url('{{$user->avatar_url}}')]"></div>
    <div class="row-span-3 col-start-4 col-span-3">
        <p>Username: {{ $user->login }} </p> 
        <p>Email: {{ $user->email ?? 'empty 😥' }} </p> 
        <p>Location: {{ $user->location ?? 'empty 😥' }} </p> 
        <p>Join date: {{ date('d-m-Y', strtotime($user->created_at)) }} </p> 
        <p>Followers: {{ $user->followers }} </p> 
        <p>Following: {{ $user->following }} </p>
    </div>
</div>

<p class="col-start-2 col-span-4 w-full">{{ $user->bio ?? 'user didn`t fill a bio 😥' }}</p>

<div class="col-start-2 col-span-4 rounded-lg w-full p-10">
    <form method="POST" class="searchForm" action="{{ route('users.search.repos', $user->login) }}">
        @csrf

        <input type="text" name="search" class="w-full p-5 mt-10" placeholder="Search for Users" value="@if(isset($searchRow)){{ $searchRow }}@endif" />
    </form>
</div>

<div class="col-start-2 col-span-4 bg-slate-200 rounded-lg w-full p-10">
    @if (isset($repos))
        @foreach ($repos as $repo) 
            <a href="{{$repo->html_url}}">
                <div class="item grid grid-cols-6 gap-4 w-full bg-slate-300 p-10 rounded-lg mb-5">
                    <div class="col-span-4">{{$repo->name}}</div>
                    <div class="col-span-2 text-right">
                        <p>{{$repo->forks_count }} Forks</p>
                        <p>{{$repo->stargazers_count }} Stars</p>
                    </div>
                </div>
            </a>
        @endforeach 
    @endif
</div>
@endsection