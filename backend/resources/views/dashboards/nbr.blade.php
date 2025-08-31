@extends('layouts.app')

@section('title', 'NBR Officer Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">NBR Officer Dashboard</h1>
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
                            <h5 class="card-title">Total Returns</h5>
                            <h2 class="mb-0">{{ $stats['total_returns'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Pending Review</h5>
                            <h2 class="mb-0">{{ $stats['pending_returns'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Approved</h5>
                            <h2 class="mb-0">{{ $stats['approved_returns'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Declined</h5>
                            <h2 class="mb-0">{{ $stats['declined_returns'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Tax Returns -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Pending Tax Returns for Review</h5>
                        </div>
                        <div class="card-body">
                            @if($pendingReturns->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>TIIN</th>
                                                <th>Citizen Name</th>
                                                <th>Tax Year</th>
                                                <th>Tax Amount</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingReturns as $return)
                                                <tr>
                                                    <td>{{ $return->citizen->tiin }}</td>
                                                    <td>{{ $return->citizen->name }}</td>
                                                    <td>{{ $return->tax_year }}</td>
                                                    <td>৳{{ number_format($return->tax_amount, 2) }}</td>
                                                    <td>{{ $return->created_at->format('d M Y, H:i') }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success" onclick="reviewReturn({{ $return->id }}, 'approve')">
                                                            Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="reviewReturn({{ $return->id }}, 'decline')">
                                                            Decline
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted">No pending tax returns for review.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            @if($recentActivity->count() > 0)
                                @foreach($recentActivity as $activity)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <small class="text-muted">{{ $activity->citizen->tiin }}</small><br>
                                            <span class="badge bg-{{ $activity->status == 'approved' ? 'success' : 'danger' }}">
                                                {{ ucfirst($activity->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ $activity->updated_at->diffForHumans() }}</small>
                                    </div>
                                    <hr>
                                @endforeach
                            @else
                                <p class="text-muted">No recent activity.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reviewReturn(returnId, action) {
    if (confirm(`Are you sure you want to ${action} this tax return?`)) {
        // Here you would implement the review functionality
        // For now, just show an alert
        alert(`Tax return ${action}d successfully!`);
        location.reload();
    }
}
</script>
@endsection
