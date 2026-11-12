<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AcademicDocumentsController extends Controller
{
    private const MAX_SIZE_KB = 10240; // 10 MB

    /**
     * GET /auth/user/academic-documents
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile;
        if (!$profile || !$profile->academic_documents_path) {
            return response()->json([
                'success'  => true,
                'document' => null,
            ]);
        }

        return response()->json([
            'success'  => true,
            'document' => [
                'path'          => $profile->academic_documents_path,
                'original_name' => $profile->academic_documents_original_name,
                'size'          => $profile->academic_documents_size,
                'uploaded_at'   => $profile->academic_documents_uploaded_at?->toISOString(),
            ],
        ]);
    }

    /**
     * POST /auth/user/academic-documents
     * Upload or replace the academic documents bundle.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:' . self::MAX_SIZE_KB,
        ], [
            'document.mimes' => 'The academic documents must be a single PDF file. Merge all your documents into one PDF.',
            'document.max'   => 'The PDF must not exceed 10 MB.',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile()->firstOrNew();
        $profile->user_id = $user->id;

        // Delete the previous bundle if any
        if ($profile->academic_documents_path && Storage::disk('public')->exists($profile->academic_documents_path)) {
            try {
                Storage::disk('public')->delete($profile->academic_documents_path);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete old academic documents', [
                    'user_id' => $user->id,
                    'path'    => $profile->academic_documents_path,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $file = $request->file('document');
        $path = $file->store('academic-documents', 'public');

        $profile->fill([
            'academic_documents_path'          => $path,
            'academic_documents_original_name' => $file->getClientOriginalName(),
            'academic_documents_size'          => $file->getSize(),
            'academic_documents_uploaded_at'   => now(),
        ]);
        $profile->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Academic documents uploaded successfully. Previous file was replaced.',
            'document' => [
                'path'          => $profile->academic_documents_path,
                'original_name' => $profile->academic_documents_original_name,
                'size'          => $profile->academic_documents_size,
                'uploaded_at'   => $profile->academic_documents_uploaded_at?->toISOString(),
            ],
        ]);
    }

    /**
     * DELETE /auth/user/academic-documents
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile;
        if (!$profile || !$profile->academic_documents_path) {
            return response()->json(['success' => false, 'message' => 'No academic documents found.'], 404);
        }

        if (Storage::disk('public')->exists($profile->academic_documents_path)) {
            Storage::disk('public')->delete($profile->academic_documents_path);
        }

        $profile->fill([
            'academic_documents_path'          => null,
            'academic_documents_original_name' => null,
            'academic_documents_size'          => null,
            'academic_documents_uploaded_at'   => null,
        ]);
        $profile->save();

        return response()->json(['success' => true, 'message' => 'Academic documents deleted.']);
    }

    /**
     * GET /auth/user/academic-documents/download?path=...
     * Ownership-checked stream — same pattern as the CV download.
     */
    public function download(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $profile = $user->seekerProfile;
        if (!$profile || $profile->academic_documents_path !== $request->query('path')) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($profile->academic_documents_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $profile->academic_documents_path,
            $profile->academic_documents_original_name ?: 'academic-documents.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}