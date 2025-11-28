<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Activity;
use App\Models\Order;
use App\Models\Employee;
use App\Models\OutstandingPayment;
use App\Models\Payment;
use App\Models\Attendance;
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
use App\Models\InfluencerVisit;
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
            // ->where('status', 'Delivered')
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

        $totalRevenue = $orders->sum(function ($order) {
            return $order->orderItems->sum('total_quantity');
        });
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
            // ->where('status', 'Delivered')
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

        // $customerVisitCount = RescheduledRoute::whereYear('assign_date', $year)
        //                     ->whereMonth('assign_date', $monthNumber+1)
        //                     ->get()
        //                     ->sum(function ($route) {
        //                         $customers = collect(json_decode($route->customers ?? '[]', true));
        //                         return $customers->where('scheduled', true)->where('status', 'Completed')->count();
        //                     });
        $customerVisitCount = InfluencerVisit::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber + 1)
                ->distinct('phone')   
                ->count('phone');


        $aashiyanaCount = Order::with(["orderItems"])->whereYear('created_at', $year)
                            ->whereMonth('created_at', $monthNumber+1)
                            ->whereHas('orderItems', function ($q) use ($productID) {
                                $q->where('product_id', $productID);
                            })
                            ->where('payment_terms_id', 3)
                            ->count();

        $orders = Order::with(["orderItems"])->whereYear('created_at', $year)
                        ->whereMonth('created_at', $monthNumber+1)
                        ->where('order_approved', '1')
                        ->pluck('id');

        $achievedOrderQuantity = DB::table('order_items')
            ->whereIn('order_id', $orders)
            ->sum('total_quantity');

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
            // $totalQty = Order::where('created_by', $employee->id)
            //     ->whereBetween('created_at', [$fromDate, $toDate])
            //     ->sum('invoice_quantity');
            $totalQty = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.created_by', $employee->id)
                ->where('orders.order_approved', '1')
                ->whereBetween('orders.created_at', [$fromDate, $toDate])
                ->sum('order_items.total_quantity');
            $totalAmount = Order::where('created_by', $employee->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('total_amount');
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

        $exportMonth = $month + 1;

        $monthFormatted = str_pad($exportMonth, 2, '0', STR_PAD_LEFT);

        $fileName = "influencer_visits_{$monthFormatted}_{$year}.xlsx";

        return Excel::download(new InfluencerVisitsExport($year, $month), $fileName);
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
    

    //...fetch data for MD

    public function fetchDealerVisitData(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $totalDealerVisit = \App\Models\DealerVisit::with(["createdBy"])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalDealerVisit' => $totalDealerVisit
        ]);
    }

    public function fetchInfluencerVisitData(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $totalInfluencerVisit = \App\Models\InfluencerVisit::with(["createdBy"])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalInfluencerVisit' => $totalInfluencerVisit
        ]);
    }

    public function fetchOverallTargetAndAchievement(Request $request)
    {
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        // ---------- CASE 1: FROM / TO DATE RANGE ----------
        if ($request->has('from') && $request->has('to')) {

            $from = $request->from;
            $to   = $request->to;

            // Targets (based on month of FROM date)
            $monthName = \Carbon\Carbon::parse($from)->format('F');
            $year      = \Carbon\Carbon::parse($from)->year;

            $targets = Target::where('month', $monthName)
                            ->where('year', $year)
                            ->where('product_id', $productID)
                            ->get();

            $totalTargets = [
                'unique_leads'    => $targets->sum('unique_lead'),
                'customer_visit'  => $targets->sum('customer_visit'),
                'aashiyana'       => $targets->sum('aashiyana'),
                'order_quantity'  => (float) $targets->sum('order_quantity'),
            ];

            // Achievements using from → to filter
            $uniqueLeads = Lead::with("createdBy")
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('createdBy', function ($q) use ($productID) {
                    $q->whereJsonContains('products', (string)$productID);
                })
                ->count();

            $customerVisit = RescheduledRoute::with("employee")
                ->whereBetween('assign_date', [$from, $to])
                ->whereHas('employee', function ($q) use ($productID) {
                    $q->whereJsonContains('products', (string)$productID);
                })
                ->get()
                ->sum(function ($route) {
                    $customers = collect(json_decode($route->customers ?? '[]', true));
                    return $customers->where('scheduled', true)->where('status', 'Completed')->count();
                });

            $aashiyanaCount = Order::with("orderItems")
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('orderItems', function ($q) use ($productID) {
                    $q->where('product_id', $productID);
                })
                ->where('payment_terms_id', 3)
                ->count();

            $orders = Order::with("orderItems")
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('orderItems', function ($q) use ($productID) {
                    $q->where('product_id', $productID);
                })
                ->where('status', 'Delivered')
                ->pluck('id');

            $orderQty = DB::table('orders')
                        ->whereIn('id', $orders)
                        ->sum('invoice_quantity');

            return [
                'target' => $totalTargets,
                'achieved' => [
                    'unique_leads'    => $uniqueLeads,
                    'customer_visit'  => $customerVisit,
                    'aashiyana'       => $aashiyanaCount,
                    'order_quantity'  => round($orderQty, 6),
                ]
            ];
        }

    }

    public function fetchLeadsData(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $totalLeads = \App\Models\Lead::with("createdBy")
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->count();

        return response()->json([
            'totalLeads' => $totalLeads
        ]);
    }
    public function fetchSalesData(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $orders = Order::whereBetween('created_at', [$from, $to])
            ->where('order_approved', '1')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                    ->whereHas('createdBy', function ($q2) {
                        $q2->where('employee_type_id', '!=', 1);
                    });
                })->orWhere('dealer_flag_order', '1'); 
            })
            ->with('orderItems')
            ->get();

        $totalSalesRevenue = $orders->sum('total_amount');

        $orderCount = $orders->count();

        return response()->json([
            'totalSalesRevenue' => $totalSalesRevenue,
            'orderCount' => $orderCount
        ]);
    }
    public function fetchSalesQuantity(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $orders = Order::whereBetween('created_at', [$from, $to])
            ->where('order_approved', '1')
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                    ->whereHas('createdBy', function ($q2) {
                        $q2->where('employee_type_id', '!=', 1);
                    });
                })->orWhere('dealer_flag_order', '1'); 
            })
            ->with('orderItems')
            ->get();

        $totalSalesQuantity = $orders->sum(function ($order) {
            return $order->orderItems->sum('total_quantity'); 
        });

        $orderCount = $orders->count();

        return response()->json([
            'totalSalesQuantity' => $totalSalesQuantity,
            'orderCount' => $orderCount
        ]);
    }
    public function fetchLeadsGenerated(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $totalLeadsGenerated = Lead::whereBetween('created_at', [$from, $to])
            ->whereNotNull('phone')
            ->distinct('phone')
            ->count('phone');

        return response()->json([
            'totalLeadsGenerated' => $totalLeadsGenerated
        ]);
    }
    public function fetchHighestLowest(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $orderIds = Order::where('order_approved', '1')
            ->whereBetween('created_at', [$from, $to])
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

                $typeName = $detail['type_name'];   
                $qty      = $detail['quantity'];

                if (!isset($productSales[$typeName])) {
                    $productSales[$typeName] = 0;
                }

                $productSales[$typeName] += $qty;
            }
        }

        $highestSelling = null;
        $lowestSelling  = null;

        if (!empty($productSales)) {
            $highestSelling = array_keys($productSales, max($productSales))[0];

            $lowestSelling  = array_keys($productSales, min($productSales))[0];
        }

        return response()->json([
            'highest' => $highestSelling,
            'lowest'  => $lowestSelling
        ]);
    }

    public function fetchCustomerPerformance(Request $request)
    {
        $from = $request->from;
        $to   = $request->to;

        $ordersQuery = Order::with('orderItems')
            ->where('order_approved', '1')
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

        if ($from && $to) {
            $ordersQuery->whereBetween('created_at', [$from . " 00:00:00", $to . " 23:59:59"]);
        }

        $orders = $ordersQuery->get();

        $customerPerformance = [];

        foreach ($orders as $order) {
            $dealerId = $order->dealer_flag_order == '1' ? $order->created_by_dealer : $order->dealer_id;
            if (!$dealerId) continue;

            if (!isset($customerPerformance[$dealerId])) {
                $customerPerformance[$dealerId] = [
                    'total_quantity' => 0,
                    'total_amount' => 0
                ];
            }

            $customerPerformance[$dealerId]['total_quantity'] += $order->orderItems->sum('total_quantity');

            $customerPerformance[$dealerId]['total_amount'] += $order->total_amount;
        }

        if (empty($customerPerformance)) {
            return response()->json([
                'most_purchased' => null,
                'least_purchased' => null
            ]);
        }

        $sorted = collect($customerPerformance)->sortByDesc('total_quantity');

        $mostId = $sorted->keys()->first();
        $leastId = $sorted->keys()->last();

        $mostDealer = Dealer::find($mostId);
        $leastDealer = Dealer::find($leastId);

        return response()->json([
            'most_purchased' => [
                'dealer_id' => $mostId,
                'dealer_name' => $mostDealer?->dealer_name ?? 'N/A',
                'total_quantity' => round($sorted[$mostId]['total_quantity'], 2),
                'total_amount' => round($sorted[$mostId]['total_amount'], 2),
            ],
            'least_purchased' => [
                'dealer_id' => $leastId,
                'dealer_name' => $leastDealer?->dealer_name ?? 'N/A',
                'total_quantity' => round($sorted[$leastId]['total_quantity'], 2),
                'total_amount' => round($sorted[$leastId]['total_amount'], 2),
            ],
        ]);
    }




    public function fetchTotalOrder(){
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        $baseQuery = \App\Models\Order::query()->with(['orderItems'])
            ->where('status', '!=', 'Rejected')
            ->whereHas('orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
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

        // $pendingOrders = \App\Models\Order::query()
        //     ->with(['orderItems'])
        //     ->where('status', '!=', 'Rejected')
        //     ->where('order_approved', '0')
        //     ->whereHas('orderItems', function ($q) use ($productID) {
        //         $q->where('product_id', $productID);
        //     })
        //     ->count();  
        $approvedOrders = (clone $baseQuery)->where('order_approved', '1')->count();
         return response()->json([
            'approvedOrders' => $approvedOrders
        ]);
    }


    public function fetchSalesPerformance(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date',
            'employee_type_id'   => 'required',
            'region_id'   => 'required',
        ]);

        $from = $request->from;
        $to   = $request->to;
        $region_id   = $request->region_id;
        $employee_type_id   = $request->employee_type_id;

        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $data = Order::with([
                'createdBy.region',
                'createdBy.employeeType'
            ])
           ->whereBetween('created_at', [$from, $to])
            ->whereHas('orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->whereHas('createdBy', function ($q) use ($employee_type_id, $region_id) {
                $q->where('employee_type_id', $employee_type_id)
                ->whereHas('district', function ($q2) use ($region_id) {
                    $q2->where('regions_id', $region_id);
                });
            })
            ->selectRaw('created_by, SUM(invoice_quantity) as qty, SUM(invoice_total) as amt')
            ->groupBy('created_by')
            ->get()
            ->map(function ($row) {

                return [
                    'region'        => $row->createdBy->region->name ?? '-',
                    'employeeType'  => $row->createdBy->employeeType->type_name ?? '-',
                    'employeeName'  => $row->createdBy->name ?? '-',
                    'sellingQty'    => (float) ($row->qty ?? 0),
                    'amount'        => (float) ($row->amt ?? 0),
                    'createdBy'     => $row->createdBy
                ];
            });

        return response()->json([
            'salesPerformance' => $data
        ]);
    }
    public function fetchTotalCompletedActivity(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date',
        ]);
        $from = $request->from;
        $to   = $request->to;
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();
        $data = Activity::whereBetween('completed_date', [$from, $to])
                        ->where('status',"Completed")   
                        ->count();
        return response()->json([
            'totalCompletedActivity' => $data
        ]);
    }

    public function fetchCreditNoteStats(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date',
        ]);

        $from = $request->from;
        $to   = $request->to;

        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $creditNotes = CreditNote::with(["order.orderItems"])
            ->whereBetween('date', [$from, $to])
            ->where('status', 'open')
            ->whereHas('order.orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->get();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'credit_note_amount' => round($creditNotes->sum('total_row_amount'), 2),
                'credit_note_count'  => $creditNotes->count(),
                'order_count'        => $creditNotes->pluck('order_id')->unique()->count(),
            ]
        ]);
    }

    public function fetchOutstandingPaymentsData(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date',
        ]);

        $from = $request->from;
        $to   = $request->to;

        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        $outstanding = OutstandingPayment::with(["order.orderItems"])
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', 'open')
            ->whereHas('order.orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->get();

        $payments = Payment::whereBetween('payment_date', [$from, $to])->get();

        return response()->json([
            'totalOutstandingAmount'  => $outstanding->sum('outstanding_amount'),
            'totalOutstandingInvoices'=> $outstanding->count(),
            'totalCollectionAmount'   => $payments->sum('payment_amount'),
            'totalPaidInvoices'       => $payments->unique('invoice_number')->count()
        ]);
    }

    public function getMDData1(Request $request)
    {
        // $from = $request->from;
        // $to   = $request->to;

        $productID = \App\Helpers\ProductHelper::getSelectedProductID();
        $productName =Product::where("id",$productID)->select("product_name")->first();
        $dealerData     = $this->fetchDealerVisitData($request)->getData()->totalDealerVisit;
        $influencerData       = $this->fetchInfluencerVisitData($request)->getData()->totalInfluencerVisit;
        $totalCompletedActivity = $this->fetchTotalCompletedActivity($request)->getData()->totalCompletedActivity;
        $LeadsData = $this->fetchLeadsData($request)->getData()->totalLeads;

        $salesData = $this->fetchSalesData($request)->getData();
        $salesQuantity = $this->fetchSalesQuantity($request)->getData();
        $leadsGenerated = $this->fetchLeadsGenerated($request)->getData();
        $approvedOrders = $this->fetchTotalOrder($request)->getData()->approvedOrders;
       
        return response()->json([

            'productName'            => $productName->product_name,
            'totalOrder'            => $approvedOrders,
            'totalLeadOpen'         => $LeadsData,
            'totalInfluencerVisit'  => $influencerData,
            'totalDealerVisit'      => $dealerData,
            'totalCompletedActivity'=> $totalCompletedActivity,

            'totalSalesRevenue'     => round($salesData->totalSalesRevenue, 2) ?? 0,     
            'totalSalesOrderCount'  => $salesData->orderCount ?? 0,
            'totalSalesQuantityTon' => round($salesQuantity->totalSalesQuantity, 2) ?? 0, 
            'totalSalesQuantityCount' => $salesQuantity->orderCount ?? 0, 
            'totalLeadGenerated'    => $leadsGenerated->totalLeadsGenerated ?? 0,
        ]);
    }

     public function getMDData2(Request $request)
    {
        // $from = $request->from;
        // $to   = $request->to;

        $creditNoteStats        = $this->fetchCreditNoteStats($request)->getData()->data;
        $outstandingPaymentData = $this->fetchOutstandingPaymentsData($request)->getData();


        return response()->json([

            'totalCreditNoteAmount' => round($creditNoteStats->credit_note_amount, 2),
            'creditNoteCount'       => $creditNoteStats->credit_note_count,
            'creditNoteOrderCount'  => $creditNoteStats->order_count,

            'totalOutstandingPayment'  => round($outstandingPaymentData->totalOutstandingAmount, 2),
            'outstandingOrderCount'=> $outstandingPaymentData->totalOutstandingInvoices,

            'totalOutstandingCollection'   => round($outstandingPaymentData->totalCollectionAmount, 2),
            'collectionOrderCount'       => $outstandingPaymentData->totalPaidInvoices,

           
        ]);
    }

     public function getMDData3(Request $request)
    {
        $highestLowest = $this->fetchHighestLowest($request)->getData();
        $customerPerformance = $this->fetchCustomerPerformance($request)->getData();
        
        return response()->json([

            
            'highestSellingItem' => $highestLowest->highest ?? '-',
            'lowestSellingItem'  => $highestLowest->lowest ?? '-',

            'topCustomerName'   => $customerPerformance->most_purchased->dealer_name ?? 'N/A',
            'topCustomerQty'    => $customerPerformance->most_purchased->total_quantity ?? 0,
            'topCustomerAmount' => $customerPerformance->most_purchased->total_amount ?? 0,

            'leastCustomerName'   => $customerPerformance->least_purchased->dealer_name ?? 'N/A',
            'leastCustomerQty'    => $customerPerformance->least_purchased->total_quantity ?? 0,
            'leastCustomerAmount' => $customerPerformance->least_purchased->total_amount ?? 0,

            // 'salesPerformance' => $salesPerformance,

        ]);
    }

    public function getMDData5(Request $request){
         $salesPerformance = $this->fetchSalesPerformance($request)->getData()->salesPerformance;
        
        return response()->json([
            'salesPerformance' => $salesPerformance,
        ]);
    }
    public function getMDData4(Request $request)
    {
        // $productID = \App\Helpers\ProductHelper::getSelectedProductID();
        // $product =Product::where("id",$productID)->select("product_code")->first();
        $overall = $this->fetchOverallTargetAndAchievement($request);
        $targets   = $overall['target'];
        $achieved  = $overall['achieved'];

        $presentCount = null;
        $leaveCount = null;
        $from = $request->from;
        $to   = $request->to;
        if($from==$to){
            $presentCount = Attendance::whereDate('date', $from)
                    ->where('status', 'present')
                    ->count();
            $leaveCount = Attendance::whereDate('date', $from)
                    ->where('status', 'leave')
                    ->count();
        }
        // $stockDetails=$this->stockDetails($product->product_code);
        $stockDetails=0;
        // dd($stockDetails);
        
        return response()->json([

            'attendance'     => (bool)($to == $from),
            'teamOnDuty'     => $presentCount,
            'teamOnLeave'    => $leaveCount,

            'totalStock'      => 350,
            'totalInStock'    => 280,
            'totalOutOfStock' => 70,

            'uniqueTarget'   => $targets['unique_leads'],
            'uniqueAchieved' => $achieved['unique_leads'],

            'influencerTarget'   => $targets['customer_visit'],
            'influencerAchieved' => $achieved['customer_visit'],

            'aashiyanaTarget'   => $targets['aashiyana'],
            'aashiyanaAchieved' => $achieved['aashiyana'],

            'productsTarget'   => $targets['order_quantity'],
            'productsAchieved' => $achieved['order_quantity'],
        ]);
    }

    public function stockDetails($productItem)
    {
        try {
           
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

            if (!$conn) {
                return response()->json([
                    'data' => []
                ]);
            }
            $sql = "CALL \"PRABHU_NEW\".\"Mobile_App_GetStock\"('$productItem','')";
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                odbc_close($conn);
                return response()->json([
                    'data' => []
                ]);
            }

           $stockData = [];

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);
                foreach ($row as $key => $value) {
                    $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                }

                $qty = isset($row['OnHand']) ? floatval($row['OnHand']) : 0;

                if ($qty > 10) {
                    $status = "In-Stock";
                } elseif ($qty >= 1 && $qty <= 10) {
                    $status = "Low Stock";
                } else {
                    $status = "Out of Stock";
                }

                $row['status'] = $status;

                $stockData[] = $row;
            }


            odbc_close($conn);

            return response()->json([
                'data' => $stockData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'data' => []
            ]);
        }
    }
}
