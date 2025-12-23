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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('case_id')->nullable()->after('flow_id')->constrained('crm_cases')->onDelete('set null');
            $table->string('sweetcrm_id')->nullable()->index()->after('case_id');
            $table->timestamp('sweetcrm_synced_at')->nullable()->after('sweetcrm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropColumn(['case_id', 'sweetcrm_id', 'sweetcrm_synced_at']);
        });
    }
};
