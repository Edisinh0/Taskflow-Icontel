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
            // Usuario original de ventas que creó la tarea
            $table->unsignedBigInteger('original_sales_user_id')->nullable()->after('created_by');

            // Cuando se delegó a operaciones
            $table->timestamp('delegated_to_ops_at')->nullable()->after('original_sales_user_id');

            // Usuario a quien se delegó
            $table->unsignedBigInteger('delegated_to_user_id')->nullable()->after('delegated_to_ops_at');

            // Estado de delegación: pending, delegated, completed, rejected
            $table->string('delegation_status')->default('pending')->after('delegated_to_user_id');

            // Razón de delegación
            $table->text('delegation_reason')->nullable()->after('delegation_status');

            // Cuando se completó la tarea delegada
            $table->timestamp('delegation_completed_at')->nullable()->after('delegation_reason');

            // Foreign keys
            $table->foreign('original_sales_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('delegated_to_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignKey(['original_sales_user_id']);
            $table->dropForeignKey(['delegated_to_user_id']);

            $table->dropColumn([
                'original_sales_user_id',
                'delegated_to_ops_at',
                'delegated_to_user_id',
                'delegation_status',
                'delegation_reason',
                'delegation_completed_at',
            ]);
        });
    }
};
