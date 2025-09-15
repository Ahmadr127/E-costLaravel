@extends('layouts.app')

@section('title', 'Upload Excel Layanan')

@section('content')
<div class="space-y-6" x-data="{ showClearModal: false }">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Upload Excel Layanan</h1>
            <p class="text-gray-600">Import data layanan dari file Excel</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('layanan.index') }}" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>
                <div>
                    <div class="font-medium">Terjadi kesalahan:</div>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upload Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-upload mr-2"></i>
                        Upload File Excel
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('layanan.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-file-excel mr-1"></i>
                                Pilih File Excel
                            </label>
                            <input type="file" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('excel_file') border-red-300 @enderror" 
                                   id="excel_file" 
                                   name="excel_file" 
                                   accept=".xlsx,.xls"
                                   required>
                            @error('excel_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">
                                Format file yang didukung: .xlsx, .xls (Maksimal 10MB)
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-upload mr-2"></i>
                                Upload & Import Data
                            </button>
                            <a href="{{ route('layanan.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Batal
                            </a>
                            @if(auth()->user()->hasPermission('manage_layanan'))
                            <button type="button" 
                                    @click="showClearModal = true" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Hapus Semua Data
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instructions Panel -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-info-circle mr-2"></i>
                        Petunjuk Upload
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Format Excel yang Diperlukan:</h4>
                        <ul class="space-y-1">
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Kolom <strong>Kode</strong>
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Kolom <strong>Jenis Pemeriksaan</strong>
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Kolom <strong>Tarif Master</strong> (II / IGD / POLI) - opsional
                            </li>
                            <li class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Kolom <strong>Unit Cost</strong>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Ketentuan:</h4>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                                Baris dengan data tidak lengkap akan dilewati
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-0.5"></i>
                                Kode yang sudah ada akan dilewati
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-info text-blue-500 mr-2 mt-0.5"></i>
                                Data akan otomatis aktif setelah diimpor
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Clear All -->
    <div x-show="showClearModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showClearModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Konfirmasi Hapus Semua Data</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Apakah Anda yakin ingin menghapus <strong>SEMUA DATA LAYANAN</strong>? 
                        Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data layanan yang ada.
                    </p>
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-700 font-medium">
                            <i class="fas fa-warning mr-1"></i>
                            PERINGATAN: Tindakan ini akan menghapus semua data layanan secara permanen!
                        </p>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <form method="POST" action="{{ route('layanan.clear-all') }}" class="inline">
                        @csrf
                        <input type="hidden" name="confirmation" value="DELETE_ALL_LAYANAN">
                        <button type="button" 
                                @click="showClearModal = false" 
                                class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 mr-2">
                            Batal
                        </button>
                        <button type="submit" 
                                @click="if(!confirm('Apakah Anda benar-benar yakin ingin menghapus SEMUA DATA LAYANAN? Tindakan ini tidak dapat dibatalkan!')) $event.preventDefault()"
                                class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-trash-alt mr-1"></i>
                            Ya, Hapus Semua Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File input validation
    const fileInput = document.getElementById('excel_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
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
    }

    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert, [class*="bg-green-100"], [class*="bg-red-100"]');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});
</script>
@endpush
