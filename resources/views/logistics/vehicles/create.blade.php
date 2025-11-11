@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.vehicles.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Add Vehicle</h4>
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
        <form action="{{ route('logistics.vehicles.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
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
                            <option value="{{ $category->id }}">{{ $category->vehicle_category_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Vehicle Type --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Type</label>
                    <select name="vehicle_type_id" id="vehicle_type_id" class="form-select" required>
                        <option value="">Select Vehicle Type</option>
                    </select>
                </div>

                {{-- Current Kilometer --}}
                <div class="col-md-6">
                    <label class="form-label">Current Kilometer</label>
                    <input type="number" name="current_km" class="form-control" step="0.01" required>
                </div>

                {{-- Load Capacity in Ton --}}
                <div class="col-md-6">
                    <label class="form-label">Load Capacity (Ton)</label>
                    <input type="number" name="load_capacity_ton" class="form-control" step="0.01">
                </div>

                {{-- Service KM Duration --}}
                <div id="service-inspection-fields">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Service KM Duration</label>
                            <input type="number" name="service_km_duration" class="form-control" step="0.01">
                        </div>

                        {{-- Service Duration (Days) --}}
                        <div class="col-md-6">
                            <label class="form-label">Service Duration (Days)</label>
                            <input type="number" name="service_duration_days" class="form-control">
                        </div>

                        {{-- Inspection KM Duration --}}
                        <div class="col-md-6">
                            <label class="form-label">Inspection KM Duration</label>
                            <input type="number" name="inspection_km_duration" class="form-control" step="0.01">
                        </div>

                        {{-- Inspection Duration (Days) --}}
                        <div class="col-md-6">
                            <label class="form-label">Inspection Duration (Days)</label>
                            <input type="number" name="inspection_duration_days" class="form-control">
                        </div>
                    </div>
                </div>
                <div id="transporter-fields" style="display:none;">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Transporter Name</label>
                            <input type="text" name="transporter_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transporter Phone</label>
                            <input type="text" name="transporter_phone" class="form-control">
                        </div>
                    </div>
                </div>

                <p>Provides the vehicle's registration details as per official records</p>

                {{-- Vehicle Reg No --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Registration No</label>
                    <input type="text" name="vehicle_no" class="form-control" required>
                </div>

                {{-- RC Expiry Date --}}
                <div class="col-md-6">
                    <label class="form-label">Registration Valid Upto</label>
                    <input type="date" name="rc_exp_date" class="form-control" required>
                </div>

                {{-- RC File --}}
                <div class="col-md-6">
                    <label class="form-label">RC Attachment</label>
                    <input type="file" name="rc_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>
                <p>Please provide other necessary details</p>
                {{-- Chasis & Engine --}}
                <div class="col-md-6">
                    <label class="form-label">Chassis No</label>
                    <input type="text" name="chasis_no" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Engine No</label>
                    <input type="text" name="engine_no" class="form-control">
                </div>

                {{-- Owner Name --}}
                <div class="col-md-6">
                    <label class="form-label">Name of Owner</label>
                    <input type="text" name="owner_name" class="form-control">
                </div>
                <p>Enter the tax details related to the vehicles, as per official records</p>
                {{-- Tax --}}
                <div class="col-md-6">
                    <label class="form-label">Vehicle Tax Amount</label>
                    <input type="number" name="vehicle_tax_amount" class="form-control" step="0.01">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Valid Upto</label>
                    <input type="date" name="tax_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tax Receipt Attachment</label>
                    <input type="file" name="tax_receipt_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>
                <p>Enter the vehicle information, including validity period and receipt details </p>
                {{-- Insurance --}}
                <div class="col-md-6">
                    <label class="form-label">Premium Amount</label>
                    <input type="number" name="premium_amount" class="form-control" step="0.01">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Type</label>
                    <input type="text" name="insurance_type" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Valid Upto</label>
                    <input type="date" name="insurance_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Insurance Receipt Attachment</label>
                    <input type="file" name="insurance_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>
                <p>Please enter the vehicles pollution and fitness certificate details, including validity details</p>
                {{-- Pollution --}}
                <div class="col-md-6">
                    <label class="form-label">Pollution Valid Upto</label>
                    <input type="date" name="pollution_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pollution Certificate Attachment</label>
                    <input type="file" name="pollution_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>
                
                {{-- Fitness --}}
                <div class="col-md-6">
                    <label class="form-label">Fitness Valid Upto</label>
                    <input type="date" name="fitness_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fitness Certificate Attachment</label>
                    <input type="file" name="fitness_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>
                <p>Enter valid permit details as per the transport department records</p>
                {{-- State Permit --}}
                <div class="col-md-6">
                    <label class="form-label">State Permit Valid Upto</label>
                    <input type="date" name="state_permit_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">State Permit Certificate Attachment</label>
                    <input type="file" name="state_permit_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>

                {{-- National Permit --}}
                <div class="col-md-6">
                    <label class="form-label">National Permit Valid Upto</label>
                    <input type="date" name="national_permit_valid_upto" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">National Permit Certificate Attachment</label>
                    <input type="file" name="national_permit_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control">
                </div>

                {{-- Authorization Date --}}
                <div class="col-md-6">
                    <label class="form-label">Authorization Date</label>
                    <input type="date" name="authorization_date" class="form-control">
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4">Submit</button>
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

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;


        if (categoryId == 2) {
            serviceFields.style.display = 'block';
            transporterFields.style.display = 'none';
        } 
        // Show transporter fields only for category 3
        else if (categoryId == 3) {
            serviceFields.style.display = 'none';
            transporterFields.style.display = 'block';
        } 
        else {
            serviceFields.style.display = 'none';
            transporterFields.style.display = 'none';
        }

        // Load vehicle types dynamically (existing code)
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
