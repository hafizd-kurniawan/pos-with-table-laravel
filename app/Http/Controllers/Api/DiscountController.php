<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    /**
     * Display a listing of discounts
     */
    public function index(): JsonResponse
    {
        $discounts = Discount::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Discounts',
            'data' => $discounts
        ]);
    }

    /**
     * Store a newly created discount
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'expired_date' => 'nullable|date|after:today'
        ]);

        $discount = Discount::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Discount Created Successfully',
            'data' => $discount
        ], 201);
    }

    /**
     * Display the specified discount
     */
    public function show(Discount $discount): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Discount Detail',
            'data' => $discount
        ]);
    }

    /**
     * Update the specified discount
     */
    public function update(Request $request, Discount $discount): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'expired_date' => 'nullable|date|after:today'
        ]);

        $discount->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Discount Updated Successfully',
            'data' => $discount->fresh()
        ]);
    }

    /**
     * Remove the specified discount
     */
    public function destroy(Discount $discount): JsonResponse
    {
        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Discount Deleted Successfully'
        ]);
    }

    /**
     * Get active discounts only
     */
    public function active(): JsonResponse
    {
        $discounts = Discount::active()->get();

        return response()->json([
            'success' => true,
            'message' => 'Active Discounts',
            'data' => $discounts
        ]);
    }
}
