<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for PRD v1.0 Enhanced Features
 * 
 * Creates tables for:
 * - Fund requests and approvals
 * - National ledger cache
 * - IPFS file tracking
 * - Enhanced procurement features
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fund Requests table
        Schema::create('fund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 50)->unique();
            $table->string('procurement_id', 50);
            $table->string('vendor_id', 100);
            $table->string('company_name', 200);
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('reason');
            $table->text('justification');
            $table->string('supporting_docs_ipfs_hash', 100)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'funded', 'cancelled'])->default('pending');
            $table->string('bppa_officer_id', 100)->nullable();
            $table->text('bppa_comments')->nullable();
            $table->string('disbursement_ref', 100)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->string('blockchain_tx_hash', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['procurement_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        // National Ledger Cache table for performance
        Schema::create('national_ledger_cache', function (Blueprint $table) {
            $table->id();
            $table->enum('entry_type', ['revenue', 'expense']);
            $table->string('reference_id', 100); // TIIN for revenue, procurement_id for expense
            $table->decimal('amount', 15, 2);
            $table->string('fiscal_year', 10)->nullable();
            $table->string('source_type', 50); // tax_return, procurement_award, fund_request
            $table->string('approver_id', 100);
            $table->string('approver_name', 200);
            $table->string('approver_type', 50); // nbr_officer, bppa_officer
            $table->text('description')->nullable();
            $table->string('blockchain_tx_hash', 100);
            $table->string('blockchain_network', 50); // private, public
            $table->timestamp('blockchain_timestamp');
            $table->boolean('is_verified')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['entry_type', 'fiscal_year']);
            $table->index(['source_type', 'blockchain_timestamp']);
            $table->index(['approver_id', 'entry_type']);
        });

        // IPFS Files tracking table
        Schema::create('ipfs_files', function (Blueprint $table) {
            $table->id();
            $table->string('ipfs_hash', 100)->unique();
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size')->nullable();
            $table->enum('file_type', ['tax_return', 'supporting_document', 'costing_document', 'other']);
            $table->string('uploaded_by_type', 50); // citizen, vendor, officer
            $table->string('uploaded_by_id', 100);
            $table->string('related_entity_type', 50)->nullable(); // tax_return, fund_request, procurement
            $table->string('related_entity_id', 100)->nullable();
            $table->string('upload_method', 50); // local_node, pinata
            $table->boolean('is_pinned')->default(false);
            $table->string('gateway_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['file_type', 'uploaded_at']);
            $table->index(['uploaded_by_type', 'uploaded_by_id']);
            $table->index(['related_entity_type', 'related_entity_id']);
        });

        // Enhanced Procurements table (additional fields for PRD features)
        Schema::create('procurement_enhancements', function (Blueprint $table) {
            $table->id();
            $table->string('procurement_id', 50)->unique();
            $table->enum('shortlisting_method', ['L1', 'QCBS'])->default('L1');
            $table->integer('max_bids')->default(10);
            $table->integer('shortlist_count')->default(3);
            $table->decimal('qcbs_cost_weight', 5, 2)->default(70.00);
            $table->decimal('qcbs_quality_weight', 5, 2)->default(30.00);
            $table->text('technical_requirements')->nullable();
            $table->enum('status', [
                'created', 'published', 'bids_received', 'shortlist_done', 
                'voting_active', 'awarded', 'completed', 'cancelled'
            ])->default('created');
            $table->timestamp('bid_deadline')->nullable();
            $table->timestamp('voting_deadline')->nullable();
            $table->integer('winning_bid_index')->nullable();
            $table->decimal('awarded_amount', 15, 2)->nullable();
            $table->string('awarded_vendor_id', 100)->nullable();
            $table->string('bppa_officer_id', 100);
            $table->json('shortlisted_bids')->nullable(); // Array of bid indices
            $table->json('voting_results')->nullable();
            $table->string('blockchain_tx_hash', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'bid_deadline']);
            $table->index(['bppa_officer_id', 'status']);
            $table->index(['awarded_vendor_id']);
        });

        // Bid Submissions table for enhanced tracking
        Schema::create('bid_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('procurement_id', 50);
            $table->string('vendor_id', 100);
            $table->string('company_name', 200);
            $table->decimal('bid_amount', 15, 2);
            $table->text('technical_proposal');
            $table->string('costing_document_ipfs_hash', 100)->nullable();
            $table->integer('completion_days');
            $table->decimal('quality_score', 5, 2)->nullable(); // For QCBS (0-100)
            $table->decimal('combined_score', 8, 2)->nullable(); // For QCBS
            $table->boolean('is_shortlisted')->default(false);
            $table->boolean('is_winner')->default(false);
            $table->text('bppa_remarks')->nullable();
            $table->string('blockchain_tx_hash', 100)->nullable();
            $table->timestamp('submitted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['procurement_id', 'is_shortlisted']);
            $table->index(['vendor_id', 'submitted_at']);
            $table->index(['is_winner']);
        });

        // Citizen Voting Records table
        Schema::create('citizen_votes', function (Blueprint $table) {
            $table->id();
            $table->string('procurement_id', 50);
            $table->unsignedBigInteger('bid_id'); // Reference to bid_submissions.id
            $table->string('citizen_id', 100); // TIIN or citizen identifier
            $table->boolean('vote'); // true for YES, false for NO
            $table->string('blockchain_tx_hash', 100)->nullable();
            $table->timestamp('voted_at');
            $table->string('voting_session_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['procurement_id', 'bid_id', 'citizen_id']);
            $table->index(['procurement_id', 'voted_at']);
            $table->index(['citizen_id']);
            $table->foreign('bid_id')->references('id')->on('bid_submissions')->onDelete('cascade');
        });

        // System Configuration table for blockchain and IPFS settings
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value');
            $table->string('config_type', 50)->default('string'); // string, json, boolean, integer
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->string('category', 50)->default('general'); // blockchain, ipfs, api, etc.
            $table->timestamp('last_updated_at');
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizen_votes');
        Schema::dropIfExists('bid_submissions');
        Schema::dropIfExists('procurement_enhancements');
        Schema::dropIfExists('ipfs_files');
        Schema::dropIfExists('national_ledger_cache');
        Schema::dropIfExists('fund_requests');
        Schema::dropIfExists('system_config');
    }
};
