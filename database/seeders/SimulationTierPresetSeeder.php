<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SimulationTierPreset;

class SimulationTierPresetSeeder extends Seeder
{
    public function run(): void
    {
        SimulationTierPreset::updateOrCreate(
            ['name' => 'Default Qty'],
            [
                'tiers' => [
                    ['min' => 1, 'max' => 10, 'percent' => 30],
                    ['min' => 11, 'max' => 25, 'percent' => 25],
                    ['min' => 26, 'max' => 50, 'percent' => 22.5],
                    ['min' => 51, 'max' => 100, 'percent' => 20],
                    ['min' => 101, 'max' => 500, 'percent' => 15],
                    // Open-ended tier: omit 'max' so it persists and is treated as unlimited
                    ['min' => 501, 'percent' => 10],
                ],
                'simulation_qty' => 0,
                'is_default' => true,
                'created_by' => null,
            ]
        );
    }
}


