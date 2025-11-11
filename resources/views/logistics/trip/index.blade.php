@extends('layouts.app')

@section('content')
<style>
.selected-row td{
    background-color: #ffc0cb !important; /* Rose color */
}
/* Center the loader on screen */
#loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* The spinning circle */
.spinner {
    border: 6px solid #f3f3f3; /* Light gray */
    border-top: 6px solid #3498db; /* Blue */
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

</style>
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Trip Management</h3>
        <button type="button" class="btn btn-primary" id="openCreateTripModal">
            Create Trip
        </button>
    </div>

    <!-- Filters -->
    <div class="filter-sec target-filter">
        <div class="row mb-2">
            <div class="col-md-2">
                <label>Date</label>
                <input type="date" id="searchDate" class="form-control" placeholder="DD/MM/YYYY">
            </div>
            <div class="col-md-2">
                <label>Driver Name</label>
                <input type="text" id="searchDriverName" class="form-control" placeholder="Search Driver Name">
            </div>
            <div class="col-md-2">
                <label>Vehicle Number</label>
                <input type="text" id="searchVehicleNumber" class="form-control" placeholder="Search Vehicle No">
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="tripsTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Trip ID</th>
                    <th>Delivery Date</th>
                    <th>To Location</th>
                    <th>Quantity</th>
                    <th>Pickup</th>
                    <th>Updates</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Create Trip Modal -->
<div class="modal fade" id="createTripModal" tabindex="-1" role="dialog" aria-labelledby="createTripModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createTripModalLabel">Create Trip</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="createTripModalBody">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i> Loading...
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<style>/* Fix Select2 input height */
.select2-container .select2-selection--single {
    height: 38px !important; /* Match your input height */
    border: 1px solid #ced4da;
    border-radius: 6px;
    display: flex;
    align-items: center;
}

/* Fix text alignment */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important; /* Should be height - 2px */
    padding-left: 10px;
}

/* Fix arrow position */
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    top: 1px;
    right: 6px;
}
.vehicle-summary td{
    background-color: #f5f5f5;
}
</style>

<script>
$(document).ready(function() {
    // Main Trips Table
    let table = $('#tripsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("logistics.trip.getData") }}',
            data: function (d) {
                d.date = $('#searchDate').val();
                d.driver = $('#searchDriverName').val();
                d.vehicle_no = $('#searchVehicleNumber').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'id', name: 'id' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'to_location', name: 'to_location' },
            { data: 'total_quantity', name: 'total_quantity' },
            { data: 'pickup_point_flag', name: 'pickup_point_flag' },
            { data: 'updates', name: 'updates' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action' }
        ]
    });

    // Redraw table on filter change
    $('#searchDate, #searchDriverName, #searchVehicleNumber').on('change keyup', function () {
        table.draw();
    });

    // Open Create Modal and Load Blade
    

    // Delegated handlers for dynamically loaded modal content

   

    
    // Back button
    $(document).on('click', '#backToStep1', function() {
        $('#assignStep2').hide();
        $('#assignStep1').show();
    });

    $(document).on('click', '#finalizeAssignBtn', function(e) {
        e.preventDefault();

        let form = $('#createTripForm'); // make sure create.php is wrapped in <form id="createTripForm">
        let formData = form.serializeArray();
        let payload = {};

        // 🔹 Convert serializeArray (inputs, selects, etc.) into object
        $.each(formData, function() {
            if (payload[this.name]) {
                if (!Array.isArray(payload[this.name])) {
                    payload[this.name] = [payload[this.name]];
                }
                payload[this.name].push(this.value);
            } else {
                payload[this.name] = this.value;
            }
        });

        // 🔹 Collect selected orders (checkboxes with class .orderSelectRow)
        
        let selectedOrders = [];

        $('.secondTable tbody tr').each(function() {
            let $tr = $(this);

            // Get OrderID (2nd td)
            let orderId = $tr.find('td:nth-child(2)').text().trim();

            // Get selected sort order from the <select>
            let sortOrder = $tr.find('select.sortOrderSelect').val();

            selectedOrders.push({
                order_id: orderId,
                sort_order: sortOrder
            });
        });

        console.log(selectedOrders);

        console.log(selectedOrders);
        payload['orders'] = selectedOrders;
        console.log(selectedOrders);
        let pickupPoints = [];
        $('#pickup-container .pickup-point').each(function() {
            let $p = $(this);
            pickupPoints.push({
                pickup_date: $p.find('input.pickup-date').val(),
                pickup_point: $p.find('input.pickup-point').val(),
                address: $p.find('input.pickup-address').val(),
                office_phone: $p.find('input.pickup-office-phone').val(),
                contact_person_name: $p.find('input.pickup-contact-person').val(),
                contact_person_phone: $p.find('input.pickup-phone').val(),
                attachment: $p.find('input[type="file"]').val() // ⚠️ file uploads need FormData
            });
        });
        payload['pickup_points'] = pickupPoints;
        console.log(payload);
        // 🔹 Send AJAX request
        $.ajax({
            url: "{{ route('logistics.trip.storeTrip') }}",
            type: "POST",
            data: {
                ...payload,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Trip Created',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#createTripModal').modal('hide');
                    $('#tripsTable').DataTable().ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: res.message ?? 'Something went wrong'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to create trip'
                });
                console.error(xhr.responseText);
            }
        });
    });

    // Reset modal when closed
    $('#createTripModal').on('hidden.bs.modal', function() {
        $('#assignStep2').hide();
        $('#assignStep1').show();
    });

    $('#openCreateTripModal').on('click', function() {
        $('#createTripModal').modal('show');
        $('#createTripModalBody').load('{{ route("logistics.trip.create") }}', function() {
            // $('#continueAssignBtn').prop('disabled', true);
            // This function runs after content is loaded
            initializeCreateModalJS();
        
        });
    });
})
</script>


<script>
function initializeCreateModalJS() {
    let selectedOrders = {}; 

    // Initialize DataTable
    let table = $('#assignTripsTable').DataTable({
        paging: true,
        searching: true,
        pageLength: 10,
        ordering: false
    });

    // Load pending trips
    function loadPendingTrips() {
        $("#loader").show();
        $.get('{{ route("logistics.trip.pendingOrders") }}', function(orders) {
            table.clear();
            $.each(orders, function(i, order) {
                let deliveryDate = '';
                if(order.delivery_date){
                    let d = new Date(order.delivery_date);
                    deliveryDate = ('0' + d.getDate()).slice(-2) + '-' +
                                   ('0' + (d.getMonth()+1)).slice(-2) + '-' +
                                   d.getFullYear();
                }

                let dealerName = order.dealer ? order.dealer.dealer_name : '';
                let district = order.dealer ? order.dealer.district : '';
                let address = order.dealer ? order.dealer.address : '';

                let productNames = order.order_items ? order.order_items.map(item => item.product_name || item.product?.product_name).join(', ') : '';
                let quantity = order.order_items ? order.order_items.reduce((sum, item) => sum + (parseFloat(item.total_quantity) || 0), 0) : '';

                table.row.add([
                    `<input type="checkbox" class="tripCheckbox" value="${order.id}" data-quantity="${quantity}" name="orders[]">`,
                    order.id,
                    deliveryDate,
                    dealerName,
                    district,
                    address,
                    productNames,
                    quantity
                ]);
            });
            table.draw();
        }).always(function() { $("#loader").hide(); });
    }

    loadPendingTrips();
    $('#vehicleNo').select2({
        placeholder: "Search Vehicle",
        ajax: {
            url: '{{ route("logistics.trip.vehicle.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // 🔹 search term typed
                    vehicle_type_id: $('#vehicleType').val() // filter by type
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (v) {
                        return {
                            id: v.id,
                            text: v.vehicle_no + " (Capacity: " + v.load_capacity + ")",
                            capacity: v.load_capacity
                        };
                    })
                };
            },
            cache: true
        },
        dropdownParent: $('#createTripModal')
    });

    $('#DriverName').select2({
        placeholder: "Search Driver",
        ajax: {
            url: '{{ route("logistics.trip.drivers") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // 🔹 search term typed
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (d) {
                        return {
                            id: d.id,
                            text: d.name + " (" + d.phone + ")",
                            phone: d.phone
                        };
                    })
                };
            },
            cache: true
        },
        dropdownParent: $('#createTripModal')
    });

    // When driver is selected, fill phone field
    $('#DriverName').on('select2:select', function (e) {
        let phone = e.params.data.phone || '';
        $('#DriverPhoneNumber')
            .val(phone)
            .prop('readonly', true); // 🔹 make it readonly
    });
    // Checkbox change & row highlight
    $('#assignTripsTable tbody').on('change', '.tripCheckbox', function() {
        let row = $(this).closest('tr');
        let orderId = $(this).val();
        let qty = parseFloat($(this).data('quantity')) || 0;

        if (this.checked) {
            let currentTotal = Object.values(selectedOrders).reduce((a,b)=>a+b,0);
            let newTotal = currentTotal + qty;
            let vehicleCapacity = parseFloat($('#vehicleNo option:selected').data('capacity')) || 0;

            if(vehicleCapacity > 0 && newTotal > vehicleCapacity){
                Swal.fire({
                    icon: 'warning',
                    title: 'Capacity Exceeded',
                    text: 'Selected orders exceed vehicle capacity!',
                    confirmButtonText: 'OK'
                });
                $(this).prop('checked', false);
                return;
            }

            row.addClass('selected-row');
            selectedOrders[orderId] = qty;
        } else {
            row.removeClass('selected-row');
            delete selectedOrders[orderId];
        }

        updateSelectedSummary();
    });

    function updateSelectedSummary() {
        let totalSelectedQty = Object.values(selectedOrders).reduce((a,b)=>a+b,0).toFixed(2);
        $('#summarySelectedQty').text(totalSelectedQty);
        updateBalanceQty();
    }

        $.get('{{ route("logistics.trip.vehicle.categories") }}', function (categories) { $.each(categories, function (i, cat) { $('#vehicleCategory').append('<option value="'+cat.id+'">'+cat.vehicle_category_name+'</option>'); }); });
    
    $('#vehicleCategory').on('change', function(){
        let categoryId = $(this).val();
        $('#vehicleType').empty().append('<option value="">Select Type</option>');
        $('#vehicleNo').empty().append('<option value="">Vehicle Registration Number</option>');
        resetSelections();

        if(categoryId){
            $.get('{{ url("logistics/trip/vehicle/types") }}/'+categoryId, function(types){
                $.each(types,function(i,type){
                    $('#vehicleType').append('<option value="'+type.id+'">'+type.vehicle_type_name+'</option>');
                });
            });
        }
    });

    $('#vehicleType').on('change', function(){
        let typeId = $(this).val();
        $('#vehicleNo').empty().append('<option value="">Vehicle Registration Number</option>');
        $('#summaryVehicleType').text($('#vehicleType option:selected').text());
        resetSelections();

        if(typeId){
            $.get('{{ route("logistics.trip.vehicle.search") }}', {vehicle_type_id:typeId}, function(vehicles){
                $.each(vehicles, function(i,v){
                    $('#vehicleNo').append('<option value="'+v.id+'" data-capacity="'+v.load_capacity+'">'+v.vehicle_no+'</option>');
                });
                $('#vehicleNo').trigger('change.select2');
            });
        }
        updateBalanceQty();
    });

    // VehicleNo change → update summary
    $("#vehicleNo").on("change", function(){
        $('#summaryVehicleCapacity').text($('#vehicleNo option:selected').data('capacity') || '-');
        resetSelections();
        updateBalanceQty();
    });

    function resetSelections(){
        $('#assignTripsTable tbody .tripCheckbox').prop('checked', false);
        $('#assignTripsTable tbody tr').removeClass('selected-row').css('background-color','');
        selectedOrders = {};
        updateSelectedSummary();
    }

    function updateBalanceQty(){
        let vehicleCapacity = parseFloat($('#vehicleNo option:selected').data('capacity')) || 0;
        let selectedQty = parseFloat($('#summarySelectedQty').text()) || 0;
        if(vehicleCapacity > 0){
            let balance = vehicleCapacity - selectedQty;
            $('#summaryBalanceQty').text(balance>=0 ? balance.toFixed(2) : '0');
        }
    }

    
}


</script>
@endsection
