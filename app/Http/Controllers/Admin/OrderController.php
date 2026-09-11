<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkrole');
    }

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('order_number', 'like', $term)
                    ->orWhere('shipping_name', 'like', $term)
                    ->orWhere('shipping_phone', 'like', $term));
            })
            ->with('user')
            ->withCount('items')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'user', 'shippingCountry', 'payments', 'paymentProblems']);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Move an order to a new status, rejecting transitions that make no sense and
     * restoring stock when an order is cancelled.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $target = OrderStatus::from($validated['status']);
        $current = $order->status;

        if (! $current->canTransitionTo($target)) {
            return back()->with('order_error', "An order that is {$current->value} cannot be marked {$target->value}.");
        }

        DB::transaction(function () use ($order, $target, $current): void {
            if ($target === OrderStatus::Cancelled && $current->restocksOnCancel()) {
                $order->restockItems();
            }

            $order->status = $target;

            // Cash on delivery settles when the goods are handed over.
            if ($target === OrderStatus::Delivered && $order->payment_method->value === 'cod') {
                $order->payment_status = PaymentStatus::Paid;
                $order->payments()->latest()->first()?->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);
            }

            $order->save();
        });

        return back()->with('order_success', "Order {$order->order_number} is now {$target->value}.");
    }
}
