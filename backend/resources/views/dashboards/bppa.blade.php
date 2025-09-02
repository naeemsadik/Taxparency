@extends('layouts.app')

@section('title', 'BPPA Officer Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">BPPA Officer Dashboard</h1>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Procurements</h5>
                            <h2 class="mb-0">{{ $stats['total_procurements'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Open</h5>
                            <h2 class="mb-0">{{ $stats['open_procurements'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Bidding</h5>
                            <h2 class="mb-0">{{ $stats['bidding_procurements'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Completed</h5>
                            <h2 class="mb-0">{{ $stats['completed_procurements'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- My Procurements -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">My Procurements</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProcurementModal">
                                <i class="fas fa-plus"></i> Create New
                            </button>
                        </div>
                        <div class="card-body">
                            @if($myProcurements->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Budget</th>
                                                <th>Deadline</th>
                                                <th>Bids</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($myProcurements as $procurement)
                                                <tr>
                                                    <td>{{ $procurement->title }}</td>
                                                    <td>৳{{ number_format($procurement->estimated_value, 2) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($procurement->submission_deadline)->format('d M Y') }}</td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $procurement->bids_count ?? 0 }} bids</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ 
                                                            $procurement->status == 'open' ? 'success' : 
                                                            ($procurement->status == 'bidding' ? 'warning' : 
                                                            ($procurement->status == 'voting' ? 'info' : 'primary'))
                                                        }}">
                                                            {{ ucfirst($procurement->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewProcurement({{ $procurement->id }})">
                                                            View
                                                        </button>
                                                        @if($procurement->status == 'bidding' && \Carbon\Carbon::parse($procurement->submission_deadline) < now())
                                                            <button class="btn btn-sm btn-warning" onclick="reviewBids({{ $procurement->id }})">
                                                                Review Bids
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted">You haven't created any procurements yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Required -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Action Required</h5>
                        </div>
                        <div class="card-body">
                            @if($actionNeeded->count() > 0)
                                @foreach($actionNeeded as $procurement)
                                    <div class="mb-3">
                                        <h6 class="mb-1">{{ $procurement->title }}</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                {{ $procurement->bids->count() }} bids received
                                            </small>
                                            <span class="badge bg-warning">Review Needed</span>
                                        </div>
                                        <small class="text-muted">
                                            Deadline passed: {{ \Carbon\Carbon::parse($procurement->submission_deadline)->diffForHumans() }}
                                        </small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-primary" onclick="reviewBids({{ $procurement->id }})">
                                                Review Bids
                                            </button>
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @else
                                <p class="text-muted">No action required at this time.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Procurements Overview -->
            @if($myProcurements->count() > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Procurement Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Procurement</th>
                                                <th>Created</th>
                                                <th>Deadline</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($myProcurements as $procurement)
                                                <tr>
                                                    <td>{{ $procurement->title }}</td>
                                                    <td>{{ $procurement->created_at->format('d M Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($procurement->submission_deadline)->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        @php
                                                            $duration = \Carbon\Carbon::parse($procurement->submission_deadline)->diffInDays($procurement->created_at);
                                                        @endphp
                                                        {{ $duration }} days
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ 
                                                            $procurement->status == 'open' ? 'success' : 
                                                            ($procurement->status == 'bidding' ? 'warning' : 
                                                            ($procurement->status == 'voting' ? 'info' : 'primary'))
                                                        }}">
                                                            {{ ucfirst($procurement->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $progress = 0;
                                                            if ($procurement->status == 'bidding') $progress = 40;
                                                            elseif ($procurement->status == 'voting') $progress = 70;
                                                            elseif ($procurement->status == 'completed') $progress = 100;
                                                            elseif ($procurement->status == 'open') $progress = 20;
                                                        @endphp
                                                        <div class="progress">
                                                            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                                                {{ $progress }}%
                                                            </div>
                                                        </div>
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

<!-- Create Procurement Modal -->
<div class="modal fade" id="createProcurementModal" tabindex="-1" aria-labelledby="createProcurementLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="createProcurementForm">
        <div class="modal-header">
          <h5 class="modal-title" id="createProcurementLabel">Create Procurement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input name="category" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Estimated Value (৳)</label>
                <input name="estimated_value" type="number" step="0.01" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Submission Deadline</label>
                <input name="submission_deadline" type="datetime-local" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button id="submitProcurementBtn" type="submit" class="btn btn-primary">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bid Management Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Bid Management</h5>
                <p class="text-muted mb-0">Review and shortlist bids for your procurements</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="bidManagementTable">
                        <thead>
                            <tr>
                                <th>Procurement</th>
                                <th>Vendor</th>
                                <th>Bid Amount</th>
                                <th>Completion Days</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bidManagementTableBody">
                            <!-- Bids will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shortlist Bids Modal -->
<div class="modal fade" id="shortlistBidsModal" tabindex="-1">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shortlist Bids</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="shortlistModalContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitShortlist()">Shortlist Selected Bids</button>
            </div>
    </div>
  </div>
</div>

<script>
async function viewProcurement(procurementId) {
    try {
        console.log('Fetching procurement details for ID:', procurementId);
        
        const response = await fetch(`/api/v1/bppa/procurements/${procurementId}/details`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorData = await response.json();
            console.error('API Error:', errorData);
            alert(errorData.message || 'Failed to fetch procurement details');
            return;
        }
        
        const data = await response.json();
        console.log('Procurement data:', data);
        const procurement = data.data;
        
        // Create detailed view modal
        const modalHtml = `
            <div class="modal fade" id="viewProcurementModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Procurement Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Basic Information</h6>
                                    <p><strong>Title:</strong> ${procurement.title}</p>
                                    <p><strong>Category:</strong> ${procurement.category}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-${procurement.status === 'open' ? 'success' : (procurement.status === 'bidding' ? 'warning' : 'info')}">${procurement.status}</span></p>
                                    <p><strong>Procurement ID:</strong> ${procurement.procurement_id}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Financial Details</h6>
                                    <p><strong>Estimated Value:</strong> ৳${parseFloat(procurement.estimated_value).toLocaleString()}</p>
                                    <p><strong>Submission Deadline:</strong> ${new Date(procurement.submission_deadline).toLocaleDateString()}</p>
                                    <p><strong>Created:</strong> ${new Date(procurement.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Description</h6>
                                    <p>${procurement.description || 'No description provided'}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Bids Received</h6>
                                    <p><strong>Total Bids:</strong> ${procurement.bids_count || 0}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('viewProcurementModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('viewProcurementModal'));
        modal.show();
        
    } catch (error) {
        console.error('Error fetching procurement details:', error);
        alert('Failed to fetch procurement details. Check console for details.');
    }
}

async function reviewBids(procurementId) {
    try {
        console.log('Fetching bids for procurement ID:', procurementId);
        
        const response = await fetch(`/api/v1/bppa/procurements/${procurementId}/bids`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            alert(errorData.message || 'Failed to fetch bids');
            return;
        }
        
        const data = await response.json();
        const procurement = data.data.procurement;
        const bids = data.data.procurement.bids;
        
        if (bids.length === 0) {
            alert('No bids received for this procurement yet.');
            return;
        }
        
        // Create shortlist modal content
        const modalContent = `
            <div class="mb-3">
                <h6>Procurement: ${procurement.title}</h6>
                <p class="text-muted">Select bids to shortlist and provide justification for each selection</p>
            </div>
            <form id="shortlistForm">
                <input type="hidden" name="procurement_id" value="${procurementId}">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th>Vendor</th>
                                <th>Bid Amount</th>
                                <th>Completion Days</th>
                                <th>Technical Proposal</th>
                                <th>Justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bids.map((bid, index) => `
                                <tr>
                                    <td>
                                        <input type="checkbox" name="bid_ids[]" value="${bid.id}" class="bid-checkbox" onchange="updateJustificationField(${index})">
                                    </td>
                                    <td>
                                        <strong>${bid.vendor.company_name}</strong><br>
                                        <small class="text-muted">${bid.vendor.vendor_license_number || 'No License'}</small>
                                    </td>
                                    <td>৳${parseFloat(bid.bid_amount).toLocaleString()}</td>
                                    <td>${bid.completion_days} days</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewTechnicalProposal('${bid.technical_proposal || 'No proposal provided'}')">
                                            View Proposal
                                        </button>
                                    </td>
                                    <td>
                                        <textarea name="shortlist_comments[]" class="form-control form-control-sm" rows="2" 
                                                  placeholder="Why did you choose this bid?" 
                                                  disabled></textarea>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </form>
        `;
        
        document.getElementById('shortlistModalContent').innerHTML = modalContent;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('shortlistBidsModal'));
        modal.show();
        
    } catch (error) {
        console.error('Error fetching bids:', error);
        alert('Failed to fetch bids. Check console for details.');
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.bid-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        updateJustificationField(Array.from(checkboxes).indexOf(checkbox));
    });
}

function updateJustificationField(index) {
    const checkbox = document.querySelectorAll('.bid-checkbox')[index];
    const justificationField = checkbox.closest('tr').querySelector('textarea[name="shortlist_comments[]"]');
    
    if (checkbox.checked) {
        justificationField.disabled = false;
        justificationField.required = true;
    } else {
        justificationField.disabled = true;
        justificationField.required = false;
        justificationField.value = '';
    }
}

function viewTechnicalProposal(proposal) {
    alert(`Technical Proposal:\n\n${proposal}`);
}

async function submitShortlist() {
    const form = document.getElementById('shortlistForm');
    const formData = new FormData(form);
    
    // Validate that at least one bid is selected
    const selectedBids = formData.getAll('bid_ids[]');
    if (selectedBids.length === 0) {
        alert('Please select at least one bid to shortlist.');
        return;
    }
    
    // Validate that all selected bids have justification
    const comments = formData.getAll('shortlist_comments[]');
    const selectedComments = comments.filter((comment, index) => selectedBids.includes(formData.getAll('bid_ids[]')[index]));
    
    if (selectedComments.some(comment => !comment.trim())) {
        alert('Please provide justification for all selected bids.');
        return;
    }
    
    try {
        const payload = {
            procurement_id: formData.get('procurement_id'),
            bid_ids: selectedBids,
            shortlist_comments: selectedComments
        };
        
        console.log('Submitting shortlist payload:', payload);
        
        const response = await fetch('/api/v1/bppa/procurements/shortlist-bids', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            alert(data.message || 'Failed to shortlist bids');
            return;
        }
        
        // Success
        alert(`Bids shortlisted successfully! Procurement status updated to: ${data.data.procurement_status}`);
        
        // Close modal and refresh page
        const modal = bootstrap.Modal.getInstance(document.getElementById('shortlistBidsModal'));
        modal.hide();
        window.location.reload();
        
    } catch (error) {
        console.error('Error shortlisting bids:', error);
        alert('Failed to shortlist bids. Check console for details.');
    }
}

// Load all bids for bid management table on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAllBids();
});

async function loadAllBids() {
    try {
        // Get all procurements and their bids
        const procurements = @json($myProcurements);
        let allBids = [];
        
        for (const procurement of procurements) {
            if (procurement.bids_count > 0) {
                const response = await fetch(`/api/v1/bppa/procurements/${procurement.id}/bids`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const bids = data.data.procurement.bids.map(bid => ({
                        ...bid,
                        procurement_title: procurement.title,
                        procurement_id: procurement.id
                    }));
                    allBids = allBids.concat(bids);
                }
            }
        }
        
        // Populate bid management table
        const tbody = document.getElementById('bidManagementTableBody');
        if (allBids.length > 0) {
            tbody.innerHTML = allBids.map(bid => `
                <tr>
                    <td>
                        <strong>${bid.procurement_title}</strong><br>
                        <small class="text-muted">ID: ${bid.procurement_id}</small>
                    </td>
                    <td>
                        <strong>${bid.vendor.company_name}</strong><br>
                        <small class="text-muted">${bid.vendor.vendor_license_number || 'No License'}</small>
                    </td>
                    <td>৳${parseFloat(bid.bid_amount).toLocaleString()}</td>
                    <td>${bid.completion_days} days</td>
                    <td>${new Date(bid.created_at).toLocaleDateString()}</td>
                    <td>
                        <span class="badge bg-${bid.is_shortlisted ? 'success' : 'secondary'}">
                            ${bid.is_shortlisted ? 'Shortlisted' : 'Submitted'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewBidDetails(${bid.id})">
                            View Details
                        </button>
                        ${!bid.is_shortlisted ? `
                            <button class="btn btn-sm btn-warning" onclick="reviewBids(${bid.procurement_id})">
                                Review
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No bids received yet</td></tr>';
        }
        
    } catch (error) {
        console.error('Error loading bids:', error);
        document.getElementById('bidManagementTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading bids</td></tr>';
    }
}

function viewBidDetails(bidId) {
    // This would show detailed bid information
    alert('Bid details view would be implemented here.');
}

/* Replace simple prompts with modal submit logic */
document.getElementById('createProcurementForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitProcurementBtn');
    btn.disabled = true;
    const form = e.target;
    const token = form.querySelector('input[name="_token"]').value;
    const payload = {
        title: form.title.value.trim(),
        category: form.category.value.trim(),
        estimated_value: form.estimated_value.value ? parseFloat(form.estimated_value.value) : null,
        submission_deadline: form.submission_deadline.value,
        description: form.description.value.trim()
    };

    // Basic client validation
    if (!payload.title || !payload.category || !payload.submission_deadline) {
        alert('Please fill required fields: Title, Category and Submission Deadline.');
        btn.disabled = false;
        return;
    }

    try {
        console.log('Submitting payload:', payload);
        console.log('CSRF Token:', token);
        
        const res = await fetch('/api/v1/bppa/procurements/create', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        });
        
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);

        const data = await res.json();
        console.log('Response data:', data);
        
        if (!res.ok) {
            console.error('API Error:', data);
            if (data.errors) {
                // Show specific validation errors
                const errorMessages = Object.values(data.errors).flat().join('\n');
                alert('Validation failed:\n' + errorMessages);
            } else {
            alert(data.message || 'Failed to create procurement.');
            }
            btn.disabled = false;
            return;
        }

        // Close modal and refresh to show new procurement
        const modalEl = document.getElementById('createProcurementModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
        alert(data.message || 'Procurement created.');
        window.location.reload();
    } catch (err) {
        console.error(err);
        alert('Request failed. Check console for details.');
        btn.disabled = false;
    }
});
</script>
@endsection
