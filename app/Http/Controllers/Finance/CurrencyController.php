<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;

class CurrencyController extends Controller
{
    /**
     * Display currencies list.
     */
    public function index()
    {
        return view('finance.currencies.index');
    }

    /**
     * Get all currencies with pagination and search.
     */
    public function getCurrencies(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = Currency::query();
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('symbol', 'like', '%' . $search . '%');
            });
        }
        
        $currencies = $query->orderBy('is_default', 'desc')
            ->orderBy('code', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $data = [
            'current_page' => $currencies->currentPage(),
            'data' => collect($currencies->items())->map(function($currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'decimal_places' => $currency->decimal_places,
                    'base_unit_multiplier' => $currency->base_unit_multiplier,
                    'exchange_rate_to_usd' => (float) $currency->exchange_rate_to_usd,
                    'is_active' => $currency->is_active,
                    'is_default' => $currency->is_default,
                    'created_at' => $currency->created_at->format('M d, Y'),
                ];
            })->toArray(),
            'first_page_url' => $currencies->url(1),
            'from' => $currencies->firstItem(),
            'last_page' => $currencies->lastPage(),
            'last_page_url' => $currencies->url($currencies->lastPage()),
            'next_page_url' => $currencies->nextPageUrl(),
            'prev_page_url' => $currencies->previousPageUrl(),
            'to' => $currencies->lastItem(),
            'total' => $currencies->total(),
            'per_page' => $perPage,
        ];
        
        return response()->json($data);
    }

    /**
     * Store a new currency.
     */
    public function storeCurrency(Request $request)
    {
        
        if (!auth()->user()->can('create currencies')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create currencies.'
            ]);
        }

        $request->validate([
            'code' => 'required|string|max:3|unique:currencies',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:4',
            'exchange_rate_to_usd' => 'required|numeric|min:0',
        ]);

        try {
            $currency = Currency::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'decimal_places' => $request->decimal_places,
                'base_unit_multiplier' => pow(10, $request->decimal_places),
                'exchange_rate_to_usd' => $request->exchange_rate_to_usd,
                'is_active' => $request->boolean('is_active'),
                'is_default' => $request->boolean('is_default'),
            ]);
            
            // If this is set as default, remove default from others
            if ($currency->is_default) {
                Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Currency created successfully',
                'currency' => $currency
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create currency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get currency details for editing.
     */
    public function getCurrency($id)
    {
        try {
            $currency = Currency::findOrFail($id);
            return response()->json($currency);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found'
            ], 404);
        }
    }

    /**
     * Update a currency.
     */
    public function updateCurrency(Request $request, $id)
    {
        
        if (!auth()->user()->can('edit currencies')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit currencies.'
            ]);
        }

        $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,' . $id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:4',
            'exchange_rate_to_usd' => 'required|numeric|min:0',
        ]);

        try {
            $currency = Currency::findOrFail($id);
            $wasDefault = $currency->is_default;
            
            $currency->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'decimal_places' => $request->decimal_places,
                'base_unit_multiplier' => pow(10, $request->decimal_places),
                'exchange_rate_to_usd' => $request->exchange_rate_to_usd,
                'is_active' => $request->boolean('is_active'),
                'is_default' => $request->boolean('is_default'),
            ]);
            
            // If this is set as default, remove default from others
            if ($currency->is_default && !$wasDefault) {
                Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully',
                'currency' => $currency
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a currency.
     */
    public function deleteCurrency($id)
    {
        if (!auth()->user()->can('delete currencies')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete currencies.'
            ]);
        }

        try {
            $currency = Currency::findOrFail($id);
            
            if ($currency->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the default currency'
                ], 400);
            }
            
            // Check if currency is in use
            if ($currency->paymentMethods()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete currency. It is being used by payment methods.'
                ], 400);
            }
            
            $currency->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete currency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle currency status.
     */
    public function toggleCurrencyStatus($id)
    {
        if (!auth()->user()->can('edit currencies')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit currencies.'
            ]);
        }
        
        try {
            $currency = Currency::findOrFail($id);
            
            if ($currency->is_default && $currency->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the default currency'
                ], 400);
            }
            
            $currency->is_active = !$currency->is_active;
            $currency->save();
            
            $status = $currency->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "Currency {$status} successfully",
                'is_active' => $currency->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle currency status'
            ], 500);
        }
    }

}
