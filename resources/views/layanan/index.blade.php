@extends('layouts.app')

@section('title', 'Data Layanan')

@section('content')
<div class="space-y-6" x-data="{ openCreate:false, openCreateKategori:false }" @close-create-kategori.window="openCreateKategori=false">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Layanan</h1>
            <p class="text-gray-600">Kelola data layanan dan tarif</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('kategori.index') }}" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-tags mr-2"></i>
                Kelola Kategori
            </a>
            <button type="button" @click="openCreate = true" 
               class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Tambah Layanan
            </button>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        if (!Alpine.store('cats')) {
            Alpine.store('cats', {
                list: {!! $kategori->map(fn($k) => ['id' => $k->id, 'name' => $k->nama_kategori])->values()->toJson() !!}
            });
        }
    });
    </script>

    <!-- Modal Create -->
    <x-modal title="Tambah Layanan" show="openCreate" maxWidth="2xl">
        <form method="POST" action="{{ route('layanan.store') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-form-input name="kode" label="Kode" placeholder="Masukkan kode layanan (opsional)" value="{{ old('kode') }}" help="Boleh dikosongkan"></x-form-input>
                <x-form-input name="jenis_pemeriksaan" label="Jenis Pemeriksaan" placeholder="Masukkan jenis pemeriksaan (opsional)" value="{{ old('jenis_pemeriksaan') }}" help="Boleh dikosongkan"></x-form-input>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Layanan</label>
                    <div class="flex items-end gap-3" x-data='{
                            q: "",
                            open: false,
                            selectedId: "{{ old('kategori_id') }}",
                            get categories(){ return Alpine.store("cats").list },
                            set categories(v){ Alpine.store("cats").list = v }
                        }'>
                        <input type="hidden" name="kategori_id" :value="selectedId">
                        <div class="relative flex-1">
                            <input type="text" x-model="q" @focus="open=true" @input="open=true" placeholder="Cari kategori..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <div x-cloak x-show="open" @click.outside="open=false" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto">
                                <ul class="py-1 text-sm">
                                    <template x-for="item in categories" :key="item.id">
                                        <li x-show="item.name.toLowerCase().includes(q.toLowerCase())" @click="selectedId = item.id; q = item.name; open = false" class="px-3 py-2 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                            <span x-text="item.name"></span>
                                            <template x-if="selectedId == item.id"><i class="fas fa-check text-green-600"></i></template>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        <button type="button" @click="openCreateKategori = true" class="inline-flex items-center h-10 px-3 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50" x-ref="btnTambahKategori">
                            <i class="fas fa-plus mr-2"></i>Tambah Kategori
                        </button>
                    </div>
                    @error('kategori_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <x-form-input name="unit_cost" label="Unit Cost" type="number" step="0.01" min="0" placeholder="0.00" required="true" value="{{ old('unit_cost') }}" help="Biaya dasar layanan"></x-form-input>
                <x-form-input name="margin" label="Margin (%)" type="number" step="0.01" min="0" max="100" placeholder="0.00" required="true" value="{{ old('margin') }}" help="Persentase margin keuntungan"></x-form-input>
                <div class="md:col-span-2">
                    <x-form-input name="tarif" label="Tarif" type="number" step="0.01" min="0" placeholder="0.00" required="true" value="{{ old('tarif') }}" help="Tarif akhir yang akan dikenakan"></x-form-input>
                </div>
            </div>
            <x-form-input name="deskripsi" label="Deskripsi" type="textarea" rows="4" placeholder="Masukkan deskripsi layanan (opsional)" value="{{ old('deskripsi') }}"></x-form-input>
            <x-form-input name="is_active" label="Status" type="checkbox" placeholder="Aktif" value="{{ old('is_active', true) }}"></x-form-input>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" @click="openCreate=false" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md">Batal</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Simpan</button>
            </div>
        </form>
        <script>
        document.addEventListener('alpine:init', () => {
            // kalkulasi tarif otomatis untuk modal create
            document.addEventListener('input', function(e){
                if(['unit_cost','margin'].includes(e.target.id)){
                    const uc = parseFloat(document.getElementById('unit_cost')?.value)||0;
                    const mg = parseFloat(document.getElementById('margin')?.value)||0;
                    const tf = document.getElementById('tarif');
                    if(tf){ tf.value = (uc*(1+(mg/100))).toFixed(2); }
                }
            });
        });
        </script>
    </x-modal>
    
    <!-- Modal Tambah Kategori (inline) -->
    <x-modal title="Tambah Kategori" show="openCreateKategori" maxWidth="xl" align="center">
        <form x-data="kategoriForm()" @submit.prevent="submitKategori($event)" class="space-y-6">
            @csrf
            <x-form-input name="nama_kategori" label="Nama Kategori" placeholder="Masukkan nama kategori" required="true"></x-form-input>
            <x-form-input name="deskripsi" label="Deskripsi" type="textarea" rows="4" placeholder="Masukkan deskripsi kategori (opsional)"></x-form-input>
            <x-form-input name="is_active" label="Status" type="checkbox" placeholder="Aktif" value="1"></x-form-input>
            <template x-if="error">
                <div class="text-sm text-red-600" x-text="error"></div>
            </template>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" @click="openCreateKategori=false" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md" :disabled="loading">Batal</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md" :class="{ 'opacity-70 cursor-not-allowed': loading }" :disabled="loading">Simpan</button>
            </div>
        </form>
    </x-modal>

    <script>
    function kategoriForm(){
        return {
            loading: false,
            error: null,
            async submitKategori(ev){
                try{
                    this.loading = true; this.error = null;
                    const res = await fetch('{{ route('kategori.store') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: new FormData(ev.target)
                    });
                    if(!res.ok){ throw await res.json(); }
                    const json = await res.json();
                    const newId = json.data.id;
                    const newName = json.data.nama_kategori;
                    const store = Alpine.store('cats');
                    if (store) {
                        const exists = store.list.some(c => String(c.id) === String(newId));
                        if (!exists) { store.list.push({ id: newId, name: newName }); }
                    }
                    const nearestHidden = document.querySelector("input[type='hidden'][name='kategori_id']");
                    if (nearestHidden) {
                        const root = nearestHidden.closest('[x-data]');
                        if (root && root.__x) {
                            root.__x.$data.selectedId = newId;
                            root.__x.$data.q = newName;
                        }
                    }
                    window.dispatchEvent(new CustomEvent('close-create-kategori'));
                    ev.target.reset();
                } catch(e){
                    this.error = (e && e.errors) ? Object.values(e.errors).flat().join('\n') : 'Gagal menyimpan.';
                } finally {
                    this.loading = false;
                }
            }
        }
    }
    </script>
    <!-- Filter Bar -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari kode, jenis pemeriksaan, kategori, deskripsi, unit cost, margin, atau tarif..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500">Mencari di semua field: kode, jenis pemeriksaan, kategori, deskripsi, unit cost, margin, dan tarif</p>
                    </div>
                    <div>
                        <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="kategori_id" id="kategori_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Semua Kategori</option>
                            @foreach($kategori as $kat)
                            <option value="{{ $kat->id }}" {{ request('kategori_id') == $kat->id ? 'selected' : '' }}>{{ $kat->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                    </div>
                    <div>
                        <label for="tarif_range" class="block text-sm font-medium text-gray-700 mb-1">Range Tarif</label>
                        <select name="tarif_range" id="tarif_range" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Semua Range</option>
                            <option value="0-50000" {{ request('tarif_range') == '0-50000' ? 'selected' : '' }}>Rp 0 - 50.000</option>
                            <option value="50000-100000" {{ request('tarif_range') == '50000-100000' ? 'selected' : '' }}>Rp 50.000 - 100.000</option>
                            <option value="100000-500000" {{ request('tarif_range') == '100000-500000' ? 'selected' : '' }}>Rp 100.000 - 500.000</option>
                            <option value="500000-1000000" {{ request('tarif_range') == '500000-1000000' ? 'selected' : '' }}>Rp 500.000 - 1.000.000</option>
                            <option value="1000000+" {{ request('tarif_range') == '1000000+' ? 'selected' : '' }}>Rp 1.000.000+</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        @if(request()->hasAny(['search','kategori_id','status','tarif_range']))
                        <a href="{{ request()->url() }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                        @endif
                    </div>
                    <div class="ml-auto flex gap-3">
                        @if(auth()->user()->hasPermission('upload_layanan_excel'))
                        <a href="{{ route('layanan.upload.form') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-upload mr-2"></i>Upload Excel
                        </a>
                        @endif
                        <a href="{{ route('layanan.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table using responsive-table component -->
        <x-responsive-table :headers="['No','Kode','Jenis Pemeriksaan','Kategori','Unit Cost','Margin (%)','Unit Cost * Margin','Tarif','Aksi']" minWidth="1200px">
            @forelse($layanan as $index => $item)
            <tr class="hover:bg-gray-50" x-data="{ openShow:false, openEdit:false }">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ ($layanan->firstItem() ?? 0) + $index }}</td>
                
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $item->kode ?: '-' }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $item->jenis_pemeriksaan ?: '-' }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $item->kategori->nama_kategori }}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $item->margin }}%</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($item->unit_cost * ($item->margin / 100), 0, ',', '.') }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Rp {{ number_format($item->tarif, 0, ',', '.') }}</td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2">
                        <button type="button" @click="openShow = true" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-eye mr-2"></i>Lihat</button>
                        <button type="button" @click="openEdit = true" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-yellow-600 text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors"><i class="fas fa-edit mr-2"></i>Edit</button>
                        <form method="POST" action="{{ route('layanan.destroy', $item) }}" class="inline">@csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?')" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"><i class="fas fa-trash mr-2"></i>Hapus</button>
                        </form>
                    </div>

                    <!-- Modal Show -->
                    <x-modal :title="'Detail Layanan: ' . ($item->jenis_pemeriksaan ?: $item->kategori->nama_kategori)" show="openShow" maxWidth="2xl">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-500">Kode</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->kode ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Jenis Pemeriksaan</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->jenis_pemeriksaan ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Kategori</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->kategori->nama_kategori }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Status</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Unit Cost</div>
                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($item->unit_cost,0,',','.') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Margin</div>
                                <div class="text-sm font-medium text-gray-900">{{ $item->margin }}%</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Tarif</div>
                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($item->tarif,0,',','.') }}</div>
                            </div>
                        </div>
                        @if($item->deskripsi)
                        <div class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $item->deskripsi }}</div>
                        @endif
                    </x-modal>

                    <!-- Modal Edit -->
                    <x-modal :title="'Edit Layanan'" show="openEdit" maxWidth="2xl" align="center">
                        <form method="POST" action="{{ route('layanan.update', $item) }}" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-form-input name="kode" label="Kode" value="{{ $item->kode }}" help="Boleh dikosongkan"></x-form-input>
                                <x-form-input name="jenis_pemeriksaan" label="Jenis Pemeriksaan" value="{{ $item->jenis_pemeriksaan }}" help="Boleh dikosongkan"></x-form-input>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Layanan</label>
                                    <div class="flex items-end gap-3" x-data='{
                                            q: "{{ e($item->kategori->nama_kategori) }}",
                                            open: false,
                                            selectedId: "{{ $item->kategori_id }}",
                                            get categories(){ return Alpine.store("cats").list },
                                            set categories(v){ Alpine.store("cats").list = v }
                                        }'>
                                        <input type="hidden" name="kategori_id" :value="selectedId">
                                        <div class="relative flex-1">
                                            <input type="text" x-model="q" @focus="open=true" @input="open=true" placeholder="Cari kategori..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <div x-cloak x-show="open" @click.outside="open=false" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto">
                                                <ul class="py-1 text-sm">
                                                    <template x-for="item in categories" :key="item.id">
                                                        <li x-show="item.name.toLowerCase().includes(q.toLowerCase())" @click="selectedId = item.id; q = item.name; open = false" class="px-3 py-2 hover:bg-gray-100 cursor-pointer flex items-center justify-between">
                                                            <span x-text="item.name"></span>
                                                            <template x-if="selectedId == item.id"><i class="fas fa-check text-green-600"></i></template>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </div>
                                        <button type="button" @click="openCreateKategori = true" class="inline-flex items-center h-10 px-3 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="fas fa-plus mr-2"></i>Tambah Layanan
                                        </button>
                                    </div>
                                    @error('kategori_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <x-form-input name="unit_cost" label="Unit Cost" type="number" step="0.01" min="0" required="true" value="{{ $item->unit_cost }}" help="Biaya dasar layanan"></x-form-input>
                                <x-form-input name="margin" label="Margin (%)" type="number" step="0.01" min="0" max="100" required="true" value="{{ $item->margin }}" help="Persentase margin keuntungan"></x-form-input>
                                <div class="md:col-span-2">
                                    <x-form-input name="tarif" label="Tarif" type="number" step="0.01" min="0" required="true" value="{{ $item->tarif }}" help="Tarif akhir yang akan dikenakan"></x-form-input>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <x-form-input name="deskripsi" label="Deskripsi" type="textarea" rows="4" value="{{ $item->deskripsi }}"></x-form-input>
                            </div>
                            <x-form-input name="is_active" label="Status" type="checkbox" placeholder="Aktif" value="{{ $item->is_active }}"></x-form-input>
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                <button type="button" @click="openEdit=false" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-md">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md">Update</button>
                            </div>
                        </form>
                    </x-modal>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                    <div class="flex flex-col items-center"><i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i><p class="text-lg font-medium">Tidak ada data</p><p class="text-sm">Belum ada data yang tersedia</p></div>
                </td>
            </tr>
            @endforelse
            @if($layanan->count())
            <tr class="bg-gray-50">
                <td class="px-4 py-3 text-sm font-semibold text-gray-900" colspan="7">Total Tarif (halaman ini)</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-extrabold text-gray-900">Rp {{ number_format($layanan->sum('tarif'), 0, ',', '.') }}</td>
                <td class="px-4 py-3"></td>
            </tr>
            @endif
        </x-responsive-table>

        <!-- Pagination -->
        @if($layanan->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-600">Menampilkan {{ $layanan->firstItem() }}-{{ $layanan->lastItem() }} dari {{ $layanan->total() }} data</div>
                <div class="overflow-x-auto">{{ $layanan->links() }}</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
