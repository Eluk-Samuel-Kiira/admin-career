<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display users list.
     */
    public function index()
    {
        return view('admin.users.index');
    }

    /**
     * Get all users with pagination and search.
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = User::with(['roles', 'permissions', 'department']);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        
        $users = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $data = [
            'current_page' => $users->currentPage(),
            'data' => collect($users->items())->map(function($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'avatar' => $user->avatar,
                    'department_id' => $user->department_id,
                    'department_name' => $user->department?->name,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('M d, Y H:i:s') : 'Never',
                    'created_at' => $user->created_at->format('M d, Y'),
                ];
            })->toArray(),
            'first_page_url' => $users->url(1),
            'from' => $users->firstItem(),
            'last_page' => $users->lastPage(),
            'last_page_url' => $users->url($users->lastPage()),
            'next_page_url' => $users->nextPageUrl(),
            'prev_page_url' => $users->previousPageUrl(),
            'to' => $users->lastItem(),
            'total' => $users->total(),
            'per_page' => $perPage,
        ];
        
        return response()->json($data);
    }

    /**
     * Get all departments for dropdown.
     */
    public function getDepartments()
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        // Make sure we return an array even if empty
        return response()->json($departments ?: []);
    }

    /**
     * Get all roles for assignment.
     */
    public function getRoles()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);
        return response()->json($roles);
    }

    /**
     * Get all permissions for assignment.
     */
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get(['id', 'name']);
        return response()->json($permissions);
    }

    /**
     * Get user's direct permissions.
     */
    public function getUserPermissions($id)
    {
        try {
            $user = User::findOrFail($id);
            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
            $roles = $user->roles->pluck('name')->toArray();
            $allPermissions = Permission::orderBy('name')->get(['id', 'name']);
            
            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'direct_permissions' => $directPermissions,
                'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->toArray(),
                'roles' => $roles,
                'all_permissions' => $allPermissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Assign direct permission to user.
     */
    public function assignPermission(Request $request, $id)
    {
        if (!auth()->user()->can('assign permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign permissions.'
            ]);
        }

        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($id);
            
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify permissions of a Super Admin user'
                ], 403);
            }
            
            $user->givePermissionTo($request->permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission assigned successfully',
                'permission' => $request->permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke direct permission from user.
     */
    public function revokePermission(Request $request, $id)
    {
        
        if (!auth()->user()->can('revoke permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to revoke permissions.'
            ]);
        }

        $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        try {
            $user = User::findOrFail($id);
            
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify permissions of a Super Admin user'
                ], 403);
            }
            
            $user->revokePermissionTo($request->permission);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully',
                'permission' => $request->permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new user.
     */
    public function storeUser(Request $request)
    {
          
        if (!auth()->user()->can('create users')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create users.'
            ]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        try {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'department_id' => $request->department_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country_code' => $request->country_code ?? '+256',
                'password' => Hash::make($request->password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            $user->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details for editing.
     */
    public function getUser($id)
    {
        try {
            $user = User::with(['roles', 'department'])->find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'id' => $user->id,
                'department_id' => $user->department_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'role' => $user->roles->first()->name ?? null,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->hasRole('super_admin'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    /**
     * Update a user.
     */
    public function updateUser(Request $request, $id)
    {
           
        if (!auth()->user()->can('edit users')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit users.'
            ]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id,
            'role' => 'required|exists:roles,name',
        ]);

        try {
            $user = User::findOrFail($id);
            
            if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot modify a Super Admin user'
                ], 403);
            }
            
            $user->department_id = $request->department_id;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country_code = $request->country_code ?? $user->country_code;
            
            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8|confirmed']);
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            $user->syncRoles([$request->role]);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status.
     */
    public function toggleUserStatus($id)
    {
        if (!auth()->user()->can('edit users')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit users.'
            ]);
        }

        try {
            $user = User::findOrFail($id);
            
            if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate a Super Admin user'
                ], 403);
            }
            
            $user->is_active = !$user->is_active;
            $user->save();
            
            $status = $user->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "User {$status} successfully",
                'is_active' => $user->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle user status'
            ], 500);
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        if (!auth()->user()->can('delete users')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete users.'
            ]);
        }

        try {
            $user = User::findOrFail($id);
            
            if ($user->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a Super Admin user'
                ], 403);
            }
            
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}