<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    /**
     * Display a listing of tables with category filter
     */
    public function index(Request $request): View
    {
        $query = Table::with('category');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Search by table name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $tables = $query->orderBy('name')->paginate(20);

        // Get category statistics
        $categoryStats = $this->getCategoryStats();

        // Get filter options
        $categories = TableCategory::active()->ordered()->get();
        $statuses = [
            'available' => 'Available',
            'occupied' => 'Occupied',
            'reserved' => 'Reserved',
            'maintenance' => 'Maintenance'
        ];

        return view('tables.index', compact('tables', 'categoryStats', 'categories', 'statuses'));
    }

    /**
     * Show the form for creating a new table
     */
    public function create(): View
    {
        $categories = TableCategory::active()->ordered()->get();
        return view('tables.create', compact('categories'));
    }

    /**
     * Store a newly created table
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:tables,name',
            'category_id' => 'required|exists:table_categories,id',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|numeric',
            'position_y' => 'nullable|numeric',
        ]);

        Table::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'position_x' => $request->position_x ?? 0,
            'position_y' => $request->position_y ?? 0,
            'status' => 'available',
        ]);

        return redirect()->route('table-management.index')
                        ->with('success', 'Table created successfully!');
    }

    /**
     * Display the specified table
     */
    public function show(Table $table): View
    {
        $table->load(['reservations' => function($query) {
            $query->where('status', '!=', 'cancelled')
                  ->where('status', '!=', 'completed')
                  ->orderBy('reservation_date')
                  ->orderBy('reservation_time');
        }]);

        return view('tables.show', compact('table'));
    }

    /**
     * Show the form for editing the specified table
     */
    public function edit(Table $table): View
    {
        $categories = TableCategory::active()->ordered()->get();
        return view('tables.edit', compact('table', 'categories'));
    }

    /**
     * Update the specified table
     */
    public function update(Request $request, Table $table)
    {
        $request->validate([
            'name' => 'required|string|unique:tables,name,' . $table->id,
            'category_id' => 'required|exists:table_categories,id',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|numeric',
            'position_y' => 'nullable|numeric',
            'status' => 'required|in:available,occupied,reserved,maintenance'
        ]);

        $table->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'position_x' => $request->position_x,
            'position_y' => $request->position_y,
            'status' => $request->status,
        ]);

        return redirect()->route('table-management.index')
                        ->with('success', 'Table updated successfully!');
    }

    /**
     * Remove the specified table
     */
    public function destroy(Table $table)
    {
        $table->delete();
        
        return redirect()->route('table-management.index')
                        ->with('success', 'Table deleted successfully!');
    }

    /**
     * Get category statistics
     */
    private function getCategoryStats()
    {
        $categories = TableCategory::active()->ordered()->get();
        $stats = [];

        foreach ($categories as $category) {
            $total = Table::where('category_id', $category->id)->count();
            $available = Table::where('category_id', $category->id)->where('status', 'available')->count();
            
            $stats[] = [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'total' => $total,
                'available' => $available,
                'occupied' => $total - $available
            ];
        }

        return $stats;
    }
}
