<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Shipping;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CustomerController extends Controller
{
    public function Customerlogin(){
        return view('customer.customerlogin');
    }

    public function customerregister(Request $request){

          User::insert([
               'name'=> $request->name,
               'email'=>$request->email,
               'phone_number'=> $request->phone_number,
               'address'=> $request->address,
               'password'=> bcrypt($request->password),
               'created_at'=>Carbon::now(),
               'role'=> 'customer'
           ]);
        $url = "http://66.45.237.70/api.php";
        $number = " $request->phone_number";
        $text = "Hello  $request->name You are registered successfully in goldfish ecommerce";
        $data = array(
            'username' => "01834833973",
            'password' => "TE47RSDM",
            'number' => "$number",
            'message' => "$text"
        );

        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsresult = curl_exec($ch);
        $p = explode("|", $smsresult);
        $sendstatus = $p[0];
           return back()->with('customer_registration','registration completed successfully!');
    }
    public function customerdashboard (){
        return view('customer.customerdashboard');
    }
    public function insertcart (Request $request){

        $is_exists = Cart::where([
            'product_id' => $request->product_id,
            'color_id' => $request->color_id,
            'size_id' => $request->size_id,
            'user_id' => $request->user_id

        ])->exists();

        $cart_amount_status = 0;

        if($is_exists){
            Cart::where([
                'product_id' => $request->product_id,
                'color_id' => $request->color_id,
                'size_id' => $request->size_id,
                'user_id' => $request->user_id
            ])->increment('cart_amount',$request->cart_amount);

        }else{
            Cart::insert([
                'product_id'=> $request->product_id,
                'product_current_price'=>$request->product_current_price,
                'color_id'=> $request->color_id,
                'size_id'=> $request->size_id,
                'cart_amount'=> $request->cart_amount,
                'user_id'=> $request->user_id,
                'created_at'=> Carbon::now()
            ]);
          $cart_amount_status = 1;
        }

        return response()->json([
         'cart_amount_status'=> $cart_amount_status
        ]);


    }
    public function cart()
    {
        if(Auth::check()){
            $countries = Shipping::select('country_id')->groupBy('country_id')->get();
            $carts = Cart::where('user_id', auth()->id())->get();
            return view('cart', compact('carts', 'countries'));

        }else{
            return redirect('login');
        }

    }
    public function getcitylist(Request $request)
    {
        $select_option = "<option value=''>--Select city--</option>";
        $cities = Shipping::where('country_id', $request->country_id)->get();

        foreach( $cities as $city){
            $select_option .= "<option value='$city->shipping_charge'>$city->city_name</option>";

        }
        echo $select_option;


    }
    public function cartremove(Request $request)
    {
        Cart::find($request->cart_id)->delete();

    }
    public function setcountrycity(Request $request){
        
        Session::put('s_country_id',$request->country_id);
        Session::put('s_city_name',$request->city_name);
    }

}


