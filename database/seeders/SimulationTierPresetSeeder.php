<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SimulationTierPreset;

class SimulationTierPresetSeeder extends Seeder
{
    public function run(): void
    {
        if (!SimulationTierPreset::query()->exists()) {
            SimulationTierPreset::create([
                'name' => 'Default Qty',
                'tiers' => [
                    ['min' => 1, 'max' => 5, 'percent' => 20],
                    ['min' => 6, 'max' => 10, 'percent' => 15],
                    ['min' => 11, 'max' => null, 'percent' => 10],
                ],
                'is_default' => true,
                'created_by' => null,
            ]);
        }
    }
}


