<?php

namespace App\Http\Controllers;

use App\Models\UserDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushTokenController extends Controller
{
    /**
     * Store a newly created device push token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $token = $request->token;
        $deviceType = $request->device_type ?? 'web';

        // Associate device token with authenticated user
        UserDeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $userId,
                'device_type' => $deviceType,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Token registered successfully.']);
    }

    /**
     * Unregister a device push token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        UserDeviceToken::where('token', $request->token)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['success' => true, 'message' => 'Token removed successfully.']);
    }
}
