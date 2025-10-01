@extends('layouts.app')

@section('title', 'Simulasi Unit Cost')

@push('scripts')
<script>
    window.SIMULATION_SEARCH_URL = "{{ route('simulation.search') }}";
    window.SIMULATION_LIST_URL = "{{ route('simulation.list') }}";
    window.SIMULATION_STORE_URL = "{{ route('simulation.store') }}";
    window.SIMULATION_CATEGORIES_URL = "{{ route('simulation.categories') }}";
    window.SIMULATION_SHOW_URL = function(id){ return `{{ url('simulation') }}/${id}` };
    window.SIMULATION_UPDATE_URL = function(id){ return `{{ url('simulation') }}/${id}` };
    window.SIMULATION_DELETE_URL = function(id){ return `{{ url('simulation') }}/${id}` };
    window.CSRF_TOKEN = "{{ csrf_token() }}";
</script>
<script src="{{ asset('js/simulasi.js') }}"></script>
@endpush

@section('content')
<div x-data="simulationApp()" class="space-y-4">
    <!-- Header & Search Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Simulasi Unit Cost</h1>
                <p class="text-sm text-gray-600">Hitung unit cost berdasarkan layanan yang dipilih</p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="hidden md:flex items-center space-x-2 mr-2">
                    <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-green-600 text-white hover:bg-green-700 focus:ring-green-500" @click="promptSaveSimulation()">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                    <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" @click="deleteActiveSimulation()" x-bind:disabled="!activeSimulationId">
                        <i class="fas fa-trash mr-1"></i> Hapus
                    </button>
                </div>
                <button 
                    type="button"
                    class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-3 py-1.5 text-xs bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="exportResults()"
                    x-bind:disabled="simulationResults.length === 0">
                    <i class="fas fa-download mr-1"></i>
                    Export
                </button>
                <button 
                    type="button"
                    class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-3 py-1.5 text-xs bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500"
                    @click="resetSimulation()">
                    <i class="fas fa-refresh mr-1"></i>
                    Reset
                </button>
            </div>
        </div>
        
        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                Cari Layanan
            </label>
            <input type="text" 
                   id="search" 
                   name="search"
                   x-model="searchQuery"
                   @input="searchLayanan($event.target.value)"
                   @focus="if(searchResults.length > 0) showDropdown = true"
                   @blur="setTimeout(() => { if (!document.activeElement || !document.activeElement.closest('[x-show*=\"showDropdown\"]')) showDropdown = false; }, 200)"
                   @keydown="handleSearchKeydown($event)"
                   placeholder="Masukkan kode atau jenis pemeriksaan..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            
            <!-- Dropdown Results -->
            <div x-show="showDropdown && searchResults.length > 0" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-80 overflow-y-auto">
                <div class="divide-y divide-gray-100">
                    <template x-for="(result, index) in searchResults" :key="result.id">
                        <div class="px-2 py-1.5 hover:bg-gray-50 border-b border-gray-50 last:border-b-0" 
                             :class="selectedSearchIndex === index ? 'bg-blue-50 border-blue-200' : ''"
                             @mouseenter="selectedSearchIndex = index">
                            <div class="flex justify-between items-center">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 truncate" x-text="result.kode + ' - ' + result.jenis_pemeriksaan"></p>
                                    <p class="text-[10px] text-gray-500" x-text="result.tarif_master ? ('Tarif: ' + result.tarif_master) : ''"></p>
                                </div>
                                <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-green-600" x-text="'Rp ' + formatNumber(result.unit_cost)"></p>
                                    </div>
                                    <button type="button" 
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-green-600 border border-transparent rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                                            @click="addLayananToSimulation(result)"
                                            :tabindex="selectedSearchIndex === index ? 0 : -1">
                                        Pilih
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Loading indicator in dropdown -->
            <div x-show="isSearching" 
                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                <div class="flex items-center justify-center py-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
                    <span class="ml-2 text-gray-600 text-xs">Mencari...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main 2-column layout: 70% simulation, 30% saved simulations -->
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4">
        <!-- Left: Simulation (span 7) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Simulation Results -->
            <div x-show="simulationResults.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Hasil Simulasi</h2>
        </div>
        <!-- Bulk Margin Controls -->
        <div class="p-3 border-b border-gray-200 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Margin Global (%)</label>
                    <div class="flex items-center">
                        <input type="number" min="0" max="100" step="1" x-model.number="globalMarginPercent" class="w-28 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" />
                    </div>
                </div>
                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-green-600 text-white hover:bg-green-700 focus:ring-green-500" @click="applyGlobalMarginToAll()">
                    <i class="fas fa-percent mr-1"></i> Terapkan ke Semua
                </button>
                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-500" @click="applyGlobalMarginToSelected()" x-bind:disabled="selectedCount === 0">
                    <i class="fas fa-list-check mr-1"></i> Terapkan ke Terpilih (<span x-text="selectedCount"></span>)
                </button>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-600"></div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="rounded border-gray-300" :checked="selectAll" @change="toggleSelectAll($event.target.checked)">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Pemeriksaan</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif Master</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Margin (%)</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Margin (Rp)</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(result, index) in simulationResults" :key="result.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <input type="checkbox" class="rounded border-gray-300" x-model="result.selected" @change="updateSelectedCount()">
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="index + 1"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-gray-900" x-text="result.kode"></td>
                            <td class="px-3 py-2 text-xs text-gray-900 max-w-xs truncate">
                                <div class="flex flex-col">
                                    <span x-text="result.jenis_pemeriksaan"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="result.kategori_nama || '-'"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(result.tarif_master || 0)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <input type="text" inputmode="numeric" class="w-28 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" :value="formatNumber(result.unit_cost)" @input="onUnitCostInput(result, $event)" @focus="$event.target.select()" @blur="onUnitCostBlur(result, $event)">
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <div class="flex items-center gap-1">
                                    <input type="number" min="0" max="100" step="1" class="w-20 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" :value="Math.round(result.marginPercentage * 100)" @input="onRowMarginChange(result, $event.target.value)">
                                    <span class="text-gray-400">%</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(result.marginValue)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-red-600" x-text="'Rp ' + formatNumber(result.totalTarif)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                <button 
                                    type="button"
                                    class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-1.5 py-1 text-xs bg-red-600 text-white hover:bg-red-700 focus:ring-red-500"
                                    @click="if(confirm('Hapus layanan ini dari simulasi?')) removeFromSimulation(index)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 text-right text-xs font-semibold text-gray-900">Total:</td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-gray-900" x-text="'Rp ' + formatNumber(sumTarifMaster)"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-gray-900" x-text="'Rp ' + formatNumber(sumUnitCost)"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-xs font-semibold text-red-600" x-text="'Rp ' + formatNumber(grandTotal)"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="bg-gray-50 px-3 py-2 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm font-semibold text-gray-900">
                    Total Tarif: <span class="text-red-600" x-text="'Rp ' + formatNumber(grandTotal)"></span>
                </div>
                <div class="text-xs text-gray-500">
                    <span x-text="simulationResults.length"></span> layanan
                </div>
            </div>
        </div>
            </div>

            <!-- Empty State -->
            <div x-show="simulationResults.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="flex flex-col items-center">
                    <i class="fas fa-calculator text-4xl text-gray-300 mb-3"></i>
                    <h3 class="text-base font-medium text-gray-900 mb-1">Belum ada simulasi</h3>
                    <p class="text-sm text-gray-500">Pilih layanan dan tambahkan ke simulasi untuk memulai perhitungan unit cost</p>
                </div>
            </div>
        </div>

        <!-- Right: Saved simulations (span 3) -->
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
                                    <button class="px-2 py-1 text-[11px] bg-blue-600 text-white rounded hover:bg-blue-700" @click="loadSimulation(item.id)">
                                        Muat
                                    </button>
                                    <button class="px-2 py-1 text-[11px] bg-red-600 text-white rounded hover:bg-red-700" @click="deleteSaved(item.id)">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredSavedSimulations.length === 0" class="p-4 text-center text-xs text-gray-500">Tidak ada data</div>
                </div>
            </div>
        </aside>
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
                <!-- Kategori picker dihapus sesuai requirement -->
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
