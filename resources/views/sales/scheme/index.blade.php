@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Scheme Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateModal">
            Create Scheme
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100 table-responsive" id="schemeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Product</th>
                    <th>Scheme Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.scheme.modal-create-edit')

@endsection 
@section('scripts')
<script>
    $(document).ready(function () {
        $('#openCreateModal').click(function () {

            loadProduct();
            $('#schemeForm')[0].reset(); 
            $('#scheme_id').val(''); 
            $('#createEditModalLabel').text('Create Scheme');
            $('#createEditModal').modal({
                backdrop: 'static',
                keyboard: false   
            }).modal('show'); 
        });

        $('#schemeForm').submit(function (e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let schemeId = $('#scheme_id').val();
            let url = schemeId ? "{{ route('sales.scheme.update') }}" : "{{ route('sales.scheme.store') }}";

            $.ajax({
                url: url,
                type: "POST",
                data: formData,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Swal.fire('Success', response.message, 'success');
                    $('#createEditModal').modal('hide'); 
                    $('#schemeTable').DataTable().ajax.reload();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessages = "";
                        $.each(errors, function (key, value) {
                            errorMessages += value[0] + "<br>";
                        });

                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            html: errorMessages
                        });

                    } else if (xhr.status === 400 || xhr.status === 409) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: xhr.responseJSON.message
                        });

                    } else {
                        Swal.fire('Error', 'Could not Create target.', 'error');
                    }
                }
            });
        });


        var table = $('#schemeTable').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: "{{ route('sales.scheme.list') }}", 
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'product_name', name: 'product_name' }, 
                { data: 'scheme', name: 'scheme' },             
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });


        window.handleAction = function (id, action) {
            if (action === 'edit') {
                $.ajax({
                    url: "/sales/scheme/" + id,
                    type: "GET",
                    success: function (response) {
                        // Update modal heading
                        $('#createEditModalLabel').text('Edit Scheme');
                        $('#scheme_id').val(id);

                        // Populate form fields
                        $('#product_id').val(response.scheme.product_id);
                        $('#scheme_amount').val(response.scheme.scheme);
                        $('#status').val(response.scheme.status);
                        $('#saveSchemeBtn').text('Update');

                        // Show modal
                        $('#createEditModal').modal({
                            backdrop: 'static',
                            keyboard: false
                        }).modal('show');
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not fetch scheme details.', 'error');
                    }
                });
            }
        };

    });
    function loadProduct() {
        $.ajax({
            url: "{{ route('loadProduct') }}",
            type: "GET",
            success: function(res) {
                console.log(res);
                if (res.products && res.products.length > 0) {
                let product = res.products[0];   // first product

                $("#product_id").val(product.id);
                $("#product_name").val(product.product_name);
            }
            },
            error: function() {
                console.log("Error loading product");
            }
        });
    }
</script>
@endsection

