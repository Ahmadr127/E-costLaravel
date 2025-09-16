<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'layanan_id',
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
        return $this->belongsTo(Simulation::class);
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }
}


