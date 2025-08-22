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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citizen_id');
            $table->unsignedBigInteger('bid_id');
            $table->boolean('vote'); // true for yes, false for no
            $table->string('blockchain_tx_hash')->nullable(); // Transaction hash on public blockchain
            $table->timestamps();
            
            $table->foreign('citizen_id')->references('id')->on('citizens');
            $table->foreign('bid_id')->references('id')->on('bids');
            $table->unique(['citizen_id', 'bid_id']); // One vote per citizen per bid
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
