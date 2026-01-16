<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Simulation;
use App\Models\SimulationItem;
use App\Models\SimulationTierPreset;
use App\Models\SimulationQty;
use App\Models\SimulationQtyItem;
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
     * Display the simulation page with quantity feature.
     */
    public function indexQty()
    {
        return view('simulation.qty');
    }

    /**
     * Tier presets (Qty Simulation only)
     */
    public function tierPresets(Request $request): JsonResponse
    {
        $presets = SimulationTierPreset::orderByDesc('is_default')->orderBy('name')->get(['id','name','tiers','simulation_qty','is_default']);
        return response()->json(['success' => true, 'data' => $presets]);
    }

    /**
     * Presets master page (single global preset management)
     */
    public function presetsPage()
    {
        return view('simulation.presets');
    }

    public function storeTierPreset(Request $request): JsonResponse
    {
        $this->authorizeTierAccess();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tiers' => 'required|array|min:1',
            'tiers.*.min' => 'required|integer|min:1',
            'tiers.*.max' => 'nullable|integer|min:1',
            'tiers.*.percent' => 'required|numeric|min:0|max:100',
            'simulation_qty' => 'required|integer|min:0',
            'is_default' => 'nullable|boolean',
        ]);
        $this->validateNonOverlappingTiers($validated['tiers']);
        // Single global preset policy: replace or create only one record and set default true
        SimulationTierPreset::query()->delete();
        $preset = SimulationTierPreset::create([
            'name' => $validated['name'],
            'tiers' => $validated['tiers'],
            'simulation_qty' => $validated['simulation_qty'],
            'is_default' => true,
            'created_by' => Auth::id(),
        ]);
        return response()->json(['success' => true, 'data' => $preset], 201);
    }

    public function updateTierPreset(Request $request, $id): JsonResponse
    {
        $this->authorizeTierAccess();
        $preset = SimulationTierPreset::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tiers' => 'required|array|min:1',
            'tiers.*.min' => 'required|integer|min:1',
            'tiers.*.max' => 'nullable|integer|min:1',
            'tiers.*.percent' => 'required|numeric|min:0|max:100',
            'simulation_qty' => 'required|integer|min:0',
            'is_default' => 'nullable|boolean',
        ]);
        $this->validateNonOverlappingTiers($validated['tiers']);
        // Single global preset policy: keep this as the only preset and default
        SimulationTierPreset::where('id', '!=', $preset->id)->delete();
        $preset->update([
            'name' => $validated['name'],
            'tiers' => $validated['tiers'],
            'simulation_qty' => $validated['simulation_qty'],
            'is_default' => true,
        ]);
        return response()->json(['success' => true]);
    }

    private function validateNonOverlappingTiers(array $tiers): void
    {
        // sort by min asc, normalize
        usort($tiers, function($a, $b) { return ($a['min'] ?? 0) <=> ($b['min'] ?? 0); });
        $coveredTo = 0;
        foreach ($tiers as $t) {
            $min = (int) ($t['min'] ?? 0);
            $max = array_key_exists('max', $t) && $t['max'] !== null ? (int) $t['max'] : null; // null = unlimited
            if ($max !== null && $max < $min) {
                abort(422, 'Tier max harus lebih besar atau sama dengan min');
            }
            // Rule: tidak boleh overlap dengan range yang sudah dicakup
            if ($min <= $coveredTo) {
                abort(422, 'Tier tidak boleh memiliki range yang sudah dicakup sebelumnya');
            }
            // Update coveredTo
            $coveredTo = $max === null ? PHP_INT_MAX : $max;
            if ($coveredTo === PHP_INT_MAX) break; // unlimited tail
        }
    }

    public function destroyTierPreset($id): JsonResponse
    {
        $this->authorizeTierAccess();
        // Single global preset policy: delete all presets
        $preset = SimulationTierPreset::findOrFail($id);
        $preset->delete();
        return response()->json(['success' => true]);
    }

    private function authorizeTierAccess(): void
    {
        if (!Auth::user() || !Auth::user()->hasPermission('access_simulation_qty')) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeAnySimulationAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        $has = $user->hasPermission('access_simulation') || $user->hasPermission('access_simulation_qty');
        if (!$has) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Search layanan for simulation.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAnySimulationAccess();
        $query = Layanan::with('kategori')
            ->where('is_active', true);

        // Jika parameter 'all' ada, return semua layanan (tanpa limit untuk dropdown)
        if ($request->has('all')) {
            $layanan = $query->orderBy('jenis_pemeriksaan')->get();
        } else {
            // Jika ada search query, filter berdasarkan search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                      ->orWhere('jenis_pemeriksaan', 'like', "%{$search}%")
                      ->orWhereHas('kategori', function($kq) use ($search) {
                          $kq->where('nama_kategori', 'like', "%{$search}%");
                      });
                });
            }
            
            $layanan = $query->limit(50)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $layanan->map(function($item) {
                return [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'jenis_pemeriksaan' => $item->jenis_pemeriksaan,
                    'tarif_master' => $item->tarif_master,
                    'unit_cost' => $item->unit_cost,
                    'kategori_nama' => $item->kategori->nama_kategori ?? '-',
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
     * List simulations for all users (shared access).
     */
    public function list(Request $request): JsonResponse
    {
        $simulations = Simulation::with(['items.layanan.kategori', 'user'])
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'user_id', 'name', 'grand_total', 'items_count', 'created_at', 'updated_at']);

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
                    'user_id' => $sim->user_id,
                    'user_name' => optional($sim->user)->name ?? 'Unknown',
                    'is_owner' => $sim->user_id == Auth::id(),
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
     * List qty simulations for all users (shared access).
     */
    public function listQty(Request $request): JsonResponse
    {
        $simulations = SimulationQty::with(['items.layanan.kategori', 'user'])
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'user_id', 'name', 'grand_total', 'items_count', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $simulations->map(function($sim) {
                $categoryNames = $sim->items
                    ->map(function($item) { return optional(optional($item->layanan)->kategori)->nama_kategori; })
                    ->filter()->unique()->values();
                $categoryName = $categoryNames->first();
                $summary = '';
                if ($categoryNames->count() > 0) {
                    $shown = $categoryNames->take(3)->implode(', ');
                    $extraCount = max(0, $categoryNames->count() - 3);
                    $summary = $extraCount > 0 ? ($shown . ' +' . $extraCount . ' lainnya') : $shown;
                }
                return [
                    'id' => $sim->id,
                    'user_id' => $sim->user_id,
                    'user_name' => optional($sim->user)->name ?? 'Unknown',
                    'is_owner' => $sim->user_id == Auth::id(),
                    'name' => $sim->name,
                    'grand_total' => $sim->grand_total,
                    'items_count' => $sim->items_count,
                    'created_at' => $sim->created_at,
                    'updated_at' => $sim->updated_at,
                    'category_name' => $categoryName,
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
        $this->authorizeAnySimulationAccess();
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
            'items.*.quantity' => 'nullable|integer|min:1',
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
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
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

    // ===== Qty Simulation endpoints (separate storage) =====
    public function storeQty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'tier_preset_id' => 'nullable|exists:simulation_tier_presets,id',
            'default_margin_percent' => 'nullable|numeric|min:0|max:100',
            'simulation_quantity' => 'required|integer|min:0',
            'simulation_margin_percent' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.layanan_id' => 'required|exists:layanan,id',
            'items.*.kode' => 'required|string',
            'items.*.jenis_pemeriksaan' => 'required|string',
            'items.*.tarif_master' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|integer|min:0',
            'total_unit_cost' => 'required|integer|min:0',
            'total_margin_value' => 'required|integer|min:0',
            'sum_unit_cost' => 'required|integer|min:0',
            'sum_tarif_master' => 'required|integer|min:0',
            'grand_total' => 'required|integer|min:0',
        ]);

        $simulation = SimulationQty::create([
            'user_id' => Auth::id(),
            'tier_preset_id' => $request->get('tier_preset_id'),
            'default_margin_percent' => (int) round($request->get('default_margin_percent', 0)),
            'simulation_quantity' => (int) $validated['simulation_quantity'],
            'simulation_margin_percent' => (int) round($validated['simulation_margin_percent']),
            'total_unit_cost' => (int) $validated['total_unit_cost'],
            'total_margin_value' => (int) $validated['total_margin_value'],
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'sum_unit_cost' => $validated['sum_unit_cost'],
            'sum_tarif_master' => $validated['sum_tarif_master'],
            'grand_total' => $validated['grand_total'],
            'items_count' => count($validated['items']),
        ]);

        foreach ($validated['items'] as $item) {
            SimulationQtyItem::create([
                'simulation_qty_id' => $simulation->id,
                'layanan_id' => $item['layanan_id'],
                'kode' => $item['kode'],
                'jenis_pemeriksaan' => $item['jenis_pemeriksaan'],
                'tarif_master' => (int) ($item['tarif_master'] ?? 0),
                'unit_cost' => (int) $item['unit_cost'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Qty simulation saved',
            'data' => $simulation->only(['id', 'name'])
        ], 201);
    }

    public function showQty($id): JsonResponse
{
    $simulation = SimulationQty::with(['items.layanan.kategori', 'user'])->find($id);
    if (!$simulation) {
        return response()->json(['success' => false, 'message' => 'Simulasi Qty tidak ditemukan'], 404);
    }
    // No authorization check - all users can view any qty simulation

    $data = [
        'id' => $simulation->id,
        'user_id' => $simulation->user_id,
        'user_name' => optional($simulation->user)->name ?? 'Unknown',
        'is_owner' => $simulation->user_id == Auth::id(),
        'tier_preset_id' => $simulation->tier_preset_id,
        'default_margin_percent' => $simulation->default_margin_percent,
        'simulation_quantity' => $simulation->simulation_quantity,
        'simulation_margin_percent' => $simulation->simulation_margin_percent,
        'total_unit_cost' => $simulation->total_unit_cost,
        'total_margin_value' => $simulation->total_margin_value,
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
                'kategori_id' => optional($item->layanan)->kategori_id,
                'kategori_nama' => optional(optional($item->layanan)->kategori)->nama_kategori,
            ];
        }),
    ];
    return response()->json(['success' => true, 'data' => $data]);
}
    public function updateQty(Request $request, $id): JsonResponse
    {
        $simulation = SimulationQty::find($id);
        if (!$simulation) return response()->json(['success' => false, 'message' => 'Simulasi Qty tidak ditemukan'], 404);
        // No authorization check - all users can edit any qty simulation

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'tier_preset_id' => 'nullable|exists:simulation_tier_presets,id',
            'default_margin_percent' => 'nullable|numeric|min:0|max:100',
            'simulation_quantity' => 'required|integer|min:0',
            'simulation_margin_percent' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.layanan_id' => 'required|exists:layanan,id',
            'items.*.kode' => 'required|string',
            'items.*.jenis_pemeriksaan' => 'required|string',
            'items.*.tarif_master' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|integer|min:0',
            'total_unit_cost' => 'required|integer|min:0',
            'total_margin_value' => 'required|integer|min:0',
            'sum_unit_cost' => 'required|integer|min:0',
            'sum_tarif_master' => 'required|integer|min:0',
            'grand_total' => 'required|integer|min:0',
        ]);

        $simulation->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
            'tier_preset_id' => $request->get('tier_preset_id'),
            'default_margin_percent' => (int) round($request->get('default_margin_percent', 0)),
            'simulation_quantity' => (int) $validated['simulation_quantity'],
            'simulation_margin_percent' => (int) round($validated['simulation_margin_percent']),
            'total_unit_cost' => (int) $validated['total_unit_cost'],
            'total_margin_value' => (int) $validated['total_margin_value'],
            'sum_unit_cost' => $validated['sum_unit_cost'],
            'sum_tarif_master' => $validated['sum_tarif_master'],
            'grand_total' => $validated['grand_total'],
            'items_count' => count($validated['items']),
        ]);

        $simulation->items()->delete();
        foreach ($validated['items'] as $item) {
            SimulationQtyItem::create([
                'simulation_qty_id' => $simulation->id,
                'layanan_id' => $item['layanan_id'],
                'kode' => $item['kode'],
                'jenis_pemeriksaan' => $item['jenis_pemeriksaan'],
                'tarif_master' => (int) ($item['tarif_master'] ?? 0),
                'unit_cost' => (int) $item['unit_cost'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Qty simulation updated']);
    }

    public function destroyQty($id): JsonResponse
    {
        $simulation = SimulationQty::find($id);
        if (!$simulation) return response()->json(['success' => false, 'message' => 'Simulasi Qty tidak ditemukan'], 404);
        // No authorization check - all users can delete any qty simulation
        $simulation->delete();
        return response()->json(['success' => true, 'message' => 'Qty simulation deleted']);
    }

    /**
 * Show a simulation with items (shared access - all users can view).
 */
public function show($id): JsonResponse
{
    \Log::info('Simulation show called with ID: ' . $id);
    \Log::info('Current user ID: ' . Auth::id());
    
    $simulation = Simulation::with(['items.layanan.kategori', 'user'])->find($id);
    
    if (!$simulation) {
        \Log::warning('Simulation not found with ID: ' . $id);
        return response()->json([
            'success' => false,
            'message' => 'Simulasi tidak ditemukan'
        ], 404);
    }
    
    \Log::info('Simulation found: ' . $simulation->id . ', User ID: ' . $simulation->user_id);
    
    // No authorization check - all users can view any simulation

    $data = [
        'id' => $simulation->id,
        'user_id' => $simulation->user_id,
        'user_name' => optional($simulation->user)->name ?? 'Unknown',
        'is_owner' => $simulation->user_id == Auth::id(),
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
                'quantity' => $item->quantity ?? 1,
                'kode' => $item->kode,
                'jenis_pemeriksaan' => $item->jenis_pemeriksaan,
                'tarif_master' => $item->tarif_master,
                'unit_cost' => $item->unit_cost,
                'margin_value' => $item->margin_value,
                'margin_percentage' => $item->margin_percentage,
                'total_tarif' => $item->total_tarif,
                // computed subtotal on client from qty * total_tarif
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
     * Update a simulation (shared access - all users can edit)
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
        
        // No authorization check - all users can edit any simulation

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.layanan_id' => 'required|exists:layanan,id',
            'items.*.quantity' => 'nullable|integer|min:1',
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
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
                'kode' => $item['kode'],
                'jenis_pemeriksaan' => $item['jenis_pemeriksaan'],
                'tarif_master' => (int) ($item['tarif_master'] ?? 0),
                'unit_cost' => (int) $item['unit_cost'],
                'margin_value' => (int) $item['margin_value'],
                'margin_percentage' => (int) round($item['margin_percentage']),
                'total_tarif' => (int) $item['total_tarif'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation updated',
        ]);
    }

    /**
     * Delete a simulation (shared access - all users can delete)
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
        
        // No authorization check - all users can delete any simulation
        $simulation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Simulation deleted',
        ]);
    }
}
