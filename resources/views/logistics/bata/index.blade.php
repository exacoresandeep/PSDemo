@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Bata Settlement</h3>
        <a href="{{ route('logistics.bata.create') }}" class="btn btn-primary">Add Bata</a>
    </div>

    <!-- Filter Section -->
    <div class="filter-sec target-filter">
        <div class="row mb-2">
            <div class="col-md-3">
                <label>Driver Name</label>
                <input type="text" id="searchDriverName" class="form-control" placeholder="Search by Driver Name">
            </div>
            <div class="col-md-3">
                <label>Driver Phone</label>
                <input type="text" id="searchDriverPhone" class="form-control" placeholder="Search by Phone">
            </div>
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" id="searchFromDate" class="form-control">
            </div>
            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" id="searchToDate" class="form-control">
            </div>
        </div>
    </div>

    <!-- DataTable Section -->
    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="bataTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Driver Name</th>
                    <th>Phone</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>No. of Trips</th>
                    <th>Total KM</th>
                    <th>Total Amount</th>
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
    let table = $('#bataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("logistics.bata.getData") }}',
            data: function(d) {
                d.driver_name = $('#searchDriverName').val();
                d.phone = $('#searchDriverPhone').val();
                d.from_date = $('#searchFromDate').val();
                d.to_date = $('#searchToDate').val();
            }
        },
        dom: 'Bfrtip',  
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'driver_name', name: 'driver_name' },
            { data: 'phone', name: 'phone' },
            { data: 'from_date', name: 'from_date' },
            { data: 'to_date', name: 'to_date' },
            { data: 'no_of_trips', name: 'no_of_trips' },
            { data: 'total_km', name: 'total_km' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // Redraw table on filter change
    $('#searchDriverName, #searchDriverPhone, #searchFromDate, #searchToDate').on('keyup change', function () {
        table.draw();
    });
});
</script>
@endsection
