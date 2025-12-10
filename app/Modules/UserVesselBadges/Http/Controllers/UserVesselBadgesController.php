<?php

namespace App\Modules\UserVesselBadges\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\UserVesselBadges\Models\UserVesselBadges;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UserVesselBadgesController
{

    public function addBadge(Request $request, $historyId)
    {
        //$userVesselBadges = UserVesselBadges::whereIn("")->get();

        $badge = UserVesselBadges::create([
            'user_vessel_history_id' => $historyId,
            'badge_place' => $request->badge_place,
            'badge_date' => $request->badge_date,
        ]);


        return response()->json($badge, 201);
    }
    public function addNewBadge(array $result)
    {
        //$userVesselBadges = UserVesselBadges::whereIn("")->get();

        foreach ($result as &$user) {

            $badge = UserVesselBadges::create([
                'user_vessel_history_id' => $user->historyId,
                'badge_place' => $user->badge_place,
                'badge_date' => $user->badge_date,
            ]);
        }



        return response()->json($badge, 201);
    }



    public function getRecords(Request $request)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        //$date = $request->input('date', date('Y-m-d'));
        $date = "2025-11-13";

        try {
            // 1) Login
            $loginPayload = [
                'User' => [
                    'login_id' => "admin",
                    'password' => "Axians2020",
                ],
            ];

            $loginResponse = Http::withoutVerifying()->post("${baseUrl}/login", $loginPayload);

            if ($loginResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Login failed'], 401);
            }

            $sessionId = $loginResponse->header('bs-session-id');
            if (!$sessionId) {
                return response()->json(['status' => 'error', 'message' => 'Missing session ID'], 400);
            }

            // 2) Build requestBody
            $requestBody = [
                'Query' => [
                    'limit' => 60000000,
                    'conditions' => [
                        [
                            'column' => 'datetime',
                            'operator' => 3,
                            'values' => [
                                $date . 'T00:00:00.00Z',
                                $date . 'T23:59:59.99Z',
                            ],
                        ],
                    ],
                    'orders' => [
                        [
                            'column' => 'datetime',
                            'descending' => false,
                        ],
                    ],
                ],
            ];

            // 3) Fetch events
            $eventsResponse = Http::withoutVerifying()
                ->withHeaders(['bs-session-id' => $sessionId])
                ->post("${baseUrl}/events/search", $requestBody);

            if ($eventsResponse->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Events search failed'], 500);
            }

            $data = $eventsResponse->json();
            $rows = $data['EventCollection']['rows'] ?? [];

            $records = $this->transformRecords($rows);

            // return directly (no file)
            return response()->json([
                'status' => 'success',
                'count' => count($records),
                'data' => $records,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
        //$result = $this->getShiftPointage("2025-11-13", "DAY");

    }

    public function getShiftPointageA(Request $request)
    {

        // Get query params from frontend
        $date = $request->input('date', now()->toDateString());
        $shift = $request->input('shift', 'ADMIN');




        // Call your unified function
        $result = $this->getShiftPointagetrue($date, $shift);
        //$result = $this->getShiftPointage("2025-11-13", "DAY");
        //dd(vars: $result);

        return $result;
    }

    public function getRecords2days(Request $request)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        $startDate = "2025-11-11";
        $endDate = date('Y-m-d', strtotime("2025-11-11" . ' +1 day'));

        // 1) Login
        $loginPayload = [
            'User' => [
                'login_id' => "admin",
                'password' => "Axians2020",
            ],
        ];

        $loginResponse = Http::withoutVerifying()->post("${baseUrl}/login", $loginPayload);
        if ($loginResponse->failed()) {
            throw new \Exception('Login failed');
        }

        $sessionId = $loginResponse->header('bs-session-id');
        if (!$sessionId) {
            throw new \Exception('Missing session ID');
        }

        // 2) Build request body
        $requestBody = [
            'Query' => [
                'limit' => 3000000,
                'conditions' => [
                    [
                        'column' => 'datetime',
                        'operator' => 3,
                        'values' => [
                            $startDate . 'T00:00:00.00Z',
                            $endDate . 'T23:59:59.99Z',
                        ],
                    ],
                ],
                'orders' => [
                    [
                        'column' => 'datetime',
                        'descending' => false,
                    ],
                ],
            ],
        ];

        // 3) Fetch events
        $eventsResponse = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", $requestBody);

        if ($eventsResponse->failed()) {
            throw new \Exception('Events search failed');
        }

        $data = $eventsResponse->json();
        $rows = $data['EventCollection']['rows'] ?? [];
        $records = $this->transformRecords($rows);

        return [
            'from' => $startDate,
            'to' => $endDate,
            'rows' => $records,
        ];
    }

    public function getShiftPointagetrue(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        // ------------------------------------------------------------
        // 1) SHIFT TIME RANGES
        // ------------------------------------------------------------
        switch (strtoupper($shift)) {

            /*         case 'A': // 22:00 → 07:00 next day
                        $start = $date . 'T22:00:00.00Z';
                        $end   = date('Y-m-d', strtotime($date . ' +1 day')) . 'T07:00:00.00Z';
                        break;

                    case 'B': // 07:00 → 13:00
                        $start = $date . 'T07:00:00.00Z';
                        $end   = $date . 'T13:00:00.00Z';
                        break;

                    case 'C': // 13:00 → 22:00
                        $start = $date . 'T13:00:00.00Z';
                        $end   = $date . 'T22:00:00.00Z';
                        break;

                    case 'ADMIN': // 06:30 → 16:00
                        $start = $date . 'T06:30:00.00Z';
                        $end   = $date . 'T18:00:00.00Z';
                        break;
                    case 'DAY': // 06:30 → 16:00
                        $start = $date . 'T00:00:00.00Z';
                        $end   = $date . 'T23:59:59.99Z';
                        break; */
            case 'A': // 22:00 → 07:00 next day
                $start = $date . 'T22:30:00.00Z';
                $end = date('Y-m-d', strtotime($date . ' +1 day')) . 'T07:30:00.00Z';
                break;

            case 'B': // 07:00 → 13:00
                $start = $date . 'T07:30:00.00Z';
                $end = $date . 'T13:30:00.00Z';
                break;

            case 'C': // 13:00 → 22:00
                $start = $date . 'T13:30:00.00Z';
                $end = $date . 'T22:30:00.00Z';
                break;

            case 'ADMIN': // 06:30 → 16:00
                $start = $date . 'T06:30:00.00Z';
                $end = $date . 'T18:00:00.00Z';
                break;
            case 'DAY':
                $start = Carbon::parse($date)->subHours(5)->format('Y-m-d\TH:i:s.00\Z');
                $end = Carbon::parse($date)->addDays(1)->addHours(6)->format('Y-m-d\TH:i:s.00\Z');
                break;

            default:
                throw new \Exception("Invalid shift: $shift. Use A, B, C or ADMIN.");
        }

        // ------------------------------------------------------------
        // 2) LOGIN
        // ------------------------------------------------------------
        $login = Http::withoutVerifying()->post("${baseUrl}/login", [
            'User' => ['login_id' => "admin", 'password' => "Axians2020"]
        ]);

        if ($login->failed()) {
            throw new \Exception("Login failed");
        }

        $sessionId = $login->header('bs-session-id');
        if (!$sessionId)
            throw new \Exception("Missing session id");


        // ------------------------------------------------------------
        // 3) FETCH RAW EVENTS FROM API
        // ------------------------------------------------------------
        $response = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", [
                'Query' => [
                    'limit' => 3000000,
                    'conditions' => [
                        [
                            'column' => 'datetime',
                            'operator' => 3,
                            'values' => [$start, $end],
                        ],
                    ],
                    'orders' => [
                        ['column' => 'datetime', 'descending' => false]
                    ],
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception("Events fetch failed");
        }


        $rawRows = $response->json()['EventCollection']['rows'] ?? [];


        // ------------------------------------------------------------
        // 4) NORMALIZE ROWS (clean output)
        // ------------------------------------------------------------
        $clean = [];
        $allowedDeviceIds = [
            546077187,
            546077176,
            544195154,
            544195149,
            544195155,
            544195159,
            544184864,
            544184865,
            544184870,
            544184868,
            544184859,
            544184869,
            544184860,
            544184861,
            1619819011,
            1619819000,
        ];

        //dd($response->json()['EventCollection']);
        $cleand = $this->transformRecords($rawRows);

        $clean = array_filter($cleand, function ($row) use ($allowedDeviceIds) {
            return in_array($row['device_id'], $allowedDeviceIds);
        });


        // ------------------------------------------------------------
        // 5) DETECT USERS STILL INSIDE (> 10 hours)
        // ------------------------------------------------------------
        $grouped = [];
        foreach ($clean as $row) {
            $grouped[$row['id']][] = $row;
        }

        $alerts = [];

        foreach ($grouped as $id => $events) {

            // Sort events chronologically
            usort($events, function ($a, $b) {
                return strtotime($a['date'] . ' ' . $a['hour']) <=> strtotime($b['date'] . ' ' . $b['hour']);
            });

            $lastIN = null;
            $hasOUT = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $lastIN = $ev;
                }
                if ($ev['action'] === 'OUT') {
                    $hasOUT = true;
                    $lastIN = null;
                }
            }

            // Check overtime (only if IN exists & no OUT)
            if ($lastIN && !$hasOUT) {
                $inTime = strtotime($lastIN['date'] . ' ' . $lastIN['hour']);
                $hoursInside = (time() - $inTime) / 3600;

                if ($hoursInside >= 10) {
                    $alerts[] = [
                        'id' => $id,
                        'since' => $lastIN['date'] . ' ' . $lastIN['hour'],
                        'hours_inside' => round($hoursInside, 2),
                    ];
                }
            }
        }


        // ------------------------------------------------------------
        // RETURN EVERYTHING
        // ------------------------------------------------------------
        return [
            'shift' => $shift,
            'from' => $start,
            'to' => $end,
            'events' => $clean,
            'alerts' => $alerts,  // Users inside more than 10h
        ];
    }

    public function getShiftPointagetrue1(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        // ------------------------------------------------------------
        // 1) SHIFT TIME RANGES (UPDATED)
        // ------------------------------------------------------------
        switch (strtoupper($shift)) {

            case 'A': // 22:30 → 07:30 next day
                $start = $date . 'T22:30:00.00Z';
                $end = date('Y-m-d', strtotime($date . ' +1 day')) . 'T07:30:00.00Z';

                $shiftStart = Carbon::parse("$date 22:30");
                $shiftEnd = Carbon::parse("$date 07:30")->addDay();
                break;

            case 'B': // 07:30 → 13:30
                $start = $date . 'T07:30:00.00Z';
                $end = $date . 'T13:30:00.00Z';

                $shiftStart = Carbon::parse("$date 07:30");
                $shiftEnd = Carbon::parse("$date 13:30");
                break;

            case 'C': // 13:30 → 22:30
                $start = $date . 'T13:30:00.00Z';
                $end = $date . 'T22:30:00.00Z';

                $shiftStart = Carbon::parse("$date 13:30");
                $shiftEnd = Carbon::parse("$date 22:30");
                break;

            case 'ADMIN': // 06:30 → 18:00
                $start = $date . 'T06:00:00.00Z';
                $end = $date . 'T16:30:00.00Z';

                $shiftStart = Carbon::parse("$date 06:30");
                $shiftEnd = Carbon::parse("$date 18:00");
                break;

            case 'DAY': // from -5h to +1 day +6h
                //$start = Carbon::parse($date)->subHours(0)->format('Y-m-d\TH:i:s.00\Z');
                //$end   = Carbon::parse($date)->addDays(0)->addHours(value: 0)->format('Y-m-d\TH:i:s.00\Z');
                $shiftStart = Carbon::parse($date)->subHours(5);
                $shiftEnd = Carbon::parse($date)->addDays(1)->addHours(6);
                $start = $date . 'T00:00:00.00Z';
                $end = $date . 'T23:59:59.00Z';
                $shiftStart = Carbon::parse("$date 00:00");
                $shiftEnd = Carbon::parse("$date 23:59");
                break;

            default:
                throw new \Exception("Invalid shift: $shift. Use A, B, C, ADMIN or DAY.");
        }

        // ------------------------------------------------------------
        // 2) LOGIN
        // ------------------------------------------------------------
        $login = Http::withoutVerifying()->post("${baseUrl}/login", [
            'User' => ['login_id' => "admin", 'password' => "Axians2020"]
        ]);

        if ($login->failed()) {
            throw new \Exception("Login failed");
        }

        $sessionId = $login->header('bs-session-id');
        if (!$sessionId)
            throw new \Exception("Missing session id");


        // ------------------------------------------------------------
        // 3) FETCH RAW EVENTS
        // ------------------------------------------------------------
        $response = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", [
                'Query' => [
                    'limit' => 3000000,
                    'conditions' => [
                        [
                            'column' => 'datetime',
                            'operator' => 3,
                            'values' => [$start, $end],
                        ],
                    ],
                    'orders' => [
                        ['column' => 'datetime', 'descending' => false]
                    ],
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception("Events fetch failed");
        }

        $rawRows = $response->json()['EventCollection']['rows'] ?? [];

        //dd($start, $end,$rawRows);

        // ------------------------------------------------------------
        // 4) NORMALIZE ROWS
        // ------------------------------------------------------------
        $allowedDeviceIds = [
            546077187,
            546077176,
            544195154,
            544195149,
            544195155,
            544195159,
            544184864,
            544184865,
            544184870,
            544184868,
            544184859,
            544184869,
            544184860,
            544184861,
            1619819011,
            1619819000,
        ];

        $cleand = $this->transformRecords($rawRows);

        $clean = array_filter($cleand, function ($row) use ($allowedDeviceIds) {
            return in_array($row['device_id'], $allowedDeviceIds);
        });


        // ------------------------------------------------------------
        // 5) GROUP EVENTS BY USER
        // ------------------------------------------------------------
        $grouped = [];
        foreach ($clean as $row) {
            $grouped[$row['id']][] = $row;
        }

        // ------------------------------------------------------------
        // 6) SHIFT ATTENDANCE + INSIDE STATUS + PREVIOUS SHIFT ALERT
        // ------------------------------------------------------------
        $attendance = [];
        $insideOver10h = [];

        foreach ($grouped as $id => $events) {

            // Sort events chronologically
            usort($events, function ($a, $b) {
                return strtotime($a['date'] . ' ' . $a['hour'])
                    <=> strtotime($b['date'] . ' ' . $b['hour']);
            });

            // Last action determines INSIDE NOW
            $last = end($events);
            $insideNow = ($last['action'] === 'IN');

            // ---------- A) First IN inside shift ----------
            $firstIn = null;
            $attended = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->between($shiftStart, $shiftEnd)) {
                        $attended = true;
                        $firstIn = $ev;
                        break;
                    }
                }
            }

            // ---------- B) Last OUT inside shift ----------
            $lastOut = null;
            foreach ($events as $ev) {
                if ($ev['action'] === 'OUT') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->between($shiftStart, $shiftEnd)) {
                        $lastOut = $ev;
                    }
                }
            }

            // ---------- C) Was the user INSIDE BEFORE shift started? ----------
            $insideBeforeShift = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->lt($shiftStart)) {

                        // Check OUT before shift start
                        $hasOutBefore = collect($events)->contains(function ($e) use ($shiftStart) {
                            return $e['action'] === 'OUT' &&
                                Carbon::parse($e['date'] . ' ' . $e['hour'])->lt($shiftStart);
                        });

                        if (!$hasOutBefore) {
                            $insideBeforeShift = true;
                        }
                    }
                }
            }

            // ---------- D) Build attendance record ----------
            $attendance[$id] = [
                'id' => $id,
                'inside' => $insideNow,
                'attended' => $attended,
                'first_in' => $firstIn ? $firstIn['date'] . ' ' . $firstIn['hour'] : null,
                'last_out' => $lastOut ? $lastOut['date'] . ' ' . $lastOut['hour'] : null,
                'alert' => ($insideBeforeShift && !$attended)
                    ? "Still inside from previous shift"
                    : null
            ];

            // ---------- E) Overtime: inside > 10h ----------
            $lastIN = null;
            $hasOUT = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN')
                    $lastIN = $ev;
                if ($ev['action'] === 'OUT') {
                    $hasOUT = true;
                    $lastIN = null;
                }
            }

            if ($lastIN && !$hasOUT) {
                $inTime = strtotime($lastIN['date'] . ' ' . $lastIN['hour']);
                $hoursInside = (time() - $inTime) / 3600;

                if ($hoursInside >= 10) {
                    $insideOver10h[] = [
                        'id' => $id,
                        'since' => $lastIN['date'] . ' ' . $lastIN['hour'],
                        'hours_inside' => round($hoursInside, 2),
                    ];
                }
            }
        }

        // ------------------------------------------------------------
        // RETURN EVERYTHING
        // ------------------------------------------------------------
        return [
            'shift' => $shift,
            'from' => $start,
            'to' => $end,
            'events' => $clean,
            'attendance' => array_values($attendance),
            'alerts' => $insideOver10h,
        ];
    }

    public function getShiftPointagetrue2(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        // ------------------------------------------------------------
        // 1) SHIFT TIME RANGES (UPDATED)
        // ------------------------------------------------------------
        switch (strtoupper($shift)) {

            case 'A': // 22:30 → 07:30 next day
                $start = $date . 'T22:30:00.00Z';
                $end = date('Y-m-d', strtotime($date . ' +1 day')) . 'T07:30:00.00Z';

                $shiftStart = Carbon::parse("$date 22:30");
                $shiftEnd = Carbon::parse("$date 07:30")->addDay();
                break;

            case 'B': // 07:30 → 13:30
                $start = $date . 'T07:30:00.00Z';
                $end = $date . 'T13:30:00.00Z';

                $shiftStart = Carbon::parse("$date 07:30");
                $shiftEnd = Carbon::parse("$date 13:30");
                break;

            case 'C': // 13:30 → 22:30
                $start = $date . 'T13:30:00.00Z';
                $end = $date . 'T22:30:00.00Z';

                $shiftStart = Carbon::parse("$date 13:30");
                $shiftEnd = Carbon::parse("$date 22:30");
                break;

            case 'ADMIN': // 06:30 → 18:00
                $start = $date . 'T06:00:00.00Z';
                $end = $date . 'T16:30:00.00Z';

                $shiftStart = Carbon::parse("$date 06:30");
                $shiftEnd = Carbon::parse("$date 18:00");
                break;

            case 'DAY': // full day
                $shiftStart = Carbon::parse("$date 00:00");
                $shiftEnd = Carbon::parse("$date 23:59");

                $start = $date . 'T00:00:00.00Z';
                $end = $date . 'T23:59:59.00Z';
                break;
            case '2DAY':
                $start = Carbon::parse($date)->subHours(5)->format('Y-m-d\TH:i:s.00\Z');
                $end = Carbon::parse($date)->addDays(1)->addHours(6)->format('Y-m-d\TH:i:s.00\Z');

                $shiftStart = Carbon::parse("$date 06:30")->subHours(5);
                $shiftEnd = Carbon::parse("$date 18:00")->addDays(1);
                break;

            default:
                throw new \Exception("Invalid shift: $shift. Use A, B, C, ADMIN or DAY.");
        }

        // ------------------------------------------------------------
        // 2) LOGIN
        // ------------------------------------------------------------
        $login = Http::withoutVerifying()->post("${baseUrl}/login", [
            'User' => ['login_id' => "admin", 'password' => "Axians2020"]
        ]);

        if ($login->failed()) {
            throw new \Exception("Login failed");
        }

        $sessionId = $login->header('bs-session-id');
        if (!$sessionId)
            throw new \Exception("Missing session id");


        // ------------------------------------------------------------
        // 3) FETCH RAW EVENTS
        // ------------------------------------------------------------
        $response = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", [
                'Query' => [
                    'limit' => 3000000,
                    'conditions' => [
                        [
                            'column' => 'datetime',
                            'operator' => 3,
                            'values' => [$start, $end],
                        ],
                    ],
                    'orders' => [
                        ['column' => 'datetime', 'descending' => false]
                    ],
                ]
            ]);

        if ($response->failed()) {
            throw new \Exception("Events fetch failed");
        }

        $rawRows = $response->json()['EventCollection']['rows'] ?? [];

        // ------------------------------------------------------------
        // 4) NORMALIZE ROWS
        // ------------------------------------------------------------
        $allowedDeviceIds = [
            546077187,
            546077176,
            544195154,
            544195149,
            544195155,
            544195159,
            544184864,
            544184865,
            544184870,
            544184868,
            544184859,
            544184869,
            544184860,
            544184861,
            1619819011,
            1619819000,
        ];

        $cleand = $this->transformRecords($rawRows);

        $clean = array_filter($cleand, function ($row) use ($allowedDeviceIds) {
            return in_array($row['device_id'], $allowedDeviceIds);
        });

        // ------------------------------------------------------------
        // 5) GROUP EVENTS BY USER
        // ------------------------------------------------------------
        $grouped = [];
        foreach ($clean as $row) {
            $grouped[$row['id']][] = $row;
        }

        // ------------------------------------------------------------
        // 6) SHIFT ATTENDANCE + INSIDE STATUS + ALERTS
        // ------------------------------------------------------------
        $attendance = [];
        $insideOver10h = [];

        foreach ($grouped as $id => $events) {

            // Sort events chronologically
            usort($events, function ($a, $b) {
                return strtotime($a['date'] . ' ' . $a['hour'])
                    <=> strtotime($b['date'] . ' ' . $b['hour']);
            });

            // Last action determines INSIDE NOW
            $last = end($events);
            $insideNow = ($last['action'] === 'IN');

            // ---------- A) First IN inside shift ----------
            $firstIn = null;
            $attended = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->between($shiftStart, $shiftEnd)) {
                        $attended = true;
                        $firstIn = $ev;
                        break;
                    }
                }
            }

            // ---------- B) Last OUT inside shift ----------
            $lastOut = null;
            foreach ($events as $ev) {
                if ($ev['action'] === 'OUT') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->between($shiftStart, $shiftEnd)) {
                        $lastOut = $ev;
                    }
                }
            }

            // ---------- C) Was user inside before shift started? ----------
            $insideBeforeShift = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $t = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                    if ($t->lt($shiftStart)) {

                        // Check OUT before shift start
                        $hasOutBefore = collect($events)->contains(function ($e) use ($shiftStart) {
                            return $e['action'] === 'OUT' &&
                                Carbon::parse($e['date'] . ' ' . $e['hour'])->lt($shiftStart);
                        });

                        if (!$hasOutBefore) {
                            $insideBeforeShift = true;
                        }
                    }
                }
            }

            // ---------- NEW LOGIC: Still inside because NO OUT after last IN ----------
            $lastIN = null;
            $hasOutAfterLastIn = false;

            foreach ($events as $ev) {

                if ($ev['action'] === 'IN') {
                    $lastIN = $ev;
                    $hasOutAfterLastIn = false;
                }

                if ($ev['action'] === 'OUT' && $lastIN) {
                    $tOut = strtotime($ev['date'] . ' ' . $ev['hour']);
                    $tIn = strtotime($lastIN['date'] . ' ' . $lastIN['hour']);

                    if ($tOut > $tIn) {
                        $hasOutAfterLastIn = true;
                    }
                }
            }

            $stillInsideAfterLastIN = ($lastIN && !$hasOutAfterLastIn);

            // ---------- D) Build attendance record ----------
            $attendance[$id] = [
                'id' => $id,
                'inside' => $insideNow,
                'attended' => $attended,
                'first_in' => $firstIn ? $firstIn['date'] . ' ' . $firstIn['hour'] : null,
                'last_out' => $lastOut ? $lastOut['date'] . ' ' . $lastOut['hour'] : null,
                'alert' =>
                    ($insideBeforeShift && !$attended)
                    ? "Still inside from previous shift"
                    : (
                        $stillInsideAfterLastIN
                        ? "Still inside (no OUT after last IN)"
                        : null
                    )
            ];

            // ---------- E) Overtime: inside > 10h ----------
            $lastIN2 = null;
            $hasOUT2 = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN')
                    $lastIN2 = $ev;
                if ($ev['action'] === 'OUT') {
                    $hasOUT2 = true;
                    $lastIN2 = null;
                }
            }

            if ($lastIN2 && !$hasOUT2) {
                $inTime = strtotime($lastIN2['date'] . ' ' . $lastIN2['hour']);
                $hoursInside = (time() - $inTime) / 3600;

                if ($hoursInside >= 10) {
                    $insideOver10h[] = [
                        'id' => $id,
                        'since' => $lastIN2['date'] . ' ' . $lastIN2['hour'],
                        'hours_inside' => round($hoursInside, 2),
                    ];
                }
            }
        }

        // ------------------------------------------------------------
        // RETURN EVERYTHING
        // ------------------------------------------------------------
        return [
            'shift' => $shift,
            'from' => $start,
            'to' => $end,
            'events' => $clean,
            'attendance' => array_values($attendance),
            'alerts' => $insideOver10h,
        ];
    }

    public function getShiftPointagetrue3(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');
    
        $day      = Carbon::parse($date);
        $yesterday = $day->copy()->subDay()->toDateString();
    
        // ------------------------------------------------------------
        // 1) SHIFT TIME RANGES (SAME AS BEFORE)
        // ------------------------------------------------------------
        switch (strtoupper($shift)) {
    
            case 'A': // 22:30 → 07:30 next day
                $shiftStart = Carbon::parse("$date 22:30");
                $shiftEnd   = Carbon::parse("$date 07:30")->addDay();
                break;
    
            case 'B': // 07:30 → 13:30
                $shiftStart = Carbon::parse("$date 07:30");
                $shiftEnd   = Carbon::parse("$date 13:30");
                break;
    
            case 'C': // 13:30 → 22:30
                $shiftStart = Carbon::parse("$date 13:30");
                $shiftEnd   = Carbon::parse("$date 22:30");
                break;
    
            case 'ADMIN': // 06:30 → 18:00
                $shiftStart = Carbon::parse("$date 06:30");
                $shiftEnd   = Carbon::parse("$date 18:00");
                break;
    
            case 'DAY': // full day (with custom window logic handled later if needed)
                $shiftStart = Carbon::parse("$date 00:00");
                $shiftEnd   = Carbon::parse("$date 23:59:59");
                break;
    
            default:
                throw new \Exception("Invalid shift: $shift. Use A, B, C, ADMIN or DAY.");
        }
    
        // ------------------------------------------------------------
        // 2) 48H WINDOW (YESTERDAY 00:00 → TODAY 23:59)
        // ------------------------------------------------------------
        $windowStartCarbon = $day->copy()->subDay()->startOfDay(); // yesterday 00:00
        $windowEndCarbon   = $day->copy()->endOfDay();             // today 23:59:59
    
        $windowStart = $windowStartCarbon->format('Y-m-d\T00:00:00.00\Z');
        $windowEnd   = $windowEndCarbon->format('Y-m-d\T23:59:59.99\Z');
    
        // ------------------------------------------------------------
        // 3) LOGIN
        // ------------------------------------------------------------
        $login = Http::withoutVerifying()->post("${baseUrl}/login", [
            'User' => ['login_id' => "admin", 'password' => "Axians2020"]
        ]);
    
        if ($login->failed()) {
            throw new \Exception("Login failed");
        }
    
        $sessionId = $login->header('bs-session-id');
        if (!$sessionId) {
            throw new \Exception("Missing session id");
        }
    
        // ------------------------------------------------------------
        // 4) FETCH RAW EVENTS (48H)
        // ------------------------------------------------------------
        $response = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", [
                'Query' => [
                    'limit' => 3000000,
                    'conditions' => [
                        [
                            'column'   => 'datetime',
                            'operator' => 3,
                            'values'   => [$windowStart, $windowEnd],
                        ],
                    ],
                    'orders' => [
                        ['column' => 'datetime', 'descending' => false]
                    ],
                ]
            ]);
    
        if ($response->failed()) {
            throw new \Exception("Events fetch failed");
        }
    
        $rawRows = $response->json()['EventCollection']['rows'] ?? [];
    
        // ------------------------------------------------------------
        // 5) NORMALIZE ROWS + FILTER DEVICES
        // ------------------------------------------------------------
        $allowedDeviceIds = [
            546077187, 546077176, 544195154, 544195149, 544195155,
            544195159, 544184864, 544184865, 544184870, 544184868,
            544184859, 544184869, 544184860, 544184861,
            1619819011, 1619819000,
        ];
    
        $normalized = $this->transformRecords($rawRows);
    
        $events48 = array_values(array_filter($normalized, function ($row) use ($allowedDeviceIds) {
            return in_array($row['device_id'], $allowedDeviceIds);
        }));
    
        // Ensure chronological sort
        usort($events48, function ($a, $b) {
            $ta = strtotime($a['date'] . ' ' . $a['hour']);
            $tb = strtotime($b['date'] . ' ' . $b['hour']);
            return $ta <=> $tb;
        });
    
        // ------------------------------------------------------------
        // 6) GROUP EVENTS BY USER
        // ------------------------------------------------------------
        $byUser = [];
        foreach ($events48 as $row) {
            $userId = $row['id']; // assuming 'id' is user_id
            $byUser[$userId][] = $row;
        }
    
        // ------------------------------------------------------------
        // 7) BUILD SESSIONS PER USER (DEVICE MATCHING RULE)
        // ------------------------------------------------------------
        $now = Carbon::now();
        $results = []; // only ALERT/WARNING entries
    
        foreach ($byUser as $userId => $userEvents) {
    
            // sort again just in case
            usort($userEvents, function ($a, $b) {
                $ta = strtotime($a['date'] . ' ' . $a['hour']);
                $tb = strtotime($b['date'] . ' ' . $b['hour']);
                return $ta <=> $tb;
            });
    
            $sessions       = [];  // complete or open sessions
            $outOnlyEvents  = [];  // OUT without open session
            $hasIn          = false;
            $hasOut         = false;
    
            $open = null;
    
            foreach ($userEvents as $ev) {
                $dt       = Carbon::parse($ev['date'] . ' ' . $ev['hour']);
                $action   = strtoupper($ev['action']);
                $deviceId = $ev['device_id'];
    
                if ($action === 'IN') {
                    $hasIn = true;
    
                    // Double IN closes previous session
                    if ($open) {
                        $open['end']      = $dt;
                        $open['end_raw']  = $ev;
                        $open['closed_by'] = 'DOUBLE_IN';
                        $sessions[] = $open;
                    }
    
                    $open = [
                        'user_id'         => $userId,
                        'start'           => $dt,
                        'start_raw'       => $ev,
                        'device_id'       => $deviceId,
                        'end'             => null,
                        'end_raw'         => null,
                        'closed_by'       => null,
                        'wrong_device_out'=> false,
                    ];
                } elseif ($action === 'OUT') {
                    $hasOut = true;
    
                    // OUT without any open IN → OUT_ONLY situation candidate
                    if (!$open) {
                        $outOnlyEvents[] = [
                            'dt'    => $dt,
                            'event' => $ev,
                        ];
                        continue;
                    }
    
                    // DEVICE MATCHING RULE
                    if ($deviceId !== $open['device_id']) {
                        // OUT on wrong device is ignored, but we mark it
                        $open['wrong_device_out'] = true;
                        continue;
                    }
    
                    // First valid OUT after IN closes the session
                    if ($dt->gt($open['start'])) {
                        $open['end']      = $dt;
                        $open['end_raw']  = $ev;
                        $open['closed_by'] = 'OUT';
                        $sessions[] = $open;
                        $open = null; // further OUTs will become OUT_ONLY
                    }
                }
            }
    
            // Last open session without OUT
            if ($open) {
                $sessions[] = $open;
            }
    
            // --------------------------------------------------------
            // 8) APPLY TIME WINDOW + SHIFT RULES ON SESSIONS
            // --------------------------------------------------------
            // Filter sessions that intersect the shift window
            $sessionsInShift = [];
            foreach ($sessions as $s) {
                $sessionStart = $s['start'];
                $sessionEnd   = $s['end'] ?? $now;
    
                // session overlaps shift
                $overlaps = $sessionStart->lt($shiftEnd) && $sessionEnd->gt($shiftStart);
                if ($overlaps) {
                    $sessionsInShift[] = $s;
                }
            }
    
            // If there is no activity at all for this user in 48h,
            // we *could* flag NO_IN_NO_OUT, but we don't even know
            // such user exists here. So we only handle known users.
    
            // --------------------------------------------------------
            // 9) DETECT ALERTS & WARNINGS FOR THIS USER
            // --------------------------------------------------------
            $type   = 'OK';
            $reason = null;
            $detail = null;
    
            // Helper: last open session (no OUT)
            $lastOpenSession = null;
            foreach (array_reverse($sessions) as $s) {
                if (!$s['end']) {
                    $lastOpenSession = $s;
                    break;
                }
            }
    
            // Helper flags
            $yesterdayInOpen = false;
            $shiftEndedStillInside = false;
            $insideTooLong = false;
            $wrongDeviceProblem = false;
    
            if ($lastOpenSession) {
    
                $inTime   = $lastOpenSession['start'];
                $duration = $inTime->diffInHours($now);
                $insideTooLong = $duration >= 10;
    
                // ALERT 2: IN yesterday + NO OUT today
                if ($inTime->toDateString() === $yesterday && !$lastOpenSession['end']) {
                    $yesterdayInOpen = true;
                }
    
                // ALERT 3: Shift ended but still inside
                if ($now->gt($shiftEnd) && $inTime->lt($shiftEnd) && !$lastOpenSession['end']) {
                    $shiftEndedStillInside = true;
                }
    
                // ALERT 4: Wrong OUT device (we saw OUT with wrong device)
                if ($lastOpenSession['wrong_device_out']) {
                    $wrongDeviceProblem = true;
                }
            }
    
            // WARNING 1: OUT_ONLY (no IN in 48h but we have OUT)
            $hasOutOnly = (!$hasIn && $hasOut) || (!empty($outOnlyEvents) && !$hasIn);
    
            // WARNING 3: OUT at shift boundary without IN
            $outShiftEdge = false;
            if ($hasOutOnly) {
                foreach ($outOnlyEvents as $oe) {
                    $dt = $oe['dt'];
                    if ($dt->between($shiftStart, $shiftEnd)) {
                        $outShiftEdge = true;
                        break;
                    }
                }
            }
    
            // WARNING 2: INSIDE but < 10h (open session)
            $insideShort = false;
            if ($lastOpenSession && !$insideTooLong) {
                $insideShort = true;
            }
    
            // --------------------------------------------------------
            // 10) PRIORITY OF RULES
            // --------------------------------------------------------
    
            // 🔥 ALERTS (Critical)
            if ($lastOpenSession && $insideTooLong) {
                $type   = 'ALERT';
                $reason = $lastOpenSession['wrong_device_out'] ? 'WRONG_DEVICE_OUT' : 'INSIDE_TOO_LONG';
                $detail = $lastOpenSession['wrong_device_out']
                    ? "User $userId has an open session ≥10h with OUT on a different device – still considered inside."
                    : "User $userId has been inside for {$lastOpenSession['start']->diffInHours($now)} hours without OUT.";
            } elseif ($yesterdayInOpen) {
                $type   = 'ALERT';
                $reason = 'YESTERDAY_IN';
                $detail = "User $userId badged IN yesterday and still has no OUT today.";
            } elseif ($shiftEndedStillInside) {
                $type   = 'ALERT';
                $reason = 'SHIFT_ENDED_STILL_INSIDE';
                $detail = "User $userId is still inside although shift $shift has ended.";
            } elseif ($lastOpenSession && $lastOpenSession['wrong_device_out']) {
                // wrong device but <10h
                $type   = 'ALERT';
                $reason = 'WRONG_DEVICE_OUT';
                $detail = "User $userId has an open session where OUT occurred on a different device (ignored).";
            }
            // 🔥 ALERT 5 (NO_IN_NO_OUT) not implementable here because
            // we only see users with at least one event in 48h.
    
            // ⚠ WARNINGS (only if no ALERT)
            if ($type === 'OK') {
    
                if ($hasOutOnly) {
                    $type   = 'WARNING';
                    $reason = $outShiftEdge ? 'OUT_SHIFT_EDGE' : 'OUT_ONLY';
                    $detail = $outShiftEdge
                        ? "User $userId has an OUT at the shift boundary without a matching IN in the last 48h (likely IN before window)."
                        : "User $userId has OUT events but no IN in the last 48h (likely entered before this window).";
                } elseif ($insideShort && $lastOpenSession) {
                    $type   = 'WARNING';
                    $reason = 'INSIDE';
                    $detail = "User $userId is currently inside (open session <10h, no OUT yet).";
                }
            }
    
            if ($type !== 'OK') {
                $results[] = [
                    'user_id' => $userId,
                    'type'    => $type,               // ALERT | WARNING
                    'reason'  => $reason,             // CODE
                    'detail'  => $detail,             // Human readable message
                ];
            }
        }
    
        // ------------------------------------------------------------
        // FINAL RETURN
        // ------------------------------------------------------------
        return [
            'shift'   => $shift,
            'from'    => $windowStart,
            'to'      => $windowEnd,
            'results' => $results,   // only ALERTS & WARNINGS
            // optional: you can also return raw events if you need for debug
            'events'  => $events48,
        ];
    }

    public function getShiftPointage(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');
    
        $today = Carbon::parse($date);
        $yesterday = $today->copy()->subDay();
        $now = Carbon::now();
    
        // ------------------ SHIFT WINDOWS ------------------
        switch (strtoupper($shift)) {
            case 'A': $shiftStart = Carbon::parse("$date 22:30"); $shiftEnd = Carbon::parse("$date 07:30")->addDay(); break;
            case 'B': $shiftStart = Carbon::parse("$date 07:30"); $shiftEnd = Carbon::parse("$date 13:30"); break;
            case 'C': $shiftStart = Carbon::parse("$date 13:30"); $shiftEnd = Carbon::parse("$date 22:30"); break;
            case 'ADMIN': $shiftStart = Carbon::parse("$date 06:30"); $shiftEnd = Carbon::parse("$date 18:00"); break;
            case 'DAY': $shiftStart = Carbon::parse("$date 00:00"); $shiftEnd = Carbon::parse("$date 23:59:59"); break;
            default:
                return ["error" => "Invalid shift"];
        }
    
        $start = $shiftStart->format("Y-m-d H:i:s");
        $end   = $shiftEnd->format("Y-m-d H:i:s");
    
        // ------------------ LOGIN ------------------
        $login = Http::withoutVerifying()->post("${baseUrl}/login", [
            'User' => ['login_id' => "admin", 'password' => "Axians2020"]
        ]);
    
        if ($login->failed()) return ["error" => "Login failed"];
        $sessionId = $login->header('bs-session-id');
    
        // ------------------ FETCH 24H + 24H ------------------
        $fetch = function($start, $end) use ($sessionId, $baseUrl) {
            $r = Http::withoutVerifying()
                ->withHeaders(['bs-session-id' => $sessionId])
                ->post("${baseUrl}/events/search", [
                    "Query" => [
                        "limit" => 900000,
                        "conditions" => [
                            ["column" => "datetime", "operator" => 3, "values" => [$start, $end]],
                        ],
                        "orders" => [["column" => "datetime", "descending" => false]]
                    ]
                ]);
    
            return $r->json()['EventCollection']['rows'] ?? [];
        };
    
        // Yesterday window
        $rowsYesterday = $fetch(
            $yesterday->startOfDay()->format('Y-m-d\T00:00:00.00\Z'),
            $yesterday->endOfDay()->format('Y-m-d\T23:59:59.99\Z')
        );
    
        // Today window
        $rowsToday = $fetch(
            $today->startOfDay()->format('Y-m-d\T00:00:00.00\Z'),
            $today->endOfDay()->format('Y-m-d\T23:59:59.99\Z')
        );
    
        $rawRows = array_merge($rowsYesterday, $rowsToday);
    
        // ------------------ NORMALIZE + FILTER DEVICES ------------------
        $allowedDeviceIds = [
            546077187, 546077176, 544195154, 544195149, 544195155,
            544195159, 544184864, 544184865, 544184870, 544184868,
            544184859, 544184869, 544184860, 544184861,
            1619819011, 1619819000,
        ];
    
        $normalized = $this->transformRecords($rawRows);
    
        $clean = array_values(array_filter($normalized, fn($row) =>
            in_array($row['device_id'], $allowedDeviceIds)
        ));
    
        usort($clean, fn($a,$b) =>
            strtotime("$a[date] $a[hour]") <=> strtotime("$b[date] $b[hour]")
        );
    
        // ------------------ GROUP BY USER ------------------
        $grouped = [];
        foreach ($clean as $row) $grouped[$row['id']][] = $row;
    
        $attendance = [];
        $alerts = [];
    
        // ------------------ APPLY ALERT LOGIC ------------------
        foreach ($grouped as $userId => $events) {
    
            usort($events, fn($a,$b) =>
                strtotime("$a[date] $a[hour]") <=> strtotime("$b[date] $b[hour]")
            );
    
            $open = null;
            $sessions = [];
            $outOnly = [];
    
            foreach ($events as $ev) {
                $dt = Carbon::parse("$ev[date] $ev[hour]");
                $device = $ev['device_id'];
                $action = strtoupper($ev['action']);
    
                if ($action === 'IN') {
                    if ($open) {
                        $open['end'] = $dt;
                        $sessions[] = $open;
                    }
                    $open = [
                        "start" => $dt,
                        "device" => $device,
                        "end" => null,
                        "wrong_out" => false
                    ];
                }
    
                if ($action === 'OUT') {
                    if (!$open) {
                        $outOnly[] = $ev;
                        continue;
                    }
    
                    if ($device !== $open['device']) {
                        $open['wrong_out'] = true;
                        continue;
                    }
    
                    $open['end'] = $dt;
                    $sessions[] = $open;
                    $open = null;
                }
            }
    
            if ($open) $sessions[] = $open;
    
            // Evaluate last open session
            $lastOpen = null;
            foreach (array_reverse($sessions) as $s) {
                if (!$s['end']) { $lastOpen = $s; break; }
            }
    
            // ------------------ ALERT RULES ------------------
            if ($lastOpen) {
                $inTime = $lastOpen['start'];
                $hours = $inTime->diffInHours($now);
    
                $yesterdayIn = $inTime->toDateString() === $yesterday->toDateString();
                $shiftEnded = $inTime->lt($shiftEnd) && $now->gt($shiftEnd);
    
                if ($hours >= 10) {
                    $alerts[] = [
                        "id" => $userId,
                        "reason" => "INSIDE_TOO_LONG",
                        "since" => $inTime->toDateTimeString(),
                        "hours" => $hours
                    ];
                }
                elseif ($yesterdayIn) {
                    $alerts[] = [
                        "id" => $userId,
                        "reason" => "YESTERDAY_IN",
                        "since" => $inTime->toDateTimeString(),
                    ];
                }
                elseif ($lastOpen['wrong_out']) {
                    $alerts[] = [
                        "id" => $userId,
                        "reason" => "WRONG_DEVICE_OUT",
                        "since" => $inTime->toDateTimeString(),
                    ];
                }
                elseif ($shiftEnded) {
                    $alerts[] = [
                        "id" => $userId,
                        "reason" => "SHIFT_ENDED_STILL_INSIDE",
                        "since" => $inTime->toDateTimeString(),
                    ];
                }
    
                $attendance[$userId] = [
                    'id' => $userId,
                    'inside' => true,
                    'first_in' => $inTime->toDateTimeString(),
                    'last_out' => null,
                    'reason' => $hours >= 10 ? "INSIDE_TOO_LONG" : "INSIDE",
                ];
            }
    
            // OUT-only
            if (!$lastOpen && !empty($outOnly)) {
                $attendance[$userId] = [
                    'id' => $userId,
                    'inside' => false,
                    'reason' => "OUT_ONLY"
                ];
            }
        }
    
        // ------------------ FINAL RETURN (YOUR FORMAT) ------------------
        return [
            'shift' => $shift,
            'from' => $start,
            'to' => $end,
            'events' => $clean,
            'attendance' => array_values($attendance),
            'alerts' => $alerts,
        ];
    }
    
    


    public function getRecordsByShift(string $date, string $shift)
    {
        $baseUrl = env('API_BASE_URL', 'https://10.20.191.200:443/api');

        // --- 1) Build shift time ranges ---
        switch (strtoupper($shift)) {
            case 'A': // 22:00 → 07:00 next day
                $startDateTime = $date . 'T22:00:00.00Z';
                $endDateTime = date('Y-m-d', strtotime($date . ' +1 day')) . 'T07:00:00.00Z';
                break;

            case 'B': // 07:00 → 13:00
                $startDateTime = $date . 'T07:00:00.00Z';
                $endDateTime = $date . 'T13:00:00.00Z';
                break;

            case 'C': // 13:00 → 22:00
                $startDateTime = $date . 'T13:00:00.00Z';
                $endDateTime = $date . 'T22:00:00.00Z';
                break;
            case 'ADMIN': // 06:30 → 16:00 (same day)
                $startDateTime = $date . 'T06:30:00.00Z';
                $endDateTime = $date . 'T16:00:00.00Z';
                break;
            default:
                throw new \Exception("Invalid shift: $shift. Use A, B, or C.");
        }

        // --- 2) Login ---
        $loginPayload = [
            'User' => [
                'login_id' => "admin",
                'password' => "Axians2020",
            ],
        ];

        $loginResponse = Http::withoutVerifying()->post("${baseUrl}/login", $loginPayload);

        if ($loginResponse->failed()) {
            throw new \Exception('Login failed');
        }

        $sessionId = $loginResponse->header('bs-session-id');
        if (!$sessionId) {
            throw new \Exception('Missing session ID');
        }

        // --- 3) Build request body using shift range ---
        $requestBody = [
            'Query' => [
                'limit' => 3000000,
                'conditions' => [
                    [
                        'column' => 'datetime',
                        'operator' => 3,
                        'values' => [
                            $startDateTime,
                            $endDateTime,
                        ],
                    ],
                ],
                'orders' => [
                    [
                        'column' => 'datetime',
                        'descending' => false,
                    ],
                ],
            ],
        ];

        // --- 4) Fetch events ---
        $eventsResponse = Http::withoutVerifying()
            ->withHeaders(['bs-session-id' => $sessionId])
            ->post("${baseUrl}/events/search", $requestBody);

        if ($eventsResponse->failed()) {
            throw new \Exception('Events search failed');
        }

        $data = $eventsResponse->json();
        $rows = $data['EventCollection']['rows'] ?? [];
        $records = $this->transformRecords($rows);

        return [
            'shift' => $shift,
            'from' => $startDateTime,
            'to' => $endDateTime,
            'data' => $records,
        ];
    }

    private function detectAction($item)
    {
        $name = strtolower($item['device_name'] ?? '');

        if (str_contains($name, '-in')) {
            return 'IN';
        }

        if (str_contains($name, '-out')) {
            return 'OUT';
        }

        return 'UNKNOWN';
    }
    private function detectOvertime(array $rows)
    {
        $users = [];

        // Group events by user
        foreach ($rows as $r) {
            $id = $r['id'];

            if (!isset($users[$id])) {
                $users[$id] = [
                    'events' => [],
                ];
            }

            $users[$id]['events'][] = $r;
        }

        $notifications = [];

        // Process each user
        foreach ($users as $id => $data) {

            $events = collect($data['events'])
                ->sortBy('date')
                ->sortBy('hour')
                ->values()
                ->all();

            $lastIN = null;
            $hasOUT = false;

            foreach ($events as $ev) {
                if ($ev['action'] === 'IN') {
                    $lastIN = $ev;
                }
                if ($ev['action'] === 'OUT') {
                    $hasOUT = true;
                    $lastIN = null;
                }
            }

            // Only check overtime if no OUT and has an IN
            if ($lastIN && !$hasOUT) {

                $inTime = strtotime($lastIN['date'] . ' ' . $lastIN['hour']);
                $now = time();
                $hoursInside = ($now - $inTime) / 3600;

                if ($hoursInside >= 10) {
                    $notifications[] = [
                        'id' => $id,
                        'since' => $lastIN['date'] . ' ' . $lastIN['hour'],
                        'hours_inside' => round($hoursInside, 2),
                    ];
                }
            }
        }

        return $notifications;
    }

    /**
     * Search records by matricule — filters the transformed data.
     */
    public function searchByMatricule(Request $request)
    {
        $matricule = "0382";
        //$matricule = $request->input('matricule');
        $matchMode = $request->input('match', 'exact'); // exact or contains

        // If you already have data in a variable or cache, you can reuse it.
        // Here we call getRecords() to fetch it again.
        $recordsResponse = $this->getRecords($request);
        $records = $recordsResponse->getData(true)['data'] ?? [];

        $filtered = collect($records)->filter(function ($item) use ($matricule, $matchMode) {
            if (!$matricule)
                return true;
            if ($matchMode === 'contains') {
                return stripos($item['id'], $matricule) !== false;
            }
            return $item['id'] === $matricule;
        })->values()->all();

        return response()->json([
            'status' => 'success',
            'count' => count($filtered),
            'data' => $filtered,
        ]);
    }

    /**
     * Transform the raw rows into the structured output.
     */
    private function transformRecords(array $rows): array
    {
        $result = [];
        $lastRow = '';

        foreach ($rows as $row) {
            $id = $row['user_id']['user_id'] ?? '';
            $badge_place = isset($row['device_id']['name'])
                ? str_replace(' ', '', $row['device_id']['name'])
                : 'null';
            $datetime = $row['datetime'] ?? null;
            $dateStr = $datetime ? date('Y-m-d', strtotime($datetime)) : '';
            $hourStr = $datetime ? substr(date('H:i:s', strtotime($datetime)), 0, 5) : '';
            $deviceId = $row['device_id']['id'] ?? '';
            $tnaKey = $row['tna_key'] ?? null;

            $action = $this->action($tnaKey);

            if ($action !== '') {
                $rowKey = implode("\t", [$id, $badge_place, $dateStr, $hourStr, $deviceId, $action]);
                if ($lastRow !== $rowKey && $id !== '') {
                    $lastRow = $rowKey;

                    $result[] = [
                        'id' => $id,
                        'badge_place' => $badge_place,
                        'date' => $dateStr,
                        'hour' => $hourStr,
                        'device_id' => $deviceId,
                        'action' => $action,
                    ];
                }
            }
        }

        return $result;
    }

    private function action($tnaKey): string
    {
        $map = ['IN' => 'IN', 'OUT' => 'OUT', 1 => 'IN', 2 => 'OUT'];
        return $map[$tnaKey] ?? ($map[(string) $tnaKey] ?? '');
    }


}
