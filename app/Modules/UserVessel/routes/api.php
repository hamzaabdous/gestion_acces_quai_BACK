<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Modules\UserVessel\Http\Controllers\UserVesselController;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'api/user-vessels'

], function ($router) {
    Route::get('/', [UserVesselController::class, 'index']);
    Route::post('/store', [UserVesselController::class, 'store']);


});