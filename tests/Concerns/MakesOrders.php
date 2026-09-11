<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds the minimum an order needs to exist, for tests about what happens to an
 * order after it is placed. Tests about placing orders build carts instead.
 */
trait MakesOrders
{
    protected function makeCustomer(): User
    {
        return User::create([
            'name' => 'Test Customer',
            'email' => 'customer'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }

    protected function makeAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    protected function makeCountry(): int
    {
        return (int) DB::table('countries')->insertGetId([
            'code' => 'BD',
            'name' => 'Bangladesh',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeOrder(array $attributes = [], ?User $user = null): Order
    {
        $user ??= $this->makeCustomer();

        return Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => OrderStatus::Pending,
            'subtotal' => 4000.00,
            'discount_total' => 0,
            'shipping_total' => 250.00,
            'grand_total' => 4250.00,
            'currency' => 'BDT',
            'payment_method' => 'sslcommerz',
            'payment_status' => PaymentStatus::Pending,
            'shipping_name' => 'Test Customer',
            'shipping_phone' => '01711000000',
            'shipping_address' => '12 Test Road, Dhaka',
            'shipping_country_id' => $this->makeCountry(),
            'shipping_city' => 'Dhaka',
            'placed_at' => now(),
        ], $attributes));
    }

    /**
     * The pending attempt PlaceOrderAction writes, carrying the reference we later
     * resolve a callback against.
     */
    protected function makePendingPayment(Order $order, string $reference): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'gateway' => 'sslcommerz',
            'gateway_reference' => $reference,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
