<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class LayananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Layanan::with('kategori');

        // Search filter - cari di semua field yang relevan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('jenis_pemeriksaan', 'like', "%{$search}%")
                  ->orWhere('tarif_master', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('unit_cost', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function($kategoriQuery) use ($search) {
                      $kategoriQuery->where('nama_kategori', 'like', "%{$search}%")
                                   ->orWhere('deskripsi', 'like', "%{$search}%");
                  });
            });
        }

        // Kategori filter
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }


        $layanan = $query->latest()->paginate(10)->withQueryString();
        $kategori = Kategori::active()->get();
        
        return view('layanan.index', compact('layanan', 'kategori'));
    }

    /**
     * Export filtered layanan to CSV (Excel-compatible) including total tarif.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Layanan::with('kategori');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('jenis_pemeriksaan', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('unit_cost', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function($kategoriQuery) use ($search) {
                      $kategoriQuery->where('nama_kategori', 'like', "%{$search}%")
                                   ->orWhere('deskripsi', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        $filename = 'layanan_' . now()->format('Ymd_His') . '.csv';

        // Count for zero-data handling and logging context
        $totalRows = (clone $query)->count();
        \Log::info('Export layanan requested', [
            'filters' => [
                'search' => $request->get('search'),
                'kategori_id' => $request->get('kategori_id'),
            ],
            'total_rows' => $totalRows,
        ]);

        return response()->streamDownload(function () use ($query, $totalRows) {
            try {
                $handle = fopen('php://output', 'w');
                // UTF-8 BOM for Excel compatibility
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Headers
                fputcsv($handle, [
                    'No', 'Kode', 'Jenis Pemeriksaan', 'Tarif Master', 'Kategori', 'Unit Cost', 'Deskripsi'
                ]);

                $counter = 0;
                $totalUnitCost = 0;

                if ($totalRows === 0) {
                    // Write empty total and return early
                    fputcsv($handle, []);
                    fputcsv($handle, ['', 'Total', '', '', '', number_format(0, 2, '.', ''), '']);
                    fclose($handle);
                    return;
                }

                $query->orderByDesc('id')->chunk(500, function ($rows) use (&$counter, &$totalUnitCost, $handle) {
                    foreach ($rows as $row) {
                        $counter++;
                        $unitCost = (float) ($row->unit_cost ?? 0);
                        $totalUnitCost += $unitCost;

                        fputcsv($handle, [
                            $counter,
                            $row->kode ?: '-',
                            $row->jenis_pemeriksaan ?: '-',
                            $row->tarif_master ?: '-',
                            optional($row->kategori)->nama_kategori,
                            number_format($unitCost, 2, '.', ''),
                            $row->deskripsi ?: '-',
                        ]);
                    }
                });

                // Total row
                fputcsv($handle, []);
                fputcsv($handle, ['', 'Total', '', '', '', number_format($totalUnitCost, 2, '.', ''), '']);

                fclose($handle);
            } catch (\Throwable $e) {
                \Log::error('Export layanan failed', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Let the exception bubble to client
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kategori = Kategori::active()->get();
        return view('layanan.create', compact('kategori'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'nullable|string|max:255|unique:layanan',
            'jenis_pemeriksaan' => 'nullable|string|max:255',
            'tarif_master' => 'nullable|string|max:20',
            'kategori_id' => 'required|exists:kategori,id',
            'unit_cost' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Layanan::create([
            'kode' => $request->kode,
            'jenis_pemeriksaan' => $request->jenis_pemeriksaan,
            'tarif_master' => $request->tarif_master,
            'kategori_id' => $request->kategori_id,
            'unit_cost' => $request->unit_cost,
            'deskripsi' => $request->deskripsi,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('layanan.index')->with('success', 'Layanan berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Layanan $layanan)
    {
        $layanan->load('kategori');
        return view('layanan.show', compact('layanan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Layanan $layanan)
    {
        $kategori = Kategori::active()->get();
        return view('layanan.edit', compact('layanan', 'kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Layanan $layanan)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'nullable|string|max:255|unique:layanan,kode,' . $layanan->id,
            'jenis_pemeriksaan' => 'nullable|string|max:255',
            'tarif_master' => 'nullable|string|max:20',
            'kategori_id' => 'required|exists:kategori,id',
            'unit_cost' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $layanan->update([
            'kode' => $request->kode,
            'jenis_pemeriksaan' => $request->jenis_pemeriksaan,
            'tarif_master' => $request->tarif_master,
            'kategori_id' => $request->kategori_id,
            'unit_cost' => $request->unit_cost,
            'deskripsi' => $request->deskripsi,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('layanan.index')->with('success', 'Layanan berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Layanan $layanan)
    {
        $layanan->delete();
        return redirect()->route('layanan.index')->with('success', 'Layanan berhasil dihapus!');
    }

    /**
     * Show the form for uploading Excel file.
     */
    public function showUploadForm()
    {
        return view('layanan.upload');
    }

    /**
     * Handle Excel file upload and import data.
     */
    public function uploadExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('excel_file');
            
            // Log file info
            \Log::info('Uploading Excel file:', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]);
            
            // Deteksi header otomatis
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Import data using Laravel Excel dengan deteksi header
            $import = new LayananExcelImport();
            $import->detectHeaders($worksheet);
            Excel::import($import, $file);

            $importedCount = $import->getImportedCount();
            $updatedCount = $import->getUpdatedCount();
            $skippedCount = $import->getSkippedCount();

            \Log::info('Import completed:', [
                'imported' => $importedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'total_processed' => $importedCount + $updatedCount + $skippedCount
            ]);

            if ($importedCount > 0 || $updatedCount > 0) {
                $message = "Import berhasil! ";
                if ($importedCount > 0) {
                    $message .= "Data baru: {$importedCount}";
                }
                if ($updatedCount > 0) {
                    $message .= ($importedCount > 0 ? ", " : "") . "Data diperbarui: {$updatedCount}";
                }
                if ($skippedCount > 0) {
                    $message .= ", Data dilewati: {$skippedCount}";
                }
                
                return redirect()->route('layanan.index')->with('success', $message);
            } else {
                return redirect()->back()->with('warning', 
                    "Tidak ada data yang berhasil diimpor. Data yang dilewati: {$skippedCount}. Periksa format file Excel dan pastikan kolom 'kode' dan 'unit_cost' terisi."
                );
            }

        } catch (\Exception $e) {
            \Log::error('Excel import error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengimpor file: ' . $e->getMessage());
        }
    }

    /**
     * Clear all layanan data with confirmation.
     */
    public function clearAll(Request $request)
    {
        // Check if user has permission to clear all data
        if (!auth()->user()->hasPermission('manage_layanan')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus semua data layanan.');
        }

        // Validate confirmation
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|in:DELETE_ALL_LAYANAN'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Konfirmasi tidak valid. Silakan coba lagi.');
        }

        try {
            // Count total records before deletion
            $totalRecords = Layanan::count();
            $totalSimulationItems = DB::table('simulation_items')->count();
            
            if ($totalRecords === 0) {
                return redirect()->back()->with('info', 'Tidak ada data layanan yang perlu dihapus.');
            }

            // Log the action
            \Log::info('Clearing all layanan data:', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'total_records' => $totalRecords,
                'ip_address' => $request->ip()
            ]);

            // Use transaction and DELETE (not TRUNCATE) to avoid FK truncate restriction
            DB::beginTransaction();
            try {
                // 1) Remove all simulation items that reference layanan
                DB::table('simulation_items')->delete();

                // 2) Now clear layanan
                DB::table('layanan')->delete();

                DB::commit();
            } catch (\Throwable $txe) {
                DB::rollBack();
                throw $txe;
            }

            \Log::info('All layanan data cleared successfully', [
                'deleted_layanan' => $totalRecords,
                'deleted_simulation_items' => $totalSimulationItems,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('layanan.index')->with('success', 
                "Semua data layanan telah berhasil dihapus. Total layanan: {$totalRecords}, simulation item: {$totalSimulationItems}."
            );

        } catch (\Exception $e) {
            \Log::error('Error clearing all layanan data:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}

/**
 * Excel Import Class for Layanan
 */
class LayananExcelImport implements ToModel, SkipsEmptyRows, SkipsOnError, WithCalculatedFormulas
{
    use Importable, SkipsErrors;

    private $importedCount = 0;
    private $updatedCount = 0;
    private $skippedCount = 0;
    private $currentRow = 0;
    private $columnMapping = [];
    private $dataStartRow = 6;

    public function startRow(): int
    {
        return $this->dataStartRow; // Data dimulai dari baris yang terdeteksi
    }

    /**
     * Deteksi header otomatis dari 3 baris pertama
     */
    public function detectHeaders($worksheet)
    {
        $headerPatterns = [
            'kode' => ['kode', 'code', 'id', 'no'],
            'jenis_pemeriksaan' => ['jenis', 'pemeriksaan', 'nama', 'tindakan', 'layanan'],
            'unit_cost' => ['unit cost', 'unitcost', 'cost', 'biaya', 'harga'],
            'tarif_master' => ['tarif master', 'ii', 'igd', 'poli'] ,
            // Perluas sinonim kategori
            'kategori' => ['kategori', 'kategori layanan', 'category', 'bagian', 'unit', 'departemen', 'instalasi', 'kelompok', 'sub kategori', 'sub-kategori', 'section'],
            'margin' => ['margin', 'keuntungan', 'profit'],
            'tarif' => ['tarif', 'harga', 'price', 'fee']
        ];

        $columnMapping = [];
        
        // Tentukan batas kolom/row aktual dari sheet
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn());
        $highestRow = (int) $worksheet->getHighestRow();
        $scanRows = min(10, max(3, $highestRow));
        \Log::info('Header scan window', ['highest_column_index' => $highestColumn, 'highest_row' => $highestRow, 'scan_rows' => $scanRows]);

        // Analisis baris-baris awal untuk mencari header (lebih toleran posisi header)
        for ($row = 1; $row <= $scanRows; $row++) {
            for ($colIndex = 1; $colIndex <= $highestColumn; $colIndex++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $cellValue = $worksheet->getCell($col . $row)->getValue();
                if ($cellValue) {
                    $value = strtolower(trim($cellValue));
                    
                    // Cek apakah nilai ini cocok dengan pola header
                    foreach ($headerPatterns as $field => $patterns) {
                        foreach ($patterns as $pattern) {
                            if (strpos($value, $pattern) !== false) {
                                $columnIndex = $colIndex - 1; // Convert 1-based to 0-based index
                                
                                // Hanya set mapping jika belum ada, atau jika ini adalah unit_cost dan kolom sebelumnya bukan yang utama
                                if (!isset($columnMapping[$field]) || 
                                    ($field === 'unit_cost' && $columnIndex < $columnMapping[$field])) {
                                    $columnMapping[$field] = $columnIndex;
                                    \Log::info("Header detected: {$field} at column {$col} (index {$columnIndex}) - value: '{$value}'", ['row' => $row]);
                                }
                                break 2; // Break dari kedua loop
                            }
                        }
                    }
                }
            }
        }

        // Set default mapping jika tidak ditemukan
        if (empty($columnMapping['kode'])) {
            $columnMapping['kode'] = 0; // Default ke kolom A
        }
        if (empty($columnMapping['jenis_pemeriksaan'])) {
            $columnMapping['jenis_pemeriksaan'] = 1; // Default ke kolom B
        }
        if (empty($columnMapping['unit_cost'])) {
            $columnMapping['unit_cost'] = 6; // Default ke kolom G
        }
        // kategori dan tarif_master opsional: hanya dipakai jika ditemukan

        $this->columnMapping = $columnMapping;
        
        // Tentukan baris data dimulai (cari baris pertama yang memiliki data di salah satu kolom kunci)
        for ($row = 4; $row <= min(30, $highestRow); $row++) {
            $kodeCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(($columnMapping['kode'] ?? 0) + 1);
            $jenisCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(($columnMapping['jenis_pemeriksaan'] ?? 1) + 1);
            $kodeCell = $worksheet->getCell($kodeCol . $row)->getValue();
            $jenisCell = $worksheet->getCell($jenisCol . $row)->getValue();
            if ((isset($kodeCell) && trim((string)$kodeCell) !== '') || (isset($jenisCell) && trim((string)$jenisCell) !== '')) {
                $this->dataStartRow = $row;
                \Log::info("Data start row detected: {$row}");
                break;
            }
        }

        \Log::info("Final column mapping:", $this->columnMapping);
        return $this->columnMapping;
    }

    public function model(array $row)
    {
        $this->currentRow++;
        
        // Log untuk debugging
        \Log::info("Processing row {$this->currentRow}:", $row);
        
        // Gunakan mapping yang terdeteksi otomatis
        $kode = isset($row[$this->columnMapping['kode']]) ? trim($row[$this->columnMapping['kode']]) : '';
        $jenisPemeriksaan = isset($row[$this->columnMapping['jenis_pemeriksaan']]) ? trim($row[$this->columnMapping['jenis_pemeriksaan']]) : '';
        $unitCost = isset($row[$this->columnMapping['unit_cost']]) ? $this->parseUnitCost($row[$this->columnMapping['unit_cost']]) : null;
        $tarifMaster = isset($this->columnMapping['tarif_master']) && isset($row[$this->columnMapping['tarif_master']]) ? $this->normalizeTarifMaster(trim((string)$row[$this->columnMapping['tarif_master']])) : null;
        $kategoriName = isset($this->columnMapping['kategori']) && isset($row[$this->columnMapping['kategori']]) ? trim((string)$row[$this->columnMapping['kategori']]) : '';

        // Heuristik: deteksi dan lewati baris header atau baris judul seksi (mis. "Tindakan Operasi")
        $lowerKode = strtolower($kode);
        $lowerJenis = strtolower($jenisPemeriksaan);
        $isHeaderLike = in_array($lowerKode, ['no', 'kode', '#'])
            || strpos($lowerJenis, 'jenis') !== false
            || strpos($lowerJenis, 'pemeriksaan') !== false
            || in_array($lowerJenis, ['tindakan operasi', 'pemeriksaan laboratorium', 'radiologi', 'farmasi', 'igd', 'ii', 'poli']);

        // Data dianggap valid jika memiliki salah satu angka (unit_cost atau tarif_master) atau memiliki kode yang bukan header
        $hasNumeric = is_numeric($unitCost) || !empty($tarifMaster);
        $hasValidKode = !empty($kode) && !in_array($lowerKode, ['no', 'kode', '#']);
        if ($isHeaderLike || (!$hasNumeric && !$hasValidKode)) {
            \Log::info("Skipping row {$this->currentRow} - detected as header/section or lacks numeric fields:", [
                'kode' => $kode,
                'jenis_pemeriksaan' => $jenisPemeriksaan,
                'unit_cost' => $unitCost,
                'tarif_master' => $tarifMaster,
            ]);
            $this->skippedCount++;
            return null;
        }

        // Resolve kategori: auto-create if not exists; default when empty
        $kategoriModel = null;
        if ($kategoriName !== '') {
            $normalizedKategori = trim(mb_strtolower($kategoriName));
            $kategoriModel = Kategori::whereRaw('LOWER(nama_kategori) = ?', [$normalizedKategori])->first();
            if (!$kategoriModel) {
                $kategoriModel = Kategori::create([
                    'nama_kategori' => trim($kategoriName),
                    'deskripsi' => 'Dibuat otomatis saat import',
                    'is_active' => true,
                ]);
                \Log::info('Created kategori from import', ['nama_kategori' => $kategoriName, 'id' => $kategoriModel->id]);
            }
        } else {
            // Use or create a default category
            $kategoriModel = Kategori::whereRaw('LOWER(nama_kategori) = ?', ['default'])->first();
            if (!$kategoriModel) {
                $kategoriModel = Kategori::create([
                    'nama_kategori' => 'Default',
                    'deskripsi' => 'Kategori default otomatis untuk data tanpa kategori',
                    'is_active' => true,
                ]);
                \Log::info('Created default kategori for empty kategori cell', ['id' => $kategoriModel->id]);
            }
        }

        // Handle existing records - update instead of skip
        if (!empty($kode)) {
            $existing = \App\Models\Layanan::where('kode', $kode)->first();
            if ($existing) {
                try {
                    // Update existing record
                    $existing->update([
                        'jenis_pemeriksaan' => $jenisPemeriksaan,
                        'tarif_master' => $tarifMaster,
                        'kategori_id' => $kategoriModel->id,
                        'unit_cost' => $unitCost ?: 0,
                        'is_active' => true
                    ]);
                    
                    $this->updatedCount++;
                    \Log::info("Updated existing row {$this->currentRow}:", [
                        'kode' => $kode, 
                        'jenis_pemeriksaan' => $jenisPemeriksaan,
                        'unit_cost' => $unitCost,
                        'action' => 'updated'
                    ]);
                    return null; // Return null to skip creating new model
                } catch (\Exception $e) {
                    \Log::error("Failed to update existing row {$this->currentRow}:", [
                        'kode' => $kode,
                        'error' => $e->getMessage(),
                        'action' => 'update_failed'
                    ]);
                    $this->skippedCount++;
                    return null;
                }
            }
        }

        // Create new record
        $this->importedCount++;
        \Log::info("Successfully created row {$this->currentRow}:", [
            'kode' => $kode, 
            'jenis_pemeriksaan' => $jenisPemeriksaan,
            'unit_cost' => $unitCost,
            'action' => 'created'
        ]);

        return new Layanan([
            'kode' => $kode,
            'jenis_pemeriksaan' => $jenisPemeriksaan,
            'tarif_master' => $tarifMaster,
            'kategori_id' => $kategoriModel->id,
            'unit_cost' => $unitCost ?: 0, // Default value 0 jika unit_cost kosong
            'is_active' => true
        ]);
    }

    private function parseUnitCost($value)
    {
        // Convert to string and trim
        $value = trim((string) $value);
        
        if (empty($value)) {
            return null;
        }

        // Check if already numeric
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Handle comma as thousands separator and dot as decimal
        $cleaned = str_replace(',', '', $value);
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        // Handle dot as thousands separator and comma as decimal (Indonesian format)
        $cleaned = str_replace('.', '', $value);
        $cleaned = str_replace(',', '.', $cleaned);
        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        // Try to extract numbers only
        $numbers = preg_replace('/[^0-9.,]/', '', $value);
        if (!empty($numbers)) {
            $cleaned = str_replace(',', '', $numbers);
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        \Log::warning('Cannot parse unit_cost value:', ['value' => $value]);
        return null;
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getTotalProcessedCount()
    {
        return $this->importedCount + $this->updatedCount + $this->skippedCount;
    }

    private function normalizeTarifMaster(?string $value): ?string
    {
        if ($value === null) return null;
        $v = strtolower(trim($value));
        if ($v === '') return null;
        // Map common variants
        if (in_array($v, ['ii', 'instalasi inap', 'rawat inap'])) return 'II';
        if (in_array($v, ['igd', 'gawat darurat'])) return 'IGD';
        if (in_array($v, ['poli', 'poliklinik', 'rawat jalan'])) return 'POLI';
        // Try to extract II/IGD/POLI substrings
        if (strpos($v, 'igd') !== false) return 'IGD';
        if (strpos($v, 'poli') !== false) return 'POLI';
        if (strpos($v, 'ii') !== false) return 'II';
        return strtoupper($value);
    }
}
