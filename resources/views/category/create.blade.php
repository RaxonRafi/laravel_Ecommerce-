@extends('layouts.dashboard_master')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Create Category</h4>
  </div>

  <div class="card-body">
      @if (session('success_message'))
    <div class="alert alert-success">
        {{session('success_message')}}
    </div>
      @endif
<form action="{{route('category.store')}}" method="post" enctype="multipart/form-data">
      @csrf
    <div class="form-group">
      <label>Category Name</label>
      <input type="text" class="@error('category_name') is-invalid @enderror form-control" name="category_name">
      @error('category_name')
       <span>{{$message}}</span>
      @enderror
    </div>
    <div class="form-group">
      <label>Category photo</label>
      <input type="file" class="@error('category_photo') is-invalid @enderror form-control" name="category_photo">
      @error('category_photo')
       <span>{{$message}}</span>
      @enderror
    </div>
    <div class="form-group">
 <button class="btn btn-success" type="submit">Add Category</button>
    </div>

</form>
  </div>
</div>
        </div>
    </div>
</div>



@endsection
