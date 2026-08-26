<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyCountryApiToken
{
    public function handle(Request $request, Closure $next)
    {
        // Try to get token from multiple sources
        // 1. Check query parameter first (for cPanel/GET requests)
        $token = $request->query('api_key');
        
        // 2. Check request body (for POST requests)
        if (!$token) {
            $token = $request->input('api_key');
        }
        
        // 3. Check Authorization header (Bearer token)
        if (!$token) {
            $token = $request->bearerToken();
        }
        
        // 4. Check custom header X-API-KEY
        if (!$token) {
            $token = $request->header('X-API-KEY');
        }

        $countryCode = $request->header('X-Country-Code');

        // Log what we found for debugging
        // Log::info('🔍 Token resolution', [
        //     'from_query' => !empty($request->query('api_key')),
        //     'from_body' => !empty($request->input('api_key')),
        //     'from_bearer' => !empty($request->bearerToken()),
        //     'from_custom_header' => !empty($request->header('X-API-KEY')),
        //     'token_present' => !empty($token),
        //     'country_code' => $countryCode,
        //     'token_preview' => $token ? substr($token, 0, 20) . '...' : 'null',
        // ]);

        if (!$token) {
            Log::warning('❌ API token missing', [
                'country_code' => $countryCode,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
                'all_params' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'API token required'
            ], 401);
        }

        if (!$countryCode) {
            Log::warning('❌ Country code header missing', [
                'token_preview' => substr($token, 0, 20) . '...',
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Country code header required (X-Country-Code)'
            ], 401);
        }

        $countryCode = strtoupper($countryCode);
        $envKey = $countryCode . '_API_KEY';
        $validToken = env($envKey);

        if (!$validToken) {
            Log::error('❌ Environment key not found', [
                'env_key' => $envKey,
                'country_code' => $countryCode,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token for country: ' . $countryCode,
            ], 401);
        }

        if (!hash_equals($validToken, $token)) {
            Log::error('❌ Token mismatch', [
                'country_code' => $countryCode,
                'env_key' => $envKey,
                'token_length' => strlen($token),
                'valid_token_length' => strlen($validToken),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token for country: ' . $countryCode
            ], 401);
        }

        // Log::info('✅ API token verified successfully', [
        //     'country_code' => $countryCode,
        //     'ip' => $request->ip(),
        // ]);

        $request->merge(['country_code' => $countryCode]);

        return $next($request);
    }
}