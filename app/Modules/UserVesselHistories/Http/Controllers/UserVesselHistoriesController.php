<?php

namespace App\Modules\UserVesselHistories\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
use App\Modules\UserVesselBadges\Http\Controllers\UserVesselBadgesController;
use Carbon\Carbon;
use App\Modules\UserVessel\Models\UserVessel;
use Illuminate\Support\Facades\DB;
use App\Modules\UserVesselBadges\Models\UserVesselBadges;
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

            $userVessel->user_vessel_id = UserVessel::where('matricule', $item["matricule"])->value('id') ?? null;
            $userVessel->shift = $item['shift'] ?? null;
            $userVessel->work_date = $item['work_date'] ?? null;
            $userVessel->workarea = $item['workarea'] ?? null;
            $userVessel->overtime = $item['overtime'] ?? 0;


            $userVessel->save();

        }

        // Return all UserVessels after insertion
        $userVesselHistoriesController = new UserVesselHistoriesController();
        $result = $userVesselHistoriesController->fetchByDay($request); // Call the method

        return [
            'payload' => $result,
            'status' => 200
        ];
    }
    public function update(Request $request)
    {
        $data = $request->data; // array of users
    
        foreach ($data as $item) {
    
            // Each item MUST contain an ID to update
            if (!isset($item['id'])) {
                continue; // Skip if no ID found
            }
    
            $history = UserVesselHistories::find($item['id']);
    
            if (!$history) {
                continue; // Skip invalid IDs
            }
    
            // Update fields only if provided
            $history->user_vessel_id = UserVessel::where('matricule', $item["matricule"])->value('id')
                                    ?? $history->user_vessel_id;
    
            $history->shift     = $item['shift']     ?? $history->shift;
            $history->work_date = $item['work_date'] ?? $history->work_date;
            $history->workarea  = $item['workarea']  ?? $history->workarea;
            $history->overtime  = $item['overtime']  ?? $history->overtime;
    
            $history->save();
        }
    
        // Return updated list for the same day
        $userVesselHistoriesController = new UserVesselHistoriesController();
        $result = $userVesselHistoriesController->fetchByDay($request);
    
        return [
            'payload' => $result,
            'status' => 200
        ];
    }
    

    function mergeResultIntoExistingPayload(array $result, array $payload): array
    {
        foreach ($payload as &$user) {
            foreach ($result as $r) {
                // Match by matricule
                if ($user['matricule'] === $r['id']) {
                    $badge_place = $r['badge_place'] ?? '';
                    $date = $r['date'] ?? '';
                    $hour = $r['hour'] ?? '00:00';
                    $device_id = $r['device_id'] ?? '';
                    $action = $r['action'] ?? '';
                    $badge_date = $date . ' ' . $hour . ':00';

                    // Check if this badge already exists in DB
                    $exists = UserVesselBadges::where('badge_date', $badge_date)
                        ->where('device_id', $device_id)
                        ->where('action', $action)
                        ->exists();

                    if ($exists) {
                        // Skip if already exists
                        continue;
                    }

                    // Find history with same work_date
                    $historyFound = false;
                    foreach ($user['histories'] as &$history) {
                        if ($history['work_date'] === $date) {
                            $historyFound = true;

                            // Save to DB
                            $badge = UserVesselBadges::create([
                                'user_vessel_history_id' => $history['id'],
                                'badge_place' => $badge_place,
                                'badge_date' => $badge_date,
                                'action' => $action,
                                'device_id' => $r['device_id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Add to array (for local response)
                            $history['badges'][] = $badge->toArray();
                            break;
                        }
                    }


                }
            }
        }
        return $payload;
    }



    public function fetchByDay(Request $request)
    {
        $userVesselHistoriesController = new UserVesselHistoriesController();

        $userVesselBadgesController = new UserVesselBadgesController();
        //$result = $userVesselBadgesController->getRecords($request); // Call the method

        $today = Carbon::now()->toDateString();

        $data = UserVessel::with([
            'histories' => function ($query) use ($today) {
                $query->whereDate('work_date', $today)
                    ->with([
                        'badges' => function ($badgeQuery) {
                            $badgeQuery->orderBy('badge_date', 'desc'); // 🔽 sort newest first
                        }
                    ])
                    ->orderBy('work_date', 'desc'); // optional: sort histories by date too
            }
        ])
            ->whereHas('histories', function ($query) use ($today) {
                $query->whereDate('work_date', $today);
            })
            ->orderBy('matricule') // optional: sort users by matricule
            ->get()->toArray();

       // $merged = $userVesselHistoriesController->mergeResultIntoExistingPayload($result->original["data"], $data);
        //$notifications = $this->generateOvertimeNotifications($data);

        return [
            //'payload' => $merged,
            //'notifications' => $notifications,
            'data' => $data,
            'status' => 200
        ];
    }

public function generateOvertimeNotifications(array $data): array
{
    $now = Carbon::now();
    $notifications = [];

    foreach ($data as $user) {
        foreach ($user['histories'] as $history) {
            $badges = $history['badges'] ?? [];
            if (count($badges) === 0) continue;

            // Parse badge datetimes
            $badgeLog = collect($badges)->map(function ($b) {
                $b['parsed_date'] = Carbon::parse($b['badge_date']);
                return $b;
            });

            $validPairs = [];
            $unpairedINFound = false;
            $overtime = (int)($history['overtime'] ?? 0);

            foreach ($badgeLog as $inBadge) {
                if (!str_contains($inBadge['badge_place'], '-IN')) continue;

                $base = explode('-IN', $inBadge['badge_place'])[0];

                $outBadge = $badgeLog->first(function ($b) use ($base, $inBadge) {
                    return str_starts_with($b['badge_place'], $base . '-OUT') &&
                        $inBadge['parsed_date']->lt($b['parsed_date']);
                });

                if ($outBadge) {
                    $validPairs[] = [
                        'base' => $base,
                        'in_time' => $inBadge['parsed_date']->toDateTimeString(),
                        'out_time' => $outBadge['parsed_date']->toDateTimeString(),
                    ];
                } else {
                    // No matching OUT, check if it exceeded 8 + overtime
                    $hoursSinceIN = $inBadge['parsed_date']->floatDiffInHours($now, false);
                    if ($hoursSinceIN >= (8 + $overtime)) {
                        $unpairedINFound = true;
                    }
                }
            }

            // If any unpaired IN is over time limit → notify
            if ($unpairedINFound) {
                $notifications[] = [
                    'user' => [
                        'id' => $user['id'],
                        'matricule' => $user['matricule'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'function' => $user['function'],
                        'company' => $user['company'],
                        'shift' => $user['shift'],
                        'workarea' => $user['workarea'],
                    ],
                    'history_id' => $history['id'],
                    'work_date' => $history['work_date'],
                    'overtime' => $overtime,
                    'badge_pairs' => $validPairs,
                    'unpaired_in_found' => $unpairedINFound,
                    'notif' => true
                ];
            }
        }
    }

    return $notifications;
}


}
