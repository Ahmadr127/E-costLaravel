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
                // Collect unique category names across items
                $categoryNames = $sim->items
                    ->map(function($item) {
                        return optional(optional($item->layanan)->kategori)->nama_kategori;
                    })
                    ->filter()
                    ->unique()
                    ->values();

                // Backward-compatible single name: first one if exists
                $categoryName = $categoryNames->first();

                // Build summary: up to 3 names, then suffix with "+N lainnya"
                $summary = '';
                if ($categoryNames->count() > 0) {
                    $shown = $categoryNames->take(3)->implode(', ');
                    $extraCount = max(0, $categoryNames->count() - 3);
                    $summary = $extraCount > 0 ? ($shown . ' +' . $extraCount . ' lainnya') : $shown;
                }

                return [
                    'id' => $sim->id,
                    'name' => $sim->name,
                    'grand_total' => $sim->grand_total,
                    'items_count' => $sim->items_count,
                    'created_at' => $sim->created_at,
                    'updated_at' => $sim->updated_at,
                    // existing field for backward compatibility
                    'category_name' => $categoryName,
                    // new fields
                    'category_names' => $categoryNames,
                    'category_summary' => $summary,
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

            // No kategori update required during simulation save
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
    public function show($id): JsonResponse
    {
        \Log::info('Simulation show called with ID: ' . $id);
        \Log::info('Current user ID: ' . Auth::id());
        
        $simulation = Simulation::find($id);
        
        if (!$simulation) {
            \Log::warning('Simulation not found with ID: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Simulasi tidak ditemukan'
            ], 404);
        }
        
        \Log::info('Simulation found: ' . $simulation->id . ', User ID: ' . $simulation->user_id);
        
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
    public function update(Request $request, $id): JsonResponse
    {
        $simulation = Simulation::find($id);
        
        if (!$simulation) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi tidak ditemukan'
            ], 404);
        }
        
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

            // No kategori update required during simulation update
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation updated',
        ]);
    }

    /**
     * Delete a simulation
     */
    public function destroy($id): JsonResponse
    {
        $simulation = Simulation::find($id);
        
        if (!$simulation) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi tidak ditemukan'
            ], 404);
        }
        
        $this->authorizeOwner($simulation);
        $simulation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Simulation deleted',
        ]);
    }

    private function authorizeOwner(Simulation $simulation): void
    {
        $simulationUserId = $simulation->user_id;
        $currentUserId = Auth::id();
        
        \Log::info('Authorizing owner - Simulation user ID: ' . $simulationUserId . ' (type: ' . gettype($simulationUserId) . ')');
        \Log::info('Current user ID: ' . $currentUserId . ' (type: ' . gettype($currentUserId) . ')');
        \Log::info('Strict comparison (===): ' . ($simulationUserId === $currentUserId ? 'true' : 'false'));
        \Log::info('Loose comparison (==): ' . ($simulationUserId == $currentUserId ? 'true' : 'false'));
        
        if ($simulationUserId != $currentUserId) {
            \Log::warning('Authorization failed - User does not own simulation');
            abort(403, 'Unauthorized');
        }
        
        \Log::info('Authorization successful');
    }
}

