@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Vehicles Management</h3>
        <a href="{{ route('logistics.vehicles.create') }}" class="btn btn-primary">Add Vehicle</a>
    </div>
    <div class="filter-sec target-filter">
        <div class="row mb-2">
            
        </div>
    </div>
    <div class="listing-sec">
        
        <table class="table table-bordered table-striped w-100 table-responsive" id="vehiclesTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Vehicle Category</th>
                    <th>Vehicle No</th>
                    <th>Chasis No</th>
                    <th>RC Exp Date</th>
                    <th>Insurance Type</th>
                    <th>Authorization date</th>
                    <th>status</th>
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
$(document).ready(function () {
    let table = $('#vehiclesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('logistics.vehicles.getData') }}',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'vehicle_category_id', name: 'vehicle_category_id' },
            { data: 'vehicle_no', name: 'vehicle_no' },
            { data: 'chasis_no', name: 'chasis_no' },
            { data: 'rc_exp_date', name: 'rc_exp_date' },
            { data: 'insurance_type', name: 'insurance_type' },
            { data: 'authorization_date', name: 'authorization_date' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Delete Vehicle (Soft Delete)
    $(document).on('click', '.delete-btn', function () {
        let url = $(this).data('url'); // use the proper route URL from button

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
                    type: 'POST', // use POST
                    data: {
                        _method: 'DELETE', // tell Laravel this is a DELETE request
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Vehicle has been removed.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#vehiclesTable').DataTable().ajax.reload();
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