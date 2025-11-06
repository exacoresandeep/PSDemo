@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Activity Type Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateActivityTypeModal">
            Create
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="activityTypeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Activity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Include modal for creating/editing activity type -->
@include('sales.activity.type-modal-create-edit')

@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@section('scripts')
<script>
    $(document).ready(function () {
        //Activity Type
        var table = $('#activityTypeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sales.activity.activity-type-list') }}",
                data: function (d) {
                    d.status = $('#statusFilter').val(); 
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, 
                { data: 'name', name: 'name' },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data) {
                        return data == 1 
                            ? '<span class="badge bg-success">Active</span>' 
                            : '<span class="badge bg-danger">Inactive</span>';
                    }
                },
                {
                    data: 'id',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return `
                            <button class="btn btn-warning btn-sm editActivityType" data-id="${data}"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-danger btn-sm deleteActivityType" data-id="${data}"><i class="fa fa-trash"></i></button>
                        `;
                    }
                }
            ]
        });


        // Open Create Modal
        $('#openCreateActivityTypeModal').click(function () {
            $('#activityTypeForm')[0].reset(); 
            $('#activity_type_id').val('');
            $('.submit-btn').text('Create');
            $('#createEditActivityTypeModalLabel').text('Create Activity Type');
            $('#createEditActivityTypeModal').modal({
                backdrop: 'static', // Prevent clicking outside to close
                keyboard: false     // Prevent "Esc" key from closing
            }).modal('show');
        });

        // Handle Create / Update
        $('#activityTypeForm').submit(function (e) {
            e.preventDefault();
            
            let id = $('#activity_type_id').val();
            let url = id 
                ? "{{ route('sales.activity.type.update', ':id') }}".replace(':id', id) 
                : "{{ route('sales.activity.type.store') }}";
            
            let method = id ? 'PUT' : 'POST';
            
            let formData = $(this).serialize(); 
            if (id) {
                formData += '&_method=PUT'; 
            }

            $.ajax({
                url: url,
                type: 'POST',  
                data: formData,
                success: function (response) {
                    Swal.fire({
                        title: "Success!",
                        text: response.message || "Activity type saved successfully!",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: true
                    });

                    $('#createEditActivityTypeModal').modal('hide');
                    $('#activityTypeForm')[0].reset();
                    table.ajax.reload();
                },
                error: function (xhr) {
                    let errorMessage = xhr.responseJSON?.message || "Something went wrong!";
                    Swal.fire({
                        title: "Error!",
                        text: errorMessage,
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }
            });
        });

        // Edit Activity Type
        $(document).on('click', '.editActivityType', function () {
            let id = $(this).data('id');

            $.ajax({
                url: "{{ route('sales.activity.type.edit', '') }}/" + id,
                type: 'GET',
                success: function (response) {
                    $('#activity_type_id').val(response.activity_type.id);
                    $('#activity_name').val(response.activity_type.name);
                    $('#status').val(response.activity_type.status);
                    $('#createEditActivityTypeModal').modal({
                        backdrop: 'static', // Prevent clicking outside to close
                        keyboard: false     // Prevent "Esc" key from closing
                    }).modal('show');
                    $('#createEditActivityTypeModalLabel').text('Edit Activity Type');
                    $('.submit-btn').text('Update');

                },
                error: function () {
                    alert('Error fetching activity type details.');
                }
            });
        });

        // Delete Activity Type
        $(document).on('click', '.deleteActivityType', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: "Are you sure?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                // confirmButtonColor: "#d33",
                // cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel!",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/sales/activity/type/delete/${id}`, 
                        type: 'DELETE', 
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                        },
                        success: function (response) {
                            Swal.fire({
                                title: "Deleted!",
                                text: response.message || "Activity Type deleted successfully!",
                                icon: "success",
                                timer: 2000,
                                showConfirmButton: true
                            });
                            table.ajax.reload();
                        },
                        error: function (xhr) {
                            Swal.fire({
                                title: "Error!",
                                text: xhr.responseJSON?.message || "Failed to delete activity type!",
                                icon: "error",
                                confirmButtonText: "OK"
                            });
                        }
                    });
                }
            });
        });


    });
</script>
@endsection
