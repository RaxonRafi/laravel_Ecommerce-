@extends('layouts.dashboard_master')

@section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Problem Payment #{{ $problem->id }}</h3>
        <a href="{{ route('admin.problem-payments.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    @if (session('problem_success'))
        <div class="alert alert-success">{{ session('problem_success') }}</div>
    @endif
    @if (session('problem_error'))
        <div class="alert alert-danger">{{ session('problem_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">What went wrong</div>
                <div class="card-body">
                    <p>
                        <span class="badge bg-{{ $problem->reason->badge() }}">{{ $problem->reason->label() }}</span>
                        <span class="text-muted ms-2">{{ $problem->created_at->format('d M Y, H:i') }}</span>
                    </p>
                    <p class="mb-3">{{ $problem->message }}</p>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Gateway</dt>
                        <dd class="col-sm-8">{{ $problem->gateway }}</dd>

                        <dt class="col-sm-4">Transaction reference</dt>
                        <dd class="col-sm-8">{{ $problem->gateway_reference ?: '— none, the payment never started' }}</dd>

                        <dt class="col-sm-4">Amount reported</dt>
                        <dd class="col-sm-8">
                            @if ($problem->amount !== null)
                                {{ $problem->currency }} {{ number_format((float) $problem->amount, 2) }}
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Raw gateway payload</div>
                <div class="card-body">
                    <pre class="mb-0" style="max-height: 360px; overflow: auto;">{{ json_encode($problem->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <small class="text-muted d-block mt-2">Credentials and signatures are stripped before storage.</small>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">Order</div>
                <div class="card-body">
                    @if ($problem->order)
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Order</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('admin.orders.show', $problem->order) }}">{{ $problem->order->order_number }}</a>
                            </dd>

                            <dt class="col-sm-5">Customer</dt>
                            <dd class="col-sm-7">{{ $problem->order->shipping_name }}</dd>

                            <dt class="col-sm-5">Order total</dt>
                            <dd class="col-sm-7">
                                {{ $problem->order->currency }} {{ number_format((float) $problem->order->grand_total, 2) }}
                            </dd>

                            <dt class="col-sm-5">Payment status</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-{{ $problem->order->payment_status->badge() }}">
                                    {{ $problem->order->payment_status->label() }}
                                </span>
                            </dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">
                            No order matches this transaction reference. Nothing can be settled from here —
                            this record exists so the attempt is not lost.
                        </p>
                    @endif
                </div>
            </div>

            @if ($problem->isResolved())
                <div class="card border-success">
                    <div class="card-header bg-success text-white">Resolved</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">How</dt>
                            <dd class="col-sm-7">{{ $problem->resolution->label() }}</dd>

                            <dt class="col-sm-5">By</dt>
                            <dd class="col-sm-7">{{ optional($problem->resolvedBy)->name ?? '—' }}</dd>

                            <dt class="col-sm-5">When</dt>
                            <dd class="col-sm-7">{{ $problem->resolved_at->format('d M Y, H:i') }}</dd>

                            <dt class="col-sm-5">Reference</dt>
                            <dd class="col-sm-7">{{ $problem->resolution_reference ?: '—' }}</dd>

                            @if ($problem->resolution_note)
                                <dt class="col-sm-5">Note</dt>
                                <dd class="col-sm-7">{{ $problem->resolution_note }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            @else
                @if ($problem->canBeRecheckedWithGateway())
                    <div class="card mb-3">
                        <div class="card-header">Re-check with gateway</div>
                        <div class="card-body">
                            <p class="text-muted">
                                Asks SSLCommerz about this transaction again. If it confirms the payment for the
                                full amount, the order settles automatically.
                            </p>
                            <form method="POST" action="{{ route('admin.problem-payments.recheck', $problem) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Re-check with gateway</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if ($problem->order)
                    <div class="card">
                        <div class="card-header">Record payment received</div>
                        <div class="card-body">
                            <p class="text-muted">
                                For money that arrived outside the gateway — bank transfer, bKash, cash. This marks
                                the order paid on your word, so the reference is required.
                            </p>
                            <form method="POST" action="{{ route('admin.problem-payments.resolve', $problem) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Reference</label>
                                    <input type="text" name="reference" class="form-control"
                                           placeholder="bKash trx id, bank slip, receipt number"
                                           value="{{ old('reference') }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Note <small class="text-muted">(optional)</small></label>
                                    <textarea name="note" class="form-control" rows="2"
                                              placeholder="How this was confirmed">{{ old('note') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    Mark payment received
                                    ({{ $problem->order->currency }} {{ number_format((float) $problem->order->grand_total, 2) }})
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection
