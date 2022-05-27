<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $carts = Cart::where('user_id', auth()->id())->get();
            return view('cart', compact('carts'));

        }else{
            return redirect('login');
        }

    }
    public function cartremove(Request $request)
    {
        Cart::find($request->cart_id)->delete();
   
    }
}


