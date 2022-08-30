@extends('layouts.dashboard_master')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Create SubCategory</h4>
  </div>

  <div class="card-body">
      @if (session('success_message'))
    <div class="alert alert-success">
        {{session('success_message')}}
    </div>
      @endif
<form action="{{route('subcategory.store')}}" method="post">
      @csrf
    <div class="form-group">
      <label>Category Name</label>
      <select id="category_dropdown" class="@error('category_id') is-invalid @enderror form-control" name="category_id">
          <option value="">--Select--</option>
          @foreach ($categories as $category)
          <option value="{{$category->id}}">{{$category->category_name}}</option>
          @endforeach
      </select>
       @error('category_id')
       <span>{{$message}}</span>
      @enderror
    </div>
    <div class="form-group">
      <label>SubCategory Name</label>
      <input type="text" class="@error('subcategory_name') is-invalid @enderror form-control" name="subcategory_name">
      @error('subcategory_name')
       <span>{{$message}}</span>
      @enderror
    </div>
    <div class="form-group">
 <button class="btn btn-success" type="submit">Add SubCategory</button>
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
    $(document).ready(function() {
    $('#category_dropdown').select2();

});
</script>
@endsection
