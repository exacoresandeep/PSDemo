<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceImage;
use App\Models\Assistance;
use App\Models\AssistanceType;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\TyreType;
use App\Models\TyreCategory;
use App\Models\JobCard;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    public function addMaintenance(Request $request, $assistanceId)
    {
        $request->validate([
            'maintenance_type' => 'required|in:Internal,External',
            'remarks' => 'nullable|string',
            'employee_name' => 'nullable|string',
            'phone_number' => 'nullable|string'
        ]);

        $report = MaintenanceReport::create([
            'assistance_id' => $assistanceId,
            'maintenance_type' => $request->maintenance_type,
            'remarks' => $request->remarks,
            'employee_name' => $request->maintenance_type === 'Internal' ? $request->employee_name : null,
            'phone_number' => $request->maintenance_type === 'Internal' ? $request->phone_number : null,
            'status' => $request->maintenance_type === 'Internal' ? 'Pending' : null,
        ]);
        $assistance = Assistance::find($assistanceId);
        if ($assistance && $assistance->trip_id) {
            Trip::where('id', $assistance->trip_id)->update([
                'status' => 'On Hold'
            ]);
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Maintenance report created successfully.',
            'data' => $report
        ], 200);
    }
    public function updateMaintenanceStatus(Request $request, $maintenanceId)
    {
        $report = MaintenanceReport::find($maintenanceId);

        if (!$report) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Maintenance report not found.',
                'data' => []
            ], 404);
        }

        if ($report->maintenance_type === 'Internal') {
            $request->validate([
                'status' => 'required|in:Pending,Completed',
                'Notification_status' => $request->status === 'Completed' ? 'pending' : $report->notification_status
            ]);

            $report->update(['status' => $request->status]);

            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Internal maintenance status updated successfully.',
                'data' => $report
            ]);
        }

        if ($report->maintenance_type === 'External') {
            // External: Upload images
            $request->validate([
                'images' => 'required|array',
                'images.*' => 'string'
            ]);

            foreach ($request->images as $img) {
                MaintenanceImage::create([
                    'maintenance_id' => $maintenanceId,
                    'image' => $img
                ]);
            }
            $report->update([
                'status' => 'Completed',
                'notification_status' => 'pending'
            ]);
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'External maintenance images uploaded successfully.',
                'data' => $report
            ], 200);
        }

        return response()->json([
            'status' => false,
            'statusCode' => 400,
            'message' => 'Invalid maintenance type.',
            'data' => []
        ], 400);
    }
    public function getMaintenanceContact()
    {
        $contact = \App\Models\MaintenanceContact::first();

        if (!$contact) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Maintenance contact not found.',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Maintenance contact fetched successfully.',
            'data' => [
                'phone_number' => $contact->phone_number
            ]
        ], 200);
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
    public function login(Request $request)
    {
        
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
                'type' => 'required|string|in:Maintenance',
            ]);

	    $user = Employee::where('employee_code', $validated['username'])
		    ->where('employee_type_id', '9')
            ->first();
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid credentials',
                ], 400);
            }

            $token = $user->createToken('Maintenance API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'employee_code' => $user->employee_code,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'designation' => $user->designation,
                        'password_reset_flag'=>$user->password_reset_flag == 0 ? false : true,
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

    public function getEmployeeProfile()
    {
        // dd('sfdsfh');
        try {
            $user = Auth::user(); // or auth()->user()

            if (!$user) {
                return response()->json([
                    'success'    => false,
                    'statusCode' => 401,
                    'message'    => 'Unauthorized',
                ], 401);
            }

            return response()->json([
                'success'    => true,
                'statusCode' => 200,
                'message'    => 'Employee profile fetched successfully',
                'data'       => [
                    'id'                 => $user->id,
                    'employee_code'      => $user->employee_code,
                    'name'               => $user->name,
                    'phone'              => $user->phone,
                    'address'            => $user->address,
                    'designation'        => $user->designation,
                    'email'              => $user->email,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'statusCode' => 500,
                'message'    => 'Something went wrong',
                'error'      => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => false,
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.'
                ], 400);
            }

            $fcmToken = trim($request->fcm_token);

            // Update only for Maintenance employees
            Employee::where("id", $user->id)
                ->where("employee_type_id", 8)
                ->update(["fcm_token" => $fcmToken]);

            return response()->json([
                'status'     => true,
                'statusCode' => 200,
                'message'    => 'FCM token updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => false,
                'statusCode' => 400,
                'message'    => 'Something went wrong.'
            ], 400);
        }
    }


    public function fileUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|array',
            'file.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', 
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => $validator->errors(),
                'data' => [],
            ], 422);
        }
    
        $fileUrls = [];
        foreach ($request->file('file') as $file) {
            $fileName = $file->hashName();

            $file->storeAs('uploads', $fileName, 'public');  

            $fileUrls[] = url('storage/uploads/' . $fileName);
        }
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Files uploaded successfully.',
            'data' => ['filePaths' => $fileUrls],
        ], 200);
    }

    public function notificationList()
    {
        return response()->json(['message' => 'Notifications list']);
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.'
                ], 400);
            }

            // Revoke the token that was used to authenticate the current request
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Logout successful.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.'
            ], 400);
        }
    }

    // public function pendingAssistanceRequests()
    // {
    //     try {
    //         $requests = Assistance::with([
    //             'type:id,name',
    //             'trip' => function ($q) {
    //                 $q->select('id', 'trip_code', 'vehicle_id', 'driver_id')
    //                   ->with([
    //                       'vehicle:id,vehicle_no',
    //                       'driver:id,name'
    //                   ]);
    //             }
    //         ])
    //         ->select(
    //             'id',
    //             'trip_id',
    //             'assistance_type_id',
    //             'remarks',
    //             'support_date',
    //             'expiry_date',
    //             'close_date',
    //             'lat',
    //             'lon'
    //         )
    //         ->whereNull('close_date')
    //         ->whereNotNull('expiry_date')
    //         ->get()
    //         ->map(function ($item) {
    //             $today = now()->startOfDay();
    //             $expiry = \Carbon\Carbon::parse($item->expiry_date)->startOfDay();
    
    //             $item->status = $expiry->gte($today) ? 'inprogress' : 'due';
    
    //             return $item;
    //         });
    
    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'message'    => 'Pending assistance requests fetched successfully.',
    //             'data'       => $requests
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 400,
    //             'message'    => 'Something went wrong.'
    //         ], 400);
    //     }
    // }
    public function pendingAssistanceRequests()
    {
        try {
            $requests = Assistance::with([
                'type:id,name',
                'trip' => function ($q) {
                    $q->select('id', 'trip_code', 'vehicle_id', 'driver_id')
                    ->with([
                        'vehicle:id,vehicle_no',
                        'driver:id,name'
                    ]);
                }
            ])
            ->select(
                'id',
                'trip_id',
                'assistance_type_id',
                'remarks',
                'support_date',
                'expiry_date',
                'close_date',
                'lat',
                'lon',
                'status',
                'created_at'
            )
            ->whereNull('close_date')
            ->get()
            ->map(function ($item) {
                $today = now()->startOfDay();

                // make sure support_date is Carbon before using diff
                $supportDate = $item->support_date 
                    ? \Carbon\Carbon::parse($item->support_date)->startOfDay() 
                    : null;
                // Custom return fields
                return [
                    'assistance_id'   => $item->id,
                    'trip_id'         => $item->trip_id,
                    'vehicle_number'  => $item->trip?->vehicle?->vehicle_no,
                    'reported_by'     => $item->trip?->driver?->name,
                    'reported_date'   => $item->created_at->format('d/m/Y'),
                    'issue_type'      => $item->type?->name,
                    'vehicle_location'=> $item->trip_id ? 'On Trip' : 'N/A',
                    'status'          => $item->status,
                    'pending_days' =>  $supportDate 
                                        ? $supportDate->diffInDays($today) 
                                        : 0,
                    // 'remarks'         => $item->remarks,
                    // 'support_date'    => optional($item->support_date)->format('d/m/Y'),
                    // 'expiry_date'     => optional($item->expiry_date)->format('d/m/Y'),
                ];
            });

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Pending assistance requests fetched successfully.',
                'data'       => $requests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'error'      => $e->getMessage()
            ], 400);
        }
    }
    public function completedAssistanceRequests()
    {
        try {
            $requests = Assistance::with([
                'type:id,name',
                'trip' => function ($q) {
                    $q->select('id', 'trip_code', 'vehicle_id', 'driver_id')
                    ->with([
                        'vehicle:id,vehicle_no',
                        'driver:id,name'
                    ]);
                }
            ])
            ->select(
                'id',
                'trip_id',
                'assistance_type_id',
                'remarks',
                'support_date',
                'expiry_date',
                'close_date',
                'lat',
                'lon',
                'status',
                'created_at'
            )
            ->whereNotNull('close_date')
            ->get()
            ->map(function ($item) {
                $today = now()->startOfDay();

                // Parse support_date safely
                $supportDate = $item->support_date 
                    ? \Carbon\Carbon::parse($item->support_date)->startOfDay() 
                    : null;

                return [
                    'assistance_id'    => $item->id,
                    'trip_id'          => $item->trip_id,
                    'vehicle_number'   => $item->trip?->vehicle?->vehicle_no,
                    'reported_by'      => $item->trip?->driver?->name,
                    'reported_date'    => $item->created_at->format('d/m/Y'),
                    'issue_type'       => $item->type?->name,
                    'vehicle_location' => $item->trip_id ? 'On Trip' : 'N/A',
                    'status'           => 'Completed', // explicitly mark as Completed
                    'pending_days'     => $supportDate 
                                            ? $supportDate->diffInDays($today) 
                                            : 0,
                    // 'remarks'          => $item->remarks,
                    // 'support_date'     => $supportDate ? $supportDate->format('d/m/Y') : null,
                    // 'expiry_date'      => $item->expiry_date 
                    //                         ? \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') 
                    //                         : null,
                ];
            });

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Completed assistance requests fetched successfully.',
                'data'       => $requests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'error'      => $e->getMessage()
            ], 400);
        }
    }
    public function viewAssistanceRequests($assistanceId)
    {
        try {
            $assistance = Assistance::with([
                'type:id,name',
                'trip' => function ($q) {
                    $q->select('id', 'trip_code', 'vehicle_id', 'driver_id')
                    ->with([
                        'vehicle:id,vehicle_no',
                        'driver:id,name,phone'
                    ]);
                },
                'images:id,assistance_id,image_path',
                'maintenanceReports.jobCard' 
            ])->findOrFail($assistanceId);

            $reportedDate = $assistance->created_at?->format('d/m/Y');
            $reportedTime = $assistance->created_at?->format('H:i:s');

            $vehicleLocation = $assistance->trip_id ? 'On Trip' : 'N/A';

            $address = $assistance->lat && $assistance->lon
                ? $this->getAddressFromLatLon($assistance->lat, $assistance->lon)
                : null;

            $complaintDetails = [
                'assistance_type' => $assistance->type?->name,
                'remarks'         => $assistance->remarks,
                'images'          => $assistance->images->pluck('image_path')
            ];

            $report = $assistance->maintenanceReports->first(); // Get first report or null

            $maintenanceReports = $report ? [
                'maintenance_id'   => $report->id,
                'maintenance_type' => $report->maintenance_type,
                'employee_name'    => $report->employee_name,
                'phone_number'     => $report->phone_number,
                'remarks'          => $report->remarks,
                'status'           => $report->status,
                'job_card'         => $report->jobCard ? [
                    'service_date'      => $report->jobCard->service_date,
                    'current_kilometer' => $report->jobCard->current_kilometer,
                    'job_details'       => $report->jobCard->job_details ?? [],
                    'bill_file'         => $report->jobCard->bill_file,
                    'cost'              => (float) $report->jobCard->cost,
                    'total_cost'        => (float) $report->jobCard->total_cost,
                    'labour_cost'       => (float) $report->jobCard->labour_cost,
                    'spare_cost'        => (float) $report->jobCard->spare_cost,
                ] : null,
            ] : null;

            $response = [
                'assistance_id'      => $assistance->id,
                'reported_date'      => $reportedDate,
                'reported_time'      => $reportedTime,
                'vehicle_number'     => $assistance->trip?->vehicle?->vehicle_no,
                'vehicle_location'   => $vehicleLocation,
                'trip_id'            => $assistance->trip_id,
                'latitude'           => $assistance->lat,
                'longitude'          => $assistance->lon,
                'reported_by'        => $assistance->trip?->driver?->name,
                'driver_phone'       => $assistance->trip?->driver?->phone,
                'address'            => $address,
                'complaint_details'  => $complaintDetails,
                'maintenance_reports'=> $maintenanceReports,
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Assistance request details fetched successfully.',
                'data'       => $response
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 404,
                'message'    => 'Assistance request not found.',
                'error'      => $e->getMessage()
            ], 404);
        }
    }

    private function getAddressFromLatLon($lat, $lon)
    {
        try {
            $googleApiKey = env('GOOGLE_MAPS_API_KEY');
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lon}&key={$googleApiKey}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            return $data['results'][0]['formatted_address'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
    public function updateAssistanceDetails(Request $request, $assistanceId)
    {
        $request->validate([
            'maintenance_type' => 'required|string',
            'employee_name'    => 'required|string',
            'phone_number'     => 'required|string',
            'remarks'          => 'nullable|string',
        ]);

        try {
            $assistance = Assistance::findOrFail($assistanceId);

            $maintenanceReport = $assistance->maintenanceReports()->create([
                'assistance_id'    => $assistanceId,
                'maintenance_type' => $request->maintenance_type,
                'employee_name'    => $request->employee_name,
                'phone_number'     => $request->phone_number,
                'remarks'          => $request->remarks,
                'status'           => 'Pending',
            ]);

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Maintenance report added successfully.',
                'data'       => $maintenanceReport
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Failed to add maintenance report.',
                'error'      => $e->getMessage()
            ], 400);
        }
    }
    public function updateJobCard(Request $request, $maintenanceReportId)
    {
        try {
            $report = MaintenanceReport::findOrFail($maintenanceReportId);

            // Validation
            if ($report->maintenance_type === 'Internal') {
                $request->validate([
                    'service_date'      => 'required|date',
                    'current_kilometer' => 'required|integer',
                    'job_details'       => 'required|array',
                    'job_details.*.complaints'   => 'nullable|string',
                    'job_details.*.parts_needed' => 'required|string|in:Yes,No',
                    'job_details.*.parts.name'   => 'required|string',
                    'job_details.*.parts.quantity' => 'required|integer',
                    'job_details.*.parts.stock_status' => 'required|string',
                    'job_details.*.instructions' => 'nullable|string',
                    'bill_file'         => 'nullable|file|mimes:jpg,png,pdf',
                    'cost'              => 'nullable|numeric',
                    'total_cost'        => 'nullable|numeric',
                ]);

                // Prepare data
                $data = [
                    'service_date'      => $request->service_date,
                    'current_kilometer' => $request->current_kilometer,
                    'job_details'       => $request->job_details,
                    'cost'              => $request->cost,
                    'total_cost'        => $request->total_cost,
                ];

            } else { // External maintenance
                $request->validate([
                    'bill_file'  => 'nullable|file|mimes:jpg,png,pdf',
                    'labour_cost'=> 'required|numeric',
                    'spare_cost' => 'required|numeric',
                    'total_cost' => 'required|numeric',
                ]);

                $data = [
                    'labour_cost' => $request->labour_cost,
                    'spare_cost'  => $request->spare_cost,
                    'total_cost'  => $request->total_cost,
                ];
            }

            // Handle file upload
            if ($request->hasFile('bill_file')) {
                $data['bill_file'] = $request->file('bill_file')->store('jobcards', 'public');
            }

            // Update or create job card
            $jobCard = $report->jobCard()->updateOrCreate([], $data);

            // Update report status
            $report->status = 'Completed';
            $report->save();

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Maintenance and job card updated successfully.',
                'data'       => $jobCard
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Failed to update maintenance/job card.',
                'error'      => $e->getMessage()
            ], 400);
        }
    }


    // public function updateJobCard(Request $request, $maintenanceReportId)
    // {
       
    //     $request->validate([
    //         'service_date'      => 'required|date',
    //         'current_kilometer' => 'required|integer',
    //         'complaints'         => 'nullable|string',  // single value
    //         'parts_needed'      => 'required|string|in:Yes,No', // Yes/No
    //         'parts'             => 'nullable|array',
    //         'parts.*.name'      => 'required_with:parts|string',
    //         'parts.*.quantity'  => 'required_with:parts|integer',
    //         'parts.*.stock_status' => 'required_with:parts|string',
    //         'instructions'      => 'nullable|string',
    //         'bill_file'         => 'nullable|file|mimes:jpg,png,pdf',
    //         'cost'              => 'nullable|numeric',
    //         'total_cost'        => 'nullable|numeric',
    //     ]);
    //     try {
    //         $report = MaintenanceReport::findOrFail($maintenanceReportId);

    //         $data = $request->all();

    //         if ($request->hasFile('bill_file')) {
    //             $path = $request->file('bill_file')->store('jobcards', 'public');
    //             $data['bill_file'] = $path;
    //         }

    //         $jobCard = $report->jobCard()->updateOrCreate([], $data);

    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'message'    => 'Job card updated successfully.',
    //             'data'       => $jobCard
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 400,
    //             'message'    => 'Failed to update job card.',
    //             'error'      => $e->getMessage()
    //         ], 400);
    //     }
    // }


    // public function completedAssistanceRequests()
    // {
    //     try {
    //         $requests = Assistance::with([
    //             'type:id,name',
    //             'trip' => function ($q) {
    //                 $q->select('id', 'trip_code', 'vehicle_id', 'driver_id')
    //                 ->with([
    //                     'vehicle:id,vehicle_no',
    //                     'driver:id,name'
    //                 ]);
    //             }
    //         ])
    //         ->select(
    //             'id',
    //             'trip_id',
    //             'assistance_type_id',
    //             'remarks',
    //             'support_date',
    //             'expiry_date',
    //             'close_date',
    //             'lat',
    //             'lon'
    //         )
    //         ->whereNotNull('close_date')
    //         ->get()
    //         ->map(function ($item) {
    //             $item->status = 'completed';
    //             return $item;
    //         });

    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'message'    => 'Completed assistance requests fetched successfully.',
    //             'data'       => $requests
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 400,
    //             'message'    => 'Something went wrong.'
    //         ], 400);
    //     }
    // }


    // public function viewAssistanceRequests($id = null)
    // {
    //     try {
    //         $query = Assistance::with([
    //             'type:id,name',
    //             'trip.vehicle:id,vehicle_no',
    //             'trip.driver:id,name,phone'
    //         ])->where(function ($q) {
    //             $today = now()->startOfDay();
    //             $q->whereNotNull('close_date') // completed
    //             ->orWhere(function ($sub) use ($today) {
    //                 $sub->whereNull('close_date')
    //                     ->where('expiry_date', '>=', $today); // pending
    //             });
    //         });

    //         if ($id) {
    //             $requests = $query->where('id', $id)->get();
    //         } else {
    //             $requests = $query->get();
    //         }

    //         $requests = $requests->map(function ($item) {
    //             if ($item->close_date) {
    //                 $item->status = 'Completed';
    //             } else {
    //                 $item->status = 'Pending';
    //             }

    //             // full image URL
    //             $item->image = $item->image ? url($item->image) : null;
    //             $item->reported_date = $item->support_date ? \Carbon\Carbon::parse($item->support_date)->format('Y-m-d') : null;
    //             $item->reported_time = $item->support_date ? \Carbon\Carbon::parse($item->support_date)->format('h:i a') : null;

    //             // optionally remove support_date if you don't want it
    //             unset($item->support_date);
    //             return $item;
    //         });

    //         if ($id && $requests->isEmpty()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Assistance request not found'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'statusCode' => 200,
    //             'data' => $id ? $requests->first() : $requests
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function updateAssistaceDetails(Request $request) { return response()->json(['message' => 'Update assistance details']); }
    public function completeAssistace(Request $request)      { return response()->json(['message' => 'Complete assistance']); }

    public function maintanceType()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            $types = [
                ['id' => 1, 'name' => 'Internal'],
                ['id' => 2, 'name' => 'External'],
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Maintenance types fetched successfully.',
                'data'       => $types
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    public function stockStatus()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            $statuses = [
                ['id' => 1, 'name' => 'In Stock'],
                ['id' => 2, 'name' => 'Out of Stock'],
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Stock statuses fetched successfully.',
                'data'       => $statuses
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    public function getTyreType()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            $types = [
                ['id' => 1, 'name' => 'New Tyre'],
                ['id' => 2, 'name' => 'Resoled Tyre'],
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Tyre types fetched successfully.',
                'data'       => $types
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    // ðŸš€ Complaints / Employees
    public function complaintsList()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            // Static complaints list for lorries
            $complaints = [
                ['id' => 1, 'name' => 'Engine Issue'],
                ['id' => 2, 'name' => 'Brake Problem'],
                ['id' => 3, 'name' => 'Tyre Puncture'],
                ['id' => 4, 'name' => 'Oil Leakage'],
                ['id' => 5, 'name' => 'Electrical Issue'],
                ['id' => 6, 'name' => 'Suspension Problem'],
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Complaints list fetched successfully.',
                'data'       => $complaints
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    public function getMaintenanceMasterData()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            $data = [
                'maintenance_types' => [
                    ['id' => 1, 'name' => 'Internal'],
                    ['id' => 2, 'name' => 'External'],
                ],
                'stock_statuses' => [
                    ['id' => 1, 'name' => 'In Stock'],
                    ['id' => 2, 'name' => 'Out of Stock'],
                ],
                'tyre_types' => [
                    ['id' => 1, 'name' => 'New Tyre'],
                    ['id' => 2, 'name' => 'Resoled Tyre'],
                ],
                'complaints' => [
                    ['id' => 1, 'name' => 'Engine Issue'],
                    ['id' => 2, 'name' => 'Brake Problem'],
                    ['id' => 3, 'name' => 'Tyre Puncture'],
                    ['id' => 4, 'name' => 'Oil Leakage'],
                    ['id' => 5, 'name' => 'Electrical Issue'],
                    ['id' => 6, 'name' => 'Suspension Problem'],
                ]
            ];

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Master data fetched successfully.',
                'data'       => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    public function employeeList()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'     => 'error',
                    'statusCode' => 400,
                    'message'    => 'User not authenticated.',
                    'data'       => []
                ], 400);
            }

            $employees = Employee::where('employee_type_id', 8)
                ->select('id', 'name', 'employee_code', 'phone')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Employee list fetched successfully.',
                'data'       => $employees
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Something went wrong.',
                'data'       => []
            ], 400);
        }
    }

    // ðŸš€ Vehicle Services
    // public function createService(Request $request)     { return response()->json(['message' => 'Service created']); }
    // public function completeService(Request $request)   { return response()->json(['message' => 'Service completed']); }
    // public function getServiceAlerts()                  { return response()->json(['message' => 'Service alerts']); }
    // public function getServiceRecords()                 { return response()->json(['message' => 'Service records']); }
    // public function updateServiceAlerts(Request $request)   { return response()->json(['message' => 'Service alert updated']); }
    // public function completeServiceAlerts(Request $request) { return response()->json(['message' => 'Service alert completed']); }
    // public function viewServiceAlertDetials()           { return response()->json(['message' => 'Service alert details']); }
    // public function viewServiceRecordDetails()          { return response()->json(['message' => 'Service record details']); }
    public function viewServiceRecordHistory()          { return response()->json(['message' => 'Service record history']); }

    // ðŸš€ Tyre Management
    public function tyreManagmentList()          { return response()->json(['message' => 'Tyre management list']); }
    

    public function requestTyreChange(Request $request) { return response()->json(['message' => 'Tyre change requested']); }
    public function viewTyreChangeRequest()      { return response()->json(['message' => 'Tyre change request details']); }

    // ðŸš€ Inspected Services
    // public function openInspectedServiceList()         { return response()->json(['message' => 'Open inspected services']); }
    // public function viewOpenInspectedServiceList()     { return response()->json(['message' => 'View open inspected service']); }
    // public function closedInspectedServiceList()       { return response()->json(['message' => 'Closed inspected services']); }
    // public function viewClosedInspectedServiceList()   { return response()->json(['message' => 'View closed inspected service']); }
    public function updateInspectedService(Request $request) { return response()->json(['message' => 'Update inspected service']); }

    // ðŸš€ Stock Insights
    public function getStockInsightList()     { return response()->json(['message' => 'Stock insights list']); }
    public function viewStockInsightDetails() { return response()->json(['message' => 'Stock insight details']); }
    
    public function getServiceTypes()
    {
        try {
            $user = Auth::user();
            $data = $user ? \App\Models\ServiceType::select('id as id', 'name')
                        ->where('status', 1)
                        ->orderBy('name')
                        ->get() : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Service types fetched successfully',
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
    public function openInspectedServiceLists()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $inspections = \App\Models\VehicleInspection::query()
                ->join('vehicles', 'vehicle_inspections.vehicle_id', '=', 'vehicles.id')
                ->join('vehicle_type', 'vehicles.vehicle_type_id', '=', 'vehicle_type.id')
                ->where('vehicle_inspections.status', 'Opened')
                ->orderBy('vehicle_inspections.inspection_date', 'desc')
                ->get([
                    'vehicle_inspections.id as inspection_id',
                    'vehicle_inspections.status',
                    'vehicle_inspections.inspection_type',
                    'vehicle_inspections.inspection_date',
                    'vehicle_type.vehicle_type_name as vehicle_type',
                    'vehicles.vehicle_no as vehicle_no'
                ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Open inspections fetched successfully',
                'data' => $inspections
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function viewOpenInspectedServiceLists($inspectionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Fetch inspection with vehicle and vehicle type
            $inspection = \App\Models\VehicleInspection::with(['vehicle.vehicleType'])
                ->where('id', $inspectionId)
                ->where('status', 'Opened')
                ->first([
                    'id as inspection_id',
                    'inspection_type',
                    'inspection_km',
                    'inspection_date',
                    'vehicle_id',
                    'engine_oil_level',
                    'coolant_level',
                    'clutch_fluid',
                    'vehicle_greasing',
                    'vehicle_washing',
                    'mirror_condition',
                    'indicator_condition',
                    'battery_condition',
                    'mudflap_condition',
                    'essential_equipment',
                    'remarks'
                ]);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Inspection not found or already closed.'
                ], 404);
            }

            // Fetch tyres linked to this inspection
            $tyres = \App\Models\InspectionTyre::where('inspection_id', $inspectionId)
                ->get([
                    'axle_type',
                    'tyre_type',
                    'tyre_category',
                    'tyre_condition'
                ]);

            // Build response
            $response = [
                'inspection_id' => $inspection->inspection_id,
                'inspection_type' => $inspection->inspection_type,
                'vehicle_type' => $inspection->vehicle->vehicleType->vehicle_type_name ?? null,
                'vehicle_number' => $inspection->vehicle->vehicle_no ?? null,
                'inspection_km' => $inspection->inspection_km,
                'inspection_date' => $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('d/m/Y') : null,
                'tyres' => $tyres,
                'vehicle_conditions' => [
                    'engine_oil_level' => $inspection->engine_oil_level,
                    'coolant_level' => $inspection->coolant_level,
                    'clutch_fluid' => $inspection->clutch_fluid,
                    'vehicle_greasing' => $inspection->vehicle_greasing,
                    'vehicle_washing' => $inspection->vehicle_washing,
                    'mirror_condition' => $inspection->mirror_condition,
                    'indicator_condition' => $inspection->indicator_condition,
                    'battery_condition' => $inspection->battery_condition,
                    'mudflap_condition' => $inspection->mudflap_condition,
                ],
                'essential_equipment' => $inspection->essential_equipment,
                'remarks' => $inspection->remarks
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Inspection details fetched successfully',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function updateInspectedServices(Request $request, $inspectionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Validate request
            $request->validate([
                'supervisor_name' => 'required|string|max:255',
                'mechanic_name' => 'required|string|max:255',
                'service_date' => 'required|date',
                'service_kilometer' => 'required|integer',
                'service_remarks' => 'nullable|string'
            ]);

            // Check if record exists for this inspection
            $maintenance = \App\Models\VehicleServiceMaintenance::where('inspection_id', $inspectionId)->first();

            if (!$maintenance) {
                // If not exists, create new
                $maintenance = new \App\Models\VehicleServiceMaintenance();
                $maintenance->inspection_id = $inspectionId;
            }

            // Update maintenance details
            $maintenance->supervisor_name = $request->supervisor_name;
            $maintenance->mechanic_name = $request->mechanic_name;
            $maintenance->service_date = $request->service_date;
            $maintenance->service_kilometer = $request->service_kilometer;
            $maintenance->service_remarks = $request->service_remarks;
            $maintenance->save();

            $inspection = \App\Models\VehicleInspection::find($inspectionId);
            if ($inspection) {
                $inspection->status = 'Closed';
                $inspection->save();
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Maintenance details updated successfully',
                'data' => $maintenance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function closedInspectedServiceLists()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $inspections = \App\Models\VehicleInspection::query()
                ->join('vehicles', 'vehicle_inspections.vehicle_id', '=', 'vehicles.id')
                ->join('vehicle_type', 'vehicles.vehicle_type_id', '=', 'vehicle_type.id')
                ->where('vehicle_inspections.status', 'Closed')
                ->orderBy('vehicle_inspections.inspection_date', 'desc')
                ->get([
                    'vehicle_inspections.id as inspection_id',
                    'vehicle_inspections.status',
                    'vehicle_inspections.inspection_type',
                    'vehicle_inspections.inspection_date',
                    'vehicle_type.vehicle_type_name as vehicle_type',
                    'vehicles.vehicle_no as vehicle_no'
                ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Closed inspections fetched successfully',
                'data' => $inspections
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function viewClosedInspectedServiceLists($inspectionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Fetch inspection with vehicle and vehicle type
            $inspection = \App\Models\VehicleInspection::with(['vehicle.vehicleType', 'maintenance'])
                ->where('id', $inspectionId)
                ->where('status', 'Closed')
                ->first([
                    'id as inspection_id',
                    'inspection_type',
                    'inspection_km',
                    'inspection_date',
                    'vehicle_id',
                    'engine_oil_level',
                    'coolant_level',
                    'clutch_fluid',
                    'vehicle_greasing',
                    'vehicle_washing',
                    'mirror_condition',
                    'indicator_condition',
                    'battery_condition',
                    'mudflap_condition',
                    'essential_equipment',
                    'remarks'
                ]);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Inspection not found or not closed yet.'
                ], 404);
            }

            // Fetch tyres
            $tyres = \App\Models\InspectionTyre::where('inspection_id', $inspectionId)
                ->get([
                    'axle_type',
                    'tyre_type',
                    'tyre_category',
                    'tyre_condition'
                ]);

            // Fetch maintenance details
            $maintenance = $inspection->maintenance; // assuming relation is defined in VehicleInspection model

            // Build response
            $response = [
                'inspection_id' => $inspection->inspection_id,
                'inspection_type' => $inspection->inspection_type,
                'vehicle_type' => $inspection->vehicle->vehicleType->vehicle_type_name ?? null,
                'vehicle_number' => $inspection->vehicle->vehicle_no ?? null,
                'inspection_km' => $inspection->inspection_km,
                'inspection_date' => $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('d/m/Y') : null,
                'tyres' => $tyres,
                'vehicle_conditions' => [
                    'engine_oil_level' => $inspection->engine_oil_level,
                    'coolant_level' => $inspection->coolant_level,
                    'clutch_fluid' => $inspection->clutch_fluid,
                    'vehicle_greasing' => $inspection->vehicle_greasing,
                    'vehicle_washing' => $inspection->vehicle_washing,
                    'mirror_condition' => $inspection->mirror_condition,
                    'indicator_condition' => $inspection->indicator_condition,
                    'battery_condition' => $inspection->battery_condition,
                    'mudflap_condition' => $inspection->mudflap_condition,
                ],
                'essential_equipment' => $inspection->essential_equipment,
                'remarks' => $inspection->remarks,
                'maintenance_details' => $maintenance ? [
                    'supervisor_name' => $maintenance->supervisor_name,
                    'mechanic_name' => $maintenance->mechanic_name,
                    'service_date' => $maintenance->service_date ? \Carbon\Carbon::parse($maintenance->service_date)->format('d/m/Y') : null,
                    'service_kilometer' => $maintenance->service_kilometer,
                    'service_remarks' => $maintenance->service_remarks
                ] : null
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Closed inspection details fetched successfully',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getComplaintsType()
    {
        try {
            $user = Auth::user();

            $data = $user 
                ? \App\Models\ComplaintsType::select('id', 'name')
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get() 
                : [];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Complaints types fetched successfully',
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
    public function getVehicleDetails(Request $request)
    {
        try {
            $vehicleNo = $request->get('vehicle_no'); // partial or full

            $vehicles = Vehicle::query()
                ->where('vehicle_no', 'LIKE', "%{$vehicleNo}%")
                ->with(['vehicleType:id,vehicle_type_name'])
                ->get()
                ->map(function ($vehicle) {
                    // Check if vehicle has trips with disallowed status
                    $activeTrip = $vehicle->has('trips')
                        ? $vehicle->trips()
                            ->whereIn('status', ['Scheduled', 'In Progress', 'On Hold'])
                            ->first()
                        : null;
                $formatDate = fn($date) => $date ? Carbon::parse($date)->format('d/m/Y') : null;
                    if ($activeTrip) {
                        return [
                            'vehicle_id'   => $vehicle->id,
                            'vehicle_no'   => $vehicle->vehicle_no,
                            'vehicle_code' => $vehicle->vehicle_code,
                            'vehicle_type_id'  => $vehicle->vehicle_type_id,
                            'vehicle_type_name'=> $vehicle->vehicleType?->vehicle_type_name,
                            'last_service_date'  => $formatDate($vehicle->last_service_date),
                            'last_service_km'    => $vehicle->last_service_km,
                            'status'       => 'Not Available',
                            'message'      => 'Vehicle is already in ' . $activeTrip->status
                        ];
                    }

                    // Find last service (MaintenanceReport + JobCard)
                    $lastService = $vehicle->trips()
                        ->with(['assistance.maintenanceReports.jobCard'])
                        ->latest('created_at')
                        ->first();

                    $lastServiceDate = null;
                    $lastServiceKm   = null;

                    if ($lastService && $lastService->assistance) {
                        $jobCard = $lastService->assistance
                            ->maintenanceReports()
                            ->latest()
                            ->first()
                            ?->jobCard;

                        if ($jobCard) {
                            $lastServiceDate = $jobCard->service_date;
                            $lastServiceKm   = $jobCard->current_kilometer;
                        }
                    }

                    return [
                        'vehicle_id'       => $vehicle->id,
                        'vehicle_no'       => $vehicle->vehicle_no,
                        'vehicle_code'     => $vehicle->vehicle_code,
                        'vehicle_type_id'  => $vehicle->vehicle_type_id,
                        'vehicle_type_name'=> $vehicle->vehicleType?->vehicle_type_name,
                        'last_service_date'=> $lastServiceDate,
                        'last_service_km'  => $lastServiceKm,
                        'status'           => 'Available'
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle details fetched successfully',
                'data'    => $vehicles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ]);
        }
    }
    public function createService(Request $request)
    {
        try {
            $request->validate([
                'vehicle_id'   => 'required|exists:vehicles,id',
                'service_type' => 'required|in:Internal,External',
            ]);

            $data = $request->all();

            if ($request->hasFile('bill_file')) {
                $data['bill_file'] = $request->file('bill_file')->store('jobcards', 'public');
            }

            if ($request->service_type === 'Internal') {
                $request->validate([
                    'supervisor_name' => 'required|string',
                    'mechanic_name'   => 'required|string',
                    'service_date'    => 'required|date',
                    'current_kilometer' => 'required|integer',
                    'job_details'     => 'required|array',
                    'job_details.*.complaints'   => 'nullable|string',
                    'job_details.*.parts_needed' => 'required|string|in:Yes,No',
                    'job_details.*.parts.name'   => 'required|string',
                    'job_details.*.parts.quantity' => 'required|integer',
                    'job_details.*.parts.stock_status' => 'required|string',
                    'job_details.*.instructions' => 'nullable|string',
                    'cost'            => 'nullable|numeric',
                    'total_cost'      => 'nullable|numeric',
                ]);
            } else {
                $request->validate([
                    'service_center_name' => 'required|string',
                    'mechanic_name'       => 'required|string',
                    'service_date'        => 'required|date',
                    'current_kilometer'   => 'required|integer',
                    'labour_cost'         => 'required|numeric',
                    'spare_cost'          => 'required|numeric',
                    'total_cost'          => 'required|numeric',
                ]);
            }

            $report = MaintenanceReport::create([
                'vehicle_id'      => $request->vehicle_id,
                'assistance_id'    => null,
                'service_category' => $request->service_type,
                'employee_name'    => $request->service_type === 'Internal' ? $request->supervisor_name : null,
                'service_center_name' => $request->service_type === 'External' ? $request->service_center_name : null,
                'mechanic_name'    => $request->mechanic_name,
                'phone_number'     => $request->phone_number ?? null,
                'status'           => 'In Progress'
            ]);

            $jobCardData = [
                'service_date'      => $request->service_date ?? null,
                'current_kilometer' => $request->current_kilometer ?? null,
                'job_details'       => $request->job_details ?? null,
                'bill_file'         => $data['bill_file'] ?? null,
                'cost'              => $request->cost ?? null,
                'total_cost'        => $request->total_cost ?? null,
                'labour_cost'       => $request->labour_cost ?? null,
                'spare_cost'        => $request->spare_cost ?? null,
            ];

            // $jobCard = $report->jobCard()->create($jobCardData);
            $jobCard = $report->jobCard()->create($jobCardData);

            if ($report->status === 'Completed') {
                $vehicle = Vehicle::find($request->vehicle_id);
                $vehicle->update([
                    'last_service_date' => $request->service_date,
                    'last_service_km'   => $request->current_kilometer,
                ]);
            }
            $responseData = $request->service_type === 'Internal'
                        ? [
                            'report'  => $report,
                            'jobCard' => $jobCard
                        ]
                        : [
                            'vehicle_id'          => $report->vehicle_id,
                            'service_type'        => 'External',
                            'service_center_name' => $report->service_center_name,
                            'mechanic_name'       => $report->mechanic_name,
                            'service_date'        => $jobCard->service_date,
                            'current_kilometer'   => $jobCard->current_kilometer,
                            'bill_file'         => $jobCard->bill_file,
                            'labour_cost'         => $jobCard->labour_cost,
                            'spare_cost'          => $jobCard->spare_cost,
                            'total_cost'          => $jobCard->total_cost,
                        ];
            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Service created successfully',
                'data'       => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Failed to create service',
                'error'      => $e->getMessage()
            ], 400);
        }
    }
    
    // public function getServiceAlertsList()
    // {
    //     try {
    //         $today = now()->startOfDay();
    
    //         $vehicles = Vehicle::with([
    //             'maintenanceReports' => function ($q) {
    //                 $q->where('status', 'In Progress');
    //             },
    //             'inspections' => function ($q) {
    //                 $q->where('status', 'Opened');
    //             }
    //         ])->get();
    
    //         $alerts = $vehicles->map(function ($vehicle) use ($today) {
    //             $serviceAlerts = [];
    
    //             // Maintenance Reports → Regular Service
    //             foreach ($vehicle->maintenanceReports as $report) {
    //                 $serviceDate = $report->created_at
    //                     ? Carbon::parse($report->created_at)->startOfDay()
    //                     : null;
    
    //                 $pendingDays = $serviceDate
    //                     ? $serviceDate->diffInDays($today) // days since due
    //                     : null;
    
    //                 $serviceAlerts[] = [
    //                     'vehicle_id'       => $vehicle->id,
    //                     'vehicle_number'   => $vehicle->vehicle_no,
    //                     'status'           => 'Pending', // always Pending for alerts
    //                     'service_type'     => 'Regular Service',
    //                     'service_due_date' => $serviceDate ? $serviceDate->toDateString() : null,
    //                     'pending_days'     => $pendingDays,
    //                 ];
    //             }
    
    //             // Vehicle Inspections → Pre/Post/Regular Inspection
    //             foreach ($vehicle->inspections as $inspection) {
    //                 $inspectionDate = $inspection->inspection_date
    //                     ? Carbon::parse($inspection->inspection_date)->startOfDay()
    //                     : null;
    
    //                 $pendingDays = $inspectionDate
    //                     ? $inspectionDate->diffInDays($today) // days since due
    //                     : null;
    
    //                 $serviceAlerts[] = [
    //                     'vehicle_id'       => $vehicle->id,
    //                     'vehicle_number'   => $vehicle->vehicle_no,
    //                     'status'           => 'Pending',
    //                     'service_type'     => $inspection->inspection_type . ' Inspection',
    //                     'service_due_date' => $inspectionDate ? $inspectionDate->toDateString() : null,
    //                     'pending_days'     => $pendingDays,
    //                 ];
    //             }
    
    //             return $serviceAlerts;
    //         })->flatten(1);
    
    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'data'       => $alerts,
    //         ]);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 500,
    //             'message'    => $e->getMessage(),
    //             'data'       => []
    //         ]);
    //     }
    // }

    public function getServiceAlertsList()
    {
        try {
            $today = now()->startOfDay();
    
            $vehicles = Vehicle::with([
                'maintenanceReports' => function ($q) {
                    $q->where('status', 'In Progress')
                      ->orderBy('created_at', 'desc')
                      ->limit(1); // fetch latest only
                },
                'inspections' => function ($q) {
                    $q->where('status', 'Opened')
                      ->orderBy('inspection_date', 'desc')
                      ->limit(1); // fetch latest only
                }
            ])->get();
    
            $alerts = $vehicles->map(function ($vehicle) use ($today) {
                $latestAlert = null;
                $latestDate = null;
    
                // Maintenance → Regular Service
                if ($vehicle->maintenanceReports->isNotEmpty()) {
                    $report = $vehicle->maintenanceReports->first();
                    $date = $report->created_at ? Carbon::parse($report->created_at)->startOfDay() : null;
    
                    if ($date && (!$latestDate || $date->gt($latestDate))) {
                        $latestDate = $date;
                        $latestAlert = [
                            'vehicle_id'       => $vehicle->id,
                            'vehicle_number'   => $vehicle->vehicle_no,
                            'status'           => 'Pending',
                            'service_type'     => 'Regular Service',
                            'service_due_date' => $date->toDateString(),
                            'pending_days'     => $date->diffInDays($today),
                        ];
                    }
                }
    
                // Inspection → Pre/Post/Regular
                if ($vehicle->inspections->isNotEmpty()) {
                    $inspection = $vehicle->inspections->first();
                    $date = $inspection->inspection_date ? Carbon::parse($inspection->inspection_date)->startOfDay() : null;
    
                    if ($date && (!$latestDate || $date->gt($latestDate))) {
                        $latestDate = $date;
                        $latestAlert = [
                            'vehicle_id'       => $vehicle->id,
                            'vehicle_number'   => $vehicle->vehicle_no,
                            'status'           => 'Pending',
                            'service_type'     => $inspection->inspection_type . ' Inspection',
                            'service_due_date' => $date->toDateString(),
                            'pending_days'     => $date->diffInDays($today),
                        ];
                    }
                }
    
                return $latestAlert;
            })->filter()->values(); // remove nulls
    
            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'data'       => $alerts,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 500,
                'message'    => $e->getMessage(),
                'data'       => []
            ]);
        }
    }

    public function viewServiceAlertDetails($vehicleId)
    {
        try {
            $vehicle = Vehicle::with(['trips' => function ($q) {
                $q->latest();
            }, 'maintenanceReports.jobCard'])->findOrFail($vehicleId);
    
            $today = now();
            $alerts = [];
    
            $report = $vehicle->maintenanceReports->first();
            $jobCard = $report ? $report->jobCard : null;
            $latestTrip = $vehicle->trips->first();
    
            $alert = [
                'vehicle_id'     => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_no,
                'vehicle_code'   => $vehicle->vehicle_code,
                'service_type'   => null,
                'status'         => 'Completed',
                'maintenance_report' => $report ? [
                    'maintenance_id'   => $report->id,
                    'maintenance_type' => $report->maintenance_type,
                    'employee_name'    => $report->employee_name,
                    'phone_number'     => $report->phone_number,
                    'remarks'          => $report->remarks,
                    'status'           => $report->status,
                    'job_card'         => $jobCard ? [
                        'service_date'      => $jobCard->service_date,
                        'current_kilometer' => $jobCard->current_kilometer,
                        'job_details'       => $jobCard->job_details ?? [],
                        'bill_file'         => $jobCard->bill_file,
                        'cost'              => (float) $jobCard->cost,
                        'total_cost'        => (float) $jobCard->total_cost,
                        'labour_cost'       => (float) $jobCard->labour_cost,
                        'spare_cost'        => (float) $jobCard->spare_cost,
                    ] : null,
                ] : null,
            ];
    
            // 🚩 Priority: Post Trip > Pre Trip > Regular
            if ($latestTrip && $latestTrip->status === 'Completed') {
                $alert['service_type'] = 'Post Trip Service';
                $alert['status'] = 'Pending';
            } elseif ($latestTrip && $latestTrip->status === 'Scheduled') {
                $alert['service_type'] = 'Pre Trip Service';
                $alert['status'] = 'Pending';
            } elseif ($vehicle->last_service_km !== null && $vehicle->inspection_km !== null) {
                $nextKmDue = $vehicle->last_service_km + 1000;
                $pendingKm = $nextKmDue - $vehicle->inspection_km;
    
                $alert['service_type'] = 'Regular Service';
                $alert['status'] = $pendingKm <= 0 ? 'Pending' : 'Completed';
            }
    
            // Only add if a service_type was set
            if ($alert['service_type']) {
                $alerts[] = $alert;
            }
    
            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'data'       => $alerts,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 404,
                'message'    => 'Vehicle not found or error occurred',
                'error'      => $e->getMessage()
            ], 404);
        }
    }

   
 // public function viewServiceAlertDetails($vehicleId)
    // {
    //     try {
    //         $vehicle = Vehicle::with(['trips' => function ($q) {
    //             $q->latest();
    //         }, 'maintenanceReports.jobCard'])->findOrFail($vehicleId);

    //         $today = now();
    //         $alerts = [];

    //         // Get latest maintenance report
    //         $report = $vehicle->maintenanceReports->first(); // or latest() if multiple
    //         $jobCard = $report ? $report->jobCard : null;

    //         // 1. Regular Service Alert
    //         if ($vehicle->last_service_km !== null && $vehicle->inspection_km !== null) {
    //             $nextKmDue = $vehicle->last_service_km + 1000; 
    //             $pendingKm = $nextKmDue - $vehicle->inspection_km;

    //             $alerts[] = [
    //                 'vehicle_id'     => $vehicle->id,
    //                 'vehicle_number' => $vehicle->vehicle_no,
    //                 'vehicle_code'   => $vehicle->vehicle_code,
    //                 'service_type'   => 'Regular Service',
    //                 'status'         => $pendingKm <= 0 ? 'Pending' : 'Completed',
    //                 'maintenance_report' => $report ? [
    //                     'maintenance_id'   => $report->id,
    //                     'maintenance_type' => $report->maintenance_type,
    //                     'employee_name'    => $report->employee_name,
    //                     'phone_number'     => $report->phone_number,
    //                     'remarks'          => $report->remarks,
    //                     'status'           => $report->status,
    //                     'job_card'         => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : null,
    //             ];
    //         }

    //         $latestTrip = $vehicle->trips->first();

    //         // 2. Pre Trip Service Alert
    //         if ($latestTrip && $latestTrip->status === 'Scheduled') {
    //             $alerts[] = [
    //                 'vehicle_id'     => $vehicle->id,
    //                 'vehicle_number' => $vehicle->vehicle_no,
    //                 'vehicle_code'   => $vehicle->vehicle_code,
    //                 'service_type'   => 'Pre Trip Service',
    //                 'status'         => 'Pending',
    //                 'maintenance_report' => $report ? [
    //                     'maintenance_id'   => $report->id,
    //                     'maintenance_type' => $report->maintenance_type,
    //                     'employee_name'    => $report->employee_name,
    //                     'phone_number'     => $report->phone_number,
    //                     'remarks'          => $report->remarks,
    //                     'status'           => $report->status,
    //                     'job_card'         => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : null,
    //             ];
    //         }

    //         // 3. Post Trip Service Alert
    //         if ($latestTrip && $latestTrip->status === 'Completed') {
    //             $alerts[] = [
    //                 'vehicle_id'     => $vehicle->id,
    //                 'vehicle_number' => $vehicle->vehicle_no,
    //                 'vehicle_code'   => $vehicle->vehicle_code,
    //                 'service_type'   => 'Post Trip Service',
    //                 'status'         => 'Pending',
    //                 'maintenance_report' => $report ? [
    //                     'maintenance_id'   => $report->id,
    //                     'maintenance_type' => $report->maintenance_type,
    //                     'employee_name'    => $report->employee_name,
    //                     'phone_number'     => $report->phone_number,
    //                     'remarks'          => $report->remarks,
    //                     'status'           => $report->status,
    //                     'job_card'         => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : null,
    //             ];
    //         }

    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'data'       => $alerts,
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 404,
    //             'message'    => 'Vehicle not found or error occurred',
    //             'error'      => $e->getMessage()
    //         ], 404);
    //     }
    // }
    public function updateServiceAlerts(Request $request)
    {
        try {
            $request->validate([
                'vehicle_id'      => 'required|exists:vehicles,id',
                'service_type'    => 'required|in:Internal,External',
                'supervisor_name' => 'nullable|string',
                'mechanic_name'   => 'nullable|string',
                'service_date'    => 'required|date',
                'current_kilometer' => 'required|integer',
                'job_details'     => 'required_if:service_type,Internal|array',
                'job_details.*.complaints'   => 'nullable|string',
                'job_details.*.parts_needed' => 'required_if:service_type,Internal|string|in:Yes,No',
                'job_details.*.parts.name'   => 'required_if:service_type,Internal|string',
                'job_details.*.parts.quantity' => 'required_if:service_type,Internal|integer',
                'job_details.*.parts.stock_status' => 'required_if:service_type,Internal|string|in:In Stock,Out Of Stock',
                'job_details.*.instructions' => 'nullable|string',
                'bill_file'       => 'nullable|file|mimes:jpg,png,pdf,docx',
                'cost'            => 'nullable|numeric',
                'total_cost'      => 'nullable|numeric',
                'labour_cost'     => 'nullable|numeric',
                'spare_cost'      => 'nullable|numeric',
                'next_service_km' => 'nullable|integer',
                'due_days'        => 'nullable|integer',
                'service_center_name' => 'required_if:service_type,External|string',
                'service_center_phone' => 'required_if:service_type,External|string',
            ]);

            $billFilePath = $request->hasFile('bill_file') 
                            ? $request->file('bill_file')->store('jobcards', 'public') 
                            : null;

            // Create Maintenance Report
            $report = MaintenanceReport::create([
                'vehicle_id'      => $request->vehicle_id,
                'assistance_id'    => null,
                'service_category' => $request->service_type,
                'employee_name'    => $request->service_type === 'Internal' ? $request->supervisor_name : null,
                
                'service_center_name' => $request->service_type === 'External' ? $request->service_center_name : null,
                'mechanic_name'    => $request->mechanic_name,
                'phone_number'     => $request->service_type === 'Internal' ? null : $request->service_center_phone,
                'status'           => 'Completed',
            ]);

            // Create Job Card
            $jobCardData = [
                'maintenance_report_id' => $report->id,
                'service_date'          => $request->service_date,
                'current_kilometer'     => $request->current_kilometer,
                'bill_file'             => $billFilePath,
                'labour_cost'           => $request->labour_cost,
                'spare_cost'            => $request->spare_cost,
                'total_cost'            => $request->total_cost,
                'next_service_km'       => $request->next_service_km,
                'due_days'              => $request->due_days,
            ];

            // Only add job_details if Internal
            if ($request->service_type === 'Internal') {
                $jobCardData['job_details'] = $request->job_details;
                $jobCardData['cost'] = $request->cost;
            }

            $jobCard = JobCard::create($jobCardData);

            // Update vehicle's last service info
            $vehicle = Vehicle::find($request->vehicle_id);
            if ($vehicle) {
                $vehicle->update([
                    'last_service_date' => $request->service_date,
                    'last_service_km'   => $request->current_kilometer,
                    'service_km'        => $request->next_service_km,
                    'service_days'      => $request->due_days,
                ]);
            }

            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'message'    => 'Job card updated successfully',
                'data'       => [
                    'report'  => $report,
                    'jobCard' => $jobCard,
                    'vehicle' => $vehicle ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 400,
                'message'    => 'Failed to update job card',
                'error'      => $e->getMessage()
            ], 400);
        }
    }

    public function completeServiceAlerts(Request $request)
    {
        try {
            $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
            ]);

            $vehicle = Vehicle::with('trips')->findOrFail($request->vehicle_id);

            $latestTrip = $vehicle->trips->first();

            $serviceType = null;
            if ($vehicle->last_service_km !== null && $vehicle->inspection_km !== null) {
                if ($vehicle->last_service_km + 1000 <= $vehicle->inspection_km) {
                    $serviceType = 'Regular Service';
                }
            } elseif ($latestTrip && $latestTrip->status === 'Scheduled') {
                $serviceType = 'Pre Trip Service';
            } elseif ($latestTrip && $latestTrip->status === 'Completed') {
                $serviceType = 'Post Trip Service';
            }

            if (!$serviceType) {
                return response()->json([
                    'status' => 'error',
                    'statusCode' => 404,
                    'message' => 'No pending service alert found for this vehicle.'
                ], 404);
            }

            // Create a MaintenanceReport and mark Completed
            $report = MaintenanceReport::create([
                'assistance_id' => null,
                'service_category' => 'Internal',
                'service_type' => $serviceType,
                'employee_name' => null,
                'phone_number' => null,
                'status' => 'Completed'
            ]);

            // Optional: update Vehicle info if Regular Service
            if ($serviceType === 'Regular Service') {
                $vehicle->update([
                    'last_service_date' => now(),
                    'last_service_km'   => $vehicle->inspection_km,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Service alert marked as Completed',
                'data' => [
                    'vehicle_id' => $vehicle->id,
                    'service_type' => $serviceType,
                    'maintenance_report_id' => $report->id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'statusCode' => 400,
                'message' => 'Failed to complete service alert',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getServiceRecordsList()
    {
        $today = now();

        $vehicles = Vehicle::with(['trips' => function ($q) {
            $q->latest();
        }])->get();

        $records = $vehicles->map(function ($vehicle) use ($today) {
            $serviceRecords = [];

            if ($vehicle->last_service_date && $vehicle->last_service_km) {
                $nextKmDue = $vehicle->last_service_km + 1000;
            
                // Calculate next service date based on service_days
                $nextServiceDate = $vehicle->last_service_date;
                if ($vehicle->service_days) {
                    $nextServiceDate = \Carbon\Carbon::parse($vehicle->last_service_date)
                        ->addDays($vehicle->service_days)
                        ->format('d/m/Y'); 
                }
            
                $serviceRecords[] = [
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'status'            => 'Completed',
                    'service_type'      => 'Regular Service',
                    'next_service_date' => $nextServiceDate,
                    'next_service_km'   => $nextKmDue,
                ];
            }

            $latestTrip = $vehicle->trips->first();

            if ($latestTrip && $latestTrip->status === 'In Progress') {
                $assignDate = $latestTrip->assign_date 
                    ? \Carbon\Carbon::parse($latestTrip->assign_date)->toDateString()
                    : null;

                $serviceRecords[] = [
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'status'            => 'Completed',
                    'service_type'      => 'Pre Trip Service',
                    'next_service_date' => $assignDate,
                ];
            }

            if ($latestTrip && $latestTrip->status === 'Post-Service Done') {
                $deliveryDate = $latestTrip->delivery_date 
                    ? \Carbon\Carbon::parse($latestTrip->delivery_date)->toDateString()
                    : null;

                $serviceRecords[] = [
                    'vehicle_id'        => $vehicle->id,
                    'vehicle_number'    => $vehicle->vehicle_no,
                    'status'            => 'Completed',
                    'service_type'      => 'Post Trip Service',
                    'next_service_date' => $deliveryDate,
                ];
            }

            return $serviceRecords;
        })->flatten(1);

        return response()->json([
            'status'     => 'success',
            'statusCode' => 200,
            'data'       => $records
        ]);
    }
    // public function viewServiceRecordDetails($vehicleId)
    // {
    //     try {
    //         $vehicle = Vehicle::with([
    //             'trips' => function ($q) { $q->latest(); },
    //             'maintenanceReports.jobCard'
    //         ])->findOrFail($vehicleId);

    //         $today = now();
    //         $records = [];

    //         $report = $vehicle->maintenanceReports->first(); 
    //         $jobCard = $report ? $report->jobCard : null;

    //         if ($vehicle->last_service_date && $vehicle->last_service_km !== null) {
    //             $nextKmDue = $vehicle->last_service_km + 1000;
    //             $nextServiceDate = $vehicle->last_service_date->addDays($vehicle->service_days ?? 30)->toDateString();

    //             $records[] = [
    //                 'vehicle_id'        => $vehicle->id,
    //                 'vehicle_number'    => $vehicle->vehicle_no,
    //                 'vehicle_code'      => $vehicle->vehicle_code,
    //                 'next_service_km'   => $nextKmDue,
    //                 'next_service_date' => $nextServiceDate,
    //                 'service_type'      => $report ? $report->maintenance_type : null,
    //                 'details'           => $report && $report->maintenance_type === 'Internal' ? [
    //                     'supervisor_name' => $report->employee_name,
    //                     'mechanic_name'   => $report->phone_number,
    //                     'job_card'        => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : [
    //                     'service_center_name' => $report->employee_name ?? null,
    //                     'phone_number'        => $report->phone_number ?? null,
    //                     'service_date'        => $jobCard->service_date ?? null,
    //                     'current_kilometer'   => $jobCard->current_kilometer ?? null,
    //                     'bill_file'           => $jobCard->bill_file ?? null,
    //                     'labour_cost'         => (float) $jobCard->labour_cost ?? null,
    //                     'spare_cost'          => (float) $jobCard->spare_cost ?? null,
    //                     'total_cost'          => (float) $jobCard->total_cost ?? null,
    //                 ],
    //             ];
    //         }

    //         $latestTrip = $vehicle->trips->first();
    //         if ($latestTrip && $latestTrip->status === 'Scheduled') {
    //             $assignDate = $latestTrip->assign_date 
    //                 ? \Carbon\Carbon::parse($latestTrip->assign_date)->toDateString() 
    //                 : null;

    //             $records[] = [
    //                 'vehicle_id'        => $vehicle->id,
    //                 'vehicle_number'    => $vehicle->vehicle_no,
    //                 'vehicle_code'      => $vehicle->vehicle_code,
    //                 'next_service_km'   => null,
    //                 'next_service_date' => $assignDate,
    //                 'service_type'      => $report ? $report->maintenance_type : null,
    //                 'details'           => $report && $report->maintenance_type === 'Internal' ? [
    //                     'supervisor_name' => $report->employee_name,
    //                     'mechanic_name'   => $report->phone_number,
    //                     'job_card'        => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : [
    //                     'service_center_name' => $report->employee_name ?? null,
    //                     'phone_number'        => $report->phone_number ?? null,
    //                     'service_date'        => $jobCard->service_date ?? null,
    //                     'current_kilometer'   => $jobCard->current_kilometer ?? null,
    //                     'bill_file'           => $jobCard->bill_file ?? null,
    //                     'labour_cost'         => (float) $jobCard->labour_cost ?? null,
    //                     'spare_cost'          => (float) $jobCard->spare_cost ?? null,
    //                     'total_cost'          => (float) $jobCard->total_cost ?? null,
    //                 ],
    //             ];
    //         }

    //         if ($latestTrip && $latestTrip->status === 'Completed') {
    //             $deliveryDate = $latestTrip->delivery_date 
    //                 ? \Carbon\Carbon::parse($latestTrip->delivery_date)->toDateString() 
    //                 : null;

    //             $records[] = [
    //                 'vehicle_id'        => $vehicle->id,
    //                 'vehicle_number'    => $vehicle->vehicle_no,
    //                 'vehicle_code'      => $vehicle->vehicle_code,
    //                 'next_service_km'   => null,
    //                 'next_service_date' => $deliveryDate,
    //                 'service_type'      => $report ? $report->maintenance_type : null,
    //                 'details'           => $report && $report->maintenance_type === 'Internal' ? [
    //                     'supervisor_name' => $report->employee_name,
    //                     'mechanic_name'   => $report->phone_number,
    //                     'job_card'        => $jobCard ? [
    //                         'service_date'      => $jobCard->service_date,
    //                         'current_kilometer' => $jobCard->current_kilometer,
    //                         'job_details'       => $jobCard->job_details ?? [],
    //                         'bill_file'         => $jobCard->bill_file,
    //                         'cost'              => (float) $jobCard->cost,
    //                         'total_cost'        => (float) $jobCard->total_cost,
    //                         'labour_cost'       => (float) $jobCard->labour_cost,
    //                         'spare_cost'        => (float) $jobCard->spare_cost,
    //                     ] : null,
    //                 ] : [
    //                     'service_center_name' => $report->employee_name ?? null,
    //                     'phone_number'        => $report->phone_number ?? null,
    //                     'service_date'        => $jobCard->service_date ?? null,
    //                     'current_kilometer'   => $jobCard->current_kilometer ?? null,
    //                     'bill_file'           => $jobCard->bill_file ?? null,
    //                     'labour_cost'         => (float) $jobCard->labour_cost ?? null,
    //                     'spare_cost'          => (float) $jobCard->spare_cost ?? null,
    //                     'total_cost'          => (float) $jobCard->total_cost ?? null,
    //                 ],
    //             ];
    //         }

    //         return response()->json([
    //             'status'     => 'success',
    //             'statusCode' => 200,
    //             'data'       => $records,
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'statusCode' => 404,
    //             'message'    => 'Vehicle not found or error occurred',
    //             'error'      => $e->getMessage()
    //         ], 404);
    //     }
    // }
    public function viewServiceRecordDetails($vehicleId)
    {
        try {
            $vehicle = Vehicle::with([
                'vehicleType',
                'trips' => fn($q) => $q->latest(),
                'maintenanceReports.jobCard',
                'maintenanceReports.images'
            ])->findOrFail($vehicleId);
    
            // ✅ Calculate next service KM and date
            $lastServiceKm = $vehicle->last_service_km ?? 0;
            $serviceKm = $vehicle->service_km ?? 1000;
            $nextKmDue = $lastServiceKm + $serviceKm;
    
            $nextServiceDate = $vehicle->last_service_date
                ? \Carbon\Carbon::parse($vehicle->last_service_date)
                    ->addDays($vehicle->service_days ?? 30)
                    ->format('d/m/Y')
                : null;
    
            $vehicleInfo = [
                'vehicle_id'        => $vehicle->id,
                'vehicle_number'    => $vehicle->vehicle_no,
                'vehicle_code'      => $vehicle->vehicle_code,
                'vehicle_type_id'   => $vehicle->vehicleType->id ?? null,
                'vehicle_type_name' => $vehicle->vehicleType->vehicle_type_name ?? null,
                'next_service_km'   => $nextKmDue,
                'next_service_date' => $nextServiceDate,
            ];
    
            $totalCost = 0;
            $serviceHistory = [];
    
            foreach ($vehicle->maintenanceReports as $report) {
                $jobCard = $report->jobCard;
                $jobDetails = [];
    
                // ✅ Handle Internal Service
                if ($report->service_category === 'Internal') {
                    $jobDetails = [
                        'supervisor_name'   => $report->employee_name,
                        'mechanic_name'     => $report->phone_number,
                        'service_date'      => $jobCard?->service_date
                            ? \Carbon\Carbon::parse($jobCard->service_date)->format('d/m/Y')
                            : null,
                        'current_kilometer' => $jobCard->current_kilometer ?? null,
                        'job_card'          => $jobCard ? collect($jobCard->job_details)->map(function ($job) {
                            return [
                                'complaints'   => $job['complaints'] ?? null,
                                'parts_needed' => $job['parts_needed'] ?? null,
                                'parts_name'   => $job['parts']['name'] ?? null,
                                'quantity'     => $job['parts']['quantity'] ?? null,
                                'stock_status' => $job['parts']['stock_status'] ?? null,
                                'instructions' => $job['instructions'] ?? null,
                            ];
                        })->values() : [],
                        'bill_file'   => $jobCard->bill_file ?? null,
                        'labour_cost' => (float)($jobCard->labour_cost ?? 0),
                        'spare_cost'  => (float)($jobCard->spare_cost ?? 0),
                        'total_cost'  => (float)($jobCard->total_cost ?? 0),
                    ];
                }
    
                // ✅ Handle External Service
                else {
                    $jobDetails = [
                        'service_center_name' => $report->service_center_name ?? null,
                        'mechanic_name'       => $report->phone_number ?? null,
                        'service_date'        => $jobCard?->service_date
                            ? \Carbon\Carbon::parse($jobCard->service_date)->format('d/m/Y')
                            : null,
                        'current_kilometer'   => $jobCard->current_kilometer ?? null,
                        'images'              => $report->images->map(fn($img) => $img->image_url ?? null)->filter()->values(),
                        'labour_cost'         => (float)($jobCard->labour_cost ?? 0),
                        'spare_cost'          => (float)($jobCard->spare_cost ?? 0),
                        'total_cost'          => (float)($jobCard->total_cost ?? 0),
                    ];
                }
    
                $totalCost += $jobCard ? (float)($jobCard->total_cost ?? 0) : 0;
    
                $serviceHistory[] = [
                    'service_type' => $report->service_category,
                    'details'      => $jobDetails,
                ];
            }
    
            return response()->json([
                'status'     => 'success',
                'statusCode' => 200,
                'data'       => [
                    'vehicle_info'    => $vehicleInfo,
                    'service_history' => $serviceHistory,
                    'total_cost'      => $totalCost,
                ],
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status'     => 'error',
                'statusCode' => 404,
                'message'    => 'Vehicle not found or error occurred',
                'error'      => $e->getMessage(),
            ], 404);
        }
    }



 
}
