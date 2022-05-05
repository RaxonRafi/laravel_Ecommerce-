     <div class="product">
            <div class="thumb">
                <a href="{{url('product/details')}}/{{$product->slug}}" class="image">
                    <img src="{{asset('uploads/product_thumbnail_photo')}}/{{$product->product_thumbnail_photo}}" alt="Product" />
                </a>
                <span class="badges">
                    @if ($product->discounted_price!=$product->regular_price)
                   <span class="sale">-{{100-round(($product->discounted_price/$product->regular_price)*100, 1)}}%</span>
                    @endif
                @if (today()->diffindays($product->created_at)<7)
                      <span class="new">New</span>
                @endif
                </span>
                <div class="actions">
                    <a href="wishlist.html" class="action wishlist" title="Wishlist"><i
                            class="pe-7s-like"></i></a>
                    <a href="#" class="action quickview" data-link-action="quickview"
                        title="Quick view" data-bs-toggle="modal"
                        data-bs-target="#exampleModal"><i class="pe-7s-search"></i></a>
                    <a href="compare.html" class="action compare" title="Compare"><i
                            class="pe-7s-refresh-2"></i></a>
                </div>
                <a href="{{url('product/details')}}/{{$product->slug}}" class=" add-to-cart">Product Details</a>
            </div>
            <div class="content">
                <span class="ratings">
                    <span class="rating-wrap">
                        <span class="star" style="width: 80%"></span>
                    </span>
                    <span class="rating-num">( 4 Review )</span>
                </span>
                <h5 class="title"><a href="{{url('product/details')}}/{{$product->slug}}">{{$product->product_name}}</a>
                </h5>
                <span class="price">
                    <span class="new">${{$product->discounted_price}}</span>
                    @if($product->discounted_price !=$product->regular_price)
                      <span class="old">${{$product->regular_price}}</span>
                    @endif
                </span>
            </div>
        </div>
