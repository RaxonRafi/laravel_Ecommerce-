@extends('layouts.dashboard_master')


@section('content')

<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title">Category Details</h4>
  </div>
  <div class="card-body">
<table class="table">

    <tbody>
        <tr>
            <td scope="row">Category Name</td>
            <td>{{$category->category_name}}</td>

        </tr>
       <tr>
            <td scope="row">Created By</td>
            <td>
            {{ App\Models\User::find($category->created_by)->name}}
            </td>
        </tr>
        <tr>
            <td scope="row">Created At</td>
            <td>
             {{$category->created_at->format('d/m/Y h:i:s a')}}
              @if ($category->created_at->diffinseconds()<60)
                <div  class="badge bg-dark text-white">Just Now</div>
                  @else
                 <div class="badge bg-dark text-white">{{$category->created_at->diffforhumans()}}</div>
                 @endif
            </td>
        </tr>
        <tr>
              <td scope="row"> Last Updated By</td>
              <td>

                  @if ($category->updated_by)
                  {{ App\Models\User::find($category->updated_by)->name}}

                  @else
      <span class="badge badge-dark">Not updated Yet</span>

                  @endif
              </td>
        </tr>
<tr>
    <td scope="row"> Last Updated At</td>
    <td>
        @if($category->updated_at)
         @if ($category->updated_at->diffinseconds()<60)
                <div  class="badge bg-dark text-white">Just Now</div>
                  @else
                 <div class="badge bg-dark text-white">{{$category->updated_at->diffforhumans()}}</div>
             @endif



        @else
      <span class="badge badge-dark">Not updated Yet</span>
     @endif
 </td>
</tr>
</tbody>
</table>
<div class="row">
    <div class="col-12 text-center">
        <a href="{{url()->previous()}}" class="btn btn-dark">Back</a>
    </div>
</div>
  </div>
</div>
        </div>
    </div>
</div>



@endsection
