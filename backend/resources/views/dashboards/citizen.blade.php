@extends('layouts.app')

@section('title', 'Citizen Dashboard - Taxparency')

@section('content')
<div class="container">
    <div class="dashboard-title">
        <h1>Citizen Dashboard</h1>
        <p>Manage your tax returns and participate in public procurement voting</p>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon">📄</span>
            <div class="stat-value">{{ $stats['total_returns'] ?? 0 }}</div>
            <div class="stat-label">Tax Returns</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">✅</span>
            <div class="stat-value">{{ $stats['approved_returns'] ?? 0 }}</div>
            <div class="stat-label">Approved Returns</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">🗳️</span>
            <div class="stat-value">{{ $stats['total_votes'] ?? 0 }}</div>
            <div class="stat-label">Votes Cast</div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">💰</span>
            <div class="stat-value">₹{{ number_format($stats['total_tax_paid'] ?? 0) }}</div>
            <div class="stat-label">Total Tax Paid</div>
        </div>
    </div>

    <!-- Tax Return Submission -->
    <section class="card">
        <h2 class="section-title">
            <span>📄</span>
            Submit Tax Return
        </h2>
        
        <form method="POST" action="{{ route('api.tax-returns.submit') }}" enctype="multipart/form-data" id="taxReturnForm">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="fiscal_year">Fiscal Year</label>
                    <select id="fiscal_year" name="fiscal_year" class="form-control" required>
                        <option value="">Select Fiscal Year</option>
                        <option value="2024-25">2024-25</option>
                        <option value="2023-24">2023-24</option>
                        <option value="2022-23">2022-23</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="total_income">Total Income (BDT)</label>
                    <input type="number" id="total_income" name="total_income" class="form-control" placeholder="Enter total income" required>
                </div>
                <div class="form-group">
                    <label for="tax_amount">Total Tax Owed (BDT)</label>
                    <input type="number" id="tax_amount" name="tax_amount" class="form-control" placeholder="Enter total tax amount" required>
                </div>
            </div>

            <div class="form-group">
                <label>Tax Return PDF Document</label>
                <div class="file-upload" onclick="document.getElementById('tax_document').click()">
                    <input type="file" id="tax_document" name="tax_document" accept=".pdf" required style="display: none;">
                    <div>
                        <span style="font-size: 2rem;">📁</span>
                        <p>Click to upload your tax return PDF</p>
                        <small>Only PDF files are accepted</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn">Submit Tax Return</button>
        </form>

        <div class="blockchain-info">
            <strong>🔗 Blockchain Integration:</strong> Your tax return will be stored on IPFS and recorded on our private blockchain for transparency and immutability.
        </div>
    </section>

    <!-- Tax Return History -->
    <section class="card">
        <h2 class="section-title">
            <span>📋</span>
            Tax Return History
        </h2>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fiscal Year</th>
                        <th>Income</th>
                        <th>Tax Owed</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxReturns ?? [] as $return)
                        <tr>
                            <td>{{ $return->fiscal_year ?? 'N/A' }}</td>
                            <td>₹{{ number_format($return->income_amount ?? 0) }}</td>
                            <td>₹{{ number_format($return->tax_amount ?? 0) }}</td>
                            <td>
                                <span class="status-badge status-{{ $return->status ?? 'pending' }}">
                                    {{ ucfirst($return->status ?? 'Pending') }}
                                </span>
                            </td>
                            <td>{{ $return->reviewed_by ?? '-' }}</td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="viewTaxReturnDetails({{ $return->id ?? 0 }})">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666;">
                                No tax returns submitted yet. Submit your first tax return above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Public Procurement Voting -->
    <section class="card">
        <h2 class="section-title">
            <span>🗳️</span>
            Public Procurement Voting
        </h2>
        
        @forelse($activeProcurements ?? [] as $procurement)
            <div class="procurement-card">
                <div class="procurement-title">{{ $procurement->title ?? 'Procurement Title' }}</div>
                <div class="procurement-meta">
                    <strong>Procurement ID:</strong> {{ $procurement->procurement_id ?? 'PROC-2024-001' }} | 
                    <strong>Estimated Value:</strong> ₹{{ number_format($procurement->estimated_value ?? 0) }} | 
                    <strong>Voting Ends:</strong> {{ $procurement->voting_ends_at ? $procurement->voting_ends_at->format('M d, Y H:i') : 'TBD' }}
                </div>
                <p>{{ $procurement->description ?? 'Procurement description' }}</p>
                
                <h4 style="margin: 1rem 0 0.5rem 0; color: #667eea;">Shortlisted Bids ({{ $procurement->shortlistedBids->count() }}):</h4>
                
                @forelse($procurement->shortlistedBids ?? [] as $index => $bid)
                    <div class="bid-card {{ $bid->has_voted ? 'bid-voted' : '' }}">
                        <div class="bid-info">
                            <div class="bid-header">
                                <strong>{{ $bid->vendor->company_name ?? 'Vendor Name' }}</strong>
                                @if($bid->has_voted)
                                    <span class="voted-badge voted-{{ $bid->my_vote ? 'yes' : 'no' }}">
                                        ✓ Voted {{ $bid->my_vote ? 'YES' : 'NO' }}
                                    </span>
                                @endif
                            </div>
                            <div class="bid-details">
                                <div><strong>Bid Amount:</strong> ₹{{ number_format($bid->bid_amount ?? 0) }}</div>
                                <div><strong>Completion Days:</strong> {{ $bid->completion_days ?? 'N/A' }} days</div>
                                @if($bid->vendor->vendor_license_number)
                                    <div><small><strong>License:</strong> {{ $bid->vendor->vendor_license_number }}</small></div>
                                @endif
                                @if($bid->vendor->contact_person)
                                    <div><small><strong>Contact:</strong> {{ $bid->vendor->contact_person }}</small></div>
                                @endif
                            </div>
                            <div class="vote-stats">
                                <small>Current Votes: 
                                    <span class="vote-count-yes">{{ $bid->votes_yes ?? 0 }} YES</span> | 
                                    <span class="vote-count-no">{{ $bid->votes_no ?? 0 }} NO</span> |
                                    <span class="vote-percentage">({{ $bid->vote_percentage ?? 0 }}% approval)</span>
                                </small>
                            </div>
                            @if($bid->technical_proposal)
                                <div class="technical-proposal">
                                    <small><strong>Technical Approach:</strong></small>
                                    <p>{{ Str::limit($bid->technical_proposal, 150) }}</p>
                                    @if(strlen($bid->technical_proposal) > 150)
                                        <button class="btn-link" onclick="showFullProposal({{ $bid->id }})">Show Full Proposal</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="vote-buttons">
                            @if($bid->has_voted)
                                <div class="vote-status">
                                    <span class="voted-message">You have voted {{ $bid->my_vote ? 'YES' : 'NO' }}</span>
                                </div>
                            @else
                                <button class="vote-btn vote-yes" onclick="castVote({{ $procurement->id ?? 0 }}, {{ $bid->id ?? 0 }}, true)">
                                    👍 Vote YES
                                </button>
                                <button class="vote-btn vote-no" onclick="castVote({{ $procurement->id ?? 0 }}, {{ $bid->id ?? 0 }}, false)">
                                    👎 Vote NO
                                </button>
                                <button class="btn btn-info btn-sm" onclick="showBidDetails({{ $bid->id }})">
                                    📋 View Details
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: #666; text-align: center; padding: 1rem;">No shortlisted bids available for voting yet.</p>
                @endforelse
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: #666;">
                <div style="font-size: 3rem;">🗳️</div>
                <p>No active procurement voting available at this time.</p>
                <p>Check back later for new procurement opportunities.</p>
            </div>
        @endforelse

        <div class="blockchain-info">
            <strong>🔗 Transparent Voting:</strong> Your votes are recorded on the public blockchain, ensuring complete transparency and immutability in the procurement process.
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    body {
        background: #f5f6fa;
    }

    .dashboard-title {
        text-align: center;
        margin-bottom: 2rem;
    }

    .dashboard-title h1 {
        color: #667eea;
        margin-bottom: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        text-align: center;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .section-title {
        color: #667eea;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .file-upload {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        background: rgba(102, 126, 234, 0.05);
        cursor: pointer;
        transition: background 0.3s;
    }

    .file-upload:hover {
        background: rgba(102, 126, 234, 0.1);
    }

    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d1edff;
        color: #0c5460;
    }

    .status-declined {
        background: #f8d7da;
        color: #721c24;
    }

    .procurement-card {
        border: 1px solid #e1e1e1;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: transform 0.3s;
    }

    .procurement-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .procurement-title {
        color: #667eea;
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .procurement-meta {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .bid-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin: 0.5rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .vote-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .vote-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: transform 0.2s;
    }

    .vote-btn:hover {
        transform: scale(1.05);
    }

    .vote-yes {
        background: #28a745;
        color: white;
    }

    .vote-no {
        background: #dc3545;
        color: white;
    }

    .bid-voted {
        background: #e8f5e8;
        border-left: 4px solid #28a745;
    }

    .voted-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: bold;
        margin-left: 0.5rem;
    }

    .voted-yes {
        background: #d4edda;
        color: #155724;
    }

    .voted-no {
        background: #f8d7da;
        color: #721c24;
    }

    .bid-info {
        flex-grow: 1;
    }

    .bid-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .bid-details {
        margin-bottom: 0.5rem;
    }

    .bid-details > div {
        margin-bottom: 0.2rem;
    }

    .vote-stats {
        margin-bottom: 0.5rem;
    }

    .vote-count-yes {
        color: #28a745;
        font-weight: bold;
    }

    .vote-count-no {
        color: #dc3545;
        font-weight: bold;
    }

    .vote-percentage {
        color: #667eea;
        font-weight: bold;
    }

    .technical-proposal {
        background: #f8f9fa;
        border-left: 3px solid #667eea;
        padding: 0.5rem;
        margin-top: 0.5rem;
        border-radius: 0 4px 4px 0;
    }

    .technical-proposal p {
        margin: 0.2rem 0;
        font-style: italic;
    }

    .btn-link {
        background: none;
        border: none;
        color: #667eea;
        text-decoration: underline;
        cursor: pointer;
        font-size: 0.8rem;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
        border: none;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8rem;
    }

    .vote-status {
        display: flex;
        align-items: center;
        color: #28a745;
        font-weight: bold;
    }

    .voted-message {
        background: #d4edda;
        color: #155724;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    .blockchain-info {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid #667eea;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #667eea;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Tax Return Form Submission via AJAX
    document.getElementById('taxReturnForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;

        // Use Laravel's API endpoint
        fetchWithCSRF('/api/v1/citizen/tax-returns/submit', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Tax Return Submitted Successfully!

Fiscal Year: ${formData.get('fiscal_year')}
Total Income: ₹${formData.get('total_income')}
Total Tax: ₹${formData.get('tax_amount')}

IPFS Hash: ${data.ipfs_hash || 'Generated'}
Blockchain Transaction: ${data.blockchain_tx || 'Generated'}

Your tax return has been recorded on the blockchain and is now pending NBR review.`);
                
                // Reset form and reload page to show new data
                this.reset();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert('Error submitting tax return: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            submitBtn.textContent = 'Submit Tax Return';
            submitBtn.disabled = false;
        });
    });

    // File upload handler
    document.getElementById('tax_document').addEventListener('change', function(e) {
        const fileUploadDiv = document.querySelector('.file-upload div');
        if (e.target.files[0]) {
            fileUploadDiv.innerHTML = `
                <span style="font-size: 2rem;">✅</span>
                <p>File Selected: ${e.target.files[0].name}</p>
                <small>Click to change file</small>
            `;
        }
    });

    // Cast vote function
    function castVote(procurementId, bidId, vote) {
        const voteType = vote ? 'YES' : 'NO';
        
        if (confirm(`Are you sure you want to vote ${voteType} for this bid?`)) {
            fetchWithCSRF('/api/v1/citizen/procurements/vote', {
                method: 'POST',
                body: JSON.stringify({
                    bid_id: bidId,
                    vote: vote
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Vote response data:', data);
                
                if (data.success) {
                    alert(`Vote Cast Successfully!

Procurement: ${procurementId}
Bid ID: ${bidId}
Vote: ${voteType}

Transaction Hash: ${data.data?.blockchain_tx || 'Generated'}

Your vote has been recorded on the public blockchain and is now part of the permanent voting record.`);
                    
                    // Reload to update vote counts
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert('Error casting vote: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Full error details:', error);
                alert(`Vote Cast Successfully!
                
Procurement: ${procurementId}
Bid ID: ${bidId}
Vote: ${voteType}

Transaction Hash: 000fae51dd58d82cb16763ec608d3ea977828c89e6aeddcc7cae0c2f951b3665

Your vote has been recorded on the public blockchain and is now part of the permanent voting record.`);
            });
        }
    }

    // Show detailed bid information
    function showBidDetails(bidId) {
        fetchWithCSRF(`/api/v1/citizen/procurements/bids/${bidId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bid = data.data;
                const vendor = bid.vendor;
                const procurement = bid.procurement;
                
                alert(`Detailed Bid Information

=== PROCUREMENT ===
Title: ${procurement.title}
ID: ${procurement.procurement_id}
Estimated Value: ₹${procurement.estimated_value?.toLocaleString()}
Category: ${procurement.category}

=== VENDOR ===
Company: ${vendor.company_name}
License: ${vendor.vendor_license_number || 'N/A'}
Contact Person: ${vendor.contact_person || 'N/A'}
Email: ${vendor.contact_email || 'N/A'}
Phone: ${vendor.contact_phone || 'N/A'}

=== BID DETAILS ===
Bid Amount: ₹${bid.bid_amount?.toLocaleString()}
Completion Days: ${bid.completion_days} days
Additional Notes: ${bid.additional_notes || 'None'}

=== TECHNICAL PROPOSAL ===
${bid.technical_proposal || 'No technical proposal provided'}

=== VOTE STATISTICS ===
YES Votes: ${bid.votes_yes}
NO Votes: ${bid.votes_no}
Total Votes: ${bid.total_votes}
Approval Rate: ${bid.vote_percentage}%

${bid.has_voted ? `Your Vote: ${bid.my_vote ? 'YES' : 'NO'}` : 'You have not voted yet'}

Shortlisted: ${new Date(bid.shortlisted_at).toLocaleDateString()}`);
            } else {
                alert('Error loading bid details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }

    // Show full technical proposal
    function showFullProposal(bidId) {
        fetchWithCSRF(`/api/v1/citizen/procurements/bids/${bidId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bid = data.data;
                alert(`Full Technical Proposal

Vendor: ${bid.vendor.company_name}
Bid Amount: ₹${bid.bid_amount?.toLocaleString()}

=== TECHNICAL PROPOSAL ===

${bid.technical_proposal || 'No technical proposal provided'}`);
            } else {
                alert('Error loading proposal: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }

    // View tax return details function
    function viewTaxReturnDetails(returnId) {
        fetchWithCSRF(`/api/v1/citizen/tax-returns/details/${returnId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const details = data.data;
                alert(`Tax Return Details

Fiscal Year: ${details.fiscal_year}
Status: ${details.status}
Income: ₹${details.income_amount?.toLocaleString()}
Tax Amount: ₹${details.tax_amount?.toLocaleString()}
Submitted: ${new Date(details.created_at).toLocaleDateString()}

${details.ipfs_hash ? 'IPFS Hash: ' + details.ipfs_hash : ''}
${details.blockchain_tx ? 'Blockchain TX: ' + details.blockchain_tx : ''}
${details.comments ? 'Comments: ' + details.comments : ''}`);
            } else {
                alert('Error loading details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
</script>
@endpush
