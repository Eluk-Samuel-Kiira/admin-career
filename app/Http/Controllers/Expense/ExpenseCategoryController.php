<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExpenseCategory;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
    /**
     * Display expense categories list.
     */
    public function index()
    {
        return view('expense.categories.index');
    }

    /**
     * Get all expense categories with pagination and search.
     */
    public function getCategories(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = ExpenseCategory::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $categories = $query->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = [
            'current_page' => $categories->currentPage(),
            'data' => $categories->items(),
            'first_page_url' => $categories->url(1),
            'from' => $categories->firstItem(),
            'last_page' => $categories->lastPage(),
            'last_page_url' => $categories->url($categories->lastPage()),
            'next_page_url' => $categories->nextPageUrl(),
            'prev_page_url' => $categories->previousPageUrl(),
            'to' => $categories->lastItem(),
            'total' => $categories->total(),
            'per_page' => $perPage,
        ];

        return response()->json($data);
    }

    /**
     * Get all categories for dropdown.
     */
    public function getAll()
    {
        $categories = ExpenseCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'requires_approval', 'requires_receipt']);
        
        return response()->json($categories);
    }

    /**
     * Store a new expense category.
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create expense categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create expense categories.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:expense_categories',
            'description' => 'nullable|string',
            'budget_monthly' => 'nullable|numeric|min:0',
            'budget_annual' => 'nullable|numeric|min:0',
        ]);

        try {
            $category = ExpenseCategory::create([
                'name' => $request->name,
                'code' => $request->code ?? strtoupper(substr(Str::slug($request->name), 0, 10)),
                'description' => $request->description,
                'requires_receipt' => $request->boolean('requires_receipt'),
                'requires_approval' => $request->boolean('requires_approval'),
                'budget_monthly' => $request->budget_monthly,
                'budget_annual' => $request->budget_annual,
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense category created successfully',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category details for editing.
     */
    public function show($id)
    {
        try {
            $category = ExpenseCategory::findOrFail($id);
            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }

    /**
     * Update an expense category.
     */
    public function update(Request $request, $id)
    {
           
        if (!auth()->user()->can('edit expense categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit expense categories.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:expense_categories,code,' . $id,
            'description' => 'nullable|string',
            'budget_monthly' => 'nullable|numeric|min:0',
            'budget_annual' => 'nullable|numeric|min:0',
        ]);

        try {
            $category = ExpenseCategory::findOrFail($id);
            $category->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'requires_receipt' => $request->boolean('requires_receipt'),
                'requires_approval' => $request->boolean('requires_approval'),
                'budget_monthly' => $request->budget_monthly,
                'budget_annual' => $request->budget_annual,
                'is_active' => $request->boolean('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense category updated successfully',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an expense category.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete expense categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete expense categories.'
            ]);
        }

        try {
            $category = ExpenseCategory::findOrFail($id);
            
            // Check if category has expenses using the correct relationship
            // Use the expenses() relationship with the correct foreign key
            $expensesCount = $category->expenses()->count();
            
            if ($expensesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category. It has ' . $expensesCount . ' expense(s) associated.'
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle category status.
     */
    public function toggleStatus($id)
    {
         
        if (!auth()->user()->can('edit expense categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit expense categories.'
            ]);
        }

        try {
            $category = ExpenseCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category ' . ($category->is_active ? 'activated' : 'deactivated') . ' successfully',
                'is_active' => $category->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status'
            ], 500);
        }
    }
}