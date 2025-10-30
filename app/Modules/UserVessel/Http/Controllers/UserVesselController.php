<?php

namespace App\Modules\UserVessel\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\UserVessel\Models\UserVessel;

class UserVesselController
{
    // List all user vessels
    public function index()
    {
        return UserVessel::all();
    }

    // Store a new user vessel
    public function store(Request $request)
    {
        $usersFromExcel = $request->data; // array of users

        foreach ($usersFromExcel as $item) {
    
            $userVessel = new UserVessel;
    
            $userVessel->matricule    = $item['matricule'] ?? null;
            $userVessel->first_name   = $item['first_name'] ?? null;
            $userVessel->last_name    = $item['last_name'] ?? null;
            $userVessel->function = $item['function'] ?? null;
            $userVessel->company      = $item['company'] ?? null;
            $userVessel->shift        = $item['shift'] ?? null;
            $userVessel->workarea     = $item['workarea'] ?? null;
    
            $userVessel->save();
        }
    
        // Return all UserVessels after insertion
        $users = UserVessel::all();
    
        return [
            'payload' => $users,
            'status'  => 200
        ];
    }

    // Show a single user vessel
    public function show(UserVessel $userVessel)
    {
        return $userVessel;
    }

    // Update a user vessel
    public function update(Request $request, UserVessel $userVessel)
    {
        $userVessel->update($request->all());
        return response()->json($userVessel, 200);
    }

    // Delete a user vessel
    public function destroy(UserVessel $userVessel)
    {
        $userVessel->delete();
        return response()->json(null, 204);
    }
}
