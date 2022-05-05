@extends('layouts.dashboard_master')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Products</h4>
  </div>

  <div class="card-body">
      @if (session('discount_error'))
    <div class="alert alert-danger">
        {{session('discount_error')}}
    </div>
      @endif
<form action="{{route('product.store')}}" method="post" enctype="multipart/form-data">
      @csrf
      <div class="row">
          <div class="col-4">
               <div class="form-group">
               <label>Product Name</label>
               <input type="text" class="form-control" name="product_name">
               </div>
          </div>
          <div class="col-4">
               <div class="form-group">
               <label>Regular price </label>
               <input type="text" class="form-control" name="regular_price">
               </div>
          </div>
          <div class="col-4">
               <div class="form-group">
               <label>Discounted price</label>
               <input type="text" class="form-control" name="discounted_price">
               @error('discounted_price')
                   <div class="alert alert-danger">
                       <span>{{$message}}</span>
                   </div>
                @enderror
               </div>
          </div>
      </div>
      <div class="row">
          <div class="col-12">
               <div class="form-group">
               <label>Short description</label>
               <textarea rows="4" type="text" class="form-control" name="short_description"></textarea>
               </div>
          </div>
          <div class="col-6">
               <div class="form-group">
               <label>Category</label>
               <select id="category_dropdown" class="form-control" name="category_id">
                   <option value="">--Select Category--</option>
                   @foreach ($categories as $category)
                      <option value="{{$category->id}}">{{$category->category_name}}</option>
                   @endforeach
               </select>
               </div>
          </div>
          <div class="col-6">
               <div class="form-group">
               <label>Subcategory</label>
               <select id="subcategory_dropdown" class="form-control" name="subcategory_id">
                   <option value="">--No data yet--</option>

               </select>
               </div>
          </div>
      </div>
          <div class="row">
          <div class="col-12">
               <div class="form-group">
               <label>Long description</label>
               <textarea id="long_desc"rows="4" type="text" class="form-control" name="long_description"></textarea>
               </div>
          </div>
         </div>
          <div class="row">
           <div class="col-3">
               <div class="form-group">
               <label>Weight</label>
               <input type="text" class="form-control" name="weight">
               </div>
          </div>
          <div class="col-3">
               <div class="form-group">
               <label>Dimensions</label>
               <input type="text" class="form-control" name="dimensions">
               </div>
          </div>
          <div class="col-3">
               <div class="form-group">
               <label>Materials</label>
               <input type="text" class="form-control" name="materials">
               </div>
          </div>
          <div class="col-3">
               <div class="form-group">
               <label>Other info</label>
               <textarea type="text" class="form-control" name="other_info"></textarea>
               </div>
          </div>

         </div>
<div class="row">
    <div class="col-12">
    <div class="form-group">
      <label>Product thumbnail photo</label>
      <input type="file" class="form-control" name="product_thumbnail_photo">
    </div>
    </div>
</div>
    <div class="form-group">
 <button class="btn btn-secondary" type="submit">Add Product</button>
    </div>

</form>
  </div>
</div>
        </div>
    </div>
</div>



@endsection
@section('footer_scripts')
<script>
    $(document).ready(function(){
       $('#category_dropdown').change(function(){
           var category_id = $(this).val();
        //ajax setup start
           $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
   $.ajax({
       type : 'POST',
       url : "{{route('get.subcategories')}}",
       data:{category_id:category_id},
       success: function(data){

           $('#subcategory_dropdown').html(data);
       }
   });
     //ajax setup end
       })
        $('#long_desc').summernote();
    });

</script>
@endsection

