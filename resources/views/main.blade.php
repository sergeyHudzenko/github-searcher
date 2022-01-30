@extends('layouts.app')

@section('content')
        <div class="col-start-2 col-span-4 rounded-lg w-full p-10">
            <h1 class="text-2xl text-center">Github Searcher</h1>
            <form method="POST" class="searchForm" action="{{ route('users.search') }}">
                @csrf

                <input type="text" name="search" class="w-full p-5 mt-10" placeholder="Search for Users" value="@if(isset($searchRow)){{ $searchRow }}@endif" />
            </form>
        </div>
        <div class="col-start-2 col-span-4 bg-slate-200 rounded-lg w-full p-10">
            @if (isset($users))
                @foreach ($users as $user) 
                    <a href="{{route('users.single', $user->login)}}">
                        <div class="item grid grid-cols-6 gap-4 w-full bg-slate-300 p-10 rounded-lg mb-5">
                            <div class="col-span-1 box-content h-16 w-16 avatar bg-cover bg-[url('{{$user->avatar_url}}')] rounded-full bg-slate-100"></div>
                            <div class="col-span-2">{{ $user->login }}</div>
                            <div class="col-span-3 text-right">Repos: {{ $user->total_repos }}</div>
                        </div>
                    </a>
                @endforeach 
            @endif

            @if(isset($searchRow))
                <a href="{{route('users.search.paginate', [$searchRow, $page])}}">Next page</a>
            @endif
        </div>
@endsection
