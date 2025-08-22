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
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('procurement_id')->unique(); // Government procurement reference number
            $table->decimal('estimated_value', 15, 2); // Estimated project value
            $table->string('category'); // Infrastructure, IT, Services, etc.
            $table->date('submission_deadline');
            $table->date('project_start_date')->nullable();
            $table->date('project_end_date')->nullable();
            $table->string('status')->default('open'); // open, bid_evaluation, shortlisted, voting, completed, cancelled
            $table->unsignedBigInteger('created_by'); // BPPA Officer ID
            $table->string('requirements_document')->nullable(); // IPFS hash for requirements PDF
            $table->string('blockchain_tx_hash')->nullable(); // Transaction hash for shortlisted bids on public blockchain
            $table->timestamp('voting_ends_at')->nullable();
            $table->unsignedBigInteger('winning_bid_id')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('bppa_officers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
