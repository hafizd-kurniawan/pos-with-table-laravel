@extends('layouts.app')

@section('title', 'Kategori Meja')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Kategori Meja</h1>
                    <p class="text-muted">Kelola kategori meja restaurant</p>
                </div>
                <a href="{{ route('table-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Kategori
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('table-categories.index') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Pencarian</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       placeholder="Cari berdasarkan nama atau deskripsi...">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="{{ route('table-categories.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="row">
        @forelse($categories as $category)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="fs-4 me-2">{{ $category->icon }}</span>
                        <h5 class="mb-0">{{ $category->name }}</h5>
                    </div>
                    <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $category->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ $category->description ?: 'Tidak ada deskripsi' }}</p>
                    
                    <div class="row text-center mb-3">
                        <div class="col">
                            <div class="border rounded p-2">
                                <div class="h4 mb-0 text-primary">{{ $category->tables_count }}</div>
                                <small class="text-muted">Total Meja</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Warna:</small>
                        <div class="d-flex align-items-center mt-1">
                            <div class="rounded" 
                                 style="width: 20px; height: 20px; background-color: {{ $category->color }}"></div>
                            <span class="ms-2 small text-muted">{{ $category->color }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Urutan: {{ $category->sort_order }}</small>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('table-categories.show', $category) }}" 
                           class="btn btn-outline-info btn-sm">
                            <i class="fas fa-eye me-1"></i>Detail
                        </a>
                        <a href="{{ route('table-categories.edit', $category) }}" 
                           class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <button type="button" 
                                class="btn btn-outline-danger btn-sm"
                                onclick="confirmDelete('{{ $category->name }}', '{{ route('table-categories.destroy', $category) }}')">
                            <i class="fas fa-trash me-1"></i>Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-table fa-3x text-muted"></i>
                    </div>
                    <h4 class="text-muted">Belum ada kategori</h4>
                    <p class="text-muted">Silakan tambahkan kategori meja pertama Anda</p>
                    <a href="{{ route('table-categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tambah Kategori Pertama
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($categories->hasPages())
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-center">
                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus kategori <strong id="categoryName"></strong>?</p>
                <p class="text-muted small">Aksi ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(name, url) {
    document.getElementById('categoryName').textContent = name;
    document.getElementById('deleteForm').action = url;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endpush

@endsection
