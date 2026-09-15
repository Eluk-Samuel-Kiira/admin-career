<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view services')) {
            abort(403, 'You do not have permission to view services.');
        }

        return view('service.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $billingType = $request->get('billing_type', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Service::with('creator');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('key', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (!empty($billingType)) {
            $query->where('billing_type', $billingType);
        }

        if ($status !== '' && $status !== null) {
            $query->where('is_active', (bool) $status);
        }

        $services = $query->ordered()
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $services->getCollection()->transform(function ($item) {
            $item->billing_badge    = $item->billing_badge;
            $item->status_badge     = $item->status_badge;
            $item->turnaround_label = $item->turnaround_label;
            $item->billing_label    = $item->billing_type_label;
            return $item;
        });

        return response()->json($services);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create services')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create services.',
            ]);
        }

        try {
            $validated = $request->validate([
                'key'                      => 'nullable|string|max:100|alpha_dash|unique:services,key',
                'name'                     => 'required|string|max:255',
                'description'              => 'nullable|string',
                'default_turnaround_hours' => 'nullable|integer|min:1|max:8760',
                'billing_type'             => ['required', Rule::in(array_keys(Service::getBillingTypes()))],
                'sort_order'               => 'nullable|integer|min:0',
                'is_active'                => 'nullable|boolean',
            ]);

            $data = $validated;

            // Auto-generate key from name if not provided
            if (empty($data['key'])) {
                $data['key'] = Service::generateKey($data['name']);

                // Ensure uniqueness
                $baseKey = $data['key'];
                $counter = 1;
                while (Service::where('key', $data['key'])->exists()) {
                    $data['key'] = $baseKey . '_' . $counter++;
                }
            }

            // Normalize booleans (FormData sends strings)
            $data['is_active'] = $this->normalizeBool($request->input('is_active', false));
            $data['default_turnaround_hours'] = $data['default_turnaround_hours'] ?? 24;
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['created_by'] = auth()->id();

            $service = Service::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully!',
                'data'    => $service,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view services')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view services.',
            ]);
        }

        try {
            $service = Service::with('creator')->findOrFail($id);
            $service->billing_badge    = $service->billing_badge;
            $service->status_badge     = $service->status_badge;
            $service->turnaround_label = $service->turnaround_label;
            $service->billing_label    = $service->billing_type_label;
            return response()->json($service);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit services')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit services.',
            ]);
        }

        try {
            $service = Service::findOrFail($id);

            $validated = $request->validate([
                'key'                      => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('services', 'key')->ignore($id)],
                'name'                     => 'required|string|max:255',
                'description'              => 'nullable|string',
                'default_turnaround_hours' => 'nullable|integer|min:1|max:8760',
                'billing_type'             => ['required', Rule::in(array_keys(Service::getBillingTypes()))],
                'sort_order'               => 'nullable|integer|min:0',
                'is_active'                => 'nullable|boolean',
            ]);

            $data = $validated;

            // If key is empty, regenerate from name
            if (empty($data['key'])) {
                $data['key'] = Service::generateKey($data['name']);
                $baseKey = $data['key'];
                $counter = 1;
                while (Service::where('key', $data['key'])->where('id', '!=', $id)->exists()) {
                    $data['key'] = $baseKey . '_' . $counter++;
                }
            }

            $data['is_active'] = $this->normalizeBool($request->input('is_active', false));
            $data['default_turnaround_hours'] = $data['default_turnaround_hours'] ?? 24;
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $service->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully!',
                'data'    => $service->fresh(),
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
                'message' => 'Service not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete services')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete services.',
            ]);
        }

        try {
            $service = Service::findOrFail($id);

            // Prevent deleting a service that's in use
            if (method_exists($service, 'orders') && $service->orders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this service because it is being used by orders.',
                ], 400);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully!',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit services')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit services.',
            ]);
        }

        try {
            $service = Service::findOrFail($id);
            $service->is_active = !$service->is_active;
            $service->save();

            return response()->json([
                'success'   => true,
                'message'   => $service->is_active
                    ? 'Service activated successfully!'
                    : 'Service deactivated successfully!',
                'is_active' => $service->is_active,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalize a boolean value coming from FormData / JSON.
     */
    private function normalizeBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            return in_array(strtolower($value), ['on', '1', 'true', 'yes'], true);
        }
        return (bool) $value;
    }
}