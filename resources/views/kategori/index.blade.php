@extends('layouts.app')

@section('title', 'Data Kategori')

@section('content')
<div class="space-y-6" x-data="{ openCreate:false }">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Kategori</h1>
            <p class="text-gray-600">Kelola kategori layanan</p>
        </div>
        <button type="button" @click="openCreate = true" 
           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Tambah Kategori
        </button>
    </div>

    <!-- Modal Create -->
    <x-modal title="Tambah Kategori" show="openCreate" maxWidth="xl">
        <form method="POST" action="{{ route('kategori.store') }}" class="space-y-6">
            @csrf
            <x-form-input name="nama_kategori" label="Nama Kategori" placeholder="Masukkan nama kategori" required="true" value="{{ old('nama_kategori') }}"></x-form-input>
            <x-form-input name="deskripsi" label="Deskripsi" type="textarea" rows="4" placeholder="Masukkan deskripsi kategori (opsional)" value="{{ old('deskripsi') }}"></x-form-input>
            <x-form-input name="is_active" label="Status" type="checkbox" placeholder="Aktif" value="{{ old('is_active', true) }}"></x-form-input>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" @click="openCreate=false" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md">Batal</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Simpan</button>
            </div>
        </form>
    </x-modal>

    <!-- Filter & Table Wrapper -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header dengan Search dan Filter -->
        <div class="p-6 border-b border-gray-200">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari data..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 justify-between items-center">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"><i class="fas fa-search mr-2"></i>Filter</button>
                    @if(request()->hasAny(['search','status']))
                    <a href="{{ request()->url() }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"><i class="fas fa-times mr-2"></i>Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table using responsive-table component -->
        <x-responsive-table :headers="['No','Nama Kategori','Deskripsi','Jumlah Layanan','Status','Dibuat','Aksi']" minWidth="950px">
            @forelse($kategori as $index => $item)
            <tr class="hover:bg-gray-50" x-data="{ openShow:false, openEdit:false }">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ ($kategori->firstItem() ?? 0) + $index }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->nama_kategori }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ Str::limit($item->deskripsi, 50) ?: '-' }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $item->layanan_count ?? $item->layanan->count() }} layanan</span></td>
                <td class="px-4 py-3 whitespace-nowrap">@if($item->is_active)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>Aktif</span>@else<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i>Tidak Aktif</span>@endif</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $item->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2">
                        <button type="button" @click="openShow = true" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-eye mr-2"></i>Lihat</button>
                        <button type="button" @click="openEdit = true" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-yellow-600 text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors"><i class="fas fa-edit mr-2"></i>Edit</button>
                        <form method="POST" action="{{ route('kategori.destroy', $item) }}" class="inline">@csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"><i class="fas fa-trash mr-2"></i>Hapus</button>
                        </form>
                    </div>

                    <!-- Modal Show -->
                    <x-modal :title="'Detail Kategori: ' . $item->nama_kategori" show="openShow" maxWidth="xl">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-500">Nama Kategori</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->nama_kategori }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Status</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-sm text-gray-500">Deskripsi</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->deskripsi ?: '-' }}</div>
                            </div>
                        </div>
                    </x-modal>

                    <!-- Modal Edit -->
                    <x-modal :title="'Edit Kategori: ' . $item->nama_kategori" show="openEdit" maxWidth="xl" align="center">
                        <form method="POST" action="{{ route('kategori.update', $item) }}" class="space-y-6">@csrf @method('PUT')
                            <x-form-input name="nama_kategori" label="Nama Kategori" required="true" value="{{ $item->nama_kategori }}"></x-form-input>
                            <x-form-input name="deskripsi" label="Deskripsi" type="textarea" rows="4" value="{{ $item->deskripsi }}"></x-form-input>
                            <x-form-input name="is_active" label="Status" type="checkbox" placeholder="Aktif" value="{{ $item->is_active }}"></x-form-input>
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                <button type="button" @click="openEdit=false" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Update</button>
                            </div>
                        </form>
                    </x-modal>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center"><i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i><p class="text-lg font-medium">Tidak ada data</p><p class="text-sm">Belum ada data yang tersedia</p></div></td>
            </tr>
            @endforelse
        </x-responsive-table>

        <!-- Pagination -->
        @if($kategori->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-600">Menampilkan {{ $kategori->firstItem() }}-{{ $kategori->lastItem() }} dari {{ $kategori->total() }} data</div>
                <div class="overflow-x-auto">{{ $kategori->links() }}</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
