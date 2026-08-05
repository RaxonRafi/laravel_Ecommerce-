@extends('layouts.frontend_master')

@section('content')

<div class="breadcrumb-area">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-12 text-center">
                <h2 class="breadcrumb-title">Order {{ $order->order_number }}</h2>
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('order.index') }}">My Orders</a></li>
                    <li class="breadcrumb-item active">{{ $order->order_number }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="pt-100px pb-100px">
    <div class="container">

        @if (session('order_success'))
            <div class="alert alert-success">{{ session('order_success') }}</div>
        @endif
        @if (session('order_warning'))
            <div class="alert alert-warning">{{ session('order_warning') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Unit Price</th>
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
                                    <td class="text-end">{{ $order->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ $order->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Subtotal</td>
                                <td class="text-end">{{ $order->currency }} {{ number_format((float) $order->subtotal, 2) }}</td>
                            </tr>
                            @if ((float) $order->discount_total > 0)
                                <tr>
                                    <td colspan="3" class="text-end">
                                        Discount @if ($order->coupon_code) <small>({{ $order->coupon_code }})</small> @endif
                                    </td>
                                    <td class="text-end">− {{ $order->currency }} {{ number_format((float) $order->discount_total, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end">Shipping</td>
                                <td class="text-end">{{ $order->currency }} {{ number_format((float) $order->shipping_total, 2) }}</td>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Grand Total</th>
                                <th class="text-end">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Order Status</h5>
                        <p class="mb-1">
                            <span class="badge bg-{{ $order->status->badge() }}">{{ $order->status->label() }}</span>
                        </p>
                        <p class="mb-1"><strong>Placed:</strong> {{ optional($order->placed_at)->format('d M Y, H:i') }}</p>
                        <p class="mb-1"><strong>Payment:</strong> {{ $order->payment_method->label() }}</p>
                        <p class="mb-0">
                            <strong>Payment status:</strong>
                            <span class="badge bg-{{ $order->payment_status->badge() }}">{{ $order->payment_status->label() }}</span>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Delivery Address</h5>
                        <p class="mb-0">
                            {{ $order->shipping_name }}<br>
                            {{ $order->shipping_phone }}<br>
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
</div>

@endsection
