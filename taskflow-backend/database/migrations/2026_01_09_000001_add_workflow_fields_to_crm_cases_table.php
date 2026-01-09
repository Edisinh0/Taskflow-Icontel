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
            // Workflow status: pending, in_validation, approved, rejected, closed
            $table->string('workflow_status')->default('pending')->after('status');

            // Usuario original de ventas que creó el caso
            $table->unsignedBigInteger('original_sales_user_id')->nullable()->after('workflow_status');

            // Cuando se inició la validación
            $table->timestamp('pending_validation_at')->nullable()->after('original_sales_user_id');

            // Usuario que inició la validación
            $table->unsignedBigInteger('validation_initiated_by_id')->nullable()->after('pending_validation_at');

            // Cuando fue aprobado
            $table->timestamp('approved_at')->nullable()->after('validation_initiated_by_id');

            // Usuario que aprobó
            $table->unsignedBigInteger('approved_by_id')->nullable()->after('approved_at');

            // Razón del rechazo si aplica
            $table->text('validation_rejection_reason')->nullable()->after('approved_by_id');

            // Cuando fue rechazado
            $table->timestamp('rejected_at')->nullable()->after('validation_rejection_reason');

            // Usuario que rechazó
            $table->unsignedBigInteger('rejected_by_id')->nullable()->after('rejected_at');

            // Foreign keys
            $table->foreign('original_sales_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validation_initiated_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropForeignKey(['original_sales_user_id']);
            $table->dropForeignKey(['validation_initiated_by_id']);
            $table->dropForeignKey(['approved_by_id']);
            $table->dropForeignKey(['rejected_by_id']);

            $table->dropColumn([
                'workflow_status',
                'original_sales_user_id',
                'pending_validation_at',
                'validation_initiated_by_id',
                'approved_at',
                'approved_by_id',
                'validation_rejection_reason',
                'rejected_at',
                'rejected_by_id',
            ]);
        });
    }
};
