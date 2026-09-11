<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PlaceOrderAction;
use App\Actions\RecordPaymentProblemAction;
use App\Enums\ProblemReason;
use App\Exceptions\CheckoutException;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Notifications\OrderPlaced;
use App\Payments\Exceptions\GatewayNotConfigured;
use App\Payments\Exceptions\SslCommerzRequestFailed;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderAction $placeOrder,
        private readonly PaymentGatewayManager $gateways,
        private readonly RecordPaymentProblemAction $recordProblem,
    ) {
        $this->middleware('auth');
    }

    /**
     * Place the order. Totals are recomputed server-side inside the action; this
     * request only carries the delivery address and payment choice.
     */
    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $countryId = Session::get('s_country_id');
        $cityName = Session::get('s_city_name');

        if (! $countryId || ! $cityName) {
            return redirect()->route('cart')
                ->with('checkout_error', 'Please choose your delivery country and city before checking out.');
        }

        try {
            $order = $this->placeOrder->execute(
                $request->user(),
                $request->safe()->only(['shipping_name', 'shipping_phone', 'shipping_address', 'notes', 'payment_method']),
                (int) $countryId,
                (string) $cityName,
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart')->with('checkout_error', $e->getMessage());
        }

        // The coupon has been consumed by this order.
        Session::forget('s_coupon_name');

        $this->notifyCustomer($order);

        $gateway = $this->gateways->make($order->payment_method->value);

        try {
            $result = $gateway->charge($order);
        } catch (GatewayNotConfigured $e) {
            // The order exists and stock is reserved; the customer settles later.
            Log::warning('Payment gateway unavailable at checkout', [
                'order' => $order->order_number,
                'gateway' => $gateway->key(),
            ]);

            return redirect()->route('order.show', $order)
                ->with('order_warning', 'Your order was placed, but online payment is not available yet. We will contact you to arrange payment.');
        } catch (SslCommerzRequestFailed $e) {
            // The customer never reached a payment page, so nothing was charged —
            // but the order and its reserved stock stand, and somebody has to chase
            // the money. That is what the problem payments queue is for.
            $this->recordProblem->execute(
                order: $order,
                reason: ProblemReason::InitiationError,
                message: $e->getMessage(),
                gateway: $gateway->key(),
            );

            return redirect()->route('order.show', $order)
                ->with('order_warning', 'Your order was placed, but we could not start the online payment. Nothing has been charged — we will contact you to arrange payment.');
        }

        if ($result->requiresRedirect()) {
            return redirect()->away($result->redirectUrl);
        }

        return redirect()->route('order.show', $order)
            ->with('order_success', 'Thank you! Your order has been placed.');
    }

    /**
     * A customer may only view their own orders.
     */
    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $order->load(['items', 'shippingCountry']);

        return view('orders.show', compact('order'));
    }

    public function index(): View
    {
        $orders = Order::where('user_id', auth()->id())
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    private function notifyCustomer(Order $order): void
    {
        // A mail failure must never lose a paid-for order.
        try {
            $order->user->notify(new OrderPlaced($order));
        } catch (\Throwable $e) {
            Log::error('Order confirmation notification failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
