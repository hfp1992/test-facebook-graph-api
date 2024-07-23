<?php

//use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('welcome');
});

Route::get('/webhook', [WebhookController::class, 'verifyWebhook']);

Route::post('/webhook', [WebhookController::class, 'getNotification']);

Route::get('/privacy', [WebhookController::class, 'privacyAndPolicy']);

//Route::get('/test', [UserController::class, 'test']);
