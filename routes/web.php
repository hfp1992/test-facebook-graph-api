<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('welcome');
});

Route::get('/webhook', [UserController::class, 'verifyWebhook']);

Route::post('/webhook', [UserController::class, 'getNotification']);

Route::get('/test', [UserController::class, 'test']);
