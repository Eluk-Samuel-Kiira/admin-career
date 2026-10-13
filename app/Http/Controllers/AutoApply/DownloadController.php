<?php

namespace App\Http\Controllers\AutoApply;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function show(Request $request)
    {
        $path = base64_decode($request->query('path', ''));
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}