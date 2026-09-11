<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\product_featured_photo;
use App\Models\Shipping;
use App\Payments\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FrontendController extends Controller
{
    /**
     * How many products each homepage tab shows. The homepage previously loaded the
     * entire catalogue on every request, including one unbounded query per category
     * executed from inside the Blade template.
     */
    private const HOMEPAGE_LIMIT = 8;

    private const PER_PAGE = 12;

    public function index()
    {
        $categories = Category::all();
        $products = Product::latest()->take(self::HOMEPAGE_LIMIT)->get();

        $categoryProducts = $categories->mapWithKeys(fn ($category) => [
            $category->id => Product::where('category_id', $category->id)
                ->latest()
                ->take(self::HOMEPAGE_LIMIT)
                ->get(),
        ]);

        return view('index', compact('categories', 'products', 'categoryProducts'));
    }

    /**
     * Paginated catalogue with keyword search and optional category filter.
     */
    public function shop(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category');

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q
                    ->where('product_name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('short_description', 'like', $term));
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $categories = Category::all();

        return view('shop', compact('products', 'categories', 'search', 'categoryId'));
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function productdetails($slug)
    {

        $product = Product::where('slug', $slug)->firstOrFail();
        $product_featured_photos = product_featured_photo::where('product_id', $product->id)->get();
        $related_products = Product::where('subcategory_id', $product->subcategory_id)->where('id', '!=', $product->id)->get();
        $inventories = Inventory::where('product_id', $product->id)->select('color_id')->groupBy('color_id')->get();
        $total_inventory = Inventory::where('product_id', $product->id)->sum('quantity');

        return view('productdetails', compact('product', 'related_products', 'product_featured_photos', 'inventories', 'total_inventory'));
    }

    public function getsizes(Request $request)
    {
        $str_size = '<option>--select size--</option>';
        $sizes = Inventory::where([

            'product_id' => $request->product_id,
            'color_id' => $request->color_id,

        ])->get();
        foreach ($sizes as $size) {
            $str_size .= "<option value='$size->size_id'>".$size->relationtosize->size_name.'</option>';
        }
        echo $str_size;
    }

    public function getinventory(Request $request)
    {
        // No matching combination means nothing is stocked, not a server error.
        $inventory = Inventory::where([
            'product_id' => $request->product_id,
            'color_id' => $request->color_id,
            'size_id' => $request->size_id,
        ])->first();

        echo $inventory->quantity ?? 0;
    }

    public function checkcoupon(Request $request)
    {

        if (coupon::where('coupon_name', $request->cpn_name)->exists()) {

            $coupon = coupon::where('coupon_name', $request->cpn_name)->first();

            if (Carbon::today() <= $coupon->coupon_validity_date) {

                if ($coupon->minimum_order > $request->sub_total) {

                    Session::put('s_coupon_name', '');

                    return response()->json([

                        'error' => 'You need to order minimum'.$coupon->minimum_order,
                    ]);

                } else {

                    if ($coupon->coupon_limit == 0) {

                        Session::put('s_coupon_name', '');

                        return response()->json([

                            'error' => 'Coupon Usage Limit is Over!',
                        ]);

                    } else {

                        Session::put('s_coupon_name', $request->cpn_name);

                        if ($coupon->coupon_type == 'Percentage') {

                            $grand_total = $request->sub_total - ($request->sub_total * ($coupon->coupon_ammount / 100));

                        } else {

                            $grand_total = $request->sub_total - $coupon->coupon_ammount;

                        }

                        return response()->json([

                            'coupon_type' => $coupon->coupon_type,
                            'coupon_ammount' => $coupon->coupon_ammount,
                            'grand_total' => $grand_total,

                        ]);

                    }

                }

            } else {

                Session::put('s_coupon_name', '');

                return response()->json([

                    'error' => 'This Coupon Validity is over!',
                ]);

            }

        } else {

            Session::put('s_coupon_name', '');

            return response()->json([

                'error' => 'Invalid Coupon Code!',
            ]);
        }

    }

    public function checkout()
    {

        $carts = Cart::where('user_id', auth()->id())->get();

        if ($carts->isEmpty()) {
            return redirect()->route('cart')->with('checkout_error', 'Your cart is empty.');
        }

        $sub_total = 0;
        foreach ($carts as $cart) {
            $sub_total += ($cart->product_current_price * $cart->cart_amount);

        }

        // Set from the cart page. Reaching checkout without it (a direct visit, or
        // an expired session) previously threw a fatal error on a null lookup.
        $shipping = Shipping::where([

            'country_id' => Session::get('s_country_id'),
            'city_name' => Session::get('s_city_name'),
        ])->first();

        if (! $shipping) {
            return redirect()->route('cart')->with('checkout_error', 'Please choose your delivery country and city before checking out.');
        }

        $shipping_charge = $shipping->shipping_charge;

        if (Session::get('s_coupon_name')) {

            $coupon = coupon::where('coupon_name', Session::get('s_coupon_name'))->first();

            if ($coupon->coupon_type == 'Percentage') {

                $after_coupon_total = $sub_total - ($sub_total * ($coupon->coupon_ammount / 100));

            } else {

                $after_coupon_total = $sub_total - $coupon->coupon_ammount;

            }
        } else {

            $after_coupon_total = $sub_total;
        }

        $grand_total = $after_coupon_total + $shipping_charge;

        $carts->load(['relationtoproduct', 'relationtocolor', 'relationtosize']);
        $gateways = app(PaymentGatewayManager::class)->available();
        $currency = config('payment.currency', 'BDT');

        return view('checkout', compact('shipping_charge', 'sub_total', 'after_coupon_total', 'grand_total', 'carts', 'gateways', 'currency'));

    }
}
