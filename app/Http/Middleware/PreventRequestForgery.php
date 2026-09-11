<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Called by SSLCommerz, not by our own pages: the IPN is server-to-server
        // with no session at all, and the customer is posted back to the other
        // three from the gateway's own domain. Neither can carry a token.
        //
        // Safe to exempt because none of these endpoints acts on what it is sent:
        // each re-validates the transaction with the gateway over our own
        // outbound connection before an order can change.
        'payment/sslcommerz/*',
    ];
}
