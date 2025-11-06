<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class VehiclesController extends Controller
{
   
    public function index()
    {
        $vehicles = Vehicle::with('vehicleType')->latest()->get();
        return view('logistics.vehicles.index', compact('vehicles'));
    }

    
    public function create()
    {
        $vehicleTypes = VehicleType::all();
        $vehicleCategories = VehicleCategory::all();
        return view('logistics.vehicles.create', compact('vehicleTypes', 'vehicleCategories'));
    }
    public function getVehicleTypesByCategory($categoryId)
    {
        $vehicleTypes = VehicleType::where('vehicle_category_id', $categoryId)
            ->where('status', '1')
            ->get(['id', 'vehicle_type_name']);

        return response()->json($vehicleTypes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_category_id' => 'required|exists:vehicle_category,id',
            'vehicle_type_id' => 'required|exists:vehicle_type,id',
            'vehicle_no' => 'required|regex:/^[A-Z0-9-]+$/|unique:vehicles,vehicle_no',
            'current_km' => 'required|numeric|min:0',
            'load_capacity_ton' => 'nullable|numeric|min:0',
            'rc_exp_date' => 'required|date',
            'rc_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'chasis_no' => 'nullable|string|max:255',
            'engine_no' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'vehicle_tax_amount' => 'nullable|numeric|min:0',
            'tax_valid_upto' => 'nullable|date',
            'tax_receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'premium_amount' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_valid_upto' => 'nullable|date',
            'insurance_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'pollution_valid_upto' => 'nullable|date',
            'pollution_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'fitness_valid_upto' => 'nullable|date',
            'fitness_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'state_permit_valid_upto' => 'nullable|date',
            'state_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'national_permit_valid_upto' => 'nullable|date',
            'national_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'authorization_date' => 'nullable|date',

            // Conditional fields
            'service_km_duration' => $request->vehicle_category_id == 2 ? 'required|numeric|min:0' : 'nullable',
            'service_duration_days' => $request->vehicle_category_id == 2 ? 'required|integer|min:0' : 'nullable',
            'inspection_km_duration' => $request->vehicle_category_id == 2 ? 'required|numeric|min:0' : 'nullable',
            'inspection_duration_days' => $request->vehicle_category_id == 2 ? 'required|integer|min:0' : 'nullable',
            'transporter_name' => $request->vehicle_category_id == 3 ? 'required|string|max:255' : 'nullable',
            'transporter_phone' => $request->vehicle_category_id == 3 ? 'required|string|max:20' : 'nullable',
        ]);

        $prefix = 'VEH';
        $lastVehicle = Vehicle::latest('id')->first();
        $vehicleCode = $prefix . str_pad($lastVehicle ? $lastVehicle->id + 1 : 1, 5, '0', STR_PAD_LEFT); 

        $vehicleData = [
            'vehicle_category_id' => $request->vehicle_category_id,
            'vehicle_code' => $vehicleCode,
            'vehicle_type_id' => $request->vehicle_type_id,
            'vehicle_no' => $request->vehicle_no,
            'starting_km' => $request->current_km,
            'load_capacity' => $request->load_capacity_ton,
            'rc_exp_date' => $request->rc_exp_date,
            'rc_file' => $request->file('rc_file') ? $request->file('rc_file')->store('vehicles/rc_files', 'public') : null,
            'chasis_no' => $request->chasis_no,
            'engine_no' => $request->engine_no,
            'owner_name' => $request->owner_name,
            'vehicle_tax_amount' => $request->vehicle_tax_amount,
            'tax_valid_upto' => $request->tax_valid_upto,
            'tax_receipt_file' => $request->file('tax_receipt_file') ? $request->file('tax_receipt_file')->store('vehicles/tax_receipts', 'public') : null,
            'premium_amount' => $request->premium_amount,
            'insurance_type' => $request->insurance_type,
            'insurance_exp_date' => $request->insurance_valid_upto,
            'insurance_file' => $request->file('insurance_file') ? $request->file('insurance_file')->store('vehicles/insurance_files', 'public') : null,
            'puc_exp_date' => $request->pollution_valid_upto,
            'puc_file' => $request->file('pollution_file') ? $request->file('pollution_file')->store('vehicles/pollution_files', 'public') : null,
            'fitness_exp_date' => $request->fitness_valid_upto,
            'fitness_file' => $request->file('fitness_file') ? $request->file('fitness_file')->store('vehicles/fitness_files', 'public') : null,
            'permit_exp_date' => $request->state_permit_valid_upto,
            'permit_file' => $request->file('state_permit_file') ? $request->file('state_permit_file')->store('vehicles/state_permits', 'public') : null,
            'national_permit_valid_upto' => $request->national_permit_valid_upto,
            'national_permit_file' => $request->file('national_permit_file') ? $request->file('national_permit_file')->store('vehicles/national_permits', 'public') : null,
            'authorization_date' => $request->authorization_date,
        ];

        if ($request->vehicle_category_id == 2) { // Internal
            $vehicleData['service_km'] = $request->service_km_duration;
            $vehicleData['service_days'] = $request->service_duration_days;
            $vehicleData['inspection_km'] = $request->inspection_km_duration;
            $vehicleData['inspection_days'] = $request->inspection_duration_days;
        } elseif ($request->vehicle_category_id == 3) { // External
            $vehicleData['transporter_name'] = $request->transporter_name;
            $vehicleData['transporter_phone'] = $request->transporter_phone;
        }

        Vehicle::create($vehicleData);

        return redirect()->route('logistics.vehicles.index')
            ->with('success', 'Vehicle added successfully!');
    }

    public function getData(Request $request)
    {
        $vehicles = Vehicle::with('vehicleType') 
            ->select([
                'id', 'vehicle_category_id', 'vehicle_no', 'chasis_no', 'engine_no', 'owner_name', 'model',
                'year_of_manufacture', 'rc_exp_date', 'insurance_type', 'vehicle_tax_amount',
                'insurance_no', 'insurance_exp_date',
                'puc_no', 'puc_exp_date','authorization_date',
                'permit_no', 'permit_exp_date', 'status'
            ]);

        return DataTables::of($vehicles)
            ->addIndexColumn()
            ->editColumn('vehicle_category_id', function ($vehicle) {
                switch ($vehicle->vehicle_category_id) {
                    case 1:
                        return 'Dealer Vehicle';
                    case 2:
                        return 'Internal Vehicle';
                    case 3:
                        return 'External Vehicle';
                    default:
                        return 'N/A';
                }
            })

            // Filter logic for searching category
            ->filterColumn('vehicle_category_id', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    if (stripos('Dealer Vehicle', $keyword) !== false) {
                        $q->where('vehicle_category_id', 1);
                    } elseif (stripos('Internal Vehicle', $keyword) !== false) {
                        $q->where('vehicle_category_id', 2);
                    } elseif (stripos('External Vehicle', $keyword) !== false) {
                        $q->where('vehicle_category_id', 3);
                    }
                });
            })
            ->editColumn('rc_exp_date', function ($vehicle) {
                return $vehicle->rc_exp_date ? \Carbon\Carbon::parse($vehicle->rc_exp_date)->format('d/m/Y') : '-';
            })
            ->editColumn('authorization_date', function ($vehicle) {
                return $vehicle->authorization_date ? \Carbon\Carbon::parse($vehicle->authorization_date)->format('d/m/Y') : '-';
            })
            ->editColumn('status', function ($vehicle) {
                return $vehicle->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function ($vehicle) {
                return '
                    <a href="'.route('logistics.vehicles.show', $vehicle->id).'" class="btn btn-sm btn-warning view-btn">
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="'.route('logistics.vehicles.edit', $vehicle->id).'" class="btn btn-sm btn-info"><i class="fa fa-edit"></i></a>
                    <button class="btn btn-sm btn-danger delete-btn" 
                            data-id="'.$vehicle->id.'" 
                            data-url="'.route('logistics.vehicles.destroy', $vehicle->id).'">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['status', 'action']) 
            ->make(true);
    }
    public function show($id)
    {
        $vehicle = Vehicle::with('vehicleType')->findOrFail($id);
        return view('logistics.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $vehicleCategories = VehicleCategory::all();
        $vehicleTypes = VehicleType::where('vehicle_category_id', $vehicle->vehicle_category_id)->get();
        return view('logistics.vehicles.edit', compact('vehicle', 'vehicleCategories', 'vehicleTypes'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_category_id' => 'required|exists:vehicle_category,id',
            'vehicle_type_id' => 'required|exists:vehicle_type,id',
            'vehicle_no' => 'required|regex:/^[A-Z0-9-]+$/|unique:vehicles,vehicle_no,' . $vehicle->id,
            'current_km' => 'required|numeric|min:0',
            'load_capacity_ton' => 'nullable|numeric|min:0',
            'rc_exp_date' => 'required|date',
            'rc_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'chasis_no' => 'nullable|string|max:255',
            'engine_no' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'vehicle_tax_amount' => 'nullable|numeric|min:0',
            'tax_valid_upto' => 'nullable|date',
            'tax_receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'premium_amount' => 'nullable|numeric|min:0',
            'insurance_type' => 'nullable|string|max:255',
            'insurance_valid_upto' => 'nullable|date',
            'insurance_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'pollution_valid_upto' => 'nullable|date',
            'pollution_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'fitness_valid_upto' => 'nullable|date',
            'fitness_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'state_permit_valid_upto' => 'nullable|date',
            'state_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'national_permit_valid_upto' => 'nullable|date',
            'national_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'authorization_date' => 'nullable|date',

            // Conditional fields
            'service_km_duration' => $request->vehicle_category_id == 2 ? 'required|numeric|min:0' : 'nullable',
            'service_duration_days' => $request->vehicle_category_id == 2 ? 'required|integer|min:0' : 'nullable',
            'inspection_km_duration' => $request->vehicle_category_id == 2 ? 'required|numeric|min:0' : 'nullable',
            'inspection_duration_days' => $request->vehicle_category_id == 2 ? 'required|integer|min:0' : 'nullable',
            'transporter_name' => $request->vehicle_category_id == 3 ? 'required|string|max:255' : 'nullable',
            'transporter_phone' => $request->vehicle_category_id == 3 ? 'required|string|max:20' : 'nullable',
        ]);

        $vehicleData = [
            'vehicle_category_id' => $request->vehicle_category_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'vehicle_no' => $request->vehicle_no,
            'starting_km' => $request->current_km,
            'load_capacity' => $request->load_capacity_ton,
            'rc_exp_date' => $request->rc_exp_date,
            'chasis_no' => $request->chasis_no,
            'engine_no' => $request->engine_no,
            'owner_name' => $request->owner_name,
            'vehicle_tax_amount' => $request->vehicle_tax_amount,
            'tax_valid_upto' => $request->tax_valid_upto,
            'premium_amount' => $request->premium_amount,
            'insurance_type' => $request->insurance_type,
            'insurance_exp_date' => $request->insurance_valid_upto,
            'puc_exp_date' => $request->pollution_valid_upto,
            'fitness_exp_date' => $request->fitness_valid_upto,
            'permit_exp_date' => $request->state_permit_valid_upto,
            'national_permit_valid_upto' => $request->national_permit_valid_upto,
            'authorization_date' => $request->authorization_date,
        ];

        // Handle file uploads
        $fileFields = [
            'rc_file' => 'vehicles/rc_files',
            'tax_receipt_file' => 'vehicles/tax_receipts',
            'insurance_file' => 'vehicles/insurance_files',
            'pollution_file' => 'vehicles/pollution_files',
            'fitness_file' => 'vehicles/fitness_files',
            'state_permit_file' => 'vehicles/state_permits',
            'national_permit_file' => 'vehicles/national_permits',
        ];

        foreach ($fileFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $vehicleData[$field] = $request->file($field)->store($folder, 'public');
            }
        }

        // Conditional internal/external logic
        if ($request->vehicle_category_id == 2) { // Internal
            $vehicleData['service_km'] = $request->service_km_duration;
            $vehicleData['service_days'] = $request->service_duration_days;
            $vehicleData['inspection_km'] = $request->inspection_km_duration;
            $vehicleData['inspection_days'] = $request->inspection_duration_days;
            $vehicleData['transporter_name'] = null;
            $vehicleData['transporter_phone'] = null;
        } elseif ($request->vehicle_category_id == 3) { // External
            $vehicleData['transporter_name'] = $request->transporter_name;
            $vehicleData['transporter_phone'] = $request->transporter_phone;
            $vehicleData['service_km'] = null;
            $vehicleData['service_days'] = null;
            $vehicleData['inspection_km'] = null;
            $vehicleData['inspection_days'] = null;
        }

        $vehicle->update($vehicleData);

        return redirect()->route('logistics.vehicles.index')
            ->with('success', 'Vehicle updated successfully!');
    }
    

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('logistics.vehicles.index')->with('success', 'Vehicle deleted successfully!');
    }
}

