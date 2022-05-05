@extends('layouts.dashboard_master')


@section('content')

<div class="container">
    <div class="row">
        <div class="col-12">
<div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Subcategory List</h4>
  </div>

  <div class="card-body">
  <table class="table table-bodered">
    @if ($subcategories->count()>0)
 <thead>
          <tr>
              <th>Sl No.</th>
              <th>category Name</th>
              <th>Subcategory Name</th>
              <th>Added By</th>
              <th>Action</th>
          </tr>
      </thead>
    @endif



      <tbody>
      @forelse ($subcategories as $subcategory )
        <tr>
            <td>{{$loop->index+1}}</td>

     <td>{{App\Models\Category::withTrashed()->find($subcategory->category_id)->category_name}}</td>
            <td>{{$subcategory->subcategory_name}}</td>
              <td>{{App\Models\User::find($subcategory->added_by)->name}}</td>

              <td>--</td>
          {{--  <td class="d-flex">
                    <form action="{{route('category.destroy',$category->id)}}" method="POST">
                        @csrf
                        @method('DELETE')
                    <button type="submit" class="btn btn-square  btn-outline-danger btn-sm">Delete</button>
                    </form>

                    <div class="ml-1">
                         <a class="btn btn-square  btn-outline-info btn-sm" href="{{route('category.show',$category->id)}}">View Details</a>


                    <a class="btn btn-square  btn-outline-warning btn-sm" href="{{route('category.edit',$category->id)}}">Edit Details</a>
                    </div>


               </td> --}}
        </tr>
        @empty
        <tr class="text-center text-danger">
            <td colspan="50" >
          no data to show
            </td>
        </tr>
          @endforelse


      </tbody>
  </table>
  </div>
</div>
        </div>
    </div>
</div>



@endsection
