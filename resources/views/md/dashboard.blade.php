{{-- resources/views/md_dashboard_recreate.blade.php --}}
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
        width: 140px; /* prevent breaking to new row */
    }
</style>
@endsection

@section('content')
<div class="md-dashboard container-fluid">

   
    <div class="row mb-3">
        <div class="col-md-4 md-header">
            Welcome to Your <span class="md-header">Tata Tiscon </span> Dashboard
        </div>
        <div class="col-md-8 d-flex justify-content-end" style="overflow-x:auto; white-space:nowrap;">
            <div class="date-filter d-flex align-items-center" style="flex-wrap:nowrap; gap:15px;">
                
                <button class="btn btn-primary" id="btn-today">Today</button>
                <button class="btn btn-outline-primary" id="btn-week">This Week</button>

                <strong>Custom Date</strong>

                <input type="date" class="form-control" id="fromDate" style="min-width:140px;">
                
                <strong>To</strong>

                <input type="date" class="form-control" id="toDate" style="min-width:140px;">

            </div>
        </div>


    </div>

    {{-- Today's summary --}}
    <div class="row md-dash-summary mb-4 pb-5">
        <div class="col-md-12 mb-3">
            <h4>Today’s Sales Summary - 03/11/202</h4>
        </div>
        <div class="col">
        <div class="dash-item-box">
            <div class="justify-content-between p-3 align-items-center">
            <div>
                <h4>Total Orders</h4>
            </div>
            <div>
                <h2>56</h2>
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
                <h2>56</h2>
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
                <h2>56</h2>
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
                <h2>56</h2>
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
                <h2>56</h2>
            </div>
            </div>                  
        </div>
        </div>

        
    </div>

    {{-- big top cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-card card-bg-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h4>Total Sales Revenue for TATA Tiscon</h4></div>
                    
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><h1><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalRevenue">357076000.56</span></h1></div>
                    <div class="text-right">
                        <h5 >Order Count</h5>
                        <h2 id="orderCount">35</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card card-bg-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div><h4>Total Sales Quantity for TATA Tiscon</h4></div>
                    
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div><h1><span>Ton</span> <span id="totalQuantity">100.23</span></h1></div>
                    <div class="text-right">
                        <h5>Order Count</h5>
                        <h2 id="orderCountQuantity">35</h2>
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
                    <div><h1 id="totalLead">104</h1></div>
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
                                <div><h1><i class="fa fa-inr"></i> <span id="totalOP">2656058.46</span></h1></div>
                                <div class="text-right">
                                    <h5>Order Count</h5>
                                    <h2 id="orderCountOP">35</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="dashboard-card card-bg-1">
                            <h4>Total Collection Against Outstanding Payments</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h1><i class="fa fa-inr"></i> <span id="opCollection">726550.55</span></h1></div>
                                <div class="text-right">
                                    <h5>Order Count</h5>
                                    <h2 id="orderCountCollection">35</h2>
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
                            <div><h1><i class="fa fa-inr"></i> <span id="totalCredit">2046000.06</span></h1></div>
                            <div class="text-right">
                                <h5>Credit Note Count</h5>
                                <h2 id="orderCountCredit">35</h2>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white hs-card">
                            <h4>Highest and Lowest Selling Item</h4>

                            <div class="row" style="background:#D9FFE3;color:#34C759;font-weight:600;padding:10px;border-radius:6px;">
                                <div class="col-md-6">Highest Selling Item</div>
                                <div class="col-md-6 text-right" id="highestSellingItem">18mm</div>
                            </div>

                            <div class="row mt-2" style="background:#FFECEA;color:#F14431;font-weight:600;padding:10px;border-radius:6px;">
                                <div class="col-md-6">Lowest Selling Item</div>
                                <div class="col-md-6 text-right" id="lowestSellingItem">12mm</div>
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
                                    <td id="topCustomerName">ABC Steels and Corporation</td>
                                    <td id="topCustomerQty">78.87</td>
                                    <td id="topCustomerAmount">28,98,456.00</td>
                                </tr>
                            </table>
                        </div>

                        <h5 style="color:#F14431">Least Purchased Customer</h5>
                        <div class="stock-table adj">
                            <table class="table table-striped">
                                <tr><td>Customer Name</td><td>Purchased Qty (TON)</td><td>Amount</td></tr>
                                <tr>
                                    <td id="leastCustomerName">Steel Hub</td>
                                    <td id="leastCustomerQty">8.87</td>
                                    <td id="leastCustomerAmount">1,98,456.00</td>
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
                            <option value="">All Regions</option>
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
                        <h2>34</h2>
                    </div>
                    <div class="text-right">
                        <h5>Team on Leave</h5>
                        <h2>15</h2>
                    </div>
                </div>
            </div>
            {{-- Stock insights --}}
                <div class="stock-cover mt-3 bg-white">
                    <h4>Stock Insights</h4>
                    <p style="color:#555;margin-bottom:8px;">This shows the current stock status for TATA Tiscon products.</p>

                    <div style="display:flex; gap:12px; margin-bottom:12px;">
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">Total <strong>12</strong> Products</div>
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">In Stock <strong>10</strong></div>
                        <div style="background:#f8f8f8;padding:10px;border-radius:8px;">Out Of Stock <strong>02</strong></div>
                    </div>

                    <div class="stock-table">
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Product</th><th>Product Type</th><th>Stock Quantity in TON</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                    <tr>
                                        <td>TATA Tiscon</td>
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
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="uniqueTarget">350</h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="uniqueAchieved">208</h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Influencer Visit</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="influencerTarget">400</h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="influencerAchieved">388</h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Aashiyana</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="aashiyanaTarget">26</h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="aashiyanaAchieved">24</h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                         <div class="dashboard-card card-bg-2 mt-2">
                            <h4>Products</h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h5>Target <i class="fa fa-bullseye"></i></h5><h2 id="productsTarget">100.00 <span style="font-size:12px;">TON</span></h2></div>
                                <div class="text-right"><h5>Achieved <i class="fa fa-trophy"></i></h5><h2 id="productsAchieved">98.87 <span style="font-size:12px;">TON</span></h2></div>
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
    // small demo behaviors: set today's date by default
    (function(){
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const dt = String(d.getDate()).padStart(2,'0');
        document.getElementById('fromDate').value = `${y}-${m}-${dt}`;
        document.getElementById('toDate').value = `${y}-${m}-${dt}`;
    })();
</script>
@endsection
