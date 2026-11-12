<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CvController extends Controller
{
    private const MAX_CV_FILES = 2;

    public function __construct(
        protected CvTextExtractionService $textExtractor,
        protected AiService $aiService
    ) {}

    /**
     * Get CV files as array - ensures it's always an array
     */
    private function getCvFiles($profile): array
    {
        if (!$profile) {
            return [];
        }
        
        $files = $profile->cv_files;
        
        // If it's null, return empty array
        if (is_null($files)) {
            return [];
        }
        
        // If it's a string, try to decode JSON
        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        // If it's already an array, return it
        if (is_array($files)) {
            return $files;
        }
        
        return [];
    }

    /**
     * List all CV files for the user
     */
    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile;
        if (!$profile) {
            return response()->json([
                'success' => true,
                'cv_files' => [],
                'total_count' => 0,
                'max_files' => self::MAX_CV_FILES,
            ]);
        }

        $cvFiles = $this->getCvFiles($profile);

        // ✅ NO MORE `url` — only the path is returned.
        // The proxy will use `path` to hit the download endpoint.
        return response()->json([
            'success' => true,
            'cv_files' => $cvFiles,
            'total_count' => count($cvFiles),
            'max_files' => self::MAX_CV_FILES,
        ]);
    }

    /**
     * Upload a CV file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile()->firstOrNew();
        $profile->user_id = $user->id;

        $currentFiles = $this->getCvFiles($profile);
        if (count($currentFiles) >= self::MAX_CV_FILES) {
            return response()->json([
                'success'       => false,
                'message'       => 'You have already uploaded the maximum of ' . self::MAX_CV_FILES . ' CVs. Please delete one before uploading another.',
                'max_files'     => self::MAX_CV_FILES,
                'current_count' => count($currentFiles),
            ], 422);
        }

        $file = $request->file('cv');

        try {
            // Extract text
            $text = $this->textExtractor->extract($file->getRealPath(), $file->getMimeType());

            if (trim($text) === '') {
                // File saved, but no readable text — treat as soft warning
                $cvFile = $this->saveCvFile($file);
                $currentFiles[] = $cvFile;
                $profile->cv_files = $currentFiles;
                $profile->save();

                return response()->json([
                    'success'     => true,
                    'message'     => "CV uploaded but no readable text could be extracted. You can fill in the details manually.",
                    'profile'     => $profile->fresh(),
                    'cv_files'    => $this->getCvFiles($profile),
                    'total_count' => count($this->getCvFiles($profile)),
                    'max_files'   => self::MAX_CV_FILES,
                ]);
            }

            // Ask AI to extract (may throw NotACvException)
            $extracted = $this->aiService->extractCvData($text);

        } catch (\App\Exceptions\NotACvException $e) {
            // ────────────────────────────────────────────────────────
            // AI says this isn't a CV → reject BEFORE saving the file
            // ────────────────────────────────────────────────────────
            Log::info('CV upload rejected as non-CV', [
                'user_id' => $user->id,
                'reason'  => $e->reason,
            ]);

            return response()->json([
                'success'     => false,
                'code'        => 'not_a_cv',
                'message'     => $e->reason,
                'current_count' => count($currentFiles),
                'max_files'   => self::MAX_CV_FILES,
            ], 422);

        } catch (\Throwable $e) {
            // AI failed for a technical reason (timeout, API error, etc.)
            // Still save the file so the user isn't stuck — they can fill manually.
            Log::error('CV extraction failed: ' . $e->getMessage());

            $cvFile = $this->saveCvFile($file);
            $currentFiles[] = $cvFile;
            $profile->cv_files = $currentFiles;
            $profile->save();

            return response()->json([
                'success'     => true,
                'message'     => "CV uploaded successfully. We couldn't auto-extract all details, but you can fill them in manually.",
                'profile'     => $profile->fresh(),
                'cv_files'    => $this->getCvFiles($profile),
                'total_count' => count($this->getCvFiles($profile)),
                'max_files'   => self::MAX_CV_FILES,
            ]);
        }

        // ────────────────────────────────────────────────────────────────
        // Valid CV → save the file and fill the profile
        // ────────────────────────────────────────────────────────────────
        try {
            $cvFile = $this->saveCvFile($file);
            $currentFiles[] = $cvFile;
            $profile->cv_files = $currentFiles;

            if (!empty(array_filter($extracted))) {
                $profile->fill($this->mapToProfile($extracted));
            }

            $profile->save();
            $profile->refresh();

            return response()->json([
                'success'     => true,
                'message'     => 'CV processed successfully!',
                'profile'     => $profile,
                'cv_files'    => $this->getCvFiles($profile),
                'total_count' => count($this->getCvFiles($profile)),
                'max_files'   => self::MAX_CV_FILES,
            ]);

        } catch (\Exception $e) {
            Log::error('CV save failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save CV data: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Save CV file and return file info
     */
    private function saveCvFile($file): array
    {
        $path = $file->store('cvs', 'public');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();
        $mimeType = $file->getMimeType();

        return [
            'path' => $path,
            'original_name' => $originalName,
            'size' => $size,
            'mime_type' => $mimeType,
            'uploaded_at' => now()->toISOString(),
        ];
    }

    /**
     * Delete a CV file
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $cvFiles = $this->getCvFiles($profile);
        $filePath = $request->file_path;

        // Find the file to delete
        $foundIndex = -1;
        foreach ($cvFiles as $index => $file) {
            if (isset($file['path']) && $file['path'] === $filePath) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex === -1) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        // Remove from array
        $deletedFile = $cvFiles[$foundIndex];
        unset($cvFiles[$foundIndex]);
        $cvFiles = array_values($cvFiles); // Re-index

        // Delete from storage
        try {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete physical file: ' . $e->getMessage());
        }

        // Update profile
        $profile->cv_files = $cvFiles;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'CV file deleted successfully',
            'deleted_file' => $deletedFile,
            'cv_files' => $this->getCvFiles($profile),
            'total_count' => count($this->getCvFiles($profile)),
            'max_files' => self::MAX_CV_FILES,
        ]);
    }

    /**
     * GET /auth/user/cv/download?path=cvs/abc.pdf
     * Streams the file. Only allows downloading files the authenticated user owns.
     */
    public function download(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $profile = $user->seekerProfile;
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $requestedPath = $request->query('path');
        $cvFiles = $this->getCvFiles($profile);

        // Ownership check
        $owned = collect($cvFiles)->contains(fn ($f) => ($f['path'] ?? null) === $requestedPath);
        if (!$owned) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        if (!Storage::disk('public')->exists($requestedPath)) {
            return response()->json(['success' => false, 'message' => 'File no longer exists on disk.'], 404);
        }

        return Storage::disk('public')->download(
            $requestedPath,
            basename($requestedPath),
            ['Content-Type' => Storage::disk('public')->mimeType($requestedPath) ?: 'application/pdf']
        );
    }


    /**
     * Map extracted CV data to profile fields
     */
    private function mapToProfile(array $data): array
    {
        return [
            // User table (mirrored on seeker profile fill)
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name']  ?? null,

            // Seeker scalar fields
            'phone'                => $data['phone']                ?? null,
            'address'              => $data['address']              ?? null,
            'city'                 => $data['city']                 ?? null,
            'country'              => $data['country']              ?? null,
            'postal_code'          => $data['postal_code']          ?? null,
            'date_of_birth'        => $data['date_of_birth']        ?? null,
            'nationality'          => $data['nationality']          ?? null,
            'professional_summary' => $data['professional_summary'] ?? null,
            'professional_title'   => $data['professional_title']   ?? null,
            'years_of_experience'  => $data['years_of_experience']  ?? null,
            'linkedin_url'         => $data['linkedin_url']         ?? null,
            'github_url'           => $data['github_url']           ?? null,
            'portfolio_url'        => $data['portfolio_url']        ?? null,

            // Arrays
            'skills'         => $data['skills']         ?? [],
            'languages'      => $data['languages']      ?? [],
            'certifications' => $data['certifications'] ?? [],
            'education'      => $data['education']      ?? [],
            'work_experience'=> $data['work_experience']?? [],
            'projects'       => $data['projects']       ?? [],

            // Filter FK IDs (optional — AI may or may not return these)
            'job_category_id'     => $data['job_category_id']     ?? null,
            'industry_id'         => $data['industry_id']         ?? null,
            'job_type_id'         => $data['job_type_id']         ?? null,
            'job_location_id'     => $data['job_location_id']     ?? null,
            'experience_level_id' => $data['experience_level_id'] ?? null,
            'education_level_id'  => $data['education_level_id']  ?? null,
            'salary_range_id'     => $data['salary_range_id']     ?? null,
        ];
    }


}