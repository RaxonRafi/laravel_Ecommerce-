@extends('layouts.dashboard_master')
@section('dashboard_bar')
Generate Coupon
@endsection

@section('content')

  <div class="row">
    <div class="col-4">

         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Generated Coupon List </h4>
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
         <form action="{{route('add.coupon')}}" method="post">
           @csrf
           <div class="form-group">
             <label>Coupon Name</label>
             <input type="text" class="form-control" name="coupon_name">

          </div>
         <div class="form-group">
            <label>Coupon validity date</label>
            <input type="date" class="form-control" name="coupon_validity_date">
         </div>
         <div class="form-group">
            <label>coupon type</label>
            <select class="form-control" name="coupon_type" id="">
                <option value="Fixed">Fixed Ammount</option>
                <option value="Percentage">Percentage</option>

            </select>
         </div>
         <div class="form-group">
            <label>coupon ammount</label>
            <input type="number" class="form-control" name="coupon_ammount">
         </div>
         <div class="form-group">
            <label>minimum order</label>
            <input type="number" class="form-control" name="minimum_order">
         </div>
         <div class="form-group">
            <label>coupon limit</label>
            <input type="number" class="form-control" name="coupon_limit">
         </div>

         <div class="form-group">
           <button class="btn btn-success" type="submit">Add Coupon</button>
           </div>
         </form>
         </div>


        </div>

    </div>
    <div class="col-8">

         <div class="card">
            <div class="card-header text-white bg-primary">
            <h4 class="card-title text-white">Shipping Charge</h4>
            </div>


           <div class="card-body">
               <table class="table table-responsive table-striped table-bordered">
                   <thead>
                       <tr>
                           <th>coupon name</th>
                           <th>coupon validity date</th>
                           <th>coupon type</th>
                           <th>coupon ammount</th>
                           <th>minimum order</th>
                           <th>coupon limit</th>
                       </tr>
                   </thead>
                   <tbody>

                        @foreach ($coupons as $coupon)
                          <tr>
                             <td>{{$coupon->coupon_name}}</td>
                             <td>{{$coupon->coupon_validity_date}}</td>
                             <td>{{$coupon->coupon_type}}</td>
                             <td>{{$coupon->coupon_ammount}}</td>
                             <td>{{$coupon->minimum_order}}</td>
                             <td>{{$coupon->coupon_limit}}</td>
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

