<?php

namespace Database\Seeders;

use App\Models\Charger;
use Illuminate\Database\Seeder;

class ChargerSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 18; $i++) {
            Charger::create([
                'number' => $i,
                'name' => "Cargador {$i}",
                'is_active' => true,
            ]);
        }
    }
}