<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationQtyItem extends Model
{
    use HasFactory;

    protected $table = 'simulation_qty_items';

    protected $fillable = [
        'simulation_qty_id',
        'layanan_id',
        'quantity',
        'kode',
        'jenis_pemeriksaan',
        'tarif_master',
        'unit_cost',
        'margin_value',
        'margin_percentage',
        'total_tarif',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(SimulationQty::class, 'simulation_qty_id');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }
}


