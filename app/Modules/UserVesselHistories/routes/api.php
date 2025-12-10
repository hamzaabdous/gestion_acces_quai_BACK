<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Modules\UserVesselHistories\Http\Controllers\UserVesselHistoriesController;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'api/histories'

], function ($router) {
    Route::get('/', [UserVesselHistoriesController::class, 'index']);
    Route::post('/store', [UserVesselHistoriesController::class, 'store']);
    Route::post('/update', [UserVesselHistoriesController::class, 'update']);
    Route::post('/fetchByDay', [UserVesselHistoriesController::class, 'fetchByDay']);


});