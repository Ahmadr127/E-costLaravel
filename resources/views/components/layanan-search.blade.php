@props([
    'placeholder' => 'Masukkan kode atau jenis pemeriksaan...',
    'label' => 'Cari Layanan',
    'showLabel' => true,
])

<div class="relative" x-data="layananSearchComponent()">
    @if($showLabel)
    <label for="layanan-search" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
    </label>
    @endif
    
    <input 
        type="text" 
        id="layanan-search" 
        name="search"
        x-model="searchQuery"
        @input="filterResults($event.target.value)"
        @focus="handleFocus()"
        @blur="setTimeout(() => { showDropdown = false; }, 200)"
        @keydown="handleSearchKeydown($event)"
        :placeholder="placeholder"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
        autocomplete="off"
    >
    
    <!-- Dropdown Results -->
    <div 
        x-show="showDropdown && filteredResults.length > 0" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-80 overflow-y-auto"
        style="display: none;"
    >
        <div class="divide-y divide-gray-100">
            <template x-for="(result, index) in filteredResults" :key="result.id">
                <div 
                    class="px-3 py-2 hover:bg-gray-50 border-b border-gray-50 last:border-b-0 cursor-pointer" 
                    :class="selectedSearchIndex === index ? 'bg-blue-50 border-blue-200' : ''"
                    @mouseenter="selectedSearchIndex = index"
                    @click="selectLayanan(result)"
                >
                    <div class="flex justify-between items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="result.kode + ' - ' + result.jenis_pemeriksaan"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="result.kategori_nama || '-'"></p>
                        </div>
                        <button 
                            type="button" 
                            class="flex-shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 border border-transparent rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                            @click.stop="selectLayanan(result)"
                            :tabindex="selectedSearchIndex === index ? 0 : -1"
                        >
                            Pilih
                        </button>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Info jumlah hasil -->
        <div class="px-3 py-2 bg-gray-50 border-t border-gray-200 text-xs text-gray-600">
            <span x-text="filteredResults.length"></span> layanan ditemukan
            <template x-if="allResults.length > filteredResults.length">
                <span> dari <span x-text="allResults.length"></span> total</span>
            </template>
        </div>
    </div>
    
    <!-- Loading indicator -->
    <div 
        x-show="isLoading" 
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
        style="display: none;"
    >
        <div class="flex items-center justify-center py-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
            <span class="ml-2 text-gray-600 text-xs">Memuat data...</span>
        </div>
    </div>
    
    <!-- Empty state -->
    <div 
        x-show="showDropdown && !isLoading && filteredResults.length === 0 && searchQuery.length > 0" 
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
        style="display: none;"
    >
        <div class="flex flex-col items-center justify-center py-4 text-gray-500">
            <i class="fas fa-search text-2xl mb-2"></i>
            <p class="text-sm">Tidak ada layanan ditemukan</p>
            <p class="text-xs">Coba kata kunci lain</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function layananSearchComponent() {
    return {
        searchQuery: '',
        allResults: [],
        filteredResults: [],
        isLoading: false,
        showDropdown: false,
        selectedSearchIndex: -1,
        placeholder: '{{ $placeholder }}',
        dataLoaded: false,

        async init() {
            // Preload data saat component dimuat (opsional untuk performa)
            // await this.loadAllLayanan();
        },

        async handleFocus() {
            // Ketika input diklik, load semua data jika belum
            if (!this.dataLoaded) {
                await this.loadAllLayanan();
            }
            
            // Tampilkan dropdown dengan semua data
            this.filteredResults = this.allResults;
            this.showDropdown = true;
            this.selectedSearchIndex = -1;
        },

        async loadAllLayanan() {
            this.isLoading = true;
            
            try {
                const url = `${window.SIMULATION_SEARCH_URL}?all=1`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                this.allResults = data.data || [];
                this.filteredResults = this.allResults;
                this.dataLoaded = true;
            } catch (error) {
                console.error('Error loading layanan:', error);
                this.allResults = [];
                this.filteredResults = [];
            } finally {
                this.isLoading = false;
            }
        },

        filterResults(query) {
            this.searchQuery = query;
            
            if (!query || query.trim() === '') {
                // Jika kosong, tampilkan semua
                this.filteredResults = this.allResults;
            } else {
                // Filter berdasarkan query
                const searchLower = query.toLowerCase();
                this.filteredResults = this.allResults.filter(item => {
                    const kode = (item.kode || '').toLowerCase();
                    const jenis = (item.jenis_pemeriksaan || '').toLowerCase();
                    const kategori = (item.kategori_nama || '').toLowerCase();
                    
                    return kode.includes(searchLower) || 
                           jenis.includes(searchLower) || 
                           kategori.includes(searchLower);
                });
            }
            
            this.selectedSearchIndex = -1;
            this.showDropdown = true;
        },

        handleSearchKeydown(event) {
            if (!this.showDropdown || this.filteredResults.length === 0) {
                return;
            }

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedSearchIndex = Math.min(this.selectedSearchIndex + 1, this.filteredResults.length - 1);
                    this.scrollToSelected();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedSearchIndex = Math.max(this.selectedSearchIndex - 1, -1);
                    this.scrollToSelected();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.selectedSearchIndex >= 0 && this.selectedSearchIndex < this.filteredResults.length) {
                        this.selectLayanan(this.filteredResults[this.selectedSearchIndex]);
                    }
                    break;
                case 'Tab':
                    event.preventDefault();
                    if (event.shiftKey) {
                        this.selectedSearchIndex = Math.max(this.selectedSearchIndex - 1, -1);
                    } else {
                        this.selectedSearchIndex = Math.min(this.selectedSearchIndex + 1, this.filteredResults.length - 1);
                    }
                    this.scrollToSelected();
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.showDropdown = false;
                    this.selectedSearchIndex = -1;
                    break;
            }
        },

        scrollToSelected() {
            // Auto-scroll ke item yang di-highlight
            this.$nextTick(() => {
                const dropdown = this.$el.querySelector('[x-show="showDropdown && filteredResults.length > 0"]');
                if (!dropdown) return;
                
                const items = dropdown.querySelectorAll('[class*="px-2 py-1.5"]');
                if (items[this.selectedSearchIndex]) {
                    items[this.selectedSearchIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        },

        selectLayanan(layanan) {
            // Dispatch custom event untuk parent component
            this.$dispatch('layanan-selected', layanan);
            
            // Clear search
            this.searchQuery = '';
            this.filteredResults = this.allResults;
            this.showDropdown = false;
            this.selectedSearchIndex = -1;
        },

        formatNumber(number) {
            const n = Math.round(Number(number) || 0);
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
        }
    }
}
</script>
@endpush
