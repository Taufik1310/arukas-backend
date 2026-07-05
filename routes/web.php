<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/auth/google/redirect',  [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback',  [AuthController::class, 'googleCallback']);
