<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationTierPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tiers',
        'simulation_qty',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'tiers' => 'array',
        'is_default' => 'boolean',
    ];
}


