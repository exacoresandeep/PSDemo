<div class="menu-header">
    <div class="row justify-content-between">
        <div class="col-md-6">
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" class="form-control" placeholder="Search...">
            </div>
        </div>
        <div class="col-md-3 align-content-center">
            <div class="settings-box">
                {{-- <a href="" class="notify"><i class="fa fa-bell" aria-hidden="true"></i></a> --}}
                <div class="profi-blk">
                    <img src="{{ asset('images/profile-pic.png') }}" class="img-fluid">
                    <p>{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>