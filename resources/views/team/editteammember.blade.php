
@extends('layouts.app');

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
           <div class="card  ">

             <div class="card-header text-white bg-primary">
               <h4 class="card-title">Edit Team Member</h4>

             </div>
             <div class="card-body">
             @if (session('update_message'))
                     <div class="alert alert-info">
                      {{session('update_message')}}
                 </div>
             @endif
             <form action="{{url('team/member/update')}}/{{$team_member_info->id}}" method="POST">
                @csrf
                  <div class="form-group">
                <label>Team Member Name</label>
                <input type="text" class="form-control" name="team_member_name"  value="{{$team_member_info->member_name}}">
              </div>
                  <div class="form-group">
                 <button type="submit" class="btn btn-success border-rounded">Edit Team Member</button>
              </div>
             </form>
             </div>
           </div>


        </div>
    </div>
</div>
@endsection
