<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\District;
use App\Models\AssistanceType;
use App\Models\MaintenanceType;
use App\Models\ExpenseType;
use App\Models\Trip;
use App\Models\TripStatusHistory;
use App\Models\TripExpense;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::all();
        return view('logistics.drivers.index', compact('drivers'));
    }
    public function create()
    {
        $districts = District::all();
        return view('logistics.drivers.create', compact('districts'));
    }
    public function store(Request $request)
    {
        //  dd($request->file('photo'));
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\d+$/|max:10|unique:drivers,phone',
            'alternative_phone' => 'nullable|string|regex:/^\d+$/|max:10',
            'address' => 'required|string',
            'district_id' => 'required|exists:districts,id',
            'pincode' => 'required|string|max:10',
            'adharcard_no' => 'required|string|max:20|unique:drivers,adharcard_no',
            'adhar_attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'liscence_no' => 'required|string|max:20|unique:drivers,liscence_no',
            'liscence_attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'liscence_exp_date' => 'required|date',
            'blood_group' => 'required|string|max:5',
            'status' => 'required|in:Active,Inactive',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // 'username' => 'required|string|max:255|unique:drivers,username',
            // 'password' => 'required|string|min:6',
            // 'dob' => 'required|date',
        ]);

        try {
            $data = $request->except(['adhar_attachment', 'liscence_attachment']);

            if ($request->hasFile('adhar_attachment')) {
                $data['adhar_attachment'] = $request->file('adhar_attachment')->store('drivers/adhar', 'public');
            }
            if ($request->hasFile('liscence_attachment')) {
                $data['liscence_attachment'] = $request->file('liscence_attachment')->store('drivers/license', 'public');
            }
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('drivers/photos', 'public'); // Store photo
            }

            $data['status'] = $request->status === 'Active' ? 1 : 2;
            // $data['username'] = $request->username;
            
            $data['password'] = Hash::make($request->phone);
            // dd($data);
            Driver::create($data);

            return redirect()->route('logistics.drivers.index')->with('success', 'Driver added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }
    public function getData(Request $request)
    {
        $query = Driver::select([
            'id',
            'name',
            'phone',
            'address',
            'adharcard_no',
            'liscence_no',
            'liscence_exp_date',
            'status'
        ]);
        if (!empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
    
        if (!empty($request->phone)) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status', function($driver) {
                return $driver->status == 1 
                    ? '<span class="text-success">Active</span>' 
                    : '<span class="text-danger">Inactive</span>';
            })
            ->addColumn('action', function($driver){
                $editUrl = route('logistics.drivers.edit', $driver->id);
                $deleteUrl = route('logistics.drivers.destroy', $driver->id);
                $viewUrl = route('logistics.drivers.show', $driver->id);
                return '
                    <a href="'.$viewUrl.'" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                    <a href="'.$editUrl.'" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                   <button type="button" class="btn btn-sm btn-danger delete-btn" data-url="'.$deleteUrl.'">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action','status']) 
            ->make(true);
    }
    public function show($id)
    {
        $driver = Driver::with('district')->findOrFail($id);
        return view('logistics.drivers.show', compact('driver'));
    }
    public function edit(Driver $driver)
    {
        $districts = District::all();

        return view('logistics.drivers.edit', compact('driver', 'districts'));
    }
     public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\d+$/|max:10|unique:drivers,phone,' . $driver->id,
            'alternative_phone' => 'nullable|string|regex:/^\d+$/|max:10',
            'address' => 'required|string',
            'district_id' => 'required|exists:districts,id',
            'pincode' => 'required|string|max:10',
            'adharcard_no' => 'required|string|max:20|unique:drivers,adharcard_no,' . $driver->id,
            'adhar_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'liscence_no' => 'required|string|max:20|unique:drivers,liscence_no,' . $driver->id,
            'liscence_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'liscence_exp_date' => 'required|date',
            'blood_group' => 'required|string|max:5',
            'status' => 'required|in:Active,Inactive',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // 'password' => 'nullable|string|min:6', // optional if you want to allow password change
        ]);

        $data = $request->except(['adhar_attachment', 'liscence_attachment', 'photo', 'password']);

        if ($request->hasFile('adhar_attachment')) {
            if ($driver->adhar_attachment) {
                Storage::disk('public')->delete($driver->adhar_attachment);
            }
            $data['adhar_attachment'] = $request->file('adhar_attachment')->store('drivers/adhar', 'public');
        }

        if ($request->hasFile('liscence_attachment')) {
            if ($driver->liscence_attachment) {
                Storage::disk('public')->delete($driver->liscence_attachment);
            }
            $data['liscence_attachment'] = $request->file('liscence_attachment')->store('drivers/license', 'public');
        }

        if ($request->hasFile('photo')) {
            if ($driver->photo) {
                Storage::disk('public')->delete($driver->photo);
            }
            $data['photo'] = $request->file('photo')->store('drivers/photos', 'public');
        }

        $data['status'] = $request->status === 'Active' ? 1 : 2;

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $driver->update($data);

        return redirect()->route('logistics.drivers.index')->with('success', 'Driver updated successfully.');
    }
    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('logistics.drivers.index')->with('success', 'Driver deleted successfully.');
    }
    public function login(Request $request)
    {
        
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
                'type' => 'required|string|in:Driver',
            ]);

	    $driver = Driver::where('phone', $validated['username'])
		    ->where('status', '1')
            ->first();

            if (!$driver || !Hash::check($validated['password'], $driver->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid credentials',
                ], 400);
            }

            $token = $driver->createToken('Driver API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Login successful',
                'data' => [
                    'driver' => [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'phone' => $driver->phone,
                        'address' => $driver->address,
                        'photo' => $driver->photo ? $driver->photo : null,
                        'password_reset_flag'=>$driver->password_reset_flag == 0 ? false : true,
                        'adharcard_no' => $driver->adharcard_no,
                        'liscence_no' => $driver->liscence_no,
                    ],
                    'token' => $token,
                    'status' => 'active',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getDriverProfile(Request $request)
    {
        try {
            $driver = $request->user();
    
            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Driver not found',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Driver profile retrieved successfully',
                'data' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'dob' => $driver->dob,
                    'blood_group' => $driver->blood_group,
                    'address' => $driver->address,
                    'adhar_no' => $driver->adharcard_no,
                    'adhar_attachment' => $driver->adhar_attachment,
                    'liscence_no' => $driver->liscence_no,
                    'liscence_attachment' => $driver->liscence_attachment,
                    'photo' => $driver->photo,
                    'status' => $driver->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'type' => 'required|string' 
        ]);

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $fcmToken = trim($request->fcm_token);

            if ($request->type === "driver") {
                Driver::where("id", $user->id)->update(["fcm_token" => $fcmToken]);
            } elseif ($request->type === "employee") {
                // Employee::where("id", $user->id)->update(["fcm_token" => $fcmToken]);
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid type.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'FCM token updated successfully.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens->each(function ($token) {
                $token->delete(); 
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function resetPassword(Request $request)
    { 
        $validated=  $request->validate([
            'current_password' => 'required',
            'new_password' => 'required', 
        ]);
        try{
            $user = Auth::user();

            if(!Hash::check($request->current_password,$user->password)){

                return response()->json([
                'status' => false,
                'statusCode'=>400,
                'message' => 'Current password is incorrect.',
                ], 400);
            }


            $user->password =Hash::make($request->new_password);
            $user->password_reset_flag = '1';
            $user->save();

            return response()->json([
                'status' => true,
                'statusCode' =>200,
                'message' => 'Password reset successfully.',
            ], 200);
        }catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 500,
                    'message' => $e->getMessage()
                ], 500);
        }
    }
    public function getAssistanceTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = AssistanceType::select('id as assistance_type_id', 'name as assistance_type_name');

                $data = $query->orderBy('name', 'asc')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Assistance types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getMaintenanceTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = MaintenanceType::select('id as maintenance_type_id', 'name as maintenance_type_name');

                $data = $query->orderBy('name', 'asc')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Maintenance types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getExpenseTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = ExpenseType::select('id as expense_type_id', 'name as expense_type_name');

                $data = $query->orderBy('name', 'asc')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Expense types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getDriverTodaysTrip(Request $request)
    {
        $driver = Auth::user();
        $driverId = $driver->id;
        $today = Carbon::today()->toDateString();

        $assignedTripsCount = Trip::where('driver_id', $driverId)
            ->whereIn('status', ['Scheduled', 'In Progress', 'On Hold'])
            ->count();

        $completedTripsCount = Trip::where('driver_id', $driverId)
            ->where('status', 'Completed')
            ->count();

        $trip = Trip::with([
            'vehicle',
            'orders.order.dealer.addresses',
            'pickups' => fn($q) => $q->orderBy('pickup_date', 'asc')
        ])
            ->where('driver_id', $driverId)
            ->whereDate('assign_date', $today)
            ->first();

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 200,
                'message' => 'No trip has been assigned to you for today.',
                'data' => [
                    'trip_management' => [
                        'assigned_trips' => $assignedTripsCount,
                        'completed_trips' => $completedTripsCount,
                    ],
                    'todays_trip' => null
                ]
            ], 200);
        }

        $points = [];
        foreach ($trip->orders as $order) {
            $dealer = $order->order?->dealer;
            $dealerAddress = $dealer?->addresses?->first()?->address;

            $points[] = [
                'type' => 'Delivery',
                'point_no' => "Delivery Point {$order->delivery_point_no}",
                'start_time' => $order->start_time ? Carbon::parse($order->start_time)->format('h:i A') : null,
                'end_time' => $order->end_time ? Carbon::parse($order->end_time)->format('h:i A') : null,
                'dealer_code' => $dealer->dealer_code ?? null,
                'dealer_name' => $dealer->dealer_name ?? null,
                'address' => $dealerAddress ?? null,
                'contact_person' => $order->contact_person,
                'contact_phone' => $order->contact_phone,
                'office_phone' => $order->office_phone,
                'quantity' => (float)$order->quantity ?? null,
            ];
        }

        foreach ($trip->pickups as $pickup) {
            $points[] = [
                'type' => 'Pickup',
                'point_no' => $pickup->pickup_point,
                'address' => $pickup->address,
                'start_time' => $pickup->start_time ? Carbon::parse($pickup->start_time)->format('h:i A') : null,
                'end_time' => $pickup->end_time ? Carbon::parse($pickup->end_time)->format('h:i A') : null,
                'contact_person' => $pickup->contact_person_name,
                'contact_phone' => $pickup->contact_person_phone,
                'office_phone' => $pickup->office_phone,
            ];
        }

        $currentPoint = null;
        $nextPoint = null;

        foreach ($points as $p) {
            if ($p['start_time'] && !$p['end_time']) {
                $currentPoint = $p;
                break;
            }
        }

        if (!$currentPoint) {
            foreach ($points as $p) {
                if (!$p['start_time']) {
                    $currentPoint = $p;
                    break;
                }
            }
        }

        if (!$currentPoint && !$trip->return_start_time) {
            $currentPoint = [
                'type' => 'Start Return',
                'km' => $trip->return_start_km,
                'km_image' => $trip->return_start_km_image,
                'start_time' => null,
                'note' => 'Returning',
            ];
            $nextPoint = [
                'type' => 'Reached Garage',
                'km' => $trip->garage_km,
                'km_image' => $trip->garage_km_image,
                'end_time' => $trip->reached_garage_time ? Carbon::parse($trip->reached_garage_time)->format('h:i A') : null,
                'note' => 'Trip Completed',
            ];
        }

        if (!$currentPoint && $trip->return_start_time && !$trip->reached_garage_time) {
            $currentPoint = [
                'type' => 'Reached Garage',
                'km' => $trip->garage_km,
                'km_image' => $trip->garage_km_image,
                'end_time' => $trip->reached_garage_time ? Carbon::parse($trip->reached_garage_time)->format('h:i A') : null,
                'note' => 'Trip Completed',
            ];
        }

        if (!$currentPoint && $trip->reached_garage_time) {
            $currentPoint = [
                'type' => 'Reached Garage',
                'km' => $trip->garage_km,
                'km_image' => $trip->garage_km_image,
                'end_time' => Carbon::parse($trip->reached_garage_time)->format('h:i A'),
                'note' => 'Trip Completed',
            ];
            $nextPoint = null;
        }

        $buttonStatus = null;
        switch ($trip->status) {
            case 'Scheduled':
                $buttonStatus = 'Start Trip';
                break;
            case 'In Progress':
                $buttonStatus = 'Trip Log';
                break;
            case 'On Hold':
                $buttonStatus = 'Restart Trip';
                break;
            case 'Completed':
                $buttonStatus = null;
                break;
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Trip details fetched successfully.',
            'data' => [
                'trip_management' => [
                    'assigned_trips' => $assignedTripsCount,
                    'completed_trips' => $completedTripsCount,
                ],
                'todays_trip' => [
                    'trip_id' => $trip->id,
                    'trip_code' => $trip->trip_code,
                    'trip_status' => $trip->status,
                    'from_location' => $trip->from_location,
                    'to_location' => $trip->to_location,
                    'button_status' => $buttonStatus,
                    'current_date' => $trip->assign_date 
                    ? $trip->assign_date->format('d/m/Y') 
                    : null,
                    'current_point' => $currentPoint,
                    'next_point' => $nextPoint,
                    'total_quantity' => (float)$trip->total_quantity,
                    'approx_km' => (float)$trip->approx_km,
                    'delivery_points_count' => $trip->orders->count(),
                    'points' => $points,
                    'vehicle' => [
                        'vehicle_id' => $trip->vehicle->id,
                        'vehicle_no' => $trip->vehicle->vehicle_no,
                        'type' => $trip->vehicle->vehicle_type_text,
                        'model' => $trip->vehicle->model,
                        'rc_file' => $trip->vehicle->rc_file,
                        'insurance_file' => $trip->vehicle->insurance_file,
                        'puc_file' => $trip->vehicle->puc_file,
                        'fitness_file' => $trip->vehicle->fitness_file,
                        'permit_file' => $trip->vehicle->permit_file,
                    ]
                ]
            ]
        ], 200);
    }
    public function showTripLog(Request $request, $tripId)
    {
        $driver = Auth::user();
        $driverId = $driver->id;

        $trip = Trip::with([
            'orders' => fn($q) => $q->orderBy('delivery_point_no', 'asc')->with(['order.dealer', 'order.dealerAddress']),
            'pickups' => fn($q) => $q->orderBy('pickup_point', 'asc')
        ])
        ->where('driver_id', $driverId)
        ->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }

        $logs = [];

        $addLog = function (&$logs, $type, $time, $km, $kmImage, $extra = []) {
            if (!is_null($km)) {
                $logs[] = array_merge([
                    'type'     => $type,
                    'time'     => $time,
                    'km'       => (float) $km,
                    'km_image' => $kmImage,
                ], $extra);
            }
        };

        $firstOrder = $trip->orders->first();

        $addLog(
            $logs,
            'Start Trip',
            $firstOrder && $firstOrder->start_time ? Carbon::parse($firstOrder->start_time)->format('h:i A') : null,
            $trip->start_km,
            $trip->start_km_image
        );

        foreach ($trip->orders as $index => $order) {
            $dealer        = $order->order?->dealer;
            $dealerAddress = $dealer?->primaryAddress ?? $dealer?->addresses?->first();

            if ($order->start_km && !($index === 0 && $order->start_km == $trip->start_km)) {
                $addLog(
                    $logs,
                    'Towards Next Delivery Point',
                    $order->start_time ? Carbon::parse($order->start_time)->format('h:i A') : null,
                    $order->start_km,
                    $order->start_km_image,
                    [
                        'delivery_point_no' => $order->delivery_point_no,
                        'dealer_name'       => $dealer->dealer_name ?? null,
                        'address'           => $dealerAddress?->address,
                        'contact_person'    => $order->contact_person,
                        'contact_phone'     => $order->contact_phone,
                        'office_phone'      => $order->office_phone,
                    ]
                );
            }

            if ($order->current_km) {
                $addLog(
                    $logs,
                    'Reached Delivery Point',
                    $order->end_time ? Carbon::parse($order->end_time)->format('h:i A') : null,
                    $order->current_km,
                    $order->km_image,
                    [
                        'delivery_point_no' => $order->delivery_point_no,
                        'dealer_name'       => $dealer->dealer_name ?? null,
                        'address'           => $dealerAddress?->address,
                        'contact_person'    => $order->contact_person,
                        'contact_phone'     => $order->contact_phone,
                        'office_phone'      => $order->office_phone,
                    ]
                );
            }
        }

        foreach ($trip->pickups as $pickup) {
            if ($pickup->start_km) {
                $addLog(
                    $logs,
                    'Towards Next Pickup Point',
                    $pickup->start_time ? Carbon::parse($pickup->start_time)->format('h:i A') : null,
                    $pickup->start_km,
                    $pickup->start_km_image,
                    [
                        'pickup_point_no'  => $pickup->pickup_point,
                        'pickup_point'     => $pickup->pickup_point_name ?? null,
                        'address'          => $pickup->address,
                        'contact_person'   => $pickup->contact_person_name ?? null,
                        'contact_phone'    => $pickup->contact_person_phone ?? null,
                        'office_phone'     => $pickup->office_phone ?? null,
                    ]
                );
            }

            if ($pickup->end_km) {
                $addLog(
                    $logs,
                    'Reached Pickup Point',
                    $pickup->end_time ? Carbon::parse($pickup->end_time)->format('h:i A') : null,
                    $pickup->end_km,
                    $pickup->end_km_image,
                    [
                        'pickup_point_no'  => $pickup->pickup_point,
                        'pickup_point'     => $pickup->pickup_point_name ?? null,
                        'address'          => $pickup->address,
                        'contact_person'   => $pickup->contact_person_name ?? null,
                        'contact_phone'    => $pickup->contact_person_phone ?? null,
                        'office_phone'     => $pickup->office_phone ?? null,
                    ]
                );
            }
        }

        $addLog(
            $logs,
            'Start Return',
            $trip->return_start_time ? Carbon::parse($trip->return_start_time)->format('h:i A') : null,
            $trip->return_start_km,
            $trip->return_start_km_image,
            ['note' => 'Returning']
        );

        $addLog(
            $logs,
            'Reached Garage',
            $trip->reached_garage_time ? Carbon::parse($trip->reached_garage_time)->format('h:i A') : null,
            $trip->garage_km,
            $trip->garage_km_image,
            ['note' => 'Trip Completed']
        );

        $currentPoint = null;

        foreach ($trip->orders as $order) {
            if (!$order->current_km) {
                $dealer        = $order->order?->dealer;
                $dealerAddress = $dealer?->primaryAddress ?? $dealer?->addresses?->first();

                $currentPoint = [
                    'type'               => 'Next Delivery Point',
                    'delivery_point_no'  => $order->delivery_point_no,
                    'dealer_name'        => $dealer->dealer_name ?? null,
                    'address'            => $dealerAddress?->address,
                    'contact_person'     => $order->contact_person,
                    'contact_phone'      => $order->contact_phone,
                    'office_phone'       => $order->office_phone,
                    'km'                 => $order->start_km,
                    'km_image'           => $order->start_km_image,
                ];
                break;
            }
        }

        if (!$currentPoint) {
            foreach ($trip->pickups as $pickup) {
                if (!$pickup->end_km) {
                    $currentPoint = [
                        'type'               => 'Next Pickup Point',
                        'pickup_point_no'    => $pickup->pickup_point,
                        'pickup_point'       => $pickup->pickup_point_name ?? null,
                        'address'            => $pickup->address,
                        'contact_person'     => $pickup->contact_person_name ?? null,
                        'contact_phone'      => $pickup->contact_person_phone ?? null,
                        'office_phone'       => $pickup->office_phone ?? null,
                        'km'                 => $pickup->start_km,
                        'km_image'           => $pickup->start_km_image,
                    ];
                    break;
                }
            }
        }

        if (!$currentPoint && !$trip->return_start_time) {
            $currentPoint = [
                'type'     => 'Start Return',
                'time'     => now()->format('h:i A'),
                'km'       => $trip->return_start_km,
                'km_image' => $trip->return_start_km_image,
                'note'     => 'Returning',
            ];
        }

        if (!$currentPoint && $trip->return_start_time) {
            $currentPoint = [
                'type'     => 'Reached Garage',
                'time'     => $trip->reached_garage_time ? Carbon::parse($trip->reached_garage_time)->format('h:i A') : now()->format('h:i A'),
                'km'       => $trip->garage_km,
                'km_image' => $trip->garage_km_image,
                'note'     => 'Trip Completed',
            ];
        }

        if (!$currentPoint) {
            $currentPoint = end($logs);
        }
         $nextAction = null;
        
            if ($trip->status === 'Scheduled') {
                $nextAction = 'start_trip';
            } elseif ($trip->status === 'In Progress') {
                if ($currentPoint) {
                    switch ($currentPoint['type']) {
                        case 'Next Delivery Point':
                            $nextAction = empty($currentPoint['km']) ? 'delivery_start' : 'delivery_end';
                            break;
        
                        case 'Next Pickup Point':
                            $nextAction = empty($currentPoint['km']) ? 'pickup_start' : 'pickup_end';
                            break;
        
                        case 'Start Return':
                            if (empty($trip->return_start_time)) {
                                $nextAction = 'return_start';
                            }
                            break;
        
                        case 'Reached Garage':
                            if (empty($trip->reached_garage_time)) {
                                $nextAction = 'garage_reach';
                            }
                            break;
                    }
                }
            } elseif ($trip->status === 'On Hold') {
                $nextAction = 'start_trip'; // or restart_trip
            }
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Trip log fetched successfully.',
            'data' => [
                'trip_id'       => $trip->id,
                'trip_status'   => $trip->status,
                'current_point' => $currentPoint,
                'logs'          => $logs,
                'action'   => $nextAction
            ]
        ], 200);
    }

    public function updateTrip(Request $request, $tripId)
    {
        $driver = Auth::user();
        $driverId = $driver->id;

        $trip = Trip::with(['orders', 'pickups'])
            ->where('driver_id', $driverId)
            ->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }

        $action = trim($request->input('action')); 

        switch ($action) {
            case 'start_trip':
                $trip->update([
                    'status' => 'In Progress',
                    'start_km' => $request->input('km'),
                    'start_km_image' => $request->input('km_image'),
                ]);

                $firstOrder = $trip->orders()->orderBy('delivery_point_no', 'asc')->first();
                if ($firstOrder) {
                    $firstOrder->update([
                        'start_time'     => now()->format('H:i:s'),
                        'start_km'       => $request->input('km'),
                        'start_km_image' => $request->input('km_image'),
                    ]);
                }
                break;

            case 'delivery_start':
                $deliveryPointNo = $request->input('delivery_point_no');
                $order = $trip->orders()->where('delivery_point_no', $deliveryPointNo)->first();
                if ($order) {
                    $order->update([
                        'start_time'     => now()->format('H:i:s'),
                        'start_km'       => $request->input('km'),
                        'start_km_image' => $request->input('km_image'),
                    ]);
                }
                $trip->update(['status' => 'In Progress']);
                break;

            case 'delivery_end':
                $deliveryPointNo = $request->input('delivery_point_no');
                $order = $trip->orders()->where('delivery_point_no', $deliveryPointNo)->first();
                if ($order) {
                    $order->update([
                        'end_time'    => now()->format('H:i:s'),
                        'current_km'  => $request->input('km'),
                        'km_image'    => $request->input('km_image'),
                    ]);
                }
                $trip->update(['status' => 'In Progress']);
                break;

            case 'pickup_start':
                $pickupId = $request->input('pickup_id'); // unique identifier for pickup
                $pickup = $trip->pickups()->where('id', $pickupId)->first();
                if ($pickup) {
                    $pickup->update([
                        'start_time'     => now()->format('H:i:s'),
                        'start_km'       => $request->input('km'),
                        'start_km_image' => $request->input('km_image'),
                    ]);
                }
                $trip->update(['status' => 'In Progress']);
                break;

            case 'pickup_end':
                $pickupId = $request->input('pickup_id'); // unique identifier for pickup
                $pickup = $trip->pickups()->where('id', $pickupId)->first();
                if ($pickup) {
                    $pickup->update([
                        'end_time'      => now()->format('H:i:s'),
                        'end_km'        => $request->input('km'),
                        'end_km_image'  => $request->input('km_image'),
                    ]);
                }
                $trip->update(['status' => 'In Progress']);
                break;

            case 'return_start':
                $trip->update([
                    'return_start_km' => $request->input('km'),
                    'return_start_km_image' => $request->input('km_image'),
                    'return_start_time' => now()->format('H:i:s'),
                    'status' => 'In Progress',
                ]);
                break;

            case 'garage_reach':
                $trip->update([
                    'garage_km' => $request->input('km'),
                    'garage_km_image' => $request->input('km_image'),
                    'reached_garage_time' => now()->format('H:i:s'),
                    'status' => 'Completed',
                    'notification_status' => 'pending'
                ]);
                break;

            default:
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid action provided.',
                    'data' => []
                ], 400);
        }

        TripStatusHistory::create([
            'trip_id' => $trip->id,
            'status' => $trip->status,
            'changed_by' => $driverId,
            'changed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Trip updated successfully.',
            'data' => $trip->fresh(['orders', 'pickups'])
        ], 200);
    }

    // public function updateTrip(Request $request, $tripId)
    // {
    //     $driver = Auth::user();
    //     $driverId = $driver->id;

    //     $trip = Trip::with('orders')
    //         ->where('driver_id', $driverId)
    //         ->find($tripId);

    //     if (!$trip) {
    //         return response()->json([
    //             'status' => false,
    //             'statusCode' => 404,
    //             'message' => 'Trip not found or not assigned to this driver.',
    //             'data' => []
    //         ], 404);
    //     }

    //     $action = trim($request->input('action')); 

    //     switch ($action) {
    //         case 'start_trip':
    //             $trip->update([
    //                 'status' => 'In Progress',
    //                 'start_km' => $request->input('km'),
    //                 'start_km_image' => $request->input('km_image'),
    //             ]);

    //             $firstOrder = $trip->orders()->orderBy('delivery_point_no', 'asc')->first();
    //             if ($firstOrder) {
    //                 $firstOrder->update([
    //                     'start_time'     => now()->format('H:i:s'),
    //                     'start_km'       => $request->input('km'),
    //                     'start_km_image' => $request->input('km_image'),
    //                 ]);
    //             }
    //             break;

    //         case 'delivery_start':
    //             $deliveryPointNo = $request->input('delivery_point_no');
    //             $order = $trip->orders()->where('delivery_point_no', $deliveryPointNo)->first();
    //             if ($order) {
    //                 $order->update([
    //                     'start_time'     => now()->format('H:i:s'),
    //                     'start_km'       => $request->input('km'),
    //                     'start_km_image' => $request->input('km_image'),
    //                 ]);
    //             }
    //             $trip->update(['status' => 'In Progress']);
    //             break;

    //         case 'delivery_end':
    //             $deliveryPointNo = $request->input('delivery_point_no');
    //             $order = $trip->orders()->where('delivery_point_no', $deliveryPointNo)->first();
    //             if ($order) {
    //                 $order->update([
    //                     'end_time'    => now()->format('H:i:s'),
    //                     'current_km'  => $request->input('km'),
    //                     'km_image'    => $request->input('km_image'),
    //                 ]);
    //             }
    //             $trip->update(['status' => 'In Progress']);
    //             break;

    //         case 'return_start':
    //             $trip->update([
    //                 'return_start_km' => $request->input('km'),
    //                 'return_start_km_image' => $request->input('km_image'),
    //                 'return_start_time' => now()->format('H:i:s'),
    //                 'status' => 'In Progress',
    //             ]);
    //             break;

    //         case 'garage_reach':
    //             $trip->update([
    //                 'garage_km' => $request->input('km'),
    //                 'garage_km_image' => $request->input('km_image'),
    //                 'reached_garage_time' => now()->format('H:i:s'),
    //                 'status' => 'Completed',
    //             ]);
    //             break;

    //         default:
    //             return response()->json([
    //                 'status' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'Invalid action provided.',
    //                 'data' => []
    //             ], 400);
    //     }

    //     TripStatusHistory::create([
    //         'trip_id' => $trip->id,
    //         'status' => $trip->status,
    //         'changed_by' => $driverId,
    //         'changed_at' => now(),
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'statusCode' => 200,
    //         'message' => 'Trip updated successfully.',
    //         'data' => $trip->fresh('orders')
    //     ], 200);
    // }
    public function getTripExpenses($tripId)
    {
        $driver = Auth::user();
        $trip = Trip::where('driver_id', $driver->id)->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }

        $expenses = TripExpense::where('trip_id', $tripId)
            ->where('driver_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($exp) {
                // Get the expense type name
                
                $expenseTypeName = $exp->expenseType ? $exp->expenseType->name : 'Unknown';

                $expense = [
                    'expense_id'   => $exp->id,
                    'expense_type' => $expenseTypeName,
                    'amount'       => (float) $exp->amount,
                    'date'         => Carbon::parse($exp->created_at)->format('d/m/Y'),
                    'time'         => Carbon::parse($exp->created_at)->format('h:i A'),
                ];

                // Add remarks only if expense_type is "Others"
                if ($expenseTypeName === 'Others') {
                    $expense['remarks'] = $exp->remarks;
                }

                return $expense;
            });

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Expense list fetched successfully.',
            'data' => $expenses
        ], 200);
    }
    public function addTripExpense(Request $request, $tripId)
    {
        $driver = Auth::user();
        $trip = Trip::where('driver_id', $driver->id)->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }

        $request->validate([
            'expense_type' => 'required|exists:expense_types,id',
            'amount'       => 'required|numeric|min:0',
            'bill_image'   => 'nullable|string', // base64 or URL
            'remarks'      => 'nullable|string|required_if:expense_type,Others',
            'current_km'   => 'nullable|numeric|required_if:expense_type,Fuel',
            'fuel_litre'   => 'nullable|numeric|required_if:expense_type,Fuel',
        ]);
        $expenseType = ExpenseType::find($request->expense_type);

        // Optionally, enforce required fields based on expense type name
        if ($expenseType->name === 'Fuel') {
            if (!$request->current_km || !$request->fuel_litre) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Current KM and Fuel Litre are required for Fuel expense.',
                ], 422);
            }
        } elseif ($expenseType->name === 'Others') {
            if (!$request->remarks) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Remarks are required for Others expense.',
                ], 422);
            }
        }

         $expense = TripExpense::create([
            'trip_id'        => $trip->id,
            'driver_id'      => $driver->id,
            'expense_type'   => $expenseType->id,
            'remarks'        => $expenseType->name === 'Others' ? $request->remarks : null,
            'amount'         => $request->amount,
            'current_km'     => $expenseType->name === 'Fuel' ? $request->current_km : null,
            'fuel_litre'     => $expenseType->name === 'Fuel' ? $request->fuel_litre : null,
            'bill_image'     => $request->bill_image,
        ]);

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Expense added successfully.',
            'data' => $expense
        ], 200);
    }
    public function getTripExpenseDetails($expenseId)
    {
        $driver = Auth::user();
        $expense = TripExpense::where('id', $expenseId)
            ->where('driver_id', $driver->id)
            ->with('expenseType')
            ->first();

        if (!$expense) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Expense not found.',
                'data' => new \stdClass() 
            ], 404);
        }
        $expenseTypeName = $expense->expenseType ? $expense->expenseType->name : 'Unknown';
        $details = [
            'expense_id'   => $expense->id,
            'expense_type' => $expenseTypeName,
            'remarks'      => $expense->remarks,
            'amount'       => (float) $expense->amount,
            'bill_image'   => $expense->bill_image,
            'current_km'   => (float) $expense->current_km,
            'fuel_litre'   => (float)$expense->fuel_litre,
            'date'         => Carbon::parse($expense->created_at)->format('d/m/Y'),
            'time'         => Carbon::parse($expense->created_at)->format('h:i A'),
        ];

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Expense details fetched successfully.',
            'data' => $details
        ], 200);
    }
    public function assignedTrips(Request $request)
    {
        $driver = Auth::user();
        $driverId = $driver->id;

        $today = Carbon::today()->toDateString();

        $trips = Trip::with('vehicle')
            ->where('driver_id', $driverId)
            ->whereDate('assign_date', '>=', $today)
            ->whereIn('status', ['Scheduled', 'In Progress', 'On Hold'])
            ->orderBy('assign_date', 'asc')
            ->get();

        if ($trips->isEmpty()) {
            return response()->json([
                'status' => false,
                'statusCode' => 200,
                'message' => 'No assigned trips found.',
                'data' => []
            ], 200);
        }

        $data = $trips->map(function ($trip) {
            return [
                'trip_id'      => $trip->id,
                'trip_code'    => $trip->trip_code,
                'trip_status'  => $trip->status,
                'assign_date'  => $trip->assign_date ? $trip->assign_date->format('d/m/Y') : null,
                'delivery_date'=> $trip->delivery_date ? $trip->delivery_date->format('d/m/Y') : null,
                'from_location'=> $trip->from_location,
                'to_location'  => $trip->to_location,
                'vehicle' => [
                    'vehicle_id'   => $trip->vehicle->id,
                    'vehicle_no'   => $trip->vehicle->vehicle_no,
                    'model'        => $trip->vehicle->model,
                    'type'         => $trip->vehicle->vehicle_type_text,
                ],
            ];
        });

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Assigned trips fetched successfully.',
            'data' => $data
        ], 200);
    }

    public function completedTrips(Request $request)
    {
        $driver = Auth::user();
        $driverId = $driver->id;

        $trips = Trip::with('vehicle')
            ->where('driver_id', $driverId)
            ->where('status', 'Completed')
            ->orderBy('delivery_date', 'desc')
            ->get();

        if ($trips->isEmpty()) {
            return response()->json([
                'status' => false,
                'statusCode' => 200,
                'message' => 'No completed trips found.',
                'data' => []
            ], 200);
        }

        $data = $trips->map(function ($trip) {
            return [
                'trip_id'      => $trip->id,
                'trip_code'    => $trip->trip_code,
                'trip_status'  => $trip->status,
                'assign_date'  => $trip->assign_date ? $trip->assign_date->format('d/m/Y') : null,
                'delivery_date'=> $trip->delivery_date ? $trip->delivery_date->format('d/m/Y') : null,
                'from_location'=> $trip->from_location,
                'to_location'  => $trip->to_location,
                'vehicle' => [
                    'vehicle_id'   => $trip->vehicle->id,
                    'vehicle_no'   => $trip->vehicle->vehicle_no,
                    'model'        => $trip->vehicle->model,
                    'type'         => $trip->vehicle->vehicle_type_text,
                ],
            ];
        });

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Completed trips fetched successfully.',
            'data' => $data
        ], 200);
    }
    

    public function completedTripDetails(Request $request, $tripId)
    {
        return $this->getTripDetails($tripId, 'Completed');
    }

    public function assignedTripDetails(Request $request, $tripId)
    {
        return $this->getTripDetails($tripId, ['Scheduled', 'In Progress', 'On Hold']);
    }
    // private function getTripDetails($tripId, $statusFilter)
    // {
    //     $driver = Auth::user();

    //     $query = Trip::with(['vehicle', 'orders', 'driver'])
    //         ->where('driver_id', $driver->id)
    //         ->where('id', $tripId);

    //     if (is_array($statusFilter)) {
    //         $query->whereIn('status', $statusFilter);
    //     } else {
    //         $query->where('status', $statusFilter);
    //     }

    //     $trip = $query->first();

    //     if (!$trip) {
    //         return response()->json([
    //             'status' => false,
    //             'statusCode' => 404,
    //             'message' => 'Trip not found or not assigned to this driver.',
    //             'data' => []
    //         ], 404);
    //     }

    //     // --- Trip Information ---
    //     $deliveryPointsCount = $trip->orders()->count();
    //     $totalExpense = TripExpense::where('trip_id', $trip->id)->sum('amount');
    //     $totalKm = $trip->garage_km && $trip->start_km ? ($trip->garage_km - $trip->start_km) : null;

    //     $tripInfo = [
    //         'assign_date' => $trip->assign_date?->format('d/m/Y'),
    //         'from_location' => $trip->from_location,
    //         'to_location' => $trip->to_location,
    //         'total_quantity' => (float)$trip->total_quantity,
    //         'vehicle_number' => $trip->vehicle?->vehicle_no,
    //         'approx_km' => (float)$trip->approx_km,
    //         'delivery_points' => $deliveryPointsCount,
    //         'total_expense_amount' => (float)$totalExpense,
    //         'total_km' => $totalKm,
    //     ];

    //     // --- Vehicle Information (only required fields) ---
    //     $vehicleInfo = $trip->vehicle ? [
    //         'vehicle_id'            => $trip->vehicle->id,
    //         'vehicle_number'        => $trip->vehicle->vehicle_no,
    //         'type'                  => $trip->vehicle->vehicle_type_text,
    //         'model'                 => $trip->vehicle->model,
    //         'reg_certificate'       => $trip->vehicle->rc_file,
    //         'insurance_certificate' => $trip->vehicle->insurance_file,
    //         'puc_certificate'       => $trip->vehicle->puc_file,
    //         'fitness_certificate'   => $trip->vehicle->fitness_file,
    //         'permit_certificate'    => $trip->vehicle->permit_file,
    //     ] : null;

    //     // --- Delivery Point Details ---
    //     $deliveryPoints = $trip->orders->map(function ($o) {
    //         return [
    //             'delivery_point_no' => $o->delivery_point_no,
    //             'delivery_address'  => $o->delivery_address,
    //             'delivery_date'     => $o->delivery_date?->format('d/m/Y'),
    //             // 'quantity'          => (float)$o->quantity,
    //             // 'start_time'        => $o->start_time,
    //             // 'end_time'          => $o->end_time,
    //             // 'start_km'          => $o->start_km,
    //             // 'current_km'        => $o->current_km,
    //             // 'km_image'          => $o->km_image,
    //         ];
    //     });

    //     // --- Expense Details ---
    //     $expenses = TripExpense::where('trip_id', $trip->id)
    //         ->with('expenseType') // eager load relation
    //         ->get()
    //         ->map(function ($e) {
    //             $expenseTypeName = $e->expenseType ? $e->expenseType->name : 'Unknown';

    //             $expense = [
    //                 'expense_id'   => $e->id,
    //                 'expense_type' => $expenseTypeName,
    //                 'amount'       => (float)$e->amount,
    //                 'bill_image'   => $e->bill_image,
    //             ];

    //             // Add conditionally
    //             if ($expenseTypeName === 'Others') {
    //                 $expense['remarks'] = $e->remarks;
    //             }

    //             if ($expenseTypeName === 'Fuel') {
    //                 $expense['current_km'] = (float) $e->current_km;
    //                 $expense['fuel_litre'] = (float) $e->fuel_litre;
    //             }

    //             return $expense;
    //         });

    //     // --- Trip Log Details ---
    //     // $tripLog = app()->call('App\Http\Controllers\Logistics\DriverController@showTripLog', ['request' => request(), 'tripId' => $trip->id]);
    //     // $tripLogData = $tripLog->getData(true)['data'] ?? [];
    //     $tripLog = app()->call('App\Http\Controllers\Logistics\DriverController@showTripLog', ['request' => request(), 'tripId' => $trip->id]);
    //     $tripLogData = $tripLog->getData(true)['data']['trip_log_details'] ?? [];

    //     // --- Support Details ---
    //     $support = app()->call('App\Http\Controllers\Logistics\AssistanceController@getAssistanceList', ['tripId' => $trip->id]);
    //     $supportData = $support->getData(true)['data'] ?? [];

    //     return response()->json([
    //         'status' => true,
    //         'statusCode' => 200,
    //         'message' => 'Trip details fetched successfully.',
    //         'data' => [
    //             'trip_status' => $trip->status,
    //             'trip_information' => $tripInfo,
    //             'vehicle_information' => $vehicleInfo,
    //             'delivery_point_details' => $deliveryPoints,
    //             'expense_details' => $expenses,
    //             'trip_log_details' => $tripLogData,
    //             'support_details' => $supportData,
    //         ]
    //     ]);
    // }
    private function getTripDetails($tripId, $statusFilter)
    {
        $driver = Auth::user();
    
        $query = Trip::with(['vehicle', 'orders.order.dealer.addresses', 'pickups', 'driver'])
            ->where('driver_id', $driver->id)
            ->where('id', $tripId);
    
        if (is_array($statusFilter)) {
            $query->whereIn('status', $statusFilter);
        } else {
            $query->where('status', $statusFilter);
        }
    
        $trip = $query->first();
    
        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }
    
    
        $deliveryPointsCount = $trip->orders()->count();
        $totalExpense = TripExpense::where('trip_id', $trip->id)->sum('amount');
        $totalKm = $trip->garage_km && $trip->start_km ? ($trip->garage_km - $trip->start_km) : null;
    
        $tripInfo = [
            'assign_date' => $trip->assign_date?->format('d/m/Y'),
            'from_location' => $trip->from_location,
            'to_location' => $trip->to_location,
            'total_quantity' => (float)$trip->total_quantity,
            'vehicle_number' => $trip->vehicle?->vehicle_no,
            'approx_km' => (float)$trip->approx_km,
            'delivery_points' => $deliveryPointsCount,
            'total_expense_amount' => (float)$totalExpense,
            'total_km' => $totalKm,
        ];
    
    
        $vehicleInfo = $trip->vehicle ? [
            'vehicle_id'            => $trip->vehicle->id,
            'vehicle_number'        => $trip->vehicle->vehicle_no,
            'type'                  => $trip->vehicle->vehicle_type_text,
            'model'                 => $trip->vehicle->model,
            'reg_certificate'       => $trip->vehicle->rc_file,
            'insurance_certificate' => $trip->vehicle->insurance_file,
            'puc_certificate'       => $trip->vehicle->puc_file,
            'fitness_certificate'   => $trip->vehicle->fitness_file,
            'permit_certificate'    => $trip->vehicle->permit_file,
        ] : null;
    
    
        $deliveryPoints = $trip->orders->map(function ($o) {
            return [
                'delivery_point_no' => $o->delivery_point_no,
                'delivery_address'  => $o->delivery_address,
                'delivery_date'     => $o->delivery_date?->format('d/m/Y'),
            ];
        });
    
     
        $points = [];
    
        foreach ($trip->orders as $order) {
            $dealer = $order->order?->dealer;
            $dealerAddress = $dealer?->addresses?->first()?->address;
    
            $points[] = [
                'type'           => 'Delivery',
                'point_no'       => "Delivery Point {$order->delivery_point_no}",
                'start_time'     => $order->start_time,
                'end_time'       => $order->end_time,
                'dealer_code'    => $dealer->dealer_code ?? null,
                'dealer_name'    => $dealer->dealer_name ?? null,
                'address' => $dealerAddress ?? null,
                'contact_person' => $order->contact_person,
                'contact_phone' => $order->contact_phone,
                'office_phone' => $order->office_phone,
                'quantity'       => (float) $order->quantity ?? null,
            ];
        }
    
        foreach ($trip->pickups as $pickup) {
            $points[] = [
                'type'       => 'Pickup',
                'point_no'   => $pickup->pickup_point,
                'address'    => $pickup->address,
                'start_time' => $pickup->start_time,
                'end_time'   => $pickup->end_time,
                'contact_person' => $pickup->contact_person_name,
                'contact_phone' => $pickup->contact_person_phone,
                'office_phone' => $pickup->office_phone,
            ];
        }
    
        // --- Expense Details ---
        $expenses = TripExpense::where('trip_id', $trip->id)
            ->with('expenseType')
            ->get()
            ->map(function ($e) {
                $expenseTypeName = $e->expenseType ? $e->expenseType->name : 'Unknown';
    
                $expense = [
                    'expense_id'   => $e->id,
                    'expense_type' => $expenseTypeName,
                    'amount'       => (float)$e->amount,
                    'bill_image'   => $e->bill_image,
                ];
    
                if ($expenseTypeName === 'Others') {
                    $expense['remarks'] = $e->remarks;
                }
    
                if ($expenseTypeName === 'Fuel') {
                    $expense['current_km'] = (float) $e->current_km;
                    $expense['fuel_litre'] = (float) $e->fuel_litre;
                }
    
                return $expense;
            });
    
        // --- Trip Log Details ---
        $tripLog = app()->call('App\Http\Controllers\Logistics\DriverController@showTripLog', [
            'request' => request(),
            'tripId' => $trip->id
        ]);
        $tripLogData = $tripLog->getData(true)['data']['trip_log_details'] ?? [];
    
        // --- Support Details ---
        $support = app()->call('App\Http\Controllers\Logistics\AssistanceController@getAssistanceList', [
            'tripId' => $trip->id
        ]);
        $supportData = $support->getData(true)['data'] ?? [];
    
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Trip details fetched successfully.',
            'data' => [
                'trip_status'          => $trip->status,
                'trip_information'     => $tripInfo,
                'vehicle_information'  => $vehicleInfo,
                // 'delivery_point_details' => $deliveryPoints,
                'points'               => $points, // ✅ Added here
                'expense_details'      => $expenses,
                'trip_log_details'     => $tripLogData,
                'support_details'      => $supportData,
            ]
        ]);
    }

    public function viewTripDetails($tripId)
    {
        $driver = Auth::user();

        $trip = Trip::with(['vehicle', 'orders', 'driver'])
            ->where('driver_id', $driver->id)
            ->where('id', $tripId)
            ->first();

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to this driver.',
                'data' => []
            ], 404);
        }

        // --- Trip Information ---
        $deliveryPointsCount = $trip->orders()->count();
        $totalExpense = TripExpense::where('trip_id', $trip->id)->sum('amount');
        $totalKm = $trip->garage_km && $trip->start_km ? ($trip->garage_km - $trip->start_km) : null;

        $tripInfo = [
            'assign_date' => $trip->assign_date?->format('d/m/Y'),
            'from_location' => $trip->from_location,
            'to_location' => $trip->to_location,
            'total_quantity' => (float)$trip->total_quantity,
            'vehicle_number' => $trip->vehicle?->vehicle_no,
            'approx_km' => (float)$trip->approx_km,
            'delivery_points' => $deliveryPointsCount,
            'total_expense_amount' => (float)$totalExpense,
            'total_km' => $totalKm,
        ];

        // --- Vehicle Information (only required fields) ---
        $vehicleInfo = $trip->vehicle ? [
            'vehicle_id'            => $trip->vehicle->id,
            'vehicle_number'        => $trip->vehicle->vehicle_no,
            'type'                  => $trip->vehicle->vehicle_type_text,
            'model'                 => $trip->vehicle->model,
            'reg_certificate'       => $trip->vehicle->rc_file,
            'insurance_certificate' => $trip->vehicle->insurance_file,
            'puc_certificate'       => $trip->vehicle->puc_file,
            'fitness_certificate'   => $trip->vehicle->fitness_file,
            'permit_certificate'    => $trip->vehicle->permit_file,
        ] : null;

        // --- Delivery Point Details ---
        $deliveryPoints = $trip->orders->map(function ($o) {
            return [
                'delivery_point_no' => $o->delivery_point_no,
                'delivery_address'  => $o->delivery_address,
                'delivery_date'     => $o->delivery_date?->format('d/m/Y'),
            ];
        });

        // --- Expense Details ---
        $expenses = TripExpense::where('trip_id', $trip->id)
            ->with('expenseType') // eager load relation
            ->get()
            ->map(function ($e) {
                $expenseTypeName = $e->expenseType ? $e->expenseType->name : 'Unknown';

                $expense = [
                    'expense_id'   => $e->id,
                    'expense_type' => $expenseTypeName,
                    'amount'       => (float)$e->amount,
                    'bill_image'   => $e->bill_image,
                ];

                // Conditional fields
                if ($expenseTypeName === 'Others') {
                    $expense['remarks'] = $e->remarks;
                }

                if ($expenseTypeName === 'Fuel') {
                    $expense['current_km'] = (float) $e->current_km;
                    $expense['fuel_litre'] = (float) $e->fuel_litre;
                }

                return $expense;
            });

        // --- Trip Log Details ---
        $tripLog = app()->call('App\Http\Controllers\Logistics\DriverController@showTripLog', [
            'request' => request(),
            'tripId' => $trip->id
        ]);
        $tripLogData = $tripLog->getData(true)['data']['trip_log_details'] ?? [];

        // --- Support Details ---
        $support = app()->call('App\Http\Controllers\Logistics\AssistanceController@getAssistanceList', [
            'tripId' => $trip->id
        ]);
        $supportData = $support->getData(true)['data'] ?? [];

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Trip details fetched successfully.',
            'data' => [
                'trip_status' => $trip->status,
                'trip_information' => $tripInfo,
                'vehicle_information' => $vehicleInfo,
                'delivery_point_details' => $deliveryPoints,
                'expense_details' => $expenses,
                'trip_log_details' => $tripLogData,
                'support_details' => $supportData,
            ]
        ]);
    }
    public function restartTrip(Request $request, $tripId)
    {
        $trip = Trip::find($tripId);

        if (!$trip) {
            return response()->json([
                'status'     => false,
                'statusCode' => 404,
                'message'    => 'Trip not found.',
                'data'       => []
            ], 404);
        }

        if ($trip->status !== 'On Hold') {
            return response()->json([
                'status'     => false,
                'statusCode' => 400,
                'message'    => 'Trip is not on hold, so it cannot be restarted.',
                'data'       => []
            ], 400);
        }

        $trip->update(['status' => 'In Progress']);

        return response()->json([
            'status'     => true,
            'statusCode' => 200,
            'message'    => 'Trip restarted successfully. Status changed to In Progress.',
            'data'       => $trip
        ], 200);
    }

    
}
