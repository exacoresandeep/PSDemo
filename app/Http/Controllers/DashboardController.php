<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Activity;
use App\Models\Order;
use App\Models\Employee;
use App\Models\OutstandingPayment;
use App\Models\Payment;
use App\Models\Lead;
use App\Models\RescheduledRoute;
use App\Models\Target;
use App\Models\Dealer;
use App\Models\District;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Exports\LeadsExport;
use App\Models\Product;
use App\Exports\OutstandingPaymentsExport;
use App\Exports\CreditNoteExport;
use App\Exports\DealerVisitExport;
use App\Exports\InfluencerVisitExport;
use App\Exports\SalesPerformanceExport;
use App\Exports\UniqueLeadsExport;
use App\Exports\InfluencerVisitsExport;
use App\Exports\AashiyanaOrdersExport;
use App\Exports\TisconOrdersExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function salesDashboard()
    {
        $today = Carbon::today();

        $todaysActivities = Activity::whereDate('assigned_date', $today)->count();
        $pendingActivities = Activity::where('status', 'Pending')->count();
        $completedActivities = Activity::where('status', 'Completed')->count();

        return view('sales.dashboard', compact('todaysActivities', 'pendingActivities', 'completedActivities'));
    }
    public function accountsDashboard()
    {
        $selectedProductCode = session('selected_product_code');$products=[];
        if ($selectedProductCode) {
            $products = Product::where('product_code', $selectedProductCode)->first();
        }else{
            $user = auth()->user();
            $productIds = $user->product_ids ?? [];

            $products = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'product_code')
                ->first();       
        }
        $baseQuery = \App\Models\Order::query()->with(['orderItems'])
            ->where('status', '!=', 'Rejected')
            ->whereHas('orderItems', function ($q) use ($products) {
                $q->where('product_id', $products->id);
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('dealer_flag_order', '1')
                        ->where(function ($subQuery) {
                            $subQuery->where('send_for_approval', '1')
                                ->orWhereNull('send_for_approval');
                        })
                        ->where(function ($subQuery) {
                            $subQuery->where('order_approved', '!=', '0')
                                ->orWhereIn('order_approved_by', function ($subQuery) {
                                    $subQuery->select('id')
                                        ->from('users')
                                        ->where('role_id', 2);
                                });
                        });
                })->orWhere(function ($q) {
                    $q->where('dealer_flag_order', '0')
                        ->where(function ($subQuery) {
                            $subQuery->where('order_approved', '!=', '0')
                                ->orWhereIn('order_approved_by', function ($subQuery) {
                                    $subQuery->select('id')
                                        ->from('users')
                                        ->where('role_id', 2);
                                });
                        });
                });
            });

        $pendingOrders = \App\Models\Order::query()
            ->with(['orderItems'])
            ->where('status', '!=', 'Rejected')
            ->where('order_approved', '0')
            ->whereHas('orderItems', function ($q) use ($products) {
                $q->where('product_id', $products->id);
            })
            ->count();  
        $approvedOrders = (clone $baseQuery)->where('order_approved', '1')->count();
        $rejectedOrders = (clone $baseQuery)->where('order_approved', '2')->count();

            return view('accounts.dashboard', compact(
                'pendingOrders',
                'approvedOrders',
                'rejectedOrders'
            ));
    }
    public function getSalesData(Request $request)
    {
        $month = $request->month; 
        $year = $request->year;

        $orders = Order::whereYear('created_at', $year)
            ->whereMonth('created_at', $month + 1) 
            ->where('status', 'Delivered')
            ->where('order_approved', '1')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                    ->whereHas('createdBy', function ($q2) {
                        $q2->where('employee_type_id', '!=', 1);
                    });
                })->orWhere('dealer_flag_order', '1'); 
            })
            ->get();

        $totalRevenue = $orders->sum('invoice_total'); 
        $orderCount = $orders->count();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'orderCount' => $orderCount
        ]);
    }
    public function getSalesQuantity(Request $request)
    {
        $month = $request->month; 
        $year = $request->year;

        $orders = Order::whereYear('created_at', $year)
            ->whereMonth('created_at', $month + 1) 
            ->where('status', 'Delivered')
            ->where('order_approved', '1')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                    ->whereHas('createdBy', function ($q2) {
                        $q2->where('employee_type_id', '!=', 1);
                    });
                })->orWhere('dealer_flag_order', '1'); 
            })
            ->get();

        // $totalRevenue = $orders->sum('invoice_total'); 
        $totalQuantity = $orders->sum('invoice_quantity');
        $orderCount = $orders->count();

        return response()->json([
            // 'totalRevenue' => $totalRevenue,
            'totalQuantity' => $totalQuantity,
            'orderCount' => $orderCount
        ]);
    }
    public function getLeadsData(Request $request)
    {
        $month = $request->month; 
        $year = $request->year;
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $totalLeads = \App\Models\Lead::with(["createdBy"])->whereYear('created_at', $year)
            ->whereMonth('created_at', $month + 1) 
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalLeads' => $totalLeads
        ]);
    }
    public function getOutstandingPaymentsData(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        // Outstanding Payments
        $outstanding = OutstandingPayment::with(["order.orderItems"])->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month + 1) 
            ->where('status', 'open')
            ->whereHas('order.orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->get();

        $totalOutstandingAmount = $outstanding->sum('outstanding_amount');
        $totalOutstandingInvoices = $outstanding->count();

        // Collections
        $payments = Payment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month + 1)
            ->get();

        $totalCollectionAmount = $payments->sum('payment_amount');
        $totalPaidInvoices = $payments->unique('invoice_number')->count();

        return response()->json([
            'totalOutstandingAmount' => $totalOutstandingAmount,
            'totalOutstandingInvoices' => $totalOutstandingInvoices,
            'totalCollectionAmount' => $totalCollectionAmount,
            'totalPaidInvoices' => $totalPaidInvoices
        ]);
    }
    public function getOverallTargetAndAchievement(Request $request)
    {
        $monthNumber = $request->month;
        $monthName = \DateTime::createFromFormat('!m', $monthNumber)->format('F');
        $year = $request->year;
        $nextMonthNumber = $monthNumber + 1;
        if ($nextMonthNumber > 12) {
            $nextMonthNumber = 1;
            $year += 1; 
        }

        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $nextMonthName = \DateTime::createFromFormat('!m', $nextMonthNumber)->format('F');
        $targets = Target::where('month', $nextMonthName)
                        ->where('year', $year)
                        ->where('product_id', $productID)
                        ->get();

        $totalTargets = [
            'unique_leads' => $targets->sum('unique_lead'),
            'customer_visit' => $targets->sum('customer_visit'),
            'aashiyana' => $targets->sum('aashiyana'),
            'order_quantity' => (float) $targets->sum('order_quantity'),
        ];

        // Sum achievements
        $uniqueLeads = Lead::with(["createdBy"])->whereYear('created_at', $year)
                            ->whereMonth('created_at', $monthNumber+1)
                            ->whereHas('createdBy', function($q) use ($productID) {
                                $q->whereJsonContains('products', (string)$productID);
                            })
                            ->count();

        $customerVisitCount = RescheduledRoute::with(["employee"])->whereYear('assign_date', $year)
                            ->whereMonth('assign_date', $monthNumber+1)
                            ->whereHas('employee', function($q) use ($productID) {
                                $q->whereJsonContains('products', (string)$productID);
                            })
                            ->get()
                            ->sum(function ($route) {
                                $customers = collect(json_decode($route->customers ?? '[]', true));
                                return $customers->where('scheduled', true)->where('status', 'Completed')->count();
                            });

        $aashiyanaCount = Order::with(["orderItems"])->whereYear('created_at', $year)
                            ->whereMonth('created_at', $monthNumber+1)
                            ->whereHas('orderItems', function ($q) use ($productID) {
                                $q->where('product_id', $productID);
                            })
                            ->where('payment_terms_id', 3)
                            ->count();

        $orders = Order::with(["orderItems"])->whereYear('created_at', $year)
                        ->whereMonth('created_at', $monthNumber+1)
                        ->whereHas('orderItems', function ($q) use ($productID) {
                            $q->where('product_id', $productID);
                        })
                        ->where('status', 'Delivered')
                        ->pluck('id');

        $achievedOrderQuantity = DB::table('orders')
                                    ->whereIn('id', $orders)
                                    ->sum('invoice_quantity');

        return response()->json([
            'target' => $totalTargets,
            'achieved' => [
                'unique_leads' => $uniqueLeads,
                'customer_visit' => $customerVisitCount,
                'aashiyana' => $aashiyanaCount,
                'order_quantity' => round($achievedOrderQuantity, 6),
            ],
        ]);
    }
    public function getCreditNoteStats(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $creditNotes = CreditNote::with(["order.orderItems"])->whereMonth('date', $month+1)
            ->whereYear('date', $year)
            ->where('status', 'open')
            ->whereHas('order.orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->get();

        $totalAmount = $creditNotes->sum('total_row_amount');
        $totalCreditNotes = $creditNotes->count();
        $totalOrders = $creditNotes->pluck('order_id')->unique()->count();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'credit_note_amount' => round($totalAmount, 2),
                'credit_note_count' => $totalCreditNotes,
                'order_count' => $totalOrders,
            ]
        ]);
    }
    public function getHighestLowestItems()
    {
        $orderIds = Order::where('order_approved', '1')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                        ->whereHas('createdBy', function ($q2) {
                            $q2->where('employee_type_id', '!=', 1);
                        });
                })->orWhere('dealer_flag_order', '1');
            })
            ->pluck('id');

        $productSales = [];

        $orderItems = \App\Models\OrderItem::whereIn('order_id', $orderIds)->get();

        foreach ($orderItems as $item) {
            foreach ($item->product_details as $detail) {
                $typeId = $detail['product_type_id'];
                $qty = $detail['quantity'];

                if (!isset($productSales[$typeId])) {
                    $productSales[$typeId] = 0;
                }

                $productSales[$typeId] += $qty;
            }
        }

        $highestSelling = null;
        $lowestSelling = null;

        if (!empty($productSales)) {
            $maxTypeId = array_keys($productSales, max($productSales))[0];
            $minTypeId = array_keys($productSales, min($productSales))[0];

            $highest = \App\Models\ProductType::find($maxTypeId);
            $lowest = \App\Models\ProductType::find($minTypeId);

            $highestSelling = $highest ? $highest->type_name : null;
            $lowestSelling = $lowest ? $lowest->type_name : null;
        }

        return response()->json([
            'highest' => $highestSelling,
            'lowest' => $lowestSelling,
        ]);
    }
    public function getCustomerPerformance(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        // Build base query
        $query = Order::selectRaw("
                CASE 
                    WHEN dealer_flag_order = '1' THEN created_by_dealer 
                    ELSE dealer_id 
                END as dealer_id,
                SUM(invoice_quantity) as total_quantity,
                SUM(invoice_total) as total_amount
            ")
            ->where('status', '!=', 'Rejected')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('dealer_flag_order', '1')
                        ->whereNotNull('created_by_dealer');
                })->orWhere(function ($q) {
                    $q->where('dealer_flag_order', '0')
                        ->whereNotNull('dealer_id')
                        ->whereHas('createdBy', function ($empQuery) {
                            $empQuery->where('employee_type_id', '!=', 1);
                        });
                });
            });

        // Apply date filter only if both month and year are passed
        if ($month && $year) {
            $fromDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d H:i:s');
            $toDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $finalTotals = $query
            ->groupBy(DB::raw("CASE 
                    WHEN dealer_flag_order = '1' THEN created_by_dealer 
                    ELSE dealer_id 
                END"))
            ->having('total_quantity', '>', 0)
            ->orderByDesc('total_quantity')
            ->get();

        if ($finalTotals->isEmpty()) {
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'No orders found.',
                'data' => null,
            ]);
        }

        $most = $finalTotals->first();
        $least = $finalTotals->last();

        $mostDealer = Dealer::find($most->dealer_id);
        $leastDealer = Dealer::find($least->dealer_id);

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'data' => [
                'most_purchased' => [
                    'dealer_id' => $most->dealer_id,
                    'dealer_name' => $mostDealer?->dealer_name ?? 'N/A',
                    'total_quantity' => round($most->total_quantity, 2),
                    'total_amount' => number_format($most->total_amount, 2),
                ],
                'least_purchased' => [
                    'dealer_id' => $least->dealer_id,
                    'dealer_name' => $leastDealer?->dealer_name ?? 'N/A',
                    'total_quantity' => round($least->total_quantity, 2),
                    'total_amount' => number_format($least->total_amount, 2),
                ],
            ],
        ]);
    }
    public function getSalesPerformance(Request $request)
    {
        $request->validate([
            'region_id' => 'required|integer',
            'employee_type_id' => 'required|integer',
            'month' => 'required|integer|min:0|max:12',
            'year' => 'required|integer|min:2000',
        ]);
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $districtIds = District::where('regions_id', $request->region_id)->pluck('id');
        $employees = Employee::where('employee_type_id', $request->employee_type_id)
            ->whereIn('district_id', $districtIds)
            ->whereJsonContains('products', (string)$productID)
            ->with(['employeeType', 'district.region']) 
            ->get();

        $fromDate = Carbon::createFromDate($request->year, $request->month+1, 1)->startOfMonth();
        $toDate = $fromDate->copy()->endOfMonth();
        // dd($toDate);
        $result = [];

        foreach ($employees as $employee) {
            $totalQty = Order::with(["orderItems"])->where('created_by', $employee->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->whereHas('orderItems', function ($q) use ($productID) {
                                $q->where('product_id', $productID);
                            })
                ->sum('invoice_quantity');
            $totalAmount = Order::with(["orderItems"])->where('created_by', $employee->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->whereHas('orderItems', function ($q) use ($productID) {
                                $q->where('product_id', $productID);
                            })
                ->sum('invoice_total');
            $result[] = [
                'region' => $employee->region?->name ?? 'N/A',
                'employee_type' => $employee->employeeType?->type_name ?? 'N/A',
                'employee_name' => $employee->name,
                'total_invoice_quantity' => (float) $totalQty,
                'total_amount' => (float) $totalAmount,
            ];
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'data' => $result,
        ]);
    }
    public function exportLeads(Request $request){
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new LeadsExport($month, $year), 'leads_export.xlsx');
    }
    public function exportOutstandingPayments(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        $filename = "Outstanding_Payments_{$year}_{$month}.xlsx";
        return Excel::download(new OutstandingPaymentsExport($month, $year), $filename);
    }
    public function exportCreditNotes(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new CreditNoteExport($month, $year), 'credit_notes_'.$month.'_'.$year.'.xlsx');
    }
    public function exportSalesPerformance(Request $request)
    {
        $request->validate([
            'region_id' => 'required|integer',
            'employee_type_id' => 'required|integer',
            'month' => 'required|integer|min:0|max:11',
            'year' => 'required|integer|min:2000',
        ]);

        return Excel::download(
            new SalesPerformanceExport(
                $request->region_id,
                $request->employee_type_id,
                $request->month,
                $request->year
            ),
            'sales_performance.xlsx'
        );
    }
    public function getDealerVisitData(Request $request)
    {
        $month = $request->month; 
        $year = $request->year;
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $totalDealerVisit = \App\Models\DealerVisit::with(["createdBy"])->whereYear('created_at', $year)
            ->whereMonth('created_at', $month + 1) 
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalDealerVisit' => $totalDealerVisit
        ]);
    }
    public function getInfluencerVisitData(Request $request)
    {
        $month = $request->month; 
        $year = $request->year;
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $totalInfluencerVisit = \App\Models\InfluencerVisit::with(["createdBy"])->whereYear('created_at', $year)
            ->whereMonth('created_at', $month + 1) 
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalInfluencerVisit' => $totalInfluencerVisit
        ]);
    }
    public function exportDealerVisit(Request $request){
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new DealerVisitExport($month, $year), 'dealer_visit_export.xlsx');
    }
    public function exportInfluencerVisit(Request $request){
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new InfluencerVisitExport($month, $year), 'influencer_visit_export.xlsx');
    }
    public function exportUniqueLeads(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new UniqueLeadsExport($year, $month), 'unique_leads_export.xlsx');
    }

    public function exportInfluencerVisits(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new InfluencerVisitsExport($year, $month), 'influencer_visits_export.xlsx');
    }

    public function exportAashiyanaOrders(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new AashiyanaOrdersExport($year, $month), 'aashiyana_orders_export.xlsx');
    }
    public function exportTisconOrders(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        return Excel::download(new TisconOrdersExport($year, $month), 'tiscon_orders_export.xlsx');
    }
  

    public function getMDData(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;
// dd($request);
        return response()->json([
            'totalEmployees'   => 1,
            'totalVisits'      => 2,
            'totalOrders'      => 3,
            'totalCollections' => 4,
            'totalOutstanding' => 5,
        ]);
    }
}
