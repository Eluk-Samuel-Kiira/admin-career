<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentPurpose;
use Illuminate\Support\Str;

class PaymentPurposeController extends Controller
{
    public function index()
    {
        return view('finance.payment-purposes.index');
    }

    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = PaymentPurpose::withCount('deposits');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $purposes = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = [
            'current_page' => $purposes->currentPage(),
            'data' => $purposes->items(),
            'first_page_url' => $purposes->url(1),
            'from' => $purposes->firstItem(),
            'last_page' => $purposes->lastPage(),
            'last_page_url' => $purposes->url($purposes->lastPage()),
            'next_page_url' => $purposes->nextPageUrl(),
            'prev_page_url' => $purposes->previousPageUrl(),
            'to' => $purposes->lastItem(),
            'total' => $purposes->total(),
            'per_page' => $perPage,
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create payment purposes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create payment purposes.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:payment_purposes',
            'category' => 'required|in:income,expense,transfer,other',
        ]);

        $purpose = PaymentPurpose::create([
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
            'message' => 'Payment purpose created successfully',
            'purpose' => $purpose
        ]);
    }

    public function show($id)
    {
        $purpose = PaymentPurpose::findOrFail($id);
        return response()->json($purpose);
    }

    public function update(Request $request, $id)
    {
          
        if (!auth()->user()->can('edit payment purposes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payment purposes.'
            ]);
        }

        $purpose = PaymentPurpose::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:payment_purposes,slug,' . $id,
            'category' => 'required|in:income,expense,transfer,other',
        ]);

        $purpose->update([
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
            'message' => 'Payment purpose updated successfully',
            'purpose' => $purpose
        ]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('delete payment purposes')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete payment purposes.'
            ]);
        }
        $purpose = PaymentPurpose::findOrFail($id);
        
        // Check if purpose has deposits
        if ($purpose->deposits()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete purpose. It is used in ' . $purpose->deposits()->count() . ' deposit(s).'
            ], 400);
        }

        $purpose->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment purpose deleted successfully'
        ]);
    }
}