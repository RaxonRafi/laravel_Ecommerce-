<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\product_featured_photo;
use App\Models\Shipping;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FrontendController extends Controller
{
   public function index(){
       $categories = Category::all();
       $products = Product::latest()->get();
        return view('index',compact('categories','products'));
   }
   public function about(){
       return view('about');
   }

   public function contact(){
       return view('contact');
   }

   public function productdetails($slug){


       $product = Product::where('slug',$slug)->first();
        $product_featured_photos = product_featured_photo::where('product_id', $product->id)->get();
       $related_products = Product::where('subcategory_id',$product->subcategory_id)->where('id','!=',$product->id)->get();
       $inventories = Inventory::where('product_id', $product->id)->select('color_id')->groupBy('color_id')->get();
       $total_inventory = Inventory::where('product_id', $product->id)->sum('quantity');
        return view('productdetails',compact('product', 'related_products', 'product_featured_photos', 'inventories','total_inventory'));
   }
    public function getsizes(Request $request)
    {
     $str_size = "<option>--select size--</option>";
     $sizes =  Inventory::where([

            'product_id'=> $request->product_id,
            'color_id'=> $request->color_id

        ])->get();
          foreach($sizes as $size){
            $str_size .= "<option value='$size->size_id'>".$size->relationtosize->size_name."</option>";
          }
          echo $str_size;
    }
    public function getinventory(Request $request)
    {
        echo  Inventory::where([
            'product_id' => $request->product_id,
            'color_id' => $request->color_id,
            'size_id' => $request->size_id
        ])->first()->quantity;
    }

    public function checkcoupon(Request $request){


     if(coupon::where('coupon_name', $request->cpn_name)->exists()){

        $coupon = coupon::where('coupon_name',$request->cpn_name)->first();


        if(Carbon::today() <= $coupon->coupon_validity_date){



            if($coupon->minimum_order > $request->sub_total ){

                Session::put('s_coupon_name','');


                return response()->json([

                    'error'=> 'You need to order minimum'.$coupon->minimum_order
                ]);


            }else{

                if( $coupon->coupon_limit == 0){

                    Session::put('s_coupon_name','');


                    return response()->json([

                        'error'=> 'Coupon Usage Limit is Over!'
                    ]);

                }else{

                    Session::put('s_coupon_name',$request->cpn_name);

                    if($coupon->coupon_type == 'Percentage'){

                        $grand_total = $request->sub_total-($request->sub_total*($coupon->coupon_ammount/100));

                    }else{

                        $grand_total =  $request->sub_total - $coupon->coupon_ammount;

                    }
                    return response()->json([

                      'coupon_type' => $coupon->coupon_type,
                      'coupon_ammount'=> $coupon->coupon_ammount,
                      'grand_total' => $grand_total

                    ]);


                }

            }


        }else{

            Session::put('s_coupon_name','');

            return response()->json([

                'error'=> 'This Coupon Validity is over!'
            ]);

        }


     }else{

        Session::put('s_coupon_name','');

        return response()->json([

            'error'=> 'Invalid Coupon Code!'
        ]);
     }


    }

    public function checkout(){


        $sub_total = 0;
        foreach(Cart::where('user_id',auth()->user()->id)->get() as $cart){
             $sub_total +=  ($cart->product_current_price*$cart->cart_amount);

        }

     $shipping_charge = Shipping::where([

            'country_id' => Session::get('s_country_id'),
            'city_name' =>  Session::get('s_city_name')
        ])->first()->shipping_charge;



        if(Session::get('s_coupon_name')){



            $coupon = coupon::where('coupon_name', Session::get('s_coupon_name'))->first();

            if($coupon->coupon_type == 'Percentage'){

                $after_coupon_total = $sub_total-($sub_total*($coupon->coupon_ammount/100));

            }else{

                $after_coupon_total =  $sub_total - $coupon->coupon_ammount;

            }
        }else{

            $after_coupon_total = $sub_total;
        }

        $grand_total = $after_coupon_total + $shipping_charge;

        return view('checkout', compact('shipping_charge','sub_total','after_coupon_total','grand_total'));

       }

}
