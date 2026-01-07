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
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sweetcrm_id')->unique()->nullable();
            $table->string('sales_stage')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency')->default('CLP');
            $table->date('expected_closed_date')->nullable();
            
            // Relaciones
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('sweetcrm_assigned_user_id')->nullable();
            
            $table->text('description')->nullable();
            $table->dateTime('sweetcrm_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
