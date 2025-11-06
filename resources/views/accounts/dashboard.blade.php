@extends('layouts.app')

@section('title', 'Accounts Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3">
      <div class="dash-item-box">
        <div class="justify-content-between p-3 align-items-center">
          <div>
            <h2>{{ $pendingOrders }}</h2>
          </div>
          <div>
            <h4>Pending Orders</h4>
          </div>
        </div>                  
      </div>
    </div>

    <div class="col-md-3">
      <div class="dash-item-box">
        <div class="justify-content-between p-3 align-items-center">
          <div>
            <h2>{{ $approvedOrders }}</h2>
          </div>
          <div>
            <h4>Approved Orders</h4>
          </div>
        </div>                  
      </div>
    </div>

    <div class="col-md-3">
      <div class="dash-item-box">
        <div class="justify-content-between p-3 align-items-center">
          <div>
            <h2>{{ $rejectedOrders }}</h2>
          </div>
          <div>
            <h4>Rejected Orders</h4>
          </div>
        </div>                  
      </div>
    </div>
</div>
@endsection

