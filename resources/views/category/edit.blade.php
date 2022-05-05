@extends('layouts.dashboard_master')


@section('content')

<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title">Edit Category</h4>
  </div>

  <div class="card-body">
      @if (session('success_message'))
    <div class="alert alert-success">
        {{session('success_message')}}
    </div>
      @endif
<form action="{{route('category.update',$category->id)}}" method="POST">
      @csrf
      @method('PATCH')
    <div class="form-group">
      <label>Category Name</label>
      <input type="text" class="@error('category_name') is-invalid @enderror form-control" name="category_name" value="{{$category->category_name}}">
      @error('category_name')
       <span>{{$message}}</span>
      @enderror
    </div>
    <div class="form-group">
 <button class="btn btn-warning" type="submit">Edit Category</button>
    </div>

</form>
  </div>
</div>
        </div>
    </div>
</div>



@endsection
