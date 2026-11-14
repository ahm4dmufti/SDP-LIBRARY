<?php

namespace App\Http\Controllers;

use App\Http\Resources\StaffRES;
use App\Models\staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $staff = staff::where('email', $request->email)->first();

        if ($staff && Hash::check($request->password, $staff->password)) {
            $token = $staff->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    public function me(Request $request)
    {
        return response()->json([
            'staff' => StaffRES::make($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
