<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeekerProfile;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function handle(Request $request)
    {
        // The signed middleware already verified the user hasn't tampered with the ID
        $userId = $request->query('user');

        $seeker = SeekerProfile::where('user_id', $userId)->first();

        if (!$seeker) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'We could not find a profile associated with this email.'
            ], 404);
        }

        $prefs = $seeker->job_preferences_array;
        
        // Flip email alerts to false
        $prefs['send_alert_by_email'] = false;

        $seeker->job_preferences = $prefs;
        $seeker->save();

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'You have been successfully unsubscribed from email job alerts.'
        ]);
    }
}