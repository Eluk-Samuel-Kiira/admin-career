<?php

namespace App\Http\Controllers\Api\Filters;

use App\Http\Controllers\Controller;
use App\Models\Job\Country;
use App\Models\Job\EducationLevel;
use App\Models\Job\ExperienceLevel;
use App\Models\Job\Industry;
use App\Models\Job\JobCategory;
use App\Models\Job\JobLocation;
use App\Models\Job\JobType;
use App\Models\Job\SalaryRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    /**
     * GET /api/filters/dropdowns
     *
     * Middleware `verifycountry` injects `country_code` into the request.
     * Country-scoped entities return rows for the current country PLUS
     * global rows (country_code IS NULL). Entities without a country_code
     * column are returned in full.
     */
    public function dropdowns(Request $request): JsonResponse
    {
        // Middleware already merges this in; fallback only for direct calls.
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        return response()->json([
            'success' => true,
            'data'    => [
                'country_code' => $countryCode,

                'categories' => JobCategory::where('is_active', true)
                    ->where(function ($q) use ($countryCode) {
                        $q->where('country_code', $countryCode)
                          ->orWhereNull('country_code');
                    })
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn($c) => ['id' => $c->id, 'label' => $c->name])
                    ->values(),

                // No country_code column -> return all
                'industries' => Industry::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn($i) => ['id' => $i->id, 'label' => $i->name])
                    ->values(),

                // No country_code column -> return all
                'job_types' => JobType::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn($t) => ['id' => $t->id, 'label' => $t->name])
                    ->values(),

                // Strictly country-scoped (no null fallback - locations belong to a country)
                'locations' => JobLocation::where('is_active', true)
                    ->where('country_code', $countryCode)
                    ->orderBy('district')
                    ->get(['id', 'district', 'city'])
                    ->map(fn($l) => [
                        'id'    => $l->id,
                        'label' => $l->city ? "{$l->district} ({$l->city})" : $l->district,
                    ])
                    ->values(),

                // No country_code column -> return all
                'experience_levels' => ExperienceLevel::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'min_years', 'max_years'])
                    ->map(fn($e) => [
                        'id'    => $e->id,
                        'label' => $e->name . ($e->min_years !== null
                            ? " ({$e->min_years}-" . ($e->max_years ?? '∞') . " yrs)"
                            : ''),
                    ])
                    ->values(),

                'education_levels' => EducationLevel::where('is_active', true)
                    ->where(function ($q) use ($countryCode) {
                        $q->where('country_code', $countryCode)
                          ->orWhereNull('country_code');
                    })
                    ->orderBy('sort_order')
                    ->get(['id', 'name'])
                    ->map(fn($e) => ['id' => $e->id, 'label' => $e->name])
                    ->values(),

                'salary_ranges' => SalaryRange::where('is_active', true)
                    ->where(function ($q) use ($countryCode) {
                        $q->where('country_code', $countryCode)
                          ->orWhereNull('country_code');
                    })
                    ->orderBy('min_salary')
                    ->get(['id', 'name', 'currency'])
                    ->map(fn($s) => ['id' => $s->id, 'label' => $s->name])
                    ->values(),

                'countries' => Country::where('is_active', true)
                    ->orderBy('name')
                    ->get(['code', 'name', 'flag'])
                    ->map(fn($c) => [
                        'code'  => $c->code,
                        'label' => ($c->flag ?? '🌍') . ' ' . $c->name,
                    ])
                    ->values(),
            ],
        ]);
    }
}