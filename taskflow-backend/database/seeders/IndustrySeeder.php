<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Industry;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default industries
        // Additional industries will be added during Sweet CRM integration
        Industry::firstOrCreate(
            ['slug' => 'sin-clasificar'],
            ['name' => 'Sin Clasificar']
        );
    }
}
