@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Price Management</h3>
    
        <button type="button" class="btn btn-primary" id="openCreatePriceModal">Create</button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="priceTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Product Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('accounts.price-management.modal-create-edit')
@include('accounts.price-management.modal-view')

@endsection

@section('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const priceTable = $('#priceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('accounts.price.list') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'product_name', name: 'product_name' }, // make sure this matches your API
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Open Modal
    $('#openCreatePriceModal').on('click', function () {
        $('#priceForm')[0].reset();
        $('#price_id').val('');
        $('#priceForm input, #priceForm select').prop('disabled', false); // Enable all fields
        $('#savePriceBtn').text('Create');
        // $("#product_id").val(1);
        // loadProductTypes(1);
        $('#createEditPriceModal').modal('show');
    });
    // $('#product_id').on('change', function() {
    //     let product_id = $(this).val();
    //     if (product_id) {
    //         loadProductTypes(product_id);
    //     }
    // });

    // On Edit button click
    $(document).on('click', '.editPrice', function () {
        let id = $(this).data('id');
        $('#priceModalLabel').text('Edit Price');
        $.get(`{{ url('accounts/price-management/edit') }}/${id}`, function (data) {
            $('#price_id').val(data.id);
            $('#start_date').val(data.start_date);
            $('#end_date').val(data.end_date);
            $('#product_type_id').val(data.product_type_id);
            $('#dealer_price').val(data.dealer_price);
            $('#advance_dealer_price').val(data.advance_dealer_price);
            $('#status').val(data.status);

            // Disable all fields except status
            $('#priceForm input, #priceForm select').prop('disabled', true);
            $('#status').prop('disabled', false);

            $('#savePriceBtn').text('Update');
            $('#createEditPriceModal').modal('show');
        });
    });
    $(document).on('click', '.viewPrice', function () {
        const startDate = $(this).data('start');
        const endDate = $(this).data('end');

        $.ajax({
            url: '{{ route('accounts.price.show') }}',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function (res) {
                let rows = '';
                res.types.forEach((type, index) => {
                    rows += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${type.product_type}</td>
                            <td>${type.dealer_price}</td>
                            <td>${type.advance_dealer_price}</td>
                        </tr>
                    `;
                });

                $('#viewPriceStartDate').text(moment(res.start_date).format('DD-MM-YYYY'));
                $('#viewPriceEndDate').text(moment(res.end_date).format('DD-MM-YYYY'));
                $('#viewPriceProduct').text(res.product_name);
                $('#viewPriceTable tbody').html(rows);

                $('#viewPriceModal').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'Failed to load price details', 'error');
            }
        });
    });
    // function loadProductTypes(product_id) {
    //     $.ajax({
    //         url: `/accounts/price-management/product-types/${product_id}`,
    //         type: "GET",
    //         success: function(types) {
    //             let html = '';
    //             types.forEach((type, index) => {
    //                 html += `
    //                     <div class="row mb-2">
    //                         <input type="hidden" name="types[${index}][product_type_id]" value="${type.id}">

    //                         <div class="col-md-4">
    //                             <label class="form-label">Product Type</label>
    //                             <input type="text" class="form-control" value="${type.type_name}" readonly>
    //                         </div>

    //                         <div class="col-md-4">
    //                             <label class="form-label">Dealer Price</label>
    //                             <input type="number" name="types[${index}][dealer_price]" class="form-control" required>
    //                         </div>

    //                         <div class="col-md-4">
    //                             <label class="form-label">Advance Dealer Price</label>
    //                             <input type="number" name="types[${index}][advance_dealer_price]" class="form-control" required>
    //                         </div>
    //                     </div>
    //                 `;
    //             });

    //             $('#productTypeContainer').html(html);
    //         }
    //     });
    // }
    $(document).ready(function () {

    $('#product_id').change(function () {
        let productId = $(this).val();

        $('#product-type-container').html(''); // Clear old types

        if (productId) {
            $.ajax({
                url: "{{ route('get.types.by.product') }}",
                type: "POST",
                data: {
                    product_id: productId,
                    _token: "{{ csrf_token() }}"
                },
                success: function (types) {

                    let html = '';

                    $.each(types, function (index, type) {
                        html += `
                            <div class="row mb-2">
                                <input type="hidden" name="types[${index}][product_type_id]" value="${type.id}">

                                <div class="col-md-4">
                                    <label class="form-label">Product Type</label>
                                    <input type="text" class="form-control" value="${type.type_name}" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Dealer Price</label>
                                    <input type="number" name="types[${index}][dealer_price]" class="form-control" step="0.01" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Advance Dealer Price</label>
                                    <input type="number" name="types[${index}][advance_dealer_price]" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        `;
                    });

                    $('#product-type-container').html(html);
                }
            });
        }
    });

});


    // Submit Form (Create or Update)
    $('#priceForm').on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        let priceId = $('#price_id').val();
        let isUpdate = !!priceId;
        let url = isUpdate ? `/accounts/price-management/update/${priceId}` : `{{ route('accounts.price.store') }}`;
        let method = 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                Swal.fire({
                    title: 'Success!',
                    text: isUpdate ? 'Price Updated Successfully.' : 'Prices Created Successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#createEditPriceModal').modal('hide');
                    priceTable.ajax.reload();
                });
            },
            error: function (xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Something went wrong',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

   

    // Delete Price
    $(document).on('click', '.deletePrice', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/price-management/delete/${id}`,
                    type: 'DELETE',
                    success: function (response) {
                        Swal.fire('Deleted!', response.message, 'success');
                        priceTable.ajax.reload();
                    },
                    error: function (xhr) {
                        Swal.fire('Error!', 'Unable to delete.', 'error');
                    }
                });
            }
        });
    });
</script>
@endsection

