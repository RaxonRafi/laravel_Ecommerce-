@extends('layouts.dashboard_master')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
  <div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Add Featured Photo</h4>
  </div>

  <div class="card-body">
      @if (session('discount_error'))
    <div class="alert alert-danger">
        {{session('discount_error')}}
    </div>
      @endif
      @if (session('file_error'))
    <div class="alert alert-danger">
        {{session('file_error')}}
    </div>
      @endif
<form action="{{route('add.featured.photo.post',$product_id)}}" method="post" enctype="multipart/form-data">
      @csrf
<div class="row">
    <div class="col-12">
    <div class="form-group">
      <label>Product Featured photo - {{$product->product_name}}</label>
      <input type="file" class="form-control" name="product_featured_photos[]" multiple>
    </div>
    </div>
</div>
    <div class="form-group">
 <button class="btn btn-secondary" type="submit">Add Featured Photo</button>
    </div>

</form>
  </div>
</div>
        </div>
    </div>
</div>
<div class="container">
<div class="row">
    <div class="col-12">
        <div class="card">
          <div class="card-header bg-primary">
            <h4 class="card-title  text-white ">Featured photos</h4>

          </div>
          <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>sl No</th>
                    <th>Featured Photos</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach ($product_featured_photos as $product_featured_photo)
                    <td>{{$loop->index+1}}</td>
                    <td>
                    <img width="100" src="{{asset('uploads/product_featured_photo')}}/{{$product_featured_photo->product_featured_photo_name}}" alt="no image Found">
                    </td>
                    <td>
                        <a class="btn btn-info" href="#">Delete</a>
                    </td>
                     @endforeach
                </tr>

            </tbody>
        </table>

          </div>
        </div>
    </div>
</div>
</div>



@endsection


