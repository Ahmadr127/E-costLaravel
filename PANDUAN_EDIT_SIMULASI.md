# 📝 Panduan Lengkap: Cara Edit Simulasi yang Sudah Disimpan

## 🎯 Cara Kerja Fitur Edit Simulasi

### **Simulasi Regular** (`/simulation`)

#### **Langkah 1: Klik Tombol Muat**
1. Buka halaman Simulasi (`/simulation`)
2. Di sidebar kanan, lihat daftar "Simulasi Tersimpan"
3. Cari simulasi yang ingin diedit
4. Klik tombol **Muat** (warna biru) 🔵

**Catatan:** Tombol "Edit" sudah dihapus karena redundant. Tombol "Muat" sudah cukup untuk load dan edit simulasi.

#### **Langkah 2: Simulasi Dimuat ke Workspace**
Setelah klik Muat, sistem akan:
- ✅ Memuat semua data simulasi ke workspace
- ✅ Menampilkan semua layanan yang ada
- ✅ Menampilkan margin yang sudah diatur
- ✅ Menyimpan ID simulasi di background (`activeSimulationId`)
- ✅ Menyimpan nama simulasi di field nama (`saveName`)

#### **Langkah 3: Lakukan Perubahan**
Anda bisa melakukan berbagai perubahan:
- ➕ **Tambah layanan baru**: Cari dan pilih layanan dari search box
- ➖ **Hapus layanan**: Klik tombol hapus (🗑️) di kolom Aksi
- ✏️ **Edit unit cost**: Klik di kolom Unit Cost dan ubah nilainya
- 📊 **Ubah margin**: Ubah margin per layanan atau gunakan margin global
- 🔢 **Terapkan margin global**: Gunakan fitur "Terapkan ke Semua" atau "Terapkan ke Terpilih"

#### **Langkah 4: Simpan Hasil Edit**
Ada 2 cara menyimpan:

**Cara A: Update Simulasi yang Sama** (RECOMMENDED)
1. Klik tombol **Simpan** di header (tombol hijau)
2. Modal "Simpan Simulasi" akan muncul
3. **PENTING**: Nama simulasi sudah terisi otomatis dengan nama yang lama
4. **Jangan ubah nama** jika ingin update simulasi yang sama
5. Klik tombol **Simpan** di modal
6. ✅ Simulasi akan **DIUPDATE** (bukan buat baru)
7. Notifikasi "Simulasi diperbarui" akan muncul

**Cara B: Simpan Sebagai Simulasi Baru (Duplikasi)**
1. Klik tombol **Simpan** di header
2. Modal "Simpan Simulasi" akan muncul
3. **Ubah nama** simulasi menjadi nama yang berbeda
4. Klik tombol **Simpan** di modal
5. ✅ Simulasi baru akan dibuat (simulasi lama tetap ada)
6. Notifikasi "Simulasi disimpan" akan muncul

---

### **Simulasi Qty** (`/simulation-qty`)

#### **Langkah 1: Klik Tombol Muat**
1. Buka halaman Simulasi Qty (`/simulation-qty`)
2. Di sidebar kanan, lihat daftar "Simulasi Tersimpan"
3. Cari simulasi yang ingin diedit
4. Klik tombol **Muat** (warna biru) 🔵

**Catatan:** Tombol "Edit" sudah dihapus karena redundant. Tombol "Muat" sudah cukup untuk load dan edit simulasi.

#### **Langkah 2: Simulasi Dimuat ke Workspace**
Setelah klik Edit, sistem akan:
- ✅ Memuat semua data simulasi ke workspace
- ✅ Menampilkan semua layanan yang ada
- ✅ Memuat quantity yang sudah diatur
- ✅ Memuat preset tier yang digunakan
- ✅ Menyimpan ID simulasi di background (`activeSimulationId`)
- ✅ Menyimpan nama simulasi di field nama (`saveName`)
- ✅ Set flag `isEditingExisting = true`

#### **Langkah 3: Lakukan Perubahan**
Anda bisa melakukan berbagai perubahan:
- ➕ **Tambah layanan baru**: Cari dan pilih layanan dari search box
- ➖ **Hapus layanan**: Klik tombol hapus di samping layanan
- 🔢 **Ubah quantity**: Ubah nilai di field "Qty"
- ✏️ **Edit unit cost**: Ubah nilai unit cost per layanan
- 📊 **Ubah preset tier**: Pilih preset tier yang berbeda (jika ada)

#### **Langkah 4: Simpan Hasil Edit**
Ada 2 cara menyimpan:

**Cara A: Update Simulasi yang Sama** (RECOMMENDED)
1. Klik tombol **Simpan** di bawah table (tombol hijau)
2. Modal "Simpan Simulasi" akan muncul
3. **PENTING**: Nama simulasi sudah terisi otomatis dengan nama yang lama
4. Jangan ubah nama jika ingin update simulasi yang sama
5. Klik tombol **Simpan** di modal
6. ✅ Simulasi akan **DIUPDATE** (bukan buat baru)
7. Notifikasi "Simulasi diperbarui" akan muncul

**Cara B: Simpan Sebagai Simulasi Baru**
1. Klik tombol **Simpan** di bawah table
2. Modal "Simpan Simulasi" akan muncul
3. **Ubah nama** simulasi menjadi nama yang berbeda
4. Klik tombol **Simpan** di modal
5. ✅ Simulasi baru akan dibuat (simulasi lama tetap ada)
6. Notifikasi "Simulasi disimpan" akan muncul

---

## 🔍 Cara Kerja di Balik Layar

### **Logic JavaScript**

#### **Simulasi Regular** (`simulasi.js`)

```javascript
// Saat tombol Edit diklik
async loadSimulation(id) {
    // 1. Fetch data simulasi dari server
    const res = await fetch(window.SIMULATION_SHOW_URL(id));
    const sim = data.data;
    
    // 2. Load items ke workspace
    this.simulationResults = sim.items.map(...);
    
    // 3. Set active simulation ID (PENTING!)
    this.activeSimulationId = sim.id;
    
    // 4. Set nama simulasi (untuk prefill modal)
    this.saveName = sim.name;
    
    // 5. Recalculate totals
    this.recalcAll();
}

// Saat tombol Simpan diklik
async saveSimulation(name) {
    // 1. Cek apakah sedang edit atau buat baru
    const isUpdating = !!this.activeSimulationId;
    
    // 2. Tentukan URL dan method
    const url = isUpdating 
        ? window.SIMULATION_UPDATE_URL(this.activeSimulationId) // PUT /simulation/{id}
        : window.SIMULATION_STORE_URL;                          // POST /simulation
    const method = isUpdating ? 'PUT' : 'POST';
    
    // 3. Kirim request
    const res = await fetch(url, { method, body: JSON.stringify(payload) });
    
    // 4. Tampilkan notifikasi
    this.showNotification(
        isUpdating ? 'Simulasi diperbarui' : 'Simulasi disimpan', 
        'success'
    );
}
```

#### **Simulasi Qty** (`simulasi_qty.js`)

```javascript
// Saat tombol Edit diklik
async loadSimulation(id) {
    // 1. Fetch data simulasi dari server
    const res = await fetch(window.SIMULATION_SHOW_URL(id));
    const sim = data.data;
    
    // 2. Load items ke workspace
    this.simulationResults = sim.items.map(...);
    
    // 3. Load simulation-level data
    this.simulationQuantity = sim.simulation_quantity;
    this.simulationMarginPercent = sim.simulation_margin_percent;
    
    // 4. Set active simulation ID (PENTING!)
    this.activeSimulationId = sim.id;
    
    // 5. Set nama simulasi
    this.saveName = sim.name;
    
    // 6. Set flag editing (PENTING!)
    this.isEditingExisting = true;
    
    // 7. Update totals
    this.updateSimulationTotals();
}

// Saat tombol Simpan diklik
async saveSimulation(name) {
    // 1. Cek apakah sedang edit atau buat baru
    const isUpdating = !!this.activeSimulationId && !!this.isEditingExisting;
    
    // 2. Tentukan URL dan method
    const url = isUpdating 
        ? window.SIMULATION_UPDATE_URL(this.activeSimulationId) // PUT /simulation-qty/{id}
        : window.SIMULATION_STORE_URL;                          // POST /simulation-qty
    const method = isUpdating ? 'PUT' : 'POST';
    
    // 3. Kirim request
    const res = await fetch(url, { method, body: JSON.stringify(payload) });
    
    // 4. Update state
    if (!isUpdating) {
        this.isEditingExisting = false; // Reset flag setelah simpan baru
    }
    
    // 5. Tampilkan notifikasi
    this.showNotification(
        isUpdating ? 'Simulasi diperbarui' : 'Simulasi disimpan', 
        'success'
    );
}
```

---

## ⚠️ Penting untuk Diperhatikan

### **1. Nama Simulasi Menentukan Mode Simpan**

| Kondisi | Nama di Modal | Hasil |
|---------|---------------|-------|
| Edit simulasi lama | Nama tetap sama | **UPDATE** simulasi yang sama |
| Edit simulasi lama | Nama diubah | **BUAT BARU** (duplikasi dengan nama baru) |
| Buat simulasi baru | Nama baru | **BUAT BARU** |

### **2. Indikator Simulasi Sedang Diedit**

**Simulasi Regular:**
- Variable `activeSimulationId` terisi dengan ID simulasi
- Variable `saveName` terisi dengan nama simulasi

**Simulasi Qty:**
- Variable `activeSimulationId` terisi dengan ID simulasi
- Variable `saveName` terisi dengan nama simulasi
- Variable `isEditingExisting = true`

### **3. Perbedaan Tombol Muat vs Edit**

| Tombol | Warna | Fungsi | Hasil Simpan |
|--------|-------|--------|--------------|
| **Muat** | Biru | Memuat simulasi ke workspace | UPDATE jika nama sama |
| **Edit** | Kuning | Memuat simulasi ke workspace | UPDATE jika nama sama |

**Catatan**: Kedua tombol memiliki fungsi yang SAMA! Tombol Edit hanya untuk clarity UX.

---

## 🧪 Cara Testing

### **Test 1: Edit dan Update Simulasi**
1. Buat simulasi baru dengan nama "Test Edit 1"
2. Tambahkan 3 layanan
3. Simpan
4. Klik tombol **Edit** (kuning)
5. Tambahkan 2 layanan lagi (total 5 layanan)
6. Klik **Simpan**
7. **Jangan ubah nama** di modal
8. Klik **Simpan** di modal
9. ✅ Verifikasi: Simulasi "Test Edit 1" sekarang punya 5 layanan (bukan 3)
10. ✅ Verifikasi: Tidak ada simulasi duplikat

### **Test 2: Edit dan Simpan Sebagai Baru**
1. Klik **Edit** pada simulasi "Test Edit 1"
2. Ubah margin menjadi 20%
3. Klik **Simpan**
4. **Ubah nama** menjadi "Test Edit 2"
5. Klik **Simpan** di modal
6. ✅ Verifikasi: Ada 2 simulasi: "Test Edit 1" dan "Test Edit 2"
7. ✅ Verifikasi: "Test Edit 1" margin tetap (tidak berubah)
8. ✅ Verifikasi: "Test Edit 2" margin 20%

### **Test 3: Edit Multiple Times**
1. Klik **Edit** pada simulasi "Test Edit 1"
2. Hapus 1 layanan
3. Klik **Simpan** (nama tetap)
4. Klik **Edit** lagi pada "Test Edit 1"
5. Tambah 1 layanan
6. Klik **Simpan** (nama tetap)
7. ✅ Verifikasi: Semua perubahan tersimpan di simulasi yang sama

---

## 🎨 Visual Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    SIMULASI TERSIMPAN                       │
├─────────────────────────────────────────────────────────────┤
│  📊 Simulasi Test 1                                         │
│  5 item · Total: Rp 1.500.000                               │
│  [Muat] [Edit] [Hapus]                                      │
│          ⬆️                                                  │
│      KLIK INI                                               │
└─────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│              SIMULASI DIMUAT KE WORKSPACE                   │
├─────────────────────────────────────────────────────────────┤
│  ✅ Semua layanan ditampilkan                               │
│  ✅ Margin sudah terisi                                     │
│  ✅ activeSimulationId = 123                                │
│  ✅ saveName = "Simulasi Test 1"                            │
└─────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│                 LAKUKAN PERUBAHAN                           │
├─────────────────────────────────────────────────────────────┤
│  ➕ Tambah layanan                                          │
│  ➖ Hapus layanan                                           │
│  ✏️ Edit unit cost                                          │
│  📊 Ubah margin                                             │
└─────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│              KLIK TOMBOL SIMPAN (HIJAU)                     │
└─────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│                  MODAL SIMPAN SIMULASI                      │
├─────────────────────────────────────────────────────────────┤
│  Nama Simulasi: [Simulasi Test 1]  ← Sudah terisi!         │
│                                                             │
│  PILIHAN:                                                   │
│  A. Tetap "Simulasi Test 1" → UPDATE simulasi lama         │
│  B. Ubah jadi "Simulasi Test 2" → BUAT simulasi baru       │
│                                                             │
│  [Batal]  [Simpan]                                          │
└─────────────────────────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
   NAMA SAMA               NAMA BEDA
        │                       │
        ▼                       ▼
  PUT /simulation/{id}    POST /simulation
  (UPDATE)                (CREATE NEW)
        │                       │
        ▼                       ▼
  "Simulasi diperbarui"   "Simulasi disimpan"
```

---

## 🔧 Troubleshooting

### **Masalah: Simulasi tidak terupdate, malah buat baru**

**Penyebab**: Nama simulasi diubah di modal

**Solusi**: 
- Jangan ubah nama di modal jika ingin update
- Nama yang sudah terisi otomatis adalah nama asli simulasi

### **Masalah: Tombol Edit tidak muncul**

**Penyebab**: Perubahan belum di-refresh

**Solusi**:
- Refresh halaman (F5)
- Atau clear cache browser

### **Masalah: Data tidak dimuat setelah klik Edit**

**Penyebab**: Error di console atau network

**Solusi**:
1. Buka Developer Tools (F12)
2. Lihat tab Console untuk error
3. Lihat tab Network untuk request yang gagal
4. Pastikan user memiliki akses ke simulasi tersebut

---

## 📊 Summary

| Aksi | Tombol | Hasil |
|------|--------|-------|
| Muat simulasi untuk diedit | **Edit** (🟡) | Data dimuat ke workspace |
| Update simulasi yang sama | **Simpan** (🟢) + nama tetap | Simulasi DIUPDATE |
| Duplikasi simulasi | **Simpan** (🟢) + nama baru | Simulasi baru DIBUAT |
| Hapus simulasi | **Hapus** (🔴) | Simulasi DIHAPUS |

---

**Dokumentasi dibuat**: 13 Januari 2026, 08:54 WIB
