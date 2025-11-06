@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Route Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateRouteModal">
            Create
        </button>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="routeTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>District</th>
                    <th>Locations</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@include('sales.route.route-modal-create-edit')

@endsection
@section('scripts')
<script>
    $(document).ready(function () {
        let locationInput;

        $('#createEditRouteModal').on('shown.bs.modal', function () {
            if (!locationInput) {
                locationInput = new Choices('#locations', {
                    delimiter: ',',
                    editItems: true,
                    removeItemButton: true,
                    paste: false,
                    duplicateItemsAllowed: false,
                    placeholderValue: 'Enter locations',
                    searchPlaceholderValue: 'Search location'
                });
            }
        });

        $('#createEditRouteModal').on('hidden.bs.modal', function () {
            $('#routeForm')[0].reset();
            $('#route_id').val('');
            $('.submit-btn').text('Create');
            $('#createEditRouteModalLabel').text('Create Route');

            if (locationInput) {
                locationInput.clearStore();
                locationInput.clearInput();
            }
        });

        let routeTable = $('#routeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("sales.route.type.list") }}',
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'district_name', name: 'district.name' },
                { data: 'locations', name: 'locations' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#openCreateRouteModal').click(function () {
            $('#createEditRouteModal').modal({
                backdrop: 'static', // Prevent clicking outside to close
                keyboard: false     // Prevent "Esc" key from closing
            }).modal('show');
        });

        $('#routeForm').on('submit', function (e) {
            e.preventDefault();

            let route_id = $('#route_id').val();
            let url = route_id
                ? `{{ route('sales.route.type.update', ':id') }}`.replace(':id', route_id)
                : "{{ route('sales.route.type.store') }}";

            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            if (route_id) formData.append('_method', 'PUT');
            formData.append('district', $('#district').val());

            let locations = locationInput.getValue().map(loc => loc.value);
            locations.forEach(location => formData.append('locations[]', location));

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire('Success', response.message, 'success');
                    routeTable.ajax.reload();
                    $('#createEditRouteModal').modal('hide');
                    $('#routeForm')[0].reset();
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
                            title: 'Validation Error',
                            html: errorMessages
                        });

                    } else if (xhr.status === 400 || xhr.status === 409) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: xhr.responseJSON.message
                        });

                    } else {
                        Swal.fire('Error', 'Could not create route.', 'error');
                    }
                }
            });
        });

        $(document).on('click', '.editRoute', function () {
            let route_id = $(this).data('id');

            $.ajax({
                url: `{{ route('sales.route.type.edit', ':id') }}`.replace(':id', route_id),
                type: 'GET',
                success: function (response) {
                    const route = response.route;

                    $('#route_id').val(route.id);
                    $('#district').val(route.district_id);

                    if (!locationInput) {
                        locationInput = new Choices('#locations', {
                            delimiter: ',',
                            editItems: true,
                            removeItemButton: true,
                            paste: false,
                            duplicateItemsAllowed: false,
                            placeholderValue: 'Enter locations',
                            searchPlaceholderValue: 'Search location'
                        });
                    }

                    // Clear & Set Locations
                    locationInput.clearStore();
                    locationInput.clearInput();
                    setTimeout(() => {
                        route.locations.forEach(loc => locationInput.setValue([loc]));
                    }, 100);

                    $('#createEditRouteModalLabel').text('Edit Route');
                    $('.submit-btn').text('Update');
                    $('#createEditRouteModal').modal({
                        backdrop: 'static', // Prevent clicking outside to close
                        keyboard: false     // Prevent "Esc" key from closing
                    }).modal('show');
                },
                error: function (xhr) {
                    Swal.fire('Error', 'Could not fetch route details.', 'error');
                }
            });
        });

        $(document).on('click', '.deleteRoute', function () {
            const route_id = $(this).data('id');

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
                    let deleteUrl = "{{ route('sales.route.type.delete', ':id') }}".replace(':id', route_id);

                    $.ajax({
                        url: deleteUrl,
                        method: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                $('#routeTable').DataTable().ajax.reload(); // Reload DataTable
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            if (xhr.status === 404) {
                                Swal.fire('Error', 'Route not found!', 'error');
                            } else {
                                Swal.fire('Error', 'Could not delete route.', 'error');
                            }
                        }
                    });
                }
            });
        });

    });

</script>
@endsection
