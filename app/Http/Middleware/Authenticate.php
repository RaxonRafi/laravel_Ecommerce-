<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Storefront routes that belong to customers rather than staff. These use the
     * customer login screen; everything else falls through to the admin login.
     */
    private const CUSTOMER_ROUTES = [
        'cart',
        'checkout',
        'insert.cart',
        'cart.remove',
        'set.country.city',
        'customer.dashboard',
    ];

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return $request->routeIs(self::CUSTOMER_ROUTES)
            ? route('customerlogin')
            : route('login');
    }
}
