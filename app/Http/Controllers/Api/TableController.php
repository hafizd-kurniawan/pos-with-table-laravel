<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    /**
     * Display a listing of tables
     */
    public function index(): JsonResponse
    {
        $tables = Table::with(['currentReservation', 'category'])
                      ->orderBy('name')
                      ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Tables',
            'data' => $tables
        ]);
    }

    /**
     * Store a newly created table
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:tables,name',
            'category_id' => 'required|exists:table_categories,id',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'party_size' => 'nullable|integer|min:0',
            'x_position' => 'nullable|numeric',
            'y_position' => 'nullable|numeric'
        ]);

        $table = Table::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'party_size' => $request->party_size ?? 0,
            'x_position' => $request->x_position ?? 0,
            'y_position' => $request->y_position ?? 0,
            'status' => 'available',
            'table_status' => 'available',
            'service_type' => 'dine_in'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Table Created Successfully',
            'data' => $table->load('category')
        ], 201);
    }

    /**
     * Display the specified table
     */
    public function show(Table $table): JsonResponse
    {
        $table->load(['reservations', 'orders', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Table Detail',
            'data' => $table
        ]);
    }

    /**
     * Update the specified table
     */
    public function update(Request $request, Table $table): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|unique:tables,name,' . $table->id,
            'category_id' => 'sometimes|exists:table_categories,id',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'party_size' => 'sometimes|integer|min:0',
            'x_position' => 'sometimes|numeric',
            'y_position' => 'sometimes|numeric',
            'status' => 'sometimes|in:available,occupied,reserved,pending_bill'
        ]);

        // Handle position sync
        $updateData = $request->only([
            'name', 'category_id', 'description', 'location', 'capacity', 'party_size', 'status'
        ]);

        if ($request->has('x_position')) {
            $updateData['x_position'] = $request->x_position;
        }

        if ($request->has('y_position')) {
            $updateData['y_position'] = $request->y_position;
        }

        $table->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Table Updated Successfully',
            'data' => $table->fresh()->load('category')
        ]);
    }

    /**
     * Remove the specified table
     */
    public function destroy(Table $table): JsonResponse
    {
        // Check if table has active reservations or orders
        if ($table->reservations()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete table with active reservations'
            ], 422);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table Deleted Successfully'
        ]);
    }

    /**
     * Update table position (for drag & drop)
     */
    public function updatePosition(Request $request, Table $table): JsonResponse
    {
        $request->validate([
            'x_position' => 'required|numeric',
            'y_position' => 'required|numeric'
        ]);

        $table->update([
            'x_position' => $request->x_position,
            'y_position' => $request->y_position
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Table Position Updated',
            'data' => $table
        ]);
    }

    /**
     * Update table status
     */
    public function updateStatus(Request $request, Table $table): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,pending_bill',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'party_size' => 'nullable|integer|min:1'
        ]);

        $updateData = ['status' => $request->status];

        if ($request->status === 'occupied' || $request->status === 'reserved') {
            $updateData['customer_name'] = $request->customer_name;
            $updateData['customer_phone'] = $request->customer_phone;
            $updateData['party_size'] = $request->party_size;
            $updateData['occupied_at'] = now();
        } elseif ($request->status === 'available') {
            $updateData['customer_name'] = null;
            $updateData['customer_phone'] = null;
            $updateData['party_size'] = null;
            $updateData['occupied_at'] = null;
            $updateData['order_id'] = null;
        }

        $table->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Table Status Updated',
            'data' => $table->fresh()
        ]);
    }

    /**
     * Get available tables
     */
    public function available(): JsonResponse
    {
        $tables = Table::available()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Available Tables',
            'data' => $tables
        ]);
    }

    /**
     * Get tables by category
     */
    public function byCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:table_categories,id'
        ]);

        $tables = Table::where('category_id', $request->category_id)
                      ->with('category')
                      ->orderBy('name')
                      ->get();

        return response()->json([
            'success' => true,
            'message' => "Tables in selected category",
            'data' => $tables
        ]);
    }

    /**
     * Get table categories with counts
     */
    public function categories(): JsonResponse
    {
        $categories = \App\Models\TableCategory::active()->ordered()->get();

        $categoryCounts = $categories->map(function ($category) {
            $total = Table::where('category_id', $category->id)->count();
            $available = Table::where('category_id', $category->id)
                             ->where('status', 'available')
                             ->count();
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'description' => $category->description,
                'total' => $total,
                'available' => $available,
                'occupied' => $total - $available
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Table Categories',
            'data' => $categoryCounts
        ]);
    }
}
