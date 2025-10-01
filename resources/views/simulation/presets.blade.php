@extends('layouts.app')

@section('title', 'Master Preset Qty')

@push('scripts')
<script>
    window.SIMULATION_QTY_PRESETS_URL = "{{ route('simulation.qty.presets') }}";
    window.CSRF_TOKEN = "{{ csrf_token() }}";
</script>
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('presetPage', () => ({
    loading: false,
    form: { name: '', simulation_qty: 0, tiers: [], is_default: true },
    presets: [],
    async init() {
      this.loading = true;
      try {
        const res = await fetch(window.SIMULATION_QTY_PRESETS_URL);
        const data = await res.json();
        this.presets = data.data || [];
        const p = this.presets[0];
        if (p) this.form = { name: p.name || '', simulation_qty: (p.simulation_qty ?? 0), tiers: (p.tiers||[]).map(t => ({...t})), is_default: true };
      } finally { this.loading = false; }
    },
    addTier() { this.form.tiers.push({ min: null, max: null, percent: 0 }); },
    removeTier(i) { this.form.tiers.splice(i,1); },
    async save() {
      const payload = {
        name: (this.form.name||'').trim(),
        simulation_qty: Number(this.form.simulation_qty ?? 0),
        is_default: true,
        tiers: (this.form.tiers||[]).filter(t => t.min != null && String(t.min).trim() !== '')
      };
      const method = this.presets.length ? 'PUT' : 'POST';
      const url = this.presets.length ? `${window.SIMULATION_QTY_PRESETS_URL}/${this.presets[0].id}` : window.SIMULATION_QTY_PRESETS_URL;
      const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.CSRF_TOKEN }, body: JSON.stringify(payload) });
      if (!res.ok) { alert('Gagal menyimpan'); return; }
      location.reload();
    }
  }))
});
</script>
@endpush

@section('content')
<div x-data="presetPage()" class="space-y-4">
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <div class="flex items-center justify-between mb-3">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Master Preset Qty</h1>
        <p class="text-sm text-gray-600">Hanya satu preset aktif; digunakan default pada simulasi</p>
      </div>
      <div>
        <a href="{{ route('simulation.qty') }}" class="text-xs text-blue-600 hover:text-blue-700">Kembali ke Simulasi</a>
      </div>
    </div>
    <div class="space-y-3">
      <div>
        <label class="block text-xs text-gray-600 mb-1">Nama Preset</label>
        <input type="text" x-model="form.name" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Qty Simulasi</label>
        <input type="number" min="0" step="1" x-model.number="form.simulation_qty" class="w-40 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
      </div>
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs text-gray-600">Daftar Tier</label>
          <button class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="addTier()">+ Tambah Tier</button>
        </div>
        <div class="space-y-2">
          <template x-for="(t, idx) in form.tiers" :key="idx">
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
        </div>
      </div>
      <div class="flex items-center justify-end">
        <button class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700" @click="save()">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection


