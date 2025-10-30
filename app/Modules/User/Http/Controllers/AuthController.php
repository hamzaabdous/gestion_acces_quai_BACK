<?php

namespace App\Modules\User\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Modules\User\Models\user;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role'     => ['sometimes', 'in:ADMIN,USER'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // hashed by model cast
            'role'     => $data['role'] ?? 'USER',
        ]);

        // Issue a Sanctum token
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username'    => ['required'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        /** @var \App\Models\User $user */
        $user = User::where('username', $credentials['username'])->firstOrFail();

        // Revoke old tokens if you want a single-session policy:
        // $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Revoke the current token only:
        $request->user()->currentAccessToken()->delete();

        // Or revoke all tokens:
        // $user->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}