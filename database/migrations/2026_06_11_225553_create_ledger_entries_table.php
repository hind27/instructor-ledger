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
      Schema::create('ledger_entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
        $table->integer('amount_piastres'); // can be negative for refunds
        $table->enum('type', ['earning', 'refund', 'payout']);
        $table->string('idempotency_key')->unique(); // THE key to prevent double recording
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
