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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('vendor_id');
            $table->decimal('bid_amount', 15, 2); // Total cost according to vendor
            $table->text('technical_proposal'); // Brief description
            $table->string('costing_document'); // IPFS hash for detailed costing PDF
            $table->integer('completion_days'); // Project completion time in days
            $table->text('additional_notes')->nullable();
            $table->string('status')->default('submitted'); // submitted, shortlisted, rejected, winning
            $table->boolean('is_shortlisted')->default(false);
            $table->timestamp('shortlisted_at')->nullable();
            $table->unsignedBigInteger('shortlisted_by')->nullable(); // BPPA Officer ID
            $table->integer('votes_yes')->default(0); // Public votes
            $table->integer('votes_no')->default(0); // Public votes
            $table->timestamps();
            
            $table->foreign('procurement_id')->references('id')->on('procurements');
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->unique(['procurement_id', 'vendor_id']); // One bid per vendor per procurement
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
