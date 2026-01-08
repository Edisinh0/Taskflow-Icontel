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
            // Agregar campos de cierre si no existen
            if (!Schema::hasColumn('crm_cases', 'closure_status')) {
                $table->enum('closure_status', ['open', 'closure_requested', 'closed'])
                    ->default('open')
                    ->after('status')
                    ->comment('Estado del flujo de cierre del caso');
            }
            
            if (!Schema::hasColumn('crm_cases', 'closure_requested_by_id')) {
                $table->foreignId('closure_requested_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('closure_status');
            }
            
            if (!Schema::hasColumn('crm_cases', 'closure_requested_at')) {
                $table->timestamp('closure_requested_at')
                    ->nullable()
                    ->after('closure_requested_by_id');
            }
            
            if (!Schema::hasColumn('crm_cases', 'closure_approved_by_id')) {
                $table->foreignId('closure_approved_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('closure_requested_at');
            }
            
            if (!Schema::hasColumn('crm_cases', 'closure_approved_at')) {
                $table->timestamp('closure_approved_at')
                    ->nullable()
                    ->after('closure_approved_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['closure_requested_by_id']);
            $table->dropForeignKeyIfExists(['closure_approved_by_id']);
            $table->dropColumnIfExists('closure_status');
            $table->dropColumnIfExists('closure_requested_by_id');
            $table->dropColumnIfExists('closure_requested_at');
            $table->dropColumnIfExists('closure_approved_by_id');
            $table->dropColumnIfExists('closure_approved_at');
        });
    }
};
