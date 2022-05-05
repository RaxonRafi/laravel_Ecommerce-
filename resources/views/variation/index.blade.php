@extends('layouts.dashboard_master')
@section('dashboard_bar')
Variation Manager
@endsection

@section('content')



<div class="row">
    <div class="col-6">
        <div class="col-12">
         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Add Color </h4>
            </div>

           <div class="card-body">
               @if (session('success_message'))
               <div class="alert alert-success">
               {{session('success_message')}}
           </div>
          @endif
         <form action="{{route('add.color')}}" method="post" enctype="multipart/form-data">
           @csrf
           <div class="form-group">
             <label>Color Name</label>
             <input type="text" class=" form-control" name="color_name">
          </div>
         <div class="col-3 form-group">
            <label>Color</label>
            <input type="color" class=" form-control" name="color_code">
         </div>

         <div class="form-group">
           <button class="btn btn-success" type="submit">Add Color</button>
           </div>
         </form>
         </div>
      </div>
        <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Color List</h4>
            </div>
           <div class="card-body">
            <table class="table table-striped">
      <thead>
      <tr>
      <th scope="col">Color Name</th>
      <th scope="col">Color Code</th>

       </thead>
      <tbody>
      @foreach ($colors as $color)
      <tr>
      <td>{{$color->color_name}}</td>
      <td>
          <span class="badge" style="background-color:{{$color->color_code}}">&nbsp;</span>
      </td>
      </tr>
       @endforeach

      </tbody>
          </table>
         </div>
       </div>
        </div>

    </div>
    <div class="col-6">
        <div class="col-12">
         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Add Size</h4>
            </div>

           <div class="card-body">
               @if (session('success_message'))
               <div class="alert alert-success">
               {{session('success_message')}}
           </div>
          @endif
         <form action="{{route('add.size')}}" method="post" enctype="multipart/form-data">
           @csrf
           <div class="form-group">
             <label>Size Name</label>
             <input type="text" class=" form-control" name="size_name">
          </div>
         <div class="form-group">
           <button class="btn btn-success" type="submit">Add size</button>
           </div>
         </form>
         </div>
      </div>
        <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Size List</h4>
            </div>
           <div class="card-body">
            <table class="table table-striped">
      <thead>
      <tr>
      <th scope="col">Size Name</th>
       </thead>
      <tbody>
      @foreach ($sizes as $size)
      <tr>
      <td>{{$size->size_name}}</td>
      
      </tr>
       @endforeach

      </tbody>
          </table>
         </div>
       </div>
        </div>

    </div>
</div>




@endsection
