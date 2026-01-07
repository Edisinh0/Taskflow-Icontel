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
            // Add created_by column to track who created the task
            $table->foreignId('created_by')->nullable()->after('assignee_id')->constrained('users')->onDelete('set null');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['created_by_id']);
            $table->dropIndex(['created_by_id']);
            $table->dropColumn('created_by');
        });
    }
};
