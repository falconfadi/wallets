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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Unique transfer reference
            $table->foreignId('from_wallet_id')->constrained('wallets')->onDelete('restrict');
            $table->foreignId('to_wallet_id')->constrained('wallets')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['from_wallet_id', 'to_wallet_id']);
            $table->index(['created_at']);
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
