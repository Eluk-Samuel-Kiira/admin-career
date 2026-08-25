<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentSource;
use App\Models\Deposit;
use Illuminate\Support\Str;

class PaymentSourceController extends Controller
{
    public function index()
    {
        return view('finance.payment-sources.index');
    }

    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = PaymentSource::withCount('deposits');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $sources = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = [
            'current_page' => $sources->currentPage(),
            'data' => $sources->items(),
            'first_page_url' => $sources->url(1),
            'from' => $sources->firstItem(),
            'last_page' => $sources->lastPage(),
            'last_page_url' => $sources->url($sources->lastPage()),
            'next_page_url' => $sources->nextPageUrl(),
            'prev_page_url' => $sources->previousPageUrl(),
            'to' => $sources->lastItem(),
            'total' => $sources->total(),
            'per_page' => $perPage,
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create payment sources')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create payment sources.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:payment_sources',
            'category' => 'required|in:revenue,capital,loan,other',
        ]);

        $source = PaymentSource::create([
            'name' => $request->name,
            'slug' => Str::slug($request->slug, '_'),
            'icon' => $request->icon,
            'color' => $request->color,
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment source created successfully',
            'source' => $source
        ]);
    }

    public function show($id)
    {
        $source = PaymentSource::findOrFail($id);
        return response()->json($source);
    }

    public function update(Request $request, $id)
    {
        
        if (!auth()->user()->can('edit payment sources')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payment sources.'
            ]);
        }

        $source = PaymentSource::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:payment_sources,slug,' . $id,
            'category' => 'required|in:revenue,capital,loan,other',
        ]);

        $source->update([
            'name' => $request->name,
            'slug' => Str::slug($request->slug, '_'),
            'icon' => $request->icon,
            'color' => $request->color,
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment source updated successfully',
            'source' => $source
        ]);
    }

    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete payment sources')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete payment sources.'
            ]);
        }
        $source = PaymentSource::findOrFail($id);
        
        // Check if source has deposits
        if ($source->deposits()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete source. It is used in ' . $source->deposits()->count() . ' deposit(s).'
            ], 400);
        }

        $source->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment source deleted successfully'
        ]);
    }
}