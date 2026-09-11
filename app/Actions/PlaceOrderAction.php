<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns a user's cart into an order.
 *
 * Everything that determines what the customer pays is re-derived here from the
 * database. Nothing about the totals is taken from the request: the checkout page
 * only supplies the delivery address and the chosen payment method.
 */
class PlaceOrderAction
{
    /**
     * @param  array{shipping_name:string, shipping_phone:string, shipping_address:string, notes?:string|null, payment_method:string}  $details
     *
     * @throws CheckoutException
     */
    public function execute(User $user, array $details, int $countryId, string $cityName): Order
    {
        return DB::transaction(function () use ($user, $details, $countryId, $cityName): Order {
            $carts = Cart::where('user_id', $user->id)->get();

            if ($carts->isEmpty()) {
                throw CheckoutException::emptyCart();
            }

            $shipping = Shipping::where('country_id', $countryId)
                ->where('city_name', $cityName)
                ->first();

            if (! $shipping) {
                throw CheckoutException::noShippingDestination();
            }

            $lines = $this->resolveLines($carts);
            $subtotal = array_sum(array_column($lines, 'line_total'));

            [$coupon, $discount] = $this->resolveCoupon($subtotal);

            $shippingCharge = round((float) $shipping->shipping_charge, 2);
            $grandTotal = round($subtotal - $discount + $shippingCharge, 2);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shippingCharge,
                'grand_total' => $grandTotal,
                'currency' => config('payment.currency', 'BDT'),
                'payment_method' => $details['payment_method'],
                'payment_status' => 'pending',
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->coupon_name,
                'shipping_name' => $details['shipping_name'],
                'shipping_phone' => $details['shipping_phone'],
                'shipping_address' => $details['shipping_address'],
                'shipping_country_id' => $countryId,
                'shipping_city' => $cityName,
                'notes' => $details['notes'] ?? null,
                'placed_at' => Carbon::now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line['attributes']);

                // Stock was locked and checked in resolveLines(), so this cannot
                // drive the quantity negative.
                DB::table('inventories')
                    ->where('id', $line['inventory_id'])
                    ->decrement('quantity', $line['attributes']['quantity']);
            }

            if ($coupon) {
                coupon::where('id', $coupon->id)
                    ->where('coupon_limit', '>', 0)
                    ->decrement('coupon_limit');
            }

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $details['payment_method'],
                'amount' => $grandTotal,
                'currency' => $order->currency,
                'status' => 'pending',
            ]);

            Cart::where('user_id', $user->id)->delete();

            return $order->load('items');
        });
    }

    /**
     * Builds the order lines, pricing each from the catalogue and reserving stock.
     *
     * Inventory rows are locked for the duration of the transaction so two
     * customers cannot both buy the last unit.
     *
     * @param  Collection<int, Cart>  $carts
     * @return array<int, array{inventory_id:int, line_total:float, attributes:array<string, mixed>}>
     *
     * @throws CheckoutException
     */
    private function resolveLines($carts): array
    {
        $lines = [];

        foreach ($carts as $cart) {
            $product = Product::find($cart->product_id);

            if (! $product) {
                throw CheckoutException::productUnavailable('A product in your cart is no longer available.');
            }

            $inventory = DB::table('inventories')
                ->where('product_id', $cart->product_id)
                ->where('color_id', $cart->color_id)
                ->where('size_id', $cart->size_id)
                ->lockForUpdate()
                ->first();

            $quantity = (int) $cart->cart_amount;

            if (! $inventory || $inventory->quantity < $quantity) {
                throw CheckoutException::insufficientStock(
                    $product->product_name,
                    $inventory->quantity ?? 0,
                );
            }

            // Priced from the catalogue at the moment of ordering, not from the
            // cart row, which may have been created days ago.
            $unitPrice = round((float) ($product->discounted_price ?: $product->regular_price), 2);
            $lineTotal = round($unitPrice * $quantity, 2);

            $lines[] = [
                'inventory_id' => $inventory->id,
                'line_total' => $lineTotal,
                'attributes' => [
                    'product_id' => $product->id,
                    'color_id' => $cart->color_id,
                    'size_id' => $cart->size_id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'color_name' => optional($cart->relationtocolor)->color_name,
                    'size_name' => optional($cart->relationtosize)->size_name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ],
            ];
        }

        return $lines;
    }

    /**
     * Re-validates the coupon held in the session. A coupon that expired or ran out
     * between the cart page and checkout is silently dropped rather than applied.
     *
     * @return array{0: coupon|null, 1: float}
     */
    private function resolveCoupon(float $subtotal): array
    {
        $code = session('s_coupon_name');

        if (! $code) {
            return [null, 0.0];
        }

        $coupon = coupon::where('coupon_name', $code)->lockForUpdate()->first();

        if (! $coupon
            || Carbon::today()->gt(Carbon::parse($coupon->coupon_validity_date))
            || $coupon->coupon_limit <= 0
            || $coupon->minimum_order > $subtotal) {
            return [null, 0.0];
        }

        $discount = $coupon->coupon_type === 'Percentage'
            ? $subtotal * ((float) $coupon->coupon_ammount / 100)
            : (float) $coupon->coupon_ammount;

        // Never discount below zero.
        return [$coupon, round(min($discount, $subtotal), 2)];
    }
}
