@extends('layouts.frontend_master')

@section('content')

<div class="breadcrumb-area">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-12 text-center">
                <h2 class="breadcrumb-title">My Orders</h2>
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Home</a></li>
                    <li class="breadcrumb-item active">My Orders</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="pt-100px pb-100px">
    <div class="container">
        @if ($orders->isEmpty())
            <div class="text-center">
                <p>You haven't placed any orders yet.</p>
                <a class="btn-hover" href="{{ route('index') }}">Start shopping</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Placed</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ optional($order->placed_at)->format('d M Y') }}</td>
                                <td class="text-center">{{ $order->items_count }}</td>
                                <td class="text-end">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</td>
                                <td><span class="badge bg-{{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('order.show', $order) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        @endif
    </div>
</div>

@endsection
