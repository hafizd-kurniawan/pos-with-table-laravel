@extends('layouts.app')

@section('title', 'Table Details - ' . $table->name)

@section('content')
<div class="row">
    <!-- Table Information -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="bi bi-table"></i> {{ $table->name }}
                </h4>
                <div>
                    <a href="{{ route('table-management.edit', $table) }}" class="btn btn-warning btn-sm me-2">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('table-management.destroy', $table) }}" 
                          method="POST" 
                          class="d-inline"
                          onsubmit="return confirm('Are you sure you want to delete this table?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Status and Category -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Current Status</h6>
                        <span class="badge fs-6 status-badge 
                            @switch($table->status)
                                @case('available') bg-success @break
                                @case('occupied') bg-warning text-dark @break
                                @case('reserved') bg-info @break
                                @case('maintenance') bg-danger @break
                                @default bg-secondary
                            @endswitch
                        ">
                            <i class="bi 
                                @switch($table->status)
                                    @case('available') bi-check-circle @break
                                    @case('occupied') bi-people @break
                                    @case('reserved') bi-clock @break
                                    @case('maintenance') bi-tools @break
                                    @default bi-question-circle
                                @endswitch
                            "></i>
                            {{ ucfirst($table->status) }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Category</h6>
                        <span class="badge fs-6 category-badge 
                            @switch($table->category)
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
                            @php
                                $categories = [
                                    'regular' => '🪑 Regular Table',
                                    'vip' => '👑 VIP Table', 
                                    'private' => '🚪 Private Room',
                                    'outdoor' => '🌿 Outdoor Table',
                                    'bar' => '🍻 Bar Counter',
                                    'family' => '👨‍👩‍👧‍👦 Family Table',
                                    'couple' => '💕 Couple Table',
                                    'group' => '👥 Group Table',
                                ];
                            @endphp
                            {{ $categories[$table->category] ?? $table->category }}
                        </span>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <h6 class="text-muted">Capacity</h6>
                        <div class="fs-4 fw-bold text-primary">
                            <i class="bi bi-person"></i> {{ $table->capacity }}
                        </div>
                        <small class="text-muted">Maximum seats</small>
                    </div>
                    
                    @if($table->party_size)
                    <div class="col-md-3">
                        <h6 class="text-muted">Current Party</h6>
                        <div class="fs-4 fw-bold text-warning">
                            <i class="bi bi-people"></i> {{ $table->party_size }}
                        </div>
                        <small class="text-muted">Current guests</small>
                    </div>
                    @endif
                    
                    @if($table->position_x || $table->position_y)
                    <div class="col-md-3">
                        <h6 class="text-muted">Position</h6>
                        <div class="fs-6 fw-bold text-info">
                            <i class="bi bi-crosshair"></i> 
                            ({{ $table->position_x ?? 0 }}, {{ $table->position_y ?? 0 }})
                        </div>
                        <small class="text-muted">Layout coordinates</small>
                    </div>
                    @endif
                    
                    <div class="col-md-3">
                        <h6 class="text-muted">Created</h6>
                        <div class="fs-6 fw-bold text-secondary">
                            <i class="bi bi-calendar"></i> 
                            {{ $table->created_at->format('M j, Y') }}
                        </div>
                        <small class="text-muted">{{ $table->created_at->diffForHumans() }}</small>
                    </div>
                </div>

                <!-- Location & Description -->
                @if($table->location || $table->description)
                <div class="row mb-4">
                    @if($table->location)
                    <div class="col-md-6">
                        <h6 class="text-muted">Location</h6>
                        <p class="mb-0">
                            <i class="bi bi-geo-alt text-primary"></i> 
                            {{ $table->location }}
                        </p>
                    </div>
                    @endif
                    
                    @if($table->description)
                    <div class="col-{{ $table->location ? 'md-6' : '12' }}">
                        <h6 class="text-muted">Description</h6>
                        <p class="mb-0">{{ $table->description }}</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Current Usage (if occupied) -->
                @if($table->status === 'occupied')
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="bi bi-people"></i> Current Usage
                    </h6>
                    <div class="row">
                        @if($table->customer_name)
                        <div class="col-md-4">
                            <strong>Customer:</strong><br>
                            {{ $table->customer_name }}
                        </div>
                        @endif
                        
                        @if($table->customer_phone)
                        <div class="col-md-4">
                            <strong>Phone:</strong><br>
                            <a href="tel:{{ $table->customer_phone }}">{{ $table->customer_phone }}</a>
                        </div>
                        @endif
                        
                        @if($table->occupied_at)
                        <div class="col-md-4">
                            <strong>Occupied Since:</strong><br>
                            {{ $table->occupied_at->format('M j, Y H:i') }}
                            <br><small class="text-muted">({{ $table->occupied_at->diffForHumans() }})</small>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- QR Code Info -->
                @if($table->qr_code)
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="bi bi-qr-code"></i> QR Code Information
                    </h6>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <strong>Order URL:</strong><br>
                            <code>{{ $table->qr_code }}</code>
                            <br>
                            <small class="text-muted">Customers can scan QR code to access this URL for ordering</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('table.print-qr', $table->id) }}" 
                               class="btn btn-primary btn-sm" 
                               target="_blank">
                                <i class="bi bi-printer"></i> Print QR
                            </a>
                            <a href="{{ route('table.download-qr', $table->id) }}" 
                               class="btn btn-success btn-sm">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions & Stats -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i> Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('table-management.edit', $table) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit Table
                    </a>
                    
                    @if($table->status === 'available')
                    <button class="btn btn-info" onclick="markAsOccupied()">
                        <i class="bi bi-people"></i> Mark as Occupied
                    </button>
                    @elseif($table->status === 'occupied')
                    <button class="btn btn-success" onclick="markAsAvailable()">
                        <i class="bi bi-check-circle"></i> Mark as Available
                    </button>
                    @endif
                    
                    <button class="btn btn-secondary" onclick="markAsMaintenance()">
                        <i class="bi bi-tools"></i> 
                        {{ $table->status === 'maintenance' ? 'Remove from' : 'Set' }} Maintenance
                    </button>
                    
                    @if(!$table->qr_code)
                    <button class="btn btn-primary" onclick="generateQR()">
                        <i class="bi bi-qr-code"></i> Generate QR Code
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        @if($table->reservations->count() > 0)
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar"></i> Recent Reservations
                </h5>
            </div>
            <div class="card-body">
                @foreach($table->reservations->take(5) as $reservation)
                <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                    <div>
                        <strong>{{ $reservation->customer_name }}</strong>
                        <br>
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> 
                            {{ $reservation->reservation_date->format('M j, Y') }}
                            <i class="bi bi-clock ms-2"></i>
                            {{ $reservation->reservation_time }}
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="bi bi-people"></i> 
                            {{ $reservation->party_size }} people
                        </small>
                    </div>
                    <span class="badge 
                        @switch($reservation->status)
                            @case('confirmed') bg-success @break
                            @case('pending') bg-warning text-dark @break
                            @case('checked_in') bg-info @break
                            @case('completed') bg-primary @break
                            @case('cancelled') bg-danger @break
                            @default bg-secondary
                        @endswitch
                    ">
                        {{ ucfirst($reservation->status) }}
                    </span>
                </div>
                @endforeach
                
                @if($table->reservations->count() > 5)
                <div class="text-center">
                    <small class="text-muted">
                        And {{ $table->reservations->count() - 5 }} more reservations...
                    </small>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Back to List -->
<div class="text-center mt-4">
    <a href="{{ route('table-management.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back to Table List
    </a>
</div>
@endsection

@push('scripts')
<script>
// Quick status updates
function markAsOccupied() {
    // This would open a modal or form for customer details
    alert('Feature coming soon: Customer details form');
}

function markAsAvailable() {
    if (confirm('Mark this table as available? This will clear current customer information.')) {
        updateTableStatus('available');
    }
}

function markAsMaintenance() {
    const currentStatus = '{{ $table->status }}';
    const newStatus = currentStatus === 'maintenance' ? 'available' : 'maintenance';
    const action = currentStatus === 'maintenance' ? 'remove from' : 'set to';
    
    if (confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} maintenance mode?`)) {
        updateTableStatus(newStatus);
    }
}

function generateQR() {
    if (confirm('Generate QR code for this table?')) {
        // Call API to generate QR code
        fetch(`/api/tables/{{ $table->id }}/generate-qr`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                location.reload();
            }
        });
    }
}

function updateTableStatus(status) {
    fetch(`/api/tables/{{ $table->id }}/status`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ status: status })
    }).then(response => {
        if (response.ok) {
            location.reload();
        }
    });
}
</script>
@endpush
