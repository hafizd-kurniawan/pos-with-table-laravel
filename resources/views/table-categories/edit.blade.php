@extends('layouts.app')

@section('title', 'Edit Kategori: ' . $tableCategory->name)

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Edit Kategori: {{ $tableCategory->name }}</h1>
                    <p class="text-muted">Perbarui informasi kategori meja</p>
                </div>
                <a href="{{ route('table-categories.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Form Edit Kategori Meja</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('table-categories.update', $tableCategory) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $tableCategory->name) }}" 
                                       placeholder="Contoh: VIP, Regular, dll"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Ikon (Emoji)</label>
                                <input type="text" 
                                       class="form-control @error('icon') is-invalid @enderror" 
                                       id="icon" 
                                       name="icon" 
                                       value="{{ old('icon', $tableCategory->icon) }}" 
                                       placeholder="Contoh: 🪑, 👑, 🚪, dll"
                                       maxlength="2">
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Gunakan emoji untuk mempermudah identifikasi</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Warna</label>
                                <div class="input-group">
                                    <input type="color" 
                                           class="form-control form-control-color @error('color') is-invalid @enderror" 
                                           id="color" 
                                           name="color" 
                                           value="{{ old('color', $tableCategory->color) }}" 
                                           title="Pilih warna">
                                    <input type="text" 
                                           class="form-control" 
                                           id="colorHex" 
                                           value="{{ old('color', $tableCategory->color) }}" 
                                           readonly>
                                </div>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Urutan</label>
                                <input type="number" 
                                       class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" 
                                       name="sort_order" 
                                       value="{{ old('sort_order', $tableCategory->sort_order) }}" 
                                       min="0"
                                       placeholder="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Urutan tampilan kategori (0 = paling awal)</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Deskripsi singkat tentang kategori meja ini...">{{ old('description', $tableCategory->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $tableCategory->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Status Aktif
                                </label>
                            </div>
                            <div class="form-text">Centang untuk mengaktifkan kategori ini</div>
                        </div>

                        <hr>

                        <!-- Preview -->
                        <div class="mb-4">
                            <label class="form-label">Preview</label>
                            <div class="border rounded p-3" id="preview">
                                <div class="d-flex align-items-center">
                                    <span class="fs-4 me-2" id="previewIcon">{{ $tableCategory->icon }}</span>
                                    <span class="fw-bold" id="previewName">{{ $tableCategory->name }}</span>
                                    <span class="badge ms-auto" id="previewBadge" style="background-color: {{ $tableCategory->color }}">
                                        {{ $tableCategory->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted" id="previewDescription">{{ $tableCategory->description ?: 'Tidak ada deskripsi' }}</small>
                                </div>
                                <div class="mt-2">
                                    <div class="rounded d-inline-block" id="previewColor" style="width: 20px; height: 20px; background-color: {{ $tableCategory->color }}"></div>
                                    <small class="ms-2 text-muted" id="previewColorText">{{ $tableCategory->color }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Current Status Info -->
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Info:</strong> Kategori ini saat ini memiliki <strong>{{ $tableCategory->tables()->count() }} meja</strong> yang terkait.
                                        @if($tableCategory->tables()->count() > 0)
                                        <br><small class="text-muted">Perubahan akan mempengaruhi semua meja dalam kategori ini.</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('table-categories.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Perbarui Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live preview update
    const nameInput = document.getElementById('name');
    const iconInput = document.getElementById('icon');
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('colorHex');
    const descriptionInput = document.getElementById('description');
    const isActiveInput = document.getElementById('is_active');

    const previewName = document.getElementById('previewName');
    const previewIcon = document.getElementById('previewIcon');
    const previewColor = document.getElementById('previewColor');
    const previewColorText = document.getElementById('previewColorText');
    const previewDescription = document.getElementById('previewDescription');
    const previewBadge = document.getElementById('previewBadge');

    function updatePreview() {
        previewName.textContent = nameInput.value || 'Nama Kategori';
        previewIcon.textContent = iconInput.value || '🪑';
        previewDescription.textContent = descriptionInput.value || 'Deskripsi akan tampil di sini';
        
        const color = colorInput.value;
        previewColor.style.backgroundColor = color;
        previewColorText.textContent = color;
        colorHex.value = color;
        
        if (isActiveInput.checked) {
            previewBadge.textContent = 'Aktif';
            previewBadge.style.backgroundColor = color;
        } else {
            previewBadge.textContent = 'Tidak Aktif';
            previewBadge.style.backgroundColor = '#6c757d';
        }
    }

    // Event listeners
    nameInput.addEventListener('input', updatePreview);
    iconInput.addEventListener('input', updatePreview);
    colorInput.addEventListener('input', updatePreview);
    descriptionInput.addEventListener('input', updatePreview);
    isActiveInput.addEventListener('change', updatePreview);

    // Initial preview update
    updatePreview();
});
</script>
@endpush

@endsection
