@extends('layouts.app')

@section('title', 'Simulasi Unit Cost per Qty')

@push('scripts')
<script>
    window.SIMULATION_SEARCH_URL = "{{ route('simulation.search') }}";
    window.SIMULATION_LIST_URL = "{{ route('simulation.list') }}";
    window.SIMULATION_QTY_LIST_URL = "{{ route('simulation.qty.list') }}";
    window.SIMULATION_STORE_URL = "{{ route('simulation.qty.store') }}";
    window.SIMULATION_CATEGORIES_URL = "{{ route('simulation.categories') }}";
    window.SIMULATION_QTY_PRESETS_URL = "{{ route('simulation.qty.presets') }}";
    window.SIMULATION_SHOW_URL = function(id){ return `{{ url('simulation-qty') }}/${id}` };
    window.SIMULATION_UPDATE_URL = function(id){ return `{{ url('simulation-qty') }}/${id}` };
    window.SIMULATION_DELETE_URL = function(id){ return `{{ url('simulation-qty') }}/${id}` };
    window.CSRF_TOKEN = "{{ csrf_token() }}";
</script>
<script src="{{ asset('js/simulasi_qty.js') }}?v={{ filemtime(public_path('js/simulasi_qty.js')) }}"></script>
@endpush

@section('content')
<div x-data="simulationQtyApp()" class="space-y-4">
    <!-- Main 2-column layout: Left = Simulation, Right = Saved Simulations -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4">
        <!-- Left: Simulation (span 7) -->
        <div class="lg:col-span-7">
            <!-- Header, Search & Simulation Results in ONE CARD -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header with Action Buttons -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Simulasi Unit Cost (Qty)</h1>
                            <p class="text-sm text-gray-600">Hitung total berdasarkan qty dengan diskon bertingkat</p>
                            <!-- Active Simulation Badge -->
                            <div x-show="activeSimulationId" class="mt-1.5">
                                <span class="inline-flex items-center text-xs font-medium text-blue-700">
                                    <span x-text="saveName" class="font-semibold"></span>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-1">
                                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-green-600 text-white hover:bg-green-700 focus:ring-green-500" @click="promptSaveSimulation()">
                                    <i class="fas fa-save mr-1"></i> Simpan
                                </button>
                                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" @click="deleteActiveSimulation()" x-bind:disabled="!activeSimulationId">
                                    <i class="fas fa-trash mr-1"></i> Hapus
                                </button>
                            </div>
                            <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-3 py-1.5 text-xs bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed" @click="exportResults()" x-bind:disabled="simulationResults.length === 0">
                                <i class="fas fa-download mr-1"></i>
                                Export
                            </button>
                            <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-3 py-1.5 text-xs bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500" @click="resetSimulation()">
                                <i class="fas fa-refresh mr-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                    
                    <!-- Search Box -->
                    <div @layanan-selected.window="addLayananToSimulation($event.detail)">
                        <x-layanan-search 
                            label="Cari Layanan"
                            placeholder="Masukkan kode atau jenis pemeriksaan..."
                        />
                    </div>
                </div>

                <!-- Simulation Results Table -->

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                        <th class="px-3 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-24">Kode</th>
                        <th class="px-3 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Jenis Pemeriksaan</th>
                        <th class="px-3 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider w-32">Kategori</th>
                        <th class="px-3 py-2 text-center text-sm font-medium text-gray-500 uppercase tracking-wider w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(result, index) in simulationResults" :key="result.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 w-12" x-text="index + 1"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 w-24" x-text="result.kode"></td>
                            <td class="px-3 py-2 text-sm text-gray-900 max-w-xs truncate">
                                <div class="flex flex-col">
                                    <span x-text="result.jenis_pemeriksaan"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 w-32">
                                <span x-text="result.kategori_nama || '-'"></span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-center w-20">
                                <button 
                                    type="button" 
                                    @click="removeFromSimulation(index)" 
                                    class="text-red-600 hover:text-red-800 transition-colors"
                                    title="Hapus layanan"
                                >
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="bg-red-50">
                        <!-- Bagian 1: Qty (rata kiri), letakkan di bawah kolom No+Kode -->
                        <td class="px-3 py-2 text-sm text-gray-700 font-medium" colspan="2">
                            <div class="flex items-center gap-2">
                                <span>Qty:</span>
                                <input type="number" min="0" step="1" class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" :value="simulationQuantity" @input="onSimulationQtyChange($event.target.value)">
                            </div>
                        </td>
                        <!-- Bagian 2: Info per pasien, margin, grand total (semua rata kiri) di bawah kolom Jenis Pemeriksaan -->
                        <td class="px-3 py-2 text-sm text-gray-700 font-medium">
    <div class="space-y-1">
        <div class="text-base text-red-700 font-semibold" 
             x-text="'Per pasien: Rp ' + (typeof perPatientPrice === 'undefined' ? '0' : formatNumber(perPatientPrice))">
        </div>
        <div class="text-base text-red-700 font-semibold">
            Grand Total:
        </div>
        <div class="text-lg text-red-700 font-semibold" 
             x-text="'Rp ' + formatNumber(grandTotal)">
        </div>
    </div>
</td>

                        <!-- Empty cells for category and action columns -->
                        <td class="px-3 py-2 text-sm text-gray-700 font-medium" colspan="2">
                            
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
            </div>
        </div>

        <!-- Right: Saved simulations -->
        <aside class="lg:col-span-3 space-y-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Simulasi Tersimpan</h3>
                    <button type="button" class="text-xs text-blue-600 hover:text-blue-700" @click="refreshSavedSimulations()">
                        <i class="fas fa-rotate mr-1"></i> Muat Ulang
                    </button>
                </div>
                <div class="p-3 border-b border-gray-100">
                    <div class="relative">
                        <input type="text" x-model="savedFilter" placeholder="Cari nama..." class="w-full px-3 py-2 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <i class="fas fa-search absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                </div>
                <div class="max-h-[50vh] overflow-y-auto divide-y divide-gray-100">
                    <template x-for="item in filteredSavedSimulations" :key="item.id">
                        <div class="px-3 py-2 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="item.name"></p>
                                        <span class="text-[10px] text-gray-500" x-text="formatDateAgo(item.updated_at)"></span>
                                    </div>
                                    <p class="text-[11px] text-gray-500">
                                        <span x-text="item.items_count"></span> item · Total: <span class="font-medium" x-text="'Rp ' + formatNumber(item.grand_total)"></span>
                                        <template x-if="item.category_summary">
                                            <span> · Kategori: <span class="font-medium" x-text="item.category_summary"></span></span>
                                        </template>
                                    </p>
                                </div>
                                <div class="flex-shrink-0 flex items-center gap-1">
                                    <button class="px-2 py-1 text-[11px] bg-blue-600 text-white rounded hover:bg-blue-700" @click="loadSimulation(item.id)" title="Muat dan edit simulasi">Muat</button>
                                    <button class="px-2 py-1 text-[11px] bg-red-600 text-white rounded hover:bg-red-700" @click="deleteSaved(item.id)" title="Hapus simulasi">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredSavedSimulations.length === 0" class="p-4 text-center text-xs text-gray-500">Tidak ada data</div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Breakdown per Qty (berdasarkan preset tiers) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-show="false">
        <div class="p-3 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleBreakdownVisibility()" class="inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors duration-200">
                        <i class="fas text-xs" :class="showBreakdown ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                    </button>
                    <h3 class="text-sm font-semibold text-gray-900">Rincian Perhitungan per Qty (berdasarkan preset)</h3>
                </div>
                <!-- PRESET-BASED QTY SIMULATION: Breakdown is now for entire simulation -->
            </div>
            <div x-show="showBreakdown" class="flex items-center gap-2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95">
                <template x-if="defaultTierUsed">
                    <span class="text-[11px] px-2 py-1 rounded bg-amber-100 text-amber-800 border border-amber-200">
                        Default margin digunakan
                    </span>
                </template>
                <div class="flex items-center text-xs text-gray-700">
                    <span class="mr-1">Default margin:</span>
                    <input type="number" min="0" max="100" step="0.01" class="w-20 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" x-model.number="defaultMarginPercent" @input="onDefaultMarginChange()">
                    <span class="ml-1">%</span>
                </div>
            </div>
        </div>
        <div x-show="showBreakdown" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Range Qty</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margin (%)</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif Master</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga/Unit</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="row in breakdownRows" :key="row.range">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <span x-text="row.range"></span>
                                <template x-if="row.isDefault">
                                    <span class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">default</span>
                                </template>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="row.qty"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="(row.marginPct).toFixed(2) + '%'">%</td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(row.unitCost)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(row.unitPrice)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-gray-900" x-text="'Rp ' + formatNumber(row.subtotal)"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-500" colspan="4"></td>
                        <td class="px-3 py-2 text-right text-xs font-medium text-gray-900">Total</td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-red-600" x-text="'Rp ' + formatNumber(breakdownRows.totalSubtotal || 0)"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tier Preset Modal -->
    <div x-cloak x-show="showTierModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-40" @click="closeTierModal()"></div>
        <div class="relative bg-white w-full max-w-xl rounded-lg shadow-xl border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900" x-text="tierForm.id ? 'Edit Preset Tier' : 'Preset Tier Baru'"></h3>
                <button class="text-gray-400 hover:text-gray-600" @click="closeTierModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Nama Preset</label>
                    <input type="text" x-model="tierForm.name" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Contoh: Default Qty">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Qty Simulasi</label>
                    <input type="number" min="1" step="1" x-model.number="tierForm.simulation_qty" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Masukkan qty simulasi">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Daftar Tier</label>
                    <div class="space-y-2">
                        <template x-for="(t, idx) in tierForm.tiers" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-3">
                                    <input type="number" min="1" class="w-full px-2 py-1 border border-gray-300 rounded-md text-xs" x-model.number="t.min" placeholder="Min">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" min="1" class="w-full px-2 py-1 border border-gray-300 rounded-md text-xs" x-model.number="t.max" placeholder="Max (kosong = unlimited)">
                                </div>
                                <div class="col-span-3">
                                    <div class="flex items-center">
                                        <input type="number" min="0" max="100" step="0.01" class="w-full px-2 py-1 border border-gray-300 rounded-md text-xs" x-model.number="t.percent" placeholder="%">
                                        <span class="ml-1 text-gray-500 text-xs">%</span>
                                    </div>
                                </div>
                                <div class="col-span-3 text-right">
                                    <button class="px-2 py-1 text-xs rounded-md bg-red-600 text-white" @click="removeTier(idx)">Hapus</button>
                                </div>
                            </div>
                        </template>
                        <div>
                            <button class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addTier()">+ Tambah Tier</button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input id="is_default" type="checkbox" x-model="tierForm.is_default" class="rounded border-gray-300">
                    <label for="is_default" class="text-xs text-gray-700">Jadikan default</label>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                <div>
                    <template x-if="tierForm.id">
                        <button class="px-3 py-1.5 text-xs rounded-md bg-red-600 text-white hover:bg-red-700" @click="deletePreset(tierForm.id)">Hapus</button>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-3 py-1.5 text-xs rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50" @click="closeTierModal()">Batal</button>
                    <button class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700" @click="savePreset()">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Simulation Modal -->
    <div x-cloak x-show="showSaveModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-40" @click="showSaveModal=false"></div>
        <div class="relative bg-white w-full max-w-md rounded-lg shadow-xl border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Simpan Simulasi</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showSaveModal=false"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Nama Simulasi</label>
                    <input type="text" x-model="saveName" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Masukkan nama simulasi">
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
                <button class="px-3 py-1.5 text-xs rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50" @click="showSaveModal=false">Batal</button>
                <button class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50" :disabled="isSaving" @click="confirmSaveFromModal()">
                    <span x-show="!isSaving"><i class="fas fa-save mr-1"></i> Simpan</span>
                    <span x-show="isSaving"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


