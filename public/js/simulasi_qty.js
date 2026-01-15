window.simulationQtyApp = function simulationQtyApp() {
    let defaultTiers = [];

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
        perPatientPrice: 0,
        sumUnitCost: 0,
        sumTarifMaster: 0,
        // Preset-based qty simulation
        selectedPresetQty: null,
        searchTimeout: null,
        isSearching: false,
        showDropdown: false,
        selectedSearchIndex: -1,
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
        effectiveMarginPercent: 0,
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
            } catch { }
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

        // Helper for displaying relative time in saved simulations list
        formatDateAgo(input) {
            try {
                const date = new Date(input);
                if (isNaN(date.getTime())) return '';
                const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
                if (seconds < 60) return `${seconds}s lalu`;
                const minutes = Math.floor(seconds / 60);
                if (minutes < 60) return `${minutes}m lalu`;
                const hours = Math.floor(minutes / 60);
                if (hours < 24) return `${hours}j lalu`;
                const days = Math.floor(hours / 24);
                if (days < 30) return `${days}h lalu`;
                const months = Math.floor(days / 30);
                if (months < 12) return `${months}bln lalu`;
                const years = Math.floor(months / 12);
                return `${years}thn lalu`;
            } catch { return ''; }
        },

        searchLayanan(query) {
            this.searchQuery = query;
            if ((query || '').length < 2) {
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
                this.selectedSearchIndex = -1;
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
            this.selectedSearchIndex = -1;
            this.updateSimulationTotals();
        },

        getTierPercent(qty) {
            const n = Math.max(0, Math.floor(Number(qty) || 0));
            for (const t of (this.activeTiers || defaultTiers)) {
                if (n >= t.min && n <= t.max) return t.percent;
            }
            const last = (this.activeTiers || defaultTiers).slice(-1)[0];
            return last ? last.percent : 0;
        },

        // NEW ARCHITECTURE: Simulation-level calculation functions
        updateSimulationTotals() {
            // Calculate totals
            this.totalUnitCost = this.simulationResults.reduce((sum, item) =>
                sum + Math.round(Number(item.unit_cost) || 0), 0);
            this.sumTarifMaster = this.simulationResults.reduce((sum, item) =>
                sum + Math.round(Number(item.tarif_master) || 0), 0);

            // Piecewise margin by tiers (as markup) based on total Unit Cost
            const breakdown = this.buildSimulationBreakdown(this.totalUnitCost, this.simulationQuantity);
            this.breakdownRows = breakdown.rows;
            this.defaultTierUsed = breakdown.defaultUsed;
            this.defaultTierPercent = breakdown.defaultPercent;
            this.effectiveMarginPercent = breakdown.effectivePercent;
            // simulation-level displayed margin percent (for info only): use tier percent at the final qty
            this.simulationMarginPercent = this.getTierPercent(this.simulationQuantity);
            // totals from breakdown
            this.grandTotal = breakdown.totalSubtotal;
            this.totalMarginValue = breakdown.totalDiscount;
            this.perPatientPrice = this.simulationQuantity > 0 ? Math.round(this.grandTotal / this.simulationQuantity) : 0;
            // keep sums for save payloads
            this.sumUnitCost = this.totalUnitCost;
        },

        // PRESET-BASED QTY SIMULATION: Apply preset qty when preset changes
        applyPresetQty() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (preset && preset.simulation_qty != null) {
                this.simulationQuantity = Math.max(0, Math.floor(Number(preset.simulation_qty) || 0));
                this.selectedPresetQty = this.simulationQuantity;
                this.updateSimulationTotals();
            }
        },

        onSimulationQtyChange(value) {
            this.simulationQuantity = Math.max(0, Math.floor(Number(value) || 0));
            this.updateSimulationTotals();
        },

        onSimulationMarginChange(value) {
            // kept for backward compatibility; margin is driven by tiers as discount now
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

        // NEW: Allow editing Tarif Master, lock Unit Cost
        onTarifMasterInput(item, evt) {
            const raw = evt.target.value;
            const val = this.unformatNumberString(raw);
            item.tarif_master = Math.round(Math.max(0, val));
            this.updateSimulationTotals();
            evt.target.value = this.formatNumber(item.tarif_master);
        },

        onTarifMasterBlur(item, evt) {
            evt.target.value = this.formatNumber(item.tarif_master);
        },

        updateSimulationBreakdown() {
            if (this.simulationResults.length === 0) {
                this.breakdownRows = [];
                this.defaultTierUsed = false;
                this.defaultTierPercent = 0;
                return;
            }
            // Build breakdown for simulation-level calculation using total Unit Cost as base
            const breakdown = this.buildSimulationBreakdown(this.totalUnitCost, this.simulationQuantity);
            this.breakdownRows = breakdown.rows;
            this.defaultTierUsed = breakdown.defaultUsed;
            this.defaultTierPercent = breakdown.defaultPercent;
            this.effectiveMarginPercent = breakdown.effectivePercent;
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
            this.activeSimulationId = '';
            this.saveName = '';
            this.isEditingExisting = false;

            // Apply preset qty
            this.applyPresetQty();
        },

        // Notifications (copy from base)
        showNotification(message, type = 'info') {
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
            setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 3000);
        },

        // Save, load, delete adapted with quantity
        promptSaveSimulation() {
            if (this.simulationResults.length === 0) { this.showNotification('Tidak ada data untuk disimpan', 'warning'); return; }
            // Reset nama jika bukan editing simulasi yang ada
            if (!this.activeSimulationId) {
                this.saveName = '';
            }
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
            } catch { }
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
                this.simulationQuantity = (sim.simulation_quantity ?? 0);
                this.simulationMarginPercent = (sim.simulation_margin_percent ?? 0);
                this.totalUnitCost = sim.total_unit_cost || 0;
                this.totalMarginValue = sim.total_margin_value || 0;

                this.simulationResults = items.map(item => ({
                    id: item.layanan_id,
                    layanan_id: item.layanan_id,
                    kode: item.kode,
                    jenis_pemeriksaan: item.jenis_pemeriksaan,
                    tarif_master: item.tarif_master,
                    unit_cost: item.unit_cost,
                    kategori_id: item.kategori_id || null,
                    kategori_nama: item.kategori_nama || '',
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
            const formatRupiah = (num) => {
                const n = Math.round(Number(num) || 0);
                return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n);
            };
            // Headers sesuai tampilan di halaman: No, Kode, Jenis Pemeriksaan, Kategori
            const headers = ['No', 'Kode', 'Jenis Pemeriksaan', 'Kategori'];
            const rows = this.simulationResults.map((item, index) => [
                index + 1,
                item.kode,
                item.jenis_pemeriksaan,
                item.kategori_nama || '-'
            ]);
            const csvLines = [];
            csvLines.push(headers.join(','));
            rows.forEach(r => csvLines.push(r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')));
            // Tambahkan baris kosong sebagai pemisah
            csvLines.push('');
            // Tambahkan informasi Qty, Per Pasien, dan Grand Total
            csvLines.push(`"Qty:","${this.simulationQuantity}","",""`);
            csvLines.push(`"Per pasien:","${formatRupiah(this.perPatientPrice)}","",""`);
            csvLines.push(`"Grand Total:","${formatRupiah(this.grandTotal)}","",""`);
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
                // still use its qty if exists, otherwise leave as 1
                this.applyPresetQty();
            } else {
                // no preset: ensure zero discount by default
                this.activeTiers = [];
                this.simulationQuantity = 1;
                this.simulationMarginPercent = 0;
                this.updateSimulationTotals();
            }
        },

        onPresetChange() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.activeTiers = (preset.tiers || []).map(normalizeTier);
            // Apply preset qty (allow 0)
            this.applyPresetQty();
        },

        openTierEditor() {
            const preset = this.tierPresets.find(p => String(p.id) === String(this.activePresetId));
            if (!preset) return;
            this.tierForm = {
                id: preset.id,
                name: preset.name,
                simulation_qty: (preset.simulation_qty ?? 0),
                is_default: !!preset.is_default,
                tiers: (preset.tiers || []).map(cloneTier)
            };
            this.showTierModal = true;
        },

        openTierCreator() {
            this.tierForm = {
                id: null,
                name: '',
                simulation_qty: 0,
                is_default: false,
                tiers: []
            };
            this.showTierModal = true;
        },

        closeTierModal() { this.showTierModal = false; },

        addTier() { this.tierForm.tiers.push({ min: null, max: null, percent: 0 }); },
        removeTier(idx) { this.tierForm.tiers.splice(idx, 1); },

        async savePreset() {
            const cleanedTiers = (this.tierForm.tiers || []).filter(t => t.min != null && String(t.min).trim() !== '').map(normalizeTier);
            const payload = {
                name: (this.tierForm.name || '').trim(),
                simulation_qty: (this.tierForm.simulation_qty ?? 0),
                is_default: !!this.tierForm.is_default,
                tiers: cleanedTiers
            };
            if (!payload.name || payload.tiers.length === 0) { this.showNotification('Nama dan tier wajib diisi', 'warning'); return; }
            if (payload.simulation_qty < 0) { this.showNotification('Qty simulasi tidak boleh negatif', 'warning'); return; }
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

        // NEW ARCHITECTURE: Build breakdown for a single applicable tier at current qty
        buildSimulationBreakdown(totalBaseUnitCost, simulationQty) {
            const rows = [];
            const tiersRaw = (this.activeTiers || defaultTiers)
                .map(normalizeTier)
                .filter(t => t.min != null && t.min >= 1 && (t.max == null || t.max >= t.min))
                .sort((a, b) => a.min - b.min);

            // Determine effective percent based on current qty
            let matchedTier = null;
            for (const t of tiersRaw) {
                const max = t.max == null ? Infinity : t.max;
                if (simulationQty >= t.min && simulationQty <= max) { matchedTier = t; break; }
            }

            const lastTier = tiersRaw[tiersRaw.length - 1];
            const fallbackDefault = Number(lastTier ? lastTier.percent : 0) || 0;
            const defaultPercent = Number(this.defaultMarginPercent || fallbackDefault);
            const effectivePercent = matchedTier ? (Number(matchedTier.percent) || 0) : defaultPercent;

            const marginValuePerUnit = Math.round(totalBaseUnitCost * effectivePercent / 100);
            const unitPrice = Math.max(0, totalBaseUnitCost + marginValuePerUnit);
            const subtotal = unitPrice * simulationQty;

            const rangeLabel = matchedTier
                ? (matchedTier.max == null ? `${matchedTier.min}+` : `${matchedTier.min}-${matchedTier.max}`)
                : `default`;

            rows.push({
                range: rangeLabel,
                qty: simulationQty,
                marginPct: effectivePercent,
                unitCost: totalBaseUnitCost,
                unitPrice: unitPrice,
                subtotal,
                isDefault: !matchedTier
            });

            rows.totalQty = simulationQty;
            rows.totalSubtotal = subtotal;
            rows.totalDiscount = (unitPrice - totalBaseUnitCost) * simulationQty;
            return { rows, defaultUsed: !matchedTier, defaultPercent, effectivePercent, unitPrice, totalSubtotal: subtotal, totalDiscount: rows.totalDiscount };
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


