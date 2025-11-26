<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeType;
use App\Models\Employee; 
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;


class AttendanceController extends Controller
{
    public function exportAttendance(Request $request)
    {
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $employee_type = $request->input('employee_type');
        $employee_id   = $request->input('employee_id');
        $status        = $request->input('status');

        return Excel::download(new AttendanceExport($from_date, $to_date, $employee_type, $employee_id, $status), 'attendance_summary_' . now()->format('d_m_Y_H_i_s') . '.xlsx');
    }

    public function punchIn(Request $request)
    {
        $employeeId = Auth::id();
        $employeeCode = Auth::user()->employee_code ?? null;
        $date = Carbon::today()->format('Y-m-d');
        $username = Auth::user()->name ?? null;
        $request->validate([
            'latitude'            => 'required|string',
            'longitude'           => 'required|string',
            'starting_remarks'    => 'required|string|max:1000',
            'starting_km'         => 'required',
            'starting_attachment' => 'required|array', // now an array
            'starting_attachment.*' => 'string' // each file name must be string
        ]);

        if (empty($request->latitude) || empty($request->longitude)) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Latitude and longitude are required. Please enable location services.',
            ]);
        }

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                // ->where('status', "present")
                                ->where(function ($q) {
                                    $q->where('status', 'present')
                                        ->orWhere(function ($q2) {
                                            $q2->where('status', 'leave')
                                                ->where('leave_type', 'Full Day');
                                        });
                                })
                                ->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'employee_id' => $employeeId,
                'date' => $date,
                'punch_in' => Carbon::now('Asia/Kolkata')->format('H:i:s'),
                'punch_out' => null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => "present",
                'starting_remarks'   => $request->starting_remarks,
                'starting_km'        => $request->starting_km,
                'starting_attachment'=> json_encode($request->starting_attachment) // store as JSON
            ]);
            DB::table('attendance_v2')->insert([
                'c1' => 'in',
                'log_date' => Carbon::now('Asia/Kolkata'),
                'pid' => null,
                'user_id' => $employeeCode,
                'username' => $username
            ]);
            try{
               // $this->sendAttendanceToGreytHR($employeeCode,"Mobile App",1);
            } catch (\Exception $e) {


            }
            $totalActiveHours = '00:00:00';
        } elseif ($attendance->punch_out !== null) {
            if($attendance->leave_type=="Full Day"){
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'You have already applied for leave',
                    ]);
            }
            $attendance->update([
                'punch_out' => null,
            ]);
            $totalActiveHours = '00:00:00';
        } else {
            if($attendance->leave_type=="Full Day"){
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'You have already applied for leave',
                    ]);
            }
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched in. Punch out first before punching in again.',
            ]);
        }

        // Convert stored JSON back to array and map URLs
        $attachments = json_decode($attendance->starting_attachment, true) ?? [];
        $fileUrls = array_map(fn($file) => url('storage/uploads/' . $file), $attachments);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched in successfully',
            'data' => [
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'starting_remarks'   => $attendance->starting_remarks,
                'starting_km'        => $attendance->starting_km,
                'starting_attachment'=> $fileUrls, // now array of URLs
                'total_active_hours' => $totalActiveHours,
            ]
        ]);
    }

    public function punchOut(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');
        $username = Auth::user()->name ?? null;
        $employeeCode = Auth::user()->employee_code ?? null;
        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->whereNotNull('punch_in')
                                ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'No punch-in record found for today.',
            ]);
        }

        if ($attendance->punch_out !== null) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched out. Punch in first before punching out again.',
            ]);
        }

        $punchOutTime = Carbon::now('Asia/Kolkata');
        $totalActiveHours = $request->input('total_active_hours');

        if (!$totalActiveHours) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Total active hours not provided.',
            ]);
        }
        $request->validate([
            'total_active_hours' => 'required|string',
            'ending_remarks'     => 'required|string|max:1000',
            'ending_km'          => 'required',
            'ending_attachment'  => 'required|array',
            'ending_attachment.*'=> 'string'
        ]);

        $attendance->update([
            'punch_out' => $punchOutTime->format('H:i:s'),
            'total_active_hours' => $totalActiveHours,
            'ending_remarks'     => $request->ending_remarks,
            'ending_km'          => $request->ending_km,
            'ending_attachment'  => json_encode($request->ending_attachment)
        ]);
        DB::table('attendance_v2')->insert([
                    'c1' => 'out',
                    'log_date' => Carbon::now('Asia/Kolkata'),
                    'pid' => null,
                    'user_id' => $employeeCode,
                    'username' => $username
                ]);
        try{
           // $this->sendAttendanceToGreytHR($employeeCode,"Mobile App",0);
        } catch (\Exception $e) {


        }
        // Map file URLs
        $startingAttachments = json_decode($attendance->starting_attachment, true) ?? [];
        $endingAttachments   = json_decode($attendance->ending_attachment, true) ?? [];

        $fileUrls1 = array_map(fn($file) => url('storage/uploads/' . $file), $startingAttachments);
        $fileUrls2 = array_map(fn($file) => url('storage/uploads/' . $file), $endingAttachments);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched out successfully',
            'data' => [
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'starting_remarks'   => $attendance->starting_remarks,
                'starting_km'        => $attendance->starting_km,
                'starting_attachment'=> $fileUrls1,
                'ending_remarks'     => $attendance->ending_remarks,
                'ending_km'          => $attendance->ending_km,
                'ending_attachment'  => $fileUrls2,
                'total_active_hours' => $attendance->total_active_hours,
            ]
        ]);
    }

    private function sendAttendanceToGreytHR($employeeCode,$doorName, $direction)
    {
        try {
            $privateKeyPath = storage_path('app/private-key.pem');

            if (!file_exists($privateKeyPath)) {
                \Log::error("GreytHR private key file not found: {$privateKeyPath}");
                return false;
            }

            $isoDate = now('Asia/Kolkata')->format('Y-m-d\TH:i:s.vP');

            $swipe = "{$isoDate},{$employeeCode},{$doorName},{$direction}\n";

            // Sign swipe data
            $privateKey = file_get_contents($privateKeyPath);
            $pkeyid = openssl_pkey_get_private($privateKey);
            if (!$pkeyid) {
                \Log::error("Failed to load GreytHR private key");
                return false;
            }
            $signature = null;
            openssl_sign($swipe, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
            openssl_free_key($pkeyid);

            $data = [
                'id'     => "adminuser",
                'swipes' => $swipe,
                'sign'   => base64_encode($signature)
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => "https://prabhusteels.greythr.com/v2/attendance/asca/swipes",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_HTTPHEADER     => ["X-Requested-With: XMLHttpRequest"],
                CURLOPT_TIMEOUT        => 20
            ]);

            $response = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                \Log::error("GreytHR API Error: {$error}");
                return false;
            }

            if ($statusCode !== 200) {
                \Log::error("GreytHR API failed: HTTP {$statusCode}. Response: {$response}");
                return false;
            }

            $directionText = $direction == 1 ? 'IN' : 'OUT';
            \Log::info("GreytHR swipe sent successfully for {$employeeCode} ({$directionText})");
            return true;
        } catch (\Exception $e) {
            \Log::error("GreytHR send failed: " . $e->getMessage());
            return false;
        }
    }


    public function getsummary(Request $request)
    {
        try {
            $request->validate([
                'from_date' => 'required|date_format:d/m/Y',
                'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
            ]);

            // Convert to Y-m-d for database query
            $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $toDate   = $request->to_date 
                ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d') 
                : now()->toDateString();
                

            $employeeId = auth()->user()->id ?? null;

            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized user',
                ], 401);
            }

            $records = Attendance::where('employee_id', $employeeId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No attendance records found for given period',
                    'data' => [],
                ], 404);
            }

            $data = $records->map(function ($record) {
                return [
                    'date' => $record->date,
                    'punch_in' => $record->punch_in,
                    'punch_out' => $record->punch_out,
                    'total_active_hours' => $record->total_active_hours,
                    'starting_remarks' => $record->starting_remarks,
                    'ending_remarks' => $record->ending_remarks,
                    'status' => $record->status,
                    'leave_type' => $record->leave_type,
                    'starting_km' => $record->starting_km,
                    'ending_km' => $record->ending_km,
                    'starting_attachment' => $record->starting_attachment 
                        ? asset('storage/attendance/' . $record->starting_attachment) 
                        : null,
                    'ending_attachment' => $record->ending_attachment 
                        ? asset('storage/attendance/' . $record->ending_attachment) 
                        : null,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Attendance summary fetched successfully',
                'data' => $data,
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

    public function entryLeave(Request $request)
    {
        try {
            $employeeId = Auth::id();
            $date = Carbon::today()->format('Y-m-d');

            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized user',
                ], 401);
            }
            if (empty($request->latitude) || empty($request->longitude)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Latitude and longitude are required. Please enable location services.',
                ]);
            }
            // ✅ Validation
            $request->validate([
                'leave_date'   => 'required|date',
                'remarks' => 'required|string|max:1000',
                'leave_type' => 'required|string'

            ]);

            $leaveDate = Carbon::parse($request->leave_date)->format('Y-m-d');

            // ✅ Check if attendance already exists for the date
            $attendance = Attendance::where('employee_id', $employeeId)
                                    ->where('date', $leaveDate)
                                    ->where('status', "leave")
                                    ->where('leave_type', "!=","Full Day")
                                    ->first();

            if ($attendance) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Attendance already marked for this date.',
                ], 400);
            }

            // ✅ Create absent record
            $attendance = Attendance::create([
                'employee_id'    => $employeeId,
                'date'           => $leaveDate,
                'status'         => 'leave',
                'leave_type' => $request->leave_type,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'starting_remarks' => $request->remarks,
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave entered successfully',
                'data' => [
                    'employee_id' => $attendance->employee_id,
                    'date'        => $attendance->date,
                    'status'      => $attendance->status,
                    'leave_type' => $attendance->leave_type,
                    'leave_reason'=> $attendance->starting_remarks,
                ]
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

    public function LeaveType()
    {
        try {
            $leaveTypes = [
                'Full Day',
                'First Half',
                'Second Half',
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave types fetched successfully',
                'data' => $leaveTypes
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

    public function getTodayAttendance()
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->where('status', "present")
                                ->first();
        
        if (!$attendance) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'No attendance record found for today.',
                'data' => [
                    'punch_in' => null,
                    'punch_out' => null,
                    'total_active_hours' => '00:00:00',
                ]
            ]);
        }
        $current_time = Carbon::now('Asia/Kolkata');
        $punchIn = Carbon::parse($attendance->punch_in);
        $total_active_hours = $punchIn->diff($current_time)->format('%H:%I:%S');
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Attendance details retrieved successfully',
            'data' => [
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'total_active_hours' => $total_active_hours,
            ]
        ]);
    }   
    
    public function index()
    {
        $attendance = Attendance::all(); 
        $employeeTypes = EmployeeType::all();
        return view('sales.attendance.index', compact('attendance','employeeTypes'));
    }

    public function list(Request $request)
    {
        $query = Attendance::with(['employee.employeeType']);

        if (!empty($request->employee_type)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employee_type_id', $request->employee_type);
            });
        }

        if (!empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }

        if (!empty($request->from_date) && !empty($request->to_date)) {
            $from = \Carbon\Carbon::parse($request->from_date)->startOfDay();
            $to   = \Carbon\Carbon::parse($request->to_date)->endOfDay();
            $query->whereBetween('date', [$from, $to]);
        }

        if (!empty($request->status)) {
            $query->where('status', strtolower($request->status)); // db has enum('present','leave','idle')
        }
        $query->orderBy('date', 'desc');
        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->whereHas('employee', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('employee_code', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('date', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%");
                }
            })
            ->addIndexColumn()
            ->addColumn('attendance_day', function ($target) {
                return \Carbon\Carbon::parse($target->date)->format('d-m-Y');
            })
            ->addColumn('employee_name', function ($target) {
                return optional($target->employee)->name . ' / ' . optional($target->employee)->employee_code;
            })
            ->addColumn('status', function ($target) {
                return ucfirst($target->status ?? '-');
            })
            ->addColumn('punch_in', fn($t) => $t->punch_in ?? '-')
            ->addColumn('starting_remarks', function ($t) {
                return $t->status === 'leave' ? '-' : ($t->starting_remarks ?? '-');
            })
            
            ->addColumn('starting_km', fn($t) => $t->starting_km ?? '-')
            ->addColumn('punch_out', fn($t) => $t->punch_out ?? '-')
            ->addColumn('ending_remarks', fn($t) => $t->ending_remarks ?? '-')
            ->addColumn('ending_km', fn($t) => $t->ending_km ?? '-')
            ->addColumn('total_time', fn($t) => $t->total_active_hours ?? '-')
            ->addColumn('leave_type', fn($t) => $t->leave_type ?? '-')
            ->addColumn('leave_remarks', function ($t) {
                if ($t->status === 'leave') {
                    return $t->starting_remarks ?? '-';
                }
                return $t->leave_type 
                ? ($t->leave_type . ' - ' . ($t->starting_remarks ?? '')) 
                : '-';
            })
            ->addColumn('total_km', function ($t) {
                return ($t->starting_km !== null && $t->ending_km !== null)
                ? $t->ending_km - $t->starting_km
                : '-';
            })
           ->addColumn('starting_attachment', function ($t) {
                $images = [];

                if (!empty($t->starting_attachment)) {
                    $images = json_decode($t->starting_attachment, true);
                }

                if (empty($images)) {
                    return '-';
                }

                return '<a href="javascript:void(0);" data-type="Starting" class="view-images" data-images=\'' . json_encode($images) . '\'>View</a>';
            })
            ->addColumn('ending_attachment', function ($t) {
                $images = [];

                if (!empty($t->ending_attachment)) {
                    $images = json_decode($t->ending_attachment, true);
                }

                if (empty($images)) {
                    return '-';
                }

                return '<a href="javascript:void(0);" data-type="Ending" class="view-images" data-images=\'' . json_encode($images) . '\'>View</a>';
            })
            ->rawColumns(['starting_attachment', 'ending_attachment'])
            ->make(true);
    }

    public function getEmployeesAjax(Request $request)
    {
        $query = \App\Models\Employee::query();

        if ($request->employee_type) {
            $query->where('employee_type_id', $request->employee_type);
        }

        if ($request->q) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $employees = $query->limit(20)->get(['id', 'name']);

        return response()->json($employees);
    }


}
