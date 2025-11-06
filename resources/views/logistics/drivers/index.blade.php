@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Driver Management</h3>
        <a href="{{ route('logistics.drivers.create') }}" class="btn btn-primary">Add Driver</a>
    </div>
    <div class="filter-sec target-filter">
        <div class="row mb-2">
            <div class="col-md-4">
                <label>Driver Name</label>
                <input type="text" id="searchDriverName" class="form-control" placeholder="Search by Driver Name">
            </div>
            <div class="col-md-4">
                <label>Driver Phone</label>
                <input type="text" id="searchDriverPhone" class="form-control" placeholder="Search by Phone">
            </div>
        </div>
    </div>
    <div class="listing-sec">
        
        <table class="table table-bordered table-striped w-100" id="driversTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Adharcard No.</th>
                    <th>Liscence No.</th>
                    <th>Liscence Expiry Date</th>
                    <th>Status</th>
                    <th>Action</th>
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
    let table = $('#driversTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("logistics.drivers.getData") }}',
            data: function (d) {
                d.name = $('#searchDriverName').val();
                d.phone = $('#searchDriverPhone').val();
            }
        },
        dom: 'Bfrtip',  
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'phone', name: 'phone' },
            { data: 'address', name: 'address' },
            { data: 'adharcard_no', name: 'adharcard_no' },
            { data: 'liscence_no', name: 'liscence_no' },
            { data: 'liscence_exp_date', name: 'liscence_exp_date' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    $('#searchDriverName, #searchDriverPhone').on('keyup', function () {
        table.draw();
    });
  
    $(document).on('click', '.delete-btn', function () {
        let url = $(this).data('url');
        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Driver has been removed.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#driversTable').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: "Error!",
                            text: xhr.responseJSON?.message || "Something went wrong.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection