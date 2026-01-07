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
        Schema::create('crm_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->nullable();
            $table->string('subject')->nullable();
            $table->string('sweetcrm_id')->unique()->nullable();
            $table->string('status')->nullable(); // Draft, Negotiation, Confirmed, etc.
            $table->decimal('total_amount', 15, 2)->nullable();
            
            // Relaciones
            $table->foreignId('opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            
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
        Schema::dropIfExists('crm_quotes');
    }
};
