<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainPageController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [MainPageController::class, 'index']);
Route::post('/', [MainPageController::class, 'searchUsers'])->name('users.search');
Route::get('/{search}/page/{page}', [MainPageController::class, 'searchUsers'])->name('users.search.paginate');
Route::get('/user/{login}', [MainPageController::class, 'show'])->name('users.single');
Route::post('/user/{login}', [MainPageController::class, 'searchRepos'])->name('users.search.repos');
