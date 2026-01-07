<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultUserSeeder::class,  // Must be first to satisfy foreign keys
            UserSeeder::class,
            TemplateSeeder::class,
            FlowSeeder::class,
        ]);
    }
}