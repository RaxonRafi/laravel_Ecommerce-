@extends('layouts.dashboard_master')


@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="profile card card-body px-3 pt-3 pb-0">
     <div class="profile-head">
       <div class="photo-content">
        <div class="cover-photo"></div>
       </div>
<div class="profile-info">
  <div class="profile-photo">

    <img src="{{asset('uploads/profile_photo')}}/{{auth()->user()->profile_photo}}" class="img-fluid rounded-circle" alt="">
  </div>
  <div class="profile-details">
       <div class="profile-name px-3 pt-2">
       <h4 class="text-primary mb-0">{{auth()->user()->name}}</h4>
       <p>UX / UI Designer</p>
  </div>
<div class="profile-email px-2 pt-2">
   <h4 class="text-muted mb-0">{{auth()->user()->email}}</h4>
   <p>Email</p>
</div>
<div class="profile-email px-2 pt-2">
     <h4 class="text-muted mb-0">
     @if (auth()->user()->phone_number)
     {{auth()->user()->phone_number}}
    @else
     N/A
    @endif
     </h4>
      <p>Phone Number</p>
</div>
<div class="dropdown ml-auto">
     <a href="#" class="btn btn-primary light sharp" data-toggle="dropdown" aria-expanded="true"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"></rect><circle fill="#000000" cx="5" cy="12" r="2"></circle><circle fill="#000000" cx="12" cy="12" r="2"></circle><circle fill="#000000" cx="19" cy="12" r="2"></circle></g></svg></a>
     <ul class="dropdown-menu dropdown-menu-right">
        <li class="dropdown-item"><i class="fa fa-user-circle text-primary mr-2"></i> View profile</li>
       <li class="dropdown-item"><i class="fa fa-users text-primary mr-2"></i> Add to close friends</li>
       <li class="dropdown-item"><i class="fa fa-plus text-primary mr-2"></i> Add to group</li>
      <li class="dropdown-item"><i class="fa fa-ban text-primary mr-2"></i> Block</li>
   </ul>
      </div>
    </div>
  </div>
</div>
</div>
</div>
</div>
<div class="row">
      <div class="col-xl-12">
        <div class="card">
          <div class="card-body">
            <div class="pt-3">
              <div class="settings-form">
               <h4 class="text-primary">Account Setting</h4>
                   @if (session('status'))

                    <div class="alert alert-success">
                       {{session('status')}}
                    </div>
                  @endif
<form action="{{route('change.name')}}" method="post" enctype="multipart/form-data">
  @csrf
  <div class="row">
      <div class="col-4">
           <div class="form-group ">
              <label>Name</label>
              <input type="text" value="{{auth()->user()->name}}" name="name" class="form-control">
                 @error('name')
                <span class="text-danger">{{$message}}</span>
                @enderror
       </div>
      </div>
      <div class="col-4">
           <div class="form-group ">
              <label>Phone Number</label>
              <input type="text" value="{{auth()->user()->phone_number}}" name="phone_number" class="form-control">
              @error('phone_number')
           <span class="text-danger">{{$message}}</span>
              @enderror
       </div>
      </div>
      <div class="col-4">
           <div class="form-group ">
              <label>Address</label>
              <input type="text" value="{{auth()->user()->address}}" name="address" class="form-control">
       </div>
      </div>
    <div class="row">
          <div class="col-12">
           <div class="form-group ">
              <label>Profile Photo</label>
              <input type="file" name="profile_photo" class="form-control">
       </div>
      </div>
    </div>
  </div>
    <button class="btn btn-square btn-secondary" type="submit">Change Details</button>
    </form>




</div>
</div>
</div>
</div>
</div>
      <div class="col-xl-12">
        <div class="card">
          <div class="card-body">
            <div class="pt-3">
              <div class="settings-form">
               <h4 class="text-primary">Change Password</h4>
                   @if (session('password_success'))
                    <div class="alert alert-success">
                       {{session('password_success')}}
                    </div>
                  @endif
 <form action="{{route('change.password')}}" method="post">
    @csrf
   <div class="form-row">
 <div class="form-group col-md-4">
    <label>Current Password</label>
    <input type="password" placeholder="Password" class="form-control" name="current_password">
        @error('current_password')
           <span class="text-danger">{{$message}}</span>
        @enderror
       @error('current_password_err')
           <span class="text-danger">{{$message}}</span>
        @enderror
   </div>
  <div class="form-group col-md-4">
    <label>New Password</label>
    <input type="password" placeholder="Password" class="form-control" name="password">
     @error('password')
           <span class="text-danger">{{$message}}</span>
        @enderror
   </div>
  <div class="form-group col-md-4">
    <label>Confirm Password</label>
    <input type="password" placeholder="Password" class="form-control" name="password_confirmation">
     @error('password_confirmation')
           <span class="text-danger">{{$message}}</span>
    @enderror
   </div>

</div>
  <button class="btn btn-square btn-secondary" type="submit">Change Password</button>

</form>




</div>
</div>
</div>
</div>
</div>


@endsection
