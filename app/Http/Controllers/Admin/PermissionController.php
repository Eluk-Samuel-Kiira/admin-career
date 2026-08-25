<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;


class PermissionController extends Controller
{
    /**
     * Display permissions list.
     */
    public function permissions()
    {
        return view('admin.permissions');
    }

    /**
     * Get all permissions with pagination and search.
     */
    public function getPermissions(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = Permission::with('roles');
        
        // Apply search if provided
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        // Get paginated results
        $permissions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        // Format the data
        $data = [
            'current_page' => $permissions->currentPage(),
            'data' => $permissions->items(), // This is already an array
            'first_page_url' => $permissions->url(1),
            'from' => $permissions->firstItem(),
            'last_page' => $permissions->lastPage(),
            'last_page_url' => $permissions->url($permissions->lastPage()),
            'next_page_url' => $permissions->nextPageUrl(),
            'prev_page_url' => $permissions->previousPageUrl(),
            'to' => $permissions->lastItem(),
            'total' => $permissions->total(),
            'per_page' => $perPage,
        ];
        
        // Transform the items
        $data['data'] = collect($data['data'])->map(function($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'roles' => $permission->roles->pluck('name')->toArray(),
                'created_at' => $permission->created_at->format('M d, Y, h:i A'),
            ];
        })->toArray();
        
        return response()->json($data);
    }

    /**
     * Store a new permission.
     */
    public function storePermission(Request $request)
    {
        
        if (!auth()->user()->can('create permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create permissions.'
            ], 403);
        }

        $request->validate([
            'permission_name' => 'required|string|unique:permissions,name'
        ]);

        try {
            $permission = Permission::create([
                'name' => $request->permission_name,
                'guard_name' => 'web'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'roles' => [],
                    'created_at' => $permission->created_at->format('M d, Y, h:i A'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a permission.
     */
    public function updatePermission(Request $request, $id)
    {
         
        if (!auth()->user()->can('edit permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit permissions.'
            ], 403);
        }

        $request->validate([
            'permission_name' => 'required|string|unique:permissions,name,' . $id
        ]);

        try {
            $permission = Permission::findOrFail($id);
            $permission->name = $request->permission_name;
            $permission->save();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'roles' => $permission->roles->pluck('name')->toArray(),
                    'created_at' => $permission->created_at->format('M d, Y, h:i A'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a permission.
     */
    public function deletePermission($id)
    {
        
        if (!auth()->user()->can('delete permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete permissions.'
            ], 403);
        }

        try {
            $permission = Permission::findOrFail($id);
            
            // Check if permission is in use (assigned to any role)
            $rolesCount = $permission->roles()->count();
            
            if ($rolesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission. It is currently assigned to ' . $rolesCount . ' role(s). Please remove it from roles first.'
                ], 400);
            }
            
            $permission->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete permission: ' . $e->getMessage()
            ], 500);
        }
    }
}
