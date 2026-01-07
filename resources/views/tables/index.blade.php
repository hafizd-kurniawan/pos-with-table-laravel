@extends('layouts.app')

@section('title', 'Table Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">
        <i class="bi bi-building text-primary"></i>
        Table Management
    </h1>
    <a href="{{ route('table-management.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Table
    </a>
</div>

<!-- Category Statistics -->
<div class="row mb-4">
    @foreach($categoryStats as $stat)
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card stats-card h-100 border-0">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <span class="badge category-badge" style="background-color: {{ $stat['color'] }}">
                            {{ $stat['icon'] }} {{ $stat['name'] }}
                        </span>
                    </div>
                    <div class="row text-center">
                        <div class="col">
                            <div class="fs-4 fw-bold text-success">{{ $stat['available'] }}</div>
                            <small class="text-muted">Available</small>
                        </div>
                        <div class="col">
                            <div class="fs-4 fw-bold text-warning">{{ $stat['occupied'] }}</div>
                            <small class="text-muted">Occupied</small>
                        </div>
                        <div class="col">
                            <div class="fs-4 fw-bold text-primary">{{ $stat['total'] }}</div>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Filter & Search -->
<div class="card filter-card mb-4">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-funnel"></i> Filters & Search
        </h5>
        
        <form method="GET" action="{{ route('table-management.index') }}" class="row g-3">
            <!-- Search -->
            <div class="col-md-3">
                <label class="form-label">Search Table</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Table name..." 
                       value="{{ request('search') }}">
            </div>

            <!-- Category Filter -->
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" 
                                {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->icon }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" 
                                {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Location Filter -->
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" 
                       placeholder="Location..." 
                       value="{{ request('location') }}">
            </div>

            <!-- Action Buttons -->
            <div class="col-12">
                <button type="submit" class="btn btn-light">
                    <i class="bi bi-search"></i> Apply Filters
                </button>
                <a href="{{ route('table-management.index') }}" class="btn btn-outline-light">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Quick Category Filters -->
<div class="mb-4">
    <h6>Quick Filter by Category:</h6>
    <div class="d-flex flex-wrap">
        <a href="{{ route('table-management.index') }}" 
           class="btn btn-outline-primary btn-category {{ !request('category') ? 'active' : '' }}">
            <i class="bi bi-list"></i> All
        </a>
        @foreach($categories as $category)
            <a href="{{ route('table-management.index', ['category' => $category->id]) }}" 
               class="btn btn-outline-primary btn-category {{ request('category') == $category->id ? 'active' : '' }}">
                {{ $category->icon }} {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>

<!-- Tables Grid -->
@if($tables->count() > 0)
    <div class="row">
        @foreach($tables as $table)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card table-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-table"></i> {{ $table->name }}
                        </h5>
                        <span class="badge status-badge 
                            @switch($table->status)
                                @case('available') bg-success @break
                                @case('occupied') bg-warning text-dark @break
                                @case('reserved') bg-info @break
                                @case('maintenance') bg-danger @break
                                @default bg-secondary
                            @endswitch
                        ">
                            {{ ucfirst($table->status) }}
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <!-- Category Badge -->
                        <div class="mb-3">
                            <span class="badge category-badge" style="background-color: {{ $table->category->color ?? '#6366f1' }}">
                                {{ $table->category->icon ?? '🪑' }} {{ $table->category->name ?? 'No Category' }}
                            </span>
                        </div>

                        <!-- Table Info -->
                        <div class="mb-3">
                            <div class="row text-center">
                                <div class="col">
                                    <div class="fw-bold text-primary">{{ $table->capacity }}</div>
                                    <small class="text-muted">Capacity</small>
                                </div>
                                @if($table->party_size)
                                <div class="col">
                                    <div class="fw-bold text-warning">{{ $table->party_size }}</div>
                                    <small class="text-muted">Current Party</small>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Location -->
                        @if($table->location)
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt"></i> {{ $table->location }}
                            </p>
                        @endif

                        <!-- Description -->
                        @if($table->description)
                            <p class="text-muted small mb-3">{{ Str::limit($table->description, 60) }}</p>
                        @endif

                        <!-- Position -->
                        @if($table->position_x || $table->position_y)
                            <p class="text-muted small mb-2">
                                <i class="bi bi-crosshair"></i> Position: ({{ $table->position_x }}, {{ $table->position_y }})
                            </p>
                        @endif
                    </div>

                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="{{ route('table-management.show', $table) }}" 
                               class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="{{ route('table-management.edit', $table) }}" 
                               class="btn btn-outline-warning btn-sm flex-fill">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="{{ route('table-management.destroy', $table) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to delete this table?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $tables->withQueryString()->links() }}
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-building display-1 text-muted"></i>
        <h3 class="text-muted mt-3">No tables found</h3>
        <p class="text-muted">
            @if(request()->hasAny(['category', 'status', 'search', 'location']))
                No tables match your current filters. 
                <a href="{{ route('table-management.index') }}">Clear filters</a> to see all tables.
            @else
                Start by creating your first table.
            @endif
        </p>
        <a href="{{ route('table-management.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Table
        </a>
    </div>
@endif
@endsection
