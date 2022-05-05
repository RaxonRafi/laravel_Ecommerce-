@extends('layouts.frontend_master')

@section('content')

    <!-- OffCanvas Wishlist Start -->
    <div id="offcanvas-wishlist" class="offcanvas offcanvas-wishlist">
        <div class="inner">
            <div class="head">
                <span class="title">Wishlist</span>
                <button class="offcanvas-close">×</button>
            </div>
            <div class="body customScroll">
                <ul class="minicart-product-list">
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/1.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Women's Elizabeth Coat</a>
                            <span class="quantity-price">1 x <span class="amount">$21.86</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/2.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Long sleeve knee length</a>
                            <span class="quantity-price">1 x <span class="amount">$13.28</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/3.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Cool Man Wearing Leather</a>
                            <span class="quantity-price">1 x <span class="amount">$17.34</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="foot">
                <div class="buttons">
                    <a href="wishlist.html" class="btn btn-dark btn-hover-primary mt-30px">view wishlist</a>
                </div>
            </div>
        </div>
    </div>
    <!-- OffCanvas Wishlist End -->
    <!-- OffCanvas Cart Start -->
    <div id="offcanvas-cart" class="offcanvas offcanvas-cart">
        <div class="inner">
            <div class="head">
                <span class="title">Cart</span>
                <button class="offcanvas-close">×</button>
            </div>
            <div class="body customScroll">
                <ul class="minicart-product-list">
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/1.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Women's Elizabeth Coat</a>
                            <span class="quantity-price">1 x <span class="amount">$18.86</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/2.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Long sleeve knee length</a>
                            <span class="quantity-price">1 x <span class="amount">$43.28</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                    <li>
                        <a href="single-product.html" class="image"><img src="assets/images/product-image/3.jpg"
                                alt="Cart product Image"></a>
                        <div class="content">
                            <a href="single-product.html" class="title">Cool Man Wearing Leather</a>
                            <span class="quantity-price">1 x <span class="amount">$37.34</span></span>
                            <a href="#" class="remove">×</a>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="foot">
                <div class="buttons mt-30px">
                    <a href="{{route('cart')}}" class="btn btn-dark btn-hover-primary mb-30px">view cart</a>
                    <a href="checkout.html" class="btn btn-outline-dark current-btn">checkout</a>
                </div>
            </div>
        </div>
    </div>
    <!-- OffCanvas Cart End -->

    <!-- OffCanvas Menu Start -->
    <div id="offcanvas-mobile-menu" class="offcanvas offcanvas-mobile-menu">
        <button class="offcanvas-close"></button>

        <div class="inner customScroll">

            <div class="offcanvas-menu mb-4">
                <ul>
                    <li><a href="#"><span class="menu-text">Home</span></a>
                        <ul class="sub-menu">
                            <li><a href="index.html"><span class="menu-text">Home 1</span></a></li>
                            <li><a href="index-2.html"><span class="menu-text">Home 2</span></a></li>
                        </ul>
                    </li>
                    <li><a href="#"><span class="menu-text">Shop</span></a>
                        <ul class="sub-menu">
                            <li>
                                <a href="#"><span class="menu-text">Shop Page</span></a>
                                <ul class="sub-menu">
                                    <li class="title"><a href="#">Shop Page</a></li>
                                    <li><a href="shop-3-column.html">Shop 3 Column</a></li>
                                    <li><a href="shop-4-column.html">Shop 4 Column</a></li>
                                    <li><a href="shop-left-sidebar.html">Shop Left Sidebar</a></li>
                                    <li><a href="shop-right-sidebar.html">Shop Right Sidebar</a></li>
                                    <li><a href="shop-list-left-sidebar.html">Shop List Left Sidebar</a></li>
                                    <li><a href="shop-list-right-sidebar.html">Shop List Right Sidebar</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#"><span class="menu-text">product Details Page</span></a>
                                <ul class="sub-menu">
                                    <li><a href="single-product.html">Product Single</a></li>
                                    <li><a href="single-product-variable.html">Product Variable</a></li>
                                    <li><a href="single-product-affiliate.html">Product Affiliate</a></li>
                                    <li><a href="single-product-group.html">Product Group</a></li>
                                    <li><a href="single-product-tabstyle-2.html">Product Tab 2</a></li>
                                    <li><a href="single-product-tabstyle-3.html">Product Tab 3</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#"><span class="menu-text">Single Product Page</span></a>
                                <ul class="sub-menu">
                                    <li><a href="single-product-slider.html">Product Slider</a></li>
                                    <li><a href="single-product-gallery-left.html">Product Gallery Left</a>
                                    </li>
                                    <li><a href="single-product-gallery-right.html">Product Gallery Right</a>
                                    </li>
                                    <li><a href="single-product-sticky-left.html">Product Sticky Left</a></li>
                                    <li><a href="single-product-sticky-right.html">Product Sticky Right</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#"><span class="menu-text">Other Shop Pages</span></a>
                                <ul class="sub-menu">
                                    <li><a href="cart.html">Cart Page</a></li>
                                    <li><a href="checkout.html">Checkout Page</a></li>
                                    <li><a href="compare.html">Compare Page</a></li>
                                    <li><a href="wishlist.html">Wishlist Page</a></li>
                                    <li><a href="my-account.html">Account Page</a></li>
                                    <li><a href="login.html">Login & Register Page</a></li>
                                    <li><a href="empty-cart.html">Empty Cart Page</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#"><span class="menu-text">Pages</span></a>
                                <ul class="sub-menu">
                                    <li><a href="404.html">404 Page</a></li>
                                    <li><a href="privacy-policy.html">Privacy Policy</a></li>
                                    <li><a href="faq.html">Faq Page</a></li>
                                    <li><a href="coming-soon.html">Coming Soon Page</a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li><a href="#"><span class="menu-text">Blog</span></a>
                        <ul class="sub-menu">
                            <li><a href="blog-grid.html">Blog Grid Page</a></li>
                            <li><a href="blog-grid-left-sidebar.html">Grid Left Sidebar</a></li>
                            <li><a href="blog-grid-right-sidebar.html">Grid Right Sidebar</a></li>
                            <li><a href="blog-single.html">Blog Single Page</a></li>
                            <li><a href="blog-single-left-sidebar.html">Single Left Sidebar</a></li>
                            <li><a href="blog-single-right-sidebar.html">Single Right Sidbar</a>
                        </ul>
                    </li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                </ul>
            </div>
            <!-- OffCanvas Menu End -->
            <div class="offcanvas-social mt-auto">
                <ul>
                    <li>
                        <a href="#"><i class="fa fa-facebook"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-twitter"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-google"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-youtube"></i></a>
                    </li>
                    <li>
                        <a href="#"><i class="fa fa-instagram"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- OffCanvas Menu End -->


    <!-- breadcrumb-area start -->
    <div class="breadcrumb-area">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-12 text-center">
                    <h2 class="breadcrumb-title">Products</h2>
                    <!-- breadcrumb-list start -->
                    <ul class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="{{route('index')}}">Home</a></li>
                        <li class="breadcrumb-item active">{{$product->product_name}}</li>
                    </ul>
                    <!-- breadcrumb-list end -->
                </div>
            </div>
        </div>
    </div>

    <!-- breadcrumb-area end -->


    <!-- Product Details Area Start -->
<div class="product-details-area pt-100px pb-100px">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-sm-12 col-xs-12 mb-lm-30px mb-md-30px mb-sm-30px">
                <!-- Swiper -->
                <div class="swiper-container zoom-top">
                    <div class="swiper-wrapper">
                          <div class="swiper-slide zoom-image-hover">
                            <img class="img-responsive m-auto" src="{{asset('uploads/product_thumbnail_photo')}}/{{$product->product_thumbnail_photo}}"
                                alt="">
                         </div>
                    @foreach ($product_featured_photos as $product_featured_photo)
                        <div class="swiper-slide zoom-image-hover">
                            <img class="img-responsive m-auto" src="{{asset('uploads/product_featured_photo')}}/{{$product_featured_photo->product_featured_photo_name}}"
                                alt="">
                        </div>
                    @endforeach
                        <div class="swiper-slide zoom-image-hover">
                            <img class="img-responsive m-auto" src="assets/images/product-image/zoom-image/2.jpg"
                                alt="">
                        </div>
                        <div class="swiper-slide zoom-image-hover">
                            <img class="img-responsive m-auto" src="assets/images/product-image/zoom-image/3.jpg"
                                alt="">
                        </div>
                        <div class="swiper-slide zoom-image-hover">
                            <img class="img-responsive m-auto" src="assets/images/product-image/zoom-image/4.jpg"
                                alt="">
                        </div>
                    </div>
                </div>
                <div class="swiper-container zoom-thumbs mt-3 mb-3">
                    <div class="swiper-wrapper">
                           <div class="swiper-slide">
                                <img class="img-responsive m-auto" src="{{asset('uploads/product_thumbnail_photo')}}/{{$product->product_thumbnail_photo}}"
                                alt="">
                            </div>
                        @foreach ($product_featured_photos as $product_featured_photo)
                            <div class="swiper-slide">
                                <img class="img-responsive m-auto" src="{{asset('uploads/product_featured_photo')}}/{{$product_featured_photo->product_featured_photo_name}}"
                                alt="">
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-sm-12 col-xs-12" data-aos="fade-up" data-aos-delay="200">
                <div class="product-details-content quickview-content">
                    @php
                      $product_id =$product->id;
                      $product_current_price =$product->discounted_price;
                    @endphp
                    <h2>{{$product->product_name}}</h2>
                    <div class="pricing-meta">
                        <ul>
                            <li class="old-price not-cut">${{$product->discounted_price}}</li>
                                @if($product->discounted_price !=$product->regular_price)
                                <s class="text-success">${{$product->regular_price}}</s>
                                @endif

                        </ul>
                    </div>
                    <div class="pro-details-rating-wrap">
                        <div class="rating-product">
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star"></i>
                        </div>
                        <span class="read-review"><a class="reviews" href="#">( 5 Customer Review )</a></span>
                    </div>
                <div class="pro-details-color-info d-flex align-items-center">
                    <input type="hidden" id="in_color_id">
                    <input type="hidden"  id="in_size_id">
                        <span>Color</span>
                        <div class="pro-details-color">
                            <ul>
                                  @forelse ($inventories as $inventory)
                                 <li id="{{$inventory->color_id}}" class="color_variation" title="{{$inventory->relationtocolor->color_name}}"><a class="{{($loop->first==1) ? 'active-color' : ''}}" style="background-color: {{$inventory->relationtocolor->color_code}}" ></a></li>
                                @empty
                                   <li class="badge bg-danger">
                                       No Color Available
                                   </li>

                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <!-- Sidebar single item -->
                    <div class="pro-details-size-info d-flex align-items-center">
                        <span>Size</span>
                        <div class="pro-details-size">
                            <ul>

                               <select id="size_dropdown" class="form-control">
                                   <option value="">--Choose color First --</option>

                               </select>
                            </ul>
                        </div>
                    </div>
                    <p class="text-dark">
                        Available in stock:
                        <span class="badge bg-success" id="available_stock">
                              {{$total_inventory}}
                        </span>
                    </p>


                    <p class="mt-30px mb-0">{{$product->short_description}}</p>
                    <div class="pro-details-quality">
                        <div class="cart-plus-minus">
                            <input id="cart_amount" class="cart-plus-minus-box" type="text" name="qtybutton" value="" />
                        </div>
                        <div class="pro-details-cart">
                            <button id="cart_btn" class="add-cart" href="#"> Add To
                                Cart</button>
                                @auth
                                 <input type="hidden" id="login_status" value="yes">
                                @else
                                  <input type="hidden" id="login_status" value="no">
                                @endauth
                        </div>
                        <div class="pro-details-compare-wishlist pro-details-wishlist ">
                            <a href="wishlist.html"><i class="pe-7s-like"></i></a>
                        </div>
                        <div class="pro-details-compare-wishlist pro-details-compare">
                            <a href="compare.html"><i class="pe-7s-refresh-2"></i></a>
                        </div>
                    </div>
                    <div class="pro-details-sku-info pro-details-same-style  d-flex">
                        <span>SKU: </span>
                        <ul class="d-flex">
                            <li>
                            {{$product->sku}}
                            </li>
                        </ul>
                    </div>
                    <div class="pro-details-categories-info pro-details-same-style d-flex">
                        <span>Categories: </span>
                        <ul class="d-flex">
                            <li>
                                <a href="#">{{App\Models\Category::find($product->category_id)->category_name}}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="pro-details-social-info pro-details-same-style d-flex">
                        <span>Share: </span>
                        <ul class="d-flex">
                            <li>
                                <div class="fb-share-button" data-href="https://developers.facebook.com/docs/plugins/" data-layout="button_count" data-size="small"><a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{url()->full()}}&;src=sdkpreparse" class="fb-xfbml-parse-ignore"></a></div>

                            </li>
                            <li>
                                <a href="http://www.twitter.com/intent/tweet?url={{url()->full()}}&via=TWITTER_HANDLE_HERE&text={{$product->product_name}}" target="_blank" class="share-popup"><i class="fa fa-twitter"></i></a>

                            </li>
                            <li>
                                <a href="#"><i class="fa fa-google"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="fa fa-youtube"></i></a>
                            </li>
                            <li>
                                <a href="#"><i class="fa fa-instagram"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- product details description area start -->
<div class="description-review-area pb-100px" data-aos="fade-up" data-aos-delay="200">
    <div class="container">
        <div class="description-review-wrapper">
            <div class="description-review-topbar nav">
                <a data-bs-toggle="tab" href="#des-details2">Information</a>
                <a class="active" data-bs-toggle="tab" href="#des-details1">Description</a>
                <a data-bs-toggle="tab" href="#des-details3">Reviews (02)</a>
            </div>
            <div class="tab-content description-review-bottom">
                <div id="des-details2" class="tab-pane">
                    <div class="product-anotherinfo-wrapper text-start">
                        <ul>
                            <li><span>Weight</span>{{$product->weight}}</li>
                            <li><span>Dimensions</span>{{$product->dimensions}}</li>
                            <li><span>Materials</span> {{$product->materials}}</li>
                            <li><span>Other Info</span>{{$product->other_info}}</li>
                        </ul>
                    </div>
                </div>
                <div id="des-details1" class="tab-pane active">
                    <div class="product-description-wrapper">
                        <p>{{$product->long_description}}</p>
                    </div>
                </div>
                <div id="des-details3" class="tab-pane">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="review-wrapper">
                                <div class="single-review">
                                    <div class="review-img">
                                        <img src="assets/images/review-image/1.png" alt="" />
                                    </div>
                                    <div class="review-content">
                                        <div class="review-top-wrap">
                                            <div class="review-left">
                                                <div class="review-name">
                                                    <h4>White Lewis</h4>
                                                </div>
                                                <div class="rating-product">
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                </div>
                                            </div>
                                            <div class="review-left">
                                                <a href="#">Reply</a>
                                            </div>
                                        </div>
                                        <div class="review-bottom">
                                            <p>
                                                Vestibulum ante ipsum primis aucibus orci luctustrices posuere
                                                cubilia Curae Suspendisse viverra ed viverra. Mauris ullarper
                                                euismod vehicula. Phasellus quam nisi, congue id nulla.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="single-review child-review">
                                    <div class="review-img">
                                        <img src="assets/images/review-image/2.png" alt="" />
                                    </div>
                                    <div class="review-content">
                                        <div class="review-top-wrap">
                                            <div class="review-left">
                                                <div class="review-name">
                                                    <h4>White Lewis</h4>
                                                </div>
                                                <div class="rating-product">
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                    <i class="fa fa-star"></i>
                                                </div>
                                            </div>
                                            <div class="review-left">
                                                <a href="#">Reply</a>
                                            </div>
                                        </div>
                                        <div class="review-bottom">
                                            <p>Vestibulum ante ipsum primis aucibus orci luctustrices posuere
                                                cubilia Curae Sus pen disse viverra ed viverra. Mauris ullarper
                                                euismod vehicula.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="ratting-form-wrapper pl-50">
                                <h3>Add a Review</h3>
                                <div class="ratting-form">
                                    <form action="#">
                                        <div class="star-box">
                                            <span>Your rating:</span>
                                            <div class="rating-product">
                                                <i class="fa fa-star"></i>
                                                <i class="fa fa-star"></i>
                                                <i class="fa fa-star"></i>
                                                <i class="fa fa-star"></i>
                                                <i class="fa fa-star"></i>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="rating-form-style">
                                                    <input placeholder="Name" type="text" />
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="rating-form-style">
                                                    <input placeholder="Email" type="email" />
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="rating-form-style form-submit">
                                                    <textarea name="Your Review" placeholder="Message"></textarea>
                                                    <button class="btn btn-primary btn-hover-color-primary "
                                                        type="submit" value="Submit">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- product details description area end -->

    <!-- Related product Area Start -->
    <div class="related-product-area pb-100px">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-title text-center mb-30px0px line-height-1">
                        <h2 class="title m-0">Related Products</h2>
                    </div>
                </div>
            </div>
            <div class="new-product-slider swiper-container slider-nav-style-1 small-nav">
                <div class="new-product-wrapper swiper-wrapper">
                    @foreach ($related_products as $product)

                    <div class="new-product-item swiper-slide">
                        <!-- Single Prodect -->
                     @include('parts.product')
                    </div>
                  @endforeach
                </div>
                <!-- Add Arrows -->
                <div class="swiper-buttons">
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Related product Area End -->

@endsection
@section('footer_scripts')
<script>
    $(document).ready(function(){

        $('.color_variation').click(function(){

            var color_id = $(this).attr('id');
            var product_id = {{$product_id}};
            $('#in_color_id').val(color_id );
            $('#in_size_id').val("");
               //ajax setup start
           $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('get.sizes')}}",
       data:{color_id:color_id,product_id:product_id},
       success: function(data){
            $('#size_dropdown').html(data);

       }
   });
     //ajax setup end
     $('#size_dropdown').change(function(){

        var size_id = $(this).val();
        $('#in_size_id').val(size_id);
        var color_id = $('#in_color_id').val();
        var product_id = {{$product_id}};
                       //ajax setup start
           $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('get.inventory')}}",
       data:{color_id:color_id,product_id:product_id , size_id:size_id},
       success: function(data){

           $('#available_stock').html(data);

       }
   });
     //ajax setup end


     });


        });
        $('#cart_btn').click(function(){
            if($('#login_status').val() == 'no'){
                Swal.fire({
                title: 'You are not logged in!',
                text: "Please Log in first!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Go to Login!'
              }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{route('customerlogin')}}";

                }
              })
            }else{
                 var available_stock = parseInt($('#available_stock').html());
                 var cart_amount = parseInt($('#cart_amount').val());

                 if(cart_amount > available_stock){
                        Swal.fire(
                          'Stock not available!',
                          'You are asking more!',
                          'warning'
                          )
                    }else{
                        if(isNaN(cart_amount)){
                            Swal.fire(
                          'Please Choose Size and Color First!',
                          'Importent!',
                          'warning'
                          )
                        }else{
                            if(cart_amount<= 0){
                                Swal.fire(
                                'Cart amount cannot be less or Equal to 0!',
                                'Importent!',
                                'warning'
                          )

                            }else{
                                var product_id = "{{$product_id}}";
                                var product_current_price  = "{{$product_current_price }}";
                                var color_id = $("#in_color_id").val();
                                var size_id = $("#in_size_id").val();
                                var cart_amount = $("#cart_amount").val();
                                var user_id = "{{auth()->id()}}";
                                //ajax setup start
                                $.ajaxSetup({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                                });
                                $.ajax({
                                    type : 'POST',
                                    url : "{{route('insert.cart')}}",
                                    data:{product_id:product_id, product_current_price:product_current_price, color_id:color_id, size_id:size_id, cart_amount:cart_amount, user_id:user_id},
                                    success: function(data){

                                        $('#header_cart_num').html(data.cart_amount_status +parseInt($('#header_cart_num').html()));
                                         Swal.fire(
                                        'Product added to cart successfully!',
                                        'Congrats!',
                                        'Success'
                                )


                                    }
                                });
                             //ajax setup end

                            }


                        };

                    }
            }
        })
    });
</script>
@endsection
