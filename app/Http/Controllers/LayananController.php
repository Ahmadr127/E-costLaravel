<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LayananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Layanan::with('kategori');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function($kategoriQuery) use ($search) {
                      $kategoriQuery->where('nama_kategori', 'like', "%{$search}%");
                  });
            });
        }

        // Kategori filter
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
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
                  ->orWhereHas('kategori', function($kategoriQuery) use ($search) {
                      $kategoriQuery->where('nama_kategori', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $filename = 'layanan_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, [
                'No', 'Tindakan', 'unit cost', 'margin', 'unit cost * margin', 'Tarif', '%'
            ]);

            $counter = 0;
            $totalTarif = 0;

            $query->orderByDesc('id')->chunk(500, function ($rows) use (&$counter, &$totalTarif, $handle) {
                foreach ($rows as $row) {
                    $counter++;
                    $unitCost = (float) $row->unit_cost;
                    $margin = (float) $row->margin;
                    $ucTimesMargin = $unitCost * ($margin / 100);
                    $tarif = (float) $row->tarif;
                    $totalTarif += $tarif;

                    fputcsv($handle, [
                        $counter,
                        optional($row->kategori)->nama_kategori,
                        number_format($unitCost, 2, '.', ''),
                        number_format($margin, 2, '.', ''),
                        number_format($ucTimesMargin, 2, '.', ''),
                        number_format($tarif, 2, '.', ''),
                        rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.') . '%',
                    ]);
                }
            });

            // Total row
            fputcsv($handle, []);
            fputcsv($handle, ['', 'Total', '', '', '', number_format($totalTarif, 2, '.', ''), '']);

            fclose($handle);
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
            'nama_layanan' => 'nullable|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'unit_cost' => 'required|numeric|min:0',
            'margin' => 'required|numeric|min:0|max:100',
            'tarif' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Layanan::create([
            'kode' => $request->kode,
            'kategori_id' => $request->kategori_id,
            'unit_cost' => $request->unit_cost,
            'margin' => $request->margin,
            'tarif' => $request->tarif,
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
            'nama_layanan' => 'nullable|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'unit_cost' => 'required|numeric|min:0',
            'margin' => 'required|numeric|min:0|max:100',
            'tarif' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $layanan->update([
            'kode' => $request->kode,
            'kategori_id' => $request->kategori_id,
            'unit_cost' => $request->unit_cost,
            'margin' => $request->margin,
            'tarif' => $request->tarif,
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
}
