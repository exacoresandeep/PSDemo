@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Created Activities</h3>
        <button type="button" class="btn btn-primary" id="openCreateActivityTypeModal">
            Create Activity
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100 table-responsive" id="activityTypeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Activity Name</th>
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
        let fieldIndex = 0; 
        $("#addFieldBtn").on("click", function () {
            fieldIndex++;
            
            let newRow = `
                <div class="row mb-3 custom-field-row">
                    <div class="col-md-4">
                        <label>Field Type</label>
                        <select class="form-control field-type" name="fields[${fieldIndex}][type]">
                            <option value="text">Text Box</option>
                            <option value="select">Drop Down</option>
                            <option value="attachment">Attachment</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Label Name</label>
                        <input type="text" class="form-control" name="fields[${fieldIndex}][label]" placeholder="Enter Label Name">
                    </div>
                    <div class="col-md-4 dropdown-options-col" style="display:none;">
                        <label>Dropdown Options</label>
                        <input type="text" class="form-control" name="fields[${fieldIndex}][options]" placeholder="Enter options in commas">
                    </div>
                </div>
            `;
            $("#customFieldsContainer").append(newRow);
        });

        $(document).on("change", ".field-type", function () {
            let row = $(this).closest(".custom-field-row");
            if ($(this).val() === "select") {
                row.find(".dropdown-options-col").show();
            } else {
                row.find(".dropdown-options-col").hide();
            }
        });
        //Activity Type
        var table = $('#activityTypeTable').DataTable({
            processing: true,
            serverSide: false,
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
             $('#customFieldsContainer').empty();
            $('#activity_type_id').val('');
            $('.submit-btn').text('Create');
            $('#createEditActivityTypeModalLabel').text('Create New Activity');
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
                        text: response.message || "New Activity saved successfully!",
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
                    let data = response.activity_type;
                    $('#activity_type_id').val(data.id);
                    $('#activity_name').val(data.name);
                    $('#status').val(data.status);
                    $('#customFieldsContainer').empty();

                    // Populate fields
                    if (data.question_labels && data.question_labels.length > 0) {
                        data.question_labels.forEach(function(labelObj) {
                            let id = labelObj.id || '';
                            let type = labelObj.type || 'text';
                            let label = labelObj.label_name || ''; // fixed property name
                            let options = labelObj.label_options || ''; // fixed property name

                            let showOptions = (type === 'select') ? '' : 'style="display:none;"';

                            let rowHtml = `
                                <div class="row mb-3 custom-field-row">
                                    <input type="hidden" name="fields[${fieldIndex}][id]" value="${id}">
                                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-field" data-id="${id}"><i class="fa fa-trash"></i></button>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Field Type</label>
                                        <select class="form-control field-type" name="fields[${fieldIndex}][type]">
                                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text Box</option>
                                            <option value="select" ${type === 'select' ? 'selected' : ''}>Drop Down</option>
                                            <option value="attachment" ${type === 'attachment' ? 'selected' : ''}>Attachment</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Label Name</label>
                                        <input type="text" class="form-control" name="fields[${fieldIndex}][label]" value="${label}" placeholder="Enter Label Name">
                                    </div>
                                    <div class="col-md-4 dropdown-options-col" ${showOptions}>
                                        <label>Dropdown Options</label>
                                        <input type="text" class="form-control" name="fields[${fieldIndex}][options]" value="${options}" placeholder="Enter options in commas">
                                    </div>
                                    
                                </div>
                            `;

                            $('#customFieldsContainer').append(rowHtml);
                            fieldIndex++;
                        });
                    } else {
                        $('#customFieldsContainer').append(`<p class="text-muted">No custom fields found.</p>`);
                    }

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

        $(document).on('click', '.remove-field', function () {
            let button = $(this);
            let id = button.data('id');

            if (!id) {
                // New row not yet saved to DB, just remove it
                button.closest('.custom-field-row').remove();
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "This field will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('sales.activity.type.questionlabel.delete', '') }}/" + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (response) {
                            if (response.success) {
                                button.closest('.custom-field-row').remove();
                                Swal.fire(
                                    'Deleted!',
                                    'The field has been deleted.',
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Error deleting field.',
                                    'error'
                                );
                            }
                        },
                        error: function () {
                            Swal.fire(
                                'Error!',
                                'Already used in activities. Cant delete.',
                                'error'
                            );
                        }
                    });
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
