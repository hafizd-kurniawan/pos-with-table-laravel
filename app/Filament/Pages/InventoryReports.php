<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{StockSummaryExport, StockMovementsExport, PurchaseOrdersExport};
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryReports extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?string $navigationLabel = 'Laporan Inventory';
    
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.inventory-reports';
    
    public ?array $data = [];
    
    public $activeTab = 'stock-summary';
    public $startDate;
    public $endDate;
    public $selectedCategory;
    public $selectedSupplier;
    public $selectedIngredient;
    public $movementType;
    
    public $stockSummary = [];
    public $stockMovements = [];
    public $purchaseOrders = [];
    public $lowStockItems = [];
    public $categoryValue = [];
    
    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        // Load all data on mount
        $this->loadStockSummary();
        $this->loadLowStockItems();
        $this->loadStockMovements();
        $this->loadPurchaseOrders();
        $this->loadCategoryValue();
    }
    
    public function loadStockSummary(): void
    {
        $query = Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->with(['ingredientCategory', 'supplier']);
        
        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }
        
        $this->stockSummary = $query->get()->map(function ($ingredient) {
            return [
                'name' => $ingredient->name,
                'sku' => $ingredient->sku,
                'category' => $ingredient->ingredientCategory?->name ?? '-',
                'current_stock' => $ingredient->current_stock,
                'unit' => $ingredient->unit,
                'cost_per_unit' => $ingredient->cost_per_unit,
                'stock_value' => $ingredient->stock_value,
                'min_stock' => $ingredient->min_stock,
                'status' => $ingredient->stock_status,
            ];
        })->toArray();
    }
    
    public function loadLowStockItems(): void
    {
        $this->lowStockItems = Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->whereRaw('current_stock <= min_stock')
            ->with('ingredientCategory')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'name' => $ingredient->name,
                    'current_stock' => $ingredient->current_stock,
                    'min_stock' => $ingredient->min_stock,
                    'unit' => $ingredient->unit,
                    'shortage' => $ingredient->min_stock - $ingredient->current_stock,
                ];
            })
            ->toArray();
    }
    
    public function loadStockMovements(): void
    {
        $query = StockMovement::where('tenant_id', auth()->user()->tenant_id)
            ->with(['ingredient', 'user'])
            ->whereBetween('moved_at', [
                $this->startDate . ' 00:00:00', 
                $this->endDate . ' 23:59:59'
            ]);
        
        if ($this->selectedIngredient) {
            $query->where('ingredient_id', $this->selectedIngredient);
        }
        
        if ($this->movementType) {
            $query->where('type', $this->movementType);
        }
        
        $this->stockMovements = $query->latest('moved_at')
            ->get()
            ->map(function ($movement) {
                return [
                    'date' => $movement->moved_at->format('d M Y H:i'),
                    'ingredient' => $movement->ingredient->name,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'unit' => $movement->ingredient->unit,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'reference' => $movement->reference_type,
                    'user' => $movement->user->name,
                    'notes' => $movement->notes,
                ];
            })
            ->toArray();
    }
    
    public function loadPurchaseOrders(): void
    {
        $query = PurchaseOrder::where('tenant_id', auth()->user()->tenant_id)
            ->with(['supplier', 'items.ingredient'])
            ->whereDate('order_date', '>=', $this->startDate)
            ->whereDate('order_date', '<=', $this->endDate);
        
        if ($this->selectedSupplier) {
            $query->where('supplier_id', $this->selectedSupplier);
        }
        
        $this->purchaseOrders = $query->latest('order_date')
            ->get()
            ->map(function ($po) {
                return [
                    'po_number' => $po->po_number,
                    'supplier' => $po->supplier->name,
                    'order_date' => $po->order_date->format('d M Y'),
                    'status' => $po->status,
                    'items_count' => $po->items->count(),
                    'total_amount' => $po->total_amount,
                    'received_date' => $po->actual_delivery_date?->format('d M Y'),
                ];
            })
            ->toArray();
    }
    
    public function loadCategoryValue(): void
    {
        $this->categoryValue = Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->select('category_id', DB::raw('SUM(current_stock * cost_per_unit) as total_value'))
            ->with('ingredientCategory')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->ingredientCategory?->name ?? 'Uncategorized',
                    'total_value' => $item->total_value,
                ];
            })
            ->toArray();
    }
    
    public function updatedActiveTab($tab): void
    {
        // Data already loaded in mount, just switch tabs
        // No need to reload
    }
    
    public function getTotalStockValue(): float
    {
        return Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->sum(DB::raw('current_stock * cost_per_unit'));
    }
    
    public function getTotalIngredients(): int
    {
        return Ingredient::where('tenant_id', auth()->user()->tenant_id)->count();
    }
    
    public function getLowStockCount(): int
    {
        return Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->whereRaw('current_stock <= min_stock')
            ->count();
    }
    
    public function getOutOfStockCount(): int
    {
        return Ingredient::where('tenant_id', auth()->user()->tenant_id)
            ->where('current_stock', 0)
            ->count();
    }
    
    /**
     * Format number without unnecessary decimals
     */
    public function formatStock($value): string
    {
        return ($value == floor($value)) 
            ? number_format($value, 0, ',', '.') 
            : number_format($value, 2, ',', '.');
    }
    
    /**
     * Export Methods
     */
    public function exportStockSummaryExcel()
    {
        return Excel::download(
            new StockSummaryExport(auth()->user()->tenant_id, $this->startDate, $this->endDate),
            'stock-summary-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    
    public function exportStockSummaryPdf()
    {
        $data = [
            'title' => 'Stock Summary Report',
            'date' => now()->format('d M Y'),
            'tenant' => $this->cleanText(auth()->user()->tenant->name ?? 'Restaurant'),
            'items' => $this->cleanDataForPdf($this->stockSummary),
            'totalValue' => (float) $this->getTotalStockValue(),
        ];
        
        // Force UTF-8 through JSON
        $data = json_decode(json_encode($data, JSON_UNESCAPED_UNICODE), true);
        
        $pdf = Pdf::loadView('exports.stock-summary-pdf', $data);
        return $pdf->download('stock-summary-' . now()->format('Y-m-d') . '.pdf');
    }
    
    public function exportStockMovementsExcel()
    {
        return Excel::download(
            new StockMovementsExport(
                auth()->user()->tenant_id,
                $this->startDate,
                $this->endDate,
                $this->movementType
            ),
            'stock-movements-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    
    public function exportStockMovementsPdf()
    {
        $data = [
            'title' => 'Stock Movements Report',
            'date_range' => $this->startDate . ' to ' . $this->endDate,
            'tenant' => $this->cleanText(auth()->user()->tenant->name ?? 'Restaurant'),
            'movements' => $this->cleanDataForPdf($this->stockMovements),
            'type' => $this->movementType ? ucfirst($this->movementType) : 'All Types',
        ];
        
        // Force UTF-8 through JSON
        $data = json_decode(json_encode($data, JSON_UNESCAPED_UNICODE), true);
        
        $pdf = Pdf::loadView('exports.stock-movements-pdf', $data);
        return $pdf->download('stock-movements-' . now()->format('Y-m-d') . '.pdf');
    }
    
    public function exportPurchaseOrdersExcel()
    {
        return Excel::download(
            new PurchaseOrdersExport(
                auth()->user()->tenant_id,
                $this->startDate,
                $this->endDate
            ),
            'purchase-orders-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    
    public function exportPurchaseOrdersPdf()
    {
        $data = [
            'title' => 'Purchase Orders Report',
            'date_range' => $this->startDate . ' to ' . $this->endDate,
            'tenant' => $this->cleanText(auth()->user()->tenant->name ?? 'Restaurant'),
            'orders' => $this->cleanDataForPdf($this->purchaseOrders),
        ];
        
        // Force UTF-8 through JSON
        $data = json_decode(json_encode($data, JSON_UNESCAPED_UNICODE), true);
        
        $pdf = Pdf::loadView('exports.purchase-orders-pdf', $data);
        return $pdf->download('purchase-orders-' . now()->format('Y-m-d') . '.pdf');
    }
    
    // UTF-8 Clean Helper for PDF
    private function cleanText($text)
    {
        if (!$text) return '';
        
        // Remove any non-printable characters
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Force UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ASCII', true));
        }
        
        // Replace problematic characters
        $text = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $text);
        
        return trim($text);
    }
    
    private function cleanDataForPdf($data)
    {
        if (is_array($data)) {
            return array_map(function($item) {
                if (is_array($item)) {
                    return array_map(function($value) {
                        // Clean strings
                        if (is_string($value)) {
                            return $this->cleanText($value);
                        }
                        // Convert numeric strings to numbers
                        if (is_numeric($value)) {
                            return floatval($value);
                        }
                        return $value;
                    }, $item);
                }
                // Clean strings
                if (is_string($item)) {
                    return $this->cleanText($item);
                }
                // Convert numeric strings to numbers
                if (is_numeric($item)) {
                    return floatval($item);
                }
                return $item;
            }, $data);
        }
        return $data;
    }
    
    // Export ALL Data (Combined)
    public function exportAllExcel()
    {
        $export = new \App\Exports\AllInventoryExport(
            auth()->user()->tenant_id,
            $this->startDate,
            $this->endDate
        );
        
        return Excel::download($export, 'inventory-all-reports-' . now()->format('Y-m-d') . '.xlsx');
    }
    
    public function exportAllPdf()
    {
        try {
            // Clean all data first
            $stockSummary = $this->cleanDataForPdf($this->stockSummary);
            $stockMovements = $this->cleanDataForPdf($this->stockMovements);
            $purchaseOrders = $this->cleanDataForPdf($this->purchaseOrders);
            $lowStockItems = $this->cleanDataForPdf($this->lowStockItems);
            $categoryValue = $this->cleanDataForPdf($this->categoryValue);
            
            $data = [
                'title' => 'Complete Inventory Reports',
                'date' => now()->format('d M Y'),
                'tenant' => $this->cleanText(auth()->user()->tenant->name ?? 'Restaurant'),
                'stockSummary' => $stockSummary,
                'stockMovements' => $stockMovements,
                'purchaseOrders' => $purchaseOrders,
                'lowStockItems' => $lowStockItems,
                'categoryValue' => $categoryValue,
                'totalValue' => (float) $this->getTotalStockValue(),
                'totalIngredients' => (int) $this->getTotalIngredients(),
                'lowStockCount' => (int) $this->getLowStockCount(),
                'outOfStockCount' => (int) $this->getOutOfStockCount(),
            ];
            
            // Serialize to JSON with proper encoding
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            
            // Generate PDF
            $pdf = Pdf::loadView('exports.all-inventory-pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false);
                
            return $pdf->download('inventory-all-reports-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('PDF Export Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return error response
            return response()->json([
                'error' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
