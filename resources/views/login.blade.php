<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">
    <link rel="stylesheet" href="{{ url ('font-awesome/css/font-awesome.min.css')}}">

    <title>Sales Backend :: Prabhu Steels</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ url ('css/bootstrap.min.css')}}" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="{{ url ('css/style.css')}}" rel="stylesheet">
    <style>
        
    </style>
  </head>

  <body>
    <main>
        <div class="main-container">
            <div class="d-flex h-100 align-items-center">
                <div class="login-cover">
                    <img src="{{ asset('images/logo.svg') }}" class="img-fluid">
                    <div class="login-box">
                        <h4>LOGIN</h4>
                        <p>Secure Access to Manage and Monitor Your Operations</p>
    
                        <!-- Display Errors -->
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
    
                        <form method="POST" action="{{ route('doLogin') }}">
                            @csrf

                            <label>User Name</label>
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
    
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" placeholder="*********" required>
    
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>
        window.jQuery || document.write('<script src="{{ asset('js/vendor/jquery-slim.min.js') }}"><\/script>')
    </script>
    
    <script src="{{ asset ('js/vendor/popper.min.js')}}"></script>
    <script src="{{ asset ('js/bootstrap.min.js')}}"></script>
  </body>
</html>
