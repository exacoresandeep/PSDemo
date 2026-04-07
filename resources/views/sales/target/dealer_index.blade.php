@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Dealer Target Management</h3>
        <button type="button" class="btn btn-primary" id="openDealerCreateModal">
            Create Dealer Target
        </button>
    </div>

    <div class="filter-sec target-filter">
        <div class="row">
            <div class="col-md-4">
                <label>Dealer</label>
                <select class="form-control" id="filter_dealer">
                    <option value="">-Select Dealer-</option>
                   
                    @foreach($dealers as $dealer)
                        <option value="{{ $dealer->id }}">{{ $dealer->dealer_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Target Year</label>
                <select class="form-control" id="filter_year">
                    @php
                        $currentYear = date('Y');
                        for ($i = 0; $i < 5; $i++) {
                            echo '<option value="' . ($currentYear + $i) . '">' . ($currentYear + $i) . '</option>';
                        }
                    @endphp
                </select>
            </div>

            <div class="col-md-4">
                <label>Target Month</label>
                <select class="form-control" id="filter_month">
                    <option value="">-Select Month-</option>
                    @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $month)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="dealerTargetTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Dealer Name</th>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Targets in Tons</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.target.dealer-modal-create-edit')
@include('sales.target.dealer-modal-view')

@endsection
@section('scripts')
<script>
$(document).ready(function () {

    /* ===============================
       OPEN CREATE MODAL
    =============================== */
    $('#openDealerCreateModal').click(function () {
        $('#dealerTargetForm')[0].reset();
        $('#dealer_target_id').val('');
        $('#dealerCreateEditModalLabel').text('Create Dealer Target');

        $('#dealerCreateEditModal').modal({
            backdrop: 'static',
            keyboard: false
        }).modal('show');
    });


    /* ===============================
       SUBMIT FORM (CREATE / UPDATE)
    =============================== */
    $('#dealerTargetForm').submit(function (e) {
        e.preventDefault();

        let formData = $(this).serialize();
        let targetId = $('#dealer_target_id').val();

        let url = targetId 
            ? "{{ route('sales.target.dealer.update') }}"
            : "{{ route('sales.target.dealer.store') }}";

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

            success: function (response) {
                Swal.fire('Success', response.message, 'success');
                $('#dealerCreateEditModal').modal('hide');
                $('#dealerTargetTable').DataTable().ajax.reload();
            },

            error: function (xhr) {

            if (xhr.status === 422) {

                // If it's Laravel validation errors
                if (xhr.responseJSON.errors) {

                    let errors = xhr.responseJSON.errors;
                    let errorMessages = "";

                    $.each(errors, function (key, value) {
                        errorMessages += value[0] + "<br>";
                    });

                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        html: errorMessages
                    });

                } 
                // If it's your custom duplicate message
                else if (xhr.responseJSON.message) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: xhr.responseJSON.message
                    });

                }

            } else {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        }
        });
    });


    /* ===============================
       DATATABLE
    =============================== */
    var table = $('#dealerTargetTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,

        ajax: {
            url: "{{ route('sales.target.dealer.list') }}",
            type: "POST",
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.dealer_id = $('#filter_dealer').val();
                d.year = $('#filter_year').val();
                d.month = $('#filter_month').val();
            }
        },

        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'dealer_name', name: 'dealer_name' },
            { data: 'year', name: 'year' },
            { data: 'month', name: 'month' },
            { data: 'order_quantity', name: 'order_quantity' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });


    /* ===============================
       FILTER RELOAD
    =============================== */
    $('.target-filter select').change(function () {
        table.ajax.reload();
    });


    /* ===============================
       DELETE
    =============================== */
    function deleteDealerTarget(id) {

        Swal.fire({
            title: 'Are you sure?',
            text: "This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ route('sales.target.dealer.delete', '') }}/" + id,
                    type: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },

                     success: function (response) {
                        if (response.status) {   // ✅ changed here
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Something went wrong!', 'error');
                    }
                });
            }
        });
    }


    /* ===============================
       EDIT / VIEW
    =============================== */
    window.handleDealerAction = function (id, action) {

        $.ajax({
            url: "{{ route('sales.target.dealer.view', ':id') }}".replace(':id', id),
            type: "GET",

            success: function (response) {

                if (response.status && action === 'edit') {

                    let data = response.data; // 👈 no [0]

                    $('#dealerCreateEditModalLabel').text('Edit Dealer Target');
                    $('#dealer_target_id').val(data.id);

                    $('#dealer_id').val(data.dealer_id);
                    $('#dealer_year').val(data.year);
                    $('#dealer_month').val(data.month);
                    $('#dealer_order_quantity').val(data.order_quantity);

                    $('#dealerCreateEditModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    }).modal('show');
                }
            },

            error: function () {
                Swal.fire('Error', 'Could not fetch dealer target.', 'error');
            }
        });
    };

    window.deleteDealerTarget = deleteDealerTarget;

});
</script>
@endsection