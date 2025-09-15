@extends('layouts.app')

@section('title', 'Simulasi Unit Cost')

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
                   @blur="setTimeout(() => showDropdown = false, 200)"
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
                    <template x-for="result in searchResults" :key="result.id">
                        <div class="px-2 py-1.5 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-b-0" 
                             @click="addLayananToSimulation(result)">
                            <div class="flex justify-between items-center">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 truncate" x-text="result.kode + ' - ' + result.jenis_pemeriksaan"></p>
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
                        <input type="number" min="0" max="100" step="0.01" x-model.number="globalMarginPercent" class="w-28 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" />
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
                            <td class="px-3 py-2 text-xs text-gray-900 max-w-xs truncate" x-text="result.jenis_pemeriksaan"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900" x-text="'Rp ' + formatNumber(result.unit_cost)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">
                                <div class="flex items-center gap-1">
                                    <input type="number" min="0" max="100" step="0.01" class="w-20 px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" :value="(result.marginPercentage * 100).toFixed(2)" @input="onRowMarginChange(result, $event.target.value)">
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
                        <td class="px-3 py-2 text-right text-xs font-semibold text-gray-900">Total:</td>
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

<script>
function simulationApp() {
    console.log('simulationApp initialized');
    return {
        searchQuery: '',
        searchResults: [],
        simulationResults: [],
        grandTotal: 0,
        sumUnitCost: 0,
        searchTimeout: null,
        isSearching: false,
        showDropdown: false,
        marginPercentage: 0.10, // Default 10% margin
        globalMarginPercent: 10,
        selectAll: false,
        selectedCount: 0,

        async searchLayanan(query) {
            console.log('searchLayanan called with:', query);
            this.searchQuery = query;
            
            if (query.length < 2) {
                this.searchResults = [];
                this.isSearching = false;
                this.showDropdown = false;
                return;
            }

            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(async () => {
                this.isSearching = true;
                this.showDropdown = true;
                try {
                    console.log('Making request to:', `{{ route('simulation.search') }}?search=${encodeURIComponent(query)}`);
                    
                    const response = await fetch(`{{ route('simulation.search') }}?search=${encodeURIComponent(query)}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        credentials: 'same-origin'
                    });
                    
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Error response:', errorText);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('Response data:', data);
                    this.searchResults = data.data || [];
                    console.log('Search results updated:', this.searchResults);
                } catch (error) {
                    console.error('Error searching layanan:', error);
                    this.searchResults = [];
                } finally {
                    this.isSearching = false;
                }
            }, 300);
        },

        addLayananToSimulation(layanan) {
            const existingIndex = this.simulationResults.findIndex(item => item.id === layanan.id);
            
            if (existingIndex !== -1) {
                // Item already exists, show message
                this.showNotification('Layanan ini sudah ada dalam simulasi', 'warning');
                return;
            } else {
                // Add new item with margin calculations
                const appliedMarginFraction = Math.max(0, Math.min(100, Number(this.globalMarginPercent))) / 100;
                const marginValue = Math.round(layanan.unit_cost * appliedMarginFraction);
                const totalTarif = layanan.unit_cost + marginValue;
                
                this.simulationResults.push({
                    ...layanan,
                    marginPercentage: appliedMarginFraction,
                    marginValue: marginValue,
                    totalTarif: totalTarif,
                    selected: false
                });
                
                // Clear search results and close dropdown after adding
                this.searchResults = [];
                this.searchQuery = '';
                this.showDropdown = false;  
                
                // Show success notification
                this.showNotification('Layanan berhasil ditambahkan ke simulasi', 'success');
            }

            this.updateGrandTotal();
        },

        onRowMarginChange(item, value) {
            const percent = isNaN(parseFloat(value)) ? 0 : Math.max(0, Math.min(100, parseFloat(value)));
            item.marginPercentage = percent / 100;
            this.recalcItem(item);
            this.updateGrandTotal();
        },

        recalcItem(item) {
            item.marginValue = Math.round(item.unit_cost * item.marginPercentage);
            item.totalTarif = item.unit_cost + item.marginValue;
        },

        recalcAll() {
            this.simulationResults.forEach(item => this.recalcItem(item));
            this.updateGrandTotal();
        },

        applyGlobalMarginToAll() {
            const fraction = Math.max(0, Math.min(100, Number(this.globalMarginPercent))) / 100;
            this.simulationResults.forEach(item => { item.marginPercentage = fraction; this.recalcItem(item); });
            this.updateGrandTotal();
            this.showNotification('Margin diterapkan ke semua layanan', 'success');
        },

        applyGlobalMarginToSelected() {
            const fraction = Math.max(0, Math.min(100, Number(this.globalMarginPercent))) / 100;
            let changed = 0;
            this.simulationResults.forEach(item => {
                if (item.selected) {
                    item.marginPercentage = fraction;
                    this.recalcItem(item);
                    changed++;
                }
            });
            this.updateGrandTotal();
            this.showNotification(changed > 0 ? `Margin diterapkan ke ${changed} layanan terpilih` : 'Tidak ada layanan terpilih', changed > 0 ? 'success' : 'info');
        },

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'warning' ? 'bg-yellow-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        },

        removeFromSimulation(index) {
            this.simulationResults.splice(index, 1);
            this.updateGrandTotal();
            this.updateSelectedCount();
        },

        updateGrandTotal() {
            this.grandTotal = this.simulationResults.reduce((sum, item) => sum + item.totalTarif, 0);
            this.sumUnitCost = this.simulationResults.reduce((sum, item) => sum + item.unit_cost, 0);
        },

        toggleSelectAll(checked) {
            this.selectAll = !!checked;
            this.simulationResults.forEach(item => item.selected = this.selectAll);
            this.updateSelectedCount();
        },

        updateSelectedCount() {
            this.selectedCount = this.simulationResults.filter(i => i.selected).length;
            this.selectAll = this.simulationResults.length > 0 && this.selectedCount === this.simulationResults.length;
        },

        clearSelection() {
            this.selectAll = false;
            this.simulationResults.forEach(item => item.selected = false);
            this.updateSelectedCount();
        },

        formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        },

        exportResults() {
            if (this.simulationResults.length === 0) return;

            const data = this.simulationResults.map((item, index) => ({
                'No': index + 1,
                'Kode': item.kode,
                'Jenis Pemeriksaan': item.jenis_pemeriksaan,
                'Unit Cost': item.unit_cost,
                'Margin (%)': (item.marginPercentage * 100).toFixed(2) + '%',
                'Nilai Margin (Rp)': item.marginValue,
                'Tarif (Unit Cost + Margin)': item.totalTarif
            }));

            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, 'simulasi-unit-cost.csv');
        },

        convertToCSV(data) {
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
            ].join('\n');
            return csvContent;
        },

        downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        resetSimulation() {
            this.simulationResults = [];
            this.grandTotal = 0;
            this.clearSelection();
        }
    }
}
</script>
@endsection

