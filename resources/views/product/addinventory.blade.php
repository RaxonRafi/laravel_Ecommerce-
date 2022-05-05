@extends('layouts.dashboard_master')
@section('dashboard_bar')
   Inventory
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
  <div class="card">
  <div class="card-header text-white bg-primary">
    <h4 class="card-title text-white">Add Inventory For - {{$product->product_name}}</h4>
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
<form action="{{route('add.inventory.post',$product_id)}}" method="post" enctype="multipart/form-data">
      @csrf
<div class="row">
    <div class="col-12">
     <div class="form-group">
      <label>Color</label>
      @foreach ($colors as $color)
        <div class="form-check">
            <input class="form-check-input" type="radio" name="color_id" id="flexRadioDefault{{$color->id}}" value="{{$color->id}}">
            <label class="form-check-label" for="flexRadioDefault{{$color->id}}">
            {{$color->color_name}} <Span class="badge" style="background-color:{{$color->color_code}}"> &nbsp;</Span>
            </label>
       </div>
      @endforeach

     <div class="col-12">
         <div class="form-group">
            <label>Size</label>
              <select name="size_id" class="form-control">
                  <option value="">--Choose Size--</option>
                     @foreach ($sizes as $size)
                     <option value="{{$size->id}}">{{$size->size_name}}</option>
                    @endforeach
              </select>


         </div>
     </div>
     <div class="col-12">
         <div class="form-group">
            <label>Quantity</label>
          <input name="quantity" class="form-control" type="number">
         </div>
     </div>
     </div>
    </div>
</div>
    <div class="form-group">
 <button class="btn btn-secondary" type="submit">Add to Inventory</button>
    </div>

</form>
  </div>
</div>
        </div>
    </div>

<div class="container">
<div class="row">
    <div class="col-12">
        <div class="card">
          <div class="card-header bg-primary">
            <h4 class="card-title  text-white ">Product Status</h4>
            <span class="badge bg-dark">Total Variations: {{$inventories->count()}}</span>

          </div>
          <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>sl No</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>Quantity</th>
                    <th>Market Value(৳)</th>
                </tr>
            </thead>
            <tbody>
                @php
                 $total_market_value = 0;
                @endphp
                 @foreach ($inventories as $inventory)
                <tr>

                    <td>{{$loop->index+1}}</td>
                    <td>{{ $inventory->relationtocolor->color_name}}
                    <span class="badge" style="background-color:{{ $inventory->relationtocolor->color_code}}"> </span></td>
                    <td>{{$inventory->relationtosize->size_name}}</td>
                    <td>{{$inventory->quantity}}</td>
                    @php
                        $total_market_value +=($product->discounted_price * $inventory->quantity)
                    @endphp
                    <td>{{$product->discounted_price * $inventory->quantity}}</td>

                </tr>
                  @endforeach
                  <tr>
                      <td colspan="3" class="text-center">
                           <b>Total</b>
                      </td>
                      <td >
                           <b>{{$inventories->sum('quantity')}}</b>
                      </td>
                      <td >
                           <b>{{$total_market_value}}</b>
                      </td>
                  </tr>

            </tbody>
        </table>

          </div>
        </div>
    </div>
</div>
</div>



@endsection


