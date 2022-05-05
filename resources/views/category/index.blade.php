@extends('layouts.dashboard_master')
@section('dashboard_bar')
   Categories
@endsection


@section('content')

<div class="container">
    <div class="row">
        <div class="col-12">
          <div class="card">
          <div class="card-header text-white bg-primary">
           <h4 class="card-title text-white">
            Category List
            <button data-toggle="modal" data-target="#exampleModalCenter" class="btn btn-danger"><i class="fa fa-trash fa-2x"></i></button>
                  <!-- Modal -->
             <div class="modal fade" id="exampleModalCenter">
                 <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h5 class="modal-title">Deleted Categories</h5>
                             <button type="button" class="close" data-dismiss="modal"><span>&times;</span>
                             </button>
                         </div>
                         <div class="modal-body">
       <table class="table table-bordered table-inverse">
           <thead class="thead-inverse">
               <tr>
                   <th>SL No.</th>
                   <th>Category Name</th>
                   <th>Action</th>
               </tr>
            </thead>
            <tbody>
                @foreach ($deleted_categories as $deleted_category)
                   <tr>
                       <td>{{$loop->index+1}}</td>
                       <td>{{$deleted_category->category_name}}</td>
                     <td>
                       <div class="btn-group">
                       <a type="button" href="{{route('category.restore',$deleted_category->id)}}" class="btn btn-square btn-outline-info btn-xs">Restore</a>
                       <a type="button" href="{{route('category.forcedelete',$deleted_category->id)}}" class="btn btn-square btn-outline-danger btn-xs">Permanent Delete</a>
                       </div>
                     </td>


                   </tr>
                @endforeach
             </tbody>
       </table>
</div>
        <div class="modal-footer">
        <button type="button" class="btn btn-dangerlight" data-dismiss="modal">Close</button>

                        </div>
                     </div>
                 </div>
             </div>
           </h4>
          </div>

  <div class="card-body table-responsive">
  <table class="table table-bodered"  id="category_table">
      <thead>
          <tr>
              <th>Sl No.</th>
              <th>Category Name</th>
              <th>Action</th>
          </tr>
      </thead>
      <tbody>
          @foreach ($categories as $category )
        <tr>
            <td>{{$loop->index+1}}</td>
              <td>{{$category->category_name}}</td>
                <td class="d-flex">
                    <form action="{{route('category.destroy',$category->id)}}" method="POST">
                        @csrf
                        @method('DELETE')
                    <button type="submit" class="btn btn-square  btn-outline-danger btn-sm">Delete</button>
                    </form>

                    <div class="ml-1">
                         <a class="btn btn-square  btn-outline-info btn-sm" href="{{route('category.show',$category->id)}}">View Details</a>


                    <a class="btn btn-square btn-outline-secondary btn-sm" href="{{route('category.edit',$category->id)}}">Edit Details</a>
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
</div>




@endsection
@section('footer_scripts')
<script>
    $(document).ready( function () {
    $('#category_table').DataTable();
} );
</script>
@endsection
