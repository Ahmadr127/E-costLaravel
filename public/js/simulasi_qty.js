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
        // Simulation-level qty and margin (NEW ARCHITECTURE)
        simulationQuantity: 1,
        simulationMarginPercent: 0,
        totalUnitCost: 0,
        totalMarginValue: 0,
        grandTotal: 0,
        sumUnitCost: 0,
        sumTarifMaster: 0,
        // Preset-based qty simulation
        selectedPresetQty: null,
        searchTimeout: null,
        isSearching: false,
        showDropdown: false,
        // presets & tiers (qty only)
        tierPresets: [],
        activePresetId: '',
        activeTiers: defaultTiers.slice(),
        showTierModal: false,
        tierForm: { id: null, name: '', simulation_qty: 1, is_default: false, tiers: defaultTiers.slice() },
        // editable default margin percent for uncovered ranges
        defaultMarginPercent: 0,
        // breakdown data for simulation-level calculation
        breakdownRows: [],
        defaultTierUsed: false,
        defaultTierPercent: 0,
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
                this.updateSimulationTotals();
                this.searchResults = [];
                this.searchQuery = '';
                this.showDropdown = false;
                return;
            }

            // NEW ARCHITECTURE: Services are added without individual qty/margin
            const item = {
                ...layanan,
                id: layanan.id,
                layanan_id: layanan.id,
                unit_cost: Math.round(Number(layanan.unit_cost) || 0),
                tarif_master: Math.round(Number(layanan.tarif_master) || 0),
            };
            
            this.simulationResults.push(item);

            this.searchResults = [];
            this.searchQuery = '';
            this.showDropdown = false;
            this.updateSimulationTotals();
        },

        getTierPercent(qty) {
            const n = Math.max(1, Math.floor(Number(qty) || 1));
            for (const t of (this.activeTiers || defaultTiers)) {
                if (n >= t.min && n <= t.max) return t.percent;
            }
            const last = (this.activeTiers || defaultTiers).slice(-1)[0];
            return last ? last.percent : 0;
        },

        // NEW ARCHITECTURE: Simulation-level calculation functions
        updateSimulationTotals() {
            // Calculate total unit cost from all services
            this.totalUnitCost = this.simulationResults.reduce((sum, item) => 
                sum + Math.round(Number(item.unit_cost) || 0), 0);
            
            // Calculate sum of tarif master
            this.sumTarifMaster = this.simulationResults.reduce((sum, item) => 
                sum + Math.round(Number(item.tarif_master) || 0), 0);
            
            // Calculate simulation margin based on tier preset
            this.simulationMarginPercent = this.getTierPercent(this.simulationQuantity);
            
            // Calculate total margin value
            this.totalMarginValue = Math.round(this.totalUnitCost * this.simulationMarginPercent / 100);
            
            // Calculate grand total: (total unit cost + margin) * simulation quantity
            this.grandTotal = (this.totalUnitCost + this.totalMarginValue) * this.simulationQuantity;
            
            // Update breakdown for simulation-level calculation
            this.updateSimulationBreakdown();
        },

        // PRESET-BASED QTY SIMULATION: Apply preset qty when preset changes
        applyPresetQty() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (preset && preset.simulation_qty) {
                this.simulationQuantity = preset.simulation_qty;
                this.selectedPresetQty = preset.simulation_qty;
                this.updateSimulationTotals();
            }
        },

        onSimulationQtyChange(value) {
            this.simulationQuantity = Math.max(1, Math.floor(Number(value) || 1));
            this.updateSimulationTotals();
        },

        onSimulationMarginChange(value) {
            this.simulationMarginPercent = Math.max(0, Math.min(100, Number(value) || 0));
            this.updateSimulationTotals();
        },

        // NEW ARCHITECTURE: Individual service unit cost editing
        onUnitCostInput(item, evt) {
            const raw = evt.target.value;
            const val = this.unformatNumberString(raw);
            item.unit_cost = Math.round(Math.max(0, val));
            this.updateSimulationTotals();
            evt.target.value = this.formatNumber(item.unit_cost);
        },

        onUnitCostBlur(item, evt) {
            evt.target.value = this.formatNumber(item.unit_cost);
        },

        updateSimulationBreakdown() {
            if (this.simulationResults.length === 0) {
                this.breakdownRows = [];
                this.defaultTierUsed = false;
                this.defaultTierPercent = 0;
                return;
            }
            
            // Build breakdown for simulation-level calculation
            const breakdown = this.buildSimulationBreakdown(this.totalUnitCost, this.simulationQuantity);
                this.breakdownRows = breakdown.rows;
                this.defaultTierUsed = breakdown.defaultUsed;
                this.defaultTierPercent = breakdown.defaultPercent;
        },

        toggleBreakdownVisibility() {
            this.showBreakdown = !this.showBreakdown;
            
            // If showing breakdown, ensure we have data to display
            if (this.showBreakdown && this.simulationResults.length > 0) {
                // Always update breakdown to ensure fresh data is displayed
                this.updateSimulationBreakdown();
            }
        },

        // NEW ARCHITECTURE: Dynamic margin is now applied at simulation level automatically

        removeFromSimulation(index) {
            this.simulationResults.splice(index, 1);
            this.updateSimulationTotals();
        },

        resetSimulation() {
            this.simulationResults = [];
            this.simulationQuantity = 1;
            this.simulationMarginPercent = 0;
            this.totalUnitCost = 0;
            this.totalMarginValue = 0;
            this.grandTotal = 0;
            this.selectedPresetQty = null;
            this.breakdownRows = [];
            this.defaultTierUsed = false;
            this.showBreakdown = false;
            
            // Apply preset qty
            this.applyPresetQty();
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
                if (items.length === 0) { this.simulationResults = []; this.updateSimulationTotals(); return; }
                
                // set preset and default margin from saved simulation
                if (sim.tier_preset_id != null) {
                    this.activePresetId = sim.tier_preset_id;
                    this.onPresetChange();
                }
                if (typeof sim.default_margin_percent !== 'undefined' && sim.default_margin_percent !== null) {
                    this.defaultMarginPercent = Number(sim.default_margin_percent) || 0;
                }
                
                // NEW ARCHITECTURE: Load simulation-level data
                this.simulationQuantity = sim.simulation_quantity || 1;
                this.simulationMarginPercent = sim.simulation_margin_percent || 0;
                this.totalUnitCost = sim.total_unit_cost || 0;
                this.totalMarginValue = sim.total_margin_value || 0;
                
                this.simulationResults = items.map(item => ({
                    id: item.layanan_id,
                    layanan_id: item.layanan_id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: item.tarif_master,
                    unit_cost: item.unit_cost,
                }));
                
                this.updateSimulationTotals();
                this.activeSimulationId = sim.id;
                this.saveName = sim.name || '';
                this.isEditingExisting = true;
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
                simulation_quantity: this.simulationQuantity,
                simulation_margin_percent: this.simulationMarginPercent,
                total_unit_cost: this.totalUnitCost,
                total_margin_value: this.totalMarginValue,
                sum_unit_cost: this.sumUnitCost,
                sum_tarif_master: this.sumTarifMaster,
                grand_total: this.grandTotal,
                items: this.simulationResults.map(item => ({
                    layanan_id: item.layanan_id || item.id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: Math.round(Number(item.tarif_master) || 0),
                    unit_cost: Math.round(Number(item.unit_cost) || 0),
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

        // NEW ARCHITECTURE: Default margin changed by user in UI
        onDefaultMarginChange() { 
            this.updateSimulationTotals();
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
                
                // Apply preset qty
                this.applyPresetQty();
            }
        },

        onPresetChange() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.activeTiers = (preset.tiers || []).map(normalizeTier);
            
            // Apply preset qty
            this.applyPresetQty();
        },

        openTierEditor() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.tierForm = { 
                id: preset.id, 
                name: preset.name, 
                simulation_qty: preset.simulation_qty || 1,
                is_default: !!preset.is_default, 
                tiers: (preset.tiers || []).map(cloneTier) 
            };
            this.showTierModal = true;
        },

        openTierCreator() {
            this.tierForm = { 
                id: null, 
                name: '', 
                simulation_qty: 1,
                is_default: false, 
                tiers: defaultTiers.slice().map(cloneTier) 
            };
            this.showTierModal = true;
        },

        closeTierModal() { this.showTierModal = false; },

        addTier() { this.tierForm.tiers.push({ min: 1, max: null, percent: 0 }); },
        removeTier(idx) { this.tierForm.tiers.splice(idx, 1); },

        async savePreset() {
            const payload = { 
                name: (this.tierForm.name || '').trim(), 
                simulation_qty: this.tierForm.simulation_qty || 1,
                is_default: !!this.tierForm.is_default, 
                tiers: (this.tierForm.tiers || []).map(normalizeTier) 
            };
            if (!payload.name || payload.tiers.length === 0) { this.showNotification('Nama dan tier wajib diisi', 'warning'); return; }
            if (!payload.simulation_qty || payload.simulation_qty < 1) { this.showNotification('Qty simulasi wajib diisi minimal 1', 'warning'); return; }
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

        // NEW ARCHITECTURE: recalcAll is replaced by updateSimulationTotals

        // NEW ARCHITECTURE: computeSubtotal is no longer needed for individual services

        // NEW ARCHITECTURE: Build breakdown for simulation-level calculation
        buildSimulationBreakdown(totalUnitCost, simulationQty) {
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
                    const gapTo = Math.min(t.min - 1, simulationQty);
                    if (gapTo >= cursor) {
                        const count = gapTo - cursor + 1;
                        const marginValue = Math.round(totalUnitCost * defaultPercent / 100);
                        const subtotal = (totalUnitCost + marginValue) * count;
                        rows.push({ 
                            range: `${cursor}-${gapTo}`, 
                            qty: count, 
                            marginPct: defaultPercent, 
                            unitCost: totalUnitCost, 
                            unitPrice: totalUnitCost + marginValue, 
                            subtotal, 
                            isDefault: true 
                        });
                        defaultUsed = true; totalQty += count; totalSubtotal += subtotal; cursor = gapTo + 1;
                    }
                }
                if (cursor > simulationQty) break;
                // coverage of this tier
                const from = Math.max(cursor, t.min);
                const to = Math.min(max, simulationQty);
                if (to >= from) {
                    const count = to - from + 1;
                    const marginPct = Number(t.percent) || 0;
                    const marginValue = Math.round(totalUnitCost * marginPct / 100);
                    const subtotal = (totalUnitCost + marginValue) * count;
                    rows.push({ 
                        range: t.max == null ? `${from}+` : `${from}-${to}`, 
                        qty: count, 
                        marginPct, 
                        unitCost: totalUnitCost, 
                        unitPrice: totalUnitCost + marginValue, 
                        subtotal, 
                        isDefault: false 
                    });
                    totalQty += count; totalSubtotal += subtotal; cursor = to + 1;
                }
                if (cursor > simulationQty) break;
            }

            // tail after last tier
            if (cursor <= simulationQty) {
                const count = simulationQty - cursor + 1;
                const marginValue = Math.round(totalUnitCost * defaultPercent / 100);
                const subtotal = (totalUnitCost + marginValue) * count;
                rows.push({ 
                    range: `${cursor}-${simulationQty}`, 
                    qty: count, 
                    marginPct: defaultPercent, 
                    unitCost: totalUnitCost, 
                    unitPrice: totalUnitCost + marginValue, 
                    subtotal, 
                    isDefault: true 
                });
                defaultUsed = true; totalQty += count; totalSubtotal += subtotal;
            }

            // if no tiers defined, cover full range with defaultPercent (0 or set by policy)
            if (rows.length === 0) {
                const marginValue = Math.round(totalUnitCost * defaultPercent / 100);
                const subtotal = (totalUnitCost + marginValue) * simulationQty;
                rows.push({ 
                    range: `1-${simulationQty}`, 
                    qty: simulationQty, 
                    marginPct: defaultPercent, 
                    unitCost: totalUnitCost, 
                    unitPrice: totalUnitCost + marginValue, 
                    subtotal, 
                    isDefault: true 
                });
                defaultUsed = true; totalQty = simulationQty; totalSubtotal = subtotal;
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


