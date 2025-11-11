@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.drivers.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Driver Details</h4>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>Name:</strong> <span class="text-muted">{{ $driver->name }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Phone:</strong> <span class="text-muted">{{ $driver->phone }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Alternative Phone:</strong> <span class="text-muted">{{ $driver->alternative_phone ?? 'N/A' }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Address:</strong> <span class="text-muted">{{ $driver->address }}</span>
                </div>
                
                <div class="col-md-6">
                    <strong>District:</strong> <span class="text-muted">{{ $driver->district->name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Pincode:</strong> <span class="text-muted">{{ $driver->pincode }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Aadhar Card No:</strong> <span class="text-muted">{{ $driver->adharcard_no }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Aadhar Attachment:</strong> 
                    @if($driver->adhar_attachment)
                        <a href="{{ asset('storage/' . $driver->adhar_attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <strong>License No:</strong> <span class="text-muted">{{ $driver->liscence_no }}</span>
                </div>
                <div class="col-md-6">
                    <strong>License Attachment:</strong> 
                    @if($driver->liscence_attachment)
                        <a href="{{ asset('storage/' . $driver->liscence_attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                        <span class="text-muted">N/A</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <strong>License Expiry Date:</strong> <span class="text-muted">{{ $driver->liscence_exp_date }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Blood Group:</strong> <span class="text-muted">{{ $driver->blood_group }}</span>
                </div>
                <div class="col-md-6">
                    <strong>Status:</strong> 
                    <span class="badge {{ $driver->status == 1 ? 'bg-success' : 'bg-danger' }}">
                        {{ $driver->status == 1 ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
