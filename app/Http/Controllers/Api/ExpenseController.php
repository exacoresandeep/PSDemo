<?php
namespace App\Http\Controllers\Api;
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DayExpense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class ExpenseController extends Controller
{
    /**
     * Get Travel Methods
     */
	public function travelMethodNew()
    {
        $methods = [
            ["name" => "Bike", "price" => 4.0],
            ["name" => "Car", "price" => 5.6],
            ["name" => "Own Car", "price" => 9.0],
        ];

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Travel methods fetched successfully',
            'data' => $methods
        ], 200);
    }
    public function travelMethod()
    {
        $methods = ["Bike", "Car", "Own Car"];

        return response()->json([
            'status' => true,
             'statusCode' => 200,
            'message' => 'Travel methods fetched successfully',
            'data' => $methods
        ], 200);
    }

    public function storeDayExpense(Request $request)
    {
        $employeeId = Auth::id(); 
        $date = Carbon::today()->format('Y-m-d');
        
        $request->validate([
            'travel_method' => 'required|string|max:100',
            'km_traveled'   => 'nullable|integer|min:0',
            'other_expense' => 'nullable|numeric',
            'remarks'       => 'nullable|string|max:1000',
            'attachment'    => 'nullable|array', 
            'attachment.*'  => 'nullable|string|max:256',
            'total_amount'  => 'required|numeric|min:0',
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

        $dayExpense = DayExpense::create([
            'employee_id'   => $employeeId,
            'travel_method' => $request->travel_method,
            'km_traveled'   => $request->km_traveled,
            'other_expense' => $request->other_expense,
            'remarks'       => $request->remarks,
            'attachment'    => json_encode($attachments),
            'total_amount'  => $request->total_amount,
        ]);

        $fileUrls = collect($attachments)->map(function ($file) {
            return url('storage/uploads/' . $file);
        });

        return response()->json([
            'success'    => true,
            'statusCode' => 201,
            'message'    => 'Day expense stored successfully',
            'data'       => [
                'id'   => $dayExpense->id,
                'employee_id'   => $dayExpense->employee_id,
                'date'          => $dayExpense->created_at->format('Y-m-d'),
                'travel_method' => $dayExpense->travel_method,
                'km_traveled'   => $dayExpense->km_traveled,
                'other_expense' => $dayExpense->other_expense,
                'remarks'       => $dayExpense->remarks,
                'attachment'    => $fileUrl,
                'total_amount'  => $dayExpense->total_amount,
            ]
        ]);
    }

    public function expenseSummary(Request $request)
    {
        $userId = auth()->id(); // logged-in employee

        $request->validate([
            'from_date' => 'required|date_format:d/m/Y',
            'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
        ]);

        // Convert to Y-m-d for database query
        $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
        $toDate   = $request->to_date 
            ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d') 
            : now()->toDateString();
            
        // Query for user's expenses in date range
        $query = DayExpense::where('employee_id', $userId)
            ->whereDate('created_at', '>=', $request->from_date)
            ->whereDate('created_at', '<=', $toDate);

        // Totals
        $totalKm      = $query->sum('km_traveled');
        $totalOther   = $query->sum('other_expense');
        $totalAmount  = $query->sum('total_amount');
        $expenseCount = $query->count();

        // Get all records and format created_at
        $allExpenses = $query->orderBy('created_at', 'desc')->get()->map(function ($expense) {
            return [
                'id'            => $expense->id,
                'employee_id'   => $expense->employee_id,
                'date'          => $expense->created_at->format('d/m/Y'),
                'travel_method' => $expense->travel_method,
                'km_traveled'   => $expense->km_traveled,
                'other_expense' => $expense->other_expense,
                'remarks'       => $expense->remarks,
                'attachments'   => $expense->attachment ? json_decode($expense->attachment, true) : [],
                'total_amount'  => $expense->total_amount,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Expense summary fetched successfully',
            
            'data' => [
                "expense_data"=>$allExpenses,
                'summary' => [
                    'total_expenses' => $expenseCount,
                    'total_km'       => $totalKm,
                    'total_other'    => $totalOther,
                    'total_amount'   => $totalAmount,
                    'from_date'      => \Carbon\Carbon::parse($request->from_date)->format('d/m/Y'),
                    'to_date'        => \Carbon\Carbon::parse($toDate)->format('d/m/Y'),
                ]
            ]

        ]);
    }


}

