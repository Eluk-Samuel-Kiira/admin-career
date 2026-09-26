<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComplianceController extends Controller
{
    /**
     * The 7 document types this system supports.
     * Each maps to: {type}_path, {type}_name, {type}_expires_at on the profile.
     */
    private const DOCUMENT_TYPES = [
        'cert_incorporation'   => 'Certificate of Incorporation',
        'trading_license'      => 'Trading License',
        'tin_certificate'      => 'TIN Certificate',
        'nssf_certificate'     => 'NSSF Registration',
        'tax_clearance'        => 'Tax Clearance',
        'insurance'            => 'Insurance Certificate',
        'professional_license' => 'Professional License',
    ];

    // =================================================================
    // LIST — everything the compliance page needs
    // =================================================================
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->buildCompliancePayload($profile),
        ]);
    }

    

    // =================================================================
    // UPLOAD — one document at a time, by type
    // =================================================================
    public function upload(Request $request, string $type): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->firstOrFail();

        if (!array_key_exists($type, self::DOCUMENT_TYPES)) {
            return response()->json([
                'success' => false,
                'message' => "Unknown document type: {$type}",
            ], 422);
        }

        $request->validate([
            'file'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'issued_at'  => 'nullable|date',
            'expires_at' => 'nullable|date|after:issued_at',
            'notes'      => 'nullable|string|max:500',
        ]);

        try {
            $pathField     = "{$type}_path";
            $nameField     = "{$type}_name";
            $expiresField  = "{$type}_expires_at";

            // Remove old file if replacing
            if ($profile->$pathField && Storage::disk('public')->exists($profile->$pathField)) {
                Storage::disk('public')->delete($profile->$pathField);
            }

            $file     = $request->file('file');
            $filename = Str::slug($profile->company_name) . "_{$type}_" . time() . '.' . $file->getClientOriginalExtension();
            $folder   = "employers/{$profile->id}/compliance/{$type}";
            $path     = $file->storeAs($folder, $filename, 'public');

            $profile->$pathField = $path;
            $profile->$nameField = $file->getClientOriginalName();
            $profile->$expiresField = $request->input('expires_at');
            $profile->save();

            // If all required docs are now present and status is 'incomplete' or 'rejected', auto-bump to 'submitted'
            if ($profile->has_all_required_documents
                && in_array($profile->compliance_status, ['incomplete', 'rejected'], true)) {
                $profile->compliance_status = 'submitted';
                $profile->compliance_submitted_at = now();
                $profile->save();
            }

            return response()->json([
                'success' => true,
                'message' => self::DOCUMENT_TYPES[$type] . ' uploaded.',
                'data'    => $this->buildCompliancePayload($profile->fresh()),
            ]);

        } catch (\Throwable $e) {
            Log::error('Compliance upload failed', [
                'user_id' => $user->id,
                'type'    => $type,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =================================================================
    // DELETE — remove one document
    // =================================================================
    public function destroy(Request $request, string $type): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->firstOrFail();

        if (!array_key_exists($type, self::DOCUMENT_TYPES)) {
            return response()->json(['success' => false, 'message' => "Unknown document type: {$type}"], 422);
        }

        $pathField     = "{$type}_path";
        $nameField     = "{$type}_name";
        $expiresField  = "{$type}_expires_at";

        // Delete physical file
        if ($profile->$pathField && Storage::disk('public')->exists($profile->$pathField)) {
            Storage::disk('public')->delete($profile->$pathField);
        }

        $profile->$pathField = null;
        $profile->$nameField = null;
        $profile->$expiresField = null;

        // If we removed a required doc, downgrade status back to incomplete
        if (!$profile->has_all_required_documents && $profile->compliance_status === 'submitted') {
            $profile->compliance_status = 'incomplete';
            $profile->compliance_submitted_at = null;
        }

        $profile->save();

        return response()->json([
            'success' => true,
            'message' => self::DOCUMENT_TYPES[$type] . ' removed.',
            'data'    => $this->buildCompliancePayload($profile->fresh()),
        ]);
    }

    // =================================================================
    // SUBMIT — manually submit for review
    // =================================================================
    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->firstOrFail();

        if (!$profile->has_all_required_documents) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload all required documents first.',
                'missing' => $profile->missing_documents,
            ], 422);
        }

        if ($profile->compliance_status === 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Your compliance is already verified.',
            ], 422);
        }

        $profile->update([
            'compliance_status'       => 'submitted',
            'compliance_submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submitted for review. Our team will verify within 24 hours.',
            'data'    => $this->buildCompliancePayload($profile->fresh()),
        ]);
    }

    // =================================================================
    // BUILD PAYLOAD
    // =================================================================
    private function buildCompliancePayload(EmployerProfile $p): array
    {
        $required = EmployerProfile::requiredDocumentsFor($p->country_code ?? 'UG');

        // Build per-document array
        $documents = [];
        foreach (self::DOCUMENT_TYPES as $key => $label) {
            $pathField    = "{$key}_path";
            $nameField    = "{$key}_name";
            $expiresField = "{$key}_expires_at";

            $path = $p->$pathField;
            $expiresAt = $p->$expiresField;

            $status = 'missing';
            if ($path) {
                if ($expiresAt && $expiresAt->isPast()) {
                    $status = 'expired';
                } elseif ($expiresAt && $expiresAt->lessThanOrEqualTo(now()->addDays(30))) {
                    $status = 'expiring_soon';
                } else {
                    $status = 'uploaded';
                }
            }

            $documents[] = [
                'type'          => $key,
                'label'         => $label,
                'required'      => array_key_exists($key, $required),
                'status'        => $status,
                'file_name'     => $p->$nameField,
                'file_url'      => $path ? Storage::disk('public')->url($path) : null,
                'expires_at'    => $expiresAt?->toDateString(),
            ];
        }

        return [
            'profile_id'             => $p->id,
            'company_id'             => $p->company_id,
            'company_name'           => $p->company_name,
            'country_code'           => $p->country_code,
            'compliance_status'      => $p->compliance_status,
            'compliance_progress'    => $p->compliance_progress,
            'compliance_submitted_at' => $p->compliance_submitted_at?->toISOString(),
            'compliance_verified_at'  => $p->compliance_verified_at?->toISOString(),
            'compliance_notes'        => $p->compliance_notes,
            'missing_documents'      => $p->missing_documents,
            'expiring_documents'     => $p->expiring_documents,
            'has_all_required'       => $p->has_all_required_documents,
            'tier'                   => $p->tier,
            'tier_label'             => $p->tier_label,
            'documents'              => $documents,
        ];
    }
}