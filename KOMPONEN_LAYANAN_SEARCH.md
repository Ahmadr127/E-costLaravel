# 📦 Komponen Layanan Search - Dokumentasi

## 📋 Overview

Komponen **Layanan Search** adalah searchable dropdown yang reusable untuk mencari dan memilih layanan di seluruh halaman simulasi. Komponen ini menggunakan Alpine.js untuk interaktivitas dan Blade untuk templating.

---

## 🎯 Fitur

- ✅ **Searchable Dropdown** - Pencarian real-time dengan debouncing
- ✅ **Keyboard Navigation** - Arrow keys, Enter, Tab, Escape
- ✅ **Loading State** - Indicator saat mencari
- ✅ **Responsive Design** - Menyesuaikan berbagai ukuran layar
- ✅ **Custom Events** - Dispatch event untuk komunikasi dengan parent
- ✅ **Reusable** - Bisa digunakan di berbagai halaman
- ✅ **Customizable** - Props untuk label dan placeholder

---

## 📁 Lokasi File

```
resources/views/components/layanan-search.blade.php
```

---

## 🚀 Cara Penggunaan

### **Basic Usage**

```blade
<div @layanan-selected.window="addLayananToSimulation($event.detail)">
    <x-layanan-search />
</div>
```

### **With Custom Props**

```blade
<div @layanan-selected.window="handleLayananSelected($event.detail)">
    <x-layanan-search 
        label="Pilih Layanan"
        placeholder="Cari berdasarkan kode atau nama..."
        :showLabel="true"
    />
</div>
```

### **Without Label**

```blade
<div @layanan-selected.window="addLayananToSimulation($event.detail)">
    <x-layanan-search 
        :showLabel="false"
        placeholder="Cari layanan..."
    />
</div>
```

---

## 🔧 Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | String | `'Cari Layanan'` | Label yang ditampilkan di atas search box |
| `placeholder` | String | `'Masukkan kode atau jenis pemeriksaan...'` | Placeholder text di search box |
| `showLabel` | Boolean | `true` | Menampilkan/menyembunyikan label |

---

## 📡 Events

### **Event yang Di-dispatch**

#### `layanan-selected`

Event ini di-dispatch ketika user memilih layanan dari dropdown.

**Event Detail:**
```javascript
{
    id: 123,
    kode: "LAB001",
    jenis_pemeriksaan: "Pemeriksaan Darah Lengkap",
    tarif_master: 150000,
    unit_cost: 120000,
    kategori_nama: "Laboratorium",
    kategori_id: 5
}
```

**Cara Menangkap Event:**

```blade
<!-- Di parent component -->
<div @layanan-selected.window="handleLayananSelected($event.detail)">
    <x-layanan-search />
</div>
```

```javascript
// Di Alpine.js component
function simulationApp() {
    return {
        handleLayananSelected(layanan) {
            console.log('Layanan dipilih:', layanan);
            // Tambahkan layanan ke simulasi
            this.addLayananToSimulation(layanan);
        }
    }
}
```

---

## ⌨️ Keyboard Navigation

| Key | Action |
|-----|--------|
| `Arrow Down` | Pindah ke item berikutnya |
| `Arrow Up` | Pindah ke item sebelumnya |
| `Enter` | Pilih item yang di-highlight |
| `Tab` | Pindah ke item berikutnya |
| `Shift + Tab` | Pindah ke item sebelumnya |
| `Escape` | Tutup dropdown |

---

## 🎨 Styling

Komponen menggunakan Tailwind CSS classes. Anda bisa customize dengan:

### **Override Classes**

```blade
<x-layanan-search 
    class="custom-class"
/>
```

### **Custom Styling via Props** (Future Enhancement)

Untuk saat ini, styling di-handle di dalam komponen. Jika perlu custom styling, edit file komponen langsung.

---

## 🔍 Cara Kerja Internal

### **1. Search Flow**

```
User mengetik → Debounce 300ms → Fetch API → Update results → Show dropdown
```

### **2. Component State**

```javascript
{
    searchQuery: '',        // Query yang diketik user
    searchResults: [],      // Hasil pencarian dari API
    isSearching: false,     // Loading state
    showDropdown: false,    // Visibility dropdown
    selectedSearchIndex: -1 // Index item yang di-highlight
}
```

### **3. API Integration**

Komponen menggunakan endpoint:
```
GET /simulation/search?search={query}
```

**Response Format:**
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "kode": "LAB001",
            "jenis_pemeriksaan": "Pemeriksaan Darah Lengkap",
            "tarif_master": 150000,
            "unit_cost": 120000,
            "kategori_nama": "Laboratorium",
            "kategori_id": 5
        }
    ]
}
```

---

## 📝 Contoh Implementasi

### **Simulasi Regular** (`simulation/index.blade.php`)

```blade
@section('content')
<div x-data="simulationApp()" class="space-y-4">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <!-- ... header content ... -->
        
        <!-- Search Component -->
        <div @layanan-selected.window="addLayananToSimulation($event.detail)">
            <x-layanan-search 
                label="Cari Layanan"
                placeholder="Masukkan kode atau jenis pemeriksaan..."
            />
        </div>
    </div>
    
    <!-- Results Table -->
    <!-- ... -->
</div>
@endsection
```

### **Simulasi Qty** (`simulation/qty.blade.php`)

```blade
@section('content')
<div x-data="simulationQtyApp()" class="space-y-4">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <!-- ... header content ... -->
        
        <!-- Search Component -->
        <div @layanan-selected.window="addLayananToSimulation($event.detail)">
            <x-layanan-search 
                label="Cari Layanan"
                placeholder="Masukkan kode atau jenis pemeriksaan..."
            />
        </div>
    </div>
    
    <!-- Results Table -->
    <!-- ... -->
</div>
@endsection
```

---

## 🧪 Testing

### **Test 1: Basic Search**
1. Ketik minimal 2 karakter di search box
2. Tunggu 300ms (debounce)
3. Verifikasi dropdown muncul dengan hasil
4. Verifikasi loading indicator muncul saat searching

### **Test 2: Keyboard Navigation**
1. Ketik di search box
2. Tekan Arrow Down → Item pertama di-highlight
3. Tekan Arrow Down lagi → Item kedua di-highlight
4. Tekan Enter → Item terpilih dan event di-dispatch

### **Test 3: Click Selection**
1. Ketik di search box
2. Klik tombol "Pilih" pada salah satu item
3. Verifikasi event `layanan-selected` di-dispatch
4. Verifikasi dropdown tertutup
5. Verifikasi search query ter-reset

### **Test 4: Escape Key**
1. Ketik di search box
2. Dropdown muncul
3. Tekan Escape
4. Verifikasi dropdown tertutup

### **Test 5: Blur Event**
1. Ketik di search box
2. Dropdown muncul
3. Klik di luar search box
4. Verifikasi dropdown tertutup setelah 200ms

---

## 🔧 Troubleshooting

### **Masalah: Dropdown tidak muncul**

**Solusi:**
1. Pastikan `window.SIMULATION_SEARCH_URL` terdefined
2. Cek console untuk error API
3. Verifikasi user memiliki permission `access_simulation` atau `access_simulation_qty`

### **Masalah: Event tidak tertangkap**

**Solusi:**
1. Pastikan menggunakan `.window` modifier: `@layanan-selected.window`
2. Pastikan parent component memiliki method handler
3. Cek console untuk error JavaScript

### **Masalah: Keyboard navigation tidak bekerja**

**Solusi:**
1. Pastikan focus ada di search input
2. Pastikan dropdown dalam keadaan terbuka
3. Cek console untuk error

---

## 🚀 Future Enhancements

### **Planned Features:**

1. **Custom Styling Props**
   ```blade
   <x-layanan-search 
       inputClass="custom-input-class"
       dropdownClass="custom-dropdown-class"
   />
   ```

2. **Multiple Selection Mode**
   ```blade
   <x-layanan-search 
       :multiple="true"
       @layanan-selected-multiple.window="handleMultiple($event.detail)"
   />
   ```

3. **Custom Result Template**
   ```blade
   <x-layanan-search>
       <x-slot name="resultTemplate">
           <!-- Custom template -->
       </x-slot>
   </x-layanan-search>
   ```

4. **Debounce Configuration**
   ```blade
   <x-layanan-search 
       :debounce="500"
   />
   ```

5. **Min Characters Configuration**
   ```blade
   <x-layanan-search 
       :minChars="3"
   />
   ```

---

## 📊 Performance

### **Optimizations Applied:**

1. **Debouncing** - 300ms delay untuk mengurangi API calls
2. **Lazy Loading** - Hanya load data saat dibutuhkan
3. **Event Delegation** - Efficient event handling
4. **Minimal Re-renders** - Alpine.js reactivity optimization

### **Performance Metrics:**

- Search delay: 300ms
- API response time: ~100-500ms (tergantung server)
- Dropdown render time: <50ms
- Memory footprint: Minimal (cleanup on blur)

---

## 🔐 Security

### **Implemented Security Measures:**

1. **CSRF Token** - Included in all API requests
2. **XSS Protection** - Alpine.js auto-escapes content
3. **Input Sanitization** - URL encoding for search query
4. **Authorization** - Backend checks user permissions

---

## 📚 Dependencies

- **Alpine.js** - For reactivity and interactivity
- **Tailwind CSS** - For styling
- **Laravel Blade** - For templating
- **Fetch API** - For AJAX requests

---

## 🤝 Contributing

Jika ingin menambahkan fitur atau memperbaiki bug:

1. Edit file: `resources/views/components/layanan-search.blade.php`
2. Test di semua halaman yang menggunakan komponen
3. Update dokumentasi ini
4. Commit dengan message yang jelas

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Cek dokumentasi ini terlebih dahulu
2. Cek console browser untuk error
3. Hubungi tim development

---

**Dokumentasi dibuat**: 13 Januari 2026, 09:12 WIB  
**Versi**: 1.0.0  
**Author**: Development Team
