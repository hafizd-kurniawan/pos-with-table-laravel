@extends('layouts.app')

@section('title', $tableCategory->name . ' - Detail Kategori')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="fs-2 me-3">{{ $tableCategory->icon }}</span>
                        <div>
                            <h1 class="h3 mb-1">{{ $tableCategory->name }}</h1>
                            <p class="text-muted mb-0">{{ $tableCategory->description ?: 'Tidak ada deskripsi' }}</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('table-categories.edit', $tableCategory) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <a href="{{ route('table-categories.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Info -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 mb-2 text-primary">{{ $stats['total_tables'] }}</div>
                    <h6 class="card-title text-muted">Total Meja</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 mb-2 text-success">{{ $stats['available_tables'] }}</div>
                    <h6 class="card-title text-muted">Tersedia</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 mb-2 text-warning">{{ $stats['occupied_tables'] }}</div>
                    <h6 class="card-title text-muted">Terisi</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h2 mb-2 text-info">{{ $stats['reserved_tables'] }}</div>
                    <h6 class="card-title text-muted">Reservasi</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Detail Kategori</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4"><strong>Nama:</strong></div>
                        <div class="col-8">{{ $tableCategory->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Ikon:</strong></div>
                        <div class="col-8">
                            <span class="fs-4">{{ $tableCategory->icon }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Warna:</strong></div>
                        <div class="col-8">
                            <div class="d-flex align-items-center">
                                <div class="rounded me-2" style="width: 24px; height: 24px; background-color: {{ $tableCategory->color }}"></div>
                                <span>{{ $tableCategory->color }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Status:</strong></div>
                        <div class="col-8">
                            <span class="badge {{ $tableCategory->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $tableCategory->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Urutan:</strong></div>
                        <div class="col-8">{{ $tableCategory->sort_order }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Dibuat:</strong></div>
                        <div class="col-8">{{ $tableCategory->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-4"><strong>Diperbarui:</strong></div>
                        <div class="col-8">{{ $tableCategory->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Statistik Penggunaan</h5>
                </div>
                <div class="card-body">
                    @if($stats['total_tables'] > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Meja Tersedia</small>
                            <small>{{ number_format(($stats['available_tables'] / $stats['total_tables']) * 100, 1) }}%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" 
                                 style="width: {{ ($stats['available_tables'] / $stats['total_tables']) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Meja Terisi</small>
                            <small>{{ number_format(($stats['occupied_tables'] / $stats['total_tables']) * 100, 1) }}%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" 
                                 style="width: {{ ($stats['occupied_tables'] / $stats['total_tables']) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Meja Reservasi</small>
                            <small>{{ number_format(($stats['reserved_tables'] / $stats['total_tables']) * 100, 1) }}%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" 
                                 style="width: {{ ($stats['reserved_tables'] / $stats['total_tables']) * 100 }}%"></div>
                        </div>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-bar fa-2x mb-3"></i>
                        <p>Belum ada meja dalam kategori ini</p>
                        <a href="{{ route('table-management.create') }}?category={{ $tableCategory->id }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Meja Pertama
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tables List -->
    @if($tableCategory->tables->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Meja ({{ $tableCategory->tables->count() }})</h5>
                    <a href="{{ route('table-management.create') }}?category={{ $tableCategory->id }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Meja Baru
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($tableCategory->tables->take(20) as $table)
                        <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">{{ $table->name }}</h6>
                                        <span class="badge {{ $table->status == 'available' ? 'bg-success' : ($table->status == 'occupied' ? 'bg-warning' : ($table->status == 'reserved' ? 'bg-info' : 'bg-danger')) }}">
                                            {{ ucfirst($table->status) }}
                                        </span>
                                    </div>
                                    @if($table->capacity)
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>{{ $table->capacity }} orang
                                    </small>
                                    @endif
                                    @if($table->location)
                                    <br><small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ $table->location }}
                                    </small>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="{{ route('table-management.show', $table) }}" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('table-management.edit', $table) }}" 
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if($tableCategory->tables->count() > 20)
                    <div class="text-center mt-3">
                        <a href="{{ route('table-management.index') }}?category={{ $tableCategory->id }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Lihat Semua Meja ({{ $tableCategory->tables->count() }})
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection
