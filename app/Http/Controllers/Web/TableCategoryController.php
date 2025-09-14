<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TableCategory;
use Illuminate\Http\Request;

class TableCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = TableCategory::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $categories = $query->withCount('tables')->ordered()->paginate(15)->withQueryString();

        return view('table-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('table-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:table_categories',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Set default values
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['color'] = $validated['color'] ?? '#6366f1';

        TableCategory::create($validated);

        return redirect()->route('table-categories.index')
            ->with('success', 'Kategori tabel berhasil dibuat!');
    }

    public function show(TableCategory $tableCategory)
    {
        $tableCategory->load(['tables' => function($query) {
            $query->select('id', 'name', 'status', 'category_id', 'capacity');
        }]);

        $stats = [
            'total_tables' => $tableCategory->tables->count(),
            'available_tables' => $tableCategory->tables->where('status', 'available')->count(),
            'occupied_tables' => $tableCategory->tables->where('status', 'occupied')->count(),
            'reserved_tables' => $tableCategory->tables->where('status', 'reserved')->count(),
        ];

        return view('table-categories.show', compact('tableCategory', 'stats'));
    }

    public function edit(TableCategory $tableCategory)
    {
        return view('table-categories.edit', compact('tableCategory'));
    }

    public function update(Request $request, TableCategory $tableCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:table_categories,name,' . $tableCategory->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Set default values
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? $tableCategory->sort_order;
        $validated['color'] = $validated['color'] ?? '#6366f1';

        $tableCategory->update($validated);

        return redirect()->route('table-categories.index')
            ->with('success', 'Kategori tabel berhasil diperbarui!');
    }

    public function destroy(TableCategory $tableCategory)
    {
        // Check if category has tables
        if ($tableCategory->tables()->count() > 0) {
            return redirect()->route('table-categories.index')
                ->with('error', 'Tidak dapat menghapus kategori yang masih memiliki meja!');
        }

        $tableCategory->delete();

        return redirect()->route('table-categories.index')
            ->with('success', 'Kategori tabel berhasil dihapus!');
    }
}
