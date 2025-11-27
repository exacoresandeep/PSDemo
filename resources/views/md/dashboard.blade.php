@extends('layouts.mdapp') {{-- or layouts.mdapp --}}
@section('title','MD Dashboard — Recreated')

@section('styles')
<style>
      .date-filter {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .date-input input {
        width: 140px; 
    }
</style>
@endsection
 
@section('content')
<div class="md-dashboard container-fluid">

   
    <div class="row mb-3">
        <div class="col-md-4 md-header">
            Welcome to Your <span class="md-header productName"><i class="fa fa-circle-o-notch fa-spin"></i></span> Dashboard
        </div>
        <div class="col-md-8 d-flex justify-content-end" style="overflow-x:auto; white-space:nowrap;">
            <div class="date-filter d-flex align-items-center" style="flex-wrap:nowrap; gap:15px;">
                
                <button class="btn btn-primary" id="btn-today">Today</button>
                <button class="btn btn-outline-primary" id="btn-week">This Week</button>
                
                <strong>Custom Date</strong>
                
                <input type="date" class="form-control" id="fromDate" style="min-width:140px;">
                
                <strong>To</strong>
                
                <input type="date" class="form-control" id="toDate" style="min-width:140px;">
                <button class="btn btn-primary" id="submitbtn">Submit</button>

            </div>
        </div>


    </div>

    <div class="row md-dash-summary mb-4 pb-5">
        <div class="col-md-12 mb-3">
            <h4 class="inputType">Today’s Sales Summary - <span class="inputDateRange">03/11/2022</span></h4>
        </div>
        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4>Total Orders</h4>
            </div>
            <div>
                <h2 id="totalOrder"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
            </div>
            </div>                  
        </div>
        </div>

        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4>Total Lead Open</h4>
            </div>
            <div>
                <h2 id="totalLead"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
            </div>
            </div>                  
        </div>
        </div>

        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4> Influencer Visit</h4>
            </div>
            <div>
                <h2 id="totalInfluencerVisit"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
            </div>
            </div>                  
        </div>
        </div>

        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4>Dealer Visit</h4>
            </div>
            <div>
                <h2 id="totalDealerVisit"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
            </div>
            </div>                  
        </div>
        </div>

        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4>Completed Activity</h4>
            </div>
            <div>
                <h2 id="totalCompletedActivity"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
            </div>
            </div>                  
        </div>
        </div>

        
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-card card-bg-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h4>Total Sales Revenue for <span class="productName"><i class="fa fa-circle-o-notch fa-spin"></i></span></h4></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><h1>Ton <span id="totalSales"><i class="fa fa-circle-o-notch fa-spin"></i></span></h1></div>
                    <div class="text-right">
                        <h5 >Order Count</h5>
                        <h2 id="totalSalesOrderCount"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card card-bg-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h4>Total Sales Quantity for <span class="productName"><i class="fa fa-circle-o-notch fa-spin"></i></span></h4></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><h1><span>Ton</span> <span id="totalQuantity"><i class="fa fa-circle-o-notch fa-spin"></i></span></h1></div>
                    <div class="text-right">
                        <h5>Order Count</h5>
                        <h2 id="totalOrderCount"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card card-bg-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h4>Total Lead Generated</h4></div>
                    
                </div>

                <div class="d-flex align-items-center mt-3">
                    <div><h1 id="totalLeadGenerated"><i class="fa fa-circle-o-notch fa-spin"></i></h1></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="dashboard-card-cover">
                <div class="row mb-3">
                    <div class="col-md-6"><h4>Outstanding Payments</h4></div>
                    
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="dashboard-card card-bg-4">
                            <h4>Total Outstanding Payments</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h1><i class="fa fa-inr"></i> <span id="totalOP"><i class="fa fa-circle-o-notch fa-spin"></i></span></h1></div>
                                <div class="text-right">
                                    <h5>Order Count</h5>
                                    <h2 id="orderCountOP"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="dashboard-card card-bg-1">
                            <h4>Total Collection Against Outstanding Payments</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h1><i class="fa fa-inr"></i> <span id="totalOPCollection"><i class="fa fa-circle-o-notch fa-spin"></i></span></h1></div>
                                <div class="text-right">
                                    <h5>Order Count</h5>
                                    <h2 id="orderCountCollection"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="row mb-4">
                <div class="col-md-5">
                    <div class="dashboard-card card-bg-5 h-auto mb-4">
                        <h4>Total Credit Note Amount</h4>
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h1><i class="fa fa-inr"></i> <span id="totalCredit"><i class="fa fa-circle-o-notch fa-spin"></i></span></h1></div>
                            <div class="text-right">
                                <h5>Credit Note Count</h5>
                                <h2 id="orderCountCredit"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white hs-card">
                            <h4>Highest and Lowest Selling Item</h4>

                            <div class="row" style="background:#D9FFE3;color:#34C759;font-weight:600;padding:10px;border-radius:6px;">
                                <div class="col-md-6">Highest Selling Item</div>
                                <div class="col-md-6 text-right" id="highestSellingItem"><i class="fa fa-circle-o-notch fa-spin"></i></div>
                            </div>

                            <div class="row mt-2" style="background:#FFECEA;color:#F14431;font-weight:600;padding:10px;border-radius:6px;">
                                <div class="col-md-6">Lowest Selling Item</div>
                                <div class="col-md-6 text-right" id="lowestSellingItem"><i class="fa fa-circle-o-notch fa-spin"></i></div>
                            </div>
                        </div>
                </div>
                <div class="col-md-7">
                    <div class="bg-white hs-card">
                        <h4>Customer Performance</h4>
                        <h5 style="color:#34C759">Top Customer</h5>

                        <div class="stock-table adj">
                            <table class="table table-striped">
                                <tr><td>Customer Name</td><td>Purchased Qty (TON)</td><td>Amount</td></tr>
                                <tr>
                                    <td id="topCustomerName">-</td>
                                    <td id="topCustomerQty">-</td>
                                    <td id="topCustomerAmount">-</td>
                                </tr>
                            </table>
                        </div>

                        <h5 style="color:#F14431">Least Purchased Customer</h5>
                        <div class="stock-table adj">
                            <table class="table table-striped">
                                <tr><td>Customer Name</td><td>Purchased Qty (TON)</td><td>Amount</td></tr>
                                <tr>
                                    <td id="leastCustomerName">-</td>
                                    <td id="leastCustomerQty">-</td>
                                    <td id="leastCustomerAmount">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white hs-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Sales Performance By Region</h4>

                    <div style="display:flex; gap:8px;">
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

                <div class="stock-table">
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
                            {{-- rows filled by JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="attnd-overview">
                <h4>Attendance Overview</h4>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <h5>Team on Duty</h5>
                        <h2 id="teamOnDuty"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                    </div>
                    <div class="text-right">
                        <h5>Team on Leave</h5>
                        <h2 id="teamOnLeave"><i class="fa fa-circle-o-notch fa-spin"></i></h2>
                    </div>
                </div>
            </div>
            {{-- Stock insights --}}
                <div class="stock-cover mt-3 bg-white">
                    <h4>Stock Insights</h4>
                    <p style="color:#555;margin-bottom:8px;">This shows the current stock status for <span class="productName"><i class="fa fa-circle-o-notch fa-spin"></i></span> products.</p>

                    <div style="display:flex; gap:12px; margin-bottom:12px;">
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">Total <strong id="totalStock"><i class="fa fa-circle-o-notch fa-spin"></i></strong> Products</div>
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">In Stock <strong id="totalInStock"><i class="fa fa-circle-o-notch fa-spin"></i></strong></div>
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">Out Of Stock <strong id="totalOutOfStock"><i class="fa fa-circle-o-notch fa-spin"></i></strong></div>
                    </div>

                    <div class="stock-table">
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Product</th><th>Product Type</th><th>Stock Quantity in TON</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                    <tr>
                                        <td><span class="productName"><i class="fa fa-circle-o-notch fa-spin"></i></span></td>
                                        <td>{{ $stock['type_name'] }}</td>
                                        <td>{{ number_format($stock['total_stock_quantity'], 2, '.', '') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>

    {{-- main content left and right --}}
    <div class="row">
        <div class="col-md-12">
            <div class="target-cover">
                <h4>Targets and Achievements</h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Unique Leads</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="uniqueTarget"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="uniqueAchieved"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Influencer Visit</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="influencerTarget"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="influencerAchieved"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Aashiyana</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="aashiyanaTarget"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="aashiyanaAchieved"><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                         <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Products</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="productsTarget"><i class="fa fa-circle-o-notch fa-spin"></i> <span style="font-size:12px;">TON</span></h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="productsAchieved"><i class="fa fa-circle-o-notch fa-spin"></i><span style="font-size:12px;">TON</span></h2></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                

                

                

               
            </div>
        </div>
        

        
    </div>

    {{-- Customer performance + sales performance --}}
    <div class="row mt-3">
        <div class="col-md-8">
            
        </div>
        <div class="col-md-4">
            
        </div>

        
    </div>

</div>

@endsection
@section('scripts')
<script>

function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString("en-GB"); // DD/MM/YYYY
}

// Highlight buttons
function activateButton(buttonId) {
    $("#btn-today, #btn-week").removeClass("btn-primary").addClass("btn-outline-primary");
    $(buttonId).removeClass("btn-outline-primary").addClass("btn-primary");
}

// Today
$("#btn-today").click(function () {
    activateButton("#btn-today");

    let today = formatDate(new Date());
    $(".inputType").html(`Today’s Sales Summary - <span class="inputDateRange">${today}</span>`);
});

// This Week
$("#btn-week").click(function () {
    activateButton("#btn-week");

    let today = new Date();
    let first = new Date(today.setDate(today.getDate() - today.getDay() + 1)); // Monday
    let last  = new Date(first);
    last.setDate(first.getDate() + 6); // Sunday

    let range = `${formatDate(first)} - ${formatDate(last)}`;

    $(".inputType").html(`Weekly Sales Summary - <span class="inputDateRange">${range}</span>`);
});

// Custom Date Range
$("#submitbtn").on("click",function () {
    let from = $("#fromDate").val();
    let to   = $("#toDate").val();

    if (from && to) {
        // Clear button highlight
        $("#btn-today, #btn-week").removeClass("btn-primary").addClass("btn-outline-primary");

        $(".inputType").html(
            `Custom Date Sales Summary - <span class="inputDateRange">${formatDate(from)} - ${formatDate(to)}</span>`
        );
    }
});
$(document).ready(function () {
    
    // Load today's data on page load
    loadToday();
    
    // Button: Today
    $("#btn-today").click(function () {
        loadToday();
    });

    // Button: This Week
    $("#btn-week").click(function () {
        loadThisWeek();
    });

    // Custom Date Change
    $("#submitbtn").on("click",function () {
        let from = $("#fromDate").val();
        let to   = $("#toDate").val();
        
        if(from !== "" && to !== ""){
            loadDashboardData1(from, to);
            loadDashboardData2(from, to);
            loadDashboardData3(from, to);
            loadDashboardData4(from, to);
            updateSalesPerformanceBody(from, to);
        }
    });

});

function loadToday() {
    let today = new Date().toISOString().split("T")[0];

    $("#fromDate").val(today);
    $("#toDate").val(today);
    loadDashboardData1(today, today);
    loadDashboardData2(today, today);
    loadDashboardData3(today, today);
    loadDashboardData4(today, today);
    updateSalesPerformanceBody(today, today);
}

function loadThisWeek() {
    let now = new Date();
    let first = new Date(now.setDate(now.getDate() - now.getDay() + 1)); // Monday
    let last  = new Date(first);
    last.setDate(first.getDate() + 6); // Sunday

    let from = first.toISOString().split("T")[0];
    let to   = last.toISOString().split("T")[0];

    $("#fromDate").val(from);
    $("#toDate").val(to);

    loadDashboardData1(from, to);
    loadDashboardData2(from, to);
    loadDashboardData3(from, to);
    loadDashboardData4(from, to);
    updateSalesPerformanceBody(from, to);
}

function loadDashboardData1(fromDate, toDate) {
    let employeeType = $('#employeeTypeSalesPerformance').val();
    let region = $('#regionSalesPerformance').val();
    $.ajax({
        url: "/md/getMDData1",
        method: "GET",
        data: {
            from: fromDate,
            to: toDate,
            employee_type_id:employeeType, 
            region_id:region
        },
        success: function(r) {
            $(".productName").html(r.productName);
            /** -------- TOP SUMMARY (Today Section) -------- **/
            $("#totalOrder").text(r.totalOrder);
            $("#totalLead").text(r.totalLeadOpen);
            $("#totalInfluencerVisit").text(r.totalInfluencerVisit);
            $("#totalDealerVisit").text(r.totalDealerVisit);
            $("#totalCompletedActivity").text(r.totalCompletedActivity);

            $("#totalEmployees").text(r.totalEmployees);
            $("#totalVisits").text(r.totalVisits);
            $("#totalOrders").text(r.totalOrders);
            $("#totalCollections").text(r.totalCollections);
            $("#totalOutstanding").text(r.totalOutstanding);
            /** -------- BIG 3 CARDS -------- **/
            $("#totalSales").text(r.totalSalesRevenue);
            $("#totalSalesOrderCount").text(r.totalSalesOrderCount);

            $("#totalQuantity").text(r.totalSalesQuantityTon);
            $("#totalOrderCount").text(r.totalSalesQuantityCount);
            $("#totalLeadGenerated").text(r.totalLeadGenerated);
           
            
        },
        error: function(xhr) {
            console.error("Dashboard Load Error", xhr.responseText);
        }
    });
}

function loadDashboardData2(fromDate, toDate) {
    let employeeType = $('#employeeTypeSalesPerformance').val();
    let region = $('#regionSalesPerformance').val();
    $.ajax({
        url: "/md/getMDData2",
        method: "GET",
        data: {
            from: fromDate,
            to: toDate,
             employee_type_id:employeeType, 
             region_id:region
        },
        success: function(r) {
           
            /** -------- OUTSTANDING PAYMENTS -------- **/
            $("#totalOP").text(r.totalOutstandingPayment);
            $("#orderCountOP").text(r.outstandingOrderCount);

            $("#totalOPCollection").text(r.totalOutstandingCollection);
            $("#orderCountCollection").text(r.collectionOrderCount);

            /** -------- CREDIT NOTE -------- **/
            $("#totalCredit").text(r.totalCreditNoteAmount);
            $("#orderCountCredit").text(r.creditNoteCount);

           
            
        },
        error: function(xhr) {
            console.error("Dashboard Load Error", xhr.responseText);
        }
    });
}

function loadDashboardData3(fromDate, toDate) {
    let employeeType = $('#employeeTypeSalesPerformance').val();
    let region = $('#regionSalesPerformance').val();
    $.ajax({
        url: "/md/getMDData3",
        method: "GET",
        data: {
            from: fromDate,
            to: toDate,
             employee_type_id:employeeType, 
             region_id:region
        },
        success: function(r) {
           
            /** -------- Highest / Lowest Selling Item -------- **/
            $("#highestSellingItem").text(r.highestSellingItem);
            $("#lowestSellingItem").text(r.lowestSellingItem);

            /** -------- CUSTOMER PERFORMANCE -------- **/
            $("#topCustomerName").text(r.topCustomerName);
            $("#topCustomerQty").text(r.topCustomerQty);
            $("#topCustomerAmount").text(r.topCustomerAmount);

            $("#leastCustomerName").text(r.leastCustomerName);
            $("#leastCustomerQty").text(r.leastCustomerQty);
            $("#leastCustomerAmount").text(r.leastCustomerAmount);

            /** -------- Sales Performance (Bottom Table) -------- **/
            // If table rows come from API array
            
            $("#leastCustomerAmount").text(r.leastCustomerAmount);
            
            
        },
        error: function(xhr) {
            console.error("Dashboard Load Error", xhr.responseText);
        }
    });
}

function loadDashboardData4(fromDate, toDate) {
    let employeeType = $('#employeeTypeSalesPerformance').val();
    let region = $('#regionSalesPerformance').val();
    $.ajax({
        url: "/md/getMDData4",
        method: "GET",
        data: {
            from: fromDate,
            to: toDate,
             employee_type_id:employeeType, 
             region_id:region
        },
        success: function(r) {
            if(r.attendance!=false){
                console.log(r.attendance);
                $("#teamOnDuty").text(r.teamOnDuty); 
                $("#teamOnLeave").text(r.teamOnLeave); 
                $(".attnd-overview").show();

            } else {
                $(".attnd-overview").hide();
            }
            
            $("#totalStock").text(r.totalStock); 
            $("#totalInStock").text(r.totalInStock); 
            $("#totalOutOfStock").text(r.totalOutOfStock); 
            $("#uniqueTarget").text(r.uniqueTarget); 
            $("#uniqueAchieved").text(r.uniqueAchieved); 
            $("#influencerAchieved").text(r.influencerAchieved); 
            $("#influencerTarget").text(r.influencerTarget); 
            $("#aashiyanaTarget").text(r.aashiyanaTarget); 
            $("#aashiyanaAchieved").text(r.aashiyanaAchieved); 
            $("#productsTarget").text(r.productsTarget); 
            $("#productsAchieved").text(r.productsAchieved); 
            
        },
        error: function(xhr) {
            console.error("Dashboard Load Error", xhr.responseText);
        }
    });
}

$("#employeeTypeSalesPerformance, #regionSalesPerformance").on('change', function () {
    let from = $("#fromDate").val();
    let to   = $("#toDate").val();
    updateSalesPerformanceBody(from,to);
});

function updateSalesPerformanceBody(fromDate, toDate) {
    let employeeType = $('#employeeTypeSalesPerformance').val();
    let region = $('#regionSalesPerformance').val();
    
    // Example AJAX
    $.ajax({
        url: "/md/getMDData5",
        type: "GET",
        data: {
            from: fromDate,
            to: toDate,
            employee_type_id:employeeType, 
            region_id:region
        },
        success: function (r) {
            if (r.salesPerformance && r.salesPerformance.length > 0) {
                
                let rows = "";

                r.salesPerformance.forEach(item => {
                    rows += `
                        <tr>
                            <td>${item.region}</td>
                            <td>${item.employeeType}</td>
                            <td>${item.employeeName}</td>
                            <td>${item.sellingQty}</td>
                            <td>${item.amount}</td>
                        </tr>
                    `;
                });

                $("#salesPerformanceTableBody").html(rows);
            }
        }
    });
}

</script>
@endsection