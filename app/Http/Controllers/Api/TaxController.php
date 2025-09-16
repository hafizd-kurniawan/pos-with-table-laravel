<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxController extends Controller
{
    /**
     * Display a listing of taxes
     */
    public function index(): JsonResponse
    {
        $taxes = Tax::orderBy('type')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Taxes',
            'data' => $taxes
        ]);
    }

    /**
     * Store a newly created tax
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:layanan,pajak',
            'value' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string'
        ]);

        $tax = Tax::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tax Created Successfully',
            'data' => $tax
        ], 201);
    }

    /**
     * Display the specified tax
     */
    public function show(Tax $tax): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Tax Detail',
            'data' => $tax
        ]);
    }

    /**
     * Update the specified tax
     */
    public function update(Request $request, Tax $tax): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:layanan,pajak',
            'value' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string'
        ]);

        $tax->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tax Updated Successfully',
            'data' => $tax->fresh()
        ]);
    }

    /**
     * Remove the specified tax
     */
    public function destroy(Tax $tax): JsonResponse
    {
        $tax->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax Deleted Successfully'
        ]);
    }

    /**
     * Get active taxes only
     */
    public function active(): JsonResponse
    {
        $taxes = Tax::active()->get();

        return response()->json([
            'success' => true,
            'message' => 'Active Taxes',
            'data' => $taxes
        ]);
    }

    /**
     * Get taxes by type (layanan or pajak)
     */
    public function byType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:layanan,pajak'
        ]);

        $taxes = Tax::where('type', $request->type)->active()->get();

        return response()->json([
            'success' => true,
            'message' => 'Taxes by Type',
            'data' => $taxes
        ]);
    }

    /**
     * Calculate tax amount
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'tax_id' => 'required|exists:taxes,id',
            'amount' => 'required|numeric|min:0'
        ]);

        $tax = Tax::findOrFail($request->tax_id);
        $taxAmount = $tax->calculateTax($request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Tax Calculated',
            'data' => [
                'tax' => $tax,
                'original_amount' => $request->amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $request->amount + $taxAmount
            ]
        ]);
    }
}
