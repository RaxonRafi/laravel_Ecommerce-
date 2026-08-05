<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRegisterRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CustomerController extends Controller
{
    public function Customerlogin()
    {
        return view('customer.customerlogin');
    }

    public function customerregister(CustomerRegisterRequest $request)
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'password' => bcrypt($request->password),
            'role' => 'customer',
        ]);

        /*
         * SMS confirmation is disabled for now.
         *
         * The original implementation had the gateway credentials hardcoded here
         * and made a blocking cURL call during registration. Credentials now live
         * in config/services.php (SMS_API_* in .env). Before re-enabling this:
         *
         *   1. Rotate the old credentials at the provider — they are still
         *      readable in this repository's git history.
         *   2. Move the send into a queued job so registration does not block on
         *      a third-party HTTP request.
         *   3. Handle failures; the original code ignored the response entirely.
         *
         * $response = Http::asForm()->post(config('services.sms.url'), [
         *     'username' => config('services.sms.username'),
         *     'password' => config('services.sms.password'),
         *     'number'   => $request->phone_number,
         *     'message'  => "Hello {$request->name} You are registered successfully in goldfish ecommerce",
         * ]);
         */

        return back()->with('customer_registration', 'registration completed successfully!');
    }

    public function customerdashboard()
    {
        return view('customer.customerdashboard');
    }

    public function insertcart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'color_id' => ['nullable', 'integer'],
            'size_id' => ['nullable', 'integer'],
            'cart_amount' => ['required', 'integer', 'min:1'],
        ]);

        // Price is derived from the catalogue, never from the request. Trusting a
        // client-supplied price would let a customer set what they pay.
        $product = Product::findOrFail($validated['product_id']);
        $price = $product->discounted_price ?: $product->regular_price;

        // Likewise the owner is the authenticated user, never a request field.
        $userId = auth()->id();

        $identity = [
            'product_id' => $validated['product_id'],
            'color_id' => $validated['color_id'] ?? null,
            'size_id' => $validated['size_id'] ?? null,
            'user_id' => $userId,
        ];

        $cart = Cart::where($identity)->first();

        $cart_amount_status = 0;

        if ($cart) {
            $cart->increment('cart_amount', $validated['cart_amount']);
        } else {
            Cart::create($identity + [
                'product_current_price' => $price,
                'cart_amount' => $validated['cart_amount'],
            ]);
            $cart_amount_status = 1;
        }

        return response()->json([
            'cart_amount_status' => $cart_amount_status,
        ]);
    }

    public function cart()
    {
        $countries = Shipping::select('country_id')->groupBy('country_id')->get();
        $carts = Cart::where('user_id', auth()->id())->get();

        return view('cart', compact('carts', 'countries'));
    }

    public function getcitylist(Request $request)
    {
        $select_option = "<option value=''>--Select city--</option>";
        $cities = Shipping::where('country_id', $request->country_id)->get();

        foreach ($cities as $city) {
            $select_option .= "<option value='$city->shipping_charge'>$city->city_name</option>";
        }

        echo $select_option;
    }

    public function cartremove(Request $request)
    {
        // Scoped to the authenticated user so one customer cannot delete another's
        // cart rows by guessing an id.
        Cart::where('id', $request->cart_id)
            ->where('user_id', auth()->id())
            ->firstOrFail()
            ->delete();
    }

    public function setcountrycity(Request $request)
    {
        Session::put('s_country_id', $request->country_id);
        Session::put('s_city_name', $request->city_name);
    }
}
