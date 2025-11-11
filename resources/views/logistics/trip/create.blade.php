<meta name="csrf-token" content="{{ csrf_token() }}">



<div class="container-fluid">
    <div class="row">
        <form id="createTripForm">
        {{-- STEP 1: Trips Table + Assign Panel --}}
        <div id="assignStep1" class="w-100">
            <div class="row">
                <!-- LEFT: Trips Table -->
                <div class="row">
                    <h4>Assign Vehicle & Driver</h4>
                      <div class="assign-vehicle-cover col-xl-4">
                        {{-- <p>
                          Choose the required vehicle type to check its capacity.
                        </p> --}}
                        <label>Vehicle Category <sup>*</sup></label>
                        <select class="form-control mb-3" id="vehicleCategory">
                        <option value="">Select Vehicle Category<sup>*</sup></option>
                        </select>

                        <label>Vehicle Type <sup>*</sup></label>
                        <select class="form-control mb-3" id="vehicleType">
                        <option value="">Select Vehicle Type<sup>*</sup></option>
                        </select>

                        <label>Vehicle Registration Number <sup>*</sup></label>
                        <select class="form-control mb-3" id="vehicleNo" name="vehicle_id">
                            <option value="">Vehicle Registration Number <sup>*</sup></option>
                        </select>
                      </div>
                        <!-- Vehicle summary below selects -->
                    <div class=" col-xl-4">
                        <div class="vehicle-summary" style="background-color: #F5F5F5 !important">
                            <table class="table table-sm w-100">
                                <tr>
                                    <td>Vehicle Type</td>
                                    <td id="summaryVehicleType">-</td>
                                </tr>
                                <tr>
                                    <td>Vehicle Capacity</td>
                                    <td id="summaryVehicleCapacity">-</td>
                                </tr>
                                <tr>
                                    <td>Selected Orders Quantity</td>
                                    <td id="summarySelectedQty">0</td>
                                </tr>
                                <tr>
                                    <td>Balance Quantity</td>
                                    <td id="summaryBalanceQty">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                      <div class="assign-vehicle-cover col-xl-4">
                        {{-- <p>
                          Choose the driver who depends on the vehicle category.
                        </p> --}}
                        <label>Driver Name <sup>*</sup></label>
                        <select type="text" class="form-select mb-3 select2" placeholder="Enter Driver Name" id="DriverName" name="driver_id"></select>
                        <label class="mt-3">Phone <sup>*</sup></label>
                        <input type="readonly" class="form-control mb-3" placeholder="Enter Phone"  id="DriverPhoneNumber">
                        <label>Salary Type <sup>*</sup></label>
                        <select class="form-control mb-3">
                          <option>Select Salary Type</option>
                          <option value="Bata">Bata</option>
                          <option value="Daily Wages">Daily Wages</option>
                        </select>
                      </div>
                </div>
                <div class="col-xl-12">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <h6 class="mb-1 mt-2">Select Trips</h6>
                        </div>
                    </div>

                    <div style="max-height:520px; overflow:auto;">
                        <table class="table table-sm table-bordered w-100"  style="font-size:12px;" id="assignTripsTable">
                            <thead>
                                <tr>
                                    <th width="20"></th>
                                    <th width="50">Order ID</th>
                                    <th width="70">Date</th>
                                    <th width="150">Dealer Name</th>
                                    <th width="80">District</th>
                                    <th>Address</th>
                                    <th width="80">Product</th>
                                    <th width="40">Qty</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: Assign Panel -->
                
            </div>

            <!-- Footer Buttons -->
            <div class="mt-3 d-flex justify-content-start">
                <button type="button" class="btn btn-primary mr-2" id="continueAssignBtn">Continue</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>

        {{-- STEP 2 --}}
        <div id="assignStep2" class="w-100" style="display:none;">
            <div class="row">                  
                    <div class="row">
                      <div class="col-md-4">                    
                        <div class="listing-sec">
                          <div class="detail-cover">
                            <h4>Vehicle Information</h4>
                            <table class="table table-responsive">
                              <tr>
                                <td>Vehicle Category</td>
                                <td class="previewVehicleCategory"></td>
                              </tr>
                              <tr>
                                <td>Vehicle Type</td>
                                <td class="previewVehicleType"></td>
                              </tr>
                              <tr>
                                <td>Vehicle Number</td>
                                <td class="previewVehicleNo"></td>
                              </tr>
                              <tr>
                                <td>Vehicle Capacity</td>
                                <td class="previewVehicleCapacity"></td>
                              </tr>
                              <tr>
                                <td>Added Quantity</td>
                                <td class="previewAddedQuantity"></td>
                              </tr>
                              <tr>
                                <td>Balance Quantity</td>
                                <td class="previewBalanceQuantity"></td>
                              </tr>
                            </table>
                          </div>
                          <div class="detail-cover">
                            <h4>Driver Information</h4>
                            <table class="table table-responsive">
                              <tr>
                                <td>Driver Name</td>
                                <td  class="previewDriverName"></td>
                              </tr>
                              <tr>
                                <td>Driver Phone</td>
                                <td class="previewDriverNo"></td>
                              </tr>
                              <tr>
                                <td>Salary Type</td>
                                <td class="previewSalaryType"></td>
                              </tr>
                            </table>
                          </div>
                          
                          
                        </div>
                      </div>
                      <div class="col-md-8">
                        <h4>Selected Sales Orders</h4>
                        <p>Selected sales orders are listed here. You can choose the delivery points.
                        </p>
                        <table class="table table-bordered table-responsive table-striped w-100 bg-white secondTable" style="font-size: 12px;">
                            <thead>
                              <th>Sl.No</th>
                              <th>OrderID</th>
                              <th width="80">Date</th>
                              <th width="80">Dealer</th>
                              <th>District</th>
                              <th>Address</th>
                              <th>Product</th>
                              <th>Qty</th>
                              <th width="70">Sort Order</th>
                            </thead>
                            <tbody>
                              
                            </tbody>
                          </table>
                      </div>
                    </div>
                    <hr>
                    <h4>Add Other Details</h4>
                    <p>
                      Provide additional delivery information such as delivery date, origin and destination locations, estimated distance, and total quantity.
                    </p>
                    <div class="detail-cover mb-3">
                      <div class="row">
                        <div class="col-md-4">
                          <label>Delivery Date <sup>*</sup></label>
                          <input type="date" class="form-control" placeholder="DD/MM/YYYY" name="delivery_date">
                        </div>
                        <div class="col-md-4">
                          <label>From Location <sup>*</sup></label>
                          <input type="text" class="form-control" placeholder="Enter From Location" name="from_location">
                        </div>
                        <div class="col-md-4">
                          <label>To Location <sup>*</sup></label>
                          <input type="text" class="form-control" placeholder="Enter the Last Delivery Point Location" name="to_location">
                        </div>
                        <div class="col-md-4">
                          <label>Approximate KM <sup>*</sup></label>
                          <input type="number" class="form-control" placeholder="Enter Approximate Total Kilometers" name="approx_km">
                        </div>
                        <div class="col-md-4">
                          <label>Total Quantity <sup>*</sup></label>
                          <input type="readonly" id="totalQuantity" class="form-control" placeholder="Enter Total Quantity" name="total_quantity">
                        </div>
                      </div>
                    </div>
                    <div id="pickup-container">
                        <!-- Existing pickup points can be here -->
                    </div>
                    <div class="mt-3">
                      <a href="#" class="btn btn-primary" id="finalizeAssignBtn">Submit</a>
                      <a href="#" class="btn btn-secondary" id="add-pickup" onclick="addPickupPoint();">Add Pick-up</a>
                      <a href="#" class="btn btn-secondary btn-secondary-cancel">Cancel</a>
                    </div>
            </div>
        </div>
        </form>
    </div>
</div>

<div id="loader" style="display:none;">
    <div class="spinner"></div>
</div>

<script>
    function toggleContinueButton() {
        // Check if at least one checkbox is selected
        let atLeastOneChecked = $('#assignTripsTable tbody input[type="checkbox"]:checked').length > 0;

        // Check if required fields are filled
        let vehicleCategory = $('#vehicleCategory').val();
        let vehicleType = $('#vehicleType').val();
        let vehicleNo = $('#vehicleNo').val();
        let driverName = $('#DriverName').val();

        // Enable button only if all conditions met
        if(atLeastOneChecked && vehicleCategory && vehicleType && vehicleNo && driverName) {
            $('#continueAssignBtn').prop('disabled', false);
        } else {
            $('#continueAssignBtn').prop('disabled', true);
        }
    }
$(document).ready(function() {
    // Listen for checkbox changes in the trips table
    $('#assignTripsTable').on('change', 'input[type="checkbox"]', toggleContinueButton);
    $('#vehicleCategory, #vehicleType, #vehicleNo, #DriverName, #DriverPhoneNumber').on('change', toggleContinueButton);



     // Continue button → go to Step 2
    $('#continueAssignBtn').on('click', function() {
        // 1️⃣ Fill Vehicle Info
        $('.previewVehicleCategory').text($('#vehicleCategory option:selected').text() || '-');
        $('.previewVehicleType').text($('#vehicleType option:selected').text() || '-');
        $('.previewVehicleNo').text($('#vehicleNo  option:selected').text() || '-');
        $('.previewVehicleCapacity').text($('#vehicleNo').find(':selected').data('capacity') || '-'); // optional if you have capacity data
        let totalQty = 0;
        $('#assignTripsTable tbody input[type="checkbox"]:checked').each(function() {
            let qty = parseFloat($(this).closest('tr').find('td:nth-child(8)').text()) || 0;
            totalQty += qty;
        });
        $('.previewAddedQuantity').text(totalQty + ' Ton');
        $('#totalQuantity').val(totalQty);
        let vehicleCapacity = parseFloat($('#vehicleNo').find(':selected').data('capacity')) || 0;
        $('.previewBalanceQuantity').text((vehicleCapacity - totalQty) + ' Ton');

        // 2️⃣ Fill Driver Info
        $('.previewDriverName').text($('#DriverName').select2('data')[0]?.text || '-');
        $('.previewDriverNo').text($('#DriverPhoneNumber').val() || '-');
        $('.previewSalaryType').text($('#DriverName').data('salary') || 'Bata'); // or get from your input if needed

        // 3️⃣ Fill Selected Sales Orders
        let tbody = '';
        $('#assignTripsTable tbody input[type="checkbox"]:checked').each(function(index) {
            let tr = $(this).closest('tr');
            let orderId = tr.find('td:nth-child(2)').text();
            let date = tr.find('td:nth-child(3)').text();
            let dealer = tr.find('td:nth-child(4)').text();
            let district = tr.find('td:nth-child(5)').text();
            let address = tr.find('td:nth-child(6)').text();
            let product = tr.find('td:nth-child(7)').text();
            let qty = tr.find('td:nth-child(8)').text();
            
            // Generate options dynamically
            let options = `<option value="">Select</option>`;
            for (let i = 1; i <= $('#assignTripsTable tbody input[type="checkbox"]:checked').length; i++) {
                // Pre-select option equal to index+1
                let selected = (i === index + 1) ? 'selected' : '';
                options += `<option value="${i}" ${selected}>${i}</option>`;
            }

            tbody += `<tr>
                <td>${index + 1}</td>
                <td>${orderId}</td>
                <td>${date}</td>
                <td>${dealer}</td>
                <td>${district}</td>
                <td>${address}</td>
                <td>${product}</td>
                <td>${qty}</td>
                <td>
                    <select class="sortOrderSelect">
                        ${options}
                    </select>
                </td>
            </tr>`;
        });
        $('.secondTable tbody').html(tbody); // make sure your Step2 table has <tbody> with id

        // 4️⃣ Show Step 2, hide Step 1
        $('#assignStep1').hide();
        $('#assignStep2').show();
        updateSortOrderOptions();
    });
    $('#assignStep2 .btn-secondary-cancel').on('click', function(e) {
        e.preventDefault(); // prevent default link behavior
        $('#assignStep2').hide();   // hide Step 2
        $('#assignStep1').show();   // show Step 1
    });

    
});
function updateSortOrderOptions() {
    // Initialize oldVal for each select first
    $('.sortOrderSelect').each(function () {
        $(this).data('oldVal', $(this).val());
    });

    // Bind change event
    $('.sortOrderSelect').off('change').on('change', function () {
        
        let changedSelect = $(this);
        let newVal = changedSelect.val();
        let oldVal = changedSelect.data('oldVal');

        // Loop through other selects
        $('.sortOrderSelect').not(changedSelect).each(function () {
            let otherSelect = $(this);
            if (otherSelect.val() === newVal) {
                // Swap values
                otherSelect.val(oldVal);
                otherSelect.data('oldVal', oldVal);
            }
        });

        // Update oldVal for changed select
        changedSelect.data('oldVal', newVal);
    });
}


updateSortOrderOptions();


$(document).on('change', '.sortOrderSelect', function() {
    updateSortOrderOptions();
});


let pickupIndex = 0;

function updateIndexes() {
    const pickups = document.querySelectorAll('#pickup-container .pickup-point');
    pickups.forEach((pickup, i) => {
        pickup.querySelector('.pickup-title').textContent = 'Pickup Point ' + (i + 1);
    });
    pickupIndex = pickups.length;
}

function createPickupPoint() {
    pickupIndex++;

    const div = document.createElement('div');
    div.className = 'pickup-point row';
    div.style.cssText = 'margin-bottom:15px;padding:15px; border-radius:8px; position:relative;';

    // Section title
    const h4 = document.createElement('h4');
    h4.className = 'pickup-title';
    h4.textContent = 'Pickup Point ' + pickupIndex;
    div.appendChild(h4);

    // Fields template
    const fields = [
        { label: 'Pickup Date *', type: 'date', placeholder: 'DD/MM/YYYY', name: 'pickup_points[][pickup_date]', class: 'pickup-date form-control' },
        { label: 'Pickup Point *', type: 'text', placeholder: 'Enter pickup point', name: 'pickup_points[][pickup_point]',class: 'pickup-point form-control' },
        { label: 'Address *', type: 'text', placeholder: 'Enter Full Address', name: 'pickup_points[][address]', class: 'pickup-address form-control' },
        { label: 'Office Phone', type: 'number', placeholder: 'Eg: 9201 001 001', name: 'pickup_points[][office_phone]', class: 'pickup-office-phone form-control' },
        { label: 'Contact Person Name', type: 'text', placeholder: 'Enter Contact Person Name',name: 'pickup_points[][contact_person_name]', class: 'pickup-contact-person form-control' },
        { label: 'Phone', type: 'number', placeholder: 'Eg: 9201 001 001',name: 'pickup_points[][contact_person_phone]', class: 'pickup-phone form-control' },
        { label: 'Attach Driver Photo', type: 'file',name: 'pickup_points[][attachment]', class: 'pickup-attachment form-control' }
    ];

    fields.forEach(f => {
        const fieldDiv = document.createElement('div');
        fieldDiv.style.marginBottom = '8px';
        fieldDiv.className = 'col-lg-4';
        const label = document.createElement('label');
        label.textContent = f.label;
        label.style.display = 'block';
        label.style.fontWeight = '600';
        label.style.marginBottom = '4px';

        const input = document.createElement('input');
        input.type = f.type;
        if (f.placeholder) input.placeholder = f.placeholder;
        if (f.class) input.className = f.class;
        input.style.width = '100%';
        input.style.padding = '6px';
        input.style.border = '1px solid #ccc';
        input.style.borderRadius = '4px';

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);

        div.appendChild(fieldDiv);
    });

    // Remove button
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.textContent = 'Remove';
    removeBtn.style.cssText = 'position:absolute; top:10px; right:30px; background:#e74c3c; color:#fff; border:none; padding:5px 10px;width:120px; border-radius:5px; cursor:pointer;';
    removeBtn.onclick = function() {
        div.remove();
        updateIndexes();
    };

    div.appendChild(removeBtn);

    return div;
}

function addPickupPoint() {
    const container = document.getElementById('pickup-container');
    container.appendChild(createPickupPoint());
}

</script>
