<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * GET /api/countries
     * Returns all active countries, ordered by sort_order then name.
     * Used by the proxy's CountryService::getAllCountries().
     */
    public function index(Request $request): JsonResponse
    {
        $countries = Country::query()
            ->active()      // ← scopeActive()
            ->ordered()     // ← scopeOrdered()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $countries,
        ]);
    }

    /**
     * GET /api/countries/{code}
     */
    public function show($code, Request $request)
    {
        $country = Country::where('code', $code)->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $country,
        ]);
    }
}