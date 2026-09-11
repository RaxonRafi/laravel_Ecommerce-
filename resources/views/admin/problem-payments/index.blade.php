@extends('layouts.dashboard_master')

@section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            Problem Payments
            @if ($unresolvedCount > 0)
                <span class="badge bg-danger">{{ $unresolvedCount }} unresolved</span>
            @endif
        </h3>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to orders</a>
    </div>

    @if (session('problem_success'))
        <div class="alert alert-success">{{ session('problem_success') }}</div>
    @endif
    @if (session('problem_error'))
        <div class="alert alert-danger">{{ session('problem_error') }}</div>
    @endif

    <p class="text-muted">
        Payments that failed for a reason the customer cannot fix. Money may still be owed on these
        orders — re-check with the gateway, or record a payment that arrived another way.
    </p>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="state" class="form-control">
                <option value="unresolved" @selected($state === 'unresolved')>Unresolved</option>
                <option value="resolved" @selected($state === 'resolved')>Resolved</option>
                <option value="all" @selected($state === 'all')>All</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="reason" class="form-control">
                <option value="">All reasons</option>
                @foreach ($reasons as $reason)
                    <option value="{{ $reason->value }}" @selected(request('reason') === $reason->value)>
                        {{ $reason->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
        </div>
        @if (request()->hasAny(['state', 'reason']))
            <div class="col-md-2">
                <a href="{{ route('admin.problem-payments.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Reason</th>
                    <th>What happened</th>
                    <th class="text-end">Amount</th>
                    <th>When</th>
                    <th>State</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($problems as $problem)
                    <tr>
                        <td>
                            @if ($problem->order)
                                <a href="{{ route('admin.orders.show', $problem->order) }}">{{ $problem->order->order_number }}</a>
                            @else
                                <span class="text-muted">No matching order</span>
                            @endif
                            <small class="d-block text-muted">{{ $problem->gateway_reference ?: '—' }}</small>
                        </td>
                        <td><span class="badge bg-{{ $problem->reason->badge() }}">{{ $problem->reason->label() }}</span></td>
                        <td><small>{{ \Illuminate\Support\Str::limit($problem->message, 90) }}</small></td>
                        <td class="text-end">
                            @if ($problem->amount !== null)
                                {{ $problem->currency }} {{ number_format((float) $problem->amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $problem->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            @if ($problem->isResolved())
                                <span class="badge bg-{{ $problem->resolution->badge() }}">{{ $problem->resolution->label() }}</span>
                                <small class="d-block text-muted">{{ optional($problem->resolvedBy)->name }}</small>
                            @else
                                <span class="badge bg-secondary">Unresolved</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.problem-payments.show', $problem) }}" class="btn btn-sm btn-outline-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Nothing here — no payment problems match this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $problems->links() }}
</div>

@endsection
