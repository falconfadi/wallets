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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // The idempotency key from header
            $table->string('request_hash'); // Hash of the request to verify it's identical
            $table->string('type'); // e.g., 'deposit', 'withdrawal'
            $table->morphs('resource'); // Links to wallet/transaction
            $table->json('response'); // Store the successful response
            $table->integer('status_code'); // Store the HTTP status code
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['key', 'resource_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
