@extends('layouts.dashboard_master')

@section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Orders</h3>
    </div>

    @if (session('order_success'))
        <div class="alert alert-success">{{ session('order_success') }}</div>
    @endif
    @if (session('order_error'))
        <div class="alert alert-danger">{{ session('order_error') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control"
                   placeholder="Order number, customer name or phone"
                   value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
        </div>
        @if (request()->hasAny(['search', 'status']))
            <div class="col-md-2">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Placed</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>
                            {{ $order->shipping_name }}
                            <small class="d-block text-muted">{{ $order->shipping_phone }}</small>
                        </td>
                        <td>{{ optional($order->placed_at)->format('d M Y, H:i') }}</td>
                        <td class="text-center">{{ $order->items_count }}</td>
                        <td class="text-end">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</td>
                        <td>
                            {{ $order->payment_method->label() }}
                            <span class="badge bg-{{ $order->payment_status->badge() }}">{{ $order->payment_status->label() }}</span>
                        </td>
                        <td><span class="badge bg-{{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>

@endsection
