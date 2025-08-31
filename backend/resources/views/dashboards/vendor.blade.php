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

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Bids</h5>
                            <h2 class="mb-0">{{ $stats['total_bids'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Bids</h5>
                            <h2 class="mb-0">{{ $stats['active_bids'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Shortlisted</h5>
                            <h2 class="mb-0">{{ $stats['shortlisted_bids'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Won Bids</h5>
                            <h2 class="mb-0">{{ $stats['won_bids'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

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
                                                    <td>৳{{ number_format($procurement->budget, 2) }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($procurement->deadline)->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $procurement->status == 'open' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($procurement->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if(\Carbon\Carbon::parse($procurement->deadline) > now())
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
                                            <small class="text-muted">৳{{ number_format($bid->amount, 2) }}</small>
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
                                                    <td>৳{{ number_format($bid->amount, 2) }}</td>
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
                                                        @if($bid->status == 'submitted' && \Carbon\Carbon::parse($bid->procurement->deadline) > now())
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

<script>
function submitBid(procurementId) {
    const amount = prompt('Enter your bid amount:');
    if (amount && !isNaN(amount) && amount > 0) {
        // Here you would implement the bid submission functionality
        alert('Bid submitted successfully!');
        location.reload();
    }
}

function editBid(bidId) {
    const newAmount = prompt('Enter new bid amount:');
    if (newAmount && !isNaN(newAmount) && newAmount > 0) {
        // Here you would implement the bid editing functionality
        alert('Bid updated successfully!');
        location.reload();
    }
}

function viewBid(bidId) {
    // Here you would implement the bid viewing functionality
    alert('Bid details would be displayed here.');
}
</script>
@endsection
