@extends('layouts.dashboard_master')
@section('dashboard_bar')
Shipping Chart
@endsection

@section('content')

  <div class="row">
    <div class="col-6">
        <div class="col-12">
         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Country List </h4>
            </div>

           <div class="card-body">
               @if (session('success_message'))
               <div class="alert alert-success">
               {{session('success_message')}}
           </div>
          @endif
               @if (session('errors'))
               <div class="alert alert-danger">
               {{session('errors')}}
           </div>
          @endif
         <form action="{{route('add.shipping')}}" method="post" enctype="multipart/form-data">
           @csrf
           <div class="form-group">
             <label>Country Name</label>
             <select class="form-control" name="country_id">
                 <option value="">--Select one--</option>
                 @foreach ($countries as $country)
                 <option value="{{$country->id}}">{{$country->name}} ({{$country->code}})</option>
                 @endforeach

             </select>
          </div>
         <div class="form-group">
            <label>City</label>
            <input type="text" class="form-control" name="city_name">
         </div>
         <div class="form-group">
            <label>Shipping charge</label>
            <input type="text" class="form-control" name="shipping_charge">
         </div>

         <div class="form-group">
           <button class="btn btn-success" type="submit">Add Shipping</button>
           </div>
         </form>
         </div>
      </div>

        </div>

    </div>
    <div class="col-6">
        <div class="col-12">
         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Shipping Charge</h4>
            </div>

           <div class="card-body">
               <table class="table table-striped table-bordered">
                   <thead>
                       <tr>
                           <th>Country</th>
                           <th>City</th>
                           <th>Charge(Tk.)</th>
                       </tr>
                   </thead>
                   <tbody>
                       @foreach ($shippings as $shipping)
                          <tr>
                           <td>{{ $shipping->relationTocountry->name}}</td>
                           <td>{{ $shipping->city_name}}</td>
                           <td>{{$shipping->shipping_charge}}</td>
                       </tr>

                       @endforeach


                   </tbody>
               </table>

           </div>


         </div>
      </div>
        </div>

    </div>
</div>





@endsection

