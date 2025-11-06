@extends('layouts.app')

@section('content')


<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Employee Management</h3>
       <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-primary" id="openCreateEmployeeModal">Add Employee</button>
        </div>

    </div>

    <div class="filter-sec target-filter">
        <div class="row">
             <div class="col-md-2">
                <label>Designation</label>
                <select class="form-control" id="filter_employee_type">
                    <option value="">-Select Designation -</option>
                    @foreach($employeeTypes as $type)
                        @if($type->id != 6)
                        <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Employee Name</label>
                <select class="form-control" id="filter_employee">
                    <option value="">-Select Employee-</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>District</label>
                <select class="form-control" id="filter_district">
                    <option value="">-Select District-</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-primary w-100" id="exportBtn">
                    <i class="fa fa-download"></i> Export
                </button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-success w-100" id="openImportSAPModal">
                    <i class="fa fa-import"></i> Import SAP Code
                </button>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="dayendTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Name</th>
                    <th>Employee ID</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th width="80">District</th>
                    <th>Reporting Person</th>
                    <th>SAP Code</th>
                    <th width="80">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="importSAPModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import SAP Codes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="importSAPForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sap_file" class="form-label">Upload Excel File</label>
                        <input type="file" class="form-control" id="sap_file" name="sap_file" accept=".xlsx,.xls" required>
                        <small>Excel format: Employee Code | Employee SAP Code</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="employeeModalTitle">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="employeeForm">
        <input type="hidden" id="employee_id" name="employee_id" value="">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Employee Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="employee_code" class="form-label">Employee ID</label>
              <input type="text" class="form-control" id="employee_code" name="employee_code" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="employee_type_id" class="form-label">Designation</label>
              <select class="form-control" id="employee_type_id" name="employee_type_id" required>
                <option value="">Select Designation</option>
                @foreach($employeeTypes as $type)
                  @if($type->id != 6)
                    <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                  @endif
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" class="form-control" id="phone" name="phone">
            </div>
            <div class="col-md-6 mb-3">
              <label for="district_id" class="form-label">District</label>
              <select class="form-control" id="district_id" name="district_id">
                <option value="">Select District</option>
                @foreach($districts as $district)
                  <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="reporting_manager" class="form-label">Reporting Manager</label>
                <select class="form-control" id="reporting_manager" name="reporting_manager">
                    <option value="">Select Manager</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="employee_sap_code" class="form-label">Employee Sap Code</label>
                <input type="text" class="form-control" id="employee_sap_code" name="employee_sap_code">
            </div>
            <div class="col-md-6 mb-3">
                <label for="products" class="form-label">Products</label>
                <select class="form-control" id="products" name="products[]" multiple="multiple" style="width: 100%;">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                    @endforeach
                </select>
            </div>            
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="employeeModalSubmit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>




@endsection 
@section('scripts')
<style>/* Fix Select2 input height */
.select2-container .select2-selection--single {
    height: 38px !important; /* Match your input height */
    border: 1px solid #ced4da;
    border-radius: 6px;
    display: flex;
    align-items: center;
}

/* Fix text alignment */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important; /* Should be height - 2px */
    padding-left: 10px;
}

/* Fix arrow position */
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    top: 1px;
    right: 6px;
}
</style>
<script>
$(document).ready(function () {

    $('#openImportSAPModal').on('click', function() {
        $('#importSAPForm')[0].reset();
        $('#importSAPModal').modal('show');
    });

    $('#importSAPForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        $.ajax({
            url: "{{ route('sales.employee.importSAP') }}",
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Import Completed',
                        html: `
                            ✅ Updated: ${res.updated_count}<br>
                            ⚠️ Skipped: ${res.skipped_count} (${res.skipped_codes.join(', ')})<br>
                            ❌ Invalid: ${res.invalid_count} (${res.invalid_codes.join(', ')})
                        `
                    });
                    $('#dayendTable').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(err) {
                Swal.fire('Error', 'Something went wrong!', 'error');
            }
        });

       
    });

    $('#openCreateEmployeeModal').on('click', function () {
        // Reset form
        $('#employeeForm')[0].reset();
        $('#employee_id').val('');
        $('#employeeModalTitle').text('Add Employee');
        $('#employeeModalSubmit').text('Save');
        $('#products').val(null).trigger('change');
        // Initialize Reporting Manager select2 inside modal
        $('#reporting_manager').select2({
            placeholder: "Select Manager",
            allowClear: true,
            dropdownParent: $('#employeeModal'), 
            width: '100%',
            ajax: {
                url: "{{ route('sales.employee.getEmployeesAjax') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: data.map(emp => ({ id: emp.id, text: emp.name }))
                    };
                },
                cache: true
            }
        });
         $('#reporting_manager').val(null).trigger('change');
        // Show modal
        $('#employeeModal').modal('show');
    });

    $('#employeeForm').submit(function(e) {
        e.preventDefault();
        let id = $('#employee_id').val();

        let url = id 
            ? "{{ url('sales/employee/update') }}/" + id  
            : "{{ route('sales.employee.create') }}";      

        let type = id ? 'PUT' : 'POST'; 

        $.ajax({
            url: url,
            type: type,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: $(this).serialize(),
            success: function(res) {
                $('#employeeModal').modal('hide');
                $('#dayendTable').DataTable().ajax.reload();

                // SweetAlert success
                Swal.fire({
                    icon: 'success',
                    title: id ? 'Employee Updated' : 'Employee Created',
                    text: res.message || 'Operation successful',
                    confirmButtonText: 'OK'
                });
            },
            error: function(err) {
                if (err.status === 422 && err.responseJSON.errors) {
                    // Extract validation messages
                    let messages = [];
                    $.each(err.responseJSON.errors, function(key, value) {
                        messages.push(value.join(', '));
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: messages.join('<br>') // show multiple errors
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.responseJSON?.message || 'Something went wrong'
                    });
                }
            }
        });
    });

    $(document).on('click', '.deleteEmployeeBtn', function() {
        let url = $(this).data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        $('#dayendTable').DataTable().ajax.reload();
                        Swal.fire('Deleted!', res.message, 'success');
                    },
                    error: function(err) {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.editEmployeeBtn', function() {
        let id = $(this).data('id');

        $.ajax({
            url: "{{ url('sales/employee/edit') }}/" + id,
            type: 'GET',
            success: function(res) {
                console.log(res);
                let emp = res.data ?? res;

                // Products
                let products = emp.products ? JSON.parse(emp.products) : [];
                $('#products').val(products).trigger('change');

                // Basic fields
                $('#employee_id').val(emp.id);
                $('#name').val(emp.name);
                $('#employee_code').val(emp.employee_code);
                $('#employee_type_id').val(emp.employee_type_id).trigger('change');
                $('#email').val(emp.email);
                $('#phone').val(emp.phone);
                $('#district_id').val(emp.district_id).trigger('change');
                $('#employee_sap_code').val(emp.employee_sap_code);

                // Reporting Manager (Select2)
                if (!$('#reporting_manager').hasClass("select2-hidden-accessible")) {
                    $('#reporting_manager').select2({
                        placeholder: "Select Manager",
                        allowClear: true,
                        dropdownParent: $('#employeeModal'),
                        width: '100%',
                        ajax: {
                            url: "{{ route('sales.employee.getEmployeesAjax') }}",
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { q: params.term };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.map(emp => ({ id: emp.id, text: emp.name }))
                                };
                            },
                            cache: true
                        }
                    });
                }

                // Set the selected manager properly
                if (emp.reporting_manager) {
                    let newOption = new Option(emp.reporting_manager_name, emp.reporting_manager, true, true);
                    $('#reporting_manager').append(newOption).trigger('change');
                } else {
                    $('#reporting_manager').val(null).trigger('change');
                }

                // Change modal heading + button
                $('#employeeModalTitle').text('Edit Employee');
                $('#employeeModalSubmit').text('Update');

                // Show modal
                $('#employeeModal').modal('show');
            },
            error: function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to fetch employee data!'
                });
            }
        });
    });


    let currentIndex = 0;
    let images = [];

    function showImage() {
        document.getElementById("modalImage").src = images[currentIndex];
    }

    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("view-images")) {
            images = JSON.parse(e.target.getAttribute("data-images"));
            currentIndex = 0;
            showImage();
            document.getElementById("imageModal").style.display = "block";
        }
        if (e.target.classList.contains("close")) {
            document.getElementById("imageModal").style.display = "none";
        }
        if (e.target.classList.contains("next")) {
            currentIndex = (currentIndex + 1) % images.length;
            showImage();
        }
        if (e.target.classList.contains("prev")) {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            showImage();
        }
    });

    var table = $('#dayendTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('sales.employee.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.designation = $('#filter_employee_type').val();
                d.employee_id = $('#filter_employee').val();
                d.district    = $('#filter_district').val();
                // d.status      = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'employee_code', name: 'employee_code' },
            { data: 'designation', name: 'designation' },
            { data: 'phone', name: 'phone' },
            { data: 'email', name: 'email' },
            { data: 'district', name: 'district' },
            { data: 'reporting_manager_name', name: 'reporting_manager_name' },
            { data: 'employee_sap_code', name: 'employee_sap_code' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('#filter_employee_type, #filter_employee, #filter_district').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_employee').select2({
        placeholder: "All",
        allowClear: true,
        ajax: {
            url: "{{ route('sales.getEmployeesAjax') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    employee_type: $('#filter_employee_type').val()
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(emp => ({ id: emp.id, text: emp.name }))
                };
            },
            cache: true
        }
    });

    $('#products').select2({
        placeholder: "Select Products",
        closeOnSelect: false,
        allowClear: true,
        dropdownParent: $('#employeeModal'),
    });

    $('#exportBtn').on('click', function() {
        let designation = $('#filter_employee_type').val();
        let employee    = $('#filter_employee').val();
        let district    = $('#filter_district').val();

        let url = "{{ route('sales.employee.export') }}" + 
                "?designation=" + designation + 
                "&employee_id=" + employee + 
                "&district=" + district;

        window.location.href = url;
    });

});


</script>
@endsection
