<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MobileTestController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // Simple hardcoded check for the "Sandbox" test
        if ($email === 'admin.com' && $password === 'password') {
            return response()->json([
                'success' => true,
                'message' => 'Handshake Successful! Welcome to Celina Engine.',
                'data' => [
                    'token' => 'test_token_' . bin2hex(random_bytes(10)),
                    'user' => [
                        'id' => 1,
                        'name' => 'Chakiii Admin',
                        'role' => 'Superuser',
                        'status' => 'Active'
                    ]
                ]
            ]);
        }

        // Return a 401 for wrong credentials
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials. Check email or password.',
            'data' => null
        ], 401);
    }
}