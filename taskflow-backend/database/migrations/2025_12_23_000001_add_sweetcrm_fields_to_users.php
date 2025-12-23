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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sweetcrm_id')) {
                $table->string('sweetcrm_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'sweetcrm_user_type')) {
                $table->string('sweetcrm_user_type')->nullable()->after('sweetcrm_id');
            }
            if (!Schema::hasColumn('users', 'sweetcrm_synced_at')) {
                $table->timestamp('sweetcrm_synced_at')->nullable()->after('sweetcrm_user_type');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            // sweetcrm_id ya existe en clients table desde create_clients_table migration
            if (!Schema::hasColumn('clients', 'sweetcrm_synced_at')) {
                $table->timestamp('sweetcrm_synced_at')->nullable()->after('sweetcrm_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sweetcrm_id', 'sweetcrm_user_type', 'sweetcrm_synced_at']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['sweetcrm_id', 'sweetcrm_synced_at']);
        });
    }
};
