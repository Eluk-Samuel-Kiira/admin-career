<?php

namespace App\Http\Controllers\Job\JobIndex;

use App\Http\Controllers\Controller;
use App\Models\Job\SocialMediaPlatform;
use App\Models\Job\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SocialMediaPlatformController extends Controller
{
    /**
     * Display a listing of social media platforms.
     */
    public function index()
    {
        if (!auth()->user()->can('view social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view social media platforms.'
            ]);
        }

        return view('job.job-index.social-media');
    }

    /**
     * Get data for DataTable with statistics.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $country = $request->get('country', '');
        $platform = $request->get('platform', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = SocialMediaPlatform::with(['creator', 'latestFollowerRecord', 'country']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('handle', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        if (!empty($platform)) {
            $query->where('platform', $platform);
        }

        $platforms = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $platforms->getCollection()->transform(function ($item) {
            $item->status_badge = $item->status_badge;
            $item->verified_badge = $item->verified_badge;
            $item->featured_badge = $item->featured_badge;
            
            // Fix: Use the country relationship
            $item->country_flag = $item->country ? $item->country->flag : '🌍';
            $item->country_name = $item->country ? $item->country->name : 'N/A';
            
            $item->current_followers = $item->current_followers;
            $item->followers_change = $item->followers_change;
            $item->followers_percentage_change = $item->followers_percentage_change;
            $item->growth_icon = $this->getGrowthIcon($item->followers_percentage_change);
            $item->growth_class = $this->getGrowthClass($item->followers_percentage_change);
            return $item;
        });

        return response()->json($platforms);
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(Request $request)
    {
        $country = $request->get('country', '');
        $platform = $request->get('platform', '');
        $period = $request->get('period', 30);

        $query = SocialMediaPlatform::with(['latestFollowerRecord', 'followerHistories']);

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        if (!empty($platform)) {
            $query->where('platform', $platform);
        }

        $platforms = $query->get();
        $totalFollowers = 0;
        $totalGrowth = 0;
        $platformStats = [];

        foreach ($platforms as $p) {
            $current = $p->current_followers;
            $change = $p->followers_change;
            $percentage = $p->followers_percentage_change;
            
            $totalFollowers += $current;
            $totalGrowth += $change;

            $history = $p->followerHistories()
                ->where('recorded_at', '>=', Carbon::now()->subDays((int)$period))
                ->orderBy('recorded_at', 'asc')
                ->get();

            $platformStats[] = [
                'id' => $p->id,
                'name' => $p->name,
                'platform' => $p->platform,
                'handle' => $p->handle,
                'icon' => $p->platform_icon,
                'color' => $p->platform_color,
                'current_followers' => $current,
                'change' => $change,
                'percentage_change' => $percentage,
                'is_featured' => $p->is_featured,
                'history' => $history->map(function($record) {
                    return [
                        'date' => $record->recorded_at->format('Y-m-d'),
                        'followers' => $record->followers_count,
                    ];
                }),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_followers' => $totalFollowers,
                'total_growth' => $totalGrowth,
                'average_growth' => $platforms->count() > 0 ? round($totalGrowth / $platforms->count(), 2) : 0,
                'platforms' => $platformStats,
                'platforms_count' => $platforms->count(),
            ]
        ]);
    }

    /**
     * Get platform details with full history.
     */
    public function getPlatformDetails($id)
    {
        try {
            $platform = SocialMediaPlatform::with(['followerHistories' => function($query) {
                $query->orderBy('recorded_at', 'asc');
            }])->findOrFail($id);

            $history = $platform->followerHistories->map(function($record) {
                return [
                    'date' => $record->recorded_at->format('Y-m-d'),
                    'followers' => $record->followers_count,
                    'note' => $record->note,
                ];
            });

            // Calculate weekly growth
            $weeklyGrowth = [];
            $weeklyData = $platform->followerHistories()
                ->where('recorded_at', '>=', Carbon::now()->subDays(30))
                ->get()
                ->groupBy(function($record) {
                    return $record->recorded_at->format('W');
                });

            foreach ($weeklyData as $week => $records) {
                $first = $records->first();
                $last = $records->last();
                if ($first && $last && $first->followers_count > 0) {
                    $growth = round((($last->followers_count - $first->followers_count) / $first->followers_count) * 100, 2);
                    $weeklyGrowth[] = [
                        'week' => $week,
                        'growth' => $growth,
                        'start' => $first->followers_count,
                        'end' => $last->followers_count,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'platform' => $platform,
                    'history' => $history,
                    'current_followers' => $platform->current_followers,
                    'change' => $platform->followers_change,
                    'percentage_change' => $platform->followers_percentage_change,
                    'weekly_growth' => $weeklyGrowth,
                    'growth_trend' => $this->calculateTrend($history),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get platform details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get countries for dropdown.
     */
    public function getCountries()
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json([
            'success' => true,
            'countries' => $countries->map(function($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag_emoji,
                ];
            })
        ]);
    }

    /**
     * Get platforms for dropdown.
     */
    public function getPlatforms()
    {
        $platforms = [
            ['value' => 'facebook', 'label' => 'Facebook'],
            ['value' => 'twitter', 'label' => 'Twitter / X'],
            ['value' => 'instagram', 'label' => 'Instagram'],
            ['value' => 'linkedin', 'label' => 'LinkedIn'],
            ['value' => 'youtube', 'label' => 'YouTube'],
            ['value' => 'whatsapp', 'label' => 'WhatsApp'],
            ['value' => 'tiktok', 'label' => 'TikTok'],
            ['value' => 'telegram', 'label' => 'Telegram'],
        ];

        return response()->json([
            'success' => true,
            'platforms' => $platforms
        ]);
    }

    /**
     * Store a newly created social media platform.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create social media platforms.'
            ]);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'platform' => 'required|string|in:facebook,twitter,instagram,linkedin,youtube,whatsapp,tiktok,telegram',
                'url' => 'nullable|url|max:255',
                'handle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'followers_count' => 'nullable|integer|min:0',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'is_active' => 'nullable|boolean',
                'is_verified' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
            ]);

            $data = $validated;

            // Handle booleans
            $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;
            $data['is_verified'] = $request->has('is_verified') ? (bool) $request->is_verified : false;
            $data['is_featured'] = $request->has('is_featured') ? (bool) $request->is_featured : false;

            // Set default meta if not provided
            if (empty($data['meta_title'])) {
                $countryName = Country::where('code', $data['country_code'])->first()->name ?? '';
                $data['meta_title'] = "Follow Great Jobs {$countryName} on " . ucfirst($data['platform']);
            }

            if (empty($data['meta_description'])) {
                $countryName = Country::where('code', $data['country_code'])->first()->name ?? '';
                $data['meta_description'] = "Follow Great Jobs {$countryName} on " . ucfirst($data['platform']) . " for the latest job opportunities, career tips, and updates.";
            }

            $data['created_by'] = auth()->id();

            $platform = SocialMediaPlatform::create($data);

            // Create initial follower history if followers_count is provided
            if (!empty($data['followers_count'])) {
                $platform->recordFollowers($data['followers_count'], 'Initial record');
            }

            return response()->json([
                'success' => true,
                'message' => 'Social media platform created successfully!',
                'data' => $platform
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create social media platform: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create social media platform: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified social media platform.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::with('creator')->findOrFail($id);
            return response()->json($platform);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social media platform not found'
            ], 404);
        }
    }

    /**
     * Update the specified social media platform.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'platform' => 'required|string|in:facebook,twitter,instagram,linkedin,youtube,whatsapp,tiktok,telegram',
                'url' => 'nullable|url|max:255',
                'handle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'is_active' => 'nullable|boolean',
                'is_verified' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
            ]);

            $data = $validated;

            // Handle booleans
            $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : $platform->is_active;
            $data['is_verified'] = $request->has('is_verified') ? (bool) $request->is_verified : $platform->is_verified;
            $data['is_featured'] = $request->has('is_featured') ? (bool) $request->is_featured : $platform->is_featured;

            $platform->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Social media platform updated successfully!',
                'data' => $platform->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update social media platform: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update social media platform: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified social media platform.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::findOrFail($id);
            $platform->delete();

            return response()->json([
                'success' => true,
                'message' => 'Social media platform deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete social media platform: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete social media platform: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified social media platform.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::findOrFail($id);
            $platform->is_active = !$platform->is_active;
            $platform->save();

            return response()->json([
                'success' => true,
                'message' => $platform->is_active ? 'Platform activated successfully!' : 'Platform deactivated successfully!',
                'is_active' => $platform->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle verification status.
     */
    public function toggleVerified($id)
    {
        if (!auth()->user()->can('edit social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::findOrFail($id);
            $platform->is_verified = !$platform->is_verified;
            $platform->save();

            return response()->json([
                'success' => true,
                'message' => $platform->is_verified ? 'Platform verified successfully!' : 'Platform unverified successfully!',
                'is_verified' => $platform->is_verified
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle verification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle verification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured($id)
    {
        if (!auth()->user()->can('edit social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit social media platforms.'
            ]);
        }

        try {
            $platform = SocialMediaPlatform::findOrFail($id);
            $platform->is_featured = !$platform->is_featured;
            $platform->save();

            return response()->json([
                'success' => true,
                'message' => $platform->is_featured ? 'Platform featured successfully!' : 'Platform unfeatured successfully!',
                'is_featured' => $platform->is_featured
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle featured: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update followers count manually.
     */
    public function updateFollowers(Request $request, $id)
    {
        if (!auth()->user()->can('edit social media platforms')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit social media platforms.'
            ]);
        }

        try {
            $request->validate([
                'followers_count' => 'required|integer|min:0',
                'note' => 'nullable|string|max:255',
            ]);

            $platform = SocialMediaPlatform::findOrFail($id);
            $platform->recordFollowers($request->followers_count, $request->note ?? 'Manual update');

            return response()->json([
                'success' => true,
                'message' => 'Followers count updated successfully!',
                'data' => [
                    'current_followers' => $platform->current_followers,
                    'change' => $platform->followers_change,
                    'percentage_change' => $platform->followers_percentage_change,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update followers: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper methods
    private function getGrowthIcon($percentage)
    {
        if ($percentage > 5) return 'ki-arrow-up';
        if ($percentage > 0) return 'ki-arrow-up';
        if ($percentage < 0) return 'ki-arrow-down';
        return 'ki-arrow-right';
    }

    private function getGrowthClass($percentage)
    {
        if ($percentage > 5) return 'text-success';
        if ($percentage > 0) return 'text-info';
        if ($percentage < 0) return 'text-danger';
        return 'text-muted';
    }

    private function calculateTrend($history)
    {
        if ($history->count() < 2) {
            return 'stable';
        }

        $last = $history->last();
        $first = $history->first();
        $growth = $last['followers'] - $first['followers'];

        if ($growth > $first['followers'] * 0.1) return 'growing';
        if ($growth < -($first['followers'] * 0.1)) return 'declining';
        return 'stable';
    }


}