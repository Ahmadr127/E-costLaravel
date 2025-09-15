<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SimulationController extends Controller
{
    /**
     * Display the simulation page.
     */
    public function index()
    {
        return view('simulation.index');
    }

    /**
     * Search layanan for simulation.
     */
    public function search(Request $request): JsonResponse
    {
        $query = Layanan::with('kategori')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('jenis_pemeriksaan', 'like', "%{$search}%");
            });
        }

        $layanan = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $layanan->map(function($item) {
                return [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'jenis_pemeriksaan' => $item->jenis_pemeriksaan,
                    'tarif_master' => $item->tarif_master,
                    'unit_cost' => $item->unit_cost,
                    'kategori' => $item->kategori->nama_kategori ?? '-'
                ];
            })
        ]);
    }

    /**
     * Get layanan details by ID.
     */
    public function getLayanan(Request $request): JsonResponse
    {
        $layanan = Layanan::with('kategori')
            ->where('id', $request->id)
            ->where('is_active', true)
            ->first();

        if (!$layanan) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $layanan->id,
                'kode' => $layanan->kode,
                'jenis_pemeriksaan' => $layanan->jenis_pemeriksaan,
                'unit_cost' => $layanan->unit_cost,
                'kategori' => $layanan->kategori->nama_kategori ?? '-'
            ]
        ]);
    }
}

