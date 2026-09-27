<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminComplianceController extends Controller
{
    /**
     * Show the admin review queue.
     */
    public function index()
    {
        if (!auth()->user()->can('view employer compliance')) {
            abort(403);
        }

        return view('employer.compliance.admin.index');
    }

    /**
     * Return the queue as JSON for the admin table.
     */
    public function data(Request $request): JsonResponse
    {
        $status = $request->get('status', 'submitted');
        $search = $request->get('search', '');
        $page   = (int) $request->get('page', 1);

        $query = EmployerProfile::with('company');

        if ($status === 'submitted') {
            $query->where('compliance_status', 'submitted');
        } elseif ($status === 'verified') {
            $query->where('compliance_status', 'verified');
        } elseif ($status === 'rejected') {
            $query->where('compliance_status', 'rejected');
        } elseif ($status === 'incomplete') {
            $query->where('compliance_status', 'incomplete');
        } else {
            // 'all' — any status
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('compliance_submitted_at', 'asc')
            ->paginate(20, ['*'], 'page', $page);

            $requests->getCollection()->transform(function ($p) {
                return [
                    'id'                     => $p->id,
                    'company_id'             => $p->company_id,
                    'company_name'           => $p->company_name,
                    'company_logo'           => $p->logo_url,
                    'contact_name'           => $p->contact_name,
                    'contact_email'          => $p->contact_email,
                    'country_code'           => $p->country_code,
                    'compliance_status'      => $p->compliance_status,
                    'compliance_progress'    => $p->compliance_progress,
                    'profile_completeness'   => $this->calculateProfileCompleteness($p),
                    'compliance_submitted_at' => $p->compliance_submitted_at?->toISOString(),
                    'compliance_verified_at'  => $p->compliance_verified_at?->toISOString(),
                    'missing_documents'      => $p->missing_documents,
                    'documents'              => $this->documentList($p),
                ];
            });

        return response()->json($requests);
    }

    private function calculateProfileCompleteness(EmployerProfile $p): int
    {
        $fields = [
            // Basic info
            'company_name',
            'legal_name',
            'industry',
            'company_size',
            'company_description',
            'company_website',

            // Contact
            'contact_name',
            'contact_email',
            'contact_phone',

            // Location
            'city',
            'country_code',

            // Compliance identifiers
            'tin_number',
            'nssf_number',

            // At least one compliance document uploaded
            // Handled below
        ];

        $filled = 0;
        $total = count($fields) + 1;   // +1 for at least one doc

        foreach ($fields as $field) {
            if (!empty($p->$field)) {
                $filled++;
            }
        }

        // At least one compliance doc uploaded
        if ($p->cert_incorporation_path || $p->trading_license_path || $p->tin_certificate_path) {
            $filled++;
        }

        return (int) round(($filled / $total) * 100);
    }

    /**
     * Show one submission's full detail.
     */
    public function show($id): JsonResponse
    {
        if (!auth()->user()->can('view employer compliance')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile = EmployerProfile::with('company')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                => $profile->id,
                'company_id'        => $profile->company_id,
                'company_name'      => $profile->company_name,
                'company_logo'      => $profile->logo_url,
                'country_code'      => $profile->country_code,
                'contact_name'      => $profile->contact_name,
                'contact_email'     => $profile->contact_email,
                'contact_phone'     => $profile->contact_phone,
                'compliance_status' => $profile->compliance_status,
                'compliance_notes'  => $profile->compliance_notes,
                'submitted_at'      => $profile->compliance_submitted_at?->toISOString(),
                'verified_at'       => $profile->compliance_verified_at?->toISOString(),
                'documents'         => $this->documentList($profile),
            ],
        ]);
    }

    /**
     * Approve — mark verified, unlock the Verified tier.
     */
    public function verify(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('edit employer compliance')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile = EmployerProfile::findOrFail($id);

        if (!$profile->has_all_required_documents) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot verify — some required documents are still missing.',
                'missing' => $profile->missing_documents,
            ], 422);
        }

        $profile->update([
            'compliance_status'      => 'verified',
            'compliance_verified_at' => now(),
            'compliance_verified_by' => auth()->id(),
            'compliance_notes'       => $request->input('notes'),
            'is_verified'            => true,
            'verification_status'    => 'verified',
        ]);

        Log::info('Employer compliance verified', [
            'profile_id' => $profile->id,
            'by'         => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employer verified.',
        ]);
    }

    /**
     * Reject — with mandatory reason.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('edit employer compliance')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $profile = EmployerProfile::findOrFail($id);

        $profile->update([
            'compliance_status' => 'rejected',
            'compliance_notes'  => $request->input('notes'),
            'is_verified'       => false,
            'verification_status' => 'rejected',
        ]);

        Log::info('Employer compliance rejected', [
            'profile_id' => $profile->id,
            'by'         => auth()->id(),
            'reason'     => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employer rejected. Notes sent to employer.',
        ]);
    }

    /**
     * Build the document list for admin view (with URLs).
     */
    private function documentList(EmployerProfile $p): array
    {
        $types = [
            'cert_incorporation'   => 'Certificate of Incorporation',
            'trading_license'      => 'Trading License',
            'tin_certificate'      => 'TIN Certificate',
            'nssf_certificate'     => 'NSSF Registration',
            'tax_clearance'        => 'Tax Clearance',
            'insurance'            => 'Insurance Certificate',
            'professional_license' => 'Professional License',
        ];

        $required = EmployerProfile::requiredDocumentsFor($p->country_code ?? 'UG');
        $docs = [];

        foreach ($types as $key => $label) {
            $path = $p->{"{$key}_path"};
            $docs[] = [
                'type'       => $key,
                'label'      => $label,
                'required'   => array_key_exists($key, $required),
                'uploaded'   => !empty($path),
                'file_url'   => $path ? Storage::disk('public')->url($path) : null,
                'file_name'  => $p->{"{$key}_name"},
                'expires_at' => $p->{"{$key}_expires_at"}?->toDateString(),
            ];
        }

        return $docs;
    }

    // app/Http/Controllers/Employer/AdminComplianceController.php

    public function stats(): JsonResponse
    {
        if (!auth()->user()->can('view employer compliance')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $pending  = EmployerProfile::where('compliance_status', 'submitted')->count();
        $verified = EmployerProfile::where('compliance_status', 'verified')->count();
        $rejected = EmployerProfile::where('compliance_status', 'rejected')->count();
        $incomplete = EmployerProfile::where('compliance_status', 'incomplete')->count();

        // Average compliance progress across all profiles
        $avgProgress = EmployerProfile::whereNotNull('company_name')
            ->get()
            ->avg(fn($p) => $p->compliance_progress) ?? 0;

        // Verified this week
        $verifiedThisWeek = EmployerProfile::where('compliance_status', 'verified')
            ->where('compliance_verified_at', '>=', now()->subWeek())
            ->count();

        return response()->json([
            'success' => true,
            'stats'   => [
                'pending'            => $pending,
                'verified'           => $verified,
                'rejected'           => $rejected,
                'incomplete'         => $incomplete,
                'avg_progress'       => (int) round($avgProgress),
                'verified_this_week' => $verifiedThisWeek,
            ],
        ]);
    }

}