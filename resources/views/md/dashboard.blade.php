@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $currentMonth = \Carbon\Carbon::now()->format('n')-1;
    $currentYear = \Carbon\Carbon::now()->year;
    $startYear = $currentYear - 3;
    $endYear = $currentYear + 5;
    $months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="dashboard-card card-bg-1">
            <div class="row">
                <div class="col-md-6">
                    <h4>Total Sales Revenue for TATA Tiscon</h4>
                </div>
                <div class="col-md-6 text-right">
                    <select class="form-control" id="monthSelect">
                        @foreach($months as $key => $month)
                            <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                                {{ $month }}
                            </option>
                        @endforeach
                    </select>

                    <select class="form-control" id="yearSelect">
                        @for($year = $startYear; $year <= $endYear; $year++)
                            <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalRevenue">0</span></h1>
                </div>
                <div class="col-md-6 text-right">
                    <h5>Order Count</h5>
                    <h2 id="orderCount">0</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-bg-2">
            <div class="row">
            <div class="col-md-6">
                <h4>Total Sales Quantity for TATA Tiscon</h4>
            </div>
            <div class="col-md-6 text-right">
                <select class="form-control" id="monthQuantity">
                    @foreach($months as $key => $month)
                        <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                            {{ $month }}
                        </option>
                    @endforeach
                </select>

                <select class="form-control" id="yearQuantity">
                    @for($year = $startYear; $year <= $endYear; $year++)
                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
            </div>
            </div>
            <div class="row">
            <div class="col-md-6">
                <h1><span>Ton</span> <span id="totalQuantity">0</span></h1>
            </div>
            <div class="col-md-6 text-right">
                <h5>Order Count</h5>
                <h2 id="orderCountQuantity">0</h2>
            </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-bg-3">
            <div class="row">
            <div class="col-md-6">
                <h4>Total Lead Generated</h4>
            </div>
            <div class="col-md-6 text-right">
                <select class="form-control" id="monthLead">
                    @foreach($months as $key => $month)
                        <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                            {{ $month }}
                        </option>
                    @endforeach
                </select>

                <select class="form-control" id="yearLead">
                    @for($year = $startYear; $year <= $endYear; $year++)
                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>                     
            </div>
            </div>
            <div class="row">
            <div class="col-md-6">
                <h1 id="totalLead"> 104</h1>
            </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
    <div class="dashboard-card-cover">
        <div class="row">
        <div class="col-md-6">
            <h4>Outstanding Payments</h4>
        </div>
        <div class="col-md-6 text-right">
            <select class="form-control" id="monthOP">
                @foreach($months as $key => $month)
                    <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                        {{ $month }}
                    </option>
                @endforeach
            </select>

            <select class="form-control" id="yearOP">
                @for($year = $startYear; $year <= $endYear; $year++)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>      
        </div>
        </div>
        <div class="row">
        <div class="col-md-6">
            <div class="dashboard-card card-bg-4">
            <h4>Total Outstanding Payments</h4>
            <div class="row">
                <div class="col-md-6">
                <h1><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalOP">0</span></h1>
                </div>
                <div class="col-md-6 text-right">
                <h5>Order Count</h5>
                <h2 id="orderCountOP">35</h2>
                </div>
            </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card card-bg-1">
            <h4>Total Collection Against Outstanding Payments</h4>
            <div class="row">
                <div class="col-md-6">
                <h1><i class="fa fa-inr" aria-hidden="true"></i> <span id="opCollection">0</span></h1>
                </div>
                <div class="col-md-6 text-right">
                <h5>Order Count</h5>
                <h2 id="orderCountCollection">35</h2>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    <div class="stock-cover">
        <h4>Stock Insights</h4>
        <p>This shows the current stock status for TATA Tiscon products.
        </p>
        <div class="stock-table">
        <table class="table table-striped">
            <tr>
            <td>Product</td>
            <td>Product Type</td>
            <td>Stock Quantity in TON</td>
            </tr>
            @foreach ($stocks as $stock)
                <tr>
                    <td>TATA Tiscon</td>
                    <td>{{ $stock['type_name'] }}</td>
                    <td>{{ number_format($stock['total_stock_quantity'], 2, '.', '') }}</td>
                </tr>
            @endforeach
        </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="dashboard-card card-bg-5">
                <div class="row">
                <div class="col-md-6">
                    <h4>Total Credit Note Amount</h4>
                </div>
                <div class="col-md-6 text-right">
                    <select class="form-control" id="monthCredit">
                        @foreach($months as $key => $month)
                            <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                                {{ $month }}
                            </option>
                        @endforeach
                    </select>
        
                    <select class="form-control" id="yearCredit">
                        @for($year = $startYear; $year <= $endYear; $year++)
                            <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>    
                </div>
                </div>
                <div class="row">
                <div class="col-md-6">
                    <h1><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalCredit">0</span></h1>
                </div>
                <div class="col-md-6 text-right">
                    <h5>Credit Note Count</h5>
                    <h2 id="orderCountCredit">0</h2>
                </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card bg-white hs-card">
                <h4>Highest and Lowest Selling Item</h4>
                <div class="row" style="background: #D9FFE3; color: #34C759; font-weight: 500;">
                <div class="col-md-6">
                    <p>Highest Selling Item</p>
                </div>
                <div class="col-md-6 text-right">
                    <p id="highestSellingItem">0</p>
                </div>
                </div>
                <div class="row" style="background: #FFECEA; color: #F14431; font-weight: 500;">
                <div class="col-md-6">
                    <p>Lowest Selling Item</p>
                </div>
                <div class="col-md-6 text-right">
                    <p id="lowestSellingItem">0</p>
                </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="col-md-4">
        <div class="target-cover">
            <h4>Targets and Achievements</h4>
            <select class="form-control" id="monthTarget">
                @foreach($months as $key => $month)
                    <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                        {{ $month }}
                    </option>
                @endforeach
            </select>

            <select class="form-control" id="yearTarget">
                @for($year = $startYear; $year <= $endYear; $year++)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>  
            <div class="dashboard-card card-bg-2">
            <h4>Unique Leads</h4>
            <div class="row">
                <div class="col-md-6">
                <h5>Target <i class="fa fa-bullseye" aria-hidden="true"></i></h5>
                <h2 id="uniqueTarget">0</h2>
                </div>
                <div class="col-md-6 text-right">
                <h5>Achieved <i class="fa fa-trophy" aria-hidden="true"></i></h5>
                <h2 id="uniqueAchieved">0</h2>
                </div>
            </div>
            </div>
            <div class="dashboard-card card-bg-2">
            <h4>Influencer Visit</h4>
            <div class="row">
                <div class="col-md-6">
                <h5>Target <i class="fa fa-bullseye" aria-hidden="true"></i></h5>
                <h2 id="influencerTarget">0</h2>
                </div>
                <div class="col-md-6 text-right">
                <h5>Achieved <i class="fa fa-trophy" aria-hidden="true"></i></h5>
                <h2 id="influencerAchieved">0</h2>
                </div>
            </div>
            </div>
            <div class="dashboard-card card-bg-2">
            <h4>Aashiyana</h4>
            <div class="row">
                <div class="col-md-6">
                <h5>Target <i class="fa fa-bullseye" aria-hidden="true"></i></h5>
                <h2 id="aashiyanaTarget">0</h2>
                </div>
                <div class="col-md-6 text-right">
                <h5>Achieved <i class="fa fa-trophy" aria-hidden="true"></i></h5>
                <h2 id="aashiyanaAchieved">0</h2>
                </div>
            </div>
            </div>
            <div class="dashboard-card card-bg-2">
            <h4>Products</h4>
            <div class="row">
                <div class="col-md-6">
                <h5>Target <i class="fa fa-bullseye" aria-hidden="true"></i></h5>
                <h2 id="productsTarget">0 <span>TON</span></h2>
                </div>
                <div class="col-md-6 text-right">
                <h5>Achieved <i class="fa fa-trophy" aria-hidden="true"></i></h5>
                <h2 id="productsAchieved">0 <span>TON</span></h2>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="dashboard-card bg-white hs-card">
            <h4 style="min-height: 0;">Customer Performance</h4>
            <h5 style="color: #34C759;">Top Customer</h5>
            <div class="stock-table adj">
            <table class="table table-striped">
                <tr>
                <td>Customer Name</td>
                <td>Purchased Qty (TON)</td>
                <td>Amount</td>
                </tr>
                <tr>
                    <td id="topCustomerName">--</td>
                    <td id="topCustomerQty">--</td>
                    <td id="topCustomerAmount">--</td>
                </tr>                         
            </table>
            </div>
            <h5 style="color:#F14431">Least Purchased Customer</h5>
            <div class="stock-table adj">
            <table class="table table-striped">
                <tr>
                <td>Customer Name</td>
                <td>Purchased Qty (TON)</td>
                <td>Amount</td>
                </tr>
                <tr>
                    <td id="leastCustomerName">--</td>
                    <td id="leastCustomerQty">--</td>
                    <td id="leastCustomerAmount">--</td>
                </tr>                        
            </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="dashboard-card bg-white hs-card">
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-4">
                    <h4 style="min-height: 0;">Sales Performance By Region </h4>
                </div>
                <div class="col-md-8 text-right">
                    <select class="form-control" id="monthSalesPerformance">
                        @foreach($months as $key => $month)
                            <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                                {{ $month }}
                            </option>
                        @endforeach
                    </select>

                    <select class="form-control" id="yearSalesPerformance">
                        @for($year = $startYear; $year <= $endYear; $year++)
                            <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>      
                    <select class="form-control" id="employeeTypeSalesPerformance">
               
                        <option value="1">Sales Executive</option>
                        <option value="2">Area Sales Officer</option>
                        <option value="3">District Sales Manager</option>
                        <option value="4">Regional Sales Manager</option>
                        <option value="5">Sales Manager</option>
                       
                    </select>
                    <select class="form-control" id="regionSalesPerformance">
                   
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="stock-table adj">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Region</th>
                            <th>Employee Type</th>
                            <th>Employee Name</th>
                            <th>Selling Quantity (TON)</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="salesPerformanceTableBody">
                    </tbody>
                </table>
            </div> 
        </div>
    </div>
</div>
             
@endsection

@section('scripts')
<script>
    function fetchDashboardData() {
        const month = $('#monthSelect').val();
        const year = $('#yearSelect').val();

        $.ajax({
            url: '{{ route("md.getSalesData") }}',
            method: 'GET',
            data: {
                month: month,
                year: year
            },
            success: function (response) {
                $('#totalRevenue').text(response.totalRevenue.toLocaleString('en-IN'));
                $('#orderCount').text(response.orderCount);
                $('#totalQuantity').text(response.totalQuantity.toLocaleString('en-IN'));
            },
            error: function (xhr, status, error) {
                alert('Error fetching dashboard data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    function fetchQuantityData() {
        const month = $('#monthQuantity').val();
        const year = $('#yearQuantity').val();

        $.ajax({
            url: '{{ route("md.getSalesQuantity") }}',
            method: 'GET',
            data: { month, year },
            success: function (response) {
                $('#totalQuantity').text(response.totalQuantity.toLocaleString('en-IN'));
                $('#orderCountQuantity').text(response.orderCount);
            },
            error: function (xhr) {
                alert('Error fetching quantity data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    function fetchLeadsData() {
        const month = $('#monthLead').val();
        const year = $('#yearLead').val();

        $.ajax({
            url: '{{ route("md.getLeadsData") }}',
            method: 'GET',
            data: { month, year },
            success: function (response) {
                $('#totalLead').text(response.totalLeads);
            },
            error: function (xhr) {
                alert('Error fetching leads data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    function fetchOutstandingPaymentsData() {
        const month = $('#monthOP').val();
        const year = $('#yearOP').val();

        $.ajax({
            url: '{{ route("md.getOutstandingPaymentsData") }}',
            method: 'GET',
            data: { month, year },
            success: function (response) {
                $('#totalOP').text(response.totalOutstandingAmount.toLocaleString('en-IN'));
                $('#orderCountOP').text(response.totalOutstandingInvoices);

                $('#opCollection').text(response.totalCollectionAmount.toLocaleString('en-IN'));
                $('#orderCountCollection').text(response.totalPaidInvoices);
            },
            error: function (xhr) {
                alert('Error fetching outstanding data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    function loadTargetAndAchievement() {
        const month = $('#monthTarget').val();
        const year = $('#yearTarget').val();

        $.ajax({
            url: '{{ route("md.overall-target-achievement") }}', 
            method: 'GET',
            data: { month: month, year: year },
            success: function(res) {
                $('#uniqueTarget').text(res.target.unique_leads);
                $('#uniqueAchieved').text(res.achieved.unique_leads);

                $('#influencerTarget').text(res.target.customer_visit);
                $('#influencerAchieved').text(res.achieved.customer_visit);

                $('#aashiyanaTarget').text(res.target.aashiyana);
                $('#aashiyanaAchieved').text(res.achieved.aashiyana);

                $('#productsTarget').text(res.target.order_quantity + ' TON');
                $('#productsAchieved').text(res.achieved.order_quantity + ' TON');
            }
        });
    }
    function fetchCreditNoteData() {
        const month = $('#monthCredit').val();
        const year = $('#yearCredit').val();

        $.ajax({
            url: "{{ route('md.credit-note-stats') }}",
            type: 'GET',
            data: { month: month, year: year },
            success: function(response) {
                if (response.success) {
                    $('#totalCredit').text(response.data.credit_note_amount);
                    $('#orderCountCredit').text(response.data.order_count);
                }
            },
            error: function(xhr) {
                console.error("Error fetching credit note data", xhr);
            }
        });
    }
    function fetchHighestLowestItems() {
        $.ajax({
            url: "{{ route('md.highest-lowest-items') }}",
            type: 'GET',
            success: function (res) {
                $('#highestSellingItem').text(res.highest ?? 'N/A');
                $('#lowestSellingItem').text(res.lowest ?? 'N/A');
            },
            error: function () {
                $('#highestSellingItem').text('Error');
                $('#lowestSellingItem').text('Error');
            }
        });
    }
    function fetchCustomerPerformance() 
    {
        let month = $('#monthSelect').val();  // Optional: pass these if you have dropdowns
        let year = $('#yearSelect').val();

        $.ajax({
            url: "{{ route('md.customer-performance') }}",
            type: 'GET',
            data: { month: month, year: year }, // can be empty if not passed
            success: function (response) {
                if (response.status && response.data) {
                    const most = response.data.most_purchased;
                    const least = response.data.least_purchased;

                    $('#topCustomerName').text(most.dealer_name);
                    $('#topCustomerQty').text(most.total_quantity);
                    $('#topCustomerAmount').text(most.total_amount);

                    $('#leastCustomerName').text(least.dealer_name);
                    $('#leastCustomerQty').text(least.total_quantity);
                    $('#leastCustomerAmount').text(least.total_amount);
                } else {
                    // Optional: Show defaults or empty values
                    $('#topCustomerName, #topCustomerQty, #topCustomerAmount').text('N/A');
                    $('#leastCustomerName, #leastCustomerQty, #leastCustomerAmount').text('N/A');
                }
            },
            error: function () {
                $('#topCustomerName, #topCustomerQty, #topCustomerAmount').text('Error');
                $('#leastCustomerName, #leastCustomerQty, #leastCustomerAmount').text('Error');
            }
        });
    }
    function loadSalesPerformance() {
        let month = $('#monthSalesPerformance').val();
        let year = $('#yearSalesPerformance').val();
        let employeeType = $('#employeeTypeSalesPerformance').val();
        let region = $('#regionSalesPerformance').val();

        $.ajax({
            url: "{{ route('md.sales-performance') }}",
            method: 'GET',
            data: {
                month: month,
                year: year,
                employee_type_id: employeeType,
                region_id: region
            },
            success: function (response) {
                let tbody = '';

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function (item) {
                        tbody += `
                            <tr>
                                <td>${item.region ?? '-'}</td>
                                <td>${item.employee_type ?? '-'}</td>
                                <td>${item.employee_name ?? '-'}</td>
                                <td>${parseFloat(item.total_invoice_quantity || 0).toFixed(2)}</td>
                                <td><i class="fa fa-inr" aria-hidden="true"></i> ${parseFloat(item.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });
                } else {
                    tbody = `<tr><td colspan="5" class="text-center">No data found</td></tr>`;
                }

                $('#salesPerformanceTableBody').html(tbody);
            },
            error: function (xhr) {
                console.error('Error loading sales performance:', xhr);
                $('#salesPerformanceTableBody').html(`<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>`);
            }
        });
    }
    $(document).ready(function () {
        fetchDashboardData();
        fetchQuantityData();
        fetchLeadsData();
        fetchOutstandingPaymentsData();
        loadTargetAndAchievement();
        fetchCreditNoteData();
        fetchHighestLowestItems();
        fetchCustomerPerformance();
        loadSalesPerformance();
       
        $('#monthSelect, #yearSelect').change(fetchDashboardData);
        $('#monthQuantity, #yearQuantity').change(fetchQuantityData);
        $('#monthLead, #yearLead').change(fetchLeadsData);
        $('#monthOP, #yearOP').change(fetchOutstandingPaymentsData);
        $('#monthTarget, #yearTarget').change(loadTargetAndAchievement);
        $('#monthTarget, #yearTarget').change(fetchCreditNoteData);
        $('#monthSelect, #yearSelect').change(fetchCustomerPerformance);
        $('#monthSalesPerformance, #yearSalesPerformance, #employeeTypeSalesPerformance, #regionSalesPerformance').change(loadSalesPerformance);
    });

</script>
@endsection