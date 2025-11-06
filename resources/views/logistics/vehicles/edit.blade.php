@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.vehicles.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Edit Vehicle</h4>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops! Something went wrong.</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-lg p-4" id="backend-forms">
        <form action="{{ route('logistics.vehicles.update', $vehicle->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <h5>Vehicle Details</h5>
                <hr>
            </div>

            <div class="row g-3">
                {{-- Vehicle Category --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Category</label>
                    <select name="vehicle_category_id" id="vehicle_category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        @foreach($vehicleCategories as $category)
                            <option value="{{ $category->id }}" 
                                {{ old('vehicle_category_id', $vehicle->vehicle_category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->vehicle_category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Vehicle Type --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Type</label>
                    <select name="vehicle_type_id" id="vehicle_type_id" class="form-select" required>
                        <option value="">Select Vehicle Type</option>
                        @foreach($vehicleTypes as $type)
                            <option value="{{ $type->id }}" 
                                {{ old('vehicle_type_id', $vehicle->vehicle_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->vehicle_type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Current Kilometer --}}
                <div class="col-md-6">
                    <label class="form-label">Current Kilometer</label>
                    <input type="number" name="current_km" class="form-control" step="0.01" 
                           value="{{ old('current_km', $vehicle->starting_km) }}" required>
                </div>
                {{-- {{$vehicle}} --}}
                {{-- Load Capacity --}}
                <div class="col-md-6">
                    <label class="form-label">Load Capacity (Ton)</label>
                    <input type="number" name="load_capacity_ton" class="form-control" step="0.01"
                           value="{{ old('load_capacity_ton', $vehicle->load_capacity) }}">
                </div>

                {{-- Service & Inspection --}}
                <div id="service-inspection-fields" style="{{ $vehicle->vehicle_category_id == 2 ? 'display:block;' : 'display:none;' }}">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Service KM Duration</label>
                            <input type="number" name="service_km_duration" class="form-control" step="0.01"
                                   value="{{ old('service_km_duration', $vehicle->service_km) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service Duration (Days)</label>
                            <input type="number" name="service_duration_days" class="form-control"
                                   value="{{ old('service_duration_days', $vehicle->service_days) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inspection KM Duration</label>
                            <input type="number" name="inspection_km_duration" class="form-control" step="0.01"
                                   value="{{ old('inspection_km_duration', $vehicle->inspection_km) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inspection Duration (Days)</label>
                            <input type="number" name="inspection_duration_days" class="form-control"
                                   value="{{ old('inspection_duration_days', $vehicle->inspection_days) }}">
                        </div>
                    </div>
                </div>

                {{-- Transporter Fields --}}
                <div id="transporter-fields" style="{{ $vehicle->vehicle_category_id == 3 ? 'display:block;' : 'display:none;' }}">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Transporter Name</label>
                            <input type="text" name="transporter_name" class="form-control"
                                   value="{{ old('transporter_name', $vehicle->transporter_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transporter Phone</label>
                            <input type="text" name="transporter_phone" class="form-control"
                                   value="{{ old('transporter_phone', $vehicle->transporter_phone) }}">
                        </div>
                    </div>
                </div>
                 <p>Provides the vehicle's registration details as per official records</p>
                {{-- Registration Details --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Registration No</label>
                    <input type="text" name="vehicle_no" class="form-control" required
                           value="{{ old('vehicle_no', $vehicle->vehicle_no) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Registration Valid Upto</label>
                    <input type="date" name="rc_exp_date" class="form-control" required
                           value="{{ old('rc_exp_date', $vehicle->rc_exp_date ? \Carbon\Carbon::parse($vehicle->rc_exp_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">RC Attachment</label>
                    <input type="file" name="rc_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->rc_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->rc_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <p>Please provide other necessary details</p>
                {{-- Chassis & Engine --}}
                <div class="col-md-6">
                    <label class="form-label">Chassis No</label>
                    <input type="text" name="chasis_no" class="form-control"
                           value="{{ old('chasis_no', $vehicle->chasis_no) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Engine No</label>
                    <input type="text" name="engine_no" class="form-control"
                           value="{{ old('engine_no', $vehicle->engine_no) }}">
                </div>

                {{-- Owner --}}
                <div class="col-md-6">
                    <label class="form-label">Name of Owner</label>
                    <input type="text" name="owner_name" class="form-control"
                           value="{{ old('owner_name', $vehicle->owner_name) }}">
                </div>
                    <p>Enter the tax details related to the vehicles, as per official records</p>
                {{-- Tax --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Tax Amount</label>
                    <input type="number" name="vehicle_tax_amount" class="form-control" step="0.01"
                           value="{{ old('vehicle_tax_amount', $vehicle->vehicle_tax_amount) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Valid Upto</label>
                    <input type="date" name="tax_valid_upto" class="form-control"
                           value="{{ old('tax_valid_upto', $vehicle->tax_valid_upto ? \Carbon\Carbon::parse($vehicle->tax_valid_upto)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Receipt Attachment</label>
                    <input type="file" name="tax_receipt_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->tax_receipt_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->tax_receipt_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <p>Enter the vehicle information, including validity period and receipt details </p>
                {{-- Insurance --}}
                <div class="col-md-6">
                    <label class="form-label">Premium Amount</label>
                    <input type="number" name="premium_amount" class="form-control" step="0.01"
                           value="{{ old('premium_amount', $vehicle->premium_amount) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Type</label>
                    <input type="text" name="insurance_type" class="form-control"
                           value="{{ old('insurance_type', $vehicle->insurance_type) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Valid Upto</label>
                    <input type="date" name="insurance_valid_upto" class="form-control"
                           value="{{ old('insurance_valid_upto', $vehicle->insurance_exp_date ? \Carbon\Carbon::parse($vehicle->insurance_exp_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Receipt Attachment</label>
                    <input type="file" name="insurance_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->insurance_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->insurance_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <p>Please enter the vehicles pollution and fitness certificate details, including validity details</p>

                {{-- Pollution & Fitness --}}
                <div class="col-md-6">
                    <label class="form-label">Pollution Valid Upto</label>
                    <input type="date" name="pollution_valid_upto" class="form-control"
                           value="{{ old('pollution_valid_upto', $vehicle->puc_exp_date ? \Carbon\Carbon::parse($vehicle->puc_exp_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pollution Certificate Attachment</label>
                    <input type="file" name="pollution_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->puc_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->puc_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fitness Valid Upto</label>
                    <input type="date" name="fitness_valid_upto" class="form-control"
                           value="{{ old('fitness_valid_upto', $vehicle->fitness_exp_date ? \Carbon\Carbon::parse($vehicle->fitness_exp_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fitness Certificate Attachment</label>
                    <input type="file" name="fitness_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->fitness_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->fitness_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <p>Enter valid permit details as per the transport department records</p>
                {{-- Permits --}}
                <div class="col-md-6">
                    <label class="form-label">State Permit Valid Upto</label>
                    <input type="date" name="state_permit_valid_upto" class="form-control"
                           value="{{ old('state_permit_valid_upto', $vehicle->permit_exp_date ? \Carbon\Carbon::parse($vehicle->permit_exp_date)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">State Permit Certificate Attachment</label>
                    <input type="file" name="state_permit_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->permit_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->permit_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">National Permit Valid Upto</label>
                    <input type="date" name="national_permit_valid_upto" class="form-control"
                           value="{{ old('national_permit_valid_upto', $vehicle->national_permit_valid_upto ? \Carbon\Carbon::parse($vehicle->national_permit_valid_upto)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">National Permit Certificate Attachment</label>
                    <input type="file" name="national_permit_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                    @if($vehicle->national_permit_file)
                        <small class="d-block mt-1">
                            Current File: 
                            <a href="{{ asset('storage/'.$vehicle->national_permit_file) }}" target="_blank">View / Download</a>
                        </small>
                    @endif
                </div>

                {{-- Authorization Date --}}
                <div class="col-md-6">
                    <label class="form-label">Authorization Date</label>
                    <input type="date" name="authorization_date" class="form-control"
                           value="{{ old('authorization_date', $vehicle->authorization_date ? \Carbon\Carbon::parse($vehicle->authorization_date)->format('Y-m-d') : '') }}">
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('vehicle_category_id');
    const typeSelect = document.getElementById('vehicle_type_id');
    const serviceFields = document.getElementById('service-inspection-fields');
    const transporterFields = document.getElementById('transporter-fields');

    function toggleFields() {
        const categoryId = categorySelect.value;
        if (categoryId == 2) {
            serviceFields.style.display = 'block';
            transporterFields.style.display = 'none';
        } else if (categoryId == 3) {
            serviceFields.style.display = 'none';
            transporterFields.style.display = 'block';
        } else {
            serviceFields.style.display = 'none';
            transporterFields.style.display = 'none';
        }
    }

    toggleFields(); // initial toggle
    categorySelect.addEventListener('change', toggleFields);

    // Load vehicle types dynamically
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        typeSelect.innerHTML = '<option value="">Loading...</option>';

        if (categoryId) {
            fetch(`{{ url('logistics/vehicles/vehicle-types') }}/${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    typeSelect.innerHTML = '<option value="">Select Vehicle Type</option>';
                    data.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id;
                        option.textContent = type.vehicle_type_name;
                        if (type.id == "{{ old('vehicle_type_id', $vehicle->vehicle_type_id) }}") {
                            option.selected = true;
                        }
                        typeSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching vehicle types:', error);
                    typeSelect.innerHTML = '<option value="">Error loading types</option>';
                });
        } else {
            typeSelect.innerHTML = '<option value="">Select Vehicle Type</option>';
        }
    });
});
</script>
@endpush
