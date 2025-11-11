@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.vehicles.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Vehicle Details</h4>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">

                <!-- Vehicle No -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vehicle No</label>
                    <div><span class="text-muted">{{ $vehicle->vehicle_no ?? 'N/A' }}</span></div>
                </div>

                <!-- Vehicle Code -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vehicle Code</label>
                    <div><span class="text-muted">{{ $vehicle->vehicle_code ?? 'N/A' }}</span></div>
                </div>

                <!-- Vehicle Category -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vehicle Category</label>
                    <div><span class="text-muted">{{ $vehicle->vehicle_category_id == 2 ? 'Internal Vehicle' : 'External Vehicle' }}</span></div>
                </div>

                <!-- Vehicle Type -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vehicle Type</label>
                    <div><span class="text-muted">{{ $vehicle->vehicleType->vehicle_type_name ?? 'N/A' }}</span></div>
                </div>

                <!-- Transport Provider Name -->
                @if(!empty($vehicle->transport_provider_name))
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Transport Provider Name</label>
                        <div><span class="text-muted">{{ $vehicle->transport_provider_name }}</span></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Transporter Phone</label>
                        <div><span class="text-muted">{{ $vehicle->transporter_phone ?? 'N/A' }}</span></div>
                    </div>
                @endif

                <!-- Load Capacity -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Load Capacity (Ton)</label>
                    <div><span class="text-muted">{{ $vehicle->load_capacity ?? 'N/A' }}</span></div>
                </div>

                <!-- Inspection Days -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Inspection Days</label>
                    <div><span class="text-muted">{{ $vehicle->inspection_days ?? 'N/A' }}</span></div>
                </div>

                <!-- RC Expiry Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">RC Expiry Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->rc_exp_date ? \Carbon\Carbon::parse($vehicle->rc_exp_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- RC File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">RC File</label>
                    <div>
                        @if($vehicle->rc_file)
                            <a href="{{ asset('storage/' . $vehicle->rc_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Insurance Type -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Insurance Type</label>
                    <div><span class="text-muted">{{ ucfirst($vehicle->insurance_type ?? 'N/A') }}</span></div>
                </div>

                <!-- Insurance Expiry Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Insurance Expiry Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->insurance_exp_date ? \Carbon\Carbon::parse($vehicle->insurance_exp_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Insurance File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Insurance File</label>
                    <div>
                        @if($vehicle->insurance_file)
                            <a href="{{ asset('storage/' . $vehicle->insurance_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- PUC Expiry Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">PUC Expiry Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->puc_exp_date ? \Carbon\Carbon::parse($vehicle->puc_exp_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- PUC File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">PUC File</label>
                    <div>
                        @if($vehicle->puc_file)
                            <a href="{{ asset('storage/' . $vehicle->puc_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Fitness Expiry Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fitness Expiry Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->fitness_exp_date ? \Carbon\Carbon::parse($vehicle->fitness_exp_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Fitness File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fitness File</label>
                    <div>
                        @if($vehicle->fitness_file)
                            <a href="{{ asset('storage/' . $vehicle->fitness_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Permit Expiry Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Permit Expiry Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->permit_exp_date ? \Carbon\Carbon::parse($vehicle->permit_exp_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Permit File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Permit File</label>
                    <div>
                        @if($vehicle->permit_file)
                            <a href="{{ asset('storage/' . $vehicle->permit_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Starting KM -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Starting KM</label>
                    <div><span class="text-muted">{{ $vehicle->starting_km ?? 'N/A' }}</span></div>
                </div>

                <!-- Service Days -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Service Days</label>
                    <div><span class="text-muted">{{ $vehicle->service_days ?? 'N/A' }}</span></div>
                </div>

                <!-- Service KM -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Service KM</label>
                    <div><span class="text-muted">{{ $vehicle->service_km ?? 'N/A' }}</span></div>
                </div>

                <!-- Chasis No -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Chasis No</label>
                    <div><span class="text-muted">{{ $vehicle->chasis_no ?? 'N/A' }}</span></div>
                </div>

                <!-- Engine No -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Engine No</label>
                    <div><span class="text-muted">{{ $vehicle->engine_no ?? 'N/A' }}</span></div>
                </div>

                <!-- Owner Name -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Owner Name</label>
                    <div><span class="text-muted">{{ $vehicle->owner_name ?? 'N/A' }}</span></div>
                </div>

                <!-- Vehicle Tax Amount -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vehicle Tax Amount</label>
                    <div><span class="text-muted">{{ $vehicle->vehicle_tax_amount ?? 'N/A' }}</span></div>
                </div>

                <!-- Tax Valid Upto -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tax Valid Upto</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->tax_valid_upto ? \Carbon\Carbon::parse($vehicle->tax_valid_upto)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Tax Receipt File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tax Receipt File</label>
                    <div>
                        @if($vehicle->tax_receipt_file)
                            <a href="{{ asset('storage/' . $vehicle->tax_receipt_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Premium Amount -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Premium Amount</label>
                    <div><span class="text-muted">{{ $vehicle->premium_amount ?? 'N/A' }}</span></div>
                </div>

                <!-- National Permit Valid Upto -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">National Permit Valid Upto</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->national_permit_valid_upto ? \Carbon\Carbon::parse($vehicle->national_permit_valid_upto)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- National Permit File -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">National Permit File</label>
                    <div>
                        @if($vehicle->national_permit_file)
                            <a href="{{ asset('storage/' . $vehicle->national_permit_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>

                <!-- Authorization Date -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Authorization Date</label>
                    <div>
                        <span class="text-muted">
                            {{ $vehicle->authorization_date ? \Carbon\Carbon::parse($vehicle->authorization_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
