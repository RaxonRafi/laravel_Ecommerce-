<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\product_featured_photo;
use App\Models\Size;
use App\Models\Subcategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkrole');
    }

    public function index()
    {

       $products = Product::latest()->get();
       return view('product.index',compact('products'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        return view('product.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->discounted_price == NULL){
            $discounted_price = $request->regular_price;
        }
        else{
               $discounted_price = $request->discounted_price;
        }
        if($request->discounted_price > $request->regular_price){
           return back()->with('discount_error', 'discounted price cannot be greater than regular price');
        }
        $slug = Str::slug($request->product_name)."-".Str::random(5);
        $sku = Str::random(10);
       $product_id = Product::insertGetId(  $request->except('_token','discounted_price')+[
        'discounted_price'=> $discounted_price,
        'slug'=> $slug,
        'sku'=> $sku,
        'created_at'=> Carbon::now()
        ]);
        if($request->hasFile('product_thumbnail_photo')){
            //photo upload code start
            //step:1-Profile photo name create Ex-(1.jpg)
            $new_name = auth()->id() . "-" . Str::random(5) . "." . $request->file('product_thumbnail_photo')->getClientOriginalExtension();
            //step:2-Profile photo upload
            $save_link = base_path("public/uploads/product_thumbnail_photo/") . $new_name;
            Image::make($request->file('product_thumbnail_photo'))->resize(270, 310)->save($save_link);
     //photo upload code end
     Product::find($product_id)->update([
        'product_thumbnail_photo'=> $new_name,
     ]);
        }
     return back();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
      //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
    public function getsubcategories(Request $request)
    {

        $subcategories=Subcategory::where('category_id', $request->category_id)->get();
        if ($subcategories->count() == 0){
            $str_to_send=" <option value=''>--No subcategory yet--</option>";
        }
        else{
            $str_to_send =  "<option value=''>--Choose One--</option>";
        }
        foreach($subcategories as $subcategory){
          $str_to_send.= "<option value='$subcategory->id'>$subcategory->subcategory_name</option>";

        }
        echo $str_to_send;
    }
    public function addfeaturedphoto($product_id){
        $product= Product::find($product_id);
        $product_featured_photos= product_featured_photo::where('product_id',$product_id)->get();
        return view('product.addfeaturedphoto', compact('product_id','product', 'product_featured_photos'));
    }
    public function addfeaturedphotopost(Request $request, $product_id){
        $status = true ;
        foreach ($request->file('product_featured_photos') as $key => $product_featured_photo){

            if(!in_array($product_featured_photo->getClientOriginalExtension(), ['jpg','png','webp'])){
                $status =false;
            }
        }
        if($status){
            foreach ($request->file('product_featured_photos') as $key => $product_featured_photo) {
                $new_name = $product_id . "-" . Str::random(5) . "." . $product_featured_photo->getClientOriginalExtension();
                //step:2-Profile photo upload
                $save_link = base_path("public/uploads/product_featured_photo/") . $new_name;
                Image::make($product_featured_photo)->resize(270, 310)->save($save_link);
                //photo upload code end
                product_featured_photo::insert([
                    'product_id' => $product_id,
                    'product_featured_photo_name' => $new_name,
                    'created_at' => carbon::now()
                ]);
            }
            return back();
        }
        else{

            return back()->with('file_error','file is not supported');
        }


    }
   public function addinventory($product_id)
    {
        $product =Product::find($product_id);
        $colors =Color::all();
        $sizes =Size::all();
        $inventories =Inventory::where('product_id',$product_id)->get();
        return view('product.addinventory', compact('product_id','product', 'colors', 'sizes', 'inventories'));
    }
   public function addinventorypost(Request $request, $product_id)
    {
     $is_exist = Inventory::where([
         'product_id' => $request->product_id,
         'size_id' => $request->size_id,
         'color_id' => $request->color_id,
     ])->exists();
     if($is_exist){
            Inventory::where([
                'product_id' => $request->product_id,
                'size_id' => $request->size_id,
                'color_id' => $request->color_id,
            ])->increment('quantity', $request->quantity);
     }
     else{
        Inventory::insert([
            'product_id' => $request->product_id,
            'size_id' => $request->size_id,
            'color_id' => $request->color_id,
            'quantity' => $request->quantity,
            'created_at' => Carbon::now()
        ]);
     }

        return back();

    }

}
