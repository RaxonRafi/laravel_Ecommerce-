@extends('layouts.app');

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
           <div class="card  ">

             <div class="card-header text-white bg-primary">
               <h4 class="card-title">Add Team Member</h4>

             </div>
             <div class="card-body">
             @if (session('success_message'))
                     <div class="alert alert-info">
                      {{session('success_message')}}
                 </div>
             @endif
             <form action="{{ url('team/member/insert') }}" method="POST">
                @csrf
                  <div class="form-group">
                <label>Team Member Name</label>
                <input type="text" class="form-control" name="team_member_name"  placeholder="Enter Team Member Name">
              </div>
                  <div class="form-group">
                 <button type="submit" class="btn btn-success border-rounded">Add Team Member</button>
              </div>
             </form>
             </div>
           </div>


        </div>
    </div>
</div>
@endsection
