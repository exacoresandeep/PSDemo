<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Employee;
use App\Models\District;
use App\Models\Regions;
use App\Models\Product;
use App\Models\EmployeeType;
use App\Models\InfluencerVisit;
use App\Models\RescheduledRoute;
use Illuminate\Http\Request;
use App\Helpers\ProductHelper;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Http\Controllers\Api\AuthController;

use App\Models\Dealer;
use App\Models\DealerTarget;

class TargetController extends Controller
{
    public function index()
    {
        $targets = Target::all(); 
        $employeeTypes = EmployeeType::all();
        return view('sales.target.index', compact('targets','employeeTypes'));
    }
    public function targetList(Request $request)
    {
        $productId = ProductHelper::getSelectedProductId(); 
        $query = Target::with(['employee.employeeType'])->where('status', '1')
            ->whereNotNull('employee_id')
            ->whereHas('employee')    
            ->where('product_id', $productId)    
            ->withTrashed();

        if ($request->has('employee_type') && !empty($request->employee_type)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employee_type_id', $request->employee_type);
            });
        }

        if ($request->has('employee_id') && !empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }

        if ($request->has('month') && !empty($request->month)) {
            $query->where('month', $request->month);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $searchValue = $request->search['value'];

                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('employee', function ($q) use ($searchValue) {
                                $q->where('name', 'like', "%{$searchValue}%");
                            })
                            ->orWhereHas('employee.employeeType', function ($q) use ($searchValue) {
                                $q->where('type_name', 'like', "%{$searchValue}%");
                            })
                            ->orWhere('year', 'like', "%{$searchValue}%")
                            ->orWhere('month', 'like', "%{$searchValue}%")
                            ->orWhere('unique_lead', 'like', "%{$searchValue}%")
                            ->orWhere('customer_visit', 'like', "%{$searchValue}%")
                            ->orWhere('aashiyana', 'like', "%{$searchValue}%")
                            ->orWhere('order_quantity', 'like', "%{$searchValue}%");
                    });
                }

                // Ensure employee is not null
                $query->whereNotNull('employee_id')
                    ->whereHas('employee');
            })
            ->addIndexColumn() 
            ->addColumn('employee_type', function ($target) {
                return optional(optional($target->employee)->employeeType)->type_name ?? '-';
            })
            ->addColumn('employee_name', function ($target) {
                return optional($target->employee)->name ?? '-';
            })
            ->addColumn('year', function ($target) {
                return $target->year ?? '-';
            })
            ->addColumn('month', function ($target) {
                return $target->month ?? '-';
            })
            ->addColumn('unique_lead', function ($target) {
                return $target->unique_lead ?? '0';
            })
            ->addColumn('customer_visit', function ($target) {
                return $target->customer_visit ?? '0';
            })
            ->addColumn('aashiyana', function ($target) {
                return $target->aashiyana ?? '0';
            })
            ->addColumn('order_quantity', function ($target) {
                return $target->order_quantity ?? '0';
            })
            ->addColumn('action', function ($target) {
                return '
                    
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $target->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTarget(' . $target->id . ')" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
                // <button class="btn btn-sm btn-info" onclick="handleAction(' . $target->id . ', \'view\')" title="View">
                //         <i class="fa fa-eye"></i>
                //     </button>
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_type' => 'required|exists:employee_types,id',
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|numeric',
            'month' => 'required|string',
            'unique_lead' => 'required|integer|min:0',
            'customer_visit' => 'required|integer|min:0',
            'aashiyana' => 'required|integer|min:0',
            'order_quantity' => 'required|integer|min:0'
        ]);
        $existingTarget = Target::where('employee_id', $request->employee_id)
            ->where('year', $request->year)
            ->where('product_id', $request->product_id) //push
            ->where('month', $request->month)
            ->first();

        if ($existingTarget) {
            return response()->json([
                'message' => 'Error',
                'errors' => ['employee_id' => ['Target already set for this employee in the selected month and year!']]
            ], 422);
        }

        $target = Target::create([
            'employee_type_id' => $request->employee_type,
            'employee_id' => $request->employee_id,
            'product_id' => ProductHelper::getSelectedProductId(),
            'year' => $request->year,
            'month' => $request->month,
            'unique_lead' => $request->unique_lead,
            'customer_visit' => $request->customer_visit,
            'aashiyana' => $request->aashiyana,
            'order_quantity' => $request->order_quantity,
            'status' => 1
        ]);

        return response()->json(['message' => 'Target created successfully!', 'target' => $target], 200);
    }
    public function update(Request $request)
    {
        $target = Target::find($request->id);

        if (!$target) {
            return response()->json(['error' => 'Target not found'], 404);
        }

        $target->update([
            'employee_id' => $request->employee_id,
            'year' => $request->year,
            'month' => $request->month,
            'unique_lead' => $request->unique_lead,
            'customer_visit' => $request->customer_visit,
            'aashiyana' => $request->aashiyana,
            'order_quantity' => $request->order_quantity,
        ]);

        return response()->json(['message' => 'Target updated successfully']);
    }
    public function viewTargets($id)
    {
        if (!$id) {
            return response()->json(['error' => 'Missing target ID.'], 400);
        }

        $target = Target::with(['employee.employeeType'])->find($id);

        if (!$target) {
            return response()->json(['error' => 'Target not found.'], 404);
        }
        return response()->json([
            'target' => $target
        ]);
    }
    public function getTargetDetails($id)
    {
        $target = Target::with(['employee.employeeType'])->find($id);
    
        if (!$target) {
            return response()->json(['error' => 'Target not found'], 404);
        }
    
        return response()->json([
            'target' => [
                'employee_type' => optional($target->employee->employeeType)->type_name ?? '-',
                'employee_name' => optional($target->employee)->name ?? '-',
                'year' => $target->year ?? '-',
                'month' => $target->month ?? '-',
                'unique_lead' => $target->unique_lead ?? '0',
                'customer_visit' => $target->customer_visit ?? '0',
                'aashiyana' => $target->aashiyana ?? '0',
                'order_quantity' => $target->order_quantity ?? '0',
                'employee_type_id' => optional($target->employee)->employee_type_id ?? '',
                'employee_id' => $target->employee_id ?? '',
            ]
        ]);
    }
    

    public function destroy($id)
    {
        $target = Target::findOrFail($id);
        $target->status = '0';
        $target->save();
        $target->delete();

        return response()->json(['success' => true, 'message' => 'Target deleted successfully!']);
    }
    public function getTargets(Request $request)
    {
        try {
            $monthNumber = $request->month ?? Carbon::now()->month;
            $month = $request->month ? Carbon::createFromDate(null, $request->month, 1)->format('F') : Carbon::now()->format('F');
            $year = $request->year ?? Carbon::now()->year;
           
            $employeeId = $request->employee_id ?? Auth::id();
    
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found.",
                ], 404);
            }
            $productId = Product::where('product_code', $request->product_code)->value('id'); //push
            if(!$productId){
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => "Invalid product code.",
                ], 404);
            }

            $target = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('product_id', $productId)
                            ->where('year', $year)
                            ->first();
            $target = $target ? $target->toArray() : null;

            if (
                $target &&
                Auth::id() === (int) $target['employee_id'] &&
                $target['notification_status'] === 'pending'
            ) {
                Target::where('id', $target['id'])
                    ->update(['notification_status' => 'opened']);
                    $target = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('product_id', $productId)
                            ->where('year', $year)
                            ->first();
                     $target = $target ? $target->toArray() : null;
            }
            
            $uniqueLeads = Lead::where('created_by', $employeeId)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $monthNumber)
                                ->distinct('phone')
                                ->count();
    
            // $customerVisitCount = RescheduledRoute::where('employee_id', $employeeId)
            //     ->whereYear('assign_date', $year)
            //     ->whereMonth('assign_date', $monthNumber)
            //     ->get()
            //     ->sum(function ($route) {
            //         $customers = collect(json_decode($route->customers ?? '[]', true));
            //         return $customers->where('scheduled', true)->where('status', 'Completed')->count();
            //     });
            // $customerVisitCount = InfluencerVisit::where('created_by', $employeeId)
            //     ->whereYear('created_at', $year)
            //     ->whereMonth('created_at', $monthNumber)
            //     ->count();
            $customerVisitCount = InfluencerVisit::where('created_by', $employeeId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber)
                ->distinct('phone')   
                // ->whereHas('order', function ($query) use ($productId) { //push
                //     $query->where('product_id', $productId);
                // })
                ->count('phone');

               
                 
    
            $aashiyanaCount = Order::where('created_by', $employeeId)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $monthNumber)
                                ->where('product_id', $productId) //push
                                ->where('payment_terms_id', 3)
                                ->count();
    
            $orders = Order::where('created_by', $employeeId)
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $monthNumber)
                            ->where('product_id', $productId)//push
                            ->where('order_approved', '1')
                            ->pluck('id');
    
            $achievedOrderQuantity = DB::table('order_items')
                                        ->whereIn('order_id', $orders)
                                        ->sum('total_quantity');
    
            $response = [
                'employee' =>[
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->name,
                    'employee_type_id' => $employee->employee_type_id,
                ],
                'target' => $target,
                'achieved' => [
                    'unique_leads' => $uniqueLeads,
                    'customer_visit' => $customerVisitCount, 
                    'aashiyana' => $aashiyanaCount,
                    'order_quantity' => (float) $achievedOrderQuantity,
                ],
            ];
            $targetchange = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('year', $year)
                            ->where('notification_status', "pending")
			    ->get();
	        $authController = new AuthController();

            foreach($targetchange as $item)
	        {
		    // dd($item);
                //......................notification..............
            //  $authController = new AuthController();
                $authController->changeNotificationStatus('targets', $item->id,'opened');  
            }
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Target data retrieved successfully.',
                'data' => $response,
            ], 200);
        
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function view($id)
    {
        $target = Target::join('employees', 'employees.id', '=', 'Target.employee_id')
            ->join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
            ->where('Target.id', $id)
            ->select(
                'Target.id',
                'Target.created_at as from_date',
                'Target.*',
                'employees.name as employee_name',
                'employee_types.type_name as employee_type',
                DB::raw("CONCAT(Target.month, '-', Target.year) as to_date")
            )
            ->first(); // Fetch a single record

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'Target not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => view('sales.target.view', compact('target'))->render()
        ]);
    }
    public function getVisitCount($employeeType, $employee)
    {
        $visitCount = Lead::where('created_by', $employee)->count();
        return response()->json(['visit_count' => $visitCount]);
    }
    // public function getTotalTargetsAchievements(Request $request)
    // {
    //     $employee = Auth::user();
    
    //     if (!$employee) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 401,
    //             'message' => "User not authenticated.",
    //         ], 401);
    //     }
    
    //     if ($employee->employee_type_id !== 5) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 403,
    //             'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
    //         ], 403);
    //     }
    
    //     $monthNumber = $request->input('month', date('m'));
    //     $year = $request->input('year', date('Y'));
    //     $month = Carbon::createFromDate(null, $monthNumber, 1)->format('F');
   
    //     $employeeTypeId = $request->input('employee_type_id', 2); 
   
       
    //     $employeeIds = Employee::where('employee_type_id', $employeeTypeId)->pluck('id')->toArray();
    
    //     // Initialize totals
    //     $totalTargets = [
    //         'unique_leads' => 0,
    //         'influencer_visits' => 0,
    //         'aashiyana_orders' => 0,
    //         'product_quantity' => 0,
    //     ];
    
    //     $totalAchieved = [
    //         'unique_leads' => 0,
    //         'influencer_visits' => 0,
    //         'aashiyana_orders' => 0,
    //         'product_quantity' => 0,
    //     ];

    //     foreach ($employeeIds as $empId) {
      
    //         $target = Target::where('employee_id', $empId)
    //                         ->where('month', $month)
    //                         ->where('year', $year)
    //                         ->first();
   
    //         if ($target) {
    //             $totalTargets['unique_leads'] += $target->unique_lead ?? 0;
    //             $totalTargets['influencer_visits'] += $target->customer_visit ?? 0;
    //             $totalTargets['aashiyana_orders'] += $target->aashiyana ?? 0;
    //             $totalTargets['product_quantity'] += $target->order_quantity ?? 0;
    //         }
    
    //         // Achievements
    //         $totalAchieved['unique_leads'] += Lead::where('created_by', $empId)
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $monthNumber)
    //             ->count();
    
    //         // $customerVisitCount = InfluencerVisit::where('created_by', $empId)
    //         //     ->whereYear('created_at', $year)
    //         //     ->whereMonth('created_at', $monthNumber)
    //         //     ->count();
    //         $customerVisitCount = InfluencerVisit::where('created_by', $empId)
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $monthNumber)
    //             ->distinct('phone')   
    //             ->count('phone');
    
    //         $totalAchieved['influencer_visits'] += $customerVisitCount;
    
    //         $totalAchieved['aashiyana_orders'] += Order::where('created_by', $empId)
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $monthNumber)
    //             ->where('payment_terms_id', 3)
    //             ->count();
    
    //         $orders = Order::where('created_by', $empId)
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $monthNumber)
    //             ->where('order_approved', '1')
    //             ->get();

    //         $orderQuantity = DB::table('order_items')
    //                                     ->whereIn('order_id', $orders)
    //                                     ->sum('total_quantity');
    //         // $orderQuantity = $orders->sum('invoice_quantity');
            
    //         $totalAchieved['product_quantity'] += $orderQuantity;
    //     }
  
    //     return response()->json([
    //         'success' => true,
    //         'statusCode' => 200,
    //         'message' => "Target vs Achievement summary fetched successfully.",
    //         'data' => [
    //             'month' => $month,
    //             'year' => (int) $year,
    //             'employee_type_id' => (int) $employeeTypeId,
    //             'targets' => $totalTargets,
    //             'achievements' => $totalAchieved,
    //         ]
    //     ]);
    // }
    public function getTotalTargetsAchievements(Request $request)
    {
        $employee = Auth::user();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'employee_type_id' => 'required|integer',
            'product_id' => 'required|integer',
        ]);

        $monthNumber = $request->month;
        $year = $request->year;
        $productId = $request->product_id;
        $employeeTypeId = $request->employee_type_id;

        $monthName = Carbon::createFromDate(null, $monthNumber, 1)->format('F');

        $from = Carbon::create($year, $monthNumber, 1)->startOfMonth();
        $to = Carbon::create($year, $monthNumber, 1)->endOfMonth();

        /** EMPLOYEES */
        $employeeIds = Employee::where('employee_type_id', $employeeTypeId)
            ->whereJsonContains('products', (string) $productId)
            ->pluck('id')
            ->toArray();

        if (empty($employeeIds)) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'month' => $monthName,
                    'year' => (int) $year,
                    'employee_type_id' => (int) $employeeTypeId,
                    'targets' => [],
                    'achievements' => [],
                ]
            ]);
        }

        /** TARGET TOTALS */
        $targets = Target::whereIn('employee_id', $employeeIds)
            ->where('month', $monthName)
            ->where('year', $year)
            ->where('product_id', $productId)
            ->get();

        $totalTargets = [
            'unique_leads' => $targets->sum('unique_lead'),
            'influencer_visits' => $targets->sum('customer_visit'),
            'aashiyana_orders' => $targets->sum('aashiyana'),
            'product_quantity' => $targets->sum('order_quantity'),
        ];

        /** UNIQUE LEADS */
        $uniqueLeads = Lead::whereIn('created_by', $employeeIds)
            ->whereBetween('created_at', [$from, $to])
            ->where(function ($q) {
                $q->where('status', '!=', 'Follow Up')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'Follow Up')
                            ->whereNotNull('follow_up_date');
                    });
            })
            ->distinct('phone')
            ->count('phone');

        /** INFLUENCER VISITS */
      //  $influencerVisits = InfluencerVisit::whereIn('created_by', $employeeIds)
        //    ->whereBetween('created_at', [$from, $to])
            // ->distinct('phone')
        //    ->count('phone');

             $influencerVisits = InfluencerVisit::whereIn('created_by', $employeeIds)
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('order', function ($query) use ($productId) {  //push
                                $query->where('product_id', $productId);
                            })
		->where(function ($query) {
                $query->where('status', '!=', 'Follow Up')
                    ->orWhere(function ($q) {
                        $q->where('status', 'Follow Up')
                            ->whereNotNull('follow_up_date');
                    });
            })		->distinct('phone')   
                ->count('phone');


        $aashiyanaCount = Order::whereYear('created_at', $year)
            ->whereMonth('created_at', $monthNumber)
            ->where('product_id', $productId)
            ->where('payment_terms_id', 3)
            ->whereIn('created_by', $employeeIds)
            ->count();

        $orders = Order::whereYear('created_at', $year)
            ->whereMonth('created_at', $monthNumber)
            ->where('product_id', $productId)
            ->where('order_approved', 1)
            ->whereIn('created_by', $employeeIds)
            ->pluck('id');

        $achievedOrderQuantity = DB::table('order_items')
            ->whereIn('order_id', $orders)
            ->where('product_id', $productId)
            ->sum('total_quantity');



        $totalAchieved = [
            'unique_leads' => $uniqueLeads,
            'influencer_visits' => $influencerVisits,
            'aashiyana_orders' => $aashiyanaCount,
            'product_quantity' => (float) $achievedOrderQuantity,
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Target vs Achievement summary fetched successfully.",
            'data' => [
                'month' => $monthName,
                'year' => (int) $year,
                'employee_type_id' => (int) $employeeTypeId,
                'product_id' => (int) $productId,
                'targets' => $totalTargets,
                'achievements' => $totalAchieved,
            ]
        ]);
    }
    
    public function getTotalTargetsAchievementsold(Request $request)
    {
        $employee = Auth::user();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'employee_type_id' => 'required|integer',
            'product_id' => 'required|integer',
        ]);

        $monthNumber = $request->month;
        $year = $request->year;
        $productId = $request->product_id;
        $employeeTypeId = $request->employee_type_id;

        $monthName = Carbon::createFromDate(null, $monthNumber, 1)->format('F');

        $employeeIds = Employee::where('employee_type_id', $employeeTypeId)
            //->whereRaw('FIND_IN_SET(?, products)', [$productId])
            ->when($productId, function ($q) use ($productId) {
                $q->whereJsonContains('products', (string) $productId);
            })
            ->pluck('id')
            ->toArray();

        if (empty($employeeIds)) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'month' => $monthName,
                    'year' => (int) $year,
                    'employee_type_id' => (int) $employeeTypeId,
                    'targets' => [],
                    'achievements' => [],
                ]
            ]);
        }

    
        $totalTargets = [
            'unique_leads' => 0,
            'influencer_visits' => 0,
            'aashiyana_orders' => 0,
            'product_quantity' => 0,
        ];

        $totalAchieved = [
            'unique_leads' => 0,
            'influencer_visits' => 0,
            'aashiyana_orders' => 0,
            'product_quantity' => 0,
        ];

    
        $targets = Target::whereIn('employee_id', $employeeIds)
            ->where('month', $monthName)
            ->where('year', $year)
            ->where('product_id', $productId)
            ->get();

        foreach ($targets as $target) {
            $totalTargets['unique_leads'] += $target->unique_lead ?? 0;
            $totalTargets['influencer_visits'] += $target->customer_visit ?? 0;
            $totalTargets['aashiyana_orders'] += $target->aashiyana ?? 0;
            $totalTargets['product_quantity'] += $target->order_quantity ?? 0;
        }


        
        foreach ($employeeIds as $empId) {

            $totalAchieved['unique_leads'] += Lead::where('created_by', $empId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber)
                ->count();

            $totalAchieved['influencer_visits'] += InfluencerVisit::where('created_by', $empId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber)
                ->distinct('phone')
                ->count('phone');

            $totalAchieved['aashiyana_orders'] += Order::where('created_by', $empId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber)
                ->where('payment_terms_id', 3)
                ->where('order_approved', '1')
                ->count();

            $orderIds = Order::where('created_by', $empId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNumber)
                ->where('order_approved', '1')
                ->pluck('id');

            $productQty = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->where('product_id', $productId)
                ->sum('total_quantity');

            $totalAchieved['product_quantity'] += (float) $productQty;
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Target vs Achievement summary fetched successfully.",
            'data' => [
                'month' => $monthName,
                'year' => (int) $year,
                'employee_type_id' => (int) $employeeTypeId,
                'product_id' => (int) $productId,
                'targets' => $totalTargets,
                'achievements' => $totalAchieved,
            ]
        ]);
    }

    public function dealer_index()
    {
        $targets = DealerTarget::all(); 
        $productId = ProductHelper::getSelectedProductId(); 
        $dealers = Dealer::whereJsonContains('products', (string) $productId)->get();
        return view('sales.target.dealer_index', compact('targets','dealers'));
    }
    public function dealerTargetList(Request $request)
    {
        $productId = ProductHelper::getSelectedProductId(); 

        $query = DealerTarget::with(['dealer'])
            ->where('status', '1')
            ->whereNotNull('dealer_id')
            ->whereHas('dealer', function ($q) use ($productId) {
                $q->whereJsonContains('products', (string) $productId);
            })
            ->withTrashed();

        if ($request->has('dealer_id') && !empty($request->dealer_id)) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }

        if ($request->has('month') && !empty($request->month)) {
            $query->where('month', $request->month);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request, $productId) {

                if (!empty($request->search['value'])) {
                    $searchValue = $request->search['value'];

                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->whereHas('dealer', function ($q) use ($searchValue) {
                                $q->where('dealer_name', 'like', "%{$searchValue}%");
                            })
                            ->orWhere('year', 'like', "%{$searchValue}%")
                            ->orWhere('month', 'like', "%{$searchValue}%")
                            ->orWhere('order_quantity', 'like', "%{$searchValue}%");
                    });
                }

                // Ensure dealer exists + product filter stays applied
                $query->whereHas('dealer', function ($q) use ($productId) {
                    $q->whereJsonContains('products', (string) $productId);
                });
            })
            ->addIndexColumn()
            ->addColumn('dealer_name', function ($target) {
                return optional($target->dealer)->dealer_name ?? '-';
            })
            ->addColumn('year', function ($target) {
                return $target->year ?? '-';
            })
            ->addColumn('month', function ($target) {
                return $target->month ?? '-';
            })
            ->addColumn('order_quantity', function ($target) {
                return $target->order_quantity ?? '0';
            })
            ->addColumn('action', function ($target) {
                return '
                    <button class="btn btn-sm btn-warning" onclick="handleDealerAction(' . $target->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteDealerTarget(' . $target->id . ')" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function dealerTargetStore(Request $request)
    {
        $request->validate([
            'dealer_id'       => 'required|exists:dealers,id',
            'year'            => 'required|digits:4',
            'month'           => 'required',
            'order_quantity' => 'required|numeric|min:0',
        ]);

        $productId = ProductHelper::getSelectedProductId();

        // Optional: prevent duplicate entry for same dealer + year + month + product
        $exists = DealerTarget::where('dealer_id', $request->dealer_id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('product_id', (int) $productId)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Target already exists for this dealer, month and product.'
            ], 422);
        }

        $dealerTarget = DealerTarget::create([
            'dealer_id'       => $request->dealer_id,
            'year'            => $request->year,
            'month'           => $request->month,
            'order_quantity' => $request->order_quantity,
            'product_id'      => $productId, // if column exists
            'status'          => 1,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Dealer target created successfully.',
            'data'    => $dealerTarget
        ]);
    }
    
    public function dealerTargetDelete($id)
    {
        $dealerTarget = DealerTarget::withTrashed()->find($id);

        if (!$dealerTarget) {
            return response()->json([
                'status' => false,
                'message' => 'Dealer target not found.'
            ], 404);
        }

        $dealerTarget->forceDelete(); // 🔥 Permanently delete

        return response()->json([
            'status' => true,
            'message' => 'Dealer target permanently deleted successfully.'
        ]);
    }
    public function viewDealerTargets($id)
    {
        $dealerTarget = DealerTarget::findOrFail($id);


        if (!$dealerTarget) {
            return response()->json([
                'status' => false,
                'message' => 'Dealer target not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $dealerTarget   // 👈 single object
        ]);
    }

    public function dealerTargetUpdate(Request $request)
    {
        $request->validate([
            'dealer_target_id' => 'required|exists:dealer_targets,id',
            'dealer_id'        => 'required|exists:dealers,id',
            'year'             => 'required',
            'month'            => 'required',
            'order_quantity'   => 'required|numeric|min:0',
        ]);

        $productId = (int) ProductHelper::getSelectedProductId();

        $dealerTarget = DealerTarget::findOrFail($request->dealer_target_id);

        // 🔥 Duplicate check (ignore current record)
        $exists = DealerTarget::withTrashed()
            ->where('dealer_id', $request->dealer_id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('product_id', $productId)
            ->where('id', '!=', $dealerTarget->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Target already exists for this dealer, month and product.'
            ], 422);
        }

        $dealerTarget->update([
            'dealer_id'      => $request->dealer_id,
            'year'           => $request->year,
            'month'          => $request->month,
            'order_quantity' => $request->order_quantity,
            'product_id'     => $productId,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Dealer target updated successfully.'
        ]);
    }

    public function getDealerTargets(Request $request)
    {
        try {

            $dealerId = Auth::id();

            if (!$dealerId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized.'
                ], 401);
            }

            $monthNo   = $request->month ?? Carbon::now()->month;
            $year      = $request->year ?? Carbon::now()->year;
            $productId = $request->product_id;

            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Product id is required.'
                ], 400);
            }

            $monthName = Carbon::createFromDate(null, $monthNo, 1)->format('F');

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Dealer Target
            |--------------------------------------------------------------------------
            */

            $target = DealerTarget::where('dealer_id', $dealerId)
                ->where('month', $monthName)
                ->where('year', $year)
                ->where('product_id', $productId)
                ->first();

            $targetData = $target ? [
                'order_quantity' => (float) $target->order_quantity,
            ] : null;


            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Dealer Achievement
            |--------------------------------------------------------------------------
            */

            $orders = Order::where(function ($query) use ($dealerId) {
                $query->where('dealer_id', $dealerId)
                    ->orWhere('created_by_dealer', $dealerId);
            })
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $monthNo)
            ->pluck('id');


            $achievedQty = DB::table('order_items')
                ->whereIn('order_id', $orders)
                ->where('product_id', $productId)
                ->sum('total_quantity');


            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer target data fetched successfully.',
                'data' => [
                    'dealer_id' => $dealerId,
                    'month' => $monthName,
                    'year' => (int) $year,
                    'product_id' => (int) $productId,
                    'target' => $targetData,
                    'achieved' => [
                        'order_quantity' => (float) $achievedQty,
                    ],
                ]
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
