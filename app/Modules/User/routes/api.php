<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\UserController;
use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\UserVesselBadges\Http\Controllers\UserVesselBadgesController;

use App\Modules\UserVesselHistories\Http\Controllers\UserVesselHistoriesController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/users'

], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'create']);
    Route::get('/getRecords', [UserVesselBadgesController::class, 'getRecords']);
    Route::get('/getShiftPointage', [UserVesselBadgesController::class, 'getShiftPointageA']);
    Route::get('/searchByMatricule', [UserVesselBadgesController::class, 'searchByMatricule']);
    Route::get('/fetchByDay', [UserVesselHistoriesController::class, 'fetchByDay']);

});

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'api/users'

], function ($router) {
    Route::get('/', [UserController::class, 'index']);


});