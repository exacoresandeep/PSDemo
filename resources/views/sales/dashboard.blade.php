@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    @php
        $currentMonth = \Carbon\Carbon::now()->format('n') - 1;
        $currentYear = \Carbon\Carbon::now()->year;
        $startYear = $currentYear - 3;
        $endYear = $currentYear + 5;
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    @endphp
    <div class="row mb-3">
        <div class="col-md-3"><strong>Select Month & Year :</strong></div>
        <div class="col-md-2">
            <select class="form-control" id="commonMonth">
                @foreach ($months as $key => $month)
                    <option value="{{ $key }}" {{ $key == $currentMonth ? 'selected' : '' }}>
                        {{ $month }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-control" id="commonYear">
                @for ($year = $startYear; $year <= $endYear; $year++)
                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endfor
            </select>
        </div>
        
        
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="dashboard-card card-bg-3">
                <div class="row">
                    <div class="col-md-8">
                        <h4>Total Lead Generated</h4>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-outline-secondary export-btn" id="exportLeadBtn">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h2 id="totalLead"> 0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card card-bg-4">
                <div class="row">
                    <div class="col-md-9">
                        <h4>Total Outstanding Payments</h4>
                    </div>
                    <div class="col-md-3 text-right">
                   
                        <button class="btn btn-outline-secondary export-btn" id="exportOutstandingBtn">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h2><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalOP">0</span></h2>
                    </div>
                    <div class="col-md-6 text-right">
                        <h5>Order Count</h5>
                        <h2 id="orderCountOP">0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card card-bg-5">
                <div class="row">
                    <div class="col-md-8">
                        <h4>Total Credit Note Amount</h4>
                    </div>
                    <div class="col-md-4 text-right">
                       
                        <button class="btn btn-outline-secondary export-btn" id="exportCreditBtn">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h2><i class="fa fa-inr" aria-hidden="true"></i> <span id="totalCredit">0</span></h2>
                    </div>
                    <div class="col-md-6 text-right">
                        <h5>Credit Note Count</h5>
                        <h2 id="orderCountCredit">0</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="target-cover sales-dash">
                <h4 style="margin-bottom:20px">Targets and Achievements</h4>
                
                <div class="row" style="margin-top:20px">
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2" style="margin-top:0px">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>Unique Leads</h4>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-outline-secondary export-btn" id="exportUniqueLeadsBtn">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                </div>
                            </div>
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
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2" style="margin-top:0px">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>Influencer Visit</h4>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-outline-secondary export-btn" id="exportInfluencerVisitBtn">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                </div>
                            </div>
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
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2" style="margin-top:0px">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>Aashiyana</h4>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-outline-secondary export-btn" id="exportAashiyanaBtn">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                </div>
                            </div>
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
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card card-bg-2" style="margin-top:0px">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><span class="productName"></span> Purchase</h4>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button class="btn btn-outline-secondary export-btn" id="exportTisconBtn">
                                        <i class="fa fa-upload"></i>
                                    </button>
                                </div>
                            </div>
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
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="dashboard-card card-bg-3 visit">
                <div class="row">
                    <div class="col-md-5">
                        <h4>Dealer Visit</h4>
                    </div>
                    <div class="col-md-7 text-right">
                        
                        <button class="btn btn-outline-secondary export-btn" id="exportDealerBtn">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 visit-date">
                        {{-- <p id="dealerVisitMonthYear">{{ $months[$currentMonth] }}, {{ $currentYear }}</p> --}}
                        <h2 id="totalDealerVisit"> 0</h2>
                    </div>
                </div>
            </div>
            <div class="dashboard-card card-bg-2 visit">
                <div class="row">
                    <div class="col-md-8">
                        <h4>Influencer Visit</h4>
                    </div>
                    <div class="col-md-4 text-right">
                       
                        <button class="btn btn-outline-secondary export-btn" id="exportInfluencerBtn">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 visit-date">
                        {{-- <p id="influencerVisitMonthYear">{{ $months[$currentMonth] }}, {{ $currentYear }}</p> --}}
                        <h2 id="totalInfluencerVisit"> 0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="dashboard-card bg-white hs-card sales-performance">
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-5">
                        <h4 style="min-height: 0;">Sales Performance By Region </h4>
                    </div>
                    <div class="col-md-7 text-right">
                        
                        <select class="form-control" id="employeeTypeSalesPerformance">
                            <option value="1">Sales Executive</option>
                            <option value="2">Area Sales Officer</option>
                            <option value="3">District Sales Manager</option>
                            <option value="4">Regional Sales Manager</option>
                            <option value="5">Sales Manager</option>
                        </select>
                        <select class="form-control" id="regionSalesPerformance">
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary export-btn" id="exportSalesBtn">
                            <i class="fa fa-upload"></i>
                        </button>
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
    const months = @json($months);
    function fetchLeadsData() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: '{{ route('sales.getLeadsData') }}',
            method: 'GET',
            data: {
                month,
                year
            },
            success: function(response) {
                $('#totalLead').text(response.totalLeads);
            },
            error: function(xhr) {
                alert('Error fetching leads data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }

    function fetchOutstandingPaymentsData() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: '{{ route('sales.getOutstandingPaymentsData') }}',
            method: 'GET',
            data: {
                month,
                year
            },
            success: function(response) {
                $('#totalOP').text(response.totalOutstandingAmount.toLocaleString('en-IN'));
                $('#orderCountOP').text(response.totalOutstandingInvoices);
                $('#opCollection').text(response.totalCollectionAmount.toLocaleString('en-IN'));
                $('#orderCountCollection').text(response.totalPaidInvoices);
            },
            error: function(xhr) {
                alert('Error fetching outstanding data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }

    function loadTargetAndAchievement() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: '{{ route('sales.overall-target-achievement') }}',
            method: 'GET',
            data: {
                month: month,
                year: year
            },
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
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: "{{ route('sales.credit-note-stats') }}",
            type: 'GET',
            data: {
                month: month,
                year: year
            },
            success: function(response) {
                // alert(response.data);
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

    function loadSalesPerformance() {
        let month = $('#commonMonth').val();
        let year = $('#commonYear').val();
        let employeeType = $('#employeeTypeSalesPerformance').val();
        let region = $('#regionSalesPerformance').val();
        $.ajax({
            url: "{{ route('sales.sales-performance') }}",
            method: 'GET',
            data: {
                month: month,
                year: year,
                employee_type_id: employeeType,
                region_id: region
            },
            success: function(response) {
                let tbody = '';
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(item) {
                        tbody += `
                        <tr>
                            <td>${item.region ?? '-'}</td>
                            <td>${item.employee_type ?? '-'}</td>
                            <td>${item.employee_name ?? '-'}</td>
                            <td>${parseFloat(item.total_invoice_quantity || 0).toFixed(2)}</td>
                            <td><i class="fa fa-inr" aria-hidden="true"></i> ${parseFloat(item.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        </tr>`;
                    });
                } else {
                    tbody = `<tr><td colspan="5" class="text-center">No data found</td></tr>`;
                }
                $('#salesPerformanceTableBody').html(tbody);
            },
            error: function(xhr) {
                console.error('Error loading sales performance:', xhr);
                $('#salesPerformanceTableBody').html(
                    `<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>`);
            }
        });
    }

    function fetchDealerVisitData() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: '{{ route('sales.getDealerVisitData') }}',
            method: 'GET',
            data: {
                month,
                year
            },
            success: function(response) {
                $('#totalDealerVisit').text(response.totalDealerVisit);
            },
            error: function(xhr) {
                alert('Error fetching dealer visit data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }

    function fetchInfluencerVisitData() {
         const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $.ajax({
            url: '{{ route('sales.getInfluencerVisitData') }}',
            method: 'GET',
            data: {
                month,
                year
            },
            success: function(response) {
                $('#totalInfluencerVisit').text(response.totalInfluencerVisit);
            },
            error: function(xhr) {
                alert('Error fetching influencer visit data: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }

    function updateVisitMonthYearText() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        $('#dealerVisitMonthYear').text(`${months[dealerMonth]}, ${dealerYear}`);
        const influencerMonth = $('#monthInfluencer').val();
        const influencerYear = $('#yearInfluencer').val();
        $('#influencerVisitMonthYear').text(`${months[influencerMonth]}, ${influencerYear}`);
    }

    $(document).ready(function() {
        fetchLeadsData();
        fetchOutstandingPaymentsData();
        loadTargetAndAchievement();
        fetchCreditNoteData();
        loadSalesPerformance();
        fetchDealerVisitData();
        fetchInfluencerVisitData();
        $('#monthLead, #yearLead').change(fetchLeadsData);
        $('#monthOP, #yearOP').change(fetchOutstandingPaymentsData);
        $('#monthTarget, #yearTarget').change(loadTargetAndAchievement);
        $('#monthCredit, #yearCredit').change(fetchCreditNoteData);
        $('#monthDealer, #yearDealer').change(function() {
            fetchDealerVisitData();
            updateVisitMonthYearText();
        });

        $('#monthInfluencer, #yearInfluencer').change(function() {
            fetchInfluencerVisitData();
            updateVisitMonthYearText();
        });
        $('#monthSalesPerformance, #yearSalesPerformance, #employeeTypeSalesPerformance, #regionSalesPerformance')
            .change(loadSalesPerformance);
    });

    $("#commonMonth,#commonYear").on('change', function() {
        // const month = $('#commonMonth').val();
        // const year = $('#commonYear').val();
        fetchLeadsData();
        fetchOutstandingPaymentsData();
        loadTargetAndAchievement();
        fetchCreditNoteData();
        loadSalesPerformance();
        fetchDealerVisitData();
        fetchInfluencerVisitData();
        // $('#monthLead, #monthOP, #monthCredit, #monthDealer, #monthInfluencer, #monthSalesPerformance, #monthTarget')
        //     .val(month).trigger('change');
        // $('#yearLead, #yearOP, #yearCredit, #yearDealer, #yearInfluencer, #yearSalesPerformance, #yearTarget')
        //     .val(year).trigger('change');
    });
    $('#exportLeadBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.exportLeads') }}?month=${month}&year=${year}`;
        window.location.href = url;
    });

    $('#exportOutstandingBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.outstanding') }}?month=${month}&year=${year}`;
        window.location.href = url;
    });

    $('#exportCreditBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.creditnote') }}?month=${month}&year=${year}`;
        window.location.href = url;
    });

    $('#exportSalesBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        let employeeType = $('#employeeTypeSalesPerformance').val();
        let region = $('#regionSalesPerformance').val();
        let url =
            `{{ route('sales.exportsalesperformance') }}?month=${month}&year=${year}&employee_type_id=${employeeType}&region_id=${region}`;
        window.location.href = url;
    });

    $('#exportDealerBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.exportDealerVisit') }}?month=${month}&year=${year}`;
        window.location.href = url;
    });

    $('#exportInfluencerBtn').on('click', function() {
       const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.exportInfluencerVisit') }}?month=${month}&year=${year}`;
        window.location.href = url;
    });

    $('#exportUniqueLeadsBtn').on('click', function() {
       const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.unique-leads') }}?month=${month}&year=${year}`;
        window.open(url, '_blank');
    });

    $('#exportInfluencerVisitBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.influencer-visits') }}?month=${month}&year=${year}`;
        window.open(url, '_blank');
    });

    $('#exportAashiyanaBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.aashiyana-orders') }}?month=${month}&year=${year}`;
        window.open(url, '_blank');
    });

    $('#exportTisconBtn').on('click', function() {
        const month = $('#commonMonth').val();
        const year = $('#commonYear').val();
        const url = `{{ route('sales.tiscon-orders') }}?month=${month}&year=${year}`;
        window.open(url, '_blank');
    });
</script>

@endsection
