<?php

namespace App\Http\Controllers\Api\Pages;

use App\Http\Controllers\Controller;
use App\Models\Job\{ Page, SocialMediaPlatform };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    /**
     * Get all active pages
     */
    public function index(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $limit = $request->input('limit', 100);

            $pages = Page::active()
                ->byCountry($countryCode)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit($limit)
                ->get(['id', 'slug', 'title', 'meta_title', 'meta_description', 'template', 'sort_order', 'is_active', 'is_featured']);

            return response()->json([
                'success' => true,
                'data' => $pages
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching pages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pages'
            ], 500);
        }
    }

    /**
     * Get a single page by slug
     */
    public function show(Request $request, $slug)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $page = Page::active()
                ->byCountry($countryCode)
                ->where('slug', $slug)
                ->first();

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'content' => $page->content,
                    'meta_title' => $page->meta_title,
                    'meta_description' => $page->meta_description,
                    'template' => $page->template,
                    'featured_image' => $page->featured_image,
                    'is_active' => $page->is_active,
                    'is_featured' => $page->is_featured,
                    'sort_order' => $page->sort_order,
                    'published_at' => $page->published_at,
                    'created_at' => $page->created_at,
                    'updated_at' => $page->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch page'
            ], 500);
        }
    }

    /**
     * Get pages by template type
     */
    public function byTemplate(Request $request, $template)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $pages = Page::active()
                ->byCountry($countryCode)
                ->where('template', $template)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(['id', 'slug', 'title', 'meta_title', 'meta_description', 'template']);

            return response()->json([
                'success' => true,
                'data' => $pages
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching pages by template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pages'
            ], 500);
        }
    }

    /**
     * Get featured pages
     */
    public function featured(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $limit = $request->input('limit', 10);

            $pages = Page::active()
                ->byCountry($countryCode)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit($limit)
                ->get(['id', 'slug', 'title', 'meta_title', 'meta_description', 'template', 'featured_image']);

            return response()->json([
                'success' => true,
                'data' => $pages
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching featured pages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured pages'
            ], 500);
        }
    }

        /**
     * Get featured social media platforms for frontend
     */
    public function getFeatured(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $limit = $request->input('limit', 10);

            $platforms = SocialMediaPlatform::active()
                ->where('is_featured', true)
                ->where('country_code', $countryCode)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            $formattedPlatforms = $platforms->map(function($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'platform' => $platform->platform,
                    'handle' => $platform->handle,
                    'url' => $platform->url,
                    'icon' => $platform->platform_icon,
                    'color' => $platform->platform_color,
                    'description' => $platform->description,
                    'followers_count' => $platform->current_followers,
                    'is_verified' => $platform->is_verified,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlatforms
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch featured platforms: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured platforms'
            ], 500);
        }
    }

    /**
     * Get social media platforms by country
     */
    public function getByCountry(Request $request, $countryCode)
    {
        try {
            $platforms = SocialMediaPlatform::active()
                ->where('country_code', strtoupper($countryCode))
                ->orderBy('sort_order')
                ->orderBy('is_featured', 'desc')
                ->orderBy('name')
                ->get();

            $formattedPlatforms = $platforms->map(function($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'platform' => $platform->platform,
                    'handle' => $platform->handle,
                    'url' => $platform->url,
                    'icon' => $platform->platform_icon,
                    'color' => $platform->platform_color,
                    'description' => $platform->description,
                    'followers_count' => $platform->current_followers,
                    'is_featured' => $platform->is_featured,
                    'is_verified' => $platform->is_verified,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlatforms
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch platforms by country: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch platforms'
            ], 500);
        }
    }
}