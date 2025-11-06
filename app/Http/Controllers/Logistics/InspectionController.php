<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\TyreType;
use App\Models\TyreCategory;
use App\Models\TyreCondition;
use App\Models\EngineOilLevel;
use App\Models\CoolantLevel;
use App\Models\VehicleGreasingCondition;
use App\Models\WashingCondition;
use App\Models\MirrorCondition;
use App\Models\IndicatorLightCondition;
use App\Models\BatteryCondition;
use App\Models\MudFlapCondition;
use App\Models\ClutchFluidCondition;
use App\Models\AxleType;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\VehicleInspection;
use App\Models\InspectionTyre;
use App\Models\InspectionPhoto;
use App\Models\VehicleServiceMaintenance;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string',
                'password' => 'required|string',
                'type' => 'required|string', // accept type instead of employee_type_id
            ]);

            // Map type to employee_type_id
            $typeMapping = [
                'Inspection' => 7,
                // add other mappings here if needed
            ];

            if (!array_key_exists($validated['type'], $typeMapping)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid employee type',
                ], 400);
            }

            $employeeTypeId = $typeMapping[$validated['type']];

            $employee = Employee::join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
                ->where('employee_code', $validated['employee_code'])
                ->where('employees.employee_type_id', $employeeTypeId)
                ->select('employees.*', 'employee_types.id as type_id', 'employee_types.type_name')
                ->first();
            
            if (!$employee || !Hash::check($validated['password'], $employee->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid credentials',
                ], 400);
            }

            $token = $employee->createToken('API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Login successful',
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->name,
                        'designation' => $employee->designation,
                        'email' => $employee->email,
                        'password_reset_flag' => $employee->password_reset_flag == 0 ? false : true,
                        'phone' => $employee->phone,
                        'address' => $employee->address,
                        'photo' => $employee->photo,
                        'emergency_contact' => $employee->emergency_contact,
                    ],
                    'employee_type' => [
                        'id' => $employee->type_id,
                        'type_name' => $employee->type_name,
                    ],
                    'token' => $token,
                    'status' => 'active',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProfile(Request $request)
    {
        try {
            $employee = $request->user();
            $employee = Employee::with('employeeType', 'reportingManager:id,name')->where('id', $employee->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Employee not found',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employee details retrieved successfully',
                'data' => $employee,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
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
    public function getTyreTypes(Request $request)
    {
        try {
            $user = Auth::user();

            $vehicleTypeId = $request->input('vehicle_type_id');
            $axleTypeId = $request->input('axle_type_id');

            $data = $user 
                ? TyreType::select('id as id', 'name')
                    ->where('status', 1)
                    ->when($vehicleTypeId, function ($q) use ($vehicleTypeId) {
                        $q->where('vehicle_type_id', $vehicleTypeId);
                    })
                    ->when($axleTypeId, function ($q) use ($axleTypeId) {
                        $q->where('axle_type_id', $axleTypeId);
                    })
                    ->orderBy('name')
                    ->get()
                : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Tyre types fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function getTyreCategories()
    {
        try {
            $user = Auth::user();
            $data = $user ? TyreCategory::select('id as tyre_category_id', 'name')->where('status', 1)->orderBy('name')->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Tyre categories fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getTyreConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? TyreCondition::select('id as tyre_condition_id', 'name')->where('status', 1)->orderBy('name')->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Tyre conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getEngineOilLevels()
    {
        try {
            $user = Auth::user();
            $data = $user ? EngineOilLevel::select('id as engine_oil_level_id', 'name')->where('status', 1)->orderBy('name')->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Engine oil levels fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getCoolantLevels()
    {
        try {
            $user = Auth::user();
            $data = $user ? CoolantLevel::select('id as coolant_level_id', 'name')->where('status', 1)->orderBy('name')->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Coolant levels fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getVehicleGreasingCondition()
    {
        try {
            $user = Auth::user();
            $data = $user ? VehicleGreasingCondition::select('id as vehicle_greasing_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle greasing conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getWashingCondition()
    {
        try {
            $user = Auth::user();
            $data = $user ? WashingCondition::select('id as washing_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Washing conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getMirrorConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? MirrorCondition::select('id as mirror_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Mirror conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getIndicatorLightConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? IndicatorLightCondition::select('id as indicator_light_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Indicator light conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getBatteryConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? BatteryCondition::select('id as battery_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Battery conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getMudFlapConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? MudFlapCondition::select('id as mud_flap_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Mud flap conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getClutchFluidConditions()
    {
        try {
            $user = Auth::user();
            $data = $user ? ClutchFluidCondition::select('id as clutch_fluid_condition_id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Clutch fluid conditions fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getAllConditions()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized',
                    'data' => []
                ]);
            }

            $data = [
                'vehicle_greasing_conditions' => VehicleGreasingCondition::select('id as vehicle_greasing_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'washing_conditions' => WashingCondition::select('id as washing_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'mirror_conditions' => MirrorCondition::select('id as mirror_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'indicator_light_conditions' => IndicatorLightCondition::select('id as indicator_light_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'battery_conditions' => BatteryCondition::select('id as battery_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'mud_flap_conditions' => MudFlapCondition::select('id as mud_flap_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
                'clutch_fluid_conditions' => ClutchFluidCondition::select('id as clutch_fluid_condition_id', 'name')
                                                    ->where('status', 1)
                                                    ->orderBy('name')
                                                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'All vehicle conditions fetched successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getAxleTypes()
    {
        try {
            $user = Auth::user();

            $data = $user 
                ? AxleType::select('id as id', 'name')
                    ->where('status', 1)
                    ->where('vehicle_type_id', 5)
                    ->whereHas('vehicleType', function ($q) {
                        $q->where('vehicle_category_id', 2);
                    })
                    ->orderBy('name')
                    ->get()
                : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Axle types fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
   
    public function regularInspectionList()
    {
        try {
            $today = Carbon::today()->startOfDay();

            $vehicles = Vehicle::with('vehicleType')
                ->where('status', 'Active')
                ->where(function ($q) use ($today) {

                    $q->where(function ($sub) use ($today) {
                        $sub->whereNull('last_inspection_date')
                            ->whereDate(DB::raw("DATE_ADD(created_at, INTERVAL inspection_days DAY)"), '<=', $today);
                    })

                    ->orWhere(function ($sub) use ($today) {
                        $sub->whereNotNull('last_inspection_date')
                            ->where(function ($q2) use ($today) {
                                $q2->whereDate(DB::raw("DATE_ADD(last_inspection_date, INTERVAL inspection_days DAY)"), '<=', $today)
                                ->orWhereRaw('starting_km - last_inspection_km >= inspection_km');
                            });
                    });
                })

                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('trips')
                        ->whereColumn('trips.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.status', ['Scheduled', 'In Progress', 'On Hold']);
                })
                ->get()
                ->map(function($vehicle) use ($today) {

                    if ($vehicle->last_inspection_date) {
                        $inspectionDate = $vehicle->last_inspection_date;
                        $inspectionKm = $vehicle->last_inspection_km;
                    } else {
                        $inspectionDate = $today->toDateString();
                        $inspectionKm = $vehicle->starting_km;
                    }

                    $lastInspectionDate = $vehicle->last_inspection_date ?? $vehicle->created_at;
                    $dueDate = Carbon::parse($lastInspectionDate)->addDays($vehicle->inspection_days)->startOfDay();

                    if ($today > $dueDate) {
                        $inspectionStatus = 'Pending';
                        $pendingDays = $dueDate->diffInDays($today);
                    } else {
                        $inspectionStatus = null;
                        $pendingDays = 0;
                    }

                    return [
                        'vehicle_id'        => $vehicle->id,
                        'vehicle_number'    => $vehicle->vehicle_no,
                        'vehicle_type'      => $vehicle->vehicleType->vehicle_type_name ?? null,
                        'inspection_date'   => $inspectionDate,
                        'inspection_km'     => $inspectionKm,
                        'inspection_status' => $inspectionStatus,
                        'pending_days'      => $pendingDays,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Regular inspection list retrieved successfully',
                'data' => $vehicles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    public function viewRegularInspection($vehicleId)
    {
        try {
            $vehicle = Vehicle::with('vehicleType')->findOrFail($vehicleId);

            $lastInspection = VehicleInspection::where('vehicle_id', $vehicleId)
                ->where('inspection_type', 'Regular')
                ->latest('inspection_date')
                ->first();

            $tyres = [];
            $photos = [];
            if ($lastInspection) {
                $inspectionId = $lastInspection->id;

                $tyres = InspectionTyre::where('inspection_id', $inspectionId)->get();

                $photos = InspectionPhoto::where('inspection_id', $inspectionId)->get();
            }

            $inspectionData = $lastInspection ? [
                'inspection_date'     => $lastInspection->inspection_date ? $lastInspection->inspection_date->format('d/m/Y') : null,
                'inspection_km'       => $lastInspection->inspection_km,
                'engine_oil_level'    => $lastInspection->engine_oil_level,
                'coolant_level'       => $lastInspection->coolant_level,
                'clutch_fluid'        => $lastInspection->clutch_fluid,
                'vehicle_greasing'    => $lastInspection->vehicle_greasing,
                'vehicle_washing'     => $lastInspection->vehicle_washing,
                'mirror_condition'    => $lastInspection->mirror_condition,
                'indicator_condition' => $lastInspection->indicator_condition,
                'battery_condition'   => $lastInspection->battery_condition,
                'mudflap_condition'   => $lastInspection->mudflap_condition,
                'essential_equipment' => $lastInspection->essential_equipment,
                'remarks'             => $lastInspection->remarks,
                'tyres'               => $tyres,
                'photos'              => $photos,
            ] : [
                'inspection_date'     => null,
                'inspection_km'       => null,
                'engine_oil_level'    => null,
                'coolant_level'       => null,
                'clutch_fluid'        => null,
                'vehicle_greasing'    => null,
                'vehicle_washing'     => null,
                'mirror_condition'    => null,
                'indicator_condition' => null,
                'battery_condition'   => null,
                'mudflap_condition'   => null,
                'essential_equipment' => [],
                'remarks'             => null,
                'tyres'               => [],
                'photos'              => [],
            ];

            $today = Carbon::today()->startOfDay();
            $dueDate = Carbon::parse($vehicle->last_inspection_date ?? $vehicle->created_at)
                ->addDays($vehicle->inspection_days)
                ->startOfDay();

            $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
            $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Vehicle inspection details retrieved successfully',
                'data' => array_merge([
                    'vehicle_no'          => $vehicle->vehicle_no,
                    'vehicle_type'        => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'vehicle_category_id' => $vehicle->vehicleType->vehicle_category_id ?? null,
                    'inspection_status'   => $inspectionStatus,
                    'pending_days'        => $pendingDays,
                    'inspection_type'     => 'Regular Inspection',
                    'last_inspected_date' => $inspectionData['inspection_date'],
                    'last_inspected_km'   => $inspectionData['inspection_km'],
                ], $inspectionData)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function submitInspection(Request $request, $vehicleId)
    {
        try {
            $request->validate([
                'current_kilometer' => 'required|integer',
                'current_inspection_date' => 'required|date',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|string',
                'tyres' => 'nullable|array',
                'tyres.*.axle_type' => 'nullable|string',
                'tyres.*.tyre_type' => 'nullable|string',
                'tyres.*.tyre_category' => 'nullable|string',
                'tyres.*.tyre_condition' => 'nullable|string',
                'engine_oil_level' => 'nullable|string',
                'coolant_level' => 'nullable|string',
                'clutch_fluid' => 'nullable|string',
                'vehicle_greasing' => 'nullable|string',
                'vehicle_washing' => 'nullable|string',
                'mirror_condition' => 'nullable|string',
                'indicator_condition' => 'nullable|string',
                'battery_condition' => 'nullable|string',
                'mudflap_condition' => 'nullable|string',
                'essential_equipment' => 'nullable|array',
                'remarks' => 'nullable|string',
            ]);

            $vehicle = Vehicle::findOrFail($vehicleId);

            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'inspection_type' => 'Regular',
                'inspection_date' => $request->current_inspection_date,
                'inspection_km' => $request->current_kilometer,
                'engine_oil_level' => $request->engine_oil_level,
                'coolant_level' => $request->coolant_level,
                'clutch_fluid' => $request->clutch_fluid,
                'vehicle_greasing' => $request->vehicle_greasing,
                'vehicle_washing' => $request->vehicle_washing,
                'mirror_condition' => $request->mirror_condition,
                'indicator_condition' => $request->indicator_condition,
                'battery_condition' => $request->battery_condition,
                'mudflap_condition' => $request->mudflap_condition,
                'essential_equipment' => $request->essential_equipment,
                'remarks' => $request->remarks,
            ]);

            if ($request->has('tyres')) {
                foreach ($request->tyres as $tyre) {
                    InspectionTyre::create([
                        'inspection_id' => $inspection->id,
                        'axle_type' => $tyre['axle_type'] ?? null,
                        'tyre_type' => $tyre['tyre_type'] ?? null,
                        'tyre_category' => $tyre['tyre_category'] ?? null,
                        'tyre_condition' => $tyre['tyre_condition'] ?? null,
                    ]);
                }
            }

            if ($request->has('photos')) {
                foreach ($request->photos as $photo) {
                    InspectionPhoto::create([
                        'inspection_id' => $inspection->id,
                        'photo_path' => $photo
                    ]);
                }
            }

            $vehicle->update([
                'last_inspection_date' => $request->current_inspection_date,
                'last_inspection_km' => $request->current_kilometer,
            ]);

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Vehicle inspection submitted successfully',
                'data' => $inspection->load('tyres', 'photos')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function preTripInspectionList()
    {
        try {
            $today = Carbon::today()->startOfDay();

            $trips = Trip::with('vehicle.vehicleType')
                ->where('status', 'Scheduled')
                ->get();

            $vehicles = $trips->map(function ($trip) use ($today) {
                $vehicle = $trip->vehicle;

                if (!$vehicle) {
                    return null; // skip if no vehicle linked
                }

                if ($vehicle->last_inspection_date) {
                    $inspectionDate = $vehicle->last_inspection_date;
                    $inspectionKm = $vehicle->last_inspection_km;
                } else {
                    $inspectionDate = $today->toDateString();
                    $inspectionKm = $vehicle->starting_km;
                }

                $lastInspectionDate = $vehicle->last_inspection_date ?? $vehicle->created_at;
                $dueDate = Carbon::parse($lastInspectionDate)->addDays($vehicle->inspection_days)->startOfDay();

                if ($today > $dueDate) {
                    $inspectionStatus = 'Pending';
                    $pendingDays = $dueDate->diffInDays($today);
                } else {
                    $inspectionStatus = null;
                    $pendingDays = 0;
                }

                return [
                    'trip_id'           => $trip->id,
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'vehicle_type'      => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'inspection_date'   => $inspectionDate,
                    'inspection_km'     => $inspectionKm,
                    'inspection_status' => $inspectionStatus,
                    'pending_days'      => $pendingDays,
                ];
            })->filter(); // remove nulls if any vehicle missing

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Pre-trip inspection list retrieved successfully',
                'data' => $vehicles->unique('vehicle_id')->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function viewPreTripInspection($vehicleId)
    {
        try {
            $vehicle = Vehicle::with('vehicleType')->findOrFail($vehicleId);

            $lastInspection = VehicleInspection::where('vehicle_id', $vehicleId)
                ->where('inspection_type', 'Pre Trip')
                ->latest('inspection_date')
                ->first();

            $tyres = [];
            $photos = [];
            if ($lastInspection) {
                $inspectionId = $lastInspection->id;
                $tyres = InspectionTyre::where('inspection_id', $inspectionId)->get();
                $photos = InspectionPhoto::where('inspection_id', $inspectionId)->get();
            }

            $inspectionData = $lastInspection ? [
                'inspection_date'     => $lastInspection->inspection_date ? $lastInspection->inspection_date->format('d/m/Y') : null,
                'inspection_km'       => $lastInspection->inspection_km,
                'engine_oil_level'    => $lastInspection->engine_oil_level,
                'coolant_level'       => $lastInspection->coolant_level,
                'clutch_fluid'        => $lastInspection->clutch_fluid,
                'vehicle_greasing'    => $lastInspection->vehicle_greasing,
                'vehicle_washing'     => $lastInspection->vehicle_washing,
                'mirror_condition'    => $lastInspection->mirror_condition,
                'indicator_condition' => $lastInspection->indicator_condition,
                'battery_condition'   => $lastInspection->battery_condition,
                'mudflap_condition'   => $lastInspection->mudflap_condition,
                'essential_equipment' => $lastInspection->essential_equipment,
                'remarks'             => $lastInspection->remarks,
                'tyres'               => $tyres,
                'photos'              => $photos,
            ] : [
                'inspection_date'     => null,
                'inspection_km'       => null,
                'engine_oil_level'    => null,
                'coolant_level'       => null,
                'clutch_fluid'        => null,
                'vehicle_greasing'    => null,
                'vehicle_washing'     => null,
                'mirror_condition'    => null,
                'indicator_condition' => null,
                'battery_condition'   => null,
                'mudflap_condition'   => null,
                'essential_equipment' => [],
                'remarks'             => null,
                'tyres'               => [],
                'photos'              => [],
            ];
            $today = Carbon::today()->startOfDay();
            $dueDate = Carbon::parse($vehicle->last_inspection_date ?? $vehicle->created_at)
                ->addDays($vehicle->inspection_days)
                ->startOfDay();

            $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
            $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Pre-trip inspection details retrieved successfully',
                'data' => array_merge([
                    'vehicle_no'          => $vehicle->vehicle_no,
                    'vehicle_type'        => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'vehicle_category_id' => $vehicle->vehicleType->vehicle_category_id ?? null,
                    'inspection_type'     => 'Pre Trip Inspection',
                    'inspection_status'   => $inspectionStatus,
                    'pending_days'        => $pendingDays,
                    'last_inspected_date' => $inspectionData['inspection_date'],
                    'last_inspected_km'   => $inspectionData['inspection_km'],
                ], $inspectionData)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function updatePreTripInspection(Request $request, $vehicleId)
    {
        try {
            $request->validate([
                'current_kilometer' => 'required|integer',
                'current_inspection_date' => 'required|date',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|string',
                'tyres' => 'nullable|array',
                'tyres.*.axle_type' => 'nullable|string',
                'tyres.*.tyre_type' => 'nullable|string',
                'tyres.*.tyre_category' => 'nullable|string',
                'tyres.*.tyre_condition' => 'nullable|string',
                'engine_oil_level' => 'nullable|string',
                'coolant_level' => 'nullable|string',
                'clutch_fluid' => 'nullable|string',
                'vehicle_greasing' => 'nullable|string',
                'vehicle_washing' => 'nullable|string',
                'mirror_condition' => 'nullable|string',
                'indicator_condition' => 'nullable|string',
                'battery_condition' => 'nullable|string',
                'mudflap_condition' => 'nullable|string',
                'essential_equipment' => 'nullable|array',
                'remarks' => 'nullable|string',
            ]);

            $vehicle = Vehicle::findOrFail($vehicleId);

            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'inspection_type' => 'Pre Trip',
                'inspection_date' => $request->current_inspection_date,
                'inspection_km' => $request->current_kilometer,
                'engine_oil_level' => $request->engine_oil_level,
                'coolant_level' => $request->coolant_level,
                'clutch_fluid' => $request->clutch_fluid,
                'vehicle_greasing' => $request->vehicle_greasing,
                'vehicle_washing' => $request->vehicle_washing,
                'mirror_condition' => $request->mirror_condition,
                'indicator_condition' => $request->indicator_condition,
                'battery_condition' => $request->battery_condition,
                'mudflap_condition' => $request->mudflap_condition,
                'essential_equipment' => $request->essential_equipment,
                'remarks' => $request->remarks,
            ]);

            if ($request->has('tyres')) {
                foreach ($request->tyres as $tyre) {
                    InspectionTyre::create([
                        'inspection_id' => $inspection->id,
                        'axle_type' => $tyre['axle_type'] ?? null,
                        'tyre_type' => $tyre['tyre_type'] ?? null,
                        'tyre_category' => $tyre['tyre_category'] ?? null,
                        'tyre_condition' => $tyre['tyre_condition'] ?? null,
                    ]);
                }
            }

            if ($request->has('photos')) {
                foreach ($request->photos as $photo) {
                    InspectionPhoto::create([
                        'inspection_id' => $inspection->id,
                        'photo_path' => $photo
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Pre-trip inspection submitted successfully',
                'data' => $inspection->load('tyres', 'photos')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    public function postTripInspectionList()
    {
        try {
            $today = Carbon::today()->startOfDay();

            $trips = Trip::with('vehicle.vehicleType')
                ->where('status', 'Completed')
                ->get();

            $vehicles = $trips->map(function ($trip) use ($today) {
                $vehicle = $trip->vehicle;
            
                if (!$vehicle) {
                    return null;
                }
            
                if ($vehicle->last_inspection_date) {
                    $inspectionDate = $vehicle->last_inspection_date;
                    $inspectionKm = $vehicle->last_inspection_km;
                } else {
                    $inspectionDate = $today->toDateString();
                    $inspectionKm = $vehicle->starting_km;
                }
            
                $lastInspectionDate = $vehicle->last_inspection_date ?? $vehicle->created_at;
                $dueDate = Carbon::parse($lastInspectionDate)->addDays($vehicle->inspection_days)->startOfDay();
            
                $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
                $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;
            
                return [
                    'trip_id'           => $trip->id,
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'vehicle_type'      => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'inspection_date'   => $inspectionDate,
                    'inspection_km'     => $inspectionKm,
                    'inspection_status' => $inspectionStatus,
                    'pending_days'      => $pendingDays,
                ];
            })
            ->filter()
            ->unique('vehicle_id') // ✅ keep only one per vehicle
            ->values();


            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Post-trip inspection list retrieved successfully',
                'data' => $vehicles->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    public function viewPostTripInspection($vehicleId)
    {
        try {
            $vehicle = Vehicle::with('vehicleType')->findOrFail($vehicleId);

            $lastInspection = VehicleInspection::where('vehicle_id', $vehicleId)
                ->where('inspection_type', 'Post Trip')
                ->latest('inspection_date')
                ->first();

            $tyres = $lastInspection ? InspectionTyre::where('inspection_id', $lastInspection->id)->get() : [];
            $photos = $lastInspection ? InspectionPhoto::where('inspection_id', $lastInspection->id)->get() : [];

            $inspectionData = $lastInspection ? [
                'inspection_date'     => $lastInspection->inspection_date ? $lastInspection->inspection_date->format('d/m/Y') : null,
                'inspection_km'       => $lastInspection->inspection_km,
                'engine_oil_level'    => $lastInspection->engine_oil_level,
                'coolant_level'       => $lastInspection->coolant_level,
                'clutch_fluid'        => $lastInspection->clutch_fluid,
                'vehicle_greasing'    => $lastInspection->vehicle_greasing,
                'vehicle_washing'     => $lastInspection->vehicle_washing,
                'mirror_condition'    => $lastInspection->mirror_condition,
                'indicator_condition' => $lastInspection->indicator_condition,
                'battery_condition'   => $lastInspection->battery_condition,
                'mudflap_condition'   => $lastInspection->mudflap_condition,
                'essential_equipment' => $lastInspection->essential_equipment,
                'remarks'             => $lastInspection->remarks,
                'tyres'               => $tyres,
                'photos'              => $photos,
            ] : null;

            // Avoiding the error by checking if $inspectionData is null or not
            $inspectionDate = $inspectionData['inspection_date'] ?? null;
            $inspectionKm = $inspectionData['inspection_km'] ?? null;

            $today = Carbon::today()->startOfDay();
            $dueDate = Carbon::parse($vehicle->last_inspection_date ?? $vehicle->created_at)
                ->addDays($vehicle->inspection_days)
                ->startOfDay();

            $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
            $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Post-trip inspection details retrieved successfully',
                'data' => array_merge([
                    'vehicle_no'          => $vehicle->vehicle_no,
                    'vehicle_type'        => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'vehicle_category_id' => $vehicle->vehicleType->vehicle_category_id ?? null,
                    'inspection_type'     => 'Post Trip Inspection',
                    'inspection_status'   => $inspectionStatus,
                    'pending_days'        => $pendingDays,
                    'last_inspected_date' => $inspectionDate,
                    'last_inspected_km'   => $inspectionKm,
                ], $inspectionData ?? [])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    public function updatePostTripInspection(Request $request, $vehicleId)
    {
        try {
            $request->validate([
                'current_kilometer' => 'required|integer',
                'current_inspection_date' => 'required|date',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|string',
                'tyres' => 'nullable|array',
                'tyres.*.axle_type' => 'nullable|string',
                'tyres.*.tyre_type' => 'nullable|string',
                'tyres.*.tyre_category' => 'nullable|string',
                'tyres.*.tyre_condition' => 'nullable|string',
                'engine_oil_level' => 'nullable|string',
                'coolant_level' => 'nullable|string',
                'clutch_fluid' => 'nullable|string',
                'vehicle_greasing' => 'nullable|string',
                'vehicle_washing' => 'nullable|string',
                'mirror_condition' => 'nullable|string',
                'indicator_condition' => 'nullable|string',
                'battery_condition' => 'nullable|string',
                'mudflap_condition' => 'nullable|string',
                'essential_equipment' => 'nullable|array',
                'remarks' => 'nullable|string',
            ]);

            $vehicle = Vehicle::findOrFail($vehicleId);

            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'inspection_type' => 'Post Trip',
                'inspection_date' => $request->current_inspection_date,
                'inspection_km' => $request->current_kilometer,
                'engine_oil_level' => $request->engine_oil_level,
                'coolant_level' => $request->coolant_level,
                'clutch_fluid' => $request->clutch_fluid,
                'vehicle_greasing' => $request->vehicle_greasing,
                'vehicle_washing' => $request->vehicle_washing,
                'mirror_condition' => $request->mirror_condition,
                'indicator_condition' => $request->indicator_condition,
                'battery_condition' => $request->battery_condition,
                'mudflap_condition' => $request->mudflap_condition,
                'essential_equipment' => $request->essential_equipment,
                'remarks' => $request->remarks,
            ]);

            if ($request->has('tyres')) {
                foreach ($request->tyres as $tyre) {
                    InspectionTyre::create([
                        'inspection_id' => $inspection->id,
                        'axle_type' => $tyre['axle_type'] ?? null,
                        'tyre_type' => $tyre['tyre_type'] ?? null,
                        'tyre_category' => $tyre['tyre_category'] ?? null,
                        'tyre_condition' => $tyre['tyre_condition'] ?? null,
                    ]);
                }
            }

            if ($request->has('photos')) {
                foreach ($request->photos as $photo) {
                    InspectionPhoto::create([
                        'inspection_id' => $inspection->id,
                        'photo_path' => $photo
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Post-trip inspection submitted successfully',
                'data' => $inspection->load('tyres', 'photos')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    public function postServiceInspectionList()
    {
        try {
            $today = Carbon::today()->startOfDay();

            $vehicles = Vehicle::with('vehicleType')
                ->where('status', 1) // active vehicles
                ->get();

            $list = $vehicles->map(function ($vehicle) use ($today) {
                if (!$vehicle) {
                    return null;
                }

                if ($vehicle->last_inspection_date) {
                    $inspectionDate = $vehicle->last_inspection_date;
                    $inspectionKm   = $vehicle->last_inspection_km;
                } else {
                    $inspectionDate = $today->toDateString();
                    $inspectionKm   = $vehicle->starting_km;
                }

                $lastInspectionDate = $vehicle->last_inspection_date ?? $vehicle->created_at;
                $dueDate = Carbon::parse($lastInspectionDate)->addDays($vehicle->inspection_days)->startOfDay();

                $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
                $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;

                return [
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'vehicle_type'      => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'inspection_date'   => $inspectionDate,
                    'inspection_km'     => $inspectionKm,
                    'inspection_status' => $inspectionStatus,
                    'pending_days'      => $pendingDays,
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Post-service inspection list retrieved successfully',
                'data' => $list->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function viewPostServiceInspection($vehicleId)
    {
        try {
            $vehicle = Vehicle::with('vehicleType')->findOrFail($vehicleId);

            $lastInspection = VehicleInspection::where('vehicle_id', $vehicleId)
                ->where('inspection_type', 'Post Service')
                ->latest('inspection_date')
                ->first();

            $tyres = $lastInspection ? InspectionTyre::where('inspection_id', $lastInspection->id)->get() : [];
            $photos = $lastInspection ? InspectionPhoto::where('inspection_id', $lastInspection->id)->get() : [];

            $maintenance = $lastInspection ? VehicleServiceMaintenance::where('inspection_id', $lastInspection->id)->first() 
            : null;

            $inspectionData = $lastInspection ? [
                'inspection_date'     => $lastInspection->inspection_date ? $lastInspection->inspection_date->format('d/m/Y') : null,
                'inspection_km'       => $lastInspection->inspection_km,
                'engine_oil_level'    => $lastInspection->engine_oil_level,
                'coolant_level'       => $lastInspection->coolant_level,
                'clutch_fluid'        => $lastInspection->clutch_fluid,
                'vehicle_greasing'    => $lastInspection->vehicle_greasing,
                'vehicle_washing'     => $lastInspection->vehicle_washing,
                'mirror_condition'    => $lastInspection->mirror_condition,
                'indicator_condition' => $lastInspection->indicator_condition,
                'battery_condition'   => $lastInspection->battery_condition,
                'mudflap_condition'   => $lastInspection->mudflap_condition,
                'essential_equipment' => $lastInspection->essential_equipment,
                'remarks'             => $lastInspection->remarks,
                'tyres'               => $tyres,
                'photos'              => $photos,
                
            ] : null;

            $today = Carbon::today()->startOfDay();
            $dueDate = Carbon::parse($vehicle->last_inspection_date ?? $vehicle->created_at)
                ->addDays($vehicle->inspection_days)
                ->startOfDay();

            $inspectionStatus = ($today > $dueDate) ? 'Pending' : null;
            $pendingDays = ($today > $dueDate) ? $dueDate->diffInDays($today) : 0;

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Post-service inspection details retrieved successfully',
                'data' => array_merge([
                    'vehicle_no'          => $vehicle->vehicle_no,
                    'vehicle_type'        => $vehicle->vehicleType->vehicle_type_name ?? null,
                    'vehicle_category_id' => $vehicle->vehicleType->vehicle_category_id ?? null,
                    'inspection_type'     => 'Post Service Inspection',
                    'inspection_status'   => $inspectionStatus,
                    'pending_days'        => $pendingDays,
                    'last_inspected_date' => $inspectionData['inspection_date'] ?? null,
                    'last_inspected_km'   => $inspectionData['inspection_km'] ?? null,
                    'maintenance'         => $maintenance,
                ], $inspectionData ?? [])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function updatePostServiceInspection(Request $request, $vehicleId)
    {
        try {
            $request->validate([
                'current_kilometer' => 'required|integer',
                'current_inspection_date' => 'required|date',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|string',
                'tyres' => 'nullable|array',
                'tyres.*.axle_type' => 'nullable|string',
                'tyres.*.tyre_type' => 'nullable|string',
                'tyres.*.tyre_category' => 'nullable|string',
                'tyres.*.tyre_condition' => 'nullable|string',
                'engine_oil_level' => 'nullable|string',
                'coolant_level' => 'nullable|string',
                'clutch_fluid' => 'nullable|string',
                'vehicle_greasing' => 'nullable|string',
                'vehicle_washing' => 'nullable|string',
                'mirror_condition' => 'nullable|string',
                'indicator_condition' => 'nullable|string',
                'battery_condition' => 'nullable|string',
                'mudflap_condition' => 'nullable|string',
                'essential_equipment' => 'nullable|array',
                'remarks' => 'nullable|string',
                'maintenance' => 'nullable|array',
                'maintenance.*.task' => 'nullable|string',
                'maintenance.*.status' => 'nullable|string',
            ]);

            $vehicle = Vehicle::findOrFail($vehicleId);

            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'inspection_type' => 'Post Service',
                'inspection_date' => $request->current_inspection_date,
                'inspection_km' => $request->current_kilometer,
                'engine_oil_level' => $request->engine_oil_level,
                'coolant_level' => $request->coolant_level,
                'clutch_fluid' => $request->clutch_fluid,
                'vehicle_greasing' => $request->vehicle_greasing,
                'vehicle_washing' => $request->vehicle_washing,
                'mirror_condition' => $request->mirror_condition,
                'indicator_condition' => $request->indicator_condition,
                'battery_condition' => $request->battery_condition,
                'mudflap_condition' => $request->mudflap_condition,
                'essential_equipment' => $request->essential_equipment,
                'remarks' => $request->remarks,
            ]);

            if ($request->has('tyres')) {
                foreach ($request->tyres as $tyre) {
                    InspectionTyre::create([
                        'inspection_id' => $inspection->id,
                        'axle_type' => $tyre['axle_type'] ?? null,
                        'tyre_type' => $tyre['tyre_type'] ?? null,
                        'tyre_category' => $tyre['tyre_category'] ?? null,
                        'tyre_condition' => $tyre['tyre_condition'] ?? null,
                    ]);
                }
            }

            if ($request->has('photos')) {
                foreach ($request->photos as $photo) {
                    InspectionPhoto::create([
                        'inspection_id' => $inspection->id,
                        'photo_path' => $photo
                    ]);
                }
            }

            if ($request->has('maintenance')) {
                foreach ($request->maintenance as $task) {
                    VehicleServiceMaintenance::create([
                        'inspection_id' => $inspection->id,
                        'task' => $task['task'] ?? null,
                        'status' => $task['status'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Post-service inspection submitted successfully',
                'data' => $inspection->load('tyres', 'photos', 'maintenance')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    public function getDashboardDetails()
    {
        try {
            $today = Carbon::today()->startOfDay();

            
            $regularInspections = Vehicle::where('status', 'Active')
                ->where(function ($q) use ($today) {
                    $q->where(function ($sub) use ($today) {
                        $sub->whereNull('last_inspection_date')
                            ->whereDate(DB::raw("DATE_ADD(created_at, INTERVAL inspection_days DAY)"), '<=', $today);
                    })
                    ->orWhere(function ($sub) use ($today) {
                        $sub->whereNotNull('last_inspection_date')
                            ->where(function ($q2) use ($today) {
                                $q2->whereDate(DB::raw("DATE_ADD(last_inspection_date, INTERVAL inspection_days DAY)"), '<=', $today)
                                ->orWhereRaw('starting_km - last_inspection_km >= inspection_km');
                            });
                    });
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('trips')
                        ->whereColumn('trips.vehicle_id', 'vehicles.id')
                        ->whereIn('trips.status', ['Scheduled', 'In Progress', 'On Hold']);
                })
                ->count();

            
            $preTripInspections = Trip::where('status', 'Scheduled')->count();

            
            $postTripInspections = Trip::where('status', 'Completed')->count();

            
            $postServiceInspections = VehicleInspection::where('inspection_type', 'Post Service')->count();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dashboard details retrieved successfully',
                'data' => [
                    'regular_inspection_count'     => $regularInspections,
                    'pre_trip_inspection_count'    => $preTripInspections,
                    'post_trip_inspection_count'   => $postTripInspections,
                    'post_service_inspection_count'=> $postServiceInspections,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
   
    public function completeInspectionList()
    {
        try {
            $inspections = VehicleInspection::with('vehicle.vehicleType')
                ->where('inspection_type', 'Post Service')
                ->orderBy('inspection_date', 'desc')
                ->get();

            $data = $inspections->map(function($inspection) {
                $previousInspection = VehicleInspection::where('vehicle_id', $inspection->vehicle_id)
                    ->where('id', '<', $inspection->id) 
                    ->orderBy('inspection_date', 'desc')
                    ->first();

                return [
                    'inspection_id'        => $inspection->id,
                    'inspection_type'      => $inspection->inspection_type,
                    'inspection_date'      => $inspection->inspection_date->format('d/m/Y'),
                    'vehicle_id'           => $inspection->vehicle_id,
                    'vehicle_number'       => $inspection->vehicle->vehicle_no ?? null,
                    'vehicle_type'         => $inspection->vehicle->vehicleType->vehicle_type_name ?? null,
                    'previous_inspection'  => $previousInspection->inspection_type ?? null,
                    'previous_date'        => $previousInspection?->inspection_date?->format('d/m/Y')
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function viewCompleteInspection($vehicleId)
    {
        try {
            if (empty($vehicleId)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Vehicle ID not provided',
                    'data' => null
                ]);
            }
    
            $vehicle = Vehicle::with('vehicleType')->find($vehicleId);
    
            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Vehicle not found',
                    'data' => null
                ]);
            }
    
            $inspection = VehicleInspection::with(['tyres', 'photos', 'maintenance'])
                ->where('vehicle_id', $vehicleId)
                ->latest('inspection_date')
                ->first();
    
            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No inspection found for this vehicle',
                    'data' => null
                ]);
            }
    
            $inspectionDetails = [
                'inspection_type'     => $inspection->inspection_type,
                'vehicle_type'        => $vehicle->vehicleType->vehicle_type_name ?? null,
                'vehicle_no'          => $vehicle->vehicle_no,
                'inspected_kilometer' => $inspection->inspection_km,
                'inspected_date'      => $inspection->inspection_date ? $inspection->inspection_date->format('d/m/Y') : null,
                'driver_name'         => $inspection->driver->name ?? null, 
            ];
    
            $maintenanceDetails = $inspection->maintenance ? [
                'supervisor_name'   => $inspection->maintenance->supervisor_name ?? null,
                'mechanic_name'     => $inspection->maintenance->mechanic_name ?? null,
                'service_date'      => $inspection->maintenance->service_date 
                                            ? \Carbon\Carbon::parse($inspection->maintenance->service_date)->format('d/m/Y') 
                                            : null,
                'service_kilometer' => $inspection->maintenance->service_kilometer ?? null,
            ] : null;
    
            $tyreCondition = $inspection->tyres->map(function ($tyre) {
                return [
                    'tyre_type'     => $tyre->tyre_type,
                    'tyre_category' => $tyre->tyre_category,
                    'tyre_condition'=> $tyre->tyre_condition,
                ];
            });
    
            $vehicleCondition = [
                'engine_oil_level'   => $inspection->engine_oil_level,
                'coolant_level'      => $inspection->coolant_level,
                'vehicle_greasing'   => $inspection->vehicle_greasing,
                'vehicle_washing'    => $inspection->vehicle_washing,
                'mirror_condition'   => $inspection->mirror_condition,
                'indicator_condition'=> $inspection->indicator_condition,
                'battery_condition'  => $inspection->battery_condition,
                'mudflap_condition'  => $inspection->mudflap_condition,
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Complete inspection details retrieved successfully',
                'data' => [
                    'inspection_details'  => $inspectionDetails,
                    'maintenance_details' => $maintenanceDetails,
                    'tyre_condition'      => $tyreCondition->isEmpty() ? null : $tyreCondition,
                    'vehicle_condition'   => $vehicleCondition,
                    'essential_equipment' => $inspection->essential_equipment,
                    'remarks'             => $inspection->remarks,
                ]
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }



}

