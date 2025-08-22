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
        Schema::create('tax_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citizen_id');
            $table->string('fiscal_year'); // e.g., "2023-24"
            $table->string('ipfs_hash'); // IPFS CID for the PDF file
            $table->decimal('total_income', 15, 2); // Total income in the tax session
            $table->decimal('total_cost', 15, 2); // Total cost/tax owed in the tax session
            $table->string('blockchain_tx_hash')->nullable(); // Transaction hash on private blockchain
            $table->string('status')->default('pending'); // pending, approved, declined
            $table->unsignedBigInteger('reviewed_by')->nullable(); // NBR Officer ID
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->timestamps();
            
            $table->foreign('citizen_id')->references('id')->on('citizens');
            $table->unique(['citizen_id', 'fiscal_year']); // One return per citizen per fiscal year
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_returns');
    }
};
