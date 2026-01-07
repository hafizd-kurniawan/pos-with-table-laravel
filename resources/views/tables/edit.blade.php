@extends('layouts.app')

@section('title', 'Edit Table - ' . $table->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h4 class="card-title mb-0">
                    <i class="bi bi-pencil"></i> Edit Table: {{ $table->name }}
                </h4>
            </div>
            
            <div class="card-body">
                <form action="{{ route('table-management.update', $table) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Current Status Alert -->
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <div>
                            <strong>Current Status:</strong>
                            <span class="badge 
                                @switch($table->status)
                                    @case('available') bg-success @break
                                    @case('occupied') bg-warning text-dark @break
                                    @case('reserved') bg-info @break
                                    @case('maintenance') bg-danger @break
                                    @default bg-secondary
                                @endswitch
                                ms-2">
                                {{ ucfirst($table->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="bi bi-info-circle"></i> Basic Information
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Table Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $table->name) }}" 
                                   placeholder="e.g: TABLE-01, VIP-A1"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" 
                                    name="category_id" 
                                    required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ old('category_id', $table->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->icon }} {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Status and Details -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="bi bi-gear"></i> Status & Details
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status" 
                                    required>
                                <option value="available" {{ old('status', $table->status) == 'available' ? 'selected' : '' }}>
                                    Available
                                </option>
                                <option value="occupied" {{ old('status', $table->status) == 'occupied' ? 'selected' : '' }}>
                                    Occupied
                                </option>
                                <option value="reserved" {{ old('status', $table->status) == 'reserved' ? 'selected' : '' }}>
                                    Reserved
                                </option>
                                <option value="maintenance" {{ old('status', $table->status) == 'maintenance' ? 'selected' : '' }}>
                                    Maintenance
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" 
                                   name="capacity" 
                                   value="{{ old('capacity', $table->capacity) }}" 
                                   min="1" 
                                   max="20"
                                   placeholder="Number of seats"
                                   required>
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" 
                                   class="form-control @error('location') is-invalid @enderror" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location', $table->location) }}" 
                                   placeholder="e.g: Lantai 1, Area VIP">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Additional information about this table (facilities, view, etc.)">{{ old('description', $table->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Position -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="bi bi-crosshair"></i> Position (for Layout)
                            </h5>
                            <p class="text-muted small">Set position coordinates for table layout in mobile app</p>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="position_x" class="form-label">X Position (pixels)</label>
                            <input type="number" 
                                   class="form-control @error('position_x') is-invalid @enderror" 
                                   id="position_x" 
                                   name="position_x" 
                                   value="{{ old('position_x', $table->position_x ?? $table->x_position ?? 0) }}" 
                                   placeholder="0">
                            @error('position_x')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="position_y" class="form-label">Y Position (pixels)</label>
                            <input type="number" 
                                   class="form-control @error('position_y') is-invalid @enderror" 
                                   id="position_y" 
                                   name="position_y" 
                                   value="{{ old('position_y', $table->position_y ?? $table->y_position ?? 0) }}" 
                                   placeholder="0">
                            @error('position_y')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Current Usage Info (if occupied) -->
                    @if($table->status === 'occupied' && ($table->customer_name || $table->party_size))
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-warning border-bottom pb-2">
                                    <i class="bi bi-people"></i> Current Usage
                                </h5>
                            </div>
                            
                            @if($table->customer_name)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" value="{{ $table->customer_name }}" readonly>
                                </div>
                            @endif
                            
                            @if($table->customer_phone)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Customer Phone</label>
                                    <input type="text" class="form-control" value="{{ $table->customer_phone }}" readonly>
                                </div>
                            @endif
                            
                            @if($table->party_size)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Party Size</label>
                                    <input type="text" class="form-control" value="{{ $table->party_size }} people" readonly>
                                </div>
                            @endif
                            
                            @if($table->occupied_at)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Occupied Since</label>
                                    <input type="text" class="form-control" value="{{ $table->occupied_at->format('M j, Y H:i') }}" readonly>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="d-flex gap-3 justify-content-end">
                        <a href="{{ route('table-management.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <a href="{{ route('table-management.show', $table) }}" class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Update Table
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Status change warning
document.getElementById('status').addEventListener('change', function() {
    const currentStatus = '{{ $table->status }}';
    const newStatus = this.value;
    
    if (currentStatus === 'occupied' && newStatus === 'available') {
        if (!confirm('Are you sure you want to make this table available? This will clear current customer information.')) {
            this.value = currentStatus;
        }
    }
});
</script>
@endpush
