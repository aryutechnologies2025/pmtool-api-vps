<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/welcome-test', function () {
    // echo bcrypt('@A-1k=TY'); //admin

    // echo bcrypt('@A-2k=TY'); //pm
    // echo bcrypt('@A-3k=TY'); // tm

    // echo bcrypt('@A-4k=TY'); //writer
    echo bcrypt('@A-5k=TY'); //reviewer
});
