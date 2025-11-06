<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\TripExpense;
use App\Models\Trip;
use App\Models\Bata;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class BataController extends Controller
{
    public function create()
    {
        $drivers = Driver::select('id', 'name', 'phone')->get();
        return view('logistics.bata.create', compact('drivers'));
    }
    public function index()
    {
        return view('logistics.bata.index');
    }
    public function store(Request $request)
    {
        $request->validate([
            'driver_name' => 'required',
            'phone' => 'required',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'payments_json' => 'required'
        ]);

        $driver = Driver::where('name', $request->driver_name)->firstOrFail();

        $payments = json_decode($request->payments_json, true) ?? [];
        $deductions = json_decode($request->deduction_json, true) ?? [];
        $expenses = json_decode($request->expenses_json, true) ?? [];
        
        $totalPayment = array_sum(array_column($payments, 'amount'));
        $totalExpense = array_sum(array_column($expenses, 'amount'));
        $totalDeduction = array_sum(array_column($deductions, 'amount'));
        $payableAmount = $totalPayment + $totalExpense - $totalDeduction;


        $bata = Bata::create([
            'driver_id' => $driver->id,
            'phone' => $request->phone,
            'from_date' => Carbon::parse($request->from_date),
            'to_date' => Carbon::parse($request->to_date),
            'total_amount' =>$totalPayment + $totalExpense,
            'deduction_amount' => $totalDeduction,
            'payable_amount' => $payableAmount,
        ]);

        foreach($payments as $p){
            $bata->trips()->create([
                'trip_code' => $p['tripId'],
                'salary_type' => $p['salaryType'],
                'amount' => $p['amount']
            ]);
        }

        foreach($deductions as $d){
            $bata->deductions()->create([
                'trip_code' => $d['trip_id'],
                'reason' => $d['reason'],
                'duration' => $d['duration'],
                'amount' => $d['amount'],
                'remarks' => $d['remarks'] ?? null
            ]);
        }

        return redirect()->route('logistics.bata.index')->with('success','Bata added successfully!');
    }
    public function getData(Request $request)
    {
        $query = Bata::with('driver');

        if($request->driver_name) {
            $query->whereHas('driver', fn($q) => $q->where('name', 'like', '%'.$request->driver_name.'%'));
        }

        if($request->phone) {
            $query->whereHas('driver', fn($q) => $q->where('phone', 'like', '%'.$request->phone.'%'));
        }

        if($request->from_date) {
            $query->whereDate('from_date', '>=', $request->from_date);
        }

        if($request->to_date) {
            $query->whereDate('to_date', '<=', $request->to_date);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('driver_name', fn($row) => $row->driver->name)
            ->addColumn('phone', fn($row) => $row->driver->phone)
            ->addColumn('no_of_trips', function($row) {
                $tripCodes = $row->trips()->pluck('trip_code');
                return \App\Models\Trip::whereIn('trip_code', $tripCodes)->count();
            })
            ->addColumn('total_km', function($row) {
                $tripCodes = $row->trips()->pluck('trip_code');
                $totalKm = \App\Models\Trip::whereIn('trip_code', $tripCodes)->sum('approx_km');
                return number_format($totalKm, 2);
            })
            ->addColumn('total_amount', fn($row) => '₹ '.number_format($row->payable_amount, 2))
            ->addColumn('status', fn($row) => $row->status ? 'Paid' : 'Pending')
            ->addColumn('action', fn($row) => '<a href="'.route("logistics.bata.show", $row->id).'" class="btn btn-sm btn-primary">View</a>')
            ->rawColumns(['action'])
            ->make(true);
    }
    public function fetchExpenses(Request $request)
    {
        $tripCodes = $request->get('trip_ids', []);

        if (empty($tripCodes)) {
            return response()->json([]);
        }

        $tripIds = Trip::whereIn('trip_code', $tripCodes)->pluck('id');

        if ($tripIds->isEmpty()) {
            return response()->json([]);
        }

        $expenses = TripExpense::with(['trip', 'expenseType'])
            ->whereIn('trip_id', $tripIds)
            ->get()
            ->map(function ($expense) {
                return [
                    'trip_code' => $expense->trip->trip_code ?? '-',
                    'expense_type' => $expense->expenseType->name ?? '-',
                    'bill_image' => $expense->bill_image ? asset('storage/' . $expense->bill_image) : null,
                    'amount' => number_format($expense->amount, 2)
                ];
            });

        return response()->json($expenses);
    }


    public function searchDrivers(Request $request)
    {
        $search = $request->get('q');

        $drivers = Driver::where('name', 'like', "%{$search}%")
            ->select('id', 'name', 'phone')
            ->take(10)
            ->get();

        return response()->json($drivers);
    }

    public function filterTrips(Request $request)
    {
        $driverId = $request->driver_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        if (!$driverId || !$fromDate || !$toDate) {
            return response()->json([]);
        }


        $trips = Trip::select(
                    'trips.*',
                    'vehicles.vehicle_no',
                    'vehicle_type.vehicle_type_name'
                )
                ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->join('vehicle_type', 'vehicles.vehicle_type_id', '=', 'vehicle_type.id')
                ->where('trips.driver_id', $driverId)
                ->where('trips.status', 'Completed')
                ->whereBetween('trips.delivery_date', [$fromDate, $toDate])
                ->get()
                ->map(function($trip, $index) {
                    return [
                        'sl_no' => $index + 1,
                        'trip_code' => $trip->trip_code,
                        'delivery_date' => \Carbon\Carbon::parse($trip->delivery_date)->format('d/m/Y'),
                        'from_location' => $trip->from_location,
                        'to_location' => $trip->to_location,
                        'total_quantity' => $trip->total_quantity,
                        'vehicle_type' => $trip->vehicle_type_name ?? 'N/A',
                        'vehicle_no' => $trip->vehicle_no ?? 'N/A',
                        'approx_km' => $trip->approx_km,
                        'pickup_point_flag' => $trip->pickup_point_flag,
                        'salary_type' => $trip->salary_type,
                    ];
                });

        return response()->json($trips);
    }
}
