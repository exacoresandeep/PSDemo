<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GreytHRController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Dealer\DealerController;
use App\Http\Controllers\Dealer\DealerOrderController;
use App\Http\Controllers\HanaController;
use App\Http\Controllers\SAPController;
use App\Http\Controllers\Logistics\DriverController;
use App\Http\Controllers\Logistics\AssistanceController;
use App\Http\Controllers\Logistics\MaintenanceController;
use App\Http\Controllers\Logistics\InspectionController;
use App\Http\Controllers\Logistics\TyreManagementController;
use App\Http\Controllers\ExpenseController;

    Route::post('/sap/outstanding', [\App\Http\Controllers\SapController::class, 'fetchOutstanding']);
    Route::post('/sap/creditnote', [\App\Http\Controllers\SapController::class, 'fetchCreditnote']);
    Route::post('/sap/downloadLedger', [\App\Http\Controllers\SapController::class, 'downloadLedger']);
    Route::post('/SalesOrderDetails', [SAPController::class, 'sendSalesOrder']);
    Route::prefix('v1')->group(function () {
        Route::post('test-push', [AuthController::class, 'testPush']);
    	Route::get('credit-note', [HanaController::class, 'getCreditNoteDetails']);
        Route::get('item-detail', [HanaController::class, 'getItemDetails']);
        Route::get('outstanding-payment', [HanaController::class, 'getOutstandingPayments']);
        Route::get('dealer-data', [HanaController::class, 'getDealerData']);
        Route::middleware('auth:sanctum')->post('reset-password', [AuthController::class, 'resetPassword']);
        Route::middleware('auth:sanctum')
            ->post('update-password', 
                [AuthController::class, 'updateEmployeePassword']);

        Route::middleware('auth:sanctum')
            ->post('dealer/update-password', 
                [AuthController::class, 'updateDealerPassword']);

        Route::post('loginCommon', [AuthController::class, 'loginCommon']);
        Route::prefix('dealer')->group(function () {
            Route::post('login', [DealerController::class, 'login']);
            Route::post('getCreditNoteForInvoice', [HanaController::class, 'getCreditNoteForInvoice']);
            Route::post('invoice-layout', [HanaController::class, 'fetchInvoiceLayout']);
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('target', [TargetController::class, 'getDealerTargets']);
                Route::post('store', [DealerController::class, 'store']);
                Route::get('profile', [DealerController::class, 'getDealerProfile']);
                Route::get('notificationList', [AuthController::class, 'notificationList']);
    	        Route::post('updateFcmToken', [AuthController::class, 'updateFcmToken']);
                Route::post('fileUpload', [AuthController::class, 'fileUpload']);
    	        Route::post('reset-password', [AuthController::class, 'resetPassword']);
                Route::get('order-types', [AuthController::class, 'getOrderTypes']);
                Route::get('payment-terms', [AuthController::class, 'getPaymentTerms']);
                Route::get('products', [AuthController::class, 'getProductForDealer']);
                Route::post('product-types', [AuthController::class, 'getProductTypes']);
                Route::get('getVehicleCategory', [AuthController::class, 'getVehicleCategory']);
                Route::post('getVehicleTypeByCategory', [AuthController::class, 'getVehicleTypeByCategory']);
    
                Route::prefix('orders')->group(function () {
                    Route::post('/', [DealerOrderController::class, 'store']); // Store  order
                    Route::get('/', [DealerOrderController::class, 'index']); // List orders by current user ID
                    Route::get('/{orderId}', [DealerOrderController::class, 'show']); // order details
                    // Route::post('/filter', [DealerOrderController::class, 'orderFilter']);
                    Route::post('/track-order', [DealerOrderController::class, 'trackOrder']);
                    Route::post('/monthly-transaction', [DealerOrderController::class, 'monthlySalesTransaction']);
                    Route::post('/monthly-targets', [DealerOrderController::class, 'monthlyTargetAchievement']);
                    Route::post('/outstanding-payments', [DealerOrderController::class, 'outstandingPaymentsList']);
                    Route::get('/outstanding-payments/{orderId}', [DealerOrderController::class, 'opDetails']);
                    Route::post('/order-request-list', [DealerOrderController::class, 'orderRequestList']);
                    Route::get('/order-request/{orderId}', [DealerOrderController::class, 'orderRequestDetails']);
                    Route::post('/order-request/{orderId}', [DealerOrderController::class, 'orderRequestStatusUpdate']);
                });
    
                Route::get('/payment-history', [DealerOrderController::class, 'paymentHistoryList']);
                Route::get('/payment-history/{orderId}', [DealerOrderController::class, 'paymentHistoryOrderDetails']);
                Route::get('/credit-notes', [DealerOrderController::class, 'creditNoteList']);
                Route::get('/credit-notes/{orderId}', [DealerOrderController::class, 'creditNoteDetails']);
                Route::get('support', [DealerOrderController::class, 'getSupport']);
                Route::get('credit-days', [DealerOrderController::class, 'getCreditDays']);
                Route::post('/product-price', [AuthController::class, 'getPriceByProduct']);
        	    // Route::get('/product-price/{product_type_id}', [AuthController::class, 'getPriceByProductType']);
        	    // Route::post('ledger/download', [DealerController::class, 'downloadLedgerF']);
        	    Route::post('ledger/downloadNew', [DealerController::class, 'downloadLedgerNew']);
        	    
                Route::post('logout', [DealerController::class, 'logout']);

            });
        });

        Route::post('loginCommon', [AuthController::class, 'loginCommon']);
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
         
            Route::post('/stock-details', [StockController::class, 'stockDetails']);
            Route::get('/travel-method-new', [ExpenseController::class, 'travelMethodNew']);
     	    Route::get('/travel-method', [ExpenseController::class, 'travelMethod']);
            Route::post('/day-expense', [ExpenseController::class, 'storeDayExpense']);
            Route::post('/expenseSummary', [ExpenseController::class, 'expenseSummary']);
            
    	    Route::post('updateFcmToken', [AuthController::class, 'updateFcmToken']);
    	    Route::get('notificationList', [AuthController::class, 'notificationList']);
            Route::post('employees', [EmployeeController::class, 'store']);
            Route::get('employee', [EmployeeController::class, 'show']);
            Route::get('employees/filter', [EmployeeController::class, 'filterEmployeesByType']);
            Route::post('/fileUpload', [AuthController::class, 'fileUpload']);
            Route::get('/filter', [AuthController::class, 'getFilteredOrders']);
    	    Route::get('/outstanding-payments', [OrderController::class, 'outstandingPaymentsStore']);
    	    Route::get('/getRegions', [AuthController::class, 'getRegions']);
            Route::prefix('orders')->group(function () {
                Route::post('/', [OrderController::class, 'store']); // Store  order
                Route::get('/', [OrderController::class, 'index']); // List orders by current user ID
                Route::get('{orderId}', [OrderController::class, 'show']); // order details
                Route::post('/filter', [OrderController::class, 'orderFilter']);
           
                Route::get('/dealer/outstanding-payments/search',[OrderController::class, 'searchOutstandingPayments']);
                Route::get('/dealer/outstanding-payments/search-by-invoice',[OrderController::class, 'searchOutstandingByInvoice']);
                Route::get('/dealer/outstanding-payments/product/{product_id}',[OrderController::class, 'outstandingPaymentsList']);
                Route::get('/dealer/outstanding-payments/dealer/{dealer_id}',[OrderController::class, 'viewOutstandingPaymentByDealer']);
                
                Route::get('/dealer/view-outstanding-payment/{orderId}', [OrderController::class, 'viewOutstandingPaymentOrderDetails']); 
                Route::post('/dealer/outstanding-payment/{id}/add-commitment', [OrderController::class, 'addOutstandingPaymentCommitment']);
                Route::post('/dealer/outstanding-payment/{id}/add-commitment-new', [OrderController::class, 'addOutstandingPaymentCommitmentNew']);

                Route::post('/send-for-approval/{orderId}', [OrderController::class, 'sendForApproval']);
                Route::post('/order-approval-list', [OrderController::class, 'orderApprovalList']);
                Route::get('/order-approval/search', [OrderController::class, 'orderApprovalSearch']);
                Route::get('/order-approval/{orderId}', [OrderController::class, 'orderApprovalDetails']);
                Route::post('getTotalCreditNotes', [OrderController::class, 'getTotalCreditNotes']);
             
            });
    
            Route::prefix('leads')->group(function () {
                Route::post('/', [LeadController::class, 'store']); // Create  lead
                Route::get('/', [LeadController::class, 'index']); // List Leads by current user ID
                Route::get('/search', [LeadController::class, 'searchLead']);
                Route::get('/{customer_type_id}/filter', [LeadController::class, 'getleadsFilter']);
                
                
                Route::get('/open-list', [LeadController::class, 'LeadOpenList']);
                Route::get('/won-list', [LeadController::class, 'LeadWonList']);
                Route::get('/followup-list', [LeadController::class, 'LeadFollowupList']);
                Route::get('/lost-list', [LeadController::class, 'LeadLostList']);
                
                Route::get('{leadId}', [LeadController::class, 'show']); // Leads details
                Route::post('{leadId}/update', [LeadController::class, 'updateLead']); // Update lead status
            });
            Route::prefix('leave')->group(function () {
                Route::post('/', [LeaveController::class, 'store']); // Create a  leave entry
                Route::get('/', [LeaveController::class, 'index']); // List leave entries
                Route::get('{month}', [LeaveController::class, 'leaveByMonth']); // Leave entries for the selected month
                Route::post('/claim/{id}', [LeaveController::class, 'updateClaim']);
            });
            Route::prefix('activities')->group(function () {
                Route::get('/', [ActivityController::class, 'index']); // List activities for the current employee
                Route::get('{activityId}', [ActivityController::class, 'viewActivity']); // View Activity
                Route::post('{activityId}/update', [ActivityController::class, 'updateActivity']); // Update activity
            });
            Route::prefix('target')->group(function () {
                Route::get('/{month}', [TargetController::class, 'getMonthlyTarget']);
                // Route::post('/list', [TargetController::class, 'indexList']);
                Route::post('/', [TargetController::class, 'getTargets']);
                Route::post('/getTotalTargetsAchievements', [TargetController::class, 'getTotalTargetsAchievements']);

                
                
            });
            Route::prefix('route')->group(function () {
                Route::get('/todays-routes', [RouteController::class, 'getTodaysTrip']);
                Route::post('/{dealerId}/update-activity', [RouteController::class, 'updateDealerTripActivity']);
                Route::get('/{dealerId}/view-trip-details', [RouteController::class, 'viewTripDetails']);
                Route::post('/{tripRouteId}/add-dealer', [RouteController::class, 'addDealerToRoute']);
                
                Route::get('/routeList', [RouteController::class, 'routeList']);
                Route::post('/routeReschedule', [RouteController::class, 'routeReschedule']);
                Route::get('/todaysRouteSchedule', [RouteController::class, 'todaysRouteSchedule']);
                Route::get('/currentWeekRoutes', [RouteController::class, 'currentWeekRoutes']);
                Route::post('/changeRouteStatus', [RouteController::class, 'changeRouteStatus']);
                Route::get('/{district_id}', [RouteController::class, 'getRoutesByDistrict']);
    
            });
            Route::prefix('attendance')->group(function () {
                Route::post('/punch-in', [AttendanceController::class, 'punchIn']);
                Route::post('/punch-out', [AttendanceController::class, 'punchOut']);
                // Route::get('/auto-punch-out', [AttendanceController::class, 'autoPunchOut']);
                Route::get('/today', [AttendanceController::class, 'getTodayAttendance']);
                Route::post('/summary', [AttendanceController::class, 'getsummary']);
                Route::post('/entryLeave', [AttendanceController::class, 'entryLeave']);
                Route::post('/LeaveType', [AttendanceController::class, 'LeaveType']);

            });
            Route::prefix('stock')->group(function () {
                Route::get('/stock-insights', [StockController::class, 'stockList']);
                Route::get('/product-stock/{product_details_id}', [StockController::class, 'getProductStockDetails']);
                Route::get('/stock-filter', [StockController::class, 'stockFilter']);
                Route::get('/getTotalStocks', [StockController::class, 'getTotalStocks']);
            });
            Route::prefix('report')->group(function () {
                //Sales ReportE
                Route::get('/sereport', [OrderController::class, 'salesExecutiveSalesReport']);
                Route::get('/sales-executive/{employee_id}/sales-report', [OrderController::class, 'salesReportDetails']);
    
                //Order Report
                Route::get('/order-report-listing', [OrderController::class, 'orderReportListing']);
                Route::get('/sales-executive/{employee_id}/order-report', [OrderController::class, 'orderReportDetails']);
    
                //Leads Report
                Route::get('/lead-report-listing', [OrderController::class, 'leadReportListing']);
                Route::get('/sales-executive/{employee_id}/lead-report', [OrderController::class, 'leadReportDetails']);
    
                //Activity Report
                Route::get('/activity-report-listing', [ActivityController::class, 'activityReportListing']);
                Route::get('/sales-executive/{employee_id}/activity-report', [ActivityController::class, 'activityReportDetails']);
    
                Route::get('/routes-report', [RouteController::class, 'getRoutesReport']);
                Route::get('/routes-details/{routeId}', [RouteController::class, 'getRouteDetails']);
                Route::post('/totalSalesLeadsSummary', [OrderController::class, 'totalSalesLeadsSummary']);
                Route::post('/filteredLeadsSummary', [OrderController::class, 'filteredLeadsSummary']);
                Route::post('/totalOPCollection', [OrderController::class, 'totalOPCollection']);
    
                //SM Exports
                Route::get('/sales-report', [OrderController::class, 'SalesReportExport']);
                Route::get('/leads-report', [OrderController::class, 'leadsReportExport']);
    
            });
            Route::get('lost-to-competitor', [AuthController::class, 'getLostToCompetitor']);
            Route::get('customer-types', [AuthController::class, 'getCustomerTypes']);
            Route::get('dealer-visit-customer-types', [AuthController::class, 'getDealerVisitCustomerTypes']);
            Route::get('order-types', [AuthController::class, 'getOrderTypes']);
            Route::get('dealers', [AuthController::class, 'getDealers']);
            Route::get('products', [AuthController::class, 'getProducts']);
            Route::post('product-types', [AuthController::class, 'getProductTypes']);
            // Route::get('/product-price/{product_type_id}', [AuthController::class, 'getPriceByProductType']);
            Route::post('/product-price', [AuthController::class, 'getPriceByProduct']);  // New
            Route::get('product-rate', [AuthController::class, 'getProductRate']);
            Route::get('leave-types', [AuthController::class, 'getLeaveTypes']);
            Route::get('payment-terms', [AuthController::class, 'getPaymentTerms']);
            Route::get('credit-days', [AuthController::class, 'getCreditDays']); // New
            Route::get('scheme', [AuthController::class, 'getScheme']); // New
            Route::get('dealer-visit-purpose', [AuthController::class, 'getDealerVisitPurposes']); // New
            Route::get('dealer-activity-item', [AuthController::class, 'getDealerActivityItem']); // New
            Route::get('brands', [AuthController::class, 'getBrands']); // New
            Route::post('dealer-visit/create', [OrderController::class, 'createDealerVisit']); // New
            Route::get('dealer-visit-listing', [OrderController::class, 'dealerVisitListing']); // New
            Route::get('dealer-visits/{id}/details', [OrderController::class, 'dealerVisitDetails']); // New
            Route::get('get-aso-by-dealer', [AuthController::class, 'getASOByDealerID']); // New
            Route::get('get-type-of-influencer', [AuthController::class, 'getTypeofInfluencer']); // New
            Route::get('get-influencer-purpose-visit', [AuthController::class, 'getInfluencerPurposeofVisit']); // New
            Route::get('get-lead-type', [AuthController::class, 'getLeadType']); // New
            Route::get('get-influencer-status', [AuthController::class, 'getInfluencerVisitStatus']); // New
            Route::post('create-influencer-visit', [LeadController::class, 'createInfluencerVisit']); //New
            Route::post('update-influencer-visit/{visitId}', [LeadController::class, 'updateInfluencerVisit']); //New
            Route::get('influencer-visit-listing', [LeadController::class, 'influencerVisitListing']); //New
            Route::get('influencer-visit-details/{id}', [LeadController::class, 'influencerVisitDetails']); //New
            Route::get('/influencer/open-list', [LeadController::class, 'influencerOpenList']);
            Route::get('/influencer/won-list', [LeadController::class, 'influencerWonList']);
            Route::get('/influencer/lost-list', [LeadController::class, 'influencerLostList']);
            Route::get('/influencer/search', [LeadController::class, 'influencerSearch']);
            Route::get('districts', [AuthController::class, 'getDistricts']);
            Route::get('employee/products', [EmployeeController::class, 'getEmployeeProducts']);
    
            Route::post('logout', [AuthController::class, 'logout']);
            
            Route::get('getVehicleCategory', [AuthController::class, 'getVehicleCategory']);
            Route::post('getVehicleTypeByCategory', [AuthController::class, 'getVehicleTypeByCategory']);
    
            Route::get('trackOrder', [AuthController::class, 'trackOrder']);
            Route::get('dealerOrderList', [OrderController::class, 'dealerOrderList']);
            Route::get('dealerOrderDetails/{orderId}', [OrderController::class, 'dealerOrderDetails']); 
            Route::post('dealerOrderStatusUpdate/{orderId}', [OrderController::class, 'dealerOrderStatusUpdate']); 
            Route::get('dealerOrderFilter', [OrderController::class, 'dealerOrderFilter']); 
    
            Route::post('getTotalSalesPerformance', [OrderController::class, 'getTotalSalesPerformance']);
            Route::post('getMostLeastPurchaseCustomer', [OrderController::class, 'getMostLeastPurchaseCustomer']);
            Route::post('highest-lowest-selling-items', [OrderController::class, 'getHighestLowestSellingItems']);
            
            Route::get('exportOP', [OrderController::class, 'exportOutstandingPayments']);
            Route::get('exportTotalOP', [OrderController::class, 'exportTotalOPCollection']);
            Route::get('export-credit-notes', [OrderController::class, 'exportCreditNotes']);
    
    	    Route::get('unique-leads', [OrderController::class, 'getUniqueLeads']);
            Route::get('influencer-visits', [OrderController::class, 'getInfluencerVisits']);
            Route::get('aashiyana-orders', [OrderController::class, 'getAashiyanaOrders']);
            Route::get('tiscon-orders', [OrderController::class, 'getTisconOrders']);
            Route::get('dealer-visits', [OrderController::class, 'getDealerVisits']);
            Route::get('influencer-visit-details', [OrderController::class, 'getInfluencerVisitDetails']);
            Route::post('dealer-visit-data', [OrderController::class, 'getDealerVisitData']);
            Route::post('influencer-visit-data', [OrderController::class, 'getInfluencerVisitData']);
            Route::post('dealer-influencer-visit-data', [OrderController::class, 'getDealerInfluencerVisitData']);
            
    
        });
    
        //Driver
        Route::prefix('driver')->group(function () {
            Route::post('login', [DriverController::class, 'login']);
    
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('profile', [DriverController::class, 'getDriverProfile']);
                Route::post('updateFcmToken', [DriverController::class, 'updateFcmToken']);
                Route::post('reset-password', [DriverController::class, 'resetPassword']);
                Route::post('/fileUpload', [AuthController::class, 'fileUpload']);
                Route::get('notificationList', [AuthController::class, 'notificationList']);
                Route::get('assistance-types', [DriverController::class, 'getAssistanceTypes']);
                Route::get('maintenance-types', [DriverController::class, 'getMaintenanceTypes']);
                Route::get('expense-types', [DriverController::class, 'getExpenseTypes']);
    
                Route::get('driver-todays-trip', [DriverController::class, 'getDriverTodaysTrip']);
    
                Route::get('trip/{tripId}/log', [DriverController::class, 'showTripLog']);
                Route::post('trip/{tripId}/update', [DriverController::class, 'updateTrip']);
    
                // Expense APIs
                Route::get('trip/{tripId}/expenses', [DriverController::class, 'getTripExpenses']);
                Route::post('trip/{tripId}/expenses/add', [DriverController::class, 'addTripExpense']);
                Route::get('trip/expenses/{expenseId}', [DriverController::class, 'getTripExpenseDetails']);
    
                Route::get('/trip/{tripId}/assistances', [AssistanceController::class, 'getAssistanceList']);
                Route::post('/trip/{tripId}/assistances', [AssistanceController::class, 'addAssistance']);
                Route::get('/assistances/{assistanceId}', [AssistanceController::class, 'viewAssistanceDetails']);
    
                // Maintenance
                Route::post('/assistances/{assistanceId}/maintenance', [MaintenanceController::class, 'addMaintenance']);
                Route::post('/maintenance/{maintenanceId}/update', [MaintenanceController::class, 'updateMaintenanceStatus']);
                Route::get('/maintenance/contact', [MaintenanceController::class, 'getMaintenanceContact']);
    
                Route::get('assigned-trips', [DriverController::class, 'assignedTrips']);
                Route::get('completed-trips', [DriverController::class, 'completedTrips']);
                Route::get('completed-trips/{tripId}', [DriverController::class, 'completedTripDetails']);
                Route::get('assigned-trips/{tripId}', [DriverController::class, 'assignedTripDetails']);
    
                Route::get('trip/{tripId}/details', [DriverController::class, 'viewTripDetails']);
                Route::post('trip/{tripId}/restart', [DriverController::class, 'restartTrip']);
    
                Route::post('logout', [DriverController::class, 'logout']);
    
            });
        });
        
        //Maintenance App
        Route::prefix('maintenance')->group(function () {
            Route::post('login', [MaintenanceController::class, 'login']);
    
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('profile', [MaintenanceController::class, 'getEmployeeProfile']);
                Route::post('updateFcmToken', [MaintenanceController::class, 'updateFcmToken']);
                Route::post('fileUpload', [MaintenanceController::class, 'fileUpload']);
                Route::get('notificationList', [AuthController::class, 'notificationList']);
                Route::post('logout', [MaintenanceController::class, 'logout']);
                Route::get('getMaintenanceMasterData', [MaintenanceController::class, 'getMaintenanceMasterData']);
                Route::get('tyre-types', [MaintenanceController::class, 'getTyreTypes']);
                Route::get('tyre-categories', [InspectionController::class, 'getTyreCategories']);
                //  Assistance Requests
                Route::get('pendingAssistanceRequests', [MaintenanceController::class, 'pendingAssistanceRequests']);
                Route::get('completedAssistanceRequests', [MaintenanceController::class, 'completedAssistanceRequests']);
                Route::get('viewAssistanceRequests/{id}', [MaintenanceController::class, 'viewAssistanceRequests']);
                Route::post('updateAssistanceDetails/{assistance_id}', [MaintenanceController::class, 'updateAssistanceDetails']);
                Route::post('job-cards/{maintenanceReportId}', [MaintenanceController::class, 'updateJobCard']);
                Route::post('completeAssistace', [MaintenanceController::class, 'completeAssistace']);
                Route::get('axle-types', [InspectionController::class, 'getAxleTypes']);
    
                //  Maintenance Types & Stock
                Route::get('maintanceType', [MaintenanceController::class, 'maintanceType']);
                Route::get('stockStatus', [MaintenanceController::class, 'stockStatus']);
    
                //  Complaints / Employees
                Route::get('complaintsList', [MaintenanceController::class, 'complaintsList']);
                Route::get('employeeList', [MaintenanceController::class, 'employeeList']);
    
                //  Vehicle Services
                Route::post('getVehicleDetails', [MaintenanceController::class, 'getVehicleDetails']);
                Route::post('createService', [MaintenanceController::class, 'createService']);
                Route::post('completeService', [MaintenanceController::class, 'completeService']);
                Route::get('getServiceAlerts', [MaintenanceController::class, 'getServiceAlerts']);
                Route::get('getServiceRecords', [MaintenanceController::class, 'getServiceRecords']);
                Route::post('updateServiceAlerts', [MaintenanceController::class, 'updateServiceAlerts']);
                Route::post('completeServiceAlerts', [MaintenanceController::class, 'completeServiceAlerts']);
                Route::get('viewServiceAlertDetials', [MaintenanceController::class, 'viewServiceAlertDetials']);
                Route::get('viewServiceRecordDetails', [MaintenanceController::class, 'viewServiceRecordDetails']);
                Route::get('viewServiceRecordHistory', [MaintenanceController::class, 'viewServiceRecordHistory']);
    
                //  Tyre Management
                Route::get('tyreManagmentList', [MaintenanceController::class, 'tyreManagmentList']);
                Route::get('getTyreType', [MaintenanceController::class, 'getTyreType']);
                Route::post('requestTyreChange', [MaintenanceController::class, 'requestTyreChange']);
                Route::get('viewTyreChangeRequest', [MaintenanceController::class, 'viewTyreChangeRequest']);
    
                //  Inspected Services
                // Route::get('openInspectedServiceList', [MaintenanceController::class, 'openInspectedServiceList']);
                // Route::get('viewOpenInspectedServiceList', [MaintenanceController::class, 'viewOpenInspectedServiceList']);
                // Route::get('closedInspectedServiceLists', [MaintenanceController::class, 'closedInspectedServiceLists']);
                // Route::get('viewClosedInspectedServiceLists', [MaintenanceController::class, 'viewClosedInspectedServiceLists']);
                // Route::post('updateInspectedService', [MaintenanceController::class, 'updateInspectedService']);
    
                //  Stock Insights
                Route::get('getStockInsightList', [MaintenanceController::class, 'getStockInsightList']);
                Route::get('viewStockInsightDetails', [MaintenanceController::class, 'viewStockInsightDetails']);
    
                
                Route::get('service-types', [MaintenanceController::class, 'getServiceTypes']);
                Route::get('open-inspected-service-list', [MaintenanceController::class, 'openInspectedServiceLists']);
                Route::get('open-inspected-service/{inspection_id}', [MaintenanceController::class, 'viewOpenInspectedServiceLists']);
                Route::post('update-inspected-service/{inspection_id}', [MaintenanceController::class, 'updateInspectedServices']);
                Route::get('closedInspectedServiceLists', [MaintenanceController::class, 'closedInspectedServiceLists']);
                Route::get('viewClosedInspectedServiceLists/{inspection_id}', [MaintenanceController::class, 'viewClosedInspectedServiceLists']);
                Route::get('complaints-types', [MaintenanceController::class, 'getComplaintsType']);
    
                Route::get('getServiceAlertsList', [MaintenanceController::class, 'getServiceAlertsList']);
                Route::get('viewServiceAlertDetails/{vehicle_id}', [MaintenanceController::class, 'viewServiceAlertDetails']);
                Route::post('updateServiceAlerts', [MaintenanceController::class, 'updateServiceAlerts']);
                Route::post('completeServiceAlerts', [MaintenanceController::class, 'completeServiceAlerts']);
                Route::get('getServiceRecordsList', [MaintenanceController::class, 'getServiceRecordsList']);
                Route::get('viewServiceRecordDetails/{vehicle_id}', [MaintenanceController::class, 'viewServiceRecordDetails']);
                
                
                Route::get('get-tyre-details', [TyreManagementController::class, 'getTyreDetailsByStencilCode']);
                Route::get('tyre-management-list', [TyreManagementController::class, 'tyreManagementList']);
                Route::post('request-tyre-change', [TyreManagementController::class, 'requestTyreChange']);
                Route::get('view-tyre-change-request/{id}', [TyreManagementController::class, 'viewTyreChangeRequest']);
                Route::post('tyre-change-request/complete/{id}', [TyreManagementController::class, 'markComplete']);
            });
    
    
        });

        //Inspection App
        
        Route::prefix('inspection')->group(function () {
            Route::post('login', [InspectionController::class, 'login']);
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('profile', [InspectionController::class, 'getProfile']);
                Route::post('updateFcmToken', [InspectionController::class, 'updateFcmToken']);
                Route::post('reset-password', [InspectionController::class, 'resetPassword']);
                Route::get('notificationList', [AuthController::class, 'notificationList']);
                Route::post('fileUpload', [AuthController::class, 'fileUpload']);
    
                Route::get('tyre-types', [InspectionController::class, 'getTyreTypes']);
                Route::get('tyre-categories', [InspectionController::class, 'getTyreCategories']);
                Route::get('tyre-conditions', [InspectionController::class, 'getTyreConditions']);
                Route::get('engine-oil-levels', [InspectionController::class, 'getEngineOilLevels']);
                Route::get('coolant-levels', [InspectionController::class, 'getCoolantLevels']);
                // Route::get('vehicle-greasing-conditions', [InspectionController::class, 'getVehicleGreasingCondition']);
                // Route::get('washing-conditions', [InspectionController::class, 'getWashingCondition']);
                // Route::get('mirror-conditions', [InspectionController::class, 'getMirrorConditions']);
                // Route::get('indicator-light-conditions', [InspectionController::class, 'getIndicatorLightConditions']);
                // Route::get('battery-conditions', [InspectionController::class, 'getBatteryConditions']);
                // Route::get('mud-flap-conditions', [InspectionController::class, 'getMudFlapConditions']);
                // Route::get('clutch-fluid-conditions', [InspectionController::class, 'getClutchFluidConditions']);
                Route::get('all-vehicle-conditions', [InspectionController::class, 'getAllConditions']);
                Route::get('axle-types', [InspectionController::class, 'getAxleTypes']);
                Route::get('regular-inspection-list', [InspectionController::class, 'regularInspectionList']);
                Route::get('view-regular-inspection/{vehicle_id}', [InspectionController::class, 'viewRegularInspection']);
                Route::post('submit-vehicle-inspection/{vehicle_id}', [InspectionController::class, 'submitInspection']);
                
                Route::get('pre-trip-inspection-list', [InspectionController::class, 'preTripInspectionList']);
                Route::get('view-pre-trip-inspection/{vehicle_id}', [InspectionController::class, 'viewPreTripInspection']);
                Route::post('update-pre-trip-inspection/{vehicle_id}', [InspectionController::class, 'updatePreTripInspection']);
                
                Route::get('post-trip-inspection-list', [InspectionController::class, 'postTripInspectionList']);
                Route::get('view-post-trip-inspection/{vehicle_id}', [InspectionController::class, 'viewPostTripInspection']);
                Route::post('update-post-trip-inspection/{vehicle_id}', [InspectionController::class, 'updatePostTripInspection']);
                
                Route::get('post-service-inspection-list', [InspectionController::class, 'postServiceInspectionList']);
                Route::get('view-post-service-inspection/{vehicle_id}', [InspectionController::class, 'viewPostServiceInspection']);
                Route::post('update-post-service-inspection/{vehicle_id}', [InspectionController::class, 'updatePostServiceInspection']);
                
                Route::get('get-dashboard-details', [InspectionController::class, 'getDashboardDetails']);
                Route::get('complete', [InspectionController::class, 'completeInspectionList']);
                Route::get('complete/{id}', [InspectionController::class, 'viewCompleteInspection']);
                
                Route::post('logout', [InspectionController::class, 'logout']);
    
            });
        });
        
        Route::get('getLeadSource', [AuthController::class, 'getLeadSource']);
        Route::get('getConstructionTypes', [AuthController::class, 'getConstructionTypes']);
        Route::get('getTypeOfVisit', [AuthController::class, 'getTypeOfVisit']);
        Route::get('getStageOfConstruction', [AuthController::class, 'getStageOfConstruction']);
        Route::get('getLeadScore', [AuthController::class, 'getLeadScore']);
        Route::get('getLeadStatus', [AuthController::class, 'getLeadStatus']);
        Route::get('getQuantityType', [AuthController::class, 'getQuantityType']);
        Route::get('getQuantityType', [AuthController::class, 'getQuantityType']);
        Route::post('calculateQuantityDetails', [AuthController::class, 'calculateQuantityDetails']);

        
});


Route::get('/greythr/access-token', [GreytHRController::class, 'getAccessToken']);
