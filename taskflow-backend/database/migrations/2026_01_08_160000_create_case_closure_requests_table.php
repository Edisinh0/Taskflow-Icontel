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
        Schema::create('case_closure_requests', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('case_id')
                ->constrained('crm_cases')
                ->onDelete('cascade');
            
            $table->foreignId('requested_by_user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Status workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');
            
            // Request details
            $table->text('reason')
                ->nullable()
                ->comment('Razón por la cual se solicita el cierre');
            
            $table->integer('completion_percentage')
                ->default(100)
                ->comment('Porcentaje de completitud del caso (0-100)');
            
            // Rejection details
            $table->text('rejection_reason')
                ->nullable()
                ->comment('Razón del rechazo (si aplica)');
            
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Timestamps
            $table->timestamp('reviewed_at')
                ->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('case_id');
            $table->index('requested_by_user_id');
            $table->index('assigned_to_user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_closure_requests');
    }
};
