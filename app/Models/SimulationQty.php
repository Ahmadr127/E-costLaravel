<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationQty extends Model
{
    use HasFactory;

    protected $table = 'simulations_qty';

    protected $fillable = [
        'user_id',
        'tier_preset_id',
        'default_margin_percent',
        'name',
        'notes',
        'sum_unit_cost',
        'sum_tarif_master',
        'grand_total',
        'items_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SimulationQtyItem::class, 'simulation_qty_id');
    }
}


