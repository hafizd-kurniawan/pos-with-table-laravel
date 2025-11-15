<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Get all suppliers
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = Supplier::where('tenant_id', $tenantId);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $suppliers = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Get single supplier
     */
    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $supplier = Supplier::where('tenant_id', $tenantId)
            ->with(['ingredients', 'purchaseOrders'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Create supplier
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $validated['tenant_id'] = $tenantId;
        $validated['code'] = Supplier::generateCode($tenantId);
        $validated['status'] = 'active';
        
        $supplier = Supplier::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Update supplier
     */
    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $supplier = Supplier::where('tenant_id', $tenantId)->findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'notes' => 'nullable|string',
        ]);
        
        $supplier->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier->fresh(),
        ]);
    }

    /**
     * Delete supplier
     */
    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $supplier = Supplier::where('tenant_id', $tenantId)->findOrFail($id);
        
        // Check if has associated ingredients or POs
        if ($supplier->ingredients()->count() > 0 || $supplier->purchaseOrders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with associated ingredients or purchase orders',
            ], 422);
        }
        
        $supplier->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully',
        ]);
    }
}
