@extends('layouts.app')

@section('title', 'Vendor Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Vendor Dashboard</h1>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Redesigned Statistics Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-2 col-6">
                    <div class="card shadow-sm border-0 h-100 stat-card bg-gradient-primary text-white position-relative">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-2">
                                <span class="fs-2"><i class="bi bi-clipboard-data"></i></span>
                            </div>
                            <div class="text-center">
                                <div class="fw-semibold small text-uppercase opacity-75">Total Bids</div>
                                <div class="fs-3 fw-bold">{{ $stats['total_bids'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card shadow-sm border-0 h-100 stat-card bg-gradient-info text-white position-relative">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-2">
                                <span class="fs-2"><i class="bi bi-lightning-charge"></i></span>
                            </div>
                            <div class="text-center">
                                <div class="fw-semibold small text-uppercase opacity-75">Active Bids</div>
                                <div class="fs-3 fw-bold">{{ $stats['active_bids'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card shadow-sm border-0 h-100 stat-card bg-gradient-warning text-white position-relative">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-2">
                                <span class="fs-2"><i class="bi bi-star"></i></span>
                            </div>
                            <div class="text-center">
                                <div class="fw-semibold small text-uppercase opacity-75">Shortlisted</div>
                                <div class="fs-3 fw-bold">{{ $stats['shortlisted_bids'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card shadow-sm border-0 h-100 stat-card bg-gradient-success text-white position-relative">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-2">
                                <span class="fs-2"><i class="bi bi-trophy"></i></span>
                            </div>
                            <div class="text-center">
                                <div class="fw-semibold small text-uppercase opacity-75">Won Bids</div>
                                <div class="fs-3 fw-bold">{{ $stats['won_bids'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card shadow-sm border-0 h-100 stat-card bg-gradient-secondary text-white position-relative">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-2">
                                <span class="fs-2"><i class="bi bi-briefcase"></i></span>
                            </div>
                            <div class="text-center">
                                <div class="fw-semibold small text-uppercase opacity-75">Ongoing Projects</div>
                                <div class="fs-3 fw-bold">{{ $stats['ongoing_projects'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                .stat-card {
                    min-height: 150px;
                    transition: transform 0.1s;
                }
                .stat-card:hover {
                    transform: translateY(-4px) scale(1.03);
                    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08);
                }
                .bg-gradient-primary {
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
                }
                .bg-gradient-info {
                    background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%) !important;
                }
                .bg-gradient-warning {
                    background: linear-gradient(135deg, #fbbf24 0%, #b45309 100%) !important;
                }
                .bg-gradient-success {
                    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
                }
                .bg-gradient-secondary {
                    background: linear-gradient(135deg, #64748b 0%, #334155 100%) !important;
                }
            </style>
            <!-- End Redesigned Statistics Cards -->

            <div class="row">
                <!-- Open Procurements -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Open Procurements</h5>
                        </div>
                        <div class="card-body">
                            @if($openProcurements->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Budget</th>
                                                <th>Deadline</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($openProcurements as $procurement)
                                                <tr>
                                                    <td>{{ $procurement->title }}</td>
                                                    <td>৳{{ number_format($procurement->estimated_value, 2) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($procurement->submission_deadline)->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $procurement->status == 'open' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($procurement->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if(\Carbon\Carbon::parse($procurement->submission_deadline) > now())
                                                            <button class="btn btn-sm btn-primary" onclick="submitBid({{ $procurement->id }})">
                                                                Submit Bid
                                                            </button>
                                                        @else
                                                            <span class="text-muted">Expired</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted">No open procurements available at the moment.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- My Bids -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">My Recent Bids</h5>
                        </div>
                        <div class="card-body">
                            @if($myBids->count() > 0)
                                @foreach($myBids->take(10) as $bid)
                                    <div class="mb-3">
                                        <h6 class="mb-1">{{ $bid->procurement->title }}</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">৳{{ number_format($bid->bid_amount, 2) }}</small>
                                            <span class="badge bg-{{ 
                                                $bid->status == 'submitted' ? 'primary' : 
                                                ($bid->status == 'shortlisted' ? 'warning' : 
                                                ($bid->status == 'winner' ? 'success' : 'secondary')) 
                                            }}">
                                                {{ ucfirst($bid->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ $bid->created_at->diffForHumans() }}</small>
                                    </div>
                                    <hr>
                                @endforeach
                            @else
                                <p class="text-muted">No bids submitted yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- All My Bids -->
            @if($myBids->count() > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">All My Bids</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Procurement</th>
                                                <th>Bid Amount</th>
                                                <th>Submitted</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($myBids as $bid)
                                                <tr>
                                                    <td>{{ $bid->procurement->title }}</td>
                                                    <td>৳{{ number_format($bid->bid_amount, 2) }}</td>
                                                    <td>{{ $bid->created_at->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ 
                                                            $bid->status == 'submitted' ? 'primary' : 
                                                            ($bid->status == 'shortlisted' ? 'warning' : 
                                                            ($bid->status == 'winner' ? 'success' : 'secondary')) 
                                                        }}">
                                                            {{ ucfirst($bid->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($bid->status == 'submitted' && \Carbon\Carbon::parse($bid->procurement->submission_deadline) > now())
                                                            <button class="btn btn-sm btn-warning" onclick="editBid({{ $bid->id }})">
                                                                Edit
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-info" onclick="viewBid({{ $bid->id }})">
                                                                View
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

            <!-- Ongoing Projects / Extra Fund Requests -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">Ongoing Projects (Winning Bids)</h4>

            @if($winningBids->count() > 0)
                <div class="row">
                    @foreach($winningBids as $winningBid)
                        <div class="col-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $winningBid->procurement->title }}</h5>
                                    <p class="mb-1"><strong>Winning Amount:</strong> ৳{{ number_format($winningBid->winning_amount, 2) }}</p>
                                    <p class="mb-1"><strong>Contract Status:</strong> 
                                        <span class="badge bg-{{ 
                                            $winningBid->contract_status == 'signed' ? 'info' : 
                                            ($winningBid->contract_status == 'in_progress' ? 'success' : 
                                            ($winningBid->contract_status == 'completed' ? 'secondary' : 'warning')) 
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $winningBid->contract_status)) }}
                                        </span>
                                    </p>
                                    <p class="mb-1"><strong>Contract Period:</strong> 
                                        {{ $winningBid->contract_start_date ? $winningBid->contract_start_date->format('d M Y') : 'Not set' }} - 
                                        {{ $winningBid->contract_end_date ? $winningBid->contract_end_date->format('d M Y') : 'Not set' }}
                                    </p>
                                    @if($winningBid->contract_start_date && $winningBid->contract_end_date)
                                        <p class="mb-1"><strong>Progress:</strong> {{ $winningBid->getContractProgress() }}%</p>
                                        <div class="progress mb-2" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $winningBid->getContractProgress() }}%"></div>
                                        </div>
                                    @endif
                                    <p class="mb-1"><strong>Awarded:</strong> {{ $winningBid->contract_awarded_at->format('d M Y') }}</p>
                                    <p class="mb-1"><strong>Vote Percentage:</strong> {{ $winningBid->vote_percentage }}% ({{ $winningBid->total_yes_votes }}/{{ $winningBid->total_votes_received }} votes)</p>

                                    @if(in_array($winningBid->contract_status, ['signed', 'in_progress']))
                                        <!-- Extra fund request form (collapsed by default) -->
                                        <button class="btn btn-sm btn-outline-primary mt-2" type="button" onclick="toggleFundForm({{ $winningBid->id }})">
                                            Request Extra Fund
                                        </button>

                                        <div id="fundFormContainer-{{ $winningBid->id }}" class="mt-3" style="display:none;">
                                            <form class="fund-request-form" data-bid-id="{{ $winningBid->id }}">
                                                <div class="mb-2">
                                                    <label class="form-label small">Requested Amount (৳)</label>
                                                    <input type="number" name="amount" step="0.01" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Reason / Notes</label>
                                                    <textarea name="reason" rows="3" class="form-control form-control-sm" required></textarea>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="submitFundRequest({{ $winningBid->id }}, this)">Submit Request</button>
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleFundForm({{ $winningBid->id }}, true)">Cancel</button>
                                                </div>
                                                <div class="mt-2 small text-muted" id="fundFormMsg-{{ $winningBid->id }}" style="display:none;"></div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">You have no ongoing projects at the moment.</p>
            @endif
        </div>
    </div>
</div>

<script>
// Bid submission modal
function submitBid(procurementId) {
    // Get procurement details
    const procurement = openProcurements.find(p => p.id === procurementId);
    if (!procurement) return;
    
    // Show bid submission modal
    const modal = new bootstrap.Modal(document.getElementById('bidSubmissionModal'));
    document.getElementById('procurementId').value = procurementId;
    document.getElementById('procurementTitle').textContent = procurement.title;
    document.getElementById('procurementBudget').textContent = '৳' + procurement.estimated_value.toLocaleString();
    document.getElementById('procurementDeadline').textContent = new Date(procurement.submission_deadline).toLocaleDateString();
    modal.show();
}

function submitBidForm() {
    const form = document.getElementById('bidSubmissionForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = document.getElementById('submitBidBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    fetch('/api/v1/vendor/bids/submit', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bid submitted successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit bid. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function editBid(bidId) {
    // Get bid details and show edit modal
    fetch(`/api/v1/vendor/bids/${bidId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bid = data.bid;
                document.getElementById('editBidId').value = bidId;
                document.getElementById('editBidAmount').value = bid.bid_amount;
                document.getElementById('editTechnicalProposal').value = bid.technical_proposal;
                document.getElementById('editCompletionDays').value = bid.completion_days;
                document.getElementById('editAdditionalNotes').value = bid.additional_notes || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editBidModal'));
                modal.show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load bid details.');
        });
}

function updateBid() {
    const form = document.getElementById('editBidForm');
    const formData = new FormData(form);
    const bidId = document.getElementById('editBidId').value;
    
    // Show loading state
    const submitBtn = document.getElementById('updateBidBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    
    fetch(`/api/v1/vendor/bids/${bidId}/edit`, {
        method: 'PUT',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Bid updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update bid. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function viewBid(bidId) {
    fetch(`/api/v1/vendor/bids/${bidId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bid = data.bid;
                
                // Populate view modal
                document.getElementById('viewBidTitle').textContent = bid.procurement.title;
                document.getElementById('viewBidAmount').textContent = '৳' + bid.bid_amount.toLocaleString();
                document.getElementById('viewTechnicalProposal').textContent = bid.technical_proposal;
                document.getElementById('viewCompletionDays').textContent = bid.completion_days + ' days';
                document.getElementById('viewAdditionalNotes').textContent = bid.additional_notes || 'None';
                document.getElementById('viewBidStatus').textContent = bid.status.charAt(0).toUpperCase() + bid.status.slice(1);
                document.getElementById('viewBidCreated').textContent = new Date(bid.created_at).toLocaleString();
                
                if (bid.winning_record) {
                    document.getElementById('winningRecordSection').style.display = 'block';
                    document.getElementById('viewWinningAmount').textContent = '৳' + bid.winning_record.winning_amount.toLocaleString();
                    document.getElementById('viewContractStatus').textContent = bid.winning_record.contract_status.replace('_', ' ').toUpperCase();
                } else {
                    document.getElementById('winningRecordSection').style.display = 'none';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('viewBidModal'));
                modal.show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load bid details.');
        });
}

function toggleFundForm(bidId, hide = false) {
    const container = document.getElementById(`fundFormContainer-${bidId}`);
    if (!container) return;
    if (hide) { container.style.display = 'none'; return; }
    container.style.display = container.style.display === 'none' ? 'block' : 'none';
}

async function submitFundRequest(bidId, btn) {
    const form = document.querySelector(`.fund-request-form[data-bid-id="${bidId}"]`);
    if (!form) return;
    const amountEl = form.querySelector('input[name="amount"]');
    const reasonEl = form.querySelector('textarea[name="reason"]');
    const msgEl = document.getElementById(`fundFormMsg-${bidId}`);

    const amount = parseFloat(amountEl.value);
    const reason = reasonEl.value.trim();

    if (!amount || amount <= 0) {
        alert('Please enter a valid requested amount.');
        return;
    }
    if (!reason) {
        alert('Please enter a reason for the request.');
        return;
    }

    btn.disabled = true;
    msgEl.style.display = 'none';

    try {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

        const res = await fetch('/api/v1/vendor/fund-requests', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({
                bid_id: bidId,
                amount: amount,
                reason: reason
            })
        });

        const data = await res.json();

        if (!res.ok) {
            msgEl.style.display = 'block';
            msgEl.className = 'mt-2 text-danger small';
            msgEl.textContent = data.message || 'Failed to submit request.';
            btn.disabled = false;
            return;
        }

        msgEl.style.display = 'block';
        msgEl.className = 'mt-2 text-success small';
        msgEl.textContent = data.message || 'Fund request submitted.';

        // Optionally disable the form after success
        amountEl.disabled = true;
        reasonEl.disabled = true;
        btn.disabled = true;

    } catch (err) {
        console.error(err);
        msgEl.style.display = 'block';
        msgEl.className = 'mt-2 text-danger small';
        msgEl.textContent = 'Request failed. Check console for details.';
        btn.disabled = false;
    }
}

// Load open procurements data for modal
let openProcurements = @json($openProcurements);
</script>

<!-- Bid Submission Modal -->
<div class="modal fade" id="bidSubmissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Bid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Procurement:</strong> <span id="procurementTitle"></span><br>
                    <strong>Budget:</strong> <span id="procurementBudget"></span><br>
                    <strong>Deadline:</strong> <span id="procurementDeadline"></span>
                </div>
                
                <form id="bidSubmissionForm">
                    <input type="hidden" id="procurementId" name="procurement_id">
                    
                    <div class="mb-3">
                        <label for="bidAmount" class="form-label">Bid Amount (৳)</label>
                        <input type="number" class="form-control" id="bidAmount" name="bid_amount" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="technicalProposal" class="form-label">Technical Proposal</label>
                        <textarea class="form-control" id="technicalProposal" name="technical_proposal" rows="4" required 
                                  placeholder="Describe your technical approach, methodology, and implementation plan..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="completionDays" class="form-label">Completion Time (Days)</label>
                        <input type="number" class="form-control" id="completionDays" name="completion_days" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="costingDocument" class="form-label">Costing Document (Optional)</label>
                        <input type="file" class="form-control" id="costingDocument" name="costing_document" accept=".pdf,.doc,.docx">
                        <small class="text-muted">PDF, DOC, or DOCX files only. Max 10MB.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="additionalNotes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="additionalNotes" name="additional_notes" rows="3" 
                                  placeholder="Any additional information, warranties, or special considerations..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitBidBtn" onclick="submitBidForm()">Submit Bid</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bid Modal -->
<div class="modal fade" id="editBidModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBidForm">
                    <input type="hidden" id="editBidId" name="bid_id">
                    
                    <div class="mb-3">
                        <label for="editBidAmount" class="form-label">Bid Amount (৳)</label>
                        <input type="number" class="form-control" id="editBidAmount" name="bid_amount" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTechnicalProposal" class="form-label">Technical Proposal</label>
                        <textarea class="form-control" id="editTechnicalProposal" name="technical_proposal" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCompletionDays" class="form-label">Completion Time (Days)</label>
                        <input type="number" class="form-control" id="editCompletionDays" name="completion_days" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editAdditionalNotes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="editAdditionalNotes" name="additional_notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateBidBtn" onclick="updateBid()">Update Bid</button>
            </div>
        </div>
    </div>
</div>

<!-- View Bid Modal -->
<div class="modal fade" id="viewBidModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bid Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 id="viewBidTitle" class="mb-3"></h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Bid Amount:</strong> <span id="viewBidAmount"></span></p>
                        <p><strong>Status:</strong> <span id="viewBidStatus"></span></p>
                        <p><strong>Completion Time:</strong> <span id="viewCompletionDays"></span></p>
                        <p><strong>Submitted:</strong> <span id="viewBidCreated"></span></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Technical Proposal:</strong>
                    <p id="viewTechnicalProposal" class="mt-2"></p>
                </div>
                
                <div class="mb-3">
                    <strong>Additional Notes:</strong>
                    <p id="viewAdditionalNotes" class="mt-2"></p>
                </div>
                
                <div id="winningRecordSection" class="mb-3" style="display: none;">
                    <div class="alert alert-success">
                        <h6>Winning Bid Information</h6>
                        <p><strong>Winning Amount:</strong> <span id="viewWinningAmount"></span></p>
                        <p><strong>Contract Status:</strong> <span id="viewContractStatus"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
