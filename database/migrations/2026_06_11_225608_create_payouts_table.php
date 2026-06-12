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
        Schema::create('payouts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
        $table->unsignedInteger('amount_piastres');
        $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'unknown']);
        $table->string('provider_reference')->nullable(); // ID returned by payment provider
        $table->string('idempotency_key')->unique();
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
