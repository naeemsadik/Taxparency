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
        Schema::table('bids', function (Blueprint $table) {
            $table->string('blockchain_tx_hash', 66)->nullable()->after('votes_no');
            $table->index('blockchain_tx_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropIndex(['blockchain_tx_hash']);
            $table->dropColumn('blockchain_tx_hash');
        });
    }
};
