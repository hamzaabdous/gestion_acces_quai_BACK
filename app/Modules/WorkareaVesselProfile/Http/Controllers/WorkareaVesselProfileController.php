<?php

namespace App\Modules\WorkareaVesselProfile\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WorkareaVesselProfileController
{

    /**
     * Display the module welcome screen
     *
     * @return \Illuminate\Http\Response
     */
    public function welcome()
    {
        return view("WorkareaVesselProfile::welcome");
    }

    public function fetchVessels()
    {
        // 1️⃣ Send POST request with body
        $response = Http::withoutVerifying()->post('http://wo-cl.tangeralliance.com:9041/getVessel', [
            "sql" => "select * from V_CB_TC3_THIS_SHIFTT_MOVES t"
        ]);

        // 2️⃣ If API failed
        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data from endpoint'
            ], 500);
        }

        // 3️⃣ Get raw result array
        $data = $response->json();

        // 4️⃣ Clean data → only voyage, vessel, service
        $clean = collect($data["datagetvessel"])->map(function ($row) {
            return [
                "voyage" => $row[7] ?? null,
                "vessel_name" => $row[8] ?? null,
                "service" => $row[9] ?? null,
            ];
        });

        // 5️⃣ Return cleaned response
        return response()->json([
            'status' => 200,
            'payload' => $clean,
            'data' => $data,

        ]);
    }

}
