<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('font-awesome/css/font-awesome.min.css') }}">
    <title>@yield('title', 'Admin Panel')</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Custom styles -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <!-- Add this in your <head> if not already included -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('scripts')
</head>

<body>

    <main>
        <div class="main-container">
            <div class="d-flex">
                {{-- @include('includes.sidebar-menu') --}}
                <div class="logo">
                <a href="{{ route('admin.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
                </div>  
                <div class="content flex-fill pl-5">
                    @include('includes.header')
                </div>
            </div>
            <div class="d-flex main-content">
                <div class="content flex-fill">
                    <div class="content-area md">
                        <div class="dashboard-area md">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
      

    @include('includes.footer')

