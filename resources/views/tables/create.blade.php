@extends('layouts.app')

@section('title', 'Create New Table')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">
                    <i class="bi bi-plus-circle"></i> Create New Table
                </h4>
            </div>
            
            <div class="card-body">
                <form action="{{ route('table-management.store') }}" method="POST">
                    @csrf
                    
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
                                   value="{{ old('name') }}" 
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
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->icon }} {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="bi bi-card-text"></i> Details
                            </h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" 
                                   name="capacity" 
                                   value="{{ old('capacity') }}" 
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
                                   value="{{ old('location') }}" 
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
                                      placeholder="Additional information about this table (facilities, view, etc.)">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Position (Optional) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="bi bi-crosshair"></i> Position (for Layout)
                            </h5>
                            <p class="text-muted small">Optional: Set position coordinates for table layout in mobile app</p>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="position_x" class="form-label">X Position (pixels)</label>
                            <input type="number" 
                                   class="form-control @error('position_x') is-invalid @enderror" 
                                   id="position_x" 
                                   name="position_x" 
                                   value="{{ old('position_x', 0) }}" 
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
                                   value="{{ old('position_y', 0) }}" 
                                   placeholder="0">
                            @error('position_y')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-3 justify-content-end">
                        <a href="{{ route('table-management.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Table
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Category Preview -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette"></i> Category Preview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($categories as $key => $label)
                        <div class="col-md-6 col-lg-4 mb-2">
                            <span class="badge category-badge 
                                @switch($key)
                                    @case('vip') bg-warning text-dark @break
                                    @case('private') bg-danger @break
                                    @case('outdoor') bg-info @break
                                    @case('bar') bg-secondary @break
                                    @case('family') bg-primary @break
                                    @case('couple') bg-success @break
                                    @case('group') bg-dark @break
                                    @default bg-success
                                @endswitch
                            ">
                                {{ $label }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-generate table name based on category
document.getElementById('category').addEventListener('change', function() {
    const nameField = document.getElementById('name');
    if (!nameField.value) {
        const categoryMap = {
            'regular': 'REG',
            'vip': 'VIP',
            'private': 'PVT',
            'outdoor': 'OUT',
            'bar': 'BAR',
            'family': 'FAM',
            'couple': 'CPL',
            'group': 'GRP'
        };
        
        const prefix = categoryMap[this.value];
        if (prefix) {
            nameField.value = prefix + '-01';
            nameField.focus();
            nameField.select();
        }
    }
});
</script>
@endpush
