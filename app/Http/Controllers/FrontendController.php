<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\coupon;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\product_featured_photo;
use App\Models\Team;

use Illuminate\Http\Request;

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


     }else{
        return response()->json([

            'error'=> 'Invalid Coupon Code!'
        ]);
     }


    }

}
