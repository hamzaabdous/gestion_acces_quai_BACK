<?php

namespace App\Modules\UserVesselBadges\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\UserVesselBadges\Models\UserVesselBadges;

class UserVesselBadgesController
{

    public function addBadge(Request $request, $historyId)
{
    $badge = UserVesselBadges::create([
        'user_vessel_history_id' => $historyId,
        'badge_place'            => $request->badge_place,
        'badge_date'             => $request->badge_date,
    ]);

    return response()->json($badge, 201);
}
}
