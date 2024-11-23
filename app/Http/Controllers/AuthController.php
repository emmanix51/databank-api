<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $request->validate([
            'idnum' => 'required|integer|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:admin,faculty,student,dean,programhead',
            // 'position' => 'nullable|string',
            'password' => 'required|string|confirmed|min:6',
            'year_level'=>'nullable|integer|max:4',
            'college_id' => 'required|exists:colleges,id',  
            'program_id' => 'required|exists:programs,id',
        ]);

        $user = User::create([
            'idnum' => $request->idnum,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
            'position' => $request->position,
            'year_level' => $request->year_level,
            'password' => Hash::make($request->password),
            'college_id' => $request->college_id,
            'program_id' => $request->program_id,
        ]);

        return response()->json(['user' => $user], 201);
    }

    // Login user
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|string|exists:users,email',
            'password' => 'required|string',
        ]);

        
        if (!Auth::attempt($credentials)) {
            return response([
                'error' => 'The provided credentials are not correct'
            ], 422);
        }

        $user = Auth::user();
        $token = $user->createToken('main')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    // Logout user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Optionally: To revoke all tokens for a user
    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens have been revoked']);
    }
}
