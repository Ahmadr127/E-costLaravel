window.simulationQtyApp = function simulationQtyApp() {
    let defaultTiers = [
        { min: 1, max: 5, percent: 20 },
        { min: 6, max: 10, percent: 15 },
        { min: 11, max: null, percent: 10 },
    ];

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
        // single-item mode (force only one layanan at a time)
        singleMode: false,
        // presets & tiers (qty only)
        tierPresets: [],
        activePresetId: '',
        activeTiers: defaultTiers.slice(),
        showTierModal: false,
        tierForm: { id: null, name: '', is_default: false, tiers: defaultTiers.slice() },
        // strategy removed from UI; keep step as internal default for single subtotal row
        strategy: 'step',
        // editable default margin percent for uncovered ranges
        defaultMarginPercent: 0,
        // breakdown data for current item
        breakdownRows: [],
        defaultTierUsed: false,
        defaultTierPercent: 0,
        // selected service for breakdown display
        selectedServiceForBreakdown: '',
        // breakdown visibility toggle
        showBreakdown: false,

        // reuse modal/save from base
        showSaveModal: false,
        saveName: '',
        isSaving: false,
        activeSimulationId: '',
        isEditingExisting: false,
        savedSimulations: [],
        savedFilter: '',

        async init() {
            try {
                await this.loadPresets();
                this.applyDefaultPreset();
                await this.refreshSavedSimulations();
            } catch {}
        },

        formatNumber(number) {
            const n = Math.round(Number(number) || 0);
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
        },

        unformatNumberString(str) {
            if (typeof str !== 'string') return Number(str) || 0;
            const cleaned = str.replace(/[^0-9]/g, '');
            return Number(cleaned || '0');
        },

        searchLayanan(query) {
            this.searchQuery = query;
            if ((query || '').length < 2) {
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
                    const url = `${window.SIMULATION_SEARCH_URL}?search=${encodeURIComponent(query)}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.searchResults = data.data || [];
                } catch {
                    this.searchResults = [];
                } finally {
                    this.isSearching = false;
                }
            }, 300);
        },

        addLayananToSimulation(layanan) {
            if (!layanan) return;
            const exists = this.simulationResults.find(i => i.id === layanan.id);
            if (exists) {
                // if service already exists, just update its data
                const existingIndex = this.simulationResults.findIndex(i => i.id === layanan.id);
                this.simulationResults[existingIndex] = {
                    ...this.simulationResults[existingIndex],
                    kode: layanan.kode,
                    jenis_pemeriksaan: layanan.jenis_pemeriksaan,
                    tarif_master: Math.round(Number(layanan.tarif_master) || 0),
                    unit_cost: Math.round(Number(layanan.unit_cost) || 0),
                };
                this.recalcItem(this.simulationResults[existingIndex]);
                this.updateGrandTotal();
                this.searchResults = [];
                this.searchQuery = '';
                this.showDropdown = false;
                return;
            }

            const qty = 1;
            const unit = Math.round(Number(layanan.unit_cost) || 0);
            const marginPct = this.getTierPercent(qty) / 100;
            const marginValue = Math.round(unit * marginPct);
            const totalTarif = unit + marginValue; // per unit
            const subtotal = this.computeSubtotal(unit, qty);

            const item = {
                ...layanan,
                id: layanan.id,
                layanan_id: layanan.id,
                quantity: qty,
                unit_cost: unit,
                tarif_master: Math.round(Number(layanan.tarif_master) || 0),
                marginPercentage: marginPct,
                marginValue: marginValue,
                totalTarif: totalTarif,
                subtotal: subtotal,
                selected: false,
                marginLocked: false,
            };
            
            this.simulationResults.push(item);

            this.searchResults = [];
            this.searchQuery = '';
            this.showDropdown = false;
            this.updateGrandTotal();
            
            // Auto-select first service for breakdown if none selected (but don't show breakdown)
            if (!this.selectedServiceForBreakdown && this.simulationResults.length > 0) {
                this.selectedServiceForBreakdown = this.simulationResults[0].id;
                // Don't automatically show breakdown when adding services
                // this.updateBreakdownForSelectedService();
            }
        },

        getTierPercent(qty) {
            const n = Math.max(1, Math.floor(Number(qty) || 1));
            for (const t of (this.activeTiers || defaultTiers)) {
                if (n >= t.min && n <= t.max) return t.percent;
            }
            const last = (this.activeTiers || defaultTiers).slice(-1)[0];
            return last ? last.percent : 0;
        },

        onQtyChange(item, value) {
            const qty = Math.max(1, Math.floor(Number(value) || 1));
            item.quantity = qty;
            // if not locked, recompute from tier
            if (!item.marginLocked) {
                item.marginPercentage = this.getTierPercent(qty) / 100;
            }
            this.recalcItem(item);
            this.updateGrandTotal();
            
            // Update breakdown if this is the selected service
            if (String(this.selectedServiceForBreakdown) === String(item.id)) {
                this.updateBreakdownForSelectedService();
            }
        },

        // margin is now derived from preset; manual input removed from UI

        onUnitCostInput(item, evt) {
            const raw = evt.target.value;
            const val = this.unformatNumberString(raw);
            item.unit_cost = Math.round(Math.max(0, val));
            this.recalcItem(item);
            this.updateGrandTotal();
            evt.target.value = this.formatNumber(item.unit_cost);
            
            // Update breakdown if this is the selected service
            if (String(this.selectedServiceForBreakdown) === String(item.id)) {
                this.updateBreakdownForSelectedService();
            }
        },

        onUnitCostBlur(item, evt) {
            evt.target.value = this.formatNumber(item.unit_cost);
        },

        recalcItem(item) {
            const unit = Math.round(Number(item.unit_cost) || 0);
            const qty = Math.max(1, Math.floor(Number(item.quantity) || 1));
            item.unit_cost = unit;
            item.marginValue = Math.round(unit * (Number(item.marginPercentage) || 0));
            item.totalTarif = unit + item.marginValue; // per unit
            item.subtotal = this.strategy === 'cumulative' ? this.computeSubtotal(unit, qty) : item.totalTarif * qty;
            // update breakdown only if this is the selected service
            if (String(this.selectedServiceForBreakdown) === String(item.id)) {
                const breakdown = this.buildBreakdown(unit, qty);
                this.breakdownRows = breakdown.rows;
                this.defaultTierUsed = breakdown.defaultUsed;
                this.defaultTierPercent = breakdown.defaultPercent;
            }
        },

        updateBreakdownForSelectedService() {
            if (!this.selectedServiceForBreakdown) {
                this.breakdownRows = [];
                this.defaultTierUsed = false;
                this.defaultTierPercent = 0;
                return;
            }
            
            const selectedService = this.simulationResults.find(s => String(s.id) === String(this.selectedServiceForBreakdown));
            if (selectedService) {
                // Ensure the service has the latest calculations
                this.recalcItem(selectedService);
                const breakdown = this.buildBreakdown(selectedService.unit_cost, selectedService.quantity);
                this.breakdownRows = breakdown.rows;
                this.defaultTierUsed = breakdown.defaultUsed;
                this.defaultTierPercent = breakdown.defaultPercent;
            } else {
                // Clear breakdown if selected service not found
                this.breakdownRows = [];
                this.defaultTierUsed = false;
                this.defaultTierPercent = 0;
            }
        },

        toggleBreakdownVisibility() {
            this.showBreakdown = !this.showBreakdown;
            
            // If showing breakdown, ensure we have data to display
            if (this.showBreakdown && this.simulationResults.length > 0) {
                // If no service selected or selected service not found, select first service
                if (!this.selectedServiceForBreakdown || !this.simulationResults.find(s => String(s.id) === String(this.selectedServiceForBreakdown))) {
                    this.selectedServiceForBreakdown = this.simulationResults[0].id;
                }
                // Always update breakdown to ensure fresh data is displayed
                this.updateBreakdownForSelectedService();
            }
        },

        applyDynamicMarginToAll() {
            let changed = 0; const EPS = 1e-6;
            this.simulationResults.forEach(item => {
                if (item.marginLocked) return;
                const nextPct = this.getTierPercent(item.quantity);
                const fraction = Number(nextPct) / 100;
                const current = Number(item.marginPercentage) || 0;
                const isChanged = Math.abs(current - fraction) > EPS;
                item.marginPercentage = fraction; // normalize
                this.recalcItem(item);
                if (isChanged) changed++;
            });
            this.updateGrandTotal();
            this.showNotification(changed > 0 ? `Margin dinamis diterapkan ke ${changed} item` : 'Tidak ada perubahan', changed > 0 ? 'success' : 'info');
        },

        applyDynamicMarginToSelected() {
            // In single mode, treat as apply to all
            if (this.singleMode) { this.applyDynamicMarginToAll(); return; }
            let changed = 0; const EPS = 1e-6;
            this.simulationResults.forEach(item => {
                if (!item.selected || item.marginLocked) return;
                const nextPct = this.getTierPercent(item.quantity);
                const fraction = Number(nextPct) / 100;
                const current = Number(item.marginPercentage) || 0;
                const isChanged = Math.abs(current - fraction) > EPS;
                item.marginPercentage = fraction;
                this.recalcItem(item);
                if (isChanged) changed++;
            });
            this.updateGrandTotal();
            this.showNotification(changed > 0 ? `Margin dinamis diterapkan ke ${changed} item terpilih` : 'Tidak ada perubahan', changed > 0 ? 'success' : 'info');
        },

        updateGrandTotal() {
            this.grandTotal = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.subtotal) || 0)), 0);
            this.sumUnitCost = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.unit_cost) || 0) * Math.max(1, Math.floor(Number(item.quantity) || 1))), 0);
            this.sumTarifMaster = this.simulationResults.reduce((sum, item) => sum + (Math.round(Number(item.tarif_master) || 0) * Math.max(1, Math.floor(Number(item.quantity) || 1))), 0);
        },

        removeFromSimulation(index) {
            const removedItem = this.simulationResults[index];
            this.simulationResults.splice(index, 1);
            this.updateGrandTotal();
            
            // If the removed item was selected for breakdown, clear selection
            if (String(this.selectedServiceForBreakdown) === String(removedItem.id)) {
                this.selectedServiceForBreakdown = '';
                this.breakdownRows = [];
                this.defaultTierUsed = false;
            }
        },

        resetSimulation() {
            this.simulationResults = [];
            this.grandTotal = 0;
            this.selectedServiceForBreakdown = '';
            this.breakdownRows = [];
            this.defaultTierUsed = false;
            this.showBreakdown = false;
            this.clearSelection();
        },

        // Notifications (copy from base)
        showNotification(message, type = 'info') {
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
            setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 3000);
        },

        // Save, load, delete adapted with quantity
        promptSaveSimulation() {
            if (this.simulationResults.length === 0) { this.showNotification('Tidak ada data untuk disimpan', 'warning'); return; }
            this.showSaveModal = true;
        },

        // Saved simulations list (qty)
        async refreshSavedSimulations() {
            try {
                const res = await fetch((window.SIMULATION_QTY_LIST_URL || '/simulation-qty/list'), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN }
                });
                if (!res.ok) return;
                const data = await res.json();
                this.savedSimulations = data.data || [];
            } catch {}
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

        async loadSimulation(id) {
            if (!id) return;
            try {
                const res = await fetch(window.SIMULATION_SHOW_URL(id), { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN } });
                if (!res.ok) { this.showNotification('Gagal memuat simulasi', 'warning'); return; }
                const data = await res.json();
                const sim = data.data;
                const items = sim.items || [];
                if (items.length === 0) { this.simulationResults = []; this.updateGrandTotal(); return; }
                
                // set preset and default margin from saved simulation
                if (sim.tier_preset_id != null) {
                    this.activePresetId = sim.tier_preset_id;
                    this.onPresetChange();
                }
                if (typeof sim.default_margin_percent !== 'undefined' && sim.default_margin_percent !== null) {
                    this.defaultMarginPercent = Number(sim.default_margin_percent) || 0;
                }
                
                this.simulationResults = items.map(item => ({
                    id: item.layanan_id,
                    layanan_id: item.layanan_id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: item.tarif_master,
                    unit_cost: item.unit_cost,
                    quantity: item.quantity || 1,
                    marginPercentage: (item.margin_percentage || item.marginPercentage || 0) / 100,
                    marginValue: item.margin_value || 0,
                    totalTarif: item.total_tarif || 0,
                    selected: false,
                    marginLocked: false,
                }));
                
                this.recalcAll();
                this.activeSimulationId = sim.id;
                this.saveName = sim.name || '';
                this.isEditingExisting = true;
                
                // Auto-select first service for breakdown if available (but don't show breakdown)
                if (this.simulationResults.length > 0) {
                    this.selectedServiceForBreakdown = this.simulationResults[0].id;
                    // Don't automatically show breakdown when loading
                    // this.updateBreakdownForSelectedService();
                }
            } catch { this.showNotification('Gagal memuat simulasi', 'warning'); }
        },

        async deleteSaved(id) {
            if (!confirm('Hapus simulasi ini?')) return;
            const res = await fetch(window.SIMULATION_DELETE_URL(id), { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN } });
            if (res.ok) {
                if (this.activeSimulationId === id) this.activeSimulationId = '';
                await this.refreshSavedSimulations();
                this.showNotification('Simulasi terhapus', 'success');
            } else {
                this.showNotification('Gagal menghapus', 'warning');
            }
        },

        async confirmSaveFromModal() {
            if (!this.saveName || !this.saveName.trim()) { this.showNotification('Nama simulasi wajib diisi', 'warning'); return; }
            this.isSaving = true;
            try {
                await this.saveSimulation(this.saveName.trim());
                this.showSaveModal = false;
            } finally { this.isSaving = false; }
        },

        async saveSimulation(name) {
            const payload = {
                name,
                notes: '',
                tier_preset_id: this.activePresetId || null,
                default_margin_percent: Number(this.defaultMarginPercent || 0),
                sum_unit_cost: this.sumUnitCost,
                sum_tarif_master: this.sumTarifMaster,
                grand_total: this.grandTotal,
                items: this.simulationResults.map(item => ({
                    layanan_id: item.layanan_id || item.id,
                    quantity: Math.max(1, Math.floor(Number(item.quantity) || 1)),
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: Math.round(Number(item.tarif_master) || 0),
                    unit_cost: Math.round(Number(item.unit_cost) || 0),
                    margin_value: Math.round(Number(item.marginValue) || 0),
                    margin_percentage: (Number(item.marginPercentage) || 0) * 100,
                    total_tarif: Math.round(Number(item.totalTarif) || 0)
                }))
            };
            // Jangan replace otomatis; hanya update jika user sedang mengedit sim yang dimuat
            const isUpdating = !!this.activeSimulationId && !!this.isEditingExisting;
            const url = isUpdating ? window.SIMULATION_UPDATE_URL(this.activeSimulationId) : window.SIMULATION_STORE_URL;
            const method = isUpdating ? 'PUT' : 'POST';
            const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN }, body: JSON.stringify(payload) });
            if (!res.ok) { this.showNotification(isUpdating ? 'Gagal memperbarui simulasi' : 'Gagal menyimpan simulasi', 'warning'); return; }
            const data = await res.json();
            this.activeSimulationId = isUpdating ? this.activeSimulationId : (data.data?.id || '');
            this.showNotification(isUpdating ? 'Simulasi diperbarui' : 'Simulasi disimpan', 'success');
            // setelah simpan baru, jangan secara otomatis masuk mode edit existing
            if (!isUpdating) { this.isEditingExisting = false; }
            await this.refreshSavedSimulations();
        },

        exportResults() {
            if (this.simulationResults.length === 0) return;
            const formatAccounting = (num) => {
                const n = Number(num) || 0;
                return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
            };
            const headers = ['No','Kode','Jenis Pemeriksaan','Qty','Tarif Master','Unit Cost','Margin (%)','Nilai Margin (Rp)','Tarif (Satuan)','Subtotal'];
            const rows = this.simulationResults.map((item, index) => [
                index + 1,
                item.kode,
                item.jenis_pemeriksaan,
                item.quantity,
                formatAccounting(item.tarif_master),
                formatAccounting(item.unit_cost),
                (item.marginPercentage * 100).toFixed(2) + '%',
                formatAccounting(item.marginValue),
                formatAccounting(item.totalTarif),
                formatAccounting(item.subtotal)
            ]);
            const totalsRow = ['', '', 'Total', '', formatAccounting(this.sumTarifMaster), formatAccounting(this.sumUnitCost), '', '', '', formatAccounting(this.grandTotal)];
            const csvLines = [];
            csvLines.push(headers.join(','));
            rows.forEach(r => csvLines.push(r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')));
            csvLines.push(totalsRow.map(v => `"${String(v).replace(/"/g,'""')}"`).join(','));
            const csv = csvLines.join('\r\n');
            this.downloadCSV(csv, 'simulasi-unit-cost-qty.csv');
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

        // selection helpers (no-op in single mode)
        toggleSelectAll(_checked) {},
        updateSelectedCount() {},
        clearSelection() {},

        // lock toggle
        toggleLock(item) { item.marginLocked = !item.marginLocked; },

        // strategy change removed (no-op)
        onStrategyChange() { this.recalcAll(); },

        // default margin changed by user in UI
        onDefaultMarginChange() { 
            this.recalcAll(); 
            this.updateBreakdownForSelectedService();
        },

        // Preset APIs
        async loadPresets() {
            const res = await fetch((window.SIMULATION_QTY_PRESETS_URL || '/simulation-qty/presets'), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            this.tierPresets = data.data || [];
        },

        applyDefaultPreset() {
            const preset = this.tierPresets.find(p => p.is_default) || this.tierPresets[0];
            if (preset) {
                this.activePresetId = preset.id;
                this.activeTiers = (preset.tiers || []).map(normalizeTier);
                this.recalcAll();
            }
        },

        onPresetChange() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.activeTiers = (preset.tiers || []).map(normalizeTier);
            this.recalcAll();
            this.updateBreakdownForSelectedService();
        },

        openTierEditor() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.tierForm = { id: preset.id, name: preset.name, is_default: !!preset.is_default, tiers: (preset.tiers || []).map(cloneTier) };
            this.showTierModal = true;
        },

        openTierCreator() {
            this.tierForm = { id: null, name: '', is_default: false, tiers: defaultTiers.slice().map(cloneTier) };
            this.showTierModal = true;
        },

        closeTierModal() { this.showTierModal = false; },

        addTier() { this.tierForm.tiers.push({ min: 1, max: null, percent: 0 }); },
        removeTier(idx) { this.tierForm.tiers.splice(idx, 1); },

        async savePreset() {
            const payload = { name: (this.tierForm.name || '').trim(), is_default: !!this.tierForm.is_default, tiers: (this.tierForm.tiers || []).map(normalizeTier) };
            if (!payload.name || payload.tiers.length === 0) { this.showNotification('Nama dan tier wajib diisi', 'warning'); return; }
            const method = this.tierForm.id ? 'PUT' : 'POST';
            const url = this.tierForm.id ? `${(window.SIMULATION_QTY_PRESETS_URL || '/simulation-qty/presets')}/${this.tierForm.id}` : (window.SIMULATION_QTY_PRESETS_URL || '/simulation-qty/presets');
            const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN }, body: JSON.stringify(payload) });
            if (!res.ok) { this.showNotification('Gagal menyimpan preset', 'warning'); return; }
            await this.loadPresets();
            if (!this.tierForm.id) {
                const created = (await res.json()).data;
                this.activePresetId = created?.id || this.activePresetId;
            }
            this.onPresetChange();
            this.showNotification('Preset tersimpan', 'success');
            this.closeTierModal();
        },

        async deletePreset(id) {
            if (!id || !confirm('Hapus preset ini?')) return;
            const res = await fetch(`${(window.SIMULATION_QTY_PRESETS_URL || '/simulation-qty/presets')}/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN } });
            if (!res.ok) { this.showNotification('Gagal menghapus preset', 'warning'); return; }
            await this.loadPresets();
            this.applyDefaultPreset();
            this.closeTierModal();
            this.showNotification('Preset dihapus', 'success');
        },

        recalcAll() { this.simulationResults.forEach(i => this.onQtyChange(i, i.quantity)); this.updateGrandTotal(); },

        // cumulative subtotal helper
        computeSubtotal(unit, qty) {
            if (this.strategy !== 'cumulative') {
                const pct = this.getTierPercent(qty) / 100;
                return (unit + Math.round(unit * pct)) * qty;
            }
            let subtotal = 0;
            const tiers = (this.activeTiers || defaultTiers).map(normalizeTier);
            for (const t of tiers) {
                const min = t.min;
                const max = t.max == null ? Infinity : t.max;
                const from = Math.max(min, 1);
                const to = Math.min(max, qty);
                const count = Math.max(0, to - from + 1);
                if (count > 0) {
                    const pricePerUnit = unit + Math.round(unit * (Number(t.percent) || 0) / 100);
                    subtotal += pricePerUnit * count;
                }
            }
            if (subtotal === 0) {
                const pct = this.getTierPercent(qty) / 100;
                subtotal = (unit + Math.round(unit * pct)) * qty;
            }
            return subtotal;
        },

        // Build breakdown rows per tier for the current qty
        buildBreakdown(unit, qty) {
            const rows = [];
            const tiersRaw = (this.activeTiers || defaultTiers).map(normalizeTier).filter(t => t.min >= 1 && (t.max == null || t.max >= t.min));
            // sort by min asc
            const tiers = tiersRaw.sort((a, b) => a.min - b.min);

            // default percent editable by user; fallback ke persen tier terakhir jika 0
            const lastTier = tiers[tiers.length - 1];
            const fallbackDefault = Number(lastTier ? lastTier.percent : 0) || 0;
            const defaultPercent = Number(this.defaultMarginPercent || fallbackDefault);

            let cursor = 1;
            let totalQty = 0; let totalSubtotal = 0; let defaultUsed = false;

            for (const t of tiers) {
                const max = t.max == null ? Infinity : t.max;
                // gap before this tier
                if (cursor < t.min) {
                    const gapTo = Math.min(t.min - 1, qty);
                    if (gapTo >= cursor) {
                        const count = gapTo - cursor + 1;
                        const unitPrice = unit + Math.round(unit * defaultPercent / 100);
                        const subtotal = unitPrice * count;
                        rows.push({ range: `${cursor}-${gapTo}`, qty: count, marginPct: defaultPercent, unitCost: unit, unitPrice, subtotal, isDefault: true });
                        defaultUsed = true; totalQty += count; totalSubtotal += subtotal; cursor = gapTo + 1;
                    }
                }
                if (cursor > qty) break;
                // coverage of this tier
                const from = Math.max(cursor, t.min);
                const to = Math.min(max, qty);
                if (to >= from) {
                    const count = to - from + 1;
                    const marginPct = Number(t.percent) || 0;
                    const unitPrice = unit + Math.round(unit * marginPct / 100);
                    const subtotal = unitPrice * count;
                    rows.push({ range: t.max == null ? `${from}+` : `${from}-${to}`, qty: count, marginPct, unitCost: unit, unitPrice, subtotal, isDefault: false });
                    totalQty += count; totalSubtotal += subtotal; cursor = to + 1;
                }
                if (cursor > qty) break;
            }

            // tail after last tier
            if (cursor <= qty) {
                const count = qty - cursor + 1;
                const unitPrice = unit + Math.round(unit * defaultPercent / 100);
                const subtotal = unitPrice * count;
                rows.push({ range: `${cursor}-${qty}`, qty: count, marginPct: defaultPercent, unitCost: unit, unitPrice, subtotal, isDefault: true });
                defaultUsed = true; totalQty += count; totalSubtotal += subtotal;
            }

            // if no tiers defined, cover full range with defaultPercent (0 or set by policy)
            if (rows.length === 0) {
                const unitPrice = unit + Math.round(unit * defaultPercent / 100);
                const subtotal = unitPrice * qty;
                rows.push({ range: `1-${qty}`, qty, marginPct: defaultPercent, unitCost: unit, unitPrice, subtotal, isDefault: true });
                defaultUsed = true; totalQty = qty; totalSubtotal = subtotal;
            }

            rows.totalQty = totalQty; rows.totalSubtotal = totalSubtotal;
            return { rows, defaultUsed, defaultPercent };
        },
    };
};

function normalizeTier(t) {
    const min = Math.max(1, parseInt(t.min || 1, 10));
    const max = t.max == null ? null : Math.max(min, parseInt(t.max, 10));
    const percent = Math.max(0, Math.min(100, Number(t.percent) || 0));
    return { min, max, percent };
}

function cloneTier(t) { return { min: t.min, max: t.max, percent: t.percent }; }


