
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gymove - Fitness Bootstrap Admin Dashboard</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('dashboard/images/favicon.png')}}">
    <link href="{{asset('dashboard/css/style.css')}}" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
</head>

<body class="h-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
					<div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
									<div class="text-center mb-3">
										<a href="index.html"><img src="{{asset('dashboard/images/logo-full.png')}}" alt=""></a>
									</div>
                                    <h4 class="text-center mb-4 text-white">Sign up your account</h4>
                                @if ($errors->any())
                                 <div class="alert alert-danger">
                                 @foreach ($errors->all() as $error)
                                  <li>{{$error}}</li>
                                 @endforeach
                                 </div>
                                 @endif
                                    <form action="{{ route('register') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label class="mb-1 text-white"><strong>Username</strong></label>
                                            <input type="text" class="form-control"  name="name">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1 text-white"><strong>Email</strong></label>
                                            <input type="email" class="form-control"  name="email">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1 text-white"><strong>Password</strong></label>
                                            <input type="password" class="form-control"  name="password" >
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1 text-white"><strong> Confirm Password</strong></label>
                                            <input type="password" class="form-control"  name="password_confirmation">
                                        </div>
                                        <div class="text-center mt-4">
                                            <button type="submit" class="btn bg-white text-primary btn-block">Sign me up</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p class="text-white">Already have an account? <a class="text-white" href="{{route('login')}}">Sign in</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!--**********************************
	Scripts
***********************************-->
<!-- Required vendors -->
<script src="{{asset('dashboard/vendor/global/global.min.js')}}"></script>
<script src="{{asset('dashboard/vendor/bootstrap-select/dist/js/bootstrap-select.min.js')}}"></script>
<script src="{{asset('dashboard/js/custom.min.js')}}"></script>
<script src="{{asset('dashboard/js/deznav-init.js')}}"></script>

</body>
</html>
