<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->timestamp('sweetcrm_created_at')->nullable()->after('sweetcrm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropColumn('sweetcrm_created_at');
        });
    }
};
