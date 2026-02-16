@if(Auth::check() && Auth::user()->role_id == 1)
<div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('admin.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap w-100">
        <ul class="w-100">
            <li>
                <a class="menu-title"  href="{{ route('admin.dashboard') }}">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    Dashboard <span class="icon-right"></span>
                </a>
                
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-tasks" aria-hidden="true"></i>
                    User Management <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('admin.users.index') }}">Backend Users <span class="icon-right"></span></a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.employee-index') }}">Employees <span class="icon-right"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.dealers-index') }}">Dealers <span class="icon-right"></span>
                        </a>
                    </li>
                    
                </ul>
            </li>
            
        </ul>
        
     
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        {{-- <a href="logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a> --}}
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
        </a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </div>
    </div>
</div>
  @endif
@if(Auth::check() && Auth::user()->role_id == 2)
<div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('sales.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap w-100">
        <ul class="w-100">
            <li>
                <a class="menu-title"  href="{{ route('sales.dashboard') }}">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    Dashboard <span class="icon-right"></span>
                </a>
                
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-tasks" aria-hidden="true"></i>
                    Activity Management <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                </a>
                <ul class="submenu">
                    {{-- <li>
                        <a href="{{ route('sales.activity.activity-type-index') }}">Activity Type <span class="icon-right"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.activity.index') }}">Assign Activities <span class="icon-right"></span>
                        </a>
                    </li> --}}
                    
                    <li>
                        <a href="{{ route('sales.activity.assigned-activities') }}">Assign Management <span class="icon-right"></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.activity.created-activities') }}">Activity Management <span class="icon-right"></span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title">
                    <i class="fa fa-road" aria-hidden="true"></i>
                    Route Management <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="{{ route('sales.route.type.index') }}">Routes <span class="icon-right"></span></a>
                    </li>
                    <li>
                        <a href="{{ route('sales.route.index') }}">Assigned Routes <span class="icon-right"></span></a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="menu-title"  href="{{ route('sales.target.index') }}">
                    <i class="fa fa-bullseye" aria-hidden="true"></i>
                    Target Management <span class="icon-right"></span>
                </a>
                
            </li>
		    <li>
                <a class="menu-title"  href="{{ route('sales.scheme.index') }}">
                    <i class="fa fa-tags" aria-hidden="true"></i>
                    Scheme Management <span class="icon-right"></span>
                </a>
            </li>

            <li>
                <a class="menu-title"  href="{{ route('sales.dayend.index') }}">
                    <i class="fa fa-hourglass-end" aria-hidden="true"></i>
                    Day End Report <span class="icon-right"></span>
                </a>
            </li>
            <li>
                <a class="menu-title"  href="{{ route('sales.attendance.index') }}">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                    Attendance Summary <span class="icon-right"></span>
                </a>
            </li>
            <li>
                <a class="menu-title"  href="{{ route('sales.employee.index') }}">
                    <i class="fa fa-users" aria-hidden="true"></i>
                    Employee Management <span class="icon-right"></span>
                </a>
            </li>
        </ul>
        
     
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        {{-- <a href="logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a> --}}
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
        </a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </div>
    </div>
</div>
  @endif
  @if(Auth::check() && Auth::user()->role_id == 3)
  <div class="side-menu d-flex flex-wrap">
    <div class="logo">
      <a href="{{ route('accounts.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
    </div>          
    <div class="menu-cover d-flex flex-wrap">
      <div class="d-flex align-self-start flex-wrap w-100">
        <ul class="w-100">
            <li>
                <a class="menu-title"  href="{{ route('accounts.dashboard') }}">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    Dashboard <span class="icon-right"></span>
                </a>
            </li>
            <li>
                <a class="menu-title" href="{{ route('accounts.orders.index') }}">
                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                    Order Request <span class="icon-right"></span>
                </a>
             
            </li>
		<li>
                    <a class="menu-title" href="{{ route('accounts.price.index') }}">
                        <i class="fa fa-money" aria-hidden="true"></i>
                        Price Management <span class="icon-right"></span>
                    </a>
                
                </li>
         	<li>
                <a class="menu-title"  href="{{ route('sales.scheme.index') }}">
                    <i class="fa fa-tags" aria-hidden="true"></i>
                    Scheme Management <span class="icon-right"></span>
                </a>
            </li> 
        </ul>
        
     
      </div>
      <div class="logout d-flex align-self-end flex-wrap w-100">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
        </a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
      </div>
    </div>
</div>
  @endif
  @if(Auth::check() && Auth::user()->role_id == 4)
    <div class="side-menu d-flex flex-wrap">
        <div class="logo">
        <a href="{{ route('logistics.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
        </div>          
        <div class="menu-cover d-flex flex-wrap">
        <div class="d-flex align-self-start flex-wrap w-100">
            <ul class="w-100">
                <li>
                    <a class="menu-title"  href="{{ route('logistics.dashboard') }}">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        Dashboard <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title" href="{{ route('logistics.orders.index') }}">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        Sales Order Management <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title" href="{{ route('logistics.drivers.index') }}">
                        <i class="fa fa-user" aria-hidden="true"></i>
                        Driver Management <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title" href="{{ route('logistics.vehicles.index') }}">
                        <i class="fa fa-truck" aria-hidden="true"></i>
                        Vehicle Management <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title" href="{{ route('logistics.trip.index') }}">
                        <i class="fa fa-road" aria-hidden="true"></i>
                        Trip Management <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title" href="{{ route('logistics.bata.index') }}">
                        <i class="fa fa-money" aria-hidden="true"></i>
                        Bata Management <span class="icon-right"></span>
                    </a>
                </li>
            </ul>
        
        </div>
        <div class="logout d-flex align-self-end flex-wrap w-100">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
            </a>
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
        </div>
    </div>
@endif
@if(Auth::check() && Auth::user()->role_id == 5)
    <div class="side-menu d-flex flex-wrap">
        <div class="logo">
        <a href="{{ route('md.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
        </div>          
        <div class="menu-cover d-flex flex-wrap">
        <div class="d-flex align-self-start flex-wrap w-100">
            <ul class="w-100">
                <li>
                    <a class="menu-title"  href="{{ route('md.dashboard') }}">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        Dashboard <span class="icon-right"></span>
                    </a>
                </li>
               
            </ul>
        
        </div>
        <div class="logout d-flex align-self-end flex-wrap w-100">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
            </a>
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
        </div>
    </div>
@endif
@if(Auth::check() && Auth::user()->role_id == 6)
    <div class="side-menu d-flex flex-wrap">
        <div class="logo">
        <a href="{{ route('operations.dashboard') }}"><img src="{{asset('images/logo.svg')}}"></a>
        </div>          
        <div class="menu-cover d-flex flex-wrap">
        <div class="d-flex align-self-start flex-wrap w-100">
            <ul class="w-100">
                <li>
                    <a class="menu-title"  href="{{ route('operations.dashboard') }}">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        Dashboard <span class="icon-right"></span>
                    </a>
                </li>
                <li>
                    <a class="menu-title">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        Order Request <span class="icon-right"><i class="fa fa-solid fa-angle-down"></i></span>
                    </a>
                    <ul class="submenu">
                        <li>
                            <a href="{{ route('operations.orders.new') }}">New Request<span class="icon-right"></span></a>
                        </li>
                        <li>
                            <a href="{{ route('operations.orders.index') }}">All Request<span class="icon-right"></span></a>
                        </li>
                    </ul>
                </li>
                 
            </ul>
        </div>
        <div class="logout d-flex align-self-end flex-wrap w-100">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
            </a>
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
        </div>
    </div>
@endif
