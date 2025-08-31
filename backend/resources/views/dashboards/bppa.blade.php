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
                                                    <td>৳{{ number_format($procurement->budget, 2) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($procurement->deadline)->format('d M Y') }}</td>
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
                                                        @if($procurement->status == 'bidding' && \Carbon\Carbon::parse($procurement->deadline) < now())
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
                                            Deadline passed: {{ \Carbon\Carbon::parse($procurement->deadline)->diffForHumans() }}
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
                                                    <td>{{ \Carbon\Carbon::parse($procurement->deadline)->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        @php
                                                            $duration = \Carbon\Carbon::parse($procurement->deadline)->diffInDays($procurement->created_at);
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
                <label class="form-label">Budget (৳)</label>
                <input name="budget" type="number" step="0.01" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Deadline</label>
                <input name="deadline" type="datetime-local" class="form-control" required>
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

<script>
function viewProcurement(procurementId) {
    // Here you would implement viewing procurement details
    alert(`View procurement ${procurementId} details would be implemented here.`);
}

function reviewBids(procurementId) {
    // Here you would implement bid review functionality
    alert(`Review bids for procurement ${procurementId} would be implemented here.`);
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
        budget: form.budget.value ? parseFloat(form.budget.value) : null,
        deadline: form.deadline.value,
        description: form.description.value.trim()
    };

    // Basic client validation
    if (!payload.title || !payload.category || !payload.deadline) {
        alert('Please fill required fields: Title, Category and Deadline.');
        btn.disabled = false;
        return;
    }

    try {
        const res = await fetch('/api/v1/bppa/procurements/create', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok) {
            console.error(data);
            alert(data.message || 'Failed to create procurement.');
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
