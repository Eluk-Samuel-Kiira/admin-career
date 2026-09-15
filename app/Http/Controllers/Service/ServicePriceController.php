<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;

use App\Models\Currency;
use App\Models\Job\Country;
use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServicePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view service prices')) {
            abort(403, 'You do not have permission to view service prices.');
        }

        return view('service.price');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $serviceId = $request->get('service_id', '');
        $countryCode = $request->get('country_code', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = ServicePrice::with(['service', 'country', 'currency']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('service', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhere('country_code', 'like', "%{$search}%")
                  ->orWhereHas('country', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('currency', fn($q) => $q->where('code', 'like', "%{$search}%"));
            });
        }

        if (!empty($serviceId)) {
            $query->where('service_id', $serviceId);
        }

        if (!empty($countryCode)) {
            $query->where('country_code', strtoupper($countryCode));
        }

        if ($status !== '' && $status !== null) {
            $query->where('is_active', (bool) $status);
        }

        $prices = $query->orderBy('service_id', 'asc')
            ->orderBy('country_code', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $prices->getCollection()->transform(function ($item) {
            $item->service_name    = $item->service->name ?? '—';
            $item->country_label   = $item->country_label;
            $item->currency_code   = $item->currency->code ?? '—';
            $item->formatted_price = $item->formatted_amount;
            $item->interval_label  = $item->interval_label;
            $item->status_badge    = $item->status_badge;
            return $item;
        });

        return response()->json($prices);
    }

    /**
     * Get services for dropdowns.
     */
    public function getServices()
    {
        return response()->json([
            'success'  => true,
            'services' => Service::active()->ordered()->get(['id', 'name', 'billing_type']),
        ]);
    }

    /**
     * Get countries for dropdowns (active only) + synthetic Global row.
     */
    public function getCountries()
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'flag' => $c->flag ?? '🌍',
            ])
            ->prepend([
                'code' => ServicePrice::GLOBAL_CODE,  // now 'XX'
                'name' => 'Global (Fallback)',
                'flag' => '🌍',
            ])
            ->values();

        return response()->json([
            'success'   => true,
            'countries' => $countries,
        ]);
    }

    /**
     * Get currencies for dropdowns.
     */
    public function getCurrencies()
    {
        $currencies = Currency::orderBy('code')
            ->get(['id', 'code', 'name', 'symbol', 'decimal_places']);

        return response()->json([
            'success'    => true,
            'currencies' => $currencies,
        ]);
    }


    public function store(Request $request)
    {
        if (!auth()->user()->can('create service prices')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create service prices.',
            ]);
        }

        try {
            $validated = $request->validate([
                'service_id'   => 'required|exists:services,id',
                'country_code' => 'required|string|size:2',
                'currency_id'  => 'required|exists:currencies,id',
                'amount'       => 'required|numeric|min:0',
                'interval'     => ['nullable', Rule::in(['month', 'year'])],
                'is_active'    => 'nullable|boolean',
            ]);

            $service  = Service::findOrFail($validated['service_id']);
            $currency = Currency::findOrFail($validated['currency_id']);

            $data = $validated;
            $data['country_code'] = strtoupper($validated['country_code']);

            // ✅ Delegate to Currency so UGX (0 decimals) and USD (2 decimals) both work
            $data['amount_cents'] = $currency->toCents((float) $validated['amount']);

            $data['is_active'] = ServicePrice::normalizeBool(
                $request->input('is_active', true)
            );

            // Country code validation: either GLOBAL or a real country
            if ($data['country_code'] !== ServicePrice::GLOBAL_CODE) {
                $exists = Country::where('code', $data['country_code'])->exists();
                if (!$exists) {
                    return response()->json([
                        'success' => false,
                        'message' => "Country code '{$data['country_code']}' does not exist.",
                    ], 422);
                }
            }

            // Business rules by billing type
            if ($service->billing_type === 'subscription') {
                if (empty($data['interval'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Subscription services require a billing interval (month/year).',
                    ], 422);
                }
            } else {
                $data['interval'] = null;
            }

            if ($service->billing_type === 'free') {
                $data['amount_cents'] = 0;
            }

            // Uniqueness (service_id, country_code)
            $exists = ServicePrice::where('service_id', $data['service_id'])
                ->where('country_code', $data['country_code'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A price already exists for this service in this country.',
                ], 422);
            }

            unset($data['amount']); // not a column

            $price = ServicePrice::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Service price created successfully!',
                'data'    => $price->load(['service', 'country', 'currency']),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create service price: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service price: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view service prices')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view service prices.',
            ]);
        }

        try {
            $price = ServicePrice::with(['service', 'country', 'currency'])->findOrFail($id);

            // `amount` is now an accessor on the model — returns a float in
            // the correct display value (25.00 for USD, 95000 for UGX).
            $price->amount          = $price->amount; // triggers accessor
            $price->formatted_price = $price->formatted_amount;
            $price->country_label   = $price->country_label;
            $price->status_badge    = $price->status_badge;

            return response()->json($price);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service price not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit service prices')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit service prices.',
            ]);
        }

        try {
            $price = ServicePrice::findOrFail($id);

            $validated = $request->validate([
                'service_id'   => 'required|exists:services,id',
                'country_code' => 'required|string|max:5',
                'currency_id'  => 'required|exists:currencies,id',
                'amount'       => 'required|numeric|min:0',
                'interval'     => ['nullable', Rule::in(['month', 'year'])],
                'is_active'    => 'nullable|boolean',
            ]);

            $service  = Service::findOrFail($validated['service_id']);
            $currency = Currency::findOrFail($validated['currency_id']);

            $data = $validated;
            $data['country_code'] = strtoupper($validated['country_code']);
            $data['amount_cents'] = $currency->toCents((float) $validated['amount']);

            $data['is_active'] = ServicePrice::normalizeBool(
                $request->input('is_active', true)
            );

            if ($data['country_code'] !== ServicePrice::GLOBAL_CODE) {
                $exists = Country::where('code', $data['country_code'])->exists();
                if (!$exists) {
                    return response()->json([
                        'success' => false,
                        'message' => "Country code '{$data['country_code']}' does not exist.",
                    ], 422);
                }
            }

            if ($service->billing_type === 'subscription') {
                if (empty($data['interval'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Subscription services require a billing interval (month/year).',
                    ], 422);
                }
            } else {
                $data['interval'] = null;
            }

            if ($service->billing_type === 'free') {
                $data['amount_cents'] = 0;
            }

            $exists = ServicePrice::where('service_id', $data['service_id'])
                ->where('country_code', $data['country_code'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A price already exists for this service in this country.',
                ], 422);
            }

            unset($data['amount']);

            $price->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Service price updated successfully!',
                'data'    => $price->fresh(['service', 'country', 'currency']),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service price not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update service price: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service price: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete service prices')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete service prices.',
            ]);
        }

        try {
            $price = ServicePrice::findOrFail($id);
            $price->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service price deleted successfully!',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete service price: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service price: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit service prices')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit service prices.',
            ]);
        }

        try {
            $price = ServicePrice::findOrFail($id);
            $price->is_active = !$price->is_active;
            $price->save();

            return response()->json([
                'success'   => true,
                'message'   => $price->is_active
                    ? 'Price activated successfully!'
                    : 'Price deactivated successfully!',
                'is_active' => $price->is_active,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }
}