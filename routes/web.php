<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class, 'getUsers']);

Route::get('/users', [UserController::class, 'getUsers']);

Route::get('/users/{id}', [UserController::class, 'show']);

