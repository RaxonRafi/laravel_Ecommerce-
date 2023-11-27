@extends('layouts.frontend_master')

@section('content')

 <!-- breadcrumb-area start -->
    <div class="breadcrumb-area">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-12 text-center">
                    <h2 class="breadcrumb-title">Shop</h2>
                    <!-- breadcrumb-list start -->
                    <ul class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="{{route('index')}}">Home</a></li>
                        <li class="breadcrumb-item active">Cart</li>
                    </ul>
                    <!-- breadcrumb-list end -->
                </div>
            </div>
        </div>
    </div>

    <!-- breadcrumb-area end -->

    <!-- Cart Area Start -->
    <div class="cart-main-area pt-100px pb-100px">
        <div class="container">
            <h3 class="cart-page-title">Your cart items</h3>
            <div class="row" id="cart_main_section">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <form action="#">
                        <div class="table-content table-responsive cart-table-content">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Unit Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <@php
                                        $sub_total = 0;
                                    @endphp
                                    @forelse ($carts as $cart)
                                    <tr>
                                        <td class="product-thumbnail">
                                            <a href="#"><img class="img-responsive ml-15px"
                                                    src="{{asset('uploads/product_thumbnail_photo')}}/{{$cart->relationtoproduct->product_thumbnail_photo}}" alt="no image found" />


                                                </a>
                                        </td>
                                        <td class="product-name"><a href="#">
                                        {{$cart->relationtoproduct->product_name}}<br>
                                      Color: {{$cart->relationtocolor->color_name}}<br>
                                      Size: {{$cart->relationtosize->size_name}}
                                        </a></td>
                                        <td class="product-price-cart"><span class="amount">৳{{$cart->product_current_price}}</span></td>
                                        <td class="product-quantity">
                                            <div class="cart-plus-minus">
                                                <input id="cart-plus-minus-box" class="cart-plus-minus-box" type="text" name="qtybutton"
                                                    value="{{$cart->cart_amount}}" />
                                            </div>
                                        </td>
                                        <td class="product-subtotal">
                                            <span id="product_subtotal">{{$cart->product_current_price * $cart->cart_amount}}</span>

                                            @php
                                            $sub_total += ($cart->product_current_price*$cart->cart_amount);
                                            @endphp
                                        </td>
                                        <td class="product-remove">

                                            <a id="{{$cart->id}}" class="cart_item_delete_btn"><i class="fa fa-times"></i></a>
                                        </td>
                                    </tr>
                                 @empty
                              <tr>
                                  <td colspan="55" class="text-danger text-center">
                                      <b>No cart Items to show</b>
                                  </td>
                              </tr>


                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="cart-shiping-update-wrapper">
                                    <div class="cart-shiping-update">
                                        <a href="#">Continue Shopping</a>
                                    </div>
                                    <div class="cart-clear">
                                        <button>Update Shopping Cart</button>
                                        <a href="#">Clear Shopping Cart</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-lm-30px">
                            <div class="cart-tax">
                                <div class="title-wrap">
                                    <h4 class="cart-bottom-title section-bg-gray">Estimate Shipping And Tax</h4>
                                </div>
                                <div class="tax-wrapper">
                                    <p>Enter your destination to get a shipping estimate.</p>
                                    <div class="tax-select-wrapper">
                                        <div class="tax-select">
                                            <label>
                                                * Country
                                            </label>
                                            <select class="email s-email s-wid" id="country_dropdown">
                                                <option>--Select one country--</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{$country->country_id}}">{{$country->relationTocountry->name}}</option>
                                                @endforeach


                                            </select>
                                        </div>
                                        <div class="tax-select">
                                            <label>
                                                * Region / State
                                            </label>
                                            <select class="email s-email s-wid" id="city_dropdown" disabled>
                                                <option>--Select Country first--</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-lm-30px">
                            <div class="discount-code-wrapper">
                                <div class="title-wrap">
                                    <h4 class="cart-bottom-title section-bg-gray">Use Coupon Code</h4>
                                </div>
                                <div class="discount-code">
                                    <p>Enter your coupon code if you have one.</p>

                                        <input type="text" required="" name="name" id="cpn_name" />
                                       <div id="coupon_error" class="alert alert-danger d-none"></div>
                                        <button id="cpn_btn" class="d-none cart-btn-2" type="button">Apply Coupon</button>

                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 mt-md-30px">
                            <div class="grand-totall">
                                <div class="title-wrap">
                                    <h4 class="cart-bottom-title section-bg-gary-cart">Cart Total</h4>
                                </div>
                                <h5>Total products(৳)<span id="sub_total">{{$sub_total}}</span></h5>
                                <div>
                                    <h5>Total shipping (+) <span id="shipping_charge">0</span></h5>
                                </div>
                                <div >
                                    <h5>Discount (-) <span id="discount_ammount">0</span></h5>
                                </div>
                                <h4 class="grand-totall-title">Grand Total(৳) <span id="grand_total">{{$sub_total}}</span></h4>
                                <a class="d-none" id="checkout_btn" href="{{route('checkout')}}">Proceed to Checkout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Cart Area End -->


@endsection
@section('footer_scripts')
<script>
   /*----------------------------
        Cart Plus Minus Button
    ------------------------------ */
    var CartPlusMinus = $(".cart-plus-minus");
CartPlusMinus.prepend('<div class="dec qtybutton">-</div>');
CartPlusMinus.append('<div class="inc qtybutton">+</div>');

$(".qtybutton").on("click", function () {
    var $button = $(this);
    var oldValue = $button.parent().find("input").val();

    if ($button.text() === "+") {
        var newVal = parseFloat(oldValue) + 1;
    } else {
    
        if (oldValue > 1) {
            var newVal = parseFloat(oldValue) - 1;
        } else {
            newVal = 1;
        }
    }

    $button.parent().find("input").val(newVal);

    // // Update the product subtotal
    // var product_subtotal_element = document.getElementById("product_subtotal");
    // var product_subtotal = parseFloat(product_subtotal_element.innerText);
    // var result = product_subtotal * newVal;

    // // Update the content of the span with the calculated result
    // product_subtotal_element.innerText = result.toFixed(2);
});
$(document).ready(function(){



     $('.cart_item_delete_btn').click(function(){
      var cart_id =$(this).attr('id');
             //ajax setup start
           $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('cart.remove')}}",
       data:{cart_id:cart_id},
       success: function(data){

           // $("#cart_main_section").load(" #cart_main_section> *");
                  Swal.fire(
                                'Cart item removed successfully!',
                                'Importent!',
                                'warning'
                          )
                          location.reload();
       }
   });
     //ajax setup end
    });
    $('#country_dropdown').change(function(){
        $('#shipping_charge').html(0);
        $('#checkout_btn').addClass('d-none');
      var country_id = $(this).val();
                 //ajax setup start
           $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('get.city.list')}}",
       data:{country_id:country_id},
       success: function(data){

             $('#city_dropdown').attr('disabled', false);
             $('#city_dropdown').html(data);
       }
   });
     //ajax setup end
    });
       $('#city_dropdown').change(function(){

        $('#shipping_charge').html($(this).val());
        $('#checkout_btn').removeClass('d-none');
        var sub_total =$('#sub_total').html() ;


        var shipping_charge = $(this).val();

        var discount_ammount = $('#discount_ammount').html();

          var grand_total = parseInt(sub_total)+parseInt(shipping_charge) - parseInt(discount_ammount);
        // var grand_total = parseInt(sub_total) + parseInt(shipping_charge);
         $('#grand_total').html(grand_total);

         var country_id = $('#country_dropdown :selected').val();
         var city_name = $(this).children("option:selected").html();
          //ajax setup start
            $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('set.country.city')}}",
       data:{country_id:country_id,city_name:city_name},
       success: function(data){


       }
   });
     //ajax setup end




       });

       $('#cpn_name').keyup(function(){


        $('#cpn_btn').removeClass('d-none');


       });
       $('#cpn_btn').click(function(){

        const cpn_name = $('#cpn_name').val();
        const sub_total = "{{$sub_total}}";

            //ajax setup start
            $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('check.coupon')}}",
       data:{cpn_name:cpn_name,sub_total:sub_total},
       success: function(data){
          if(data.error){
            $('#coupon_error').removeClass('d-none');
            $('#coupon_error').html(data.error);
          }else{

             $('#coupon_error').addClass('d-none');
             $('#discount_ammount').html(data.coupon_type);
             $('#discount_ammount').html(data.coupon_ammount);

             const shipping_charge = $('#shipping_charge').html();
             $('#grand_total').html(data.grand_total + parseInt(shipping_charge) );



          }
       }
   });
     //ajax setup end



        });

});
</script>
@endsection
