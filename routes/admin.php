<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DealerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Logistics\LogisticsController;
use App\Http\Controllers\Logistics\TripController;
use App\Http\Controllers\Logistics\BataController;

use App\Http\Controllers\Logistics\DriverController;

use App\Http\Controllers\Logistics\VehiclesController;
use App\Http\Controllers\SchemeController;
use App\Http\Controllers\Logistics\OperationsController;
use App\Http\Controllers\UserManagementController;

    Route::get('/', [AdminController::class, 'login'])->name('login');
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('doLogin');


Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware('auth');

    Route::prefix('sales')->middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('sales.dashboard');
    //  Route::get('/dashboard', [DashboardController::class, 'salesDashboard'])->name('sales.dashboard');
        Route::prefix('activity')->group(function () {
            // Route::get('/type', [ActivityController::class, 'activityTypeIndex'])->name('sales.activity.activity-type-index');
            Route::get('/type', [ActivityController::class, 'activityTypeIndex'])->name('sales.activity.created-activities');
            Route::get('/type/list', [ActivityController::class, 'getActivityTypes'])->name('sales.activity.activity-type-list');
            Route::post('/type/store', [ActivityController::class, 'activityTypeStore'])->name('sales.activity.type.store');
            Route::get('/type/edit/{activity_type}', [ActivityController::class, 'editActivityType'])->name('sales.activity.type.edit');
            Route::put('/type/update/{activity_type}', [ActivityController::class, 'updateActivityType'])->name('sales.activity.type.update');
            Route::delete('/type/delete/{activity_type}', [ActivityController::class, 'deleteActivityType'])->name('sales.activity.type.delete');

            // Route::get('/', [ActivityController::class, 'activityIndex'])->name('sales.activity.index');
            Route::get('/', [ActivityController::class, 'activityIndex'])->name('sales.activity.assigned-activities');
            Route::delete('/activity-type/question-label/{id}', [ActivityController::class, 'deleteQuestionLabel'])
            ->name('sales.activity.type.questionlabel.delete');
            Route::get('/list', [ActivityController::class, 'list'])->name('sales.activity.list');
            Route::post('/store', [ActivityController::class, 'store'])->name('sales.activity.store');
            Route::get('/view/{activity}', [ActivityController::class, 'view'])->name('sales.activity.view');
            Route::get('/edit/{activity}', [ActivityController::class, 'edit'])->name('sales.activity.edit');
            Route::put('/update/{activity}', [ActivityController::class, 'update'])->name('sales.activity.update');
            Route::delete('/delete/{activity}', [ActivityController::class, 'delete'])->name('sales.activity.delete');
            
            Route::get('employees-by-district-type/{district_id}/{employee_type_id}', [ActivityController::class, 'getEmployeesByDistrictType']);
            Route::get('dealers-by-employee/{employee_id}', [ActivityController::class, 'getDealersByEmployee']);
        });
        Route::prefix('routes')->group(function () {
            Route::get('/', [RouteController::class, 'assignedIndex'])->name('sales.route.index');
            Route::get('/list', [RouteController::class, 'assignedList'])->name('sales.route.assigned.list');
            Route::post('/store', [RouteController::class, 'storeAssignedRoute'])->name('sales.route.assigned.store');
            Route::get('/edit/{id}', [RouteController::class, 'editAssignedRoute'])->name('sales.route.assigned.edit');
            Route::put('/update/{id}', [RouteController::class, 'updateAssignedRoute'])->name('sales.route.assigned.update');
            Route::delete('/delete/{id}', [RouteController::class, 'deleteAssignedRoute'])->name('sales.route.assigned.delete');

            // Sub-routes
            Route::get('/type', [RouteController::class, 'routeIndex'])->name('sales.route.type.index');
            Route::get('/type/list', [RouteController::class, 'routeList'])->name('sales.route.type.list');
            Route::post('/type/store', [RouteController::class, 'routeStore'])->name('sales.route.type.store');
            Route::get('/type/edit/{route_id}', [RouteController::class, 'editRoute'])->name('sales.route.type.edit');
            Route::put('/type/update/{route_id}', [RouteController::class, 'updateRoute'])->name('sales.route.type.update');
            Route::delete('/type/delete/{route_id}', [RouteController::class, 'deleteRoute'])->name('sales.route.type.delete');

            Route::get('/get-employees-by-type', [RouteController::class, 'getEmployeesByType'])->name('sales.get-employees-by-type');
            Route::get('/get-all-locations', [RouteController::class, 'getAllLocations'])->name('sales.get-all-locations');
            Route::get('/get-dealers-by-locations', [RouteController::class, 'getDealersByLocations'])->name('sales.get-dealers-by-locations');

        });

        Route::prefix('targets')->group(function () {
            Route::get('/', [TargetController::class, 'index'])->name('sales.target.index');
            Route::post('/list', [TargetController::class, 'targetList'])->name('sales.target.list');
            Route::post('/store', [TargetController::class, 'store'])->name('sales.target.store');
            Route::post('/update', [TargetController::class, 'update'])->name('sales.target.update');
            Route::get('/view/{id}', [TargetController::class, 'viewTargets'])->name('sales.target.view');
            Route::delete('/delete/{id}', [TargetController::class, 'destroy'])->name('sales.target.delete');
            Route::get('/{id}', [TargetController::class, 'getTargetDetails'])->name('sales.target.details');
            Route::get('/getVisitCount/{employeeType}/employee/{employee}', [TargetController::class, 'getVisitCount'])->name('sales.getVisitCount');
        });

        Route::prefix('scheme')->group(function () {
            Route::get('/', [SchemeController::class, 'index'])->name('sales.scheme.index');
            Route::post('/list', [SchemeController::class, 'schemeList'])->name('sales.scheme.list');
            Route::post('/store', [SchemeController::class, 'store'])->name('sales.scheme.store');
            Route::post('/update', [SchemeController::class, 'update'])->name('sales.scheme.update');
            Route::get('/{id}', [SchemeController::class, 'getSchemeDetails'])->name('sales.scheme.details');

        });

        Route::prefix('dayend')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('sales.dayend.index');
            Route::post('/list', [ExpenseController::class, 'list'])->name('sales.dayend.list');
            Route::get('/dayend-export', [ExpenseController::class, 'export'])->name('sales.dayend.export');

        });
        
        Route::prefix('employee')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('sales.employee.index');
            Route::post('/list', [EmployeeController::class, 'list'])->name('sales.employee.list');
            Route::get('/export', [EmployeeController::class, 'export'])->name('sales.employee.export');
            Route::get('/edit', [EmployeeController::class, 'edit'])->name('sales.employee.edit');
            Route::delete('/delete/{id}', [EmployeeController::class, 'destroy'])->name('sales.employee.delete');
            Route::get('/get-employees-ajax', [EmployeeController::class, 'getEmployeesAjax'])->name('sales.employee.getEmployeesAjax');
            Route::post('/import-sap', [EmployeeController::class, 'importSAP'])->name('sales.employee.importSAP');
            Route::post('/create', [EmployeeController::class, 'create'])->name('sales.employee.create');
            Route::get('/edit/{id}', [EmployeeController::class, 'edit'])->name('sales.employee.edit');
            Route::put('/update/{id}', [EmployeeController::class, 'update'])->name('sales.employee.update');

        });

        Route::prefix('attendance')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('sales.attendance.index');
            Route::post('/list', [AttendanceController::class, 'list'])->name('sales.attendance.list');
            Route::get('/attendance-export', [AttendanceController::class, 'exportAttendance'])->name('attendance.export');

        });

        Route::get('/get-employees/{employeeTypeId}', [EmployeeController::class, 'getEmployeesByType'])->name('sales.getEmployees');
        Route::get('/employees-by-dealer/{dealer_id}', [ActivityController::class, 'getEmployeesByDealer']);
        Route::get('/dealers-by-district/{district_id}', [ActivityController::class, 'getDealersByDistrict']);
        Route::get('/get-districts', [RouteController::class, 'getDistricts'])->name('sales.get-districts');
        Route::get('/get-employees', [RouteController::class, 'getEmployees'])->name('sales.get-employees');
        Route::get('/get-locations', [RouteController::class, 'getLocations'])->name('sales.get-locations');
        Route::get('/leads-data', [DashboardController::class, 'getLeadsData'])->name('sales.getLeadsData');
        Route::get('/outstanding-payments-data', [DashboardController::class, 'getOutstandingPaymentsData'])->name('sales.getOutstandingPaymentsData');
        Route::get('/overall-target-achievement', [DashboardController::class, 'getOverallTargetAndAchievement'])->name('sales.overall-target-achievement');
        Route::get('/credit-note-stats', [DashboardController::class, 'getCreditNoteStats'])->name('sales.credit-note-stats');
        Route::get('/sales-performance', [DashboardController::class, 'getSalesPerformance'])->name('sales.sales-performance');
        Route::get('/export-leads', [DashboardController::class, 'exportLeads'])->name('sales.exportLeads');
        Route::get('/export-outstanding-payments', [DashboardController::class, 'exportOutstandingPayments'])->name('sales.outstanding');
        Route::get('/export-credit-notes', [DashboardController::class, 'exportCreditNotes'])->name('sales.creditnote');
        Route::get('/export-sales-performance', [DashboardController::class, 'exportSalesPerformance'])->name('sales.exportsalesperformance');
        Route::get('/sales-data', [DashboardController::class, 'getSalesData'])->name('sales.getSalesData'); 
        Route::get('/sales-quantity', [DashboardController::class, 'getSalesQuantity'])->name('sales.getSalesQuantity'); 
        Route::get('/dealer-visit-data', [DashboardController::class, 'getDealerVisitData'])->name('sales.getDealerVisitData');
        Route::get('/influencer-visit-data', [DashboardController::class, 'getInfluencerVisitData'])->name('sales.getInfluencerVisitData');
        Route::get('/export-dealer-visit', [DashboardController::class, 'exportDealerVisit'])->name('sales.exportDealerVisit');
        Route::get('/export-influencer-visit', [DashboardController::class, 'exportInfluencerVisit'])->name('sales.exportInfluencerVisit');
        Route::get('/export/unique-leads', [DashboardController::class, 'exportUniqueLeads'])->name('sales.unique-leads');
        Route::get('/export/influencer-visits', [DashboardController::class, 'exportInfluencerVisits'])->name('sales.influencer-visits');
        Route::get('/export/aashiyana-orders', [DashboardController::class, 'exportAashiyanaOrders'])->name('sales.aashiyana-orders');
        Route::get('/export/tiscon-orders', [DashboardController::class, 'exportTisconOrders'])->name('sales.tiscon-orders');
    });

    Route::prefix('accounts')->middleware('auth')->group(function () {
        //Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('accounts.dashboard');
        Route::get('/dashboard', [DashboardController::class, 'accountsDashboard'])->name('accounts.dashboard');   
        Route::prefix('orders')->group(function () {
            Route::get('/', [AccountsController::class, 'index'])->name('accounts.orders.index');
            Route::get('/list', [AccountsController::class, 'orderList'])->name('accounts.orders.list');
            Route::get('/view/{id}', [AccountsController::class, 'viewOrder'])->name('view'); 
            Route::post('/approve/{id}', [AccountsController::class, 'approveOrder'])->name('accounts.orders.approve');
            Route::post('/reject/{id}', [AccountsController::class, 'rejectOrder'])->name('accounts.orders.reject');
            Route::get('/export', [AccountsController::class, 'export'])->name('accounts.orders.export');
        });
        Route::prefix('price-management')->group(function () {
            Route::get('/', [AccountsController::class, 'PriceIndex'])->name('accounts.price.index');
            Route::post('/store', [AccountsController::class, 'priceStore'])->name('accounts.price.store');
            Route::get('/edit', [AccountsController::class, 'editPrice'])->name('accounts.price.edit');
            Route::post('/update', [AccountsController::class, 'updatePrice'])->name('accounts.price.update');
            Route::get('/list', [AccountsController::class, 'priceList'])->name('accounts.price.list');
            Route::get('/show', [AccountsController::class, 'priceShow'])->name('accounts.price.show');
            Route::get('/get-product-types', [AccountsController::class, 'getProductTypes'])->name('get.product.types');
            Route::get('/get-product-types-pm', [AccountsController::class, 'getProductTypesforPM'])->name('get.product.typespm');
        });
    });
     Route::get('/load-product', [AdminController::class, 'loadProduct'])->name('loadProduct');

    Route::prefix('admin')->middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::prefix('users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('admin.users.index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('admin.users.create');
            Route::get('/list', [UserManagementController::class, 'list'])->name('admin.users.list');
            Route::post('/store', [UserManagementController::class, 'store'])->name('admin.users.store');
            Route::get('/edit/{id}', [UserManagementController::class, 'edit'])->name('admin.users.edit');
            Route::post('/update/{id}', [UserManagementController::class, 'update'])->name('admin.users.update');
            Route::delete('/delete/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
            Route::get('/check-username', [UserManagementController::class, 'checkUsername'])->name('admin.users.check-username');

            Route::get('/employees', [AdminController::class, 'employeeIndex'])->name('admin.users.employee-index');
            Route::get('/employees/list', [AdminController::class, 'employeeList'])->name('admin.users.employee-list');
            Route::post('/employees/import', [AdminController::class, 'importEmployees'])->name('admin.users.import-employees');
        });
    });
    Route::prefix('logistics')->middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('logistics.dashboard');
        Route::prefix('orders')->group(function () {
            Route::get('/', [LogisticsController::class, 'index'])->name('logistics.orders.index');
            Route::get('/getSalesOrders', [LogisticsController::class, 'getSalesOrders'])->name('logistics.orders.getSalesOrders');
        });
        Route::prefix('drivers')->group(function () {
            Route::get('/', [DriverController::class, 'index'])->name('logistics.drivers.index');
            Route::get('/create', [DriverController::class, 'create'])->name('logistics.drivers.create');
            Route::post('/store', [DriverController::class, 'store'])->name('logistics.drivers.store');
            Route::get('/getData', [DriverController::class, 'getData'])->name('logistics.drivers.getData');
            Route::get('/{driver}', [DriverController::class, 'show'])->name('logistics.drivers.show');
            Route::get('/edit/{driver}', [DriverController::class, 'edit'])->name('logistics.drivers.edit');
            Route::put('/update/{driver}', [DriverController::class, 'update'])->name('logistics.drivers.update');
            Route::delete('/destroy/{driver}', [DriverController::class, 'destroy'])->name('logistics.drivers.destroy');
        });
        Route::prefix('vehicles')->group(function () {
            Route::get('/', [VehiclesController::class, 'index'])->name('logistics.vehicles.index');
            Route::get('/vehicle-types/{categoryId}', [VehiclesController::class, 'getVehicleTypesByCategory'])->name('vehicle.types.by.category');
            Route::get('/create', [VehiclesController::class, 'create'])->name('logistics.vehicles.create');
            Route::post('/store', [VehiclesController::class, 'store'])->name('logistics.vehicles.store');
            Route::get('/getData', [VehiclesController::class, 'getData'])->name('logistics.vehicles.getData');
            Route::get('/{vehicle}', [VehiclesController::class, 'show'])->name('logistics.vehicles.show');
            Route::get('/edit/{vehicle}', [VehiclesController::class, 'edit'])->name('logistics.vehicles.edit');
            Route::put('/update/{vehicle}', [VehiclesController::class, 'update'])->name('logistics.vehicles.update');
            Route::delete('/{vehicle}', [VehiclesController::class, 'destroy'])->name('logistics.vehicles.destroy');
        });
        Route::prefix('trip')->group(function () {
            Route::post('/storeTrip', [TripController::class, 'storeTrip'])->name('logistics.trip.storeTrip');
            Route::get('/', [TripController::class, 'index'])->name('logistics.trip.index');
            Route::get('/create', [TripController::class, 'create'])->name('logistics.trip.create');
            Route::get('/getData', [TripController::class, 'getData'])->name('logistics.trip.getData');
            Route::get('/show', [TripController::class, 'show'])->name('logistics.trip.show');


            Route::get('/vehicle/categories', [TripController::class, 'getVehicleCategories'])->name('logistics.trip.vehicle.categories');
            Route::get('/vehicle/types/{categoryId}', [TripController::class, 'getVehicleTypes'])->name('logistics.trip.vehicle.types');
            Route::get('/vehicle/search', [TripController::class, 'searchVehicles'])->name('logistics.trip.vehicle.search');
            Route::get('/pendingOrders', [TripController::class, 'pendingOrders'])->name('logistics.trip.pendingOrders');
            Route::get('/drivers', [TripController::class, 'getDrivers'])->name('logistics.trip.drivers');


        
        });
        Route::prefix('bata')->group(function () {
            Route::get('/drivers/search', [BataController::class, 'searchDrivers'])->name('bata.searchDrivers');
            Route::get('/trips/filter', [BataController::class, 'filterTrips'])->name('bata.filterTrips');
            Route::get('/fetch-expenses', [BataController::class, 'fetchExpenses'])->name('bata.fetchExpenses');
            Route::get('/', [BataController::class, 'index'])->name('logistics.bata.index');
            Route::get('/create', [BataController::class, 'create'])->name('logistics.bata.create');
            Route::post('/store', [BataController::class, 'store'])->name('logistics.bata.store');
            Route::get('/getData', [BataController::class, 'getData'])->name('logistics.bata.getData');
            Route::get('/{bata}', [BataController::class, 'show'])->name('logistics.bata.show');
            // Route::get('/edit/{bata}', [BataController::class, 'edit'])->name('logistics.bata.edit');
            // Route::put('/update/{bata}', [BataController::class, 'update'])->name('logistics.bata.update');
            // Route::delete('/destroy/{bata}', [BataController::class, 'destroy'])->name('logistics.bata.destroy');
        });
    });

    Route::prefix('md')->middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('md.dashboard');
        Route::get('/getMDData1', [DashboardController::class, 'getMDData1'])->name('md.getMDData1');
        Route::get('/getMDData2', [DashboardController::class, 'getMDData2'])->name('md.getMDData2');
        Route::get('/getMDData3', [DashboardController::class, 'getMDData3'])->name('md.getMDData3');
        Route::get('/getMDData4', [DashboardController::class, 'getMDData4'])->name('md.getMDData4');
        Route::get('/getMDData5', [DashboardController::class, 'getMDData5'])->name('md.getMDData5');
        Route::get('/sales-data', [DashboardController::class, 'getSalesData'])->name('md.getSalesData'); 
        Route::get('/sales-quantity', [DashboardController::class, 'getSalesQuantity'])->name('md.getSalesQuantity'); 
        Route::get('/leads-data', [DashboardController::class, 'getLeadsData'])->name('md.getLeadsData');
        Route::get('/outstanding-payments-data', [DashboardController::class, 'getOutstandingPaymentsData'])->name('md.getOutstandingPaymentsData');
        Route::get('/overall-target-achievement', [DashboardController::class, 'getOverallTargetAndAchievement'])->name('md.overall-target-achievement');
        Route::get('/credit-note-stats', [DashboardController::class, 'getCreditNoteStats'])->name('md.credit-note-stats');
        Route::get('/highest-lowest-items', [DashboardController::class, 'getHighestLowestItems'])->name('md.highest-lowest-items');
        Route::get('/customer-performance', [DashboardController::class, 'getCustomerPerformance'])->name('md.customer-performance');
        Route::get('/sales-performance', [DashboardController::class, 'getSalesPerformance'])->name('md.sales-performance');
    });

    Route::prefix('operations')->middleware('auth')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('operations.dashboard');
        Route::prefix('orders')->group(function () {
            Route::get('/', [OperationsController::class, 'index'])->name('operations.orders.index');
            Route::get('/new', [OperationsController::class, 'new'])->name('operations.orders.new');
            Route::get('/listNew', [OperationsController::class, 'orderListNew'])->name('operations.orders.listNew');
            Route::get('/list', [OperationsController::class, 'orderList'])->name('operations.orders.list');
            Route::get('/view/{id}', [OperationsController::class, 'viewOrder'])->name('view');
            Route::post('/change-status/{id}', [OperationsController::class, 'changeStatus'])->name('operations.orders.changeStatus');
            Route::get('/export', [OperationsController::class, 'export'])->name('operations.orders.export'); 
        });
    });
