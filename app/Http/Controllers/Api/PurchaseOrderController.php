<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Get all purchase orders
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = PurchaseOrder::where('tenant_id', $tenantId)
            ->with(['supplier', 'items.ingredient', 'creator']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }
        
        $purchaseOrders = $query->orderBy('order_date', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $purchaseOrders->map(function($po) {
                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'order_date' => $po->order_date->format('Y-m-d'),
                    'expected_delivery_date' => $po->expected_delivery_date?->format('Y-m-d'),
                    'status' => $po->status,
                    'total_amount' => $po->total_amount,
                    'total_amount_formatted' => 'Rp ' . number_format($po->total_amount, 0, ',', '.'),
                    'supplier' => [
                        'id' => $po->supplier->id,
                        'name' => $po->supplier->name,
                        'phone' => $po->supplier->phone,
                    ],
                    'items_count' => $po->items->count(),
                    'created_by' => $po->creator->name,
                ];
            }),
        ]);
    }

    /**
     * Get single purchase order
     */
    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)
            ->with(['supplier', 'items.ingredient', 'creator', 'receiver'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'order_date' => $po->order_date->format('Y-m-d'),
                'expected_delivery_date' => $po->expected_delivery_date?->format('Y-m-d'),
                'actual_delivery_date' => $po->actual_delivery_date?->format('Y-m-d'),
                'status' => $po->status,
                'subtotal' => $po->subtotal,
                'tax' => $po->tax,
                'discount' => $po->discount,
                'shipping_cost' => $po->shipping_cost,
                'total_amount' => $po->total_amount,
                'notes' => $po->notes,
                'supplier' => $po->supplier,
                'items' => $po->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'ingredient' => [
                            'id' => $item->ingredient->id,
                            'name' => $item->ingredient->name,
                            'sku' => $item->ingredient->sku,
                            'unit' => $item->ingredient->unit,
                        ],
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'notes' => $item->notes,
                    ];
                }),
                'created_by' => $po->creator->name,
                'received_by' => $po->receiver?->name,
            ],
        ]);
    }

    /**
     * Create purchase order
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        try {
            $po = DB::transaction(function() use ($validated, $tenantId) {
                // Create PO
                $po = PurchaseOrder::create([
                    'tenant_id' => $tenantId,
                    'supplier_id' => $validated['supplier_id'],
                    'po_number' => PurchaseOrder::generatePONumber($tenantId),
                    'order_date' => $validated['order_date'],
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'status' => PurchaseOrder::STATUS_DRAFT,
                    'tax' => $validated['tax'] ?? 0,
                    'discount' => $validated['discount'] ?? 0,
                    'shipping_cost' => $validated['shipping_cost'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);
                
                // Create PO items
                foreach ($validated['items'] as $itemData) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'ingredient_id' => $itemData['ingredient_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
                
                // Calculate totals (auto-triggered by model events)
                return $po->fresh(['items', 'supplier']);
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $po,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update purchase order (only if draft)
     */
    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($id);
        
        if (!$po->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Can only update draft purchase orders',
            ], 422);
        }
        
        $validated = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'order_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        
        DB::transaction(function() use ($po, $validated) {
            // Update PO
            $po->update(array_filter($validated, function($key) {
                return $key !== 'items';
            }, ARRAY_FILTER_USE_KEY));
            
            // Update items if provided
            if (isset($validated['items'])) {
                // Delete old items
                $po->items()->delete();
                
                // Create new items
                foreach ($validated['items'] as $itemData) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'ingredient_id' => $itemData['ingredient_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                    ]);
                }
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'data' => $po->fresh(['items', 'supplier']),
        ]);
    }

    /**
     * Mark as sent
     */
    public function markAsSent($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($id);
        
        if (!$po->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be sent',
            ], 422);
        }
        
        $po->markAsSent();
        
        return response()->json([
            'success' => true,
            'message' => 'Purchase order marked as sent',
            'data' => $po->fresh(),
        ]);
    }

    /**
     * Receive purchase order and update stock
     */
    public function receive($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($id);
        
        if (!$po->canBeReceived()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be received in current status',
            ], 422);
        }
        
        try {
            $po->receive(Auth::id());
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase order received successfully. Stock updated.',
                'data' => $po->fresh(['items.ingredient']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive purchase order: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($id);
        
        if (!$po->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be cancelled',
            ], 422);
        }
        
        $po->markAsCancelled();
        
        return response()->json([
            'success' => true,
            'message' => 'Purchase order cancelled',
            'data' => $po->fresh(),
        ]);
    }

    /**
     * Delete purchase order (only if draft or cancelled)
     */
    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($id);
        
        if ($po->isReceived()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete received purchase order',
            ], 422);
        }
        
        $po->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Purchase order deleted successfully',
        ]);
    }
}
