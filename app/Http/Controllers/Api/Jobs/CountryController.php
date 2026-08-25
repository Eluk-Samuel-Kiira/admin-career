<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function show($code, Request $request)
    {
        $country = Country::where('code', $code)->first();
        
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $country
        ]);
    }
}