<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\UserVesselBadges\Http\Controllers\UserVesselBadgesController;


Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'api/user-vessel-badges'

], function ($router) {
    Route::get('/', [UserVesselBadgesController::class, 'index']);
    Route::post('/store', [UserVesselBadgesController::class, 'store']);


});