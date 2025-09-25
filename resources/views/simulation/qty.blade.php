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
<script src="{{ asset('js/simulasi_qty.js') }}"></script>
@endpush

@section('content')
<div x-data="simulationQtyApp()" class="space-y-4">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Simulasi Unit Cost (Qty)</h1>
                <p class="text-sm text-gray-600">Hitung total berdasarkan qty dengan margin bertingkat</p>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-2.5 py-1.5 text-xs bg-green-600 text-white hover:bg-green-700 focus:ring-green-500" @click="promptSaveSimulation()">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
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

        <div class="relative">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                Cari Layanan
            </label>
            <input type="text" id="search" name="search" x-model="searchQuery" @input="searchLayanan($event.target.value)" @focus="if(searchResults.length > 0) showDropdown = true" @blur="setTimeout(() => showDropdown = false, 200)" placeholder="Masukkan kode atau jenis pemeriksaan..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">

            <div x-show="showDropdown && searchResults.length > 0" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-80 overflow-y-auto">
                <div class="divide-y divide-gray-100">
                    <template x-for="result in searchResults" :key="result.id">
                        <div class="px-2 py-1.5 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-b-0" @click="addLayananToSimulation(result)">
                            <div class="flex justify-between items-center">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 truncate" x-text="result.kode + ' - ' + result.jenis_pemeriksaan"></p>
                                    <p class="text-[10px] text-gray-500" x-text="result.tarif_master ? ('Tarif: ' + result.tarif_master) : ''"></p>
                                </div>
                                <div class="text-right ml-2 flex-shrink-0">
                                    <p class="text-xs font-semibold text-green-600" x-text="'Rp ' + formatNumber(result.unit_cost)"></p>
                                    <p class="text-xs text-gray-400">+</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="isSearching" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md">
                <div class="flex items-center justify-center py-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
                    <span class="ml-2 text-gray-600 text-xs">Mencari...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4">
        <div class="lg:col-span-7 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-3 border-b border-gray-200 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col md:flex-row md:items-end gap-2 w-full">
                <div class="flex-1">
                    <label class="block text-xs text-gray-600 mb-1">Preset Tier</label>
                    <div class="flex items-center gap-2">
                        <select x-model="activePresetId" @change="onPresetChange()" class="w-full md:w-64 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <template x-for="p in tierPresets" :key="p.id">
                                <option :value="p.id" x-text="p.name + (p.is_default ? ' (default)' : '')"></option>
                            </template>
                        </select>
                        <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="openTierEditor()">Edit</button>
                        <button type="button" class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="openTierCreator()">Baru</button>
                    </div>
                </div>
                <!-- PRESET-BASED QTY SIMULATION: Qty from preset -->
                <div class="flex items-center gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Qty Simulasi (dari Preset)</label>
                        <div class="w-24 px-2 py-1 bg-gray-100 rounded-md text-xs text-center font-medium" x-text="simulationQuantity"></div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Margin (%)</label>
                        <div class="w-20 px-2 py-1 bg-gray-100 rounded-md text-xs text-center font-medium" x-text="simulationMarginPercent.toFixed(2) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Pemeriksaan</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif Master</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(result, index) in simulationResults" :key="result.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="index + 1"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-gray-900" x-text="result.kode"></td>
                            <td class="px-3 py-2 text-xs text-gray-900 max-w-xs truncate">
                                <div class="flex flex-col">
                                    <span x-text="result.jenis_pemeriksaan"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(result.tarif_master || 0)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <input type="text" inputmode="numeric" class="w-28 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" :value="formatNumber(result.unit_cost)" @input="onUnitCostInput(result, $event)" @focus="$event.target.select()" @blur="onUnitCostBlur(result, $event)">
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                <button type="button" class="inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-1.5 py-1 text-xs bg-red-600 text-white hover:bg-red-700 focus:ring-red-500" @click="if(confirm('Hapus layanan ini dari simulasi?')) removeFromSimulation(index)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-500" colspan="2"></td>
                        <td class="px-3 py-2 text-xs text-gray-500 text-right font-medium">Total:</td>
                        <td class="px-3 py-2 text-xs text-gray-500 font-medium" x-text="'Rp ' + formatNumber(sumTarifMaster)"></td>
                        <td class="px-3 py-2 text-xs text-gray-500 font-medium" x-text="'Rp ' + formatNumber(totalUnitCost)"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                    </tr>
                    <!-- PRESET-BASED QTY SIMULATION: Simulation-level totals -->
                    <tr class="bg-green-50">
                        <td class="px-3 py-2 text-xs text-gray-500" colspan="2"></td>
                        <td class="px-3 py-2 text-xs text-gray-700 font-medium">Simulasi:</td>
                        <td class="px-3 py-2 text-xs text-gray-700 font-medium" x-text="'Qty: ' + simulationQuantity + ' (Preset)'"></td>
                        <td class="px-3 py-2 text-xs text-gray-700 font-medium" x-text="'Margin: ' + simulationMarginPercent.toFixed(2) + '%'"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                    </tr>
                    <tr class="bg-red-50">
                        <td class="px-3 py-2 text-xs text-gray-500" colspan="2"></td>
                        <td class="px-3 py-2 text-xs text-red-700 font-semibold">Grand Total:</td>
                        <td class="px-3 py-2 text-xs text-red-700 font-semibold" x-text="'Rp ' + formatNumber(grandTotal)"></td>
                        <td class="px-3 py-2 text-xs text-red-700 font-semibold" x-text="'Margin: Rp ' + formatNumber(totalMarginValue)"></td>
                        <td class="px-3 py-2 text-xs text-gray-500"></td>
                    </tr>
                </tfoot>
            </table>
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
                                    <button class="px-2 py-1 text-[11px] bg-blue-600 text-white rounded hover:bg-blue-700" @click="loadSimulation(item.id)">Muat</button>
                                    <button class="px-2 py-1 text-[11px] bg-red-600 text-white rounded hover:bg-red-700" @click="deleteSaved(item.id)">Hapus</button>
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
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-show="simulationResults.length > 0">
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
                        Default margin used
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
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
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


