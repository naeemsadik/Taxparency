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
        Schema::create('winning_bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('bid_id');
            $table->unsignedBigInteger('vendor_id');
            $table->decimal('winning_amount', 15, 2); // The final winning bid amount
            $table->integer('total_votes_received'); // Total votes the winning bid received
            $table->integer('total_yes_votes'); // YES votes received
            $table->integer('total_no_votes'); // NO votes received
            $table->decimal('vote_percentage', 5, 2); // Approval percentage
            $table->datetime('voting_completed_at'); // When voting was completed
            $table->datetime('contract_awarded_at'); // When contract was officially awarded
            $table->unsignedBigInteger('awarded_by'); // BPPA officer who awarded the contract
            $table->text('award_justification')->nullable(); // Justification for the award
            $table->enum('contract_status', ['awarded', 'signed', 'in_progress', 'completed', 'terminated'])->default('awarded');
            $table->datetime('contract_start_date')->nullable();
            $table->datetime('contract_end_date')->nullable();
            $table->decimal('final_contract_value', 15, 2)->nullable(); // May differ from winning_amount due to negotiations
            
            // Blockchain integration fields
            $table->string('blockchain_tx_hash')->nullable(); // Transaction hash for award on blockchain
            $table->text('smart_contract_address')->nullable(); // Smart contract address if deployed
            $table->boolean('is_on_chain')->default(false); // Whether this is stored on blockchain
            $table->json('blockchain_metadata')->nullable(); // Additional blockchain data
            
            // Off-chain backup fields (when blockchain is not available)
            $table->text('offchain_hash')->nullable(); // Hash of the award data for integrity
            $table->boolean('blockchain_sync_pending')->default(false); // Whether sync to blockchain is pending
            $table->datetime('last_blockchain_sync_attempt')->nullable();
            $table->text('blockchain_sync_error')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('procurement_id')->references('id')->on('procurements')->onDelete('cascade');
            $table->foreign('bid_id')->references('id')->on('bids')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('awarded_by')->references('id')->on('bppa_officers');
            
            // Unique constraint - one winning bid per procurement
            $table->unique('procurement_id');
            
            // Indexes for performance
            $table->index(['vendor_id', 'contract_status']);
            $table->index(['contract_status', 'contract_start_date']);
            $table->index(['is_on_chain', 'blockchain_sync_pending']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('winning_bids');
    }
};
