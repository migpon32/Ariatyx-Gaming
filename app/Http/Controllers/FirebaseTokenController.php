<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $request->user()->forceFill([
            'fcm_token' => $validated['token'],
            'fcm_token_updated_at' => now(),
        ])->save();

        return response()->json([
            'saved' => true,
        ]);
    }
}
