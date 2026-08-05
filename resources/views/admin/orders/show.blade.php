@extends('layouts.dashboard_master')

@section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Order {{ $order->order_number }}</h3>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to orders</a>
    </div>

    @if (session('order_success'))
        <div class="alert alert-success">{{ session('order_success') }}</div>
    @endif
    @if (session('order_error'))
        <div class="alert alert-danger">{{ session('order_error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Unit</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->product_name }}
                                            <small class="d-block text-muted">
                                                SKU {{ $item->sku }}
                                                @if ($item->color_name || $item->size_name)
                                                    — {{ collect([$item->color_name, $item->size_name])->filter()->implode(' / ') }}
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal</td>
                                    <td class="text-end">{{ number_format((float) $order->subtotal, 2) }}</td>
                                </tr>
                                @if ((float) $order->discount_total > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">Discount ({{ $order->coupon_code }})</td>
                                        <td class="text-end">− {{ number_format((float) $order->discount_total, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="text-end">Shipping</td>
                                    <td class="text-end">{{ number_format((float) $order->shipping_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Grand Total</th>
                                    <th class="text-end">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            @if ($order->payments->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Attempts</h5>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Gateway</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Paid at</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->gateway }}</td>
                                        <td>{{ $payment->gateway_reference ?? '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td><span class="badge bg-{{ $payment->status->badge() }}">{{ $payment->status->label() }}</span></td>
                                        <td>{{ optional($payment->paid_at)->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Status</h5>
                    <p>
                        <span class="badge bg-{{ $order->status->badge() }}">{{ $order->status->label() }}</span>
                    </p>

                    @if (count($order->status->allowedTransitions()) > 0)
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <div class="input-group">
                                <select name="status" class="form-control">
                                    @foreach ($order->status->allowedTransitions() as $next)
                                        <option value="{{ $next->value }}">{{ $next->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary" type="submit">Update</button>
                            </div>
                            @if (in_array(\App\Enums\OrderStatus::Cancelled, $order->status->allowedTransitions(), true))
                                <small class="text-muted d-block mt-2">
                                    Cancelling returns the items to inventory.
                                </small>
                            @endif
                        </form>
                    @else
                        <p class="text-muted mb-0">This order is in a final state.</p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Customer</h5>
                    <p class="mb-0">
                        {{ $order->shipping_name }}<br>
                        {{ $order->shipping_phone }}<br>
                        <small class="text-muted">{{ optional($order->user)->email }}</small>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Address</h5>
                    <p class="mb-0">
                        {{ $order->shipping_address }}<br>
                        {{ $order->shipping_city }}@if ($order->shippingCountry), {{ $order->shippingCountry->name }}@endif
                    </p>
                    @if ($order->notes)
                        <hr>
                        <small class="text-muted">{{ $order->notes }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
