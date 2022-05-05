@extends('layouts.dashboard_master')
@section('dashboard_bar')
   Products
@endsection

@section('content')


    <div class="row">
        <div class="col-12">
          <div class="card">
          <div class="card-header text-white bg-primary">
            Product List
          </div>
  <div class="card-body table-responsive">
  <table class="table table-bodered"  id="category_table">
      <thead>
          <tr>
              <th>Sl No.</th>
              <th>Product Name</th>
              <th>Regular Price</th>
              <th>Discounted Price</th>
              <th>Product photo</th>
              <th>Action</th>
          </tr>
      </thead>
      <tbody>
          @foreach ($products as $product )
        <tr>
            <td>{{$loop->index+1}}</td>
              <td>{{$product->product_name}}</td>
              <td>{{$product->regular_price}}</td>
              <td>{{$product->discounted_price}}</td>
              <td>
                  <img width="100px" src="{{asset('uploads/product_thumbnail_photo')}}/{{$product->product_thumbnail_photo}}" alt="">
              </td>
              <td>
                    <div class="btn-group btn-group-sm">
                         <a type="button" class="btn btn-square btn-info btn-xs" href="{{route('add.featured.photo',$product->id)}}">Add Featured Photo</a>
                         <a type="button" class="btn btn-square btn-warning btn-xs" href="{{route('productdetails',$product->slug)}}" target="_blank">Preview</a>
                         <a type="button" class="btn btn-square btn-secondary btn-xs" href="{{route('add.inventory',$product->id)}}">Add to inventory</a>


                    </div>


                </td>

        </tr>
          @endforeach


      </tbody>
  </table>
  </div>
    </div>
        </div>
    </div>





@endsection
@section('footer_scripts')
<script>
    $(document).ready( function () {
    $('#category_table').DataTable();
} );
</script>
@endsection
