<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\SeekerProfile;
use App\Models\User;
use App\Models\Job\Country;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SeekerController extends Controller
{
    /**
     * Display a listing of job seekers.
     */
    public function index()
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        return view('job-seeker.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $search = $request->get('search', '');
        $country = $request->get('country', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        $query = SeekerProfile::with(['user'])
            ->where('is_active', true);

        // Search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('professional_title', 'like', '%' . $search . '%')
                  ->orWhere('skills', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('email', 'like', '%' . $search . '%')
                         ->orWhere('first_name', 'like', '%' . $search . '%')
                         ->orWhere('last_name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Country filter
        if (!empty($country)) {
            $query->where('country', $country);
        }

        // Status filter
        if (!empty($status)) {
            if ($status === 'has_cv') {
                $query->whereNotNull('cv_file_path')
                      ->orWhereNotNull('cv_files');
            } elseif ($status === 'no_cv') {
                $query->whereNull('cv_file_path')
                      ->whereNull('cv_files');
            } elseif ($status === 'has_applied') {
                $query->whereHas('jobSeekerJobs', function($q) {
                    $q->where('is_applied', true);
                });
            }
        }

        $seekers = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $seekers->getCollection()->transform(function ($item) {
            return $this->formatSeekerData($item);
        });

        return response()->json($seekers);
    }

    /**
     * Display a specific job seeker - returns HTML for modal.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        $seeker = SeekerProfile::with(['user', 'jobSeekerJobs.jobPost.company'])
            ->findOrFail($id);

        $formattedSeeker = $this->formatSeekerData($seeker);
        
        // Return HTML for modal
        return view('job-seeker.partials.details', compact('formattedSeeker'));
    }

    /**
     * View CV of a job seeker - supports multiple CVs.
     */
    public function viewCv(Request $request, $id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view CVs.');
        }

        $seeker = SeekerProfile::findOrFail($id);
        
        // Check if specific file is requested
        $filePath = $request->get('file');
        
        if ($filePath) {
            // View specific CV from cv_files array
            $cvFiles = $this->getCvFilesArray($seeker);
            $found = false;
            foreach ($cvFiles as $cv) {
                if (isset($cv['path']) && $cv['path'] === $filePath) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                abort(404, 'CV file not found');
            }
            if (!Storage::disk('public')->exists($filePath)) {
                abort(404, 'CV file not found');
            }
            return response()->file(Storage::disk('public')->path($filePath));
        }
        
        // Fallback to single CV file
        if (!$seeker->cv_file_path) {
            abort(404, 'CV not found');
        }

        if (!Storage::disk('public')->exists($seeker->cv_file_path)) {
            abort(404, 'CV file not found');
        }

        return response()->file(Storage::disk('public')->path($seeker->cv_file_path));
    }

    /**
     * Get applications for a specific seeker.
     */
    public function applications(Request $request, $id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view applications.');
        }

        $seeker = SeekerProfile::with(['user'])->findOrFail($id);

        $applications = JobSeekerJob::with(['jobPost.company', 'jobPost.jobLocation', 'jobPost.jobType'])
            ->where('seeker_profile_id', $id)
            ->where('is_applied', true)
            ->orderBy('applied_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'job_title' => $item->jobPost->job_title ?? 'N/A',
                    'company_name' => $item->jobPost->company->name ?? 'N/A',
                    'location' => $item->jobPost->jobLocation->district ?? $item->jobPost->duty_station ?? 'N/A',
                    'applied_at' => $item->applied_at,
                    'status' => $this->getApplicationStatus($item),
                    'salary' => $item->jobPost->formatted_salary ?? 'Negotiable',
                    'job_type' => $item->jobPost->jobType->name ?? $item->jobPost->employment_type ?? 'Full-time',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $applications,
            'total' => $applications->count(),
        ]);
    }

    /**
     * Get filters for dropdowns.
     */
    public function getFilters(Request $request)
    {
        // Get countries from the Country model
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'flag'])
            ->map(function ($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag ?? '🌍',
                ];
            });

        return response()->json([
            'success' => true,
            'countries' => $countries,
        ]);
    }

    /**
     * Get CV files as array - ensures it's always an array.
     */
    private function getCvFilesArray($seeker): array
    {
        if (!$seeker) {
            return [];
        }
        
        $files = $seeker->cv_files;
        
        if (is_null($files)) {
            return [];
        }
        
        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($files)) {
            return $files;
        }
        
        return [];
    }

    /**
     * Format seeker data for table display.
     */
    private function formatSeekerData($seeker)
    {
        $user = $seeker->user;
        $fullName = $seeker->first_name . ' ' . $seeker->last_name;
        if (empty(trim($fullName)) && $user) {
            $fullName = $user->name ?? 'Unknown';
        }

        // ✅ FIX: Get cv_files properly
        $cvFiles = $this->getCvFilesArray($seeker);
        $cvCount = count($cvFiles);
        
        // Check legacy cv_file_path too
        $hasCv = !is_null($seeker->cv_file_path) || $cvCount > 0;

        $appliedCount = $seeker->jobSeekerJobs()->where('is_applied', true)->count();
        $savedCount = $seeker->jobSeekerJobs()->where('is_saved', true)->count();

        // Get country flag from Country model
        $flag = '🌍';
        if ($seeker->country) {
            $country = Country::where('code', $seeker->country)->first();
            $flag = $country->flag ?? '🌍';
        }

        // ✅ Build CV file URLs
        $cvFilesWithUrls = [];
        foreach ($cvFiles as $cv) {
            $cvFile = $cv;
            if (isset($cv['path'])) {
                $cvFile['url'] = Storage::disk('public')->url($cv['path']);
            }
            $cvFilesWithUrls[] = $cvFile;
        }

        return [
            'id' => $seeker->id,
            'user_id' => $seeker->user_id,
            'avatar' => $user ? $user->avatar_url : asset('assets/media/avatars/blank.png'),
            'full_name' => $fullName,
            'email' => $seeker->email ?? ($user ? $user->email : 'N/A'),
            'phone' => $seeker->phone ?? ($user ? $user->phone : 'N/A'),
            'professional_title' => $seeker->professional_title ?? 'N/A',
            'country' => $seeker->country ?? 'N/A',
            'flag' => $flag,
            'city' => $seeker->city ?? 'N/A',
            'years_of_experience' => $seeker->years_of_experience ?? 0,
            'skills' => $seeker->skills ? (is_array($seeker->skills) ? $seeker->skills : $seeker->skills) : 'N/A',
            'professional_summary' => $seeker->professional_summary ?? 'N/A',
            'languages' => $seeker->languages ?? [],
            'linkedin_url' => $seeker->linkedin_url ?? null,
            'github_url' => $seeker->github_url ?? null,
            'portfolio_url' => $seeker->portfolio_url ?? null,
            'has_cv' => $hasCv,
            'cv_count' => $cvCount,
            'cv_file_path' => $seeker->cv_file_path,
            'cv_files' => $cvFilesWithUrls, // ✅ Full array with URLs
            'applied_count' => $appliedCount,
            'saved_count' => $savedCount,
            'is_public' => $seeker->is_public,
            'created_at' => $seeker->created_at,
            'updated_at' => $seeker->updated_at,
            'status_badge' => $this->getStatusBadge($seeker),
            'cv_badge' => $this->getCvBadge($seeker),
        ];
    }

    /**
     * Get application status.
     */
    private function getApplicationStatus($jobSeekerJob): string
    {
        if ($jobSeekerJob->is_got_job) return 'hired';
        if ($jobSeekerJob->is_called_for_interview) return 'interviewing';
        if ($jobSeekerJob->is_rejected) return 'rejected';
        return 'applied';
    }

    /**
     * Get status badge HTML.
     */
    private function getStatusBadge($seeker)
    {
        if ($seeker->is_public) {
            return '<span class="badge badge-light-success">Public</span>';
        }
        return '<span class="badge badge-light-secondary">Private</span>';
    }

    /**
     * Get CV badge HTML.
     */
    private function getCvBadge($seeker)
    {
        $cvCount = count($this->getCvFilesArray($seeker));
        if ($seeker->cv_file_path || $cvCount > 0) {
            return '<span class="badge badge-light-success">✅ ' . ($cvCount > 0 ? $cvCount . ' CV(s)' : 'Uploaded') . '</span>';
        }
        return '<span class="badge badge-light-danger">❌ Not Uploaded</span>';
    }
}