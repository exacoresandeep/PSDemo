<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\District;
use App\Models\Order;
use App\Models\AssistanceType;
use App\Models\MaintenanceType;
use App\Models\ExpenseType;
use App\Models\AssignRoute;
use App\Models\VehicleCategory;
use App\Models\TripOrder;
use App\Models\TripPickup;
use App\Models\VehicleType;
use App\Models\Vehicle;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::all();
        return view('logistics.trip.index', compact('trips'));
    }
    public function create()
    {
        $districts = District::all();
        return view('logistics.trip.create', compact('districts'));
    }
    public function store(Request $request)
    {
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
            'password' => 'required|string|min:6',
            'dob' => 'required|date',
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
            $data['password'] = Hash::make($request->password);
            Driver::create($data);

            return redirect()->route('logistics.drivers.index')->with('success', 'Driver added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Something went wrong: ' . $e->getMessage()])->withInput();
        }
    }
    
    public function getData(Request $request)
    {
        $query = Trip::with(['vehicle', 'driver'])
            ->select([
                'id',
                'trip_code',
                'delivery_date',
                'from_location',
                'to_location',
                'total_quantity',
                'vehicle_id',
                'driver_id',
                'pickup_point_flag',
                'status',
                'updated_at'
            ]);

        // Optional filters
        if (!empty($request->trip_code)) {
            $query->where('trip_code', 'like', '%' . $request->trip_code . '%');
        }

        if (!empty($request->delivery_date)) {
            $query->whereDate('delivery_date', $request->delivery_date);
        }
        // Filter by date
        if (!empty($request->date)) {
            $query->whereDate('delivery_date', $request->date);
        }

        // Filter by driver name
        if (!empty($request->driver)) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->driver . '%');
            });
        }

        // Filter by vehicle number
        if (!empty($request->vehicle_no)) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('vehicle_no', 'like', '%' . $request->vehicle_no . '%');
            });
        }
        return DataTables::of($query)
            ->addIndexColumn() // Sl.No
            ->addColumn('delivery_date', function ($trip) {
                return $trip->delivery_date 
                    ? \Carbon\Carbon::parse($trip->delivery_date)->format('d-m-Y') 
                    : '-';
            })
            ->addColumn('updates', function ($trip) {
                return $trip->updated_at ? $trip->updated_at->format('d-m-Y H:i') : '-';
            })
            ->addColumn('status', function ($trip) {
                return $trip->status ;
            })
            ->addColumn('action', function ($trip) {
                $viewUrl = route('logistics.trip.show', $trip->id);
                return '
                    <a href="'.$viewUrl.'" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                ';
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }

    public function getVehicleCategories()
    {
        return VehicleCategory::select('id', 'vehicle_category_name')->get();
    }

    public function getVehicleTypes($categoryId)
    {
        return VehicleType::where('id', $categoryId)
            ->select('id', 'vehicle_type_name')
            ->get();
    }

    public function searchVehicles(Request $request)
    {
        $vehicleTypeId = $request->input('vehicle_type_id'); 
        $search = $request->input('q'); // 🔹 search text from select2

        $query = Vehicle::query()
            ->where('vehicle_type_id', $vehicleTypeId);

        if (!empty($search)) {
            $query->where('vehicle_no', 'like', '%' . $search . '%');
        }

        $vehicles = $query->select('id', 'vehicle_no', 'load_capacity')
                        ->orderBy('vehicle_no')
                        ->get();

        return response()->json($vehicles);
    }

    public function getDrivers(Request $request)
    {
        $search = $request->input('q'); // 🔹 search text from select2

        $query = Driver::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $drivers = $query->select('id', 'name', 'phone')
                        ->orderBy('name', 'asc')
                        ->get();

        return response()->json($drivers);
    }

    public function pendingOrders()
    {
        $orders = Order::with(['dealer', 'orderItems.product'])
            ->where('status', 'Accounts Approved')
            ->orderBy('delivery_date', 'asc')
            ->get();

        return response()->json($orders);
    }

    public function storeTrip(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|integer',
            'driver_id' => 'required|integer',
            'orders' => 'required|array|min:1',
            'pickup_points' => 'nullable|array',
            'total_quantity' => 'required',
            'approx_km' => 'required|integer',
            'from_location' => 'required|string',
            'to_location' => 'required|string',
            'delivery_date' => 'required|string',
        ]);

        // Example save logic
        $trip = new Trip();

        $lastTrip = Trip::latest('id')->first();
        $nextId = $lastTrip ? $lastTrip->id + 1 : 1;
        $tripCode = 'TRIP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $trip->vehicle_id = $validated['vehicle_id'];
        $trip->trip_code = $tripCode;
        $trip->driver_id = $validated['driver_id'];
        $trip->total_quantity = $validated['total_quantity'];
        $trip->approx_km = $validated['approx_km'];
        $trip->from_location = $validated['from_location'];
        $trip->to_location = $validated['to_location'];
        $trip->assign_date = now()->format('Y-m-d');
        $trip->delivery_date = $validated['delivery_date'];
        $trip->pickup_point_flag = !empty($pickupPoints) ? 'Yes' : 'No';
        $trip->notification_status = 'Pending';
        $trip->created_by = '1';
        $trip->updated_by = '1';
        $trip->status = 'Scheduled';
        $trip->save();

        $orders = is_string($validated['orders'])
        ? json_decode($validated['orders'], true)
        : $validated['orders'];
// dd($orders);
        //  $orders = array_map('intval', $orders);
        foreach ($orders as $index => $order) {
             $orderId   = (int) $order['order_id'];
            $sortOrder = $order['sort_order'] ?? null;
            $orderData = Order::with(['dealer', 'orderItems.product'])->find((int) $orderId);

            $quantity = $orderData->orderItems->sum(function($item) {
                return $item->total_quantity ?? 0;
            });
            TripOrder::create([
                'trip_id'           => $trip->id,
                'order_id'          => $orderId,
                'delivery_point_no' => $sortOrder,
                'delivery_address'  => $orderData->dealer->address ?? null,
                'contact_person'    => $orderData->dealer->dealer_name ?? null,
                'contact_phone'     => $orderData->dealer->phone ?? null,
                'office_phone'      => $orderData->dealer->phone ?? null,
                'delivery_date'     => $orderData->delivery_date ?? $trip->delivery_date,
                'quantity'          => $quantity ?? 0,
            ]);
        }


        if (!empty($validated['pickup_points']) && is_array($validated['pickup_points'])) {
            foreach ($validated['pickup_points'] as $pickupData) {

                // Optional: cast start/end km to float
                $startKm = isset($pickupData['start_km']) ? floatval($pickupData['start_km']) : null;
                $endKm   = isset($pickupData['end_km']) ? floatval($pickupData['end_km']) : null;

                $trip->pickups()->create([
                    'pickup_date'          => $pickupData['pickup_date'] ?? null,
                    'pickup_point'         => $pickupData['pickup_point'] ?? null,
                    'address'              => $pickupData['address'] ?? null,
                    'office_phone'         => $pickupData['office_phone'] ?? null,
                    'contact_person_name'  => $pickupData['contact_person_name'] ?? null,
                    'contact_person_phone' => $pickupData['contact_person_phone'] ?? null,
                    'attachment'           => $pickupData['attachment'] ?? null,
                    'start_km'             => $startKm,
                    'start_km_image'       => $pickupData['start_km_image'] ?? null,
                    'start_time'           => $pickupData['start_time'] ?? null,
                    'end_km'               => $endKm,
                    'end_km_image'         => $pickupData['end_km_image'] ?? null,
                    'end_time'             => $pickupData['end_time'] ?? null,
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Trip created successfully!',
            'trip_id' => $trip->id,
        ]);
    }

    
    
}

