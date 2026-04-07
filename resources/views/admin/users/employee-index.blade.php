@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align d-flex justify-content-between">
        <h3>Employee Management</h3>
        <button class="btn btn-primary" id="importButton">Import</button>
        <input type="file" id="fileInput" style="display: none;" accept=".csv, .xlsx"/>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped table-responsive w-100" id="employeesTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>District</th>
                    <th>Area</th>
                    <th>Designation</th>
                    <th>Reporting Manager</th>
                    <th>Address</th>
                    <th>Emergency Contact</th>
                    <th>Reset Password</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ------------------- Reset User Password -------------------
    function resetEmployeePassword(Id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to reset the password of this employee",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Reset Password!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ url('admin/users/employees/resetEmployeePassword') }}/" + Id,
                    type: "GET",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    beforeSend: function() { Swal.showLoading(); },
                    success: function(response) {
                        Swal.close();
                        window.table.ajax.reload();

                        Swal.fire({
                            icon: 'success',
                            title: 'Reset Password!',
                            text: response.message || 'User password resetted successfully.',
                            showConfirmButton: false,
                            timer: 1800
                        });
                    },
                    
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Something went wrong while resettig the user password.',
                        });
                    }
                });
            }
        });
    }
$(document).ready(function() {
   window.table = $('#employeesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: "{{ route('admin.users.employee-list') }}",
        columns: [
            { 
                data: null,
                name: 'index',
                render: function (data, type, row, meta) {
                    return meta.row + 1; // Row index (1-based)
                }
            },
            { data: 'employee_code', name: 'employee_code' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'district', name: 'district' },
            { data: 'area', name: 'area' },
            { data: 'designation', name: 'designation' },
            { data: 'reporting_manager', name: 'reporting_manager' },
            { data: 'address', name: 'address' },
            { data: 'emergency_contact', name: 'emergency_contact' },
            { data: 'reset_password', name: 'reset_password' },
        ]
    });

    $('#importButton').click(function() {
        $('#fileInput').click(); 
    });

    $('#fileInput').change(function(e) {
        var file = e.target.files[0];
        if (file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                url: "{{ route('admin.users.import-employees') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    });
                    $('#employeesTable').DataTable().ajax.reload(); // Refresh table
                },
                error: function(response) {
                    msg='Error importing file.';
                    Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: msg,
                    confirmButtonColor: '#d33'
                });
                }
            });
        }
    });

    
    
});
</script>
@endsection
