@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Header -->
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.bata.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Add Bata</h4>
    </div>

    <!-- Card -->
    <div class="card shadow-lg p-4">
        <!-- Bata Details -->
        <h5 class="mb-3 fw-semibold">Bata Details</h5>
        <form id="bataForm" action="{{ route('logistics.bata.store') }}" method="POST">
            @csrf
            <!-- Hidden Inputs for JSON -->
            <input type="hidden" name="payments_json" id="payments_json">
            <input type="hidden" name="deduction_json" id="deduction_json">
            <input type="hidden" name="expenses_json" id="expenses_json">

            <div class="row g-3 position-relative">
                <div class="col-md-6">
                    <label class="form-label">Choose Driver</label>
                    <input type="text" name="driver_name" id="driver_name" class="form-control" placeholder="Search Driver’s Name" autocomplete="off">
                    <!-- Driver list dropdown -->
                    <ul id="driverList" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Auto-filled based on selected driver" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="text" name="from_date" class="form-control" placeholder="DD/MM/YYYY" onfocus="(this.type='date')">
                </div>

                <div class="col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="text" name="to_date" class="form-control" placeholder="DD/MM/YYYY" onfocus="(this.type='date')">
                </div>
            </div>

            <!-- Trip History Overview -->
            <div class="mt-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="fw-semibold mb-1">Trip History Overview</h5>
                        <p class="text-muted small mb-1">
                            The table below displays each trip’s key details, helping you track and review travel activity efficiently.
                        </p>
                        
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger px-4" id="setPaymentBtn">Set Payment</button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-responsive align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="select_all"></th>
                                <th>Sl No.</th>
                                <th>Trip ID</th>
                                <th>Delivery Date</th>
                                <th>From Location</th>
                                <th>To Location</th>
                                <th>Total Quantity</th>
                                <th>Vehicle Types</th>
                                <th>Vehicle Number</th>
                                <th>Total Kilometers</th>
                                <th>Pickup</th>
                                <th>Salary Type</th>
                            </tr>
                        </thead>
                        <tbody id="tripTableBody">
                            <tr>
                                <td colspan="12">No trips found. Please select a driver and date range.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                <h5 class="fw-semibold mb-3">Trips Chosen for Settlement</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-responsive align-middle text-center" id="paymentListTable">
                        <thead class="table-light">
                            <tr>
                                <th>Selected Trips</th>
                                <th>Salary Type</th>
                                <th>Total Kilometer</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4">No trips selected yet.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Amount</th>
                                <th id="grandTotal">₹ 0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- Trip Expenses Section -->
            <div class="mt-5">
                <h5 class="fw-semibold mb-3">Other Expenses</h5>
                <p class="text-muted small">Listed below are the other expenses associated with the selected trips.</p>

                <div class="table-responsive">
                    <table class="table table-bordered table-responsive align-middle text-center" id="otherExpensesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Trip ID</th>
                                <th>Expense Type</th>
                                <th>Attachment</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4">No trips selected yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Deduction Section -->
            <div class="mt-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="fw-semibold mb-1">Trip Deductions</h5>
                        <p class="text-muted small mb-1">
                            You can add a deduction for the trip, if applicable. Please click on 
                            <a href="#" id="addDeductionBtn" class="text-danger fw-semibold text-decoration-none">Add Deductions</a>.
                        </p>
                        <p class="text-danger small mb-0 d-none" id="deductionNotice">
                            A payment deduction has already been applied for this driver.*
                        </p>
                    </div>
                </div>

                <!-- Deduction Details Card -->
                <div id="deductionCard" class="card shadow-sm p-3" style="display: none;">
                    <h6 class="fw-semibold mb-3">Deduction Details</h6>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <p><strong>Deduction Reason:</strong> <span id="deductionReasonText"></span></p>
                            <p><strong>Total Deduction Amount:</strong> ₹ <span id="deductionAmountText"></span></p>
                            <p><strong>Remarks:</strong> <span id="deductionRemarksText"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Trip:</strong> <span id="deductionTripText"></span></p>
                            <p><strong>Payment Duration:</strong> <span id="deductionDurationText"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Amount Overview -->
                <div class="mt-4">
                    <h6 class="fw-semibold mb-3">Amount Overview</h6>
                    <p class="text-muted small">Review the complete breakdown of charges, deductions, and final payable amounts for this transaction.</p>

                    <div class="border p-3 bg-light mb-2 d-flex justify-content-between">
                        <span>Total Amount</span>
                        <strong id="overviewTotalAmount">₹ 0.00</strong>
                    </div>
                    <div class="border p-3 bg-light mb-2 d-flex justify-content-between">
                        <span>Total Deduction Amount</span>
                        <strong id="overviewDeductionAmount">₹ 0.00</strong>
                    </div>
                    <div class="border p-3 bg-success bg-opacity-10 d-flex justify-content-between">
                        <strong>Total Payable Amount</strong>
                        <strong id="overviewPayableAmount" class="text-success">₹ 0.00</strong>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-3">
                    <button type="submit" class="btn btn-danger px-4">Submit</button>
                    <button type="button" class="btn btn-light px-4">Cancel</button>
                </div>
            </div>


        </form>
    </div>
</div>
<!-- Deduction Modal -->
<div class="modal fade" id="deductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header border-0">
                <h5 class="fw-semibold">Trip Deduction Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Enter the deduction amount and relevant details for the selected trip. These adjustments will be reflected in the final payment summary.</p>

                <form id="deductionForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Deduction Reason <span class="text-danger">*</span></label>
                            <select class="form-select" name="deduction_reason" required>
                                <option value="">-Select Reason-</option>
                                <option value="Accident">Accident</option>
                                <option value="Penalty">Penalty</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Trip <span class="text-danger">*</span></label>
                            <select class="form-select" name="trip_id" id="deductionTripSelect" required>
                                <option value="">-Select Trip ID-</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total Deduction Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="deduction_amount" placeholder="Enter the deduction amount" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Durations <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_duration" required>
                                <option value="">-Select payment duration-</option>
                                <option value="1 Month">1 Month</option>
                                <option value="2 Months">2 Months</option>
                                <option value="3 Months">3 Months</option>
                                <option value="4 Months">4 Months</option>
                                <option value="5 Months">5 Months</option>
                                <option value="6 Months">6 Months</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remarks" rows="3" placeholder="Type any remarks here" required></textarea>
                    </div>

                    <div class="mt-3 d-flex gap-3">
                        <button type="submit" class="btn btn-danger px-4">Apply</button>
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header border-0">
                <h5 class="fw-semibold">Set Payment Amount for Selected Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2">Enter the amount for the selected trip. Payment terms are shown automatically for your reference.</p>

                <div class="mb-3">
                    <label class="fw-semibold">Selected Trips:</label>
                    <div id="selectedTrips" class="d-flex flex-wrap gap-2"></div>
                </div>

                <p><strong>Total Kilometers:</strong> <span id="totalKM">0 KM</span></p>

                <form id="paymentForm">
                    <div id="paymentFields"></div>

                    <div class="mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-danger px-4">Submit</button>
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let selectedDriverId = null;
let payments = [];   // Global array for payments
let deductions = []; // Global array for deductions

// =======================
// DRIVER SEARCH
// =======================
const driverInput = document.getElementById('driver_name');
const driverList = document.getElementById('driverList');

driverInput.addEventListener('input', function() {
    let query = this.value.trim();
    if (!query) {
        driverList.style.display = 'none';
        driverList.innerHTML = '';
        return;
    }

    fetch(`{{ route('bata.searchDrivers') }}?q=${query}`)
        .then(res => res.json())
        .then(data => {
            driverList.innerHTML = '';
            if(data.length === 0) {
                driverList.style.display = 'none';
                return;
            }
            data.forEach(driver => {
                let li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.textContent = driver.name;
                li.dataset.id = driver.id;
                li.dataset.phone = driver.phone;
                driverList.appendChild(li);
            });
            driverList.style.display = 'block';
        });
});

document.addEventListener('click', function(e) {
    if(e.target.closest('#driverList li')) {
        selectedDriverId = e.target.dataset.id;
        driverInput.value = e.target.textContent;
        document.getElementById('phone').value = e.target.dataset.phone;
        driverList.style.display = 'none';
    } else if(!e.target.closest('#driver_name')) {
        driverList.style.display = 'none';
    }
});

// =======================
// DATE FILTER TRIPS
// =======================
document.querySelectorAll('[name="from_date"], [name="to_date"]').forEach(input => {
    input.addEventListener('change', function() {
        let from = document.querySelector('[name="from_date"]').value;
        let to = document.querySelector('[name="to_date"]').value;
        if(!from || !to || !selectedDriverId) return;

        fetch(`{{ route('bata.filterTrips') }}?driver_id=${selectedDriverId}&from_date=${from}&to_date=${to}`)
            .then(res => res.json())
            .then(data => {
                let tbody = document.getElementById('tripTableBody');
                tbody.innerHTML = '';

                if(data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12">No trips found for selected period.</td></tr>';
                    return;
                }

                data.forEach((trip, index) => {
                    tbody.innerHTML += `
                        <tr>
                            <td><input type="checkbox" class="trip-checkbox"></td>
                            <td>${index + 1}</td>
                            <td>${trip.trip_code}</td>
                            <td>${trip.delivery_date}</td>
                            <td>${trip.from_location}</td>
                            <td>${trip.to_location}</td>
                            <td>${trip.total_quantity}</td>
                            <td>${trip.vehicle_type}</td>
                            <td>${trip.vehicle_no}</td>
                            <td>${trip.approx_km} KM</td>
                            <td>${trip.pickup_point_flag}</td>
                            <td>${trip.salary_type}</td>
                        </tr>`;
                });
            });
    });
});

// =======================
// SELECT ALL TRIPS
// =======================
document.getElementById('select_all').addEventListener('change', function() {
    document.querySelectorAll('.trip-checkbox').forEach(cb => cb.checked = this.checked);
});

// =======================
// SET PAYMENT BUTTON
// =======================
document.getElementById('setPaymentBtn').addEventListener('click', function() {
    let selectedRows = document.querySelectorAll('.trip-checkbox:checked');
    if(selectedRows.length === 0) {
        alert('Please select at least one trip to set payment.');
        return;
    }

    payments = []; // Reset global payments array
    let totalKM = 0;
    let container = document.getElementById('selectedTrips');
    let fields = document.getElementById('paymentFields');
    container.innerHTML = '';
    fields.innerHTML = '';

    selectedRows.forEach(cb => {
        let row = cb.closest('tr');
        let tripId = row.cells[2].innerText.trim();
        let salaryType = row.cells[11].innerText.trim();
        let km = parseFloat(row.cells[9].innerText.replace(' KM', '')) || 0;
        totalKM += km;

        let badge = document.createElement('span');
        badge.className = 'badge bg-danger';
        badge.innerText = tripId;
        container.appendChild(badge);

        fields.innerHTML += `
            <div class="row g-3 align-items-center mb-3 border-bottom pb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Trip ID</label>
                    <input type="text" class="form-control" value="${tripId}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Salary Type</label>
                    <input type="text" class="form-control" value="${salaryType}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" placeholder="Enter Amount here">
                    </div>
                </div>
            </div>`;
    });

    document.getElementById('totalKM').innerText = totalKM + ' KM';
    let modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
});

// =======================
// PAYMENT MODAL SUBMIT
// =======================
document.getElementById('paymentForm').addEventListener('submit', function(e){
    e.preventDefault();

    let allValid = true;
    payments = []; // Reset before collecting

    document.querySelectorAll('#paymentFields .row').forEach(row => {
        let tripId = row.querySelector('input[readonly]').value;
        let salaryType = row.querySelectorAll('input[readonly]')[1].value;
        let amountField = row.querySelector('input[type="number"]');
        let amount = parseFloat(amountField.value);

        if(!amount || amount <= 0){
            allValid = false;
            amountField.classList.add('is-invalid');
        } else {
            amountField.classList.remove('is-invalid');
        }

        payments.push({ tripId, salaryType, amount });
    });

    if(!allValid){
        alert('Please enter all amounts before submitting.');
        return;
    }

    // Update Payment Table
    let tbody = document.querySelector('#paymentListTable tbody');
    tbody.innerHTML = '';
    let grandTotal = 0;

    payments.forEach(p => {
        let km = document.getElementById('totalKM').innerText;
        grandTotal += p.amount;
        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="badge bg-danger">${p.tripId}</span></td>
            <td>${p.salaryType}</td>
            <td>${km}</td>
            <td>₹ ${p.amount.toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('grandTotal').innerText = '₹ ' + grandTotal.toFixed(2);

    // Fetch related expenses
    let tripIds = payments.map(p => p.tripId);
    fetch(`{{ route('bata.fetchExpenses') }}?trip_ids[]=` + tripIds.join('&trip_ids[]=')) 
    .then(res => res.json())
    .then(expenses => {
        let tbody = document.querySelector('#otherExpensesTable tbody');
        tbody.innerHTML = '';
        let totalExpense = 0;

        if (expenses.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4">No expenses found for selected trips.</td></tr>`;
        } else {
            expenses.forEach(exp => {
                totalExpense += parseFloat(exp.amount.replace(/,/g, ''));
                tbody.innerHTML += `
                    <tr>
                        <td><span class="badge bg-danger">${exp.trip_code}</span></td>
                        <td>${exp.expense_type}</td>
                        <td>${exp.bill_image ? `<a href="${exp.bill_image}" target="_blank">View</a>` : 'N/A'}</td>
                        <td>₹ ${exp.amount}</td>
                    </tr>`;
            });
            tbody.innerHTML += `
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Total Amount</td>
                    <td>₹ ${totalExpense.toFixed(2)}</td>
                </tr>`;
        }

        // Update overview
        const paymentTotal = parseFloat(document.getElementById('grandTotal').innerText.replace(/[₹,]/g, '')) || 0;
        const deductionAmount = parseFloat(document.getElementById('overviewDeductionAmount').innerText.replace(/[₹,]/g, '')) || 0;
        const combinedTotal = paymentTotal + totalExpense;
        const payableAfterDeduction = combinedTotal - deductionAmount;

        document.getElementById('overviewTotalAmount').innerText = '₹ ' + combinedTotal.toFixed(2);
        document.getElementById('overviewPayableAmount').innerText = '₹ ' + payableAfterDeduction.toFixed(2);
    });

    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
});

// =======================
// DEDUCTION MODAL
// =======================
const addDeductionBtn = document.getElementById('addDeductionBtn');
const deductionModalEl = document.getElementById('deductionModal');
const deductionForm = document.getElementById('deductionForm');

addDeductionBtn.addEventListener('click', () => {
    const tripSelect = document.getElementById('deductionTripSelect');
    tripSelect.innerHTML = '<option value="">-Select Trip ID-</option>';

    document.querySelectorAll('#paymentListTable tbody tr').forEach(row => {
        let tripId = row.querySelector('.badge')?.textContent.trim();
        if (tripId) {
            let opt = document.createElement('option');
            opt.value = tripId;
            opt.textContent = tripId;
            tripSelect.appendChild(opt);
        }
    });

    let modal = new bootstrap.Modal(deductionModalEl);
    modal.show();
});

deductionForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(deductionForm);
    const data = Object.fromEntries(formData.entries());

    // Update global deductions array
    deductions.push({
        trip_id: data.trip_id,
        reason: data.deduction_reason,
        duration: data.payment_duration,
        amount: parseFloat(data.deduction_amount),
        remarks: data.remarks
    });

    // Fill summary card
    document.getElementById('deductionReasonText').innerText = data.deduction_reason;
    document.getElementById('deductionTripText').innerText = data.trip_id;
    document.getElementById('deductionDurationText').innerText = data.payment_duration;
    document.getElementById('deductionAmountText').innerText = parseFloat(data.deduction_amount).toFixed(2);
    document.getElementById('deductionRemarksText').innerText = data.remarks;
    document.getElementById('deductionCard').style.display = 'block';

    // Update overview
    const paymentTotal = parseFloat(document.getElementById('grandTotal').innerText.replace(/[₹,]/g, '')) || 0;
    let expenseTotal = 0;
    document.querySelectorAll('#otherExpensesTable tbody tr').forEach(tr => {
        let amountTd = tr.cells[3];
        if(amountTd){
            expenseTotal += parseFloat(amountTd.innerText.replace(/[₹,]/g, '')) || 0;
        }
    });

    const totalAmount = paymentTotal + expenseTotal;
    const deductionAmount = parseFloat(data.deduction_amount) || 0;
    const payableAmount = totalAmount - deductionAmount;

    document.getElementById('overviewTotalAmount').innerText = '₹ ' + totalAmount.toFixed(2);
    document.getElementById('overviewDeductionAmount').innerText = '₹ ' + deductionAmount.toFixed(2);
    document.getElementById('overviewPayableAmount').innerText = '₹ ' + payableAmount.toFixed(2);

    bootstrap.Modal.getInstance(deductionModalEl).hide();
});

// =======================
// MAIN FORM SUBMIT
// =======================
document.getElementById('bataForm').addEventListener('submit', function(e){
    
    let expenses = [];

    document.querySelectorAll('#otherExpensesTable tbody tr').forEach(tr => {
        // Skip placeholder rows
        if(tr.cells.length < 4) return;

        let tripId = tr.querySelector('td span')?.innerText.trim() || tr.cells[0]?.innerText.trim();
        let type = tr.cells[1]?.innerText.trim() || '';
        let amount = parseFloat(tr.cells[3]?.innerText.replace(/[₹,]/g, '').trim()) || 0;

        if(tripId && amount > 0){
            expenses.push({tripId, type, amount});
        }
    });

    document.getElementById('payments_json').value = JSON.stringify(payments);
    document.getElementById('deduction_json').value = JSON.stringify(deductions);
    document.getElementById('expenses_json').value = JSON.stringify(expenses);
});
</script>

@endsection
