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
        Schema::create('blockchain_backup', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['winning_bid', 'vote', 'procurement', 'tax_return', 'bid', 'fund_request']);
            $table->unsignedBigInteger('record_id'); // ID of the record in the respective table
            $table->string('data_hash', 64); // SHA256 hash for integrity verification
            $table->json('data_json'); // JSON representation of the data
            $table->string('mock_tx_hash')->nullable(); // Mock transaction hash for off-chain records
            $table->boolean('sync_completed')->default(false); // Whether successfully synced to blockchain
            $table->datetime('sync_attempted_at')->nullable(); // Last sync attempt timestamp
            $table->text('sync_error')->nullable(); // Last sync error message
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'record_id']);
            $table->index(['sync_completed', 'sync_attempted_at']);
            $table->index('data_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blockchain_backup');
    }
};
