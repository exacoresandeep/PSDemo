<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\AuthController;
use App\Models\Activity;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\ActivitiesQuestionDetail;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Regions;
use App\Models\AssignRoute;
use App\Helpers\ProductHelper;
use App\Models\DealerRouteAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;
use DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Services\FirebasePushService;

class ActivityController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $activities = Activity::where('employee_id', $user->id)
                ->with(['activityType', 'dealer']) 
	                ->orderBy('created_at', 'desc')
                ->orderBy('assigned_date', 'desc')
                ->get();
            // dd($activities);
            $activitiesData = $activities->map(function ($activity) {
                $activity->assigned_date =\Carbon\Carbon::parse($activity->assigned_date)->format('d/m/Y');
                $activity->completed_date = \Carbon\Carbon::parse($activity->completed_date)->format('d/m/Y');
                
                return [
                    'id' => $activity->id,
                    'assigned_date' => $activity->assigned_date,
                    'completed_date' => $activity->completed_date,
                    'status' => $activity->status,
                    'activity_type' => [
                        'id' => $activity->activityType->id,
                        'name' => $activity->activityType->name,
                    ],
                    'dealer' => $activity->dealer ? [
                        'id' => $activity->dealer->id,
                        'dealer_code' => $activity->dealer->dealer_code,
                        'dealer_name' => $activity->dealer->dealer_name,
                    ] : null,
                ];
            });
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activities retrieved successfully!',
                'data' => $activitiesData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateActivity(Request $request, $activityId)
    {
        try {

            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $validatedData = $request->validate([
                'remarks' => 'required|string', 
                'attachments' => 'required|array',
                'attachments.*' => 'string',
                'question_inputs' => 'required|array',
                'question_inputs.*.activity_question_labels_id' => 'required|integer',
                'question_inputs.*.activity_input' => 'required|string|max:300',
            ]);

            $activity = Activity::find($activityId);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Activity not found',
                ], 400);
            }

            if ($activity->employee_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Forbidden: You can only update your own activities.',
                ], 400);
            }

            $activity->remarks = $validatedData['remarks'];
            $activity->attachments = $validatedData['attachments'];
            $activity->completed_date = now(); 
            $activity->status = 'Completed';
            $activity->save(); 
           
            ActivitiesQuestionDetail::where('activity_id', $activityId)->delete();
            foreach ($validatedData['question_inputs'] as $input) {
                ActivitiesQuestionDetail::create([
                    'activity_id' => $activityId,
                    'activity_question_labels_id' => $input['activity_question_labels_id'],
                    'activity_input' => $input['activity_input'],
                ]);
                
            }
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activity updated successfully!',
                'data' => $activity,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
 
    public function viewActivity($activityId)
    {
        try {
            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $activity = Activity::with(['activityType','activityType.questionLabels','dealer'])
                ->find($activityId);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Activity not found',
                ], 404);
            }

            $activityEmployee = Employee::find($activity->employee_id);
            if (!$activityEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Activity creator not found',
                ], 404);
            }

            if($user->id==$activity->employee_id && $activity->notification_status=="pending"){
                $activity->update(['notification_status' => 'opened']);      
            }
        $questionInputs = ActivitiesQuestionDetail::with(["questionLabel"])->where('activity_id', $activityId)
            // ->select('activity_question_labels_id', 'activity_input')
            ->get();
	    //......................notification..............
	    $authController = new AuthController();
        $authController->changeNotificationStatus('activities', $activityId,'opened');
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activity retrieved successfully!',
                 'data' => [
                        'activity' => $activity,
                        'question_inputs' => $questionInputs,
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

    public function activityReportListing(Request $request)
    {
        try {
            $employee = Auth::user();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            $salesExecutives = Employee::all();

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $totalActivitiesForPeriod = 0;

            $reportData = $salesExecutives->map(function ($se) use ($month, $year, &$totalActivitiesForPeriod) {
                $activityCount = Activity::where('employee_id', $se->id)
                    ->whereYear('assigned_date', $year)
                    ->whereMonth('assigned_date', $month)
                    ->count();

                $totalActivitiesForPeriod += $activityCount;

                return [
                    'employee_id' => $se->id,
                    'employee_name' => $se->name,
                    'employee_code' => $se->employee_code,
                    'total_activities' => $activityCount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Activity report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_activities_for_period' => $totalActivitiesForPeriod,
                    'activity_report' => $reportData,
                ],
                
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function activityReportDetails(Request $request, $employee_id)
    {
        try {
            $employee = Auth::user();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            $salesExecutive = Employee::find($employee_id);
            if (!$salesExecutive || $salesExecutive->district !== $employee->district) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Sales Executive not found in your district.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $activities = Activity::where('employee_id', $salesExecutive->id)
                ->whereYear('assigned_date', $year)
                ->whereMonth('assigned_date', $month)
                ->with(['activityType', 'dealer']) 
                ->get();

            $totalActivities = $activities->count();

            $activityList = $activities->map(function ($activity) {
                return [
                    'activity_id' => $activity->id,
                    'activity_type' => $activity->activityType ? $activity->activityType->name : null,
                    'dealer_code' => $activity->dealer ? $activity->dealer->dealer_code : null,
                    'dealer_name' => $activity->dealer ? $activity->dealer->dealer_name : null,
                    'completed_date' => $activity->status === 'Pending' ? null : ($activity->completed_date ? Carbon::parse($activity->completed_date)->format('d/m/Y') : null),
                    'assigned_date' => $activity->status === 'Pending' ? Carbon::parse($activity->assigned_date)->format('d/m/Y') : null,
                    'status' => $activity->status,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Activity report details fetched successfully for $month/$year.",
                'data' =>[
                    'employee_details' => [
                        'employee_id' => $salesExecutive->id,
                        'employee_name' => $salesExecutive->name,
                        'employee_code' => $salesExecutive->employee_code,
                        'email' => $salesExecutive->email,
                        'phone' => $salesExecutive->phone,
                        'total_activities' => $totalActivities,
                    ],
                    'activities' => $activityList,
                ],        
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function activityTypeIndex()
    {
        return view('sales.activity.created-activities'); 
    }

    public function activityTypeStore(Request $request)
    {
       
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
        ]);
$user = Auth::user();
        $activity_type = ActivityType::create([
            "id" =>27,
            'name' => $request->activity_name,
            'status' => "1",
            "created_by"=>$user->id,
        ]);

        if (!empty($request->fields)) {
            foreach ($request->fields as $field) 
                {
                    
                DB::table('activity_question_labels')->insert([
                    'activity_types_id' => $activity_type->id,
                    'type' => strtolower($field['type']),
                    'label_name' => $field['label'],
                    'label_options' => strtolower($field['type']) === 'select' ? $field['options'] : '',
                    "created_by"=>$user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Activity Type created successfully!',
            'activity_type' => $activity_type
        ], 201);
    }

    public function getActivityTypes(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $query = ActivityType::whereIn('status', ['1', '2'])
                    ->whereNull('deleted_at') 
                    ->where("created_by",$user->id)
                    ->orderBy('id', 'desc');
    
            return DataTables::of($query)
                ->addIndexColumn()
                ->make(true);
        }
        return abort(403, 'Unauthorized access');
    }

    public function deleteQuestionLabel($id)
    {
        $label = DB::table('activity_question_labels')->where('id', $id)->first();

        if (!$label) {
            return response()->json(['success' => false, 'message' => 'Label not found.'], 404);
        }
   
        $isUsed = DB::table('activities_question_details')
            ->where('activity_question_labels_id', $id)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'This field is already linked to an activity and cannot be deleted.'
            ], 400);
        }

        DB::table('activity_question_labels')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Label deleted successfully.']);
    }

    public function editActivityType(ActivityType $activity_type)
    {
        $activity_type->load('questionLabels');
        return response()->json(['activity_type' => $activity_type]);
    }

    public function updateActivityType(Request $request, ActivityType $activity_type)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
        ]);

        $activity_type->update([
            'name' => $request->activity_name,
            'status' => $request->status,
        ]);
        if (!empty($request->fields)) {
            foreach ($request->fields as $field) {
                if (!empty($field['id'])) {
                 
                    DB::table('activity_question_labels')
                        ->where('id', $field['id'])
                        ->update([
                            'type' => strtolower($field['type']),
                            'label_name' => $field['label'],
                            'label_options' => strtolower($field['type']) === 'select' ? $field['options'] : '',
                            'updated_at' => now(),
                        ]);
                } else {
               
                    DB::table('activity_question_labels')->insert([
                        'activity_types_id' => $activity_type->id,
                        'type' => strtolower($field['type']),
                        'label_name' => $field['label'],
                        'label_options' => strtolower($field['type']) === 'select' ? $field['options'] : '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Activity Type updated successfully!',
            'activity_type' => $activity_type
        ]);
    }

    public function deleteActivityType(ActivityType $activity_type)
    {
        $activity_type->delete(); 

        return response()->json(['message' => 'Activity Type deleted successfully!']);
    }

    // public function getEmployeesByDealer($dealer_id)
    // {
    //     $dealer = Dealer::find($dealer_id);

    //     if (!$dealer) {
    //         return response()->json([], 404);
    //     }

    //     $employees = AssignRoute::where('id', $dealer->assigned_route_id)
    //         ->where('employee_type_id', 1) 
    //         ->with('employee:id,name')
    //         ->get()
    //         ->pluck('employee'); 

    //     return response()->json($employees);
    // }

    public function getDealersByDistrict($district_id)
    {
        $dealers = Dealer::where('district_id', $district_id)
            ->select('id', 'dealer_name', 'dealer_code', 'assigned_route_id')
            ->get();

        return response()->json($dealers);
    }

    public function activityIndex()
    {
        $activityTypes = ActivityType::all();
        $districts = District::select('id', 'name')->get();
        return view('sales.activity.index', compact('activityTypes', 'districts'));
    }
    public function list(Request $request)
    {
        $user = Auth::user();
        $query = Activity::with(['activityType', 'dealer', 'employee'])->whereNull('deleted_at')->where("created_by",$user->id);

        if ($request->activity_type) {
            $query->where('activity_type_id', $request->activity_type);
        }
        if ($request->dealer) {
            $query->whereHas('dealer', function ($q) use ($request) {
                $q->where('dealer_name', 'LIKE', "%{$request->dealer}%")
                ->orWhere('dealer_code', 'LIKE', "%{$request->dealer}%");
            });
        }
        if ($request->district) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('district_id', $request->district);
            });
        }
        if ($request->employee) {
            $query->where('employee_id', $request->employee);
        }
        if ($request->assigned_date) {
            $query->whereDate('assigned_date', $request->assigned_date);
        }
        if ($request->due_date) {
            $query->whereDate('due_date', $request->due_date);
        }

        $activities = $query->get(); 

        return DataTables::of($activities)
            ->addIndexColumn()
            ->addColumn('activity_type_name', function ($activity) {
                return optional($activity->activityType)->name ?? '-';
            })
            ->addColumn('dealer_name', function ($activity) {
                return optional($activity->dealer)->dealer_name 
                    ? optional($activity->dealer)->dealer_name . ' (' . optional($activity->dealer)->dealer_code . ')' 
                    : '-';
            })
            ->addColumn('employee_name', function ($activity) {
                return optional($activity->employee)->name ?? '-';
	    })
	    ->addColumn('assigned_date', function ($activity) {
                return \Carbon\Carbon::parse($activity->assigned_date)->format('d/m/Y');
            })
            ->addColumn('due_date', function ($activity) {
                return \Carbon\Carbon::parse($activity->due_date)->format('d/m/Y');
            })
            ->addColumn('status', function ($activity) {
                $status = $activity->status ?? 'Pending';
                $dueDate = $activity->due_date;
                $today = now()->toDateString();
            
                $statusBadge = match ($status) {
                    'Completed' => '<span class="badge bg-success text-white">Completed</span>',
                    'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                    default => '<span class="badge bg-secondary text-white">' . $status . '</span>',
                };
            
                $overdueButton = '';
                if ($status == 'Pending' && $dueDate < $today) {
                    $overdueButton = '<span class="badge bg-danger text-white">Overdue</span>';
                }
            
                return $statusBadge . $overdueButton;
            })
            ->addColumn('action', function ($activity) {
                return '
                    <button class="btn btn-sm btn-info" onclick="handleAction(' . $activity->id . ', \'view\')" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $activity->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteActivity" data-id="' . $activity->id . '" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action','status'])
            ->make(true);


    }

    public function store(Request $request,FirebasePushService $fcm)
    {
        $request->validate([
            'activity_type_id' => 'required|exists:activity_types,id',
            'dealer_id' => 'required|exists:dealers,id',
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'instruction' => 'required|string',
    	]);
    	$emp=Employee::find($request->employee_id);
    	$deviceToken=$emp->fcm_token ?? null;
        $user = Auth::user();
            $activity = Activity::create([
                'activity_type_id' => $request->activity_type_id,
                'dealer_id' => $request->dealer_id,
                'employee_id' => $request->employee_id,
                'assigned_date' => $request->assigned_date,
                'due_date' => $request->due_date,
                'instructions' => $request->instruction,
                'created_by' => $user->id,
                'status' => 'Pending',
    	]);
    
    	if ($deviceToken) {

            $title = 'New Activity Assigned';

            $body = 'New Activity on ' . $request->assigned_date;

            $fcm->sendNotification($deviceToken, $title, $body, 'employees');

        }

        return response()->json(['message' => 'Activity created successfully!', 'activity' => $activity]);
    }
    public function view($id)
    {
        $activity = Activity::with(['activityType', 'dealer', 'employee'])->find($id);
        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }
        $activity->assigned_date =\Carbon\Carbon::parse($activity->assigned_date)->format('d/m/Y');
                $activity->completed_date = \Carbon\Carbon::parse($activity->completed_date)->format('d/m/Y');
                
    
        return response()->json(['activity' => $activity]);
    }
    public function edit($id)
    {
        $activity = Activity::with(['activityType', 'employee', 'dealer'])->find($id);

        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }
    
        return response()->json(['activity' => $activity]);
    }

    public function update(Request $request, Activity $activity)
    {
        $request->validate([
            'activity_type_id' => 'required|exists:activity_types,id',
            'dealer_id' => 'required|exists:dealers,id',
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'instruction' => 'required|string',
        ]);

        $activity->update([
            'activity_type_id' => $request->activity_type_id,
            'dealer_id' => $request->dealer_id,
            'employee_id' => $request->employee_id,
            'assigned_date' => $request->assigned_date,
            'due_date' => $request->due_date,
            'instructions' => $request->instruction,
            'status' => 'Pending',
        ]);

        return response()->json(['message' => 'Activity updated successfully!', 'activity' => $activity]);
    }

    public function delete(Activity $activity)
    {
        $activity->delete(); 
        return response()->json(['message' => 'Activity deleted successfully!']);
    }
    
   
    public function getEmployeesByDistrictType($district_id, $employee_type_id)
    {

        $productId = ProductHelper::getSelectedProductId(); //push
        $query = Employee::whereRaw("JSON_CONTAINS(products, ?)", ['["' . $productId . '"]'])->select('id', 'name')->orderBy('name', 'asc');
        //push
        if (in_array($employee_type_id, [1, 2, 3])) {
            $query->where('district_id', $district_id)
                  ->where('employee_type_id', $employee_type_id);
        }
    
       
        elseif ($employee_type_id == 4) {

            $region = Regions::whereHas('districts', function ($q) use ($district_id) {
                $q->where('id', $district_id);
            })->first();
    
            if ($region) {
          
                $districtIds = District::where('regions_id', $region->id)->pluck('id')->toArray();
    
                $query->where('employee_type_id', 4)
                      ->whereIn('district_id', $districtIds);
            } else {
             
                $query->where('employee_type_id', 4);
            }
        }

        elseif ($employee_type_id == 5) {
            $query->where('employee_type_id', 5);
        }
    
        $employees = $query->get();
    
        return response()->json($employees);
    }

    // public function getDealersByEmployee($employee_id)
    // {
    //     $user = Employee::find($employee_id);
    //     if (!$user) {
    //         return response()->json(['message' => 'Employee not found'], 404);
    //     }
    
    //     $query = Dealer::select(
    //         'id as dealer_id',
    //         'dealer_code',
    //         'dealer_name',
    //         'phone',
    //         'email',
    //         'address',
    //         'user_zone',
    //         'pincode',
    //         'state',
    //         'district',
    //         'taluk'
    //     )->where('status', '1');
    
    //     if ($user->employee_type_id == 1 || $user->employee_type_id == 2) {
    //         $assignedRouteIds = AssignRoute::where('employee_id', $user->id)->pluck('id')->toArray();
    //         $query->whereIn('assigned_route_id', $assignedRouteIds);
    //     } elseif ($user->employee_type_id == 3) {
    //         $query->where('district_id', $user->district_id);
    //     } elseif ($user->employee_type_id == 4) {
    //         $region = Regions::whereHas('districts', function ($q) use ($user) {
    //             $q->where('id', $user->district_id);
    //         })->first();
    //         if ($region) {
    //             $districtIds = District::where('regions_id', $region->id)->pluck('id')->toArray();
    //             $query->whereIn('district_id', $districtIds);
    //         }
    //     } elseif ($user->employee_type_id == 5) {
    //         // Sales Manager - show all dealers
    //     }
    
    //     $dealers = $query->orderBy('dealer_name', 'asc')->get();
    
    //     return response()->json($dealers);
    // }
    public function getDealersByEmployee($employee_id)
    {
        $employee = Employee::find($employee_id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)
            ->pluck('id')
            ->toArray();

        if (empty($assignedRouteIds) && $employee->employee_type_id == 5) {
            $dealers = Dealer::where('status', '1')
                ->orderBy('dealer_name', 'asc')
                ->get();
            return response()->json($dealers);
        }

        if (empty($assignedRouteIds)) {
            return response()->json([]);
        }

        $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
            ->pluck('dealer_id')
            ->unique()
            ->toArray();

        $dealers = Dealer::select(
            'id as dealer_id',
            'dealer_code',
            'dealer_name',
            'phone',
            'email',
            'address',
            'user_zone',
            'pincode',
            'state',
            'district',
            'taluk'
        )
            ->whereIn('id', $dealerIds)
            ->where('status', '1')
            ->orderBy('dealer_name', 'asc')
            ->get();

        return response()->json($dealers);
    }


}
