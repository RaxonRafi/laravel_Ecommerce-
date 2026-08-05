@extends('layouts.frontend_master')

@section('content')

 <!-- breadcrumb-area start -->
 <div class="breadcrumb-area">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-12 text-center">
                <h2 class="breadcrumb-title">Shop</h2>
                <!-- breadcrumb-list start -->
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Home</a></li>
                    <li class="breadcrumb-item active">Checkout</li>
                </ul>
                <!-- breadcrumb-list end -->
            </div>
        </div>
    </div>
</div>

<!-- breadcrumb-area end -->


<!-- checkout area start -->
<div class="checkout-area pt-100px pb-100px">
    <div class="container">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('order.place') }}" method="POST" id="place-order-form">
            @csrf
            <div class="row">
                <div class="col-lg-7">
                    <div class="billing-info-wrap">
                        <h3>Delivery Details</h3>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="billing-info mb-4">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="shipping_name" required
                                           value="{{ old('shipping_name', auth()->user()->name) }}" />
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="billing-info mb-4">
                                    <label>Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="shipping_phone" required
                                           value="{{ old('shipping_phone', auth()->user()->phone_number) }}" />
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="billing-info mb-4">
                                    <label>Street Address <span class="text-danger">*</span></label>
                                    <input class="billing-address" placeholder="House number and street name"
                                           name="shipping_address" required
                                           type="text" value="{{ old('shipping_address', auth()->user()->address) }}" />
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="billing-info mb-4">
                                    <label>Delivering To</label>
                                    {{-- Chosen on the cart page; it determines the shipping charge. --}}
                                    <input type="text" value="{{ session('s_city_name') }}" readonly />
                                    <small class="text-muted">
                                        To change this, go back to your <a href="{{ route('cart') }}">cart</a>.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="additional-info-wrap">
                            <h4>Additional information</h4>
                            <div class="additional-info">
                                <label>Order notes</label>
                                <textarea placeholder="Notes about your order, e.g. special notes for delivery."
                                          name="notes">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-5 mt-md-30px mt-lm-30px ">
                    <div class="your-order-area">

                        <h3>Your order</h3>
                        <div class="your-order-wrap gray-bg-4">
                            <div class="your-order-product-info">
                                <div class="your-order-top">
                                    <ul>
                                        <li>Product</li>
                                        <li>Total</li>
                                    </ul>
                                </div>
                                <div class="your-order-middle">
                                    <ul>
                                        @foreach ($carts as $cart)
                                            <li>
                                                <span class="order-middle-left">
                                                    {{ optional($cart->relationtoproduct)->product_name }}
                                                    @if ($cart->relationtocolor || $cart->relationtosize)
                                                        <small>({{ collect([optional($cart->relationtocolor)->color_name, optional($cart->relationtosize)->size_name])->filter()->implode(' / ') }})</small>
                                                    @endif
                                                    &times; {{ $cart->cart_amount }}
                                                </span>
                                                <span class="order-price">
                                                    {{ $currency }} {{ number_format($cart->product_current_price * $cart->cart_amount, 2) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="your-order-bottom">
                                    <ul>
                                        <li class="your-order-shipping">Sub Total</li>
                                        <li>{{ $currency }} {{ number_format($sub_total, 2) }}</li>
                                    </ul>
                                    @if ($after_coupon_total != $sub_total)
                                        <ul>
                                            <li class="your-order-shipping">After Coupon</li>
                                            <li>{{ $currency }} {{ number_format($after_coupon_total, 2) }}</li>
                                        </ul>
                                    @endif
                                    <ul>
                                        <li class="your-order-shipping">Shipping</li>
                                        <li>{{ $currency }} {{ number_format($shipping_charge, 2) }}</li>
                                    </ul>
                                </div>
                                <div class="your-order-total">
                                    <ul>
                                        <li class="order-total">Grand Total</li>
                                        <li>{{ $currency }} {{ number_format($grand_total, 2) }}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="payment-method mt-3">
                                <h4>Payment Method</h4>
                                @forelse ($gateways as $key => $gateway)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                               id="pm-{{ $key }}" value="{{ $key }}"
                                               {{ old('payment_method', array_key_first($gateways)) === $key ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="pm-{{ $key }}">
                                            {{ $gateway->label() }}
                                        </label>
                                    </div>
                                @empty
                                    <div class="alert alert-warning mb-0">
                                        No payment method is currently available. Please contact us to complete your order.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        <div class="Place-order mt-25">
                            <button type="submit" class="btn-hover" id="place-order-btn"
                                    @disabled(count($gateways) === 0)>
                                Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- checkout area end -->

<script>
    // Guard against a double submit creating two orders.
    document.getElementById('place-order-form').addEventListener('submit', function () {
        var btn = document.getElementById('place-order-btn');
        btn.disabled = true;
        btn.innerText = 'Placing order...';
    });
</script>

@endsection
