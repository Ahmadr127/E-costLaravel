@extends('layouts.app')

@section('title', 'Upload Excel Layanan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload mr-2"></i>
                        Upload Excel Layanan
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('layanan.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <form action="{{ route('layanan.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="excel_file" class="form-label">
                                        <i class="fas fa-file-excel mr-1"></i>
                                        Pilih File Excel
                                    </label>
                                    <input type="file" 
                                           class="form-control @error('excel_file') is-invalid @enderror" 
                                           id="excel_file" 
                                           name="excel_file" 
                                           accept=".xlsx,.xls"
                                           required>
                                    @error('excel_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Format file yang didukung: .xlsx, .xls (Maksimal 10MB)
                                    </small>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload mr-1"></i>
                                        Upload & Import Data
                                    </button>
                                    <a href="{{ route('layanan.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times mr-1"></i>
                                        Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Petunjuk Upload
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h6>Format Excel yang Diperlukan:</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success mr-1"></i> Kolom <strong>Kode</strong></li>
                                        <li><i class="fas fa-check text-success mr-1"></i> Kolom <strong>Jenis Pemeriksaan</strong></li>
                                        <li><i class="fas fa-check text-success mr-1"></i> Kolom <strong>Unit Cost</strong></li>
                                    </ul>
                                    
                                    <h6 class="mt-3">Ketentuan:</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-exclamation-triangle text-warning mr-1"></i> Baris dengan data tidak lengkap akan dilewati</li>
                                        <li><i class="fas fa-exclamation-triangle text-warning mr-1"></i> Kode yang sudah ada akan dilewati</li>
                                        <li><i class="fas fa-info text-info mr-1"></i> Data akan otomatis aktif setelah diimpor</li>
                                    </ul>

                                    <div class="mt-3">
                                        <a href="{{ asset('resources/views/sampleexcel/simulasi unit cost.xlsx') }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           download>
                                            <i class="fas fa-download mr-1"></i>
                                            Download Sample Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // File input validation
    $('#excel_file').on('change', function() {
        const file = this.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        
        if (file) {
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar. Maksimal 10MB.');
                this.value = '';
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan file Excel (.xlsx atau .xls).');
                this.value = '';
                return;
            }
        }
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@endpush
