@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Activity Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateActivityModal">
            Create Activity
        </button>
    </div>

    <div class="filter-sec">
        <div class="row">
            <div class="col-md-3">
                <label>Activity Type</label>
                <select class="form-control" id="filter_activity_type">
                    <option value="">-Select Activity Type-</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>District</label>
                <select class="form-control" id="filter_district">
                    <option value="">-Select District-</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Dealer</label>
                <input type="text" class="form-control" id="filter_dealer" placeholder="Search by Dealer Name/Code">
            </div>
            
            {{-- <div class="col-md-3">
                <label>Employee</label>
                <select class="form-control" id="filter_employee">
                    <option value="">-Select Employee-</option>
                </select>
            </div> --}}
            <div class="col-md-2">
                <label>Assigned Date</label>
                <input type="date" class="form-control" id="filter_assigned_date">
            </div>
            <div class="col-md-2">
                <label>Due Date</label>
                <input type="date" class="form-control" id="filter_due_date">
            </div>
         
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100 table-responsive" id="activityTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Activity Type</th>
                    <th>Dealer</th>
                    <th>Assigned Employee</th>
                    <th>Assigned Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.activity.modal-create-edit')
@include('sales.activity.modal-view')
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@section('scripts')
<script>
    $(document).ready(function () {

        // $('#district').change(function () {
        //     let district_id = $(this).val();
        //     if (district_id) {
        //         // Fetch dealers in the selected district
        //         $.ajax({
        //             url: `/sales/dealers-by-district/${district_id}`,
        //             type: 'GET',
        //             success: function (response) {
        //                 $('#dealer_id').html('<option value="">-Select Dealer-</option>');
        //                 $.each(response, function (key, dealer) {
        //                     $('#dealer_id').append(`<option value="${dealer.id}">${dealer.dealer_name} (${dealer.dealer_code})</option>`);
        //                 });
        //             }
        //         });

        //         // Clear the employee dropdown
        //         $('#employee_id').html('<option value="">-Select Employee-</option>');
        //     }
        // });

        // $('#dealer_id').change(function () {
        //     let dealer_id = $(this).val();

        //     if (dealer_id) {
        //         $.ajax({
        //             url: `/sales/employees-by-dealer/${dealer_id}`,
        //             type: 'GET',
        //             success: function (response) {
        //                 $('#employee_id').html('<option value="">-Select Employee-</option>');
        //                 $.each(response, function (key, employee) {
        //                     $('#employee_id').append(`<option value="${employee.id}">${employee.name}</option>`);
        //                 });
        //             }
        //         });
        //     }
        // });


        $('#openCreateActivityModal').click(function () {
            $('#activityForm')[0].reset();
            $('#activity_id').val('');
            $('.submit-btn').text('Create');
            $('#createEditActivityModalLabel').text('Create Activity');
            $('#createEditActivityModal').modal({
                backdrop: 'static', // Prevent clicking outside to close
                keyboard: false     // Prevent "Esc" key from closing
            }).modal('show');
        });

        // $('#activityForm').submit(function (e) {
        //     e.preventDefault();
            
        //     let id = $('#activity_id').val();
        //     let url = id ? `/sales/activity/update/${id}` : "/sales/activity/store";
        //     let method = id ? 'PUT' : 'POST';
            
        //     let formData = $(this).serialize();
        //     if (id) {
        //         formData += '&_method=PUT';
        //     }

        //     Swal.fire({
        //         title: 'Are you sure?',
        //         text: id ? 'Do you want to update this activity?' : 'Do you want to create this activity?',
        //         icon: 'question',
        //         showCancelButton: true,
        //         confirmButtonText: id ? 'Yes, update it!' : 'Yes, create it!',
        //         cancelButtonText: 'No, cancel!',
        //         reverseButtons: true
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             $.ajax({
        //                 url: url,
        //                 type: 'POST',
        //                 data: formData,
        //                 success: function (response) {
        //                     Swal.fire({
        //                         title: 'Success!',
        //                         text: response.message,
        //                         icon: 'success',
        //                         timer: 2000,
        //                         showConfirmButton: true
        //                     });
        //                     $('#createEditActivityModal').modal('hide');
        //                     $('#activityForm')[0].reset();
        //                     $('#activityTable').DataTable().ajax.reload();
        //                 },
        //                 error: function (xhr) {
        //                     Swal.fire({
        //                         title: 'Error!',
        //                         text: xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong!',
        //                         icon: 'error'
        //                     });
        //                 }
        //             });
        //         }
        //     });
        // });
        // $('#assigned_date, #due_date').on('change', function () {
        //     let assignedDate = new Date($('#assigned_date').val());
        //     let dueDate = new Date($('#due_date').val());

        //     if ($('#assigned_date').val() && $('#due_date').val()) {
        //         if (dueDate < assignedDate) {
        //             Swal.fire({
        //                 icon: 'warning',
        //                 title: 'Invalid Dates',
        //                 text: 'Due Date must be greater than or equal to Assigned Date.',
        //             });
        //             $('#due_date').val(''); // reset due date if invalid
        //         }
        //     }
        // });

        
        window.handleAction = function (id, action) {
            let url = action === 'view' 
                ? "{{ route('sales.activity.view', ':id') }}" 
                : "{{ route('sales.activity.edit', ':id') }}";
            
            $.get(url.replace(':id', id), function (response) {
                if (action === 'edit') {
             
                    $('#createEditActivityModalLabel').text('Edit Activity');
                    $('#activity_id').val(response.activity.id);
                    $('#activity_type_id').val(response.activity.activity_type_id);
                    $('#assigned_date').val(response.activity.assigned_date);
                    $('#due_date').val(response.activity.due_date);
                    $('#status').val(response.activity.status);
                    $('#instruction').val(response.activity.instructions);
                    $('.submit-btn').text('Update');
                    if (response.activity.employee) {
                        let districtId = response.activity.employee.district_id;
                        let employeeId = response.activity.employee.id;

                        $('#district').val(districtId).trigger('change');

                        setTimeout(() => {
                            $('#employee_id').val(employeeId).trigger('change');
                        }, 1500); 
                    }

                    if (response.activity.dealer) {
                        setTimeout(() => {
                            $('#dealer_id').val(response.activity.dealer_id).trigger('change');
                        }, 500);
                    }

                    $('#createEditActivityModal').modal({
                        backdrop: 'static', // Prevent clicking outside to close
                        keyboard: false     // Prevent "Esc" key from closing
                    }).modal('show');

                } else if (action === 'view') {
                    let activity = response.activity; // ✅ IMPORTANT

                    $('#view_activity_type').text(
                        activity.activity_type?.name ?? '-'
                    );

                    $('#view_dealer').text(
                        activity.dealer
                            ? activity.dealer.dealer_name + ' (' + activity.dealer.dealer_code + ')'
                            : '-'
                    );

                    $('#view_employee_name').text(activity.employee?.name ?? '-');
                    $('#view_assigned_date').text(activity.assigned_date ?? '-');
                    $('#view_due_date').text(activity.due_date ?? '-');

                    $('#view_status').html(
                        activity.status === 'Completed'
                            ? '<span class="badge bg-success text-white">Completed</span>'
                            : '<span class="badge bg-warning text-dark">Pending</span>'
                    );

                    $('#view_instructions').text(
                        activity.instructions || 'No instructions provided'
                    );

                    $('#view_remarks').text(activity.remarks ?? '-');
                    $('#view_completed_date').text(activity.completed_date ?? '-');

                    let attachmentHtml = '-';

                    if (Array.isArray(activity.attachments) && activity.attachments.length) {
                        attachmentHtml = activity.attachments.map(file => {
                            // If already full URL, use as is
                            const fileUrl = file.startsWith('http')
                                ? file
                                : `/storage/${file}`;

                            return `
                                <a href="${fileUrl}" target="_blank" class="d-block">
                                    ${file.split('/').pop()}
                                </a>
                            `;
                        }).join('');
                    }

                    $('#view_attachments').html(attachmentHtml);

                    // ✅ ACTIVITY QUESTIONS
                    let questionsHtml = '';

                    if (Array.isArray(activity.question_details)) {
                        activity.question_details.forEach(q => {
                            const label = q.question_label;
                            if (!label) return;

                            questionsHtml += `
                                <div class="mb-2">
                                    <strong>${label.label_name}:</strong>
                                    <div>${q.activity_input ?? '-'}</div>
                                </div>
                            `;
                        });
                    }

                    $('#view_activity_questions').html(
                        questionsHtml || '<p>No activity inputs available</p>'
                    );

                    $('#viewModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    }).modal('show');
                }
            }).fail(function () {
                Swal.fire('Error', 'Could not fetch activity details.', 'error');
            });
        };

        var table = $('#activityTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sales.activity.list') }}",
                data: function (d) {
                    d.activity_type = $('#filter_activity_type').val();
                    d.dealer = $('#filter_dealer').val();
                    d.district = $('#filter_district').val();
                    d.employee = $('#filter_employee').val();
                    d.assigned_date = $('#filter_assigned_date').val();
                    d.due_date = $('#filter_due_date').val();
                }
            },
            columns: [
                { data: null, render: function (data, type, row, meta) { return meta.row + 1; } },
                { data: 'activity_type_name', name: 'activity_type_name' },
                { data: 'dealer_name', name: 'dealer_name' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'assigned_date', name: 'assigned_date' },
                { data: 'due_date', name: 'due_date' },
                { 
                    data: 'status', 
                    name: 'status', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        return data; // Render as HTML
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            columnDefs: [
                { targets: 6, className: "text-center" } // Center align status column
            ]
        });
        $('.filter-sec select, .filter-sec input').on('change keyup', function () {
            table.ajax.reload();
        });
      
        $('#filterSearch').click(function () {
            table.ajax.reload();
        });

       
        $(document).on('click', '.deleteActivity', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/sales/activity/delete/${id}`,
                        type: 'POST',  // Keeping POST as per your setup
                        data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: true
                            });
                            $('#activityTable').DataTable().ajax.reload();
                        },
                        error: function () {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Could not delete activity.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
        
        
        
        
        $('#district, #employee_type_id').change(function () {
            let district_id = $('#district').val();
            let employee_type_id = $('#employee_type_id').val();
    
            if (district_id && employee_type_id) {
                $.ajax({
                    url: `/sales/activity/employees-by-district-type/${district_id}/${employee_type_id}`,
                    type: 'GET',
                    success: function (response) {
                        $('#employee_id').html('<option value="">-Select Employee-</option>');
                        $.each(response, function (key, emp) {
                            $('#employee_id').append(`<option value="${emp.id}">${emp.name}</option>`);
                        });
                    }
                });
            } else {
                $('#employee_id').html('<option value="">-Select Employee-</option>');
                $('#dealer_id').html('<option value="">-Select Dealer-</option>');
            }
        });
    
        // Fetch dealers when an employee is selected
        $('#employee_id').change(function () {
            let employee_id = $(this).val();
            if (employee_id) {
                $.ajax({
                    url: `/sales/activity/dealers-by-employee/${employee_id}`,
                    type: 'GET',
                    success: function (response) {
                        console.log(response);
                        $('#dealer_id').html('<option value="">-Select Dealer-</option>');
                        $.each(response, function (key, dealer) {
                            $('#dealer_id').append(`<option value="${dealer.id}">${dealer.dealer_name}</option>`);
                        });
                    }
                });
            }
        });
    
        // Date validation
        $('#assigned_date, #due_date').on('change', function () {
            let assignedDate = new Date($('#assigned_date').val());
            let dueDate = new Date($('#due_date').val());
            if ($('#assigned_date').val() && $('#due_date').val() && dueDate < assignedDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Dates',
                    text: 'Due Date must be greater than or equal to Assigned Date.',
                });
                $('#due_date').val('');
            }
        });
    
        // Form submit (same as your previous logic)
        $('#activityForm').submit(function (e) {
            e.preventDefault();
            let id = $('#activity_id').val();
            let url = id ? `/sales/activity/update/${id}` : "/sales/activity/store";
            let method = id ? 'PUT' : 'POST';
            let formData = $(this).serialize();
            if (id) formData += '&_method=PUT';
    
            Swal.fire({
                title: 'Are you sure?',
                text: id ? 'Do you want to update this activity?' : 'Do you want to create this activity?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: id ? 'Yes, update it!' : 'Yes, create it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        success: function (response) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: true
                            });
                            $('#createEditActivityModal').modal('hide');
                            $('#activityForm')[0].reset();
                            $('#activityTable').DataTable().ajax.reload();
                        },
                        error: function (xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong!',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

    });
</script>
@endsection

