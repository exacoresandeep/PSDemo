@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Assigned Route Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateAssignRouteModal">Create</button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="routeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Type</th>
                    <th>Employee</th>
                    <th>Assigned Routes & Dealers</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.route.modal-create-edit', ['productId' => $productId])

@endsection

@section('scripts')
<script>
    // $(document).ready(function () {
      
    //     function initializeSelect2() {
    //         $('.select2').select2({ width: '100%', dropdownParent: $('#createEditAssignRouteModal') });
    //         $('.select2-multi').select2({ width: '100%', dropdownParent: $('#createEditAssignRouteModal'), placeholder: "Select Locations", allowClear: true });
    //     }

    //     initializeSelect2();
        
    //     function updateRouteDropdowns() {
    //         let selectedRoutes = [];

    //         $('select[name^="routes"]').each(function () {
    //             let val = $(this).val();
    //             if (val) selectedRoutes.push(val);
    //         });

    //         $('select[name^="routes"]').each(function () {
    //             let currentSelect = $(this);
    //             let currentValue = currentSelect.val();

    //             currentSelect.find('option').each(function () {
    //                 let optionValue = $(this).val();

    //                 if (optionValue && selectedRoutes.includes(optionValue) && optionValue !== currentValue) {
    //                     $(this).prop('disabled', true);
    //                 } else {
    //                     $(this).prop('disabled', false);
    //                 }
    //             });
    //         });
    //     }
     
    //     function updateLocationDropdowns() {
    //         let selectedLocations = new Set();

    //         $('.select2-multi').each(function () {
    //             let values = $(this).val();
    //             if (values) {
    //                 values.forEach(value => selectedLocations.add(value));
    //             }
    //         });

    //         $('.select2-multi').each(function () {
    //             let currentSelect = $(this);
    //             let currentValue = new Set(currentSelect.val() || []);

    //             currentSelect.find('option').each(function () {
    //                 let optionValue = $(this).val();

    //                 if (optionValue && selectedLocations.has(optionValue) && !currentValue.has(optionValue)) {
    //                     $(this).prop('disabled', true);
    //                 } else {
    //                     $(this).prop('disabled', false);
    //                 }
    //             });

    //             currentSelect.trigger('change.select2');
    //         });
    //     }
    //     $(document).on('change', '.select2-multi', function () {
    //         updateLocationDropdowns();
    //     });

    //     $(document).on('change', 'select[name^="routes"]', function () {
    //         updateRouteDropdowns();
    //     });
   

    //     function loadDistricts() {
    //         $.get("{{ route('sales.get-districts') }}", function (data) {
    //             $('#district').empty().append('<option value="">Select District</option>');
    //             $.each(data, function (key, district) {
    //                 $('#district').append(`<option value="${district.id}">${district.name}</option>`);
    //             });
    //         });
    //     }
    //     $('#district, #employee_type').on('change', function () {
    //         let district_id = $('#district').val();
    //         let employee_type_id = $('#employee_type').val();

    //         if (employee_type_id == 1) { 
    //             $('#asoField').show();
    //             $('#aso').attr('required', true);
    //             if (district_id) {
    //                 $.get("{{ route('sales.get-employees') }}", { district_id: district_id, employee_type_id: 2 }, function (data) {
    //                     $('#aso').empty().append('<option value="">Select Area Sales Officer</option>');
    //                     $.each(data, function (key, employee) {
    //                         $('#aso').append(`<option value="${employee.id}">${employee.name}</option>`);
    //                     });
    //                 });
    //             }
    //         } else {
    //             $('#asoField').hide();
    //             $('#aso').removeAttr('required'); 
    //             $('#aso').val("").trigger('change');
    //         }

    //         if (district_id && employee_type_id) {
    //             $.get("{{ route('sales.get-employees') }}", { district_id, employee_type_id }, function (data) {
    //                 $('#employee').empty().append('<option value="">Select Employee</option>');
    //                 $.each(data, function (key, employee) {
    //                     $('#employee').append(`<option value="${employee.id}">${employee.name}</option>`);
    //                 });
    //             });
    //         }
    //         if (district_id) {
    //             $.get("{{ route('sales.get-locations') }}", { district_id: district_id }, function (data) {
    //                 $('.select2-multi').each(function () {
    //                     let $select = $(this);
    //                     // $select.empty();
    //                     $select.empty().trigger('change');

    //                     if (data.length > 0) {
    //                         $.each(data, function (index, location) {
    //                             $select.append(`<option value="${location}">${location}</option>`);
    //                         });
    //                     } else {
    //                         $select.append('<option value="">No Locations Available</option>');
    //                     }

    //                     // Trigger change and reapply disabled logic
    //                     $select.trigger('change.select2');
    //                     updateLocationDropdowns();
    //                 });
    //             });
    //         } else {
    //             $('.select2-multi').empty().trigger('change.select2');
    //         }


    //     });

    //     let routeTable = $('#routeTable').DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: "{{ route('sales.route.assigned.list') }}",
    //         columns: [
    //             { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
    //             { data: 'district', name: 'district' },
    //             { data: 'employee_type', name: 'employee_type' },
    //             { data: 'employee', name: 'employee' },
    //             { data: 'route_name', name: 'route_name' },
    //             { data: 'action', name: 'action', orderable: false, searchable: false }
    //         ]
    //     });

    //     $('#openCreateAssignRouteModal').on('click', function () {
    //         $('#assignedRouteForm')[0].reset();
    //         $('.select2').val(null).trigger('change');

    //         $('.select2-multi').each(function () {
    //             $(this).find('option').prop('disabled', false); 
    //             $(this).val([]).trigger('change'); 
    //         });            
    //         updateRouteDropdowns();
    //         updateLocationDropdowns();
    //         $('#createEditAssignRouteModal').modal({
    //             backdrop: 'static', // Prevent clicking outside to close
    //             keyboard: false     // Prevent "Esc" key from closing
    //         }).modal('show');
    //     });

    //     $(document).on('click', '.editRoute', async function () 
    //     {
    //         try {
    //             let routeId = $(this).data('id');
    //             if (routeId) {
    //                 $("#district").prop("disabled", true); 
    //             } else {
    //                 $("#district").prop("disabled", false);
    //             }
    //             let response = await $.ajax({
    //                 url: `/sales/routes/edit/${routeId}`,
    //                 type: 'GET'
    //             });

    //             $('#route_id').val(response.id);
    //             $('#district').val(response.district_id).trigger('change');
    //             $('#employee_type').val(response.employee_type_id).trigger('change');

    //             let employees = await $.ajax({
    //                 url: '/sales/get-employees',
    //                 type: 'GET',
    //                 data: {
    //                     district_id: response.district_id,
    //                     employee_type_id: response.employee_type_id
    //                 }
    //             });

    //             let $employeeSelect = $('#employee').empty().append('<option value="">Select Employee</option>');
    //             employees.forEach(employee => {
    //                 $employeeSelect.append(`<option value="${employee.id}">${employee.name}</option>`);
    //             });
    //             $employeeSelect.val(response.employee_id).trigger('change');

    //             if (response.employee_type_id == 1) {
    //                 $('#asoField').show();
    //                 let asoDropdown = $('#aso');

    //                 let asoList = await $.ajax({
    //                     url: '/sales/get-employees',
    //                     type: 'GET',
    //                     data: { employee_type_id: 2, district_id: response.district_id }
    //                 });

    //                 asoDropdown.empty().append('<option value="">Select ASO</option>');
    //                 asoList.forEach(aso => {
    //                     asoDropdown.append(`<option value="${aso.id}">${aso.name}</option>`);
    //                 });

    //                 asoDropdown.val(response.aso_id).trigger('change');
    //             } else {
    //                 $('#asoField').hide();
    //             }

    //             $('.form-control[name^="routes"]').val('');
    //             $('.select2-multi').empty().trigger('change');

    //             for (let index = 0; index < response.routes.length; index++) {
    //                 let route = response.routes[index];
    //                 let routeSelector = $(`select[name="routes[${index + 1}][route_name]"]`);
    //                 let locationSelector = $(`select[name="routes[${index + 1}][locations][]"]`);

    //                 if (routeSelector.length) {
    //                     routeSelector.val(route.route_name).trigger('change');
    //                 }

    //                 if (locationSelector.length) {
    //                     let locations = await $.ajax({
    //                         url: '/sales/get-locations',
    //                         type: 'GET',
    //                         data: { district_id: response.district_id }
    //                     });

    //                     locationSelector.empty();
    //                     locations.forEach(location => {
    //                         locationSelector.append(`<option value="${location}">${location}</option>`);
    //                     });

    //                     locationSelector.val(route.locations).trigger('change');
    //                 }
    //             }

    //             $('#createEditAssignRouteModal .modal-title').text('Edit Assigned Route');
    //             $('#createEditAssignRouteModal button[type="submit"]').text('Update');
    //             $('#createEditAssignRouteModal').modal({
    //                 backdrop: 'static', // Prevent clicking outside to close
    //                 keyboard: false     // Prevent "Esc" key from closing
    //             }).modal('show');

    //         } catch (error) {
    //             console.error('Error loading route data:', error);
    //         }
    //     });
    //     $.ajaxSetup({
    //         headers: {
    //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //         }
    //     });
    //     $('#assignedRouteForm').on('submit', function (e) {
    //         e.preventDefault();

    //         let isValid = true;

    //         $('select[name^="routes"]').each(function () {
    //             if (!$(this).val()) {
    //                 $(this).attr('required', true); 
    //                 isValid = false;
    //             } else {
    //                 $(this).removeAttr('required');
    //             }
    //         });

    //         $('.select2-multi').each(function () {
    //             if (!$(this).val() || $(this).val().length === 0) {
    //                 $(this).attr('required', true);
    //                 isValid = false;
    //             } else {
    //                 $(this).removeAttr('required'); 
    //             }
    //         });

    //         if (!isValid) {
    //             return false; 
    //         }
    //         let routeId = $('#route_id').val().trim();
            
    //         let formData = new FormData(this);
    //         formData.append('employee_type_id', $('#employee_type').val());

    //         let url, type;

    //         if (routeId) { 
    //             url = "{{ route('sales.route.assigned.update', ':id') }}".replace(':id', routeId);
    //             formData.append('_method', 'PUT'); 
    //             type = "POST"; // Laravel requires _method override for PUT
    //             actionType = "updated";
    //         } else {
    //             url = "{{ route('sales.route.assigned.store') }}";
    //             type = "POST"; // Direct POST request for storing new data
    //             actionType = "created";
    //         }

    //         $.ajax({
    //             url: url,
    //             type: type,
    //             data: formData,
    //             processData: false,
    //             contentType: false, 
                
    //             success: function (response) {
    //                 Swal.fire({
    //                     title: 'Success!',
    //                     text: `Route successfully ${actionType}.`,
    //                     icon: 'success',
    //                     confirmButtonText: 'OK'
    //                 }).then(() => {
    //                     $('#createEditAssignRouteModal').modal('hide');
    //                     routeTable.ajax.reload();
    //                 });
    //             },
    //             error: function (xhr) {
    //                 Swal.fire({
    //                     title: 'Error!',
    //                     text: xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong',
    //                     icon: 'error',
    //                     confirmButtonText: 'OK'
    //                 });
    //             }
    //         });

    //     });

    //     loadDistricts();
    // });
    $(document).ready(function() {

        function initializeSelect2() {
            $('.select2, .select2-multi').select2({
                width: '100%',
                dropdownParent: $('#createEditAssignRouteModal'),
                placeholder: "Select option",
                allowClear: true
            });
        }

        initializeSelect2();

        // Load employees based on type
        $('#employee_type').on('change', function() {
            let employeeTypeId = $(this).val();
            $('#employee').empty().append('<option value="">Select Employee</option>');

            if (employeeTypeId) {
                $.get("{{ route('sales.get-employees-by-type') }}", { employee_type_id: employeeTypeId }, function(data) {
                    $.each(data, function(key, emp) {
                        $('#employee').append(`<option value="${emp.id}">${emp.name}</option>`);
                    });
                });
            }
        });

        // Fetch all locations (all districts)
        function loadAllLocations(callback) {
            $.get("{{ route('sales.get-all-locations') }}", function(data) {
                $('.location-select').each(function() {
                    let $select = $(this);
                    $select.empty();
                    $.each(data, function(index, location) {
                        $select.append(`<option value="${location}">${location}</option>`);
                    });
                    $select.trigger('change.select2');
                });
                if (callback) callback();
            });
        }

        // Load dealers for selected locations
        $(document).on('change', '.location-select', function() {
            let $row = $(this).closest('.route-row');
            let selectedLocations = $(this).val() || [];
            let $dealerSelect = $row.find('.dealer-select');

            $dealerSelect.empty();

            if (selectedLocations.length > 0) {
                $.get("{{ route('sales.get-dealers-by-locations') }}", { locations: selectedLocations }, function(data) {
                    if (data.length > 0) {
                        $.each(data, function(index, dealer) {
                            $dealerSelect.append(`<option value="${dealer.id}">${dealer.dealer_name}</option>`);
                        });
                    } else {
                        $dealerSelect.append('<option value="">No Dealers Found</option>');
                    }
                    $dealerSelect.trigger('change.select2');
                });
            }
        });

        // Prevent duplicate route names (R1–R6)
        $(document).on('change', '.route-select', function() {
            let selectedRoutes = [];
            $('.route-select').each(function() {
                if ($(this).val()) selectedRoutes.push($(this).val());
            });

            $('.route-select').each(function() {
                let currentVal = $(this).val();
                $(this).find('option').each(function() {
                    let optionVal = $(this).val();
                    if (optionVal && selectedRoutes.includes(optionVal) && optionVal !== currentVal) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
            });
        });

        // Submit
        $('#assignedRouteForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let mode = $(this).attr('data-mode');
            let id = $(this).attr('data-id');

            let url, method;
            if (mode === 'edit' && id) {
                url = "{{ url('sales/routes/update') }}/" + id;
                method = 'POST'; // Use POST but send `_method: PUT` for Laravel
                formData.append('_method', 'PUT');
            } else {
                url = "{{ route('sales.route.assigned.store') }}";
                method = 'POST';
            }

            $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire('Success', response.message || 'Operation successful!', 'success');
                    $('#createEditAssignRouteModal').modal('hide');
                    $('#routeTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        });
        let routeTable = $('#routeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('sales.route.assigned.list') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
                { data: 'employee_type', name: 'employee_type' },
                { data: 'employee', name: 'employee' },
                { data: 'route_name', name: 'route_name' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
        $('#openCreateAssignRouteModal').on('click', function() {
            $('#assignedRouteForm')[0].reset();
            $('.select2, .select2-multi').val(null).trigger('change');
            loadAllLocations();
            $('#createEditAssignRouteModal').modal({
                backdrop: 'static',
                keyboard: false
            }).modal('show');
        });
        // Handle Edit button click
            // Handle Edit button click
        $(document).on('click', '.editRoute', function () {
            let id = $(this).data('id');
            $('#assignedRouteForm')[0].reset();
            $('.select2, .select2-multi').val(null).trigger('change');

            // Always reload all location lists before loading edit data
            loadAllLocations(function () {
                $.get("{{ url('sales/routes/edit') }}/" + id, function (response) {
                    if (response.success) {
                        let data = response.data;

                        $('#route_id').val(data.id);
                        $('#employee_type').val(data.employee_type_id).trigger('change');

                        // Wait until employees list finishes loading before setting the employee
                        setTimeout(() => {
                            $('#employee').val(data.employee_id).trigger('change');
                        }, 600);

                        // Wait another small delay to ensure all selects are ready
                        setTimeout(() => {
                            data.routes.forEach((route, index) => {
                                let $row = $('.route-row').eq(index);

                                // Set route name
                                $row.find('.route-select').val(route.route_name).trigger('change');

                                // Set locations
                                let $locationSelect = $row.find('.location-select');
                                $locationSelect.val(route.locations).trigger('change');

                                // Load dealers for selected locations, then assign dealer values
                                if (route.locations && route.locations.length > 0) {
                                    $.get("{{ route('sales.get-dealers-by-locations') }}", { locations: route.locations }, function (dealers) {
                                        let $dealerSelect = $row.find('.dealer-select');
                                        $dealerSelect.empty();
                                        $.each(dealers, function (i, dealer) {
                                            $dealerSelect.append(`<option value="${dealer.id}">${dealer.dealer_name}</option>`);
                                        });
                                        $dealerSelect.val(route.dealers).trigger('change');
                                    });
                                }
                            });
                        }, 1000);

                        $('#createEditAssignRouteModal .modal-title').text('Edit Assigned Route');
                        $('#assignedRouteForm').attr('data-mode', 'edit');
                        $('#assignedRouteForm').attr('data-id', data.id);

                        $('#createEditAssignRouteModal').modal({
                            backdrop: 'static',
                            keyboard: false
                        }).modal('show');
                    } else {
                        Swal.fire('Error', 'Failed to load route data', 'error');
                    }
                });
            });
        });
        $(document).on('click', '.deleteRoute', function () {
            var id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this assigned route?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: '/sales/routes/delete/' + id,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            $('#assignedRoutesTable').DataTable().ajax.reload(null, false);
                            // remove full reload if not needed
                            // location.reload();
                        },
                        error: function (xhr) {
                            console.log(xhr.responseText);

                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong while deleting!'
                            });
                        }
                    });

                }
            });
        });




    });
</script>

@endsection
