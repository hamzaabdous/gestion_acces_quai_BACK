<?php

namespace App\Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;

class UserController
{

    /**
     * Display the module welcome screen
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $users = User::all();

        return [
            "payload" => $users,
            "status" => "200"
        ];
    }
}
