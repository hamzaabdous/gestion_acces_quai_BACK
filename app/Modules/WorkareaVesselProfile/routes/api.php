<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Modules\WorkareaVesselProfile\Http\Controllers\WorkareaVesselProfileController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/workareavesselprofile'

], function ($router) {
    Route::get('/fetchVessels', [WorkareaVesselProfileController::class, 'fetchVessels']);


});