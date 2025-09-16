<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Simulation;
use App\Models\SimulationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
                    'kategori' => $item->kategori->nama_kategori ?? '-',
                    'kategori_id' => $item->kategori_id
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

    /**
     * List simulations for current user.
     */
    public function list(Request $request): JsonResponse
    {
        $simulations = Simulation::with(['items.layanan.kategori'])
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'name', 'grand_total', 'items_count', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $simulations->map(function($sim) {
                // Derive category name from the first item's layanan.kategori if exists
                $firstItem = $sim->items->first();
                $categoryName = $firstItem && $firstItem->layanan && $firstItem->layanan->kategori
                    ? $firstItem->layanan->kategori->nama_kategori
                    : null;
                return [
                    'id' => $sim->id,
                    'name' => $sim->name,
                    'grand_total' => $sim->grand_total,
                    'items_count' => $sim->items_count,
                    'created_at' => $sim->created_at,
                    'updated_at' => $sim->updated_at,
                    'category_name' => $categoryName,
                ];
            }),
        ]);
    }

    /**
     * List categories (for searchable dropdown)
     */
    public function categories(Request $request): JsonResponse
    {
        $q = Kategori::query();
        if ($request->filled('search')) {
            $term = $request->get('search');
            $q->where('nama_kategori', 'like', "%{$term}%");
        }
        $items = $q->orderBy('nama_kategori')->limit(100)->get(['id','nama_kategori']);
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Store a new simulation with items.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.layanan_id' => 'required|exists:layanan,id',
            'items.*.kode' => 'required|string',
            'items.*.jenis_pemeriksaan' => 'required|string',
            'items.*.tarif_master' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|integer|min:0',
            'items.*.margin_value' => 'required|integer|min:0',
            'items.*.margin_percentage' => 'required|numeric|min:0|max:100',
            'items.*.total_tarif' => 'required|integer|min:0',
            'items.*.kategori_id' => 'required|exists:kategori,id',
            'sum_unit_cost' => 'required|integer|min:0',
            'sum_tarif_master' => 'required|integer|min:0',
            'grand_total' => 'required|integer|min:0',
        ]);

        $simulation = Simulation::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'sum_unit_cost' => $validated['sum_unit_cost'],
            'sum_tarif_master' => $validated['sum_tarif_master'],
            'grand_total' => $validated['grand_total'],
            'items_count' => count($validated['items']),
        ]);

        foreach ($validated['items'] as $item) {
            SimulationItem::create([
                'simulation_id' => $simulation->id,
                'layanan_id' => $item['layanan_id'],
                'kode' => $item['kode'],
                'jenis_pemeriksaan' => $item['jenis_pemeriksaan'],
                'tarif_master' => (int) ($item['tarif_master'] ?? 0),
                'unit_cost' => (int) $item['unit_cost'],
                'margin_value' => (int) $item['margin_value'],
                'margin_percentage' => (int) round($item['margin_percentage']),
                'total_tarif' => (int) $item['total_tarif'],
            ]);

            // Update layanan kategori based on selection
            Layanan::where('id', $item['layanan_id'])->update(['kategori_id' => $item['kategori_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation saved',
            'data' => $simulation->only(['id', 'name'])
        ], 201);
    }

    /**
     * Show a simulation with items.
     */
    public function show(Simulation $simulation): JsonResponse
    {
        $this->authorizeOwner($simulation);

        $simulation->load(['items.layanan.kategori']);

        $data = [
            'id' => $simulation->id,
            'user_id' => $simulation->user_id,
            'name' => $simulation->name,
            'notes' => $simulation->notes,
            'sum_unit_cost' => $simulation->sum_unit_cost,
            'sum_tarif_master' => $simulation->sum_tarif_master,
            'grand_total' => $simulation->grand_total,
            'items_count' => $simulation->items_count,
            'created_at' => $simulation->created_at,
            'updated_at' => $simulation->updated_at,
            'items' => $simulation->items->map(function ($item) {
                return [
                    'layanan_id' => $item->layanan_id,
                    'kode' => $item->kode,
                    'jenis_pemeriksaan' => $item->jenis_pemeriksaan,
                    'tarif_master' => $item->tarif_master,
                    'unit_cost' => $item->unit_cost,
                    'margin_value' => $item->margin_value,
                    'margin_percentage' => $item->margin_percentage,
                    'total_tarif' => $item->total_tarif,
                    'kategori_id' => optional($item->layanan)->kategori_id,
                    'kategori_nama' => optional(optional($item->layanan)->kategori)->nama_kategori,
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update a simulation (rename, notes, items replace)
     */
    public function update(Request $request, Simulation $simulation): JsonResponse
    {
        $this->authorizeOwner($simulation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.layanan_id' => 'required|exists:layanan,id',
            'items.*.kode' => 'required|string',
            'items.*.jenis_pemeriksaan' => 'required|string',
            'items.*.tarif_master' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|integer|min:0',
            'items.*.margin_value' => 'required|integer|min:0',
            'items.*.margin_percentage' => 'required|numeric|min:0|max:100',
            'items.*.total_tarif' => 'required|integer|min:0',
            'items.*.kategori_id' => 'required|exists:kategori,id',
            'sum_unit_cost' => 'required|integer|min:0',
            'sum_tarif_master' => 'required|integer|min:0',
            'grand_total' => 'required|integer|min:0',
        ]);

        $simulation->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'sum_unit_cost' => $validated['sum_unit_cost'],
            'sum_tarif_master' => $validated['sum_tarif_master'],
            'grand_total' => $validated['grand_total'],
            'items_count' => count($validated['items']),
        ]);

        // Replace items
        $simulation->items()->delete();
        foreach ($validated['items'] as $item) {
            SimulationItem::create([
                'simulation_id' => $simulation->id,
                'layanan_id' => $item['layanan_id'],
                'kode' => $item['kode'],
                'jenis_pemeriksaan' => $item['jenis_pemeriksaan'],
                'tarif_master' => (int) ($item['tarif_master'] ?? 0),
                'unit_cost' => (int) $item['unit_cost'],
                'margin_value' => (int) $item['margin_value'],
                'margin_percentage' => (int) round($item['margin_percentage']),
                'total_tarif' => (int) $item['total_tarif'],
            ]);

            // Update layanan kategori based on selection
            Layanan::where('id', $item['layanan_id'])->update(['kategori_id' => $item['kategori_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation updated',
        ]);
    }

    /**
     * Delete a simulation
     */
    public function destroy(Simulation $simulation): JsonResponse
    {
        $this->authorizeOwner($simulation);
        $simulation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Simulation deleted',
        ]);
    }

    private function authorizeOwner(Simulation $simulation): void
    {
        if ($simulation->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}

