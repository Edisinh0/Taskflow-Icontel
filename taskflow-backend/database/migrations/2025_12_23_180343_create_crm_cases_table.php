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
        Schema::create('crm_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->nullable();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->string('type')->nullable();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('sweetcrm_id')->nullable()->unique()->index();
            $table->string('sweetcrm_account_id')->nullable()->index();
            $table->string('sweetcrm_assigned_user_id')->nullable()->index();
            $table->timestamp('sweetcrm_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_cases');
    }
};
