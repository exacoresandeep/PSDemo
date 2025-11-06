@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align d-flex justify-content-between">
        <h3>Employee Management</h3>
        <button class="btn btn-primary" id="importButton">Import</button>
        <input type="file" id="fileInput" style="display: none;" accept=".csv, .xlsx"/>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="employeesTable">
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
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#employeesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.users.employee-list') }}",
        columns: [
            { data: 'id', name: 'id' },
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
                    alert(response.message);
                    $('#employeesTable').DataTable().ajax.reload(); // Refresh table
                },
                error: function(response) {
                    alert('Error importing file.');
                }
            });
        }
    });
});
</script>
@endsection
