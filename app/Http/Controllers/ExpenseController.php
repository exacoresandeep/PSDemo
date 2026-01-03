<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DayExpense;
use App\Models\EmployeeType;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\ExpenseExport;

class ExpenseController extends Controller
{
    public function export(Request $request)
    {
        $from_date = $request->input('from_date');
        $to_date   = $request->input('to_date');
        $employee_type = $request->employee_type;
        $employee_id = $request->employee_id;
        $travel_method = $request->travel_method;

        return Excel::download(
            new ExpenseExport($from_date, $to_date, $employee_type, $employee_id, $travel_method),
            'dayend_report.xlsx'
        );
    }

    public function travelMethod()
    {
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Travel methods fetched successfully',
            'data' => array_keys(config('travel_methods')), // only names
        ], 200);
    }

    public function travelMethodNew()
    {

        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->first();
        
        $totalKilometer = 0;
        if ($attendance) {
            $totalKilometer = max(0, ($attendance->ending_km ?? 0) - ($attendance->starting_km ?? 0));
        }
        $methods = collect(config('travel_methods'))
            ->map(fn($price, $name) => ["name" => $name, "price" => $price])
            ->values();

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Travel methods fetched successfully',
            'data' => [ 
                    "Methods"=>$methods,
                    "Total kilometer"=>$totalKilometer
                    ],
        ], 200);
    }

    public function storeDayExpense(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');

        $request->validate([
            'travel_method' => 'required|string|max:100',
            // 'km_traveled'   => 'nullable|integer|min:0',
            'other_expense' => 'nullable|numeric',
            'remarks'       => 'nullable|string|max:1000',
            'route'       => 'nullable|string|max:255',
            'attachment'    => 'nullable|array',
            'attachment.*'  => 'nullable|string|max:256',
        ]);

        $dayExpense = DayExpense::where('employee_id', $employeeId)
                                ->whereDate('created_at', $date)
                                ->first();

        if ($dayExpense) {
            return response()->json([
                'success'    => false,
                'statusCode' => 400,
                'message'    => 'Expense already recorded for today.',
            ]);
        }
        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->whereNull('punch_out')
                                ->first();
        if ($attendance) {
            return response()->json([
                'success'    => false,
                'statusCode' => 400,
                'message'    => 'You cannot add day expense before punching out.',
            ]);
        }

        $attachments = $request->attachment ?? [];
        $methodRates = config('travel_methods');
        $rate = $methodRates[$request->travel_method] ?? 0;
        $totalAmount = ($request->km_traveled * $rate) + ($request->other_expense ?? 0);

        $dayExpense = DayExpense::create([
            'employee_id'   => $employeeId,
            'travel_method' => $request->travel_method,
            'km_traveled'   => $request->km_traveled,
            'other_expense' => $request->other_expense,
            'remarks'       => $request->remarks,
            'route'         => $request->route,
            'attachment'    => json_encode($attachments),
            'total_amount'  => $totalAmount,
        ]);

        $fileUrls = collect($attachments)->map(function ($file) {
            return url('storage/uploads/' . $file);
        });

        return response()->json([
            'success'    => true,
            'statusCode' => 201,
            'message'    => 'Day expense stored successfully',
            'data'       => [
                'id'            => $dayExpense->id,
                'employee_id'   => $dayExpense->employee_id,
                'date'          => $dayExpense->created_at->format('Y-m-d'),
                'travel_method' => $dayExpense->travel_method,
                'km_traveled'   => $dayExpense->km_traveled,
                'other_expense' => $dayExpense->other_expense,
                'remarks'       => $dayExpense->remarks,
                'route'         => $dayExpense->route,                
                'attachment'    => $fileUrls,
                'total_amount'  => $dayExpense->total_amount,
            ]
        ]);
    }

    public function expenseSummary(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'from_date' => 'required|date_format:d/m/Y',
            'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
        ]);

        $fromDate = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
        $toDate   = $request->to_date 
            ? Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d')
            : now()->toDateString();

        $query = DayExpense::where('employee_id', $userId)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);

        $methodRates = config('travel_methods');

        $allExpenses = $query->orderBy('created_at', 'desc')->get()->map(function ($expense) use ($methodRates) {
            $rate = $methodRates[$expense->travel_method] ?? 0;
            $calculatedAmount = ($expense->km_traveled * $rate) + ($expense->other_expense ?? 0);

            return [
                'id'            => $expense->id,
                'employee_id'   => $expense->employee_id,
                'date'          => $expense->created_at->format('d/m/Y'),
                'travel_method' => $expense->travel_method,
                'rate' => $rate,
                'km_traveled'   => $expense->km_traveled,
                'other_expense' => $expense->other_expense,
                'remarks'       => $expense->remarks,
                'route'         => $expense->route,
                'attachments'   => $expense->attachment ? json_decode($expense->attachment, true) : [],
                'total_amount'  => $calculatedAmount,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Expense summary fetched successfully',
            'data' => [
                "expense_data" => $allExpenses,
                'summary' => [
                    'total_expenses' => $allExpenses->count(),
                    'total_km'       => $allExpenses->sum('km_traveled'),
                    'total_other'    => $allExpenses->sum('other_expense'),
                    'total_amount'   => $allExpenses->sum('total_amount'),
                    'from_date'      => Carbon::parse($fromDate)->format('d/m/Y'),
                    'to_date'        => Carbon::parse($toDate)->format('d/m/Y'),
                ]
            ]
        ]);
    }

    public function index()
    {
        $expense = DayExpense::all();
        $employeeTypes = EmployeeType::all();
        return view('sales.dayend.index', compact('expense','employeeTypes'));
    }

    public function list(Request $request)
    {
        // $query = DayExpense::with(['employee.employeeType']);
        $user = Auth::user();
        $product_ids = is_array($user->product_ids)? $user->product_ids : json_decode($user->product_ids, true);
        
       $query = DayExpense::with(['employee.employeeType'])
            ->whereHas('employee', function ($sub) use ($product_ids) {
                $sub->where(function ($q) use ($product_ids) {
                    foreach ($product_ids as $pid) {
                        $q->orWhereRaw("JSON_CONTAINS(employees.products, '\"$pid\"')");
                    }
                });
            });
        if (!empty($request->employee_type)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employee_type_id', $request->employee_type);
            });
        }

        if (!empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }

        if (!empty($request->from_date) && !empty($request->to_date)) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        }

        if (!empty($request->travel_method)) {
            $query->where('travel_method', $request->travel_method);
        }

        $query->orderBy('created_at', 'desc');
        $methodPrices = config('travel_methods');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->whereHas('employee', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                          ->orWhere('employee_code', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('travel_method', 'like', "%{$searchValue}%")
                    ->orWhere('remarks', 'like', "%{$searchValue}%")
                    ->orWhere('route', 'like', "%{$searchValue}%")
                    ->orWhere('total_amount', 'like', "%{$searchValue}%");
                }
            })
            ->addIndexColumn()
            ->addColumn('employee', fn($t) => optional($t->employee)->name . ' / ' . optional($t->employee)->employee_code)
            ->addColumn('date', fn($t) => $t->created_at ? $t->created_at->format('d-m-Y') : '-')
            ->addColumn('time', fn($t) => $t->created_at ? $t->created_at->format('h:i A') : '-')
            ->addColumn('travel_method', fn($t) => ucfirst($t->travel_method ?? '-'))
            ->addColumn('km_traveled', fn($t) => $t->km_traveled ?? 0)
            ->addColumn('route', fn($t) => $t->route ?? '-')
            ->addColumn('other_expense', fn($t) => number_format($t->other_expense ?? 0, 2))
            ->addColumn('remarks', fn($t) => $t->remarks ?? '-')
            ->addColumn('attachment', function ($t) {
                $images = [];

                if (!empty($t->attachment)) {
                    $images = json_decode($t->attachment, true);
                }

                if (empty($images)) {
                    return '-';
                }

                return '<a href="javascript:void(0);" data-type="Starting" class="view-images" data-images=\'' . json_encode($images) . '\'>View</a>';
            })
            
            ->addColumn('total_amount', function ($t) use ($methodPrices) {
                $pricePerKm = $methodPrices[$t->travel_method] ?? 0;
                $km = $t->km_traveled ?? 0;
                $other = $t->other_expense ?? 0;

                $total = ($km * $pricePerKm) + $other;
                return number_format($total, 2);
            })
            ->rawColumns(['attachment'])
            ->make(true);
    }
}
