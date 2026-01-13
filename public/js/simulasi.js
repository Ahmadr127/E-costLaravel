window.simulationApp = function simulationApp() {
    console.log('simulationApp initialized');
    return {
        searchQuery: '',
        searchResults: [],
        simulationResults: [],
        grandTotal: 0,
        sumUnitCost: 0,
        sumTarifMaster: 0,
        searchTimeout: null,
        isSearching: false,
        showDropdown: false,
        selectedSearchIndex: -1,
        marginPercentage: 0.00, // Default 0% margin
        globalMarginPercent: 0,
        selectAll: false,
        selectedCount: 0,
        savedSimulations: [],
        activeSimulationId: '',
        savedFilter: '',
        categoryOptions: {},
        // Save modal state
        showSaveModal: false,
        saveName: '',
        categoryModalOptions: [],
        categoryModalSearch: '',
        selectedCategory: null,
        isSaving: false,

        async init() {
            try { await this.refreshSavedSimulations(); } catch (e) { console.warn(e); }
        },

        get filteredSavedSimulations() {
            const q = (this.savedFilter || '').toLowerCase();
            if (!q) return this.savedSimulations;
            return this.savedSimulations.filter(s => {
                const name = (s.name || '').toLowerCase();
                const category = (s.category_name || '').toLowerCase();
                return name.includes(q) || category.includes(q);
            });
        },

        async searchLayanan(query) {
            console.log('searchLayanan called with:', query);
            this.searchQuery = query;

            if (query.length < 2) {
                this.searchResults = [];
                this.isSearching = false;
                this.showDropdown = false;
                this.selectedSearchIndex = -1;
                return;
            }

            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(async () => {
                this.isSearching = true;
                this.showDropdown = true;
                this.selectedSearchIndex = -1;
                try {
                    const url = `${window.SIMULATION_SEARCH_URL}?search=${encodeURIComponent(query)}`;
                    console.log('Making request to:', url);

                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]').getAttribute('content') || ''
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

        handleSearchKeydown(event) {
            if (!this.showDropdown || this.searchResults.length === 0) {
                return;
            }

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedSearchIndex = Math.min(this.selectedSearchIndex + 1, this.searchResults.length - 1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedSearchIndex = Math.max(this.selectedSearchIndex - 1, -1);
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.selectedSearchIndex >= 0 && this.selectedSearchIndex < this.searchResults.length) {
                        this.addLayananToSimulation(this.searchResults[this.selectedSearchIndex]);
                    }
                    break;
                case 'Tab':
                    // Navigate through search results with Tab
                    event.preventDefault();
                    if (event.shiftKey) {
                        // Shift+Tab: go to previous item
                        this.selectedSearchIndex = Math.max(this.selectedSearchIndex - 1, -1);
                    } else {
                        // Tab: go to next item
                        this.selectedSearchIndex = Math.min(this.selectedSearchIndex + 1, this.searchResults.length - 1);
                    }
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.showDropdown = false;
                    this.selectedSearchIndex = -1;
                    break;
            }
        },

        addLayananToSimulation(layanan) {
            const existingIndex = this.simulationResults.findIndex(item => item.id === layanan.id);

            if (existingIndex !== -1) {
                // Item already exists, show message
                this.showNotification('Layanan ini sudah ada dalam simulasi', 'warning');
                return;
            } else {
                // Add new item with integer normalization and margin calculations
                const appliedMarginFraction = Math.max(0, Math.min(100, Number(this.globalMarginPercent))) / 100;
                const unitCostValue = Math.round(Number(layanan.unit_cost) || 0);
                const tarifMasterValue = Math.round(Number(layanan.tarif_master) || 0);
                const marginValue = Math.round(unitCostValue * appliedMarginFraction);
                const totalTarif = unitCostValue + marginValue;

                this.simulationResults.push({
                    ...layanan,
                    unit_cost: unitCostValue,
                    tarif_master: tarifMasterValue,
                    marginPercentage: appliedMarginFraction,
                    marginValue: marginValue,
                    totalTarif: totalTarif,
                    selected: false,
                    layanan_id: layanan.id,
                    kategori_id: layanan.kategori_id || null,
                    kategori_nama: layanan.kategori_nama || ''
                });

                // Clear search results and close dropdown after adding
                this.searchResults = [];
                this.searchQuery = '';
                this.showDropdown = false;
                this.selectedSearchIndex = -1;

                // Show success notification
                this.showNotification('Layanan berhasil ditambahkan ke simulasi', 'success');
            }

            this.updateGrandTotal();
        },

        onRowMarginChange(item, value) {
            const parsed = isNaN(parseInt(value, 10)) ? 0 : Math.max(0, Math.min(100, parseInt(value, 10)));
            item.marginPercentage = parsed / 100;
            this.recalcItem(item);
            this.updateGrandTotal();
        },

        onRowUnitCostChange(item, value) {
            const parsed = Math.round(Math.max(0, Number(value) || 0));
            item.unit_cost = parsed;
            this.recalcItem(item);
            this.updateGrandTotal();
        },

        // Formatted input handlers for unit cost
        unformatNumberString(str) {
            if (typeof str !== 'string') return Number(str) || 0;
            const cleaned = str.replace(/[^0-9]/g, '');
            return Number(cleaned || '0');
        },

        onUnitCostInput(item, evt) {
            const raw = evt.target.value;
            const val = this.unformatNumberString(raw);
            item.unit_cost = Math.round(Math.max(0, val));
            this.recalcItem(item);
            this.updateGrandTotal();
            // Keep caret near end while formatting
            evt.target.value = this.formatNumber(item.unit_cost);
        },

        onUnitCostBlur(item, evt) {
            evt.target.value = this.formatNumber(item.unit_cost);
        },

        recalcItem(item) {
            const unitCostValue = Math.round(Number(item.unit_cost) || 0);
            item.unit_cost = unitCostValue;
            item.marginValue = Math.round(unitCostValue * (Number(item.marginPercentage) || 0));
            item.totalTarif = unitCostValue + item.marginValue;
        },

        recalcAll() {
            this.simulationResults.forEach(item => this.recalcItem(item));
            this.updateGrandTotal();
        },

        applyGlobalMarginToAll() {
            // enforce integer percent
            this.globalMarginPercent = parseInt(this.globalMarginPercent, 10) || 0;
            const fraction = Math.max(0, Math.min(100, this.globalMarginPercent)) / 100;
            this.simulationResults.forEach(item => { item.marginPercentage = fraction; this.recalcItem(item); });
            this.updateGrandTotal();
            this.showNotification('Margin diterapkan ke semua layanan', 'success');
        },

        applyGlobalMarginToSelected() {
            // enforce integer percent
            this.globalMarginPercent = parseInt(this.globalMarginPercent, 10) || 0;
            const fraction = Math.max(0, Math.min(100, this.globalMarginPercent)) / 100;
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
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${type === 'success' ? 'bg-green-500 text-white' :
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
            this.grandTotal = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.totalTarif) || 0)), 0);
            this.sumUnitCost = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.unit_cost) || 0)), 0);
            this.sumTarifMaster = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.tarif_master) || 0)), 0);
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
            const n = Math.round(Number(number) || 0);
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
        },

        exportResults() {
            if (this.simulationResults.length === 0) return;

            const formatAccounting = (num) => {
                const n = Number(num) || 0;
                return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
            };

            const headers = ['No', 'Kode', 'Jenis Pemeriksaan', 'Kategori', 'Tarif Master', 'Unit Cost', 'Margin (%)', 'Nilai Margin (Rp)', 'Tarif (Unit Cost + Margin)'];
            const rows = this.simulationResults.map((item, index) => [
                index + 1,
                item.kode,
                item.jenis_pemeriksaan,
                item.kategori_nama || '-',
                formatAccounting(item.tarif_master),
                formatAccounting(item.unit_cost),
                (item.marginPercentage * 100).toFixed(2) + '%',
                formatAccounting(item.marginValue),
                formatAccounting(item.totalTarif)
            ]);

            const totalsRow = [
                '', '', 'Total', '',
                formatAccounting(this.sumTarifMaster),
                formatAccounting(this.sumUnitCost),
                '',
                '',
                formatAccounting(this.grandTotal)
            ];

            const csvLines = [];
            csvLines.push(headers.join(','));
            rows.forEach(r => csvLines.push(r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')));
            csvLines.push(totalsRow.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));

            const csv = csvLines.join('\r\n');
            this.downloadCSV(csv, 'simulasi-unit-cost.csv');
        },

        async refreshSavedSimulations() {
            try {
                const res = await fetch(window.SIMULATION_LIST_URL, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN
                    }
                });
                if (!res.ok) {
                    console.error('Failed to fetch saved simulations:', res.status, res.statusText);
                    return;
                }
                const data = await res.json();
                this.savedSimulations = data.data || [];
            } catch (e) {
                console.error('Error refreshing saved simulations:', e);
            }
        },

        promptSaveSimulation() {
            if (this.simulationResults.length === 0) {
                this.showNotification('Tidak ada data untuk disimpan', 'warning');
                return;
            }
            this.openSaveModal();
        },

        openSaveModal() {
            // Prefill with existing name ONLY if editing an active simulation
            this.saveName = this.activeSimulationId ? this.saveName : '';
            this.selectedCategory = null;
            this.categoryModalSearch = '';
            this.categoryModalOptions = [];
            this.fetchCategoriesForModal('');
            this.showSaveModal = true;
        },

        async fetchCategoriesForModal(q = '') {
            try {
                const url = `${window.SIMULATION_CATEGORIES_URL}?search=${encodeURIComponent(q)}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN
                    }
                });
                const data = await res.json();
                this.categoryModalOptions = data.data || [];
            } catch (e) {
                console.error(e);
                this.categoryModalOptions = [];
            }
        },

        selectModalCategory(opt) {
            this.selectedCategory = opt;
        },

        applyModalCategoryToAll() {
            if (!this.selectedCategory) return;
            this.simulationResults.forEach(item => {
                item.kategori_id = this.selectedCategory.id;
                item.kategori_nama = this.selectedCategory.nama_kategori;
            });
        },

        async confirmSaveFromModal() {
            if (!this.saveName || !this.saveName.trim()) {
                this.showNotification('Nama simulasi wajib diisi', 'warning');
                return;
            }
            // Tidak perlu memilih kategori untuk menyimpan simulasi
            this.isSaving = true;
            try {
                await this.saveSimulation(this.saveName.trim());
                this.showSaveModal = false;
            } finally {
                this.isSaving = false;
            }
        },

        async saveSimulation(name) {
            const payload = {
                name,
                notes: '',
                sum_unit_cost: this.sumUnitCost,
                sum_tarif_master: this.sumTarifMaster,
                grand_total: this.grandTotal,
                items: this.simulationResults.map(item => ({
                    layanan_id: item.layanan_id || item.id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: Math.round(Number(item.tarif_master) || 0),
                    unit_cost: Math.round(Number(item.unit_cost) || 0),
                    margin_value: Math.round(Number(item.marginValue) || 0),
                    margin_percentage: (Number(item.marginPercentage) || 0) * 100,
                    total_tarif: Math.round(Number(item.totalTarif) || 0)
                }))
            };

            const isUpdating = !!this.activeSimulationId;
            const url = isUpdating ? window.SIMULATION_UPDATE_URL(this.activeSimulationId) : window.SIMULATION_STORE_URL;
            const method = isUpdating ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN
                },
                body: JSON.stringify(payload)
            });
            if (!res.ok) {
                const text = await res.text();
                console.error(text);
                this.showNotification(isUpdating ? 'Gagal memperbarui simulasi' : 'Gagal menyimpan simulasi', 'warning');
                return;
            }
            const data = await res.json();
            this.showNotification(isUpdating ? 'Simulasi diperbarui' : 'Simulasi disimpan', 'success');
            this.activeSimulationId = isUpdating ? this.activeSimulationId : (data.data?.id || '');
            await this.refreshSavedSimulations();
        },

        async loadSimulation(id) {
            if (!id) return;
            try {
                console.log('Loading simulation with ID:', id);
                console.log('CSRF Token:', window.CSRF_TOKEN);
                console.log('URL:', window.SIMULATION_SHOW_URL(id));

                const res = await fetch(window.SIMULATION_SHOW_URL(id), {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN
                    }
                });

                console.log('Response status:', res.status);
                console.log('Response headers:', res.headers);
                if (!res.ok) {
                    if (res.status === 403) {
                        this.showNotification('Tidak memiliki akses ke simulasi ini', 'warning');
                    } else if (res.status === 404) {
                        this.showNotification('Simulasi tidak ditemukan', 'warning');
                    } else {
                        this.showNotification('Gagal memuat simulasi', 'warning');
                    }
                    return;
                }
                const data = await res.json();
                const sim = data.data;
                this.simulationResults = (sim.items || []).map(item => ({
                    id: item.layanan_id,
                    layanan_id: item.layanan_id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: item.tarif_master,
                    unit_cost: item.unit_cost,
                    marginPercentage: (item.margin_percentage || 0) / 100,
                    marginValue: item.margin_value,
                    totalTarif: item.total_tarif,
                    selected: false,
                    kategori_id: item.kategori_id || null,
                    kategori_nama: item.kategori_nama || ''
                }));
                this.recalcAll();
                // Ensure selectedCategory reflects loaded data if available
                const firstCat = this.simulationResults[0]?.kategori_nama || '';
                if (firstCat) {
                    this.selectedCategory = { id: this.simulationResults[0].kategori_id, nama_kategori: firstCat };
                }
                // Set active simulation id and current name for editing
                this.activeSimulationId = sim.id;
                this.saveName = sim.name || '';
            } catch (error) {
                console.error('Error loading simulation:', error);
                this.showNotification('Terjadi kesalahan saat memuat simulasi', 'warning');
            }
        },

        async deleteActiveSimulation() {
            if (!this.activeSimulationId) return;
            if (!confirm('Hapus simulasi tersimpan ini?')) return;
            const res = await fetch(window.SIMULATION_DELETE_URL(this.activeSimulationId), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'Accept': 'application/json' }
            });
            if (res.ok) {
                this.showNotification('Simulasi terhapus', 'success');
                this.activeSimulationId = '';
                await this.refreshSavedSimulations();
            } else {
                this.showNotification('Gagal menghapus', 'warning');
            }
        },

        async deleteSaved(id) {
            if (!confirm('Hapus simulasi ini?')) return;
            const res = await fetch(window.SIMULATION_DELETE_URL(id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'Accept': 'application/json' }
            });
            if (res.ok) {
                if (this.activeSimulationId === id) this.activeSimulationId = '';
                await this.refreshSavedSimulations();
                this.showNotification('Simulasi terhapus', 'success');
            } else {
                this.showNotification('Gagal menghapus', 'warning');
            }
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
            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        formatDateAgo(iso) {
            try {
                const then = new Date(iso);
                const now = new Date();
                const diff = Math.max(0, now - then);
                const mins = Math.floor(diff / 60000);
                if (mins < 1) return 'baru saja';
                if (mins < 60) return `${mins} m lalu`;
                const hours = Math.floor(mins / 60);
                if (hours < 24) return `${hours} j lalu`;
                const days = Math.floor(hours / 24);
                if (days < 7) return `${days} h lalu`;
                return then.toLocaleDateString('id-ID');
            } catch { return ''; }
        },

        // Category helpers
        async fetchCategories(q = '', idx = null) {
            const url = `${window.SIMULATION_CATEGORIES_URL}?search=${encodeURIComponent(q)}`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN
                }
            });
            const data = await res.json();
            const options = data.data || [];
            if (idx === null) {
                this.categoryOptions = this.simulationResults.reduce((acc, _it, i) => { acc[i] = options; return acc; }, {});
            } else {
                this.$nextTick(() => { this.categoryOptions[idx] = options; });
            }
        },

        selectCategory(idx, opt) {
            const item = this.simulationResults[idx];
            if (!item) return;
            item.kategori_id = opt.id;
            item.kategori_nama = opt.nama_kategori;
        },

        resetSimulation() {
            this.simulationResults = [];
            this.grandTotal = 0;
            this.activeSimulationId = '';
            this.saveName = '';
            this.clearSelection();
        }
    }
};