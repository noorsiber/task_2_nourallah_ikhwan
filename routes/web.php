<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/register', function () {
    return view('signup');
});
Route::get('/login', function () {
    return view('login');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth','twofactor')->group(function (){
    
    // Route::get('two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
    // Route::post('two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});