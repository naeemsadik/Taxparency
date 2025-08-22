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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('company_name');
            $table->string('password');
            $table->string('vendor_license_number')->unique(); // BPPA License Number
            $table->string('contact_person');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->boolean('is_approved')->default(false); // Approved by BPPA
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // BPPA Officer ID
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
