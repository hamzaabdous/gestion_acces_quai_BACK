<?php

namespace App\Modules\UserVesselHistories\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
class UserVesselHistoriesController
{

    public function index()
    {
        return UserVesselHistories::all();
    }

    // Store a new user vessel
    public function store(Request $request)
    {
        $data = $request->data; // array of users

        foreach ($data as $item) {
    
            $userVessel = new UserVesselHistories;
    
            $userVessel->user_vessel_id    = $item['user_vessel_id'] ?? null;
            $userVessel->shift   = $item['shift'] ?? null;
            $userVessel->work_date    = $item['work_date'] ?? null;
            $userVessel->workarea = $item['workarea'] ?? null;

    
            $userVessel->save();
            
        }
    
        // Return all UserVessels after insertion
        $users = UserVesselHistories::all();
    
        return [
            'payload' => $users,
            'status'  => 200
        ];
    }
}
