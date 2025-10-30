<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\UserController;
use App\Modules\User\Http\Controllers\AuthController;


Route::group([
    'middleware' => 'api',
    'prefix' => 'api/users'

], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'create']);

});

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'api/users'

], function ($router) {
    Route::get('/', [UserController::class, 'index']);


});