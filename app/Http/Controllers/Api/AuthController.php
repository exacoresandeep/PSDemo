<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\AssignRoute;
use App\Models\DealerRouteAssignment;
use App\Models\ProductType;
use App\Models\PaymentTerms;
use App\Models\ProductDetails; 
use App\Models\LeaveType;
use App\Models\VehicleCategory;
use App\Models\VehicleType;
use App\Models\Regions;
use App\Models\CreditDays;
use App\Models\Scheme;
use App\Models\Price;
use App\Models\District;
use App\Models\Brands;
use App\Models\DealerVisitPurpose;
use App\Models\DealerActivityItem;
use App\Models\LeadType;
use App\Models\TypeOfInfluencer;
use App\Models\InfluencerVisitPurpose;
use App\Models\InfluencerVisitStatus;
use App\Models\LostToCompetitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;
use App\Services\FirebasePushService;
use Carbon\Carbon;

class AuthController extends Controller
{
	public function testPush(FirebasePushService $fcm)
	{
    $deviceToken = 'cPqleuyBSS2jtWcW0S3pt1:APA91bHHEZlG8ZVFlMDf6Hw_kOLAHnKL9PPB3g6jq1CxNKh3_24Jh9BiRjX5XL5PPDV5R4N2_vDzTD4QjWf2_iEOyUl8YqMHMwGN8iQpHVCpgfLZI2PvkmQ';
    $title = 'Hello';
    $body = 'This is a push from Laravel';

    return $fcm->sendNotification($deviceToken, $title, $body);
	}
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string',
                'password' => 'required|string',
                'employee_type_id' => 'required|integer',
            ]);

            $employee = Employee::join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
            ->where('employee_code', $validated['employee_code'])
            ->where('employees.employee_type_id', $validated['employee_type_id'])
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
		    'password_reset_flag' =>$employee->password_reset_flag == 0 ? false : true,
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


    public function loginCommon(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string',  
                'password' => 'required|string',
            ]);
            $employee = Employee::join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
                ->where('employee_code', $validated['employee_code'])
                ->select('employees.*', 'employee_types.id as type_id', 'employee_types.type_name')
                ->first();
            if ($employee && Hash::check($validated['password'], $employee->password)) {

                $token = $employee->createToken('Employee API Token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Login successful',
                    'data' => [
                        'role' => 'Employee',
                        'employee' => [
                            'id' => $employee->id,
                            'employee_code' => $employee->employee_code,
                            'name' => $employee->name,
                            'designation' => $employee->designation,
                            'email' => $employee->email,
                            'password_reset_flag' => $employee->password_reset_flag ? true : false,
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
            }

            
            $dealer = Dealer::where('dealer_code', $validated['employee_code'])
                ->where('status', '1')
                ->first();

            if ($dealer && Hash::check($validated['password'], $dealer->password)) {

                $token = $dealer->createToken('Dealer API Token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Login successful',
                    'data' => [
                        'role' => 'Dealer',
                        'dealer' => [
                            'id' => $dealer->id,
                            'dealer_code' => $dealer->dealer_code,
                            'name' => $dealer->dealer_name,
                            'email' => $dealer->email,
                            'password_reset_flag' => $dealer->password_reset_flag ? true : false,
                            'phone' => $dealer->phone,
                            'address' => $dealer->address,
                        ],
                        'token' => $token,
                        'status' => 'active',
                    ],
                ], 200);
            }

            // ============================================
            // 3. INVALID CREDENTIALS (Both Failed)
            // ============================================
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Invalid credentials',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

	// Hardcoded or dynami
    public function resetPassword(Request $request)
    { 
      $validated=  $request->validate([
            'current_password' => 'required',
            'new_password' => 'required', 
        ]);
    try{
        $user = Auth::user();
        
        if(!Hash::check($request->current_password,$user->password)){
        //if ($user->password !== md5($request->current_password)) {
            return response()->json([
    		'status' => false,
    		'statusCode'=>400,
                'message' => 'Current password is incorrect.',
            ], 400);
        }
    
        // Update password and flag
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
public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
        'type' => 'required|string' // dealer or employee
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

        if ($request->type === "dealer") {
            Dealer::where("id", $user->id)->update(["fcm_token" => $fcmToken]);
        } elseif ($request->type === "employee") {
            Employee::where("id", $user->id)->update(["fcm_token" => $fcmToken]);
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
//     public function notificationList(){
//         try{
//             $user = Auth::user();
//             if (!$user) {
//                 return response()->json([
//                     'status' => 'error',
//                     'success' => false,
//                     'statusCode' => 401,
//                     'message' => 'User not authenticated.'
//                 ], 401);
//             }
    
//         $notifications = collect();
//         $today = now()->toDateString();
// 	    if ($user->dealer_code && !$user->employee_code) {
	       
//             $dealerId = DB::table('dealers')->where('dealer_code', $user->dealer_code)->value('id');

//             $orders = DB::table('orders')
//                 ->where('dealer_id', $dealerId)
//                 ->selectRaw("
//                     'orders' as type, id,
//                     CASE
//                         // WHEN dealer_flag_order = '0' THEN CONCAT('Order request has been created by ', (SELECT name FROM employees WHERE id = created_by))
//                         WHEN dealer_flag_order = '0' THEN 
//                         CASE 
//                             WHEN created_by IS NOT NULL THEN 
//                                 CONCAT('Order request has been created by ', (SELECT name FROM employees WHERE id = created_by))
//                             ELSE 
//                                 'Order request has been created'
//                         END
//                         WHEN dealer_flag_order = '1' AND status = 'Approved' THEN 'Your Order request is approved by ASO'
//                         WHEN dealer_flag_order = '1' AND status = 'Rejected' THEN 'Your Order request is rejected by ASO'
//                         ELSE 'SE assigned an Order'
//                     END as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                     DATE_FORMAT(created_at, '%h:%i %p') as time
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool)$item->is_read;
//                     return $item;
//                 });
                
                
//             $payments = DB::table('outstanding_payments')
//                 ->where('dealer_id', $dealerId)
//                 ->where('outstanding_amount', '>', 0)
//                 ->whereDate('due_date', '<=', now())
//                 ->selectRaw("
//                     'outstanding_payments' as type, id,
//                     'Outstanding payment is due' as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(due_date, '%d-%m-%Y') as date
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool) $item->is_read;
//                     return $item;
//                 });

//             $notifications = $notifications->merge($orders)->merge($payments);
          
//         }

// 	    if ($user->employee_code && !$user->dealer_code) {

//             $employeeId = $user->id;

//             $today = now()->toDateString();

//             // Rescheduled routes for today
//             $routes = DB::table('rescheduled_routes')
//                 ->where('employee_id', $employeeId)
//                 ->whereDate('assign_date', $today)
//                 ->selectRaw("'rescheduled_routes' as type, id, 
//                     CONCAT('Today\'s rescheduled route is ', route_name) as notification_message, 
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(assign_date, '%d-%m-%Y') as date,
//                     DATE_FORMAT(assign_date, '%h:%i %p') as time")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool) $item->is_read;
//                     return $item;
//                 });

//             $activities = DB::table('activities')
//                 ->where('employee_id', $employeeId)
//                 ->where(function($query) use ($today) {
//                     $query->whereDate('assigned_date', $today)
//                           ->orWhereDate('due_date', $today);
//                 })
//                 ->selectRaw("
//                     'activities' as type,
//                     id,
//                     CASE 
//                         WHEN DATE(assigned_date) = ? THEN 'New Activity is assigned to you'
//                         WHEN DATE(due_date) = ? THEN 'Activity is due today'
//                         ELSE ''
//                     END as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                     DATE_FORMAT(created_at, '%h:%i %p') as time
//                 ", [$today, $today])
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool) $item->is_read;
//                     return $item;
//                 });

//              $employeeOrders = DB::table('orders')
//                 ->where('created_by', $employeeId)
//                 ->where('dealer_flag_order', '0')
//                 ->where('status', '!=', 'Pending')
//                 ->selectRaw("
//                     'Eorders' as type, id,
//                     CASE
//                         WHEN status = 'Accepted' THEN CONCAT('Your order request is accepted by Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
//                         WHEN status = 'Rejected' THEN CONCAT('Your order request is rejected by Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
//                         ELSE ''
//                     END as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                     DATE_FORMAT(created_at, '%h:%i %p') as time
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool)$item->is_read;
//                     return $item;
//                 });

//             $dealerOrders = DB::table('orders')
//                 ->where('dealer_flag_order', '1')
//                 ->where(function ($q) use ($employeeId) {
//                     $q->whereIn('dealer_id', function ($sub) use ($employeeId) {
//                         $sub->select('dealer_id')->from('assigned_routes')->where('employee_id', $employeeId);
//                     });
//                 })
//                 ->selectRaw("
//                     'Dorders' as type, id,
//                     CASE
//                         WHEN status = 'Pending' AND send_for_approval = '0' THEN CONCAT('You have received an order request from Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
//                         WHEN status = 'Pending' AND send_for_approval = '1' THEN CONCAT('You have received an order request from ASO ', (SELECT name FROM employees WHERE id = created_by))
//                         WHEN status = 'Accepted' AND send_for_approval = '1' THEN CONCAT('Order from Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id), ' is approved by ASO ', (SELECT name FROM employees WHERE id = created_by))
//                         ELSE ''
//                     END as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                     DATE_FORMAT(created_at, '%h:%i %p') as time
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool)$item->is_read;
//                     return $item;
//                 });

//             $accountApprovals = DB::table('orders')
//                 ->where('created_by', $employeeId)
//                 ->where('dealer_flag_order', '0')
//                 ->whereIn('created_by', function ($sub) {
//                     $sub->select('id')->from('employees')->whereIn('employee_type_id', [2, 3, 4, 5]);
//                 })
//                 ->whereIn('order_approved', ['1', '2'])
//                 ->selectRaw("
//                     'Aorders' as type, id,
//                     CASE
//                         WHEN order_approved = '1' THEN 'Your order is approved by Accounts.'
//                         WHEN order_approved = '2' THEN 'Your order is rejected by Accounts.'
//                         ELSE ''
//                     END as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                     DATE_FORMAT(created_at, '%h:%i %p') as time
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool)$item->is_read;
//                     return $item;
//                 });

                
//             $targets = DB::table('targets')
//                 ->where('employee_id', $employeeId)
//                 ->selectRaw("'targets' as type, id, 
//                              'New Target is assigned to you' as notification_message, 
//                              notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                              DATE_FORMAT(created_at, '%d-%m-%Y') as date,
//                              DATE_FORMAT(created_at, '%h:%i %p') as time")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool) $item->is_read;
//                     return $item;
//                 });
                


//             $leads = DB::table('lead_follow_ups')
//                 ->whereDate('follow_up_date', $today)
//                 ->where('created_by', $employeeId)
//                 ->selectRaw("
//                     'leads' as type,
//                     lead_id as id,
//                     'You have a lead to follow up' as notification_message,
//                     notification_status,
//                     IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
//                     IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
//                     DATE_FORMAT(follow_up_date, '%d-%m-%Y') as date,
//                     DATE_FORMAT(follow_up_date, '%h:%i %p') as time
//                 ")
//                 ->get()
//                 ->map(function ($item) {
//                     $item->is_read = (bool) $item->is_read;
//                     return $item;
//                 });

//             $notifications = collect()
//                 ->merge($routes)
//                 ->merge($activities)
//                 ->merge($employeeOrders)
//                 ->merge($dealerOrders)
//                 ->merge($accountApprovals)
//                 ->merge($targets)
//                 ->merge($leads);
//         }

//           $unreadCount = $notifications->where('is_read', false)->count();
// // dd($unreadCount);
//             return response()->json([
//                 'success' => true,
//                 'statusCode' => 200,
//                 'data' => [
//                     'notifications' => $notifications,
//                     'unread_count' => $unreadCount
//                 ],
//                 'message' => 'Notification list.'
//             ]);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'statusCode' => 500,
//                 'message' => $e->getMessage(),
//             ], 500);
//         }
//     }
public function notificationList()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $notifications = collect();
            $today = now()->toDateString();
            if ($user->dealer_code && !$user->employee_code) {

                $dealerId = DB::table('dealers')->where('dealer_code', $user->dealer_code)->value('id');

                $orders = DB::table('orders')
                    ->where('dealer_id', $dealerId)
                    ->where(function ($query) {
                        $query->where('dealer_flag_order', '1')
                            ->orWhere(function ($subQuery) {
                                $subQuery->where('dealer_flag_order', '0')
                                    ->whereNotNull('created_by');
                            });
                    })
                    ->selectRaw("
                    'orders' as type, id,
                    CASE
                        WHEN dealer_flag_order = '0' THEN 
                            CONCAT('Order request has been created by ', (SELECT name FROM employees WHERE id = created_by))
                        WHEN dealer_flag_order = '1' AND status = 'Approved' THEN 'Your Order request is approved by ASO'
                        WHEN dealer_flag_order = '1' AND status = 'Rejected' THEN 'Your Order request is rejected by ASO'
                        ELSE 'SE assigned an Order'
                    END as notification_message,
                    notification_status,
                    IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                    IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                    DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                    DATE_FORMAT(created_at, '%h:%i %p') as time
                ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool)$item->is_read;
                        return $item;
                    });


                $payments = DB::table('outstanding_payments')
                    ->where('dealer_id', $dealerId)
                    ->where('outstanding_amount', '>', 0)
                    ->whereDate('due_date', '<=', now())
                    ->selectRaw("
                        'outstanding_payments' as type, id,
                        'Outstanding payment is due' as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(due_date, '%d-%m-%Y') as date,
                        DATE_FORMAT(due_date, '%h:%i %p') as time
                    ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool) $item->is_read;
                        return $item;
                    });

                $notifications = $notifications->merge($orders)->merge($payments);
            }

            if ($user->employee_code && !$user->dealer_code) {

                $employeeId = $user->id;

                $today = now()->toDateString();

                $routes = DB::table('rescheduled_routes')
                    ->where('employee_id', $employeeId)
                    ->whereDate('assign_date', $today)
                    ->selectRaw("'rescheduled_routes' as type, id, 
                        CONCAT('Today\'s rescheduled route is ', route_name) as notification_message, 
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(assign_date, '%d-%m-%Y') as date,
                        DATE_FORMAT(assign_date, '%h:%i %p') as time")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool) $item->is_read;
                        return $item;
                    });

                $activities = DB::table('activities')
                    ->where('employee_id', $employeeId)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('assigned_date', $today)
                            ->orWhereDate('due_date', $today);
                    })
                    ->selectRaw("
                        'activities' as type,
                        id,
                        CASE 
                            WHEN DATE(assigned_date) = ? THEN 'New Activity is assigned to you'
                            WHEN DATE(due_date) = ? THEN 'Activity is due today'
                            ELSE ''
                        END as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                        DATE_FORMAT(created_at, '%h:%i %p') as time
                    ", [$today, $today])
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool) $item->is_read;
                        return $item;
                    });

                $employeeOrders = DB::table('orders')
                    ->where('created_by', $employeeId)
                    ->where('dealer_flag_order', '0')
                    ->where('status', '!=', 'Pending')
                    ->selectRaw("
                        'Eorders' as type, id,
                        CASE
                            WHEN status = 'Accepted' THEN CONCAT('Your order request is accepted by Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
                            WHEN status = 'Rejected' THEN CONCAT('Your order request is rejected by Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
                            ELSE ''
                        END as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                        DATE_FORMAT(created_at, '%h:%i %p') as time
                    ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool)$item->is_read;
                        return $item;
                    });

                $dealerOrders = DB::table('orders')
                    ->where('dealer_flag_order', '1')
                    ->where(function ($q) use ($employeeId) {
                        $q->whereIn('dealer_id', function ($sub) use ($employeeId) {
                            $sub->select('dealer_id')->from('assigned_routes')->where('employee_id', $employeeId);
                        });
                    })
                    ->selectRaw("
                        'Dorders' as type, id,
                        CASE
                            WHEN status = 'Pending' AND send_for_approval = '0' THEN CONCAT('You have received an order request from Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id))
                            WHEN status = 'Pending' AND send_for_approval = '1' THEN CONCAT('You have received an order request from ASO ', (SELECT name FROM employees WHERE id = created_by))
                            WHEN status = 'Accepted' AND send_for_approval = '1' THEN CONCAT('Order from Dealer ', (SELECT dealer_name FROM dealers WHERE id = dealer_id), ' is approved by ASO ', (SELECT name FROM employees WHERE id = created_by))
                            ELSE ''
                        END as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                        DATE_FORMAT(created_at, '%h:%i %p') as time
                    ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool)$item->is_read;
                        return $item;
                    });

                $accountApprovals = DB::table('orders')
                    ->where('created_by', $employeeId)
                    ->where('dealer_flag_order', '0')
                    ->whereIn('created_by', function ($sub) {
                        $sub->select('id')->from('employees')->whereIn('employee_type_id', [2, 3, 4, 5]);
                    })
                    ->whereIn('order_approved', ['1', '2'])
                    ->selectRaw("
                        'Aorders' as type, id,
                        CASE
                            WHEN order_approved = '1' THEN 'Your order is approved by Accounts.'
                            WHEN order_approved = '2' THEN 'Your order is rejected by Accounts.'
                            ELSE ''
                        END as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                        DATE_FORMAT(created_at, '%h:%i %p') as time
                    ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool)$item->is_read;
                        return $item;
                    });


                $targets = DB::table('targets')
                    ->where('employee_id', $employeeId)
                    ->selectRaw("'targets' as type, id, 
                                'New Target is assigned to you' as notification_message, 
                                notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                                DATE_FORMAT(created_at, '%d-%m-%Y') as date,
                                DATE_FORMAT(created_at, '%h:%i %p') as time")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool) $item->is_read;
                        return $item;
                    });



                $leads = DB::table('lead_follow_ups')
                    ->whereDate('follow_up_date', $today)
                    ->where('created_by', $employeeId)
                    ->selectRaw("
                        'leads' as type,
                        lead_id as id,
                        'You have a lead to follow up' as notification_message,
                        notification_status,
                        IF(notification_status IN ('opened', 'approved'), 1, 0) as is_read,
                        IF(notification_status IN ('opened', 'approved'), 'read', 'unread') as read_class,
                        DATE_FORMAT(follow_up_date, '%d-%m-%Y') as date,
                        DATE_FORMAT(follow_up_date, '%h:%i %p') as time
                    ")
                    ->get()
                    ->map(function ($item) {
                        $item->is_read = (bool) $item->is_read;
                        return $item;
                    });

                // $notifications = collect()
                //     ->merge($routes)
                //     ->merge($activities)
                //     ->merge($employeeOrders)
                //     ->merge($dealerOrders)
                //     ->merge($accountApprovals)
                //     ->merge($targets)
                //     ->merge($leads);
                if ($user->employee_type_id == 7) {
                    $inspectionTypes = ['Pre Trip', 'Post Trip', 'Post Service'];
                    $inspectionNotifications = collect();

                    // loop through vehicles (assuming inspection user can see all vehicles)
                    $vehicles = DB::table('vehicles')->select('id', 'vehicle_no')->get();

                    foreach ($vehicles as $vehicle) {
                        foreach ($inspectionTypes as $type) {
                            $lastInspection = DB::table('vehicle_inspections')
                                ->where('vehicle_id', $vehicle->id)
                                ->where('inspection_type', $type)
                                ->latest('inspection_date')
                                ->first();

                            if ($lastInspection) {
                                $dueDate = Carbon::parse($lastInspection->inspection_date)->addDays(30);
                                $daysLeft = now()->diffInDays($dueDate, false);

                                if ($daysLeft > 3) {
                                    $msg = "The vehicle {$vehicle->vehicle_no} is ready for {$type} inspection.";
                                } elseif ($daysLeft > 0) {
                                    $msg = "The vehicle {$vehicle->vehicle_no} is pending for {$type} inspection in {$daysLeft} days.";
                                } else {
                                    $msg = "The vehicle {$vehicle->vehicle_no} is overdue for {$type} inspection.";
                                }
                            } else {
                                $msg = "The vehicle {$vehicle->vehicle_no} is ready for {$type} inspection.";
                            }

                            $inspectionNotifications->push((object)[
                                'type' => 'inspection',
                                'id' => uniqid(),
                                'notification_message' => $msg,
                                'notification_status' => 'unread',
                                'is_read' => false,
                                'read_class' => 'unread',
                                'date' => now()->format('d-m-Y'),
                                'time' => now()->format('h:i A')
                            ]);
                        }
                    }

                    // merge with other employee notifications
                    $notifications = collect()
                        ->merge($routes)
                        ->merge($activities)
                        ->merge($employeeOrders)
                        ->merge($dealerOrders)
                        ->merge($accountApprovals)
                        ->merge($targets)
                        ->merge($leads)
                        ->merge($inspectionNotifications);
                } else if($user->employee_type_id == 8){
                     $notifications = collect();

                        // 🔹 1. Assistance Notifications
                        $assistances = DB::table('assistances')
                            ->join('trips', 'assistances.trip_id', '=', 'trips.id')
                            ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                            ->selectRaw("
                                'assistance' as type,
                                assistances.id,
                                CASE 
                                    WHEN DATEDIFF(NOW(), assistances.support_date) >= 3 
                                        THEN CONCAT('Vehicle ', vehicles.vehicle_no, ' request pending for ', DATEDIFF(NOW(), assistances.support_date), ' days. Please check immediately.')
                                    ELSE CONCAT('Vehicle ', vehicles.vehicle_no, ' reported a breakdown. Please check immediately.')
                                END as notification_message,
                                assistances.status as notification_status,
                                IF(assistances.status IN ('Opened', 'Approved'), 1, 0) as is_read,
                                IF(assistances.status IN ('Opened', 'Approved'), 'read', 'unread') as read_class,
                                DATE_FORMAT(assistances.support_date, '%d-%m-%Y') as date,
                                DATE_FORMAT(assistances.support_date, '%h:%i %p') as time
                            ")
                            ->where('assistances.status', '!=', 'Closed')
                            ->get()
                            ->map(function ($item) {
                                $item->is_read = (bool)$item->is_read;
                                return $item;
                            });
                    
                        $notifications = $notifications->merge($assistances);
                    
                    
                        // 🔹 2. Service Alerts
                        $serviceAlerts = DB::table('vehicles')
                            ->leftJoin('trips', 'vehicles.id', '=', 'trips.vehicle_id')
                            ->select('vehicles.id', 'vehicles.vehicle_no', 'vehicles.last_service_date', 'vehicles.last_service_km', 'vehicles.inspection_km', 'vehicles.service_days', 'trips.status as trip_status', 'trips.delivery_date', 'trips.assign_date')
                            ->get()
                            ->flatMap(function ($vehicle) {
                                $notifications = collect();
                                $today = now();
                    
                                if (!is_null($vehicle->last_service_km) && !is_null($vehicle->inspection_km)) {
                                    $nextKmDue = $vehicle->last_service_km + 1000;
                                    $pendingKm = $nextKmDue - $vehicle->inspection_km;
                    
                                    if ($pendingKm <= 0) {
                                        $notifications->push((object)[
                                            'type' => 'service',
                                            'id' => $vehicle->id, // ✅ vehicle id
                                            'notification_message' => "Service Alert: Vehicle {$vehicle->vehicle_no} has been pending for 3 days. Please check immediately.",
                                            'notification_status' => 'unread',
                                            'is_read' => false,
                                            'read_class' => 'unread',
                                            'date' => $today->format('d-m-Y'),
                                            'time' => $today->format('h:i A'),
                                        ]);
                                    } else {
                                        $notifications->push((object)[
                                            'type' => 'service',
                                            'id' => $vehicle->id, // ✅ vehicle id
                                            'notification_message' => "Service Update: Vehicle {$vehicle->vehicle_no} is ready for service. Please check immediately.",
                                            'notification_status' => 'unread',
                                            'is_read' => false,
                                            'read_class' => 'unread',
                                            'date' => $today->format('d-m-Y'),
                                            'time' => $today->format('h:i A'),
                                        ]);
                                    }
                                }
                    
                                if ($vehicle->trip_status === 'Completed' && $vehicle->delivery_date) {
                                    $pendingDays = $today->diffInDays(\Carbon\Carbon::parse($vehicle->delivery_date), false);
                                    if ($pendingDays < 0) {
                                        $notifications->push((object)[
                                            'type' => 'service',
                                            'id' => $vehicle->id, // ✅ vehicle id
                                            'notification_message' => "Service Alert: Vehicle {$vehicle->vehicle_no} has been pending for " . abs($pendingDays) . " days. Please check immediately.",
                                            'notification_status' => 'unread',
                                            'is_read' => false,
                                            'read_class' => 'unread',
                                            'date' => $today->format('d-m-Y'),
                                            'time' => $today->format('h:i A'),
                                        ]);
                                    }
                                }
                    
                                return $notifications;
                            });
                    
                        $notifications = $notifications->merge($serviceAlerts);
                    
                    
                        // 🔹 3. Inspection → Maintenance Notifications
                        $inspectionNotifications = DB::table('vehicle_inspections as vi')
                            ->join('vehicles as v', 'vi.vehicle_id', '=', 'v.id')
                            ->leftJoin('vehicle_service_maintenances as vsm', 'vi.id', '=', 'vsm.inspection_id')
                            ->where('vi.status', 'Opened')
                            ->whereNull('vsm.id')
                            ->selectRaw("
                                'inspection_maintenance' as type,
                                vi.id, -- ✅ inspection id
                                CASE
                                    WHEN vi.inspection_type = 'Pre Trip' THEN CONCAT('Vehicle ', v.vehicle_no, ' is ready for maintenance after pre-trip inspection. Please check immediately.')
                                    WHEN vi.inspection_type = 'Regular' THEN CONCAT('Vehicle ', v.vehicle_no, ' is ready for maintenance after regular inspection. Please check immediately.')
                                    WHEN vi.inspection_type = 'Post Trip' THEN CONCAT('Vehicle ', v.vehicle_no, ' is ready for maintenance after post-trip inspection. Please check immediately.')
                                    ELSE CONCAT('Vehicle ', v.vehicle_no, ' is ready for maintenance after inspection. Please check immediately.')
                                END as notification_message,
                                vi.notification_status,
                                IF(vi.notification_status IN ('opened','approved'), 1, 0) as is_read,
                                IF(vi.notification_status IN ('opened','approved'), 'read','unread') as read_class,
                                DATE_FORMAT(vi.inspection_date, '%d-%m-%Y') as date,
                                DATE_FORMAT(vi.inspection_date, '%h:%i %p') as time
                            ")
                            ->get()
                            ->map(function ($item) {
                                $item->is_read = (bool)$item->is_read;
                                return $item;
                            });
                    
                        $notifications = $notifications->merge($inspectionNotifications);
                } else {
                    // normal employee (not inspection user)
                    $notifications = collect()
                        ->merge($routes)
                        ->merge($activities)
                        ->merge($employeeOrders)
                        ->merge($dealerOrders)
                        ->merge($accountApprovals)
                        ->merge($targets)
                        ->merge($leads);
                }
            }
            
        // ===============================
        // Driver Notifications
        // ===============================
        if (!$user->dealer_code && !$user->employee_code) {
            $driverId = $user->id;

            // ✅ Maintenance notifications (JOIN trips via assistances)
            $maintenanceNotifications = DB::table('maintenance_reports')
                ->join('assistances', 'maintenance_reports.assistance_id', '=', 'assistances.id')
                ->join('trips', 'assistances.trip_id', '=', 'trips.id')
                ->where('trips.driver_id', $driverId)
                ->where('maintenance_reports.status', 'Completed')
                ->selectRaw("
                    'maintenance' as type,
                    maintenance_reports.id,
                    'Maintenance completed. Now restart your trip.' as notification_message,
                    maintenance_reports.notification_status,
                    IF(maintenance_reports.notification_status IN ('opened','approved'), 1, 0) as is_read,
                    IF(maintenance_reports.notification_status IN ('opened','approved'), 'read','unread') as read_class,
                    DATE_FORMAT(maintenance_reports.updated_at, '%d-%m-%Y') as date,
                    DATE_FORMAT(maintenance_reports.updated_at, '%h:%i %p') as time
                ")
                ->get()
                ->map(function ($item) {
                    $item->is_read = (bool)$item->is_read;
                    return $item;
                });

            // ✅ Trip notifications
            $tripNotifications = DB::table('trips')
                ->where('driver_id', $driverId)
                ->where('status', 'Completed')
                ->selectRaw("
                    'trip' as type,
                    id,
                    'Trip completed. You have reached the garage.' as notification_message,
                    notification_status,
                    IF(notification_status IN ('opened','approved'), 1, 0) as is_read,
                    IF(notification_status IN ('opened','approved'), 'read','unread') as read_class,
                    DATE_FORMAT(updated_at, '%d-%m-%Y') as date,
                    DATE_FORMAT(updated_at, '%h:%i %p') as time
                ")
                ->get()
                ->map(function ($item) {
                    $item->is_read = (bool)$item->is_read;
                    return $item;
                });

            $notifications = $notifications->merge($maintenanceNotifications)->merge($tripNotifications);
        }

            $unreadCount = $notifications->where('is_read', false)->count();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount
                ],
                'message' => 'Notification list.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
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

    public function getCustomerTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = CustomerType::select('id as customer_type_id', 'name as customer_type_name');

                if ($user->employee_type_id == 1) {
                    $query->where('name', '!=', 'Dealer');
                }

                $data = $query->orderBy('name', 'asc')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Customer types fetched successfully',
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

    public function getOrderTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = OrderType::select('id as order_type_id', 'name as order_type_name')->get();
            } else {
                $data = [];
            }
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order types fetched successfully',
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
    public function getDealerVisitPurposes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = DealerVisitPurpose::select('id as purpose_id', 'purpose');
                $data = $query->where('status', '1')
                            ->orderBy('purpose', 'asc')
                            ->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer visit purposes fetched successfully',
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
    public function getDealerActivityItem()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = DealerActivityItem::select('id as item_id', 'type');
                $data = $query->where('status', '1')
                            ->orderBy('type', 'asc')
                            ->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer activity items fetched successfully',
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
    public function getBrands()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = Brands::select('id as brand_id', 'name');
                $data = $query->where('status', '1')
                            ->orderBy('name', 'asc')
                            ->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Brands fetched successfully',
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
    public function getLostToCompetitor()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = LostToCompetitor::select('id as competitor_id', 'name')
                    ->where('status', '1')
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lost to Competitor list fetched successfully',
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
    public function getASOByDealerID(Request $request)
    {
        try {
            $dealerId = $request->dealer_id;

            $dealer = Dealer::find($dealerId);

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found'
                ], 404);
            }

            $assignedRouteIds = DealerRouteAssignment::where('dealer_id', $dealerId)
                                    ->pluck('assign_route_id');

            if ($assignedRouteIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No assigned routes found for this dealer',
                    'data' => []
                ]);
            }

            $employeeIds = AssignRoute::whereIn('id', $assignedRouteIds)
                            ->where('employee_type_id', 2)
                            ->pluck('employee_id');

            if ($employeeIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No ASO found for this dealer',
                    'data' => []
                ]);
            }

            $employees = Employee::whereIn('id', $employeeIds)
                            ->get(['id', 'name', 'email', 'phone', 'employee_code']);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'ASO fetched successfully',
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTypeofInfluencer()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = TypeOfInfluencer::select('id as influencer_type_id', 'name')
                            ->where('status', '1')
                            ->orderBy('name', 'asc')
                            ->get();
            } else {
                $data = [];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Influencer types fetched successfully',
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
    public function getInfluencerPurposeofVisit()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = DealerVisitPurpose::select('id as purpose_id', 'purpose')
                            ->where('status', '1')
                            ->orderBy('purpose', 'asc')
                            ->get();
            } else {
                $data = [];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Influencer visit purposes fetched successfully',
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
    public function getLeadType()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = LeadType::select('id as lead_type_id', 'type')
                            ->where('status', '1')
                            ->orderBy('type', 'asc')
                            ->get();
            } else {
                $data = [];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead types fetched successfully',
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
    public function getInfluencerVisitStatus()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = InfluencerVisitStatus::select('status')
                            ->orderBy('id', 'asc')
                            ->get();
            } else {
                $data = [];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Status fetched successfully',
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


    public function getTest(Request $request) {
        try {
            $dsn = 'odbc:HANAODBC';
            $username = 'INDUS';
            $password = 'Indus@123';
    
            $pdo = new PDO($dsn, $username, $password);
    $docNum = '10205254';
           $stmt = $pdo->prepare('CALL "MOBILE_APPLICATION_TEST"."MobileApp_CreditNote_Detail"()');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    dd($results);
    
        } catch (\PDOException $e) {
            dd('PDO Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            dd('General Error: ' . $e->getMessage());
        }
    }

    public function getDealers(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $user->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRouteIds) && $user->employee_type_id == 5) {
                $query = Dealer::query()->where('status', '1');
            }
            elseif (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No assigned dealers found for this employee.',
                    'data' => [],
                ]);
            } else {
                $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                    ->pluck('dealer_id')
                    ->unique()
                    ->toArray();

                $query = Dealer::query()
                    ->whereIn('id', $dealerIds)
                    ->where('status', '1');
            }

            if ($request->has('search_key') && !empty($request->search_key)) {
                $searchKey = $request->search_key;
                $query->where(function ($q) use ($searchKey) {
                    $q->where('dealer_code', 'like', '%' . $searchKey . '%')
                        ->orWhere('dealer_name', 'like', '%' . $searchKey . '%');
                });
            }

            $dealers = $query->select(
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
                ->orderBy('dealer_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealers fetched successfully.',
                'data' => $dealers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    // Fetch Products
    public function getProducts()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated',
                    'data' => [],
                ], 401);
            }

            $productIds = json_decode($user->products, true);

            if (empty($productIds) || !is_array($productIds)) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No products assigned to this employee',
                    'data' => [],
                ], 200);
            }

            $data = \App\Models\Product::whereIn('id', $productIds)
                ->select('id as product_id', 'product_name')
                ->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Products fetched successfully',
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
    public function getProductTypes(Request $request)
    {
        try {
            $user = Auth::user();
            $productId = $request->input('product_id');
        
            if ($user !== null) {
                if ($productId == 0) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'Invalid product_id provided.',
                        'data' => []
                    ], 400);
                }
                $query = ProductType::select('product_id', 'id as product_type_id', 'type_name', 'rate');

                if ($productId) {
                    $query->where('product_id', $productId);
                }
                
                $data = $query->get();

                if ($data->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => 'No product types found for the given product_id.',
                    ], 404);
                }

            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Product types fetched successfully',
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
    // public function getPriceByProduct($product_type_id, $product_id)
    // {
    //     try {
    //         $price = Price::where('product_type_id', $product_type_id)
    //             ->where('product_id', $product_id)
    //             ->where('status', '1')
    //             ->orderByDesc('id') 
    //             ->first();

    //         if (!$price) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'No active price found for this product and product type.'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Product price fetched successfully',
    //             'data' => [
    //                 'dp_price' => (float) $price->dealer_price,
    //                 'adp_price' => (float) $price->advance_dealer_price,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => 'Something went wrong while fetching product price.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function getPriceByProduct(Request $request)
    {
        $product_type_id = $request->product_type_id;
        $product_id = $request->product_id;

        try {

            $price = Price::where('product_type_id', $product_type_id)
                ->where('product_id', $product_id)
                ->where('status', '1')
                ->orderByDesc('id')
                ->first();

            $productDetails = ProductDetails::where('product_id', $product_id)
                ->where('type_id', $product_type_id)
                ->first();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Product details fetched successfully',
                'data' => [
                    'dp_price'          => $price ? (float)$price->dealer_price : null,
                    'adp_price'         => $price ? (float)$price->advance_dealer_price : null,
                    'tonnage_per_piece' => $productDetails ? (float)$productDetails->weight : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductRate(Request $request)
    {
        try {

            $user = Auth::user();

            if ($user !== null) {
                $validated = $request->validate([
                    'product_type_id' => 'required|exists:product_types,id',
                    'product_id' => 'required|exists:products,id',
                ]);
    
                $data = DB::table('products_details')
                ->join('product_types', 'products_details.type_id', '=', 'product_types.id')
                ->select('products_details.rate', 'product_types.type_name')
                ->where('products_details.product_id', $validated['product_id']) 
                ->where('products_details.type_id', $validated['product_type_id'])
                ->first();

                if (!$data) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'Product rate not found',
                    ], 400);
                }
                
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Rate fetched successfully',
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
    public function getLeaveTypes()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = LeaveType::select('id as leave_type_id', 'name as leave_type', 'status as leave_type_status')->get();
            } else {
                $data = [];
            }
            
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave types fetched successfully',
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
    public function getPaymentTerms()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = PaymentTerms::select('id as payment_terms_id', 'name as payment_terms_name')->get();
            } else {
                $data = [];
            }
            
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Payment terms fetched successfully',
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
    public function getCreditDays()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = CreditDays::where('status', '1')
                ->select('id as days_id', 'days as credit_day')->get();
            } else {
                $data = [];
            }
          
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Credit Days fetched successfully',
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
    public function getScheme(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $query = Scheme::where('status', '1')
                ->select('id as scheme_id', 'scheme', 'product_id');

            if ($request->has('product_id') && !empty($request->product_id)) {
                $query->where('product_id', $request->product_id);
            }

            $data = $query->get();
          
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Scheme fetched successfully',
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
            // $path = $file->store('uploads', 'public');  
            // $fileUrls[] = asset('storage/' . $path);
            $fileName = $file->hashName();

            $file->storeAs('uploads', $fileName, 'public');  

            $fileUrls[] = url('storage/uploads/' . $fileName);
        }
        // $fileUrls = [];
        // foreach ($request->file('file') as $file) {
        //     $fileName = time() . '_' . $file->getClientOriginalName();

        //     $file->move(public_path('uploads'), $fileName);

        //     $fileUrls[] = url('uploads/' . $fileName);
        // }
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Files uploaded successfully.',
            'data' => ['filePaths' => $fileUrls],
        ], 200);
    }
    
    public function getVehicleCategory()
    {
        try {
            $user = Auth::user(); 

            if ($user) {
                $data = VehicleCategory::select('id as vehicle_category_id', 'vehicle_category_name')
                    ->get();
            } else {
                $data = []; 
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle Category fetched successfully',
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
    public function getVehicleTypeByCategory(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user) {
                $validated = $request->validate([
                    'vehicle_category_id' => 'required|integer',
                ]);

                $data = VehicleType::where('vehicle_category_id', $validated['vehicle_category_id'])
                    ->select('id as vehicle_type_id', 'vehicle_type_name')
                    ->get();
            } else {
                $data = []; 
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle Types fetched successfully',
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

    public function trackOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'statusCode' => 422,
                'data' => [],
                'success' => 'error',
            ], 422);
        }
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = Order::select('id', 'status', 'created_at as pending_time', 'accepted_time', 'rejected_time', 'dispatched_time', 'intransit_time', 'delivered_time')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Track order fetched successfully',
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


    //Common Filter

    public function getFilteredOrders(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access',
                    'data' => []
                ], 401);
            }

            $query = Order::join('dealers', 'orders.dealer_id', '=', 'dealers.id')
                ->select(
                    'orders.id as order_id',
                    'orders.created_at',
                    'orders.total_amount',
                    'orders.status',
                    'dealers.dealer_code',
                    'dealers.dealer_name'
                )
                ->where('orders.created_by', $user->id);

            if ($request->has('search_key') && !empty($request->search_key)) {
                $searchKey = $request->search_key;

                if (strpos($searchKey, 'OD00') === 0) {
                    $searchKey = str_replace('OD00', '', $searchKey);

                    $query->where(function ($q) use ($searchKey) {
                        $q->where('orders.id', '=', $searchKey); 
                    });
                } else {
                    $query->where(function ($q) use ($searchKey) {
                        $q->where('orders.id', 'like', '%' . $searchKey . '%')
                        ->orWhere('dealers.dealer_code', 'like', '%' . $searchKey . '%')
                        ->orWhere('dealers.dealer_name', 'like', '%' . $searchKey . '%');
                    });
                }
            }


            $data = $query->orderBy('orders.id', 'desc')->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Filtered orders fetched successfully',
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
    public function getDistricts()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $query = DB::table('districts')
                ->select('id as district_id', 'name as district_name')
                ->orderBy('name', 'asc');

            switch ($user->employee_type_id) {
                case 1: 
                case 2:
                case 3: 
                    $query->where('id', $user->district_id);
                    break;

                case 4: 
                    $region = \App\Models\Regions::whereHas('districts', function ($query) use ($user) {
                        $query->where('id', $user->district_id);
                    })->first();

                    if (!$region) {
                        return response()->json([
                            'success' => false,
                            'statusCode' => 404,
                            'message' => "Region not found for the employee's district.",
                        ], 404);
                    }

                    $query->where('regions_id', $region->id);
                    break;

                case 5: 
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'statusCode' => 403,
                        'message' => 'Forbidden: Unauthorized access to districts',
                    ], 403);
            }

            $districts = $query->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Districts fetched successfully',
                'data' => $districts,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function changeNotificationStatus($table, $id, $value)
    {
        
        $allowedTables = [
            'assigned_routes', 'activities', 'orders',
            'targets', 'leads', 'outstanding_payments', 'outstanding_payment_commitments'
        ];
    
        if (!in_array($table, $allowedTables)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid parameter'
            ], 400);
        }
    
        DB::table($table)->where('id', $id)->update(['notification_status' => $value]);
    
        return response()->json(['status' => 'success']);
   
    }
   
    public function getRegions()
    {
        $regions = Regions::with(['districts:id,regions_id,name'])->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => $regions
        ]);
    }
    public function getLeadSource()
    {
        $data = ["Own","Dealer","Ace","MITR","Influencer","Mason","Digital Leads","Prabhu Steels","Consumer Champion"];
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Lead Source fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getConstructionTypes()
    {
        $data = [
            'Residential (Independent House, Villa, Apartment)',
            'Commercial (Shops, Showrooms, Offices, Hotels, Resorts)',
            'Industrial (Factories, Warehouses, Manufacturing Units)',
            'Institutional (Schools, Colleges, Hospitals, Religious Structures)',
            'Infrastructure (Roads, Bridges, Government Projects)',
            'Renovation & Remodeling',
            'Interior Fit-Out',
            'Other (Specify)',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Construction types fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getTypeOfVisit()
    {
        $data = [
            'Phone Call',
            'First Visit',
            'Re-Visit',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Type of Visit fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getStageOfConstruction()
    {
        $data = [
            'Pre Foundation',
            'Foundation',
            'Slab Work',
            'Finished',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Stage of construction fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getLeadScore()
    {
        $data = [
            'Hot',
            'Warm',
            'Cold',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Lead score fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getLeadStatus()
    {
        $data = [
            'Follow Up',
            'Won',
            'Lost',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Lead status fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function getQuantityType()
    {
        $data = [
            'Pieces',
            'Ton',
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Quantity Types fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function calculateQuantityDetails(Request $request)
    {
        $request->validate([
            'quantity_type' => 'required|in:Pieces,Ton',
            'quantity' => 'required|numeric|min:0',
        ]);

        $quantityType = $request->quantity_type;
        $quantity = $request->quantity;

        // Define your conversion rate
        $piecesPerTon = 100; // example: 1 Ton = 100 Pieces

        // Define base prices
        $pricePerPiece = 50; // example: ₹50 per piece
        $pricePerTon = $pricePerPiece * $piecesPerTon;

        if ($quantityType === 'Pieces') {
            $pieces = $quantity;
            $tons = $quantity / $piecesPerTon;
            $price = $pieces * $pricePerPiece;
            $pricePerUnit = $pricePerPiece;
        } else {
            $tons = $quantity;
            $pieces = $quantity * $piecesPerTon;
            $price = $tons * $pricePerTon;
            $pricePerUnit = $pricePerTon;
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Quantity details calculated successfully',
            'data' => [
                'quantity_type' => $quantityType,
                'entered_quantity' => $quantity,
                'pieces' => $pieces,
                'tons' => $tons,
                'price' => $price,
                'price_per_unit' => $pricePerUnit,
            ],
        ], 200);
    }
   
}
