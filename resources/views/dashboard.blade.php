@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    @php
        use App\Models\Layanan;
        use App\Models\Kategori;
        $totalLayanan = Layanan::count();
        $totalKategori = Kategori::count();
        $latestLayanan = Layanan::with('kategori')->latest()->take(8)->get();
        $totalTarifHalaman = $latestLayanan->sum('tarif');
    @endphp

    <!-- Header / Welcome -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Selamat Datang, {{ $user->name }}!</h2>
            <p class="text-gray-600">Ringkasan layanan dan aktivitas terbaru</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Total Layanan</div>
                    <div class="text-2xl font-extrabold text-gray-900">{{ number_format($totalLayanan) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fas fa-stethoscope"></i></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Total Kategori</div>
                    <div class="text-2xl font-extrabold text-gray-900">{{ number_format($totalKategori) }}</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-tags"></i></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Aksi Cepat</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('kategori.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"><i class="fas fa-tags mr-2"></i>Kelola Kategori</a>
                    <a href="{{ route('layanan.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"><i class="fas fa-stethoscope mr-2"></i>Kelola Layanan</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Layanan -->
    <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Layanan Terbaru</h3>
                <a href="{{ route('layanan.index') }}" class="text-sm text-green-700 hover:text-green-800">Lihat semua</a>
            </div>
            <div class="overflow-x-auto w-full max-w-full">
                <div style="min-width: 980px; width: max-content;">
                    <table class="divide-y divide-gray-200 whitespace-nowrap w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Layanan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margin (%)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($latestLayanan as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $item->nama_layanan ?? ('Layanan #' . $item->id) }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ optional($item->kategori)->nama_kategori ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">Rp {{ number_format($item->unit_cost ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ number_format($item->margin ?? 0, 2) }}%</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">Rp {{ number_format($item->tarif ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center"><i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i><p class="text-lg font-medium">Belum ada layanan</p><p class="text-sm">Data layanan terbaru akan tampil di sini</p></div>
                                </td>
                            </tr>
                            @endforelse
                            @if($latestLayanan->count())
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 text-sm font-semibold text-gray-900" colspan="3">Total Tarif (terbaru)</td>
                                <td class="px-4 py-2 text-sm font-extrabold text-gray-900">Rp {{ number_format($totalTarifHalaman, 0, ',', '.') }}</td>
                                <td class="px-4 py-2"></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
