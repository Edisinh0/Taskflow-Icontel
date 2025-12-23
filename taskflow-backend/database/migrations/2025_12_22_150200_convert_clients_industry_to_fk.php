<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Industry;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add industry_id FK column
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
        });

        // Migrate existing data from industry text field to industry_id FK
        // Find or create industries from existing text values
        $clients = DB::table('clients')->whereNotNull('industry')->distinct('industry')->get(['industry']);

        foreach ($clients as $client) {
            if ($client->industry && trim($client->industry) !== '') {
                $slug = \Illuminate\Support\Str::slug($client->industry);

                // Find or create the industry
                $industry = Industry::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => ucwords($client->industry)]
                );

                // Update all clients with this industry text
                DB::table('clients')
                    ->where('industry', $client->industry)
                    ->update(['industry_id' => $industry->id]);
            }
        }

        // Create default "Sin Clasificar" industry if not exists
        Industry::firstOrCreate(
            ['slug' => 'sin-clasificar'],
            ['name' => 'Sin Clasificar']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeignKeyIfExists('clients_industry_id_foreign');
            $table->dropColumn('industry_id');
        });
    }
};
