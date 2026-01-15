@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Day End Report</h3>
    </div>

    <div class="filter-sec target-filter">
        <div class="row">
             <div class="col-md-2">
                <label>Designation</label>
                <select class="form-control" id="filter_employee_type">
                    <option value="">-Select Designation -</option>
                    @foreach($employeeTypes as $type)
                        @if($type->id != 6)
                        <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Employee Name</label>
                <select class="form-control" id="filter_employee">
                    <option value="">-Select Employee-</option>
                </select>
            </div>

             <div class="col-md-2">
                <label>From Date</label>
                <input type="date" class="form-control" id="fromdate">
            </div>
            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" class="form-control" id="todate">
            </div>
            <div class="col-md-2">
                <label>Travel Method</label>
                <select class="form-control" id="travel_method">
                    <option value="">All</option>
                    <option value="Bike">Bike</option>
                    <option value="Car">Car</option>
                    <option value="Own Car">Own Car</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label> &nbsp;</label>
                <button class="btn btn-primary" id="exportBtn"><i class="fa fa-download"></i> Export</button>
            </div>
        </div>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="dayendTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Employee Name/ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Travel Method</th>
                    <th>Kilometer Travelled</th>
                    <th>Route</th>
                    <th>Other Expense</th>
                    <th>Reason</th>
                    <th>Attachment</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal" style="display: none;">
    <div class="modal-content" style="width: 80%; max-width: 800px; height: 500px; position: relative; margin: auto; top: 50%; transform: translateY(-50%); border-radius: 10px; overflow: hidden; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">

        <!-- Close button -->
        <span class="close" style="position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: bold; color: #333; cursor: pointer; z-index: 10;">&times;</span>

        <!-- Slider Container -->
        <div class="slider-container" style="display: flex; align-items: center; justify-content: center; height: 100%; position: relative;">

            <!-- Prev Button -->
            <button class="prev" style="position: absolute; left: 10px; background: rgba(0,0,0,0.5); border: none; color: #fff; font-size: 24px; padding: 10px; border-radius: 50%; cursor: pointer; z-index: 5;">&#10094;</button>

            <!-- Image Wrapper -->
            <div class="slider" style="flex: 1; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;">
                <img id="modalImage" src="" style="max-height: 100%; max-width: 100%; object-fit: contain;">
            </div>

            <!-- Next Button -->
            <button class="next" style="position: absolute; right: 10px; background: rgba(0,0,0,0.5); border: none; color: #fff; font-size: 24px; padding: 10px; border-radius: 50%; cursor: pointer; z-index: 5;">&#10095;</button>
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
</style>
<script>
   $(document).ready(function () {

    let currentIndex = 0;
    let images = [];

    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("view-images")) {
            images = JSON.parse(e.target.getAttribute("data-images"));
            currentIndex = 0;
            showImage();
            document.getElementById("imageModal").style.display = "block";
        }

        if (e.target.classList.contains("close")) {
            document.getElementById("imageModal").style.display = "none";
        }

        if (e.target.classList.contains("next")) {
            currentIndex = (currentIndex + 1) % images.length;
            showImage();
        }

        if (e.target.classList.contains("prev")) {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            showImage();
        }
    });

    function showImage() {
        document.getElementById("modalImage").src = images[currentIndex];
    }

    $('#exportBtn').click(function () {
        let params = $.param({
            employee_type: $('#filter_employee_type').val(),
            employee_id: $('#filter_employee').val(),
            from_date: $('#fromdate').val(),
            to_date: $('#todate').val(),
            travel_method: $('#travel_method').val()
        });

        window.location = "{{ route('sales.dayend.export') }}?" + params;
    });

    var table = $('#dayendTable').DataTable({
        processing: true,
        serverSide: false,
        searching: true,
        ajax: {
            url: "{{ route('sales.dayend.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.employee_type = $('#filter_employee_type').val();
                d.employee_id   = $('#filter_employee').val();
                d.from_date     = $('#fromdate').val();
                d.to_date       = $('#todate').val();
                d.travel_method = $('#travel_method').val(); // corrected
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, // Sl.No
            { data: 'employee', name: 'employee' },          // Employee Name/ID (join with users table)
            { data: 'date', name: 'date' },      // Date & Time
            { data: 'time', name: 'time' },      // Date & Time
            { data: 'travel_method', name: 'travel_method' },// Travel Method
            { data: 'route', name: 'route' },// Route
            { data: 'km_traveled', name: 'km_traveled' },    // Kilometer Travelled
            { data: 'other_expense', name: 'other_expense' },// Other Expense
            { data: 'remarks', name: 'remarks' },            // Remarks
            { data: 'attachment', name: 'attachment'},
            { data: 'total_amount', name: 'total_amount' },  // Total Amount
        ]
    });
    // ✅ Trigger reload on filter change
    $('#fromdate, #todate, #filter_employee_type, #filter_employee, #travel_method').on('change keyup', function () {
        table.ajax.reload();
    });

    // Initialize Select2 for employee dropdown
    $('#filter_employee').select2({
        placeholder: "All",
        allowClear: true,
        ajax: {
            url: "{{ route('sales.getEmployeesAjax') }}", // 🔹 Create this route
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    employee_type: $('#filter_employee_type').val() // optional filter by designation
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function (employee) {
                        return { id: employee.id, text: employee.name };
                    })
                };
            },
            cache: true
        }
    });

});

</script>
@endsection
