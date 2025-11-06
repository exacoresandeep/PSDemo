@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Target Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateModal">
            Create Target
        </button>
    </div>

    <div class="filter-sec target-filter">
        <div class="row">
            <div class="col-md-3">
                <label>Employee Type</label>
                <select class="form-control" id="filter_employee_type">
                    <option value="">-Select Employee Type-</option>
                    @foreach($employeeTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Employee</label>
                <select class="form-control" id="filter_employee">
                    <option value="">-Select Employee-</option>
                </select>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label>Target Month</label>
                <select class="form-control" id="filter_month">
                    <option value="">-Select Month-</option>
                    @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="targetTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Type</th>
                    <th>Employee Name</th>
                    <th>Target By Year</th>
                    <th>Target By Month</th>
                    <th>Unique Lead</th>
                    <th>Customer Visit</th>
                    <th>Aashiyana Count</th>
                    <th>Targets in Tons</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.target.modal-create-edit')

@include('sales.target.modal-view')

@endsection 
@section('scripts')
<script>
    $(document).ready(function () {
        $('#openCreateModal').click(function () {
            $('#targetForm')[0].reset(); 
            $('#target_id').val(''); 
            $('#createEditModalLabel').text('Create Target');
            $('#createEditModal').modal({
                backdrop: 'static', // Prevent clicking outside to close
                keyboard: false     // Prevent "Esc" key from closing
            }).modal('show'); 
        });

        $('#targetForm').submit(function (e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let targetId = $('#target_id').val();
            let url = targetId ? "{{ route('sales.target.update') }}" : "{{ route('sales.target.store') }}";

            $.ajax({
                url: url,
                type: "POST",
                data: formData,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    Swal.fire('Success', response.message, 'success');
                    $('#createEditModal').modal('hide'); 
                    $('#targetTable').DataTable().ajax.reload();
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


        var table = $('#targetTable').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: "{{ route('sales.target.list') }}",
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (d) {
                    d.employee_type = $('#filter_employee_type').val();
                    d.employee_id = $('#filter_employee').val();
                    d.year = $('#filter_year').val();
                    d.month = $('#filter_month').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'employee_type', name: 'employee_type' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'year', name: 'year' },
                { data: 'month', name: 'month' },
                { data: 'unique_lead', name: 'unique_lead' },
                { data: 'customer_visit', name: 'customer_visit' },
                { data: 'aashiyana', name: 'aashiyana' },
                { data: 'order_quantity', name: 'order_quantity' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#employee_type').change(function () {
            let employeeTypeId = $(this).val();
            $('#employee_id').html('<option value="">Loading...</option>');
            $('#customer_visit').val('').prop('readonly', false);
            if (employeeTypeId) {
                $.get("{{ route('sales.getEmployees', '') }}/" + employeeTypeId, function (response) {
                    $('#employee_id').html('<option value="">-Select Employee-</option>');
                    $.each(response, function (index, employee) {
                        $('#employee_id').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                    });
                    
                }).fail(function () {
                    Swal.fire('Error', 'Could not load employees.', 'error');
                });
            } else {
                $('#employee_id').html('<option value="">-Select Employee-</option>');
            }
        });
        $('#employee_id').change(function () {
            let employeeId = $(this).val();
            let employeeTypeId = $('#employee_type').val();
            $('#customer_visit').val('').prop('readonly', false); // Reset field

            if (employeeId) {
                $.get("{{ url('sales/targets/getVisitCount') }}/" + employeeTypeId + "/employee/" + employeeId, function (response) {
                    if (response.visit_count > 0) {
                        $('#customer_visit').val(response.visit_count).prop('readonly', true);
                    } else {
                        $('#customer_visit').val('').prop('readonly', false);
                    }
                }).fail(function () {
                    Swal.fire('Error', 'Could not load visit count.', 'error');
                });
            }
        });
        $('.target-filter select').change(function () {
            table.ajax.reload();
        });

        $('#filter_employee_type').change(function () {
            let employeeTypeId = $(this).val();
            $('#filter_employee').html('<option value="">Loading...</option>');

            if (employeeTypeId) {
                $.get("{{ route('sales.getEmployees', '') }}/" + employeeTypeId, function (response) {
                    $('#filter_employee').html('<option value="">-Select Employee-</option>');
                    $.each(response, function (index, employee) {
                        $('#filter_employee').append('<option value="' + employee.id + '">' + employee.name + '</option>');
                    });
                });
            } else {
                $('#filter_employee').html('<option value="">-Select Employee-</option>');
            }
        });

        function deleteTarget(id) {
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
                        url: "{{ route('sales.target.delete', '') }}/" + id,
                        type: "DELETE",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                $('#targetTable').DataTable().ajax.reload(); // Reload DataTable
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Error', 'Could not delete target.', 'error');
                        }
                    });
                }
            });
        }

        window.handleAction = function (id, action) {
            $.ajax({
                url: "/sales/targets/" + id, 
                type: "GET",
                success: function (response) {
                    if (action === 'edit') {
                        $('#createEditModalLabel').text('Edit Target');
                        $('#target_id').val(id);
                        $('#employee_type').val(response.target.employee_type_id).trigger('change');

                        setTimeout(() => {
                            $('#employee_id').val(response.target.employee_id);
                            $.get("{{ url('sales/targets/getVisitCount') }}/" + employeeTypeId + "/employee/" + employeeId, function (response) {
                                if (visitResponse.visit_count > 0) {
                                    $('#customer_visit').val(visitResponse.visit_count).prop('readonly', true);
                                } else {
                                    $('#customer_visit').val(response.target.customer_visit).prop('readonly', false);
                                }
                            }).fail(function () {
                                Swal.fire('Error', 'Could not load visit count.', 'error');
                            });

                        }, 500);

                        $('#year').val(response.target.year);
                        $('#month').val(response.target.month);
                        $('#unique_lead').val(response.target.unique_lead);
                        $('#customer_visit').val(response.target.customer_visit);
                        $('#aashiyana').val(response.target.aashiyana);
                        $('#order_quantity').val(response.target.order_quantity);
                        $('#saveTargetBtn').text('Update');
                        $('#createEditModal').modal({
                            backdrop: 'static', // Prevent clicking outside to close
                            keyboard: false     // Prevent "Esc" key from closing
                        }).modal('show');
                    } else if (action === 'view') {
                        $('#view_employee_type').text(response.target.employee_type || '-');
                        $('#view_employee_name').text(response.target.employee_name || '-');
                        $('#view_year').text(response.target.year || '-');
                        $('#view_month').text(response.target.month || '-');
                        $('#view_unique_lead').text(response.target.unique_lead || '0');
                        $('#view_customer_visit').text(response.target.customer_visit || '0');
                        $('#view_aashiyana').text(response.target.aashiyana || '0');
                        $('#view_order_quantity').text(response.target.order_quantity || '0');

                        $('#viewModal').modal({
                            backdrop: 'static', // Prevent clicking outside to close
                            keyboard: false     // Prevent "Esc" key from closing
                        }).modal('show');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Could not fetch target details.', 'error');
                }
            });
        };

        window.deleteTarget = deleteTarget;
    });
</script>
@endsection
