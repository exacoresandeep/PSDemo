<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AssignRoute;
use App\Models\DealerRouteAssignment;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\District;
use App\Models\Regions;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\OutstandingPaymentCommitment;
use App\Models\OutstandingPayment;
use App\Models\OutstandingNew;
use App\Models\ProductType;
use App\Models\CreditNote;
use App\Models\DealerVisit;
use App\Models\InfluencerVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;


class OrderController extends Controller
{
    

    public function index(Request $request)
    {
        try {
            if ($request->has('search_key')) {
                return $this->orderFilter($request); 
            }

            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $query = Order::where('created_by', $employee->id)
                ->where('dealer_flag_order', "0")
                ->with([
                    'dealer:id,dealer_name,dealer_code',
                    'orderItems:id,order_id,product_id,total_quantity' // optional, if you want to see products
                ]);

            if ($request->has('product_id') && !empty($request->product_id)) {
                $productId = $request->product_id;
                $query->whereHas('orderItems', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });
            }

            $orders = $query->select('id', 'total_amount', 'status', 'created_at', 'dealer_id')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($order) {
                    $order->total_amount = round((float) $order->total_amount, 6);
                    return $order;
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders fetched successfully',
                'data' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        // 'date' => $order->created_at->format('d-m-Y'),
                        // 'time' => $order->created_at->format('H:i:s'),
                        'created_at' => $order->created_at->format('d/m/Y h:i:s A'),
                        'dealer' => [
                            'name' => optional($order->dealer)->dealer_name,
                            'dealer_code' => optional($order->dealer)->dealer_code,
                        ],
                    ];
                }),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function store(Request $request)
    {
       
        try {
            $employee = Auth::user();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
              $rules = [
                'order_type' => 'nullable|exists:order_types,id',
                'customer_type_id' => 'nullable|exists:customer_types,id',
                'order_category' => 'nullable|string',
                'lead_id' => 'nullable|exists:leads,id',
                'dealer_id' => 'required|exists:dealers,id',
                'payment_terms_id' => 'required|exists:payment_terms,id',
                'credit_days' => 'nullable|string',
                'advance_amount' => 'nullable|numeric',
                'payment_date' => 'nullable|string',
                'utr_number' => 'nullable|string',
                'billing_date' => 'required|string',
                'total_amount' => 'nullable|numeric',
                'additional_information' => 'nullable|string',
                'status' => 'nullable|in:Pending,Dispatched,Delivered',
                'vehicle_category_id' => 'nullable|integer',
                'vehicle_type' => 'nullable|string',
                'vehicle_number' => 'nullable|string',
                'driver_name' => 'nullable|string',
                'scheme' => 'nullable|string',
                'driver_phone' => 'nullable|string',
                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.total_quantity' => 'nullable',  //push
                'order_items.*.product_details' => 'nullable|array',
                'attachment' => 'nullable|array',
                'attachment.*' => 'nullable|string',
                
            ];
 
            if ($employee->employee_type_id != 1) {
                $rules['delivery_date'] = 'required|string';
            } else {
                $rules['delivery_date'] = 'nullable|string';
            }
   
            $validatedData = $request->validate($rules);
     
            foreach (['billing_date', 'delivery_date', 'payment_date'] as $dateField) {
                if (!empty($validatedData[$dateField])) {
                    // $dateValue = trim($validatedData[$dateField]);
                    // $dateValue = str_replace('/', '-', $dateValue);
                    $dateValue = trim(str_replace('\\', '', $validatedData[$dateField])); 
                    $dateValue = str_replace('/', '-', $dateValue);

                    try {
                        $validatedData[$dateField] = Carbon::createFromFormat('d-m-Y', $dateValue)->format('Y-m-d');
                    } catch (\Exception $e) {
                   
                        $validatedData[$dateField] = Carbon::parse($dateValue)->format('Y-m-d');
                    }
                }
            }
 
            $validatedData['created_by'] = $employee->id;
            if (in_array($employee->employee_type_id, [2, 3, 4, 5])) {
                $validatedData['order_approved'] = '0';
            }
            $validatedData['product_id'] = $validatedData['order_items'][0]['product_id'] ?? null;
            $order = Order::create($validatedData);
           
      
            foreach ($validatedData['order_items'] as $orderItem) {
                $totalQuantity = 0;
                if (!empty($orderItem['product_details'])) {
                    foreach ($orderItem['product_details'] as $productDetail) {
                        //push
                        // if (isset($productDetail['pieces'])) {
                        //     $totalQuantity += (float)$productDetail['pieces'];
                        // }
                        // if (isset($productDetail['tonnage'])) {
                        //     $totalQuantity += (float)$productDetail['tonnage'];
                        // }
                        $typeName = \App\Models\ProductType::where('id', $productDetail['product_type_id'])
                        ->value('type_name');

                        $productDetail['quantity_type'] = $orderItem['quantity_type'] ?? null;
                        $productDetail['type_name'] = $typeName ?? null;

                        $productDetails[] = $productDetail;
                    }

                } else {
                    //push
                    // $totalQuantity = (float)($orderItem['quantity'] ?? 0);
                    $orderItem['product_details'] = null;
                }
                $totalQuantity = (float)($orderItem['total_quantity'] ?? 0);  //push

                $orderItem['total_quantity'] = round($totalQuantity, 6);//push
                unset($orderItem['quantity_type']);

                $order->orderItems()->create($orderItem);
            }

     

            $responseData = [
                    'order_type' => $order->order_type,
                    'customer_type_id' => $order->customerType, 
                    'lead_id' => $order->lead_id,
                    'dealer_id' => $order->dealer_id,
                    'payment_terms_id' => $order->payment_terms_id,
                    'credit_days' => $order->credit_days,
                    'billing_date' => $order->billing_date,
                    'total_amount' => round((float) $order->total_amount, 6),
                    'additional_information' => $order->additional_information,
                    'status' => $order->status,
                    'created_by' => $order->created_by,
                    'updated_at' => Carbon::parse($order->updated_at)->format('d-m-Y'),
                    'created_at' => Carbon::parse($order->created_at)->format('d-m-Y'),
                    'id' => $order->id,
                    'product_id' => $order->product_id,

            ];
           
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order created successfully!',
                'data' => $responseData
            ], 200);
            

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($orderId)
    {
        try {

            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'You must be logged in to view this order.'
                ], 400);
            }
            
            $order = Order::with([
                'orderType:id,name',
                'dealer:id,dealer_name,dealer_code', 
                'orderItems.product:id,product_name,product_code',
                'lead:id,customer_type,customer_name,phone,address',
                'lead.customerType:id,name', 
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name' 
            ])->findOrFail($orderId);
      
            $order->vehicle_category_name = $order->vehicleCategory->vehicle_category_name ?? null;
            
            $order->total_amount = round((float) $order->total_amount, 6);

          
            if ($order->orderItems && count($order->orderItems)) {
                foreach ($order->orderItems as $item) {
                    $item->total_quantity = (float) $item->total_quantity;
                    unset($item->quantity_type);
            
                    $totalPieces = 0;
                    $totalTon = 0;
                    
                    if (isset($item->product_details) && is_array($item->product_details)) {
                        foreach ($item->product_details as $pd) {
                            $totalPieces += $pd['pieces'] ?? 0;
                            $tonnage = $pd['tonnage'] ?? 0;
                            $pieces  = $pd['pieces'] ?? 0;
                            $totalTon += $tonnage * $pieces;
                        }
                    }
                    
                    $item->total_pieces = $totalPieces;
                    $item->total_ton = $totalTon;
            
            
                    $item->product_name = $item->product['product_name'] ?? null;
                    $item->product_code = $item->product['product_code'] ?? null;
                }
            }
    	    if($order->dealer_flag_order!="0"){
    	 	    //......................notification..............
                $authController = new AuthController();
                $authController->changeNotificationStatus('orders', $orderId,'opened');
    	    }

	        return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders details fetched successfully',
                'data' => $order,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderFilter(Request $request)
    {
        try {
            $employeeId = Auth::id();
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized user.',
                ], 401);
            }
            $searchKey = $request->input('search_key', '');
            $product_id = $request->input('product_id', '');

            $searchParts = explode('|', $searchKey);
            $statusFilter = isset($searchParts[0]) ? $searchParts[0] : '';
            $dateFilter   = isset($searchParts[1]) ? $searchParts[1] : '';
            
            // $statusFilter = ucfirst(strtolower($statusFilter));
            
            $ordersQuery = Order::with(['dealer:id,dealer_name as name'])
                ->where('created_by', $employeeId)
                ->select('id','created_at','status','total_amount','dealer_id');
                
            if (!empty($product_id)) {
                $ordersQuery->where('product_id', $product_id);
            }
            
            if (in_array($statusFilter, ['All','Pending','Accepted','Rejected','Accounts Approved','Accounts Rejected'])) {
                if ($statusFilter != 'All') {
                    $ordersQuery->where('status', $statusFilter);
                }
            }
            
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateFilter)) {
                try {
                    $parsedDate = Carbon::createFromFormat('d-m-Y', $dateFilter);
                    $start = (clone $parsedDate)->startOfDay(); 
                    $end   = (clone $parsedDate)->endOfDay();
                    $ordersQuery->whereBetween('created_at', [$start, $end]);
                } catch (\Exception $e) {
                }
            }
            elseif (in_array($dateFilter, ['Today','Weekly','Monthly', 'By financial year'])) {
                $dateFilter   = ucfirst(strtolower($dateFilter));
                if ($dateFilter == 'Today') {
                    $start = Carbon::today()->startOfDay();
                    $end   = Carbon::today()->endOfDay();
                    $ordersQuery->whereBetween('created_at', [$start, $end]);

                } elseif ($dateFilter == 'Weekly') {
                    $start = Carbon::now()->startOfWeek();
                    $end   = Carbon::now()->endOfWeek();
                    $ordersQuery->whereBetween('created_at', [$start, $end]);

                } elseif ($dateFilter == 'Monthly') {
                    $start = Carbon::now()->startOfMonth();
                    $end   = Carbon::now()->endOfMonth();
                    $ordersQuery->whereBetween('created_at', [$start, $end]);

                } elseif ($dateFilter == 'By financial year') {
                    $today = Carbon::now();
                    if ($today->month >= 4) {
                        $fyStart = Carbon::create($today->year, 4, 1)->startOfDay();
                        $fyEnd   = Carbon::create($today->year + 1, 3, 31)->endOfDay();
                    } else {
                        $fyStart = Carbon::create($today->year - 1, 4, 1)->startOfDay();
                        $fyEnd   = Carbon::create($today->year, 3, 31)->endOfDay();
                    }

                    $ordersQuery->whereBetween('created_at', [$fyStart, $fyEnd]);
                }else{
                }
            }
            // dd($ordersQuery->toRawSql());

            $orders = $ordersQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'created_at' => Carbon::parse($order->created_at)->format('d-m-Y'),
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'dealer' => $order->dealer,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders filtered successfully',
                'data' => $orders,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function dealerOrderList(Request $request)
    {
        try {
            //push
            if ($request->hasAny(['search_key', 'product_id'])) {
                return $this->orderFilter($request);
            }
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }

            $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (empty($dealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }

            $orders = Order::join('dealers', 'orders.created_by_dealer', '=', 'dealers.id')
                ->where('orders.dealer_flag_order', '1')
                ->whereIn('orders.created_by_dealer', $dealerIds)
                ->select([
                    'orders.id',
                    'orders.total_amount',
                    'orders.status',
                    'orders.created_at',
                    'dealers.id as dealer_id',
                    'dealers.dealer_name',
                    'dealers.dealer_code'
                ])
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => "No dealer-created orders found.",
                    'data' => []
                ], 200);
            }

            $data = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'dealer' => [
                        'id' => $order->dealer_id,
                        'name' => $order->dealer_name,
                        'code' => $order->dealer_code,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer-created orders fetched successfully',
                'data' => $data,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function dealerOrderDetails($orderId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'You must be logged in to view this order.'
                ], 400);
            }

            $order = Order::with([
                'orderType:id,name',
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name,product_code',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($orderId);

            $dealer = $order->dealers;
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found for this order.'
                ], 404);
            }

            $allowedRouteIds = AssignRoute::where('employee_id', $user->id)->pluck('id')->toArray();

            $dealerAssignedRouteCount = DealerRouteAssignment::where('dealer_id', $dealer->id)
                ->whereIn('assign_route_id', $allowedRouteIds)
                ->count();

            if ($dealerAssignedRouteCount === 0) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'You are not authorized to view this order.'
                ], 403);
            }

            $totalOutstandingPayments = (float) OutstandingNew::where('dealer_id', $dealer->id)
                ->orderBy('created_at', 'desc')
                ->value('outstanding_amount');

            $billingDate = $order->billing_date ? $order->billing_date : null;
            $createdAt = Carbon::parse($order->created_at)->format('d/m/Y');

            $orderItems = $order->orderItems->map(function ($item) {
                $productDetails = collect($item->product_details)->map(function ($detail) {
                    $productType = ProductType::find($detail['product_type_id']);
                    return [
                        'product_type_id' => $detail['product_type_id'],
                        'type_name' => $productType->type_name ?? null,
                        'quantity' => (float) $detail['quantity'],
                        'rate' => $detail['rate']
                    ];
                });

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? null,
                    'product_code' => $item->product->product_code ?? null,
                    'total_quantity' => (float) $item->total_quantity,
                    'product_details' => $productDetails,
                ];
            });

            $response = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_term' => $order->paymentTerm->name ?? null,
                'credit_days' => $order->credit_days ?? null,
                'billing_date' => $billingDate,
                'attachment' => $order->attachment,
                'aashiyana_attachment' => $order->aashiyana_attachment ?? null,
                'advance_attachment' => $order->advance_attachment ?? null,
                'total_amount' => $order->total_amount,
                'additional_information' => $order->additional_information,
                'created_at' => $createdAt,
                'order_items' => $orderItems,
                'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
                'vehicle_number' => $order->vehicle_number,
                'driver_name' => $order->driver_name,
                'driver_phone' => $order->driver_phone,
                'status' => $order->status,
                'total_outstanding_payments' => (float) $totalOutstandingPayments,
                'dealer_code' => $dealer->dealer_code,
                'dealer_name' => $dealer->dealer_name
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $response,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function dealerOrderStatusUpdate(Request $request, $orderId)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:Accepted,Rejected',
                'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            // Fetch order with necessary relationships
            $order = Order::with('dealers:id,dealer_name,dealer_code,assigned_route_id')->find($orderId);
            $dealer = $order->dealers;

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found for this order.',
                ], 404);
            }

            if ($user->employee_type_id != 5) {
            $allowedRoutes = AssignRoute::where('employee_id', $user->id)->pluck('id')->toArray();

            if (!in_array($dealer->assigned_route_id, $allowedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'You are not authorized to update this order status.',
                ], 403);
            }
        }

        // Update order status and rejection reason if applicable
        $order->update([
            'status' => $validatedData['status'],
            'reason_for_rejection' => $validatedData['status'] === 'Rejected' ? $validatedData['reason_for_rejection'] : null,
        ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order status updated successfully!',
                'data' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'reason_for_rejection' => $order->reason_for_rejection,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => "Validation error",
                'errors' => $e->errors(),
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


     public function dealerOrderFilter(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search_key' => 'required|string|in:All,Pending,Accepted,Rejected',
            ]);

            $searchKey = $validatedData['search_key'];
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }

            $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (empty($dealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }

            $ordersQuery = Order::join('dealers', 'orders.created_by_dealer', '=', 'dealers.id')
                ->where('orders.dealer_flag_order', '1')
                ->whereIn('orders.created_by_dealer', $dealerIds)
                ->select([
                    'orders.id',
                    'orders.total_amount',
                    'orders.status',
                    'orders.created_at',
                    'dealers.id as dealer_id',
                    'dealers.dealer_name',
                    'dealers.dealer_code'
                ]);

            if ($searchKey !== 'All') {
                $ordersQuery->where('orders.status', $searchKey);
            }

            $orders = $ordersQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'total_amount' => (float) sprintf("%.2f", $order->total_amount),
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'dealer' => [
                        'id' => $order->dealer_id,
                        'name' => $order->dealer_name,
                        'code' => $order->dealer_code,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Filtered dealer-created orders fetched successfully",
                'data' => $orders,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function outstandingPaymentsList()
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for this employee.",
                ], 404);
            }

            $dealers = DealerRouteAssignment::whereIn('assign_route_id', $assignedRoutes)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                ], 404);
            }

            $outstandingSummaries = OutstandingNew::select('dealer_id', 'outstanding_amount')
                ->whereIn('dealer_id', $dealers)
                ->where('outstanding_amount', '>', 0)
                ->with(['dealers:id,dealer_name,dealer_code'])
                ->get()
                ->map(function ($item) {
                    return [
                        'dealer_id' => $item->dealer_id,
                        'dealer_code' => $item->dealer->dealer_code ?? null,
                        'dealer_name' => $item->dealer->dealer_name ?? null,
                        'total_outstanding_amount' => (float) $item->outstanding_amount,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer-wise outstanding amounts fetched successfully',
                'data' => $outstandingSummaries,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
  
     public function searchOutstandingPayments(Request $request)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $search = $request->input('search');

            if (!$search) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => "Search query is required.",
                ], 422);
            }

            // ✅ Get all routes assigned to this employee
            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for this employee.",
                ], 404);
            }

            // ✅ Get dealers linked to those routes (ignore district/region)
            $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRoutes)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (empty($dealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => [],
                ], 404);
            }

            // ✅ Filter dealers by search (dealer_name or dealer_code)
            $filteredDealers = Dealer::whereIn('id', $dealerIds)
                ->where(function ($q) use ($search) {
                    $q->where('dealer_name', 'like', "%{$search}%")
                    ->orWhere('dealer_code', 'like', "%{$search}%");
                })
                ->pluck('id')
                ->toArray();

            if (empty($filteredDealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found matching the search criteria.",
                    'data' => [],
                ], 404);
            }

            // ✅ Fetch outstanding data for filtered dealers
            $outstandingSummaries = OutstandingNew::select('dealer_id', 'outstanding_amount')
                ->whereIn('dealer_id', $filteredDealers)
                ->where('outstanding_amount', '>', 0)
                ->with(['dealer:id,dealer_name,dealer_code'])
                ->get()
                ->map(function ($item) {
                    return [
                        'dealer_id' => $item->dealer_id,
                        'dealer_code' => $item->dealer->dealer_code ?? '',
                        'dealer_name' => $item->dealer->dealer_name ?? '',
                        'total_outstanding_amount' => (float) $item->outstanding_amount,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Filtered outstanding payments fetched successfully.',
                'data' => $outstandingSummaries,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function viewOutstandingPaymentByDealer($dealer_id)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated",
                ], 401);
            }

            $dealer = Dealer::find($dealer_id);

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Dealer not found.",
                ], 404);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for this employee.",
                ], 404);
            }

            $accessibleDealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (!in_array($dealer_id, $accessibleDealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have access to this dealer's outstanding payments.",
                ], 403);
            }

            $outstandingPayments = OutstandingPayment::where('dealer_id', $dealer_id)
                ->where('outstanding_amount', '>', 0)
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'order_id' => $item->order_id,
                        'invoice_number' => $item->invoice_number,
                        'invoice_date' => $item->invoice_date ? $item->invoice_date->format('d/m/Y') : null,
                        'due_date' => $item->due_date ? $item->due_date->format('d/m/Y') : null,
                        'outstanding_amount' => (float) $item->outstanding_amount,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding payments fetched successfully.',
                'data' => $outstandingPayments,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function searchOutstandingByInvoice(Request $request)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated",
                ], 401);
            }

            $invoiceNumber = $request->input('invoice_number');
            $dealerId = $request->input('dealer_id');

            if (!$invoiceNumber || !$dealerId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => "Both invoice number and dealer ID are required.",
                ], 422);
            }

            $dealer = Dealer::find($dealerId);
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Dealer not found.",
                ], 404);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)
                ->pluck('id')
                ->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for this employee.",
                ], 404);
            }

            $accessibleDealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (!in_array($dealerId, $accessibleDealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have access to this dealer's outstanding payments.",
                ], 403);
            }

            $outstandingRecords = OutstandingPayment::where('dealer_id', $dealerId)
                ->where('invoice_number', 'like', "%{$invoiceNumber}%")
                ->where('outstanding_amount', '>', 0)
                ->orderBy('due_date', 'asc')
                ->get();

            if ($outstandingRecords->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No outstanding payments found for this invoice and dealer.",
                    'data' => [],
                ], 404);
            }

            $formattedResults = $outstandingRecords->map(function ($item) {
                return [
                    'order_id' => $item->order_id,
                    'invoice_number' => $item->invoice_number,
                    'invoice_date' => $item->invoice_date ? $item->invoice_date->format('d/m/Y') : null,
                    'due_date' => $item->due_date ? $item->due_date->format('d/m/Y') : null,
                    'outstanding_amount' => (float) $item->outstanding_amount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding payment details found successfully.',
                'data' => $formattedResults,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewOutstandingPaymentOrderDetails($orderId)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRouteIds = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for this employee.",
                ], 404);
            }

            $dealerIds = DealerRouteAssignment::whereIn('assign_route_id', $assignedRouteIds)
                ->pluck('dealer_id')
                ->unique()
                ->toArray();

            if (empty($dealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for this employee’s assigned routes.",
                ], 404);
            }

            $outstandingPayment = OutstandingPayment::with([
                'dealer:id,dealer_name,dealer_code',
                'order.orderType:id,name',
                'order.orderItems.product:id,product_name,product_code',
                'order.paymentTerm:id,name',
                'order.vehicleCategory:id,vehicle_category_name',
                'commitments'
            ])->where('order_id', $orderId)->first();

            if (!$outstandingPayment) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No outstanding payment found for this order.",
                    'data' => []
                ], 404);
            }

            if (!in_array($outstandingPayment->dealer->id, $dealerIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to view this dealer’s order.",
                ], 403);
            }

            $order = $outstandingPayment->order;

            $payments = Payment::where('order_id', $order->id)
                ->where('dealer_id', $outstandingPayment->dealer->id)
                ->select('payment_date', 'payment_amount', 'payment_document_no')
                ->orderBy('payment_date', 'asc')
                ->get();

            $totalPaidAmount = $payments->sum('payment_amount');
            $totalOutstandingAmount = $order->invoice_total - $totalPaidAmount;

            $orderItems = $order->orderItems->map(function ($item) {
                $productDetails = collect($item->product_details)->map(function ($detail) {
                    $productType = ProductType::find($detail['product_type_id']);
                    return [
                        'product_type_id' => $detail['product_type_id'],
                        'type_name' => $productType->type_name ?? null,
                        'quantity' => (float) $detail['quantity'],
                        'rate' => $detail['rate']
                    ];
                });

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? null,
                    'product_code' => $item->product->product_code ?? null,
                    'total_quantity' => (float) $item->total_quantity,
                    'product_details' => $productDetails,
                ];
            });

            $commitment_data = $outstandingPayment->commitments->map(function ($commitment) {
                return [
                    'id' => $commitment->id,
                    'committed_date' => $commitment->committed_date
                        ? \Carbon\Carbon::parse($commitment->committed_date)->format('d/m/Y')
                        : null,
                    'committed_amount' => (float) $commitment->committed_amount,
                    'notification_status' => $commitment->notification_status,
                ];
            })->toArray();

            $response = [
                'id' => $outstandingPayment->id,
                'order_id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_term' => $order->paymentTerm->name ?? null,
                'billing_date' => $order->billing_date ? \Carbon\Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                'attachment' => $order->attachment,
                'aashiyana_attachment' => $order->aashiyana_attachment ? asset('uploads/orders/' . $order->aashiyana_attachment) : null,
                'advance_attachment' => $order->advance_attachment ? asset('uploads/orders/' . $order->advance_attachment) : null,
                'total_amount' => $order->total_amount,
                'additional_information' => $order->additional_information,
                'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'),
                'order_items' => $orderItems,
                'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
                'vehicle_number' => $order->vehicle_number,
                'driver_name' => $order->driver_name,
                'driver_phone' => $order->driver_phone,
                'outstanding_payment' => [
                    'outstanding_payment_id' => $outstandingPayment->id,
                    'invoice_number' => $outstandingPayment->invoice_number,
                    'invoice_date' => $outstandingPayment->invoice_date ? \Carbon\Carbon::parse($outstandingPayment->invoice_date)->format('d/m/Y') : null,
                    'due_date' => $outstandingPayment->due_date ? \Carbon\Carbon::parse($outstandingPayment->due_date)->format('d/m/Y') : null,
                    'invoice_total' => (float) $outstandingPayment->invoice_total,
                    'paid_amount' => (float) $outstandingPayment->paid_amount,
                    'outstanding_amount' => (float) $outstandingPayment->outstanding_amount,
                    'payment_doc_number' => $outstandingPayment->payment_doc_number,
                    'payment_date' => $outstandingPayment->payment_date ? \Carbon\Carbon::parse($outstandingPayment->payment_date)->format('d/m/Y') : null,
                    'payment_amount_applied' => (float) $outstandingPayment->payment_amount_applied,
                    'status' => $outstandingPayment->status,
                ],
                'commitment_data' => $commitment_data,
                'dealer' => [
                    'id' => $outstandingPayment->dealer->id,
                    'name' => $outstandingPayment->dealer->dealer_name,
                    'code' => $outstandingPayment->dealer->dealer_code,
                ]
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding Payment Order details fetched successfully',
                'data' => $response,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function addOutstandingPaymentCommitment(Request $request, $outstandingPaymentId)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $validatedData = $request->validate([
                'commitments' => 'required|array|min:1',
                'commitments.*.committed_date' => 'required|date|after_or_equal:today',
                'commitments.*.committed_amount' => 'required|numeric|min:1',
            ]);

            $outstandingPayment = OutstandingPayment::with('dealer')->findOrFail($outstandingPaymentId);
            $dealer = $outstandingPayment->dealer;

            $allowed = false;

            $dealerAssigned = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->exists();

            switch ($employee->employee_type_id) {
                case 2: // ASO
                case 3: // DSM
                case 4: // RSM
                case 5: // SM
                    $allowed = $dealerAssigned;
                    break;
                default:
                    $allowed = false;
                    break;
            }

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to add commitments for this dealer.",
                ], 403);
            }

            $totalCommitted = OutstandingPaymentCommitment::where('outstanding_payment_id', $outstandingPaymentId)
                ->sum('committed_amount');
            $remainingOutstanding = $outstandingPayment->outstanding_amount - $totalCommitted;

            $commitmentsToInsert = [];
            $totalNewCommitments = 0;

            foreach ($validatedData['commitments'] as $commitment) {
                $totalNewCommitments += $commitment['committed_amount'];

                if ($totalNewCommitments > $remainingOutstanding) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => "Total committed amount exceeds remaining outstanding balance of $remainingOutstanding.",
                    ], 400);
                }

                $commitmentsToInsert[] = [
                    'outstanding_payment_id' => $outstandingPaymentId,
                    'committed_date' => $commitment['committed_date'],
                    'committed_amount' => $commitment['committed_amount'],
                    'employee_id' => $employee->id,
                    'notification_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            OutstandingPaymentCommitment::insert($commitmentsToInsert);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Commitments added successfully!',
                'data' => $commitmentsToInsert,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function addOutstandingPaymentCommitmentNew(Request $request, $outstandingPaymentId)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $validatedData = $request->validate([
                'commitments' => 'required|array|min:1',
                'commitments.*.committed_date' => 'required|date|after_or_equal:today',
                'commitments.*.committed_amount' => 'required|numeric|min:1',
            ]);

            $outstandingPayment = OutstandingPayment::with('dealer')->findOrFail($outstandingPaymentId);
            $dealer = $outstandingPayment->dealer;

            $dealerAssigned = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->exists();

            $allowed = false;

            switch ($employee->employee_type_id) {
                case 1: // SE
                case 2: // ASO
                case 3: // DSM
                case 4: // RSM
                case 5: // SM
                    $allowed = $dealerAssigned;
                    break;
                default:
                    $allowed = false;
                    break;
            }

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to add commitments for this dealer.",
                ], 403);
            }

            $totalCommitted = OutstandingPaymentCommitment::where('outstanding_payment_id', $outstandingPaymentId)
                ->sum('committed_amount');
            $remainingOutstanding = $outstandingPayment->outstanding_amount - $totalCommitted;

            $commitmentsToInsert = [];
            $totalNewCommitments = 0;

            foreach ($validatedData['commitments'] as $commitment) {
                $totalNewCommitments += $commitment['committed_amount'];

                if ($totalNewCommitments > $remainingOutstanding) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => "Total committed amount exceeds remaining outstanding balance of $remainingOutstanding.",
                    ], 400);
                }

                $commitmentsToInsert[] = [
                    'outstanding_payment_id' => $outstandingPaymentId,
                    'committed_date' => $commitment['committed_date'],
                    'committed_amount' => $commitment['committed_amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            OutstandingPaymentCommitment::insert($commitmentsToInsert);

            $outstandingPayments = OutstandingPayment::where('dealer_id', $dealer->id)
                ->where('outstanding_amount', '>', 0)
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'order_id' => $item->order_id,
                        'invoice_number' => $item->invoice_number,
                        'invoice_date' => optional($item->invoice_date)->format('d/m/Y'),
                        'due_date' => optional($item->due_date)->format('d/m/Y'),
                        'outstanding_amount' => (float) $item->outstanding_amount,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Commitments added successfully!',
                'data' => [
                    "inserted_data" => $commitmentsToInsert,
                    "outstandingPayments" => $outstandingPayments
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function salesExecutiveSalesReport(Request $request)
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
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $product_id = $request->input('product_id', null);
    
            $totalSalesForPeriod = 0;
            $salesReport = collect([]);
            if ($employee->employee_type_id == 3) {  // DSM
                $salesExecutives = Employee::where('district_id', $employee->district_id)
                    ->whereIn('employee_type_id', [1, 2])
                    ->get();
    
                if ($salesExecutives->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No Sales Executives and Area Sales Officers found in this district.",
                    ], 404);
                }

                $salesReport = $salesExecutives->map(function ($se) use ($month, $year, $product_id, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $se->id)
                        // ->where('status', 'Delivered')
                        ->where('order_approved', '1')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where(function ($q) {
                            $q->whereNull('source')
                            ->orWhere('source', '')
                            ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                        })

                        ->when($product_id, function ($q) use ($product_id) {
                            $q->where('product_id', $product_id);
                        })
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    return [
                        'employee_id' => $se->id,
                        'employee_name' => $se->name,
                        'employee_code' => $se->employee_code,
			            'employee_type_id' => $se->employee_type_id,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
    
            } elseif ($employee->employee_type_id == 4) {  //RSM
                $region = Regions::whereHas('districts', function ($query) use ($employee) {
                    $query->where('id', $employee->district_id);
                })->first();
    
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for the employee's district.",
                    ], 404);
                }
    
                $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                $employees = Employee::whereIn('district_id', $districtsInRegion)
                    ->whereIn('employee_type_id', [1, 2, 3])
                    ->get();
    
                if ($employees->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No Sales Executives or Area Sales Officers found in this region.",
                    ], 404);
                }
    
                $salesReport = $employees->map(function ($emp) use ($month, $year, $product_id, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $emp->id)
                        // ->where('status', 'Delivered')
                        ->where('order_approved', '1')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where(function ($q) {
                            $q->whereNull('source')
                            ->orWhere('source', '')
                            ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                        })

                        ->when($product_id, function ($q) use ($product_id) {
                            $q->where('product_id', $product_id);
                        })
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    $employeeType = match ($emp->employee_type_id) {
                        1 => 'Sales Executive',
                        2 => 'Area Sales Officer',
                        3 => 'District Sales Manager',
                        default => 'Unknown',
                    };
    
                    return [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->name,
                        'employee_code' => $emp->employee_code,
                        'employee_type_id' => $emp->employee_type_id,
                        'employee_type' => $employeeType,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
    
            } elseif ($employee->employee_type_id == 5) {  //SM
                $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4])->get();
    
                if ($employees->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No Employees found.",
                    ], 404);
                }
    
                $salesReport = $employees->map(function ($emp) use ($month, $year, $product_id, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $emp->id)
                        // ->where('status', 'Delivered')
                        ->where('order_approved', '1')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->where(function ($q) {
                            $q->whereNull('source')
                            ->orWhere('source', '')
                            ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                        })

                        ->when($product_id, function ($q) use ($product_id) {
                            $q->where('product_id', $product_id);
                        })
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    $employeeType = match ($emp->employee_type_id) {
                        1 => 'Sales Executive',
                        2 => 'Area Sales Officer',
                        3 => 'District Sales Manager',
                        4 => 'Regional Sales Manager',
                        default => 'Unknown',
                    };
    
                    return [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->name,
                        'employee_code' => $emp->employee_code,
                        'employee_type_id' => $emp->employee_type_id,
                        'employee_type' => $employeeType,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales report fetched successfully for $month/$year.",
                'data' => [
                    'total_sales_for_period' => (float) $totalSalesForPeriod,
                    'sales_report' => $salesReport,
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
   
    public function salesReportDetails(Request $request, $employee_id)
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
            if ($employee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1, 2]; 
                $salesEmployee = Employee::where('id', $employee_id)
                    ->whereIn('employee_type_id', $allowedEmployeeTypes)
                    ->first();
            } elseif ($employee->employee_type_id == 4) { 
                $region = Regions::whereHas('districts', function ($query) use ($employee) {
                    $query->where('id', $employee->district_id);
                })->first();
                
        
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for the RSM.",
                    ], 404);
                }
       //..
                $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                $salesEmployee = Employee::where('id', $employee_id)
                    ->whereIn('district_id', $districtsInRegion)
                    ->whereIn('employee_type_id', [1, 2, 3])
                    ->first();

            } elseif ($employee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [1, 2, 3, 4];
                 $salesEmployee = Employee::where('id', $employee_id)
                    ->whereIn('employee_type_id', $allowedEmployeeTypes)
                    ->first();
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            if (!$salesEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or access not allowed.",
                ], 404);
            }
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $product_id = $request->input('product_id', null);
    
            $orders = Order::where('created_by', $salesEmployee->id)
                // ->where('status', 'Delivered')
                ->where('order_approved', '1')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where(function ($q) {
                    $q->whereNull('source')
                    ->orWhere('source', '')
                    ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                })
                 ->when($product_id, function ($q) use ($product_id) {
                    $q->where('product_id', $product_id);
                })
                ->with('dealer:id,dealer_name') 
                ->get();
    
            $totalSalesAmount = $orders->sum('invoice_total');
    
            $ordersData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                    'dealer_name' => $order->dealer ? $order->dealer->dealer_name : 'N/A',
                    'invoice_total' => (float) $order->invoice_total,
                ];
            });
  
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales report details fetched successfully for $month/$year.",
                'data' => [
                    'employee_details' => [
                        'employee_id' => $salesEmployee->id,
                        'employee_code' => $salesEmployee->employee_code,
                        'employee_name' => $salesEmployee->name,
                        'email' => $salesEmployee->email,
                        'phone' => $salesEmployee->phone,
                        'total_sales_amount' => (float) $totalSalesAmount,
                    ],
                    'orders' => $ordersData,
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
    public function orderReportListing(Request $request)
    {
        try {
            $loggedInEmployee = Auth::user();
    
            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $product_id = $request->input('product_id', null);
      
            if ($loggedInEmployee->employee_type_id == 3) {

                $employees = Employee::where('district_id', $loggedInEmployee->district_id)
                    ->whereIn('employee_type_id', [1, 2])
                    ->get();
    
            } elseif ($loggedInEmployee->employee_type_id == 4) {
                // RSM: fetch ASOs and DSMs in the same region
                $region = Regions::whereHas('districts', function ($q) use ($loggedInEmployee) {
                    $q->where('id', $loggedInEmployee->district_id);
                })->first();
    
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for employee.",
                    ], 404);
                }
    
                $districts = District::where('regions_id', $region->id)->pluck('id');
    
                $employees = Employee::whereIn('district_id', $districts)
                    ->whereIn('employee_type_id', [1, 2, 3])
                    ->get();
    
            } elseif ($loggedInEmployee->employee_type_id == 5) {
                // SM: fetch DSMs and RSMs
                $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4])->get();
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }
    
            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No employees found under your hierarchy.",
                ], 404);
            }
    
            $totalOrdersForPeriod = 0;
    
            $reportData = $employees->map(function ($emp) use ($month, $year, $product_id, &$totalOrdersForPeriod) {
                $orderCount = Order::where('created_by', $emp->id)
                    ->where('status', '!=', 'Pending')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->where(function ($q) {
                        $q->whereNull('source')
                        ->orWhere('source', '')
                        ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                    })
                    ->when($product_id, function ($q) use ($product_id) {
                        $q->where('product_id', $product_id);
                    })
                    ->count();
    
                $totalOrdersForPeriod += $orderCount;
    
                $employeeType = match ($emp->employee_type_id) {
                    1 => 'Sales Executive',
                    2 => 'Area Sales Officer',
                    3 => 'District Sales Manager',
                    4 => 'Regional Sales Manager',
                    default => 'Unknown',
                };
    
                return [
                    'employee_id' => $emp->id,
                    'employee_name' => $emp->name,
                    'employee_code' => $emp->employee_code,
                    'employee_type_id' => $emp->employee_type_id,
                    'employee_type' => $employeeType,
                    'total_orders' => $orderCount,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_orders_for_period' => $totalOrdersForPeriod,
                    'order_report' => $reportData,
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

    public function orderReportDetails(Request $request, $employee_id)
    {
        try {
            $loggedInEmployee = Auth::user();

            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            if ($loggedInEmployee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1,2]; 
            } elseif ($loggedInEmployee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [1, 2, 3]; 
            } elseif ($loggedInEmployee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [1, 2, 3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $employee = Employee::whereIn('employee_type_id', $allowedEmployeeTypes)
                ->where('id', $employee_id)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or access denied.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $product_id = $request->input('product_id', null);

            $totalOrders = Order::where('created_by', $employee->id)
                ->where('status', '!=', 'Pending')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where(function ($q) {
                    $q->whereNull('source')
                    ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                })
                ->when($product_id, function ($q) use ($product_id) {
                    $q->where('product_id', $product_id);
                })
                ->count();

            $orders = Order::where('created_by', $employee->id)
                ->where('status', '!=', 'Pending')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where(function ($q) {
                    $q->whereNull('source')
                    ->orWhereNotIn('source', ['lead_won', 'dealer_visit', 'influencer_visit']);
                })
                ->when($product_id, function ($q) use ($product_id) {
                    $q->where('product_id', $product_id);
                })
                ->with('dealer:id,dealer_name') 
                ->orderBy('created_at', 'desc')
                ->get();

            $orderData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'dealer_name' => optional($order->dealer)->dealer_name,
                    'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'), 
                    'status' => $order->status,
                    'amount' => ($order->order_approved === '1') ? (float) $order->total_amount : (float) $order->total_amount,
                    // 'amount' => ($order->status === 'Delivered') ? (float) $order->invoice_total : (float) $order->total_amount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order details fetched successfully for $month/$year.",
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'employee_code' => $employee->employee_code,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'total_orders' => $totalOrders,
                    ],
                    'orders' => $orderData,
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
    
    public function leadReportListing(Request $request)
    {
        try {
            $loggedInEmployee = Auth::user();
    
            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
    
            // Determine employees based on role
            if ($loggedInEmployee->employee_type_id == 3) {
                // DSM: fetch SEs in same district
                $employees = Employee::where('district_id', $loggedInEmployee->district_id)
                    ->whereIn('employee_type_id', [1, 2])
                    ->get();
    
            } elseif ($loggedInEmployee->employee_type_id == 4) {
                // RSM: fetch ASOs, DSMs, and SEs in the same region
                $region = Regions::whereHas('districts', function ($q) use ($loggedInEmployee) {
                    $q->where('id', $loggedInEmployee->district_id);
                })->first();
    
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for employee.",
                    ], 404);
                }
    
                $districts = District::where('regions_id', $region->id)->pluck('id');
    
                $employees = Employee::whereIn('district_id', $districts)
                    ->whereIn('employee_type_id', [1, 2, 3])
                    ->get();
    
            } elseif ($loggedInEmployee->employee_type_id == 5) {
                // SM: fetch RSMs and DSMs
                $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4])->get();
    
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }
    
            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No employees found under your hierarchy.",
                ], 404);
            }
    
            $totalOpenedLeads = 0;
            $totalWonLeads = 0;
            $totalLostLeads = 0;
    
            $reportData = $employees->map(function ($employee) use ($month, $year, &$totalOpenedLeads, &$totalWonLeads, &$totalLostLeads) {
                $openedLeads = Lead::where('created_by', $employee->id)
                    ->whereIn('status', ['Opened', 'Follow Up'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();
    
                $wonLeads = Lead::where('created_by', $employee->id)
                    ->where('status', 'Won')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();
    
                $lostLeads = Lead::where('created_by', $employee->id)
                    ->where('status', 'Lost')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();
    
                $totalOpenedLeads += $openedLeads;
                $totalWonLeads += $wonLeads;
                $totalLostLeads += $lostLeads;
    
                $totalLeads = $openedLeads + $wonLeads + $lostLeads;
    
                $employeeType = match ($employee->employee_type_id) {
                    1 => 'Sales Executive',
                    2 => 'Area Sales Officer',
                    3 => 'District Sales Manager',
                    4 => 'Regional Sales Manager',
                    default => 'Unknown',
                };
    
                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'employee_type_id' => $employee->employee_type_id,
                    'employee_type' => $employeeType,
                    'total_leads' => $totalLeads,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Lead report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_leads_for_period' => [
                        'opened' => $totalOpenedLeads,
                        'won' => $totalWonLeads,
                        'lost' => $totalLostLeads,
                    ],
                    'lead_report' => $reportData,
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


    public function leadReportDetails(Request $request, $employee_id)
    {
        try {
            $loggedInEmployee = Auth::user();

            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            if ($loggedInEmployee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1,2]; 
            } elseif ($loggedInEmployee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [1, 2, 3]; 
            } elseif ($loggedInEmployee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [1, 2, 3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $salesEmployee = Employee::where('id', $employee_id)
                ->whereIn('employee_type_id', $allowedEmployeeTypes)
                ->first();

            if (!$salesEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or not authorized.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $leadCounts = Lead::where('created_by', $salesEmployee->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw("
                    SUM(CASE WHEN status IN ('Opened', 'Follow Up') THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN status = 'Won' THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost
                ")
                ->first();

            $leadDetails = Lead::where('created_by', $salesEmployee->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with('customerType') 
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => optional($lead->customerType)->name ?? 'N/A',
                        'created_at' => $lead->created_at->format('d/m/Y'),
                        'location' => $lead->location,
                        'status' => $lead->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Lead report details fetched successfully for {$salesEmployee->name}.",
                'data' => [
                    'employee' => [
                        'employee_id' => $salesEmployee->id,
                        'employee_name' => $salesEmployee->name,
                        'employee_code' => $salesEmployee->employee_code,
                        'email' => $salesEmployee->email,
                        'phone' => $salesEmployee->phone,
                    ],
                    'total_leads' => [
                        'opened' => (int) $leadCounts->opened,
                        'won' => (int) $leadCounts->won,
                        'lost' => (int) $leadCounts->lost,
                    ],
                    'leads' => $leadDetails,
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

    public function sendForApproval($orderId)
    {
        try {
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            if (Auth::user()->employee_type_id !== 2) { 
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $order->update([
                'send_for_approval' => 1,
                'send_for_approval_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order sent for approval successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in sendForApproval: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while sending order for approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function orderApprovalList()
    {
        try {
            $user = Auth::user();

            // Check if the logged-in user is Sales Manager
            if ($user->employee_type_id !== 5) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Access denied. Only Sales Managers can view this list.'
                ], 403);
            }

            // Step 1: Get dealer IDs with due_balance > 0
            $dealerIdsWithDue = OutstandingNew::where('due_balance', '>', 0)->pluck('dealer_id');

            if ($dealerIdsWithDue->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No dealers found with due balance'
                ], 404);
            }

            // Step 2: Get employee IDs (ASO, DSM, RSM)
            $employeeIds = Employee::whereIn('employee_type_id', [2, 3, 4])->pluck('id');

            // Step 3: Fetch orders created by employees for those dealers or created by those dealers
            $orders = Order::where(function ($query) use ($employeeIds, $dealerIdsWithDue) {
                    $query->whereIn('created_by', $employeeIds)
                        ->whereIn('dealer_id', $dealerIdsWithDue)
                        ->where('dealer_flag_order', '0');
                })
                ->orWhere(function ($query) use ($dealerIdsWithDue) {
                    $query->whereIn('created_by_dealer', $dealerIdsWithDue)
                        ->where('dealer_flag_order', '1');
                })
                ->with(['createdBy', 'dealer', 'orderType', 'paymentTerm']) 
                ->orderBy('created_at', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No orders found for approval'
                ], 404);
            }

            // Format the orders
            $formattedOrders = $orders->map(function ($order) {
                $dealerName = $order->dealer->dealer_name ?? 'N/A';

                return [
                    'id'            => $order->id,
                    'created_at'    => Carbon::parse($order->created_at)->format('d/m/Y'),
                    'dealer_name'   => $dealerName,
                    'order_status'  => $order->status,
                    'total_amount'  => (int) $order->total_amount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order approval list retrieved successfully',
                'data' => $formattedOrders
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while retrieving the order approval list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function orderApprovalDetails($orderId)
    {
        try {
            $user = Auth::user();
    
            // Only Sales Manager can access
            if ($user->employee_type_id !== 5) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Access denied. Only Sales Managers can view order details.'
                ], 403);
            }
    
            // Get dealers with due_balance > 0
            $dealerIdsWithDue = OutstandingNew::where('due_balance', '>', 0)->pluck('dealer_id');
    
            if ($dealerIdsWithDue->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No dealers with due balance found.'
                ], 404);
            }
    
            $employeeIds = Employee::whereIn('employee_type_id', [2, 3, 4])->pluck('id');
    
            $order = Order::where('id', $orderId)
                ->where(function ($query) use ($employeeIds, $dealerIdsWithDue) {
                    $query->where(function ($subQuery) use ($employeeIds, $dealerIdsWithDue) {
                        $subQuery->whereIn('created_by', $employeeIds)
                            ->whereIn('dealer_id', $dealerIdsWithDue)
                            ->where('dealer_flag_order', '0');
                    })->orWhere(function ($subQuery) use ($dealerIdsWithDue) {
                        $subQuery->whereIn('created_by_dealer', $dealerIdsWithDue)
                            ->where('dealer_flag_order', '1')
                            ->where('send_for_approval', '1');
                    });
                })
                ->with(['createdBy', 'dealer', 'orderType', 'paymentTerm', 'orderItems.product', 'vehicleCategory'])
                ->first();
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found or not eligible for approval.'
                ], 404);
            }
    
            // Employee & Dealer details
            $employee = $order->dealer_flag_order == 1
                ? Employee::find($order->send_for_approval_by)
                : $order->createdBy;
    
            $dealer = $order->dealer_flag_order == 1
                ? Dealer::find($order->created_by_dealer)
                : $order->dealer;
    
            $totalOutstandingAmount = OutstandingPayment::where('dealer_id', $dealer?->id)->sum('outstanding_amount');
    
            $dealerDetails = $dealer ? [
                'dealer_code' => $dealer->dealer_code ?? 'N/A',
                'dealer_name' => $dealer->dealer_name ?? 'N/A',
                'phone'       => $dealer->phone ?? 'N/A',
                'email'       => $dealer->email ?? 'N/A',
                'address'     => $dealer->address ?? 'N/A',
            ] : null;
    
            $employeeDetails = $employee ? [
                'employee_id' => $employee->employee_code ?? 'N/A',
                'name'        => $employee->name ?? 'N/A',
                'phone'       => $employee->phone ?? 'N/A',
                'designation' => $employee->designation ?? 'N/A',
            ] : null;
    
            $orderItems = $order->orderItems->map(function ($item) {
                return [
                    'product_id'     => $item->product_id,
                    'product_name'   => optional($item->product)->product_name ?? 'N/A',
                    'product_code'   => optional($item->product)->product_code ?? 'N/A',
                    'total_quantity' => (int) $item->total_quantity,
                    'product_details' => collect($item->product_details)->map(function ($detail) {
                        return [
                            'product_type_id' => $detail['product_type_id'],
                            'type_name'       => optional(ProductType::find($detail['product_type_id']))->type_name ?? 'N/A',
                            'quantity'        => (int) $detail['quantity'],
                            'rate'            => $detail['rate']
                        ];
                    })
                ];
            });
    
            $vehicleDetails = [
                'vehicle_category_id'   => $order->vehicleCategory->id ?? null,
                'vehicle_category_name' => $order->vehicleCategory->vehicle_category_name ?? 'N/A',
                'vehicle_number'        => $order->vehicle_number ?? null,
                'driver_name'           => $order->driver_name ?? null,
                'driver_phone'          => $order->driver_phone ?? null,
            ];
    
            $orderDetails = [
                'order_id'       => (int) $orderId,
                'employee_details' => $employeeDetails,
                'order_type'     => optional($order->orderType)->name ?? 'N/A',
                'payment_term'   => optional($order->paymentTerm)->name ?? 'N/A',
                'billing_date'   => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : 'N/A',
                'created_at'     => Carbon::parse($order->created_at)->format('d/m/Y'),
                'dealer_details' => $dealerDetails,
                'additional_information' => $order->additional_information,
                'order_status'   => $order->status ?? 'N/A',
                'total_amount'   => (int) $order->total_amount,
                'order_items'    => $orderItems->isEmpty() ? null : ($orderItems->count() === 1 ? $orderItems->first() : $orderItems),
                'total_outstanding_amount' => (float) $totalOutstandingAmount,
                'vehicle_category' => $vehicleDetails,
            ];
    
            return response()->json([
                'success'    => true,
                'statusCode' => 200,
                'message'    => 'Order details retrieved successfully.',
                'data'       => $orderDetails
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'statusCode' => 500,
                'message'    => 'An error occurred while retrieving the order details.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
    
    public function totalSalesLeadsSummary(Request $request)
    {
        $employee = Auth::user();
    
        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }
    
        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }
    
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4, 5])->pluck('id')->toArray();
        
        $orders = Order::whereIn('created_by', $employees)
            // ->where('status', 'Delivered')
            ->where('order_approved', '1')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
    
        $totalSalesQuantityTon = $orders->sum('invoice_quantity');
        $totalSalesOrderCount = $orders->count();
    
     
        $leads = Lead::whereIn('created_by', $employees)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
    
        $totalLeadsGenerated = $leads->count();
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'total_sales_quantity_ton' => (float) $totalSalesQuantityTon,
                'total_sales_order_count' => $totalSalesOrderCount,
                'total_leads_generated' => $totalLeadsGenerated,
            ]
        ]);
    }
    public function filteredLeadsSummary(Request $request)
    {
        $employee = Auth::user();
    
        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }
    
        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }
    
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $leadStatus = $request->input('lead_status', 'All');
        $customerType = $request->input('customer_type', 'All');
    
        $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4, 5])->pluck('id')->toArray();
    
        $leadsQuery = Lead::whereIn('created_by', $employees)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
    
        if (strtolower($leadStatus) !== 'all') {
            $leadsQuery->where('status', $leadStatus);
        }
    
        if (strtolower($customerType) !== 'all') {
            $leadsQuery->where('customer_type', intval($customerType));
        }
    
        $totalLeadsGenerated = $leadsQuery->count();
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'total_leads_generated' => $totalLeadsGenerated,
            ]
        ]);
    }
    public function totalOPCollection(Request $request)
    {
       $outstanding = OutstandingNew::all(); // or use ->get() if needed
        $totalOutstandingAmount = $outstanding->sum('outstanding_amount');
        $payments = Payment::all();
        $totalCollectionAmount = $payments->sum('payment_amount');
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'total_outstanding_amount' => (float) $totalOutstandingAmount,
                'total_collection_amount' => (float) $totalCollectionAmount,
            ]
           
        ]);
    }
    public function getTotalCreditNotes(Request $request)
    {
        $creditNotes = CreditNote::all();
    
        $totalAmount = $creditNotes->sum('total_row_amount');
        $totalCreditNotes = $creditNotes->count();
        $totalOrders = $creditNotes->pluck('order_id')->unique()->count();
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => [
                'credit_note_amount' => (float) $totalAmount,
                // 'credit_note_count' => $totalCreditNotes,
                // 'order_count' => $totalOrders
            ]
        ]);
    }

    public function getTotalSalesPerformance(Request $request)
    {
        $employee = Auth::user();
    
        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'region_id' => 'required|integer',
            'employee_type_id' => 'required|integer',
        ]);
      
        $districtIds = District::where('regions_id', $request->region_id)->pluck('id');

        $employees = Employee::where('employee_type_id', $request->employee_type_id)
            ->whereIn('district_id', $districtIds)
            ->with(['employeeType', 'district.region']) 
            ->get();

        $fromDate = Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth();
        $toDate = $fromDate->copy()->endOfMonth();

        $result = [];

        foreach ($employees as $employee) {
            $totalQty = Order::where('created_by', $employee->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('invoice_quantity');

            $result[] = [
                'region' => $employee->region?->name ?? 'N/A',
                'employee_type' => $employee->employeeType?->type_name ?? 'N/A',
                'employee_name' => $employee->name,
                'total_invoice_quantity' => (float) $totalQty,
            ];
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'data' => $result,
        ]);
    }
    public function getMostLeastPurchaseCustomer(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);
    
      
        $fromDate = Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth()->format('Y-m-d H:i:s');
        $toDate = Carbon::createFromDate($request->year, $request->month, 1)->endOfMonth()->format('Y-m-d H:i:s');
  
        $finalTotals = Order::selectRaw("
            CASE 
                WHEN dealer_flag_order = '1' THEN created_by_dealer 
                ELSE dealer_id 
            END as dealer_id,
            SUM(invoice_quantity) as total_quantity
        ")
        ->where('status', '!=', 'Rejected')
        ->whereBetween('created_at', [$fromDate, $toDate])
        ->where(function ($query) {
            $query->where(function ($q) {
                $q->where('dealer_flag_order', '1')
                    ->whereNotNull('created_by_dealer');
            })->orWhere(function ($q) {
                $q->where('dealer_flag_order', '0')
                    ->whereNotNull('dealer_id')
                    ->whereHas('createdBy', function ($empQuery) {
                        $empQuery->where('employee_type_id', '!=', 1); // Not SE
                    });
            });
        })
        ->groupBy(DB::raw("CASE 
                WHEN dealer_flag_order = '1' THEN created_by_dealer 
                ELSE dealer_id 
            END"))
        ->having('total_quantity', '>', 0)
        ->orderByDesc('total_quantity')
        ->get();

        if ($finalTotals->isEmpty()) {
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'No orders found for the selected month and year.',
                'data' => null,
            ]);
        }


        $most = $finalTotals->first();        
        $least = $finalTotals->last();       
    
        $mostDealer = Dealer::find($most->dealer_id);
        $leastDealer = Dealer::find($least->dealer_id);
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'data' => [
                'most_purchased' => [
                    'dealer_id' => $most->dealer_id,
                    'dealer_name' => $mostDealer?->dealer_name ?? 'N/A',
                    'total_quantity' => (float) $most->total_quantity,
                ],
                'least_purchased' => [
                    'dealer_id' => $least->dealer_id,
                    'dealer_name' => $leastDealer?->dealer_name ?? 'N/A',
                    'total_quantity' => (float) $least->total_quantity,
                ],
            ],
        ]);
    }
    public function getHighestLowestSellingItems(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
    
        if (!$month || !$year) {
            return response()->json([
                'status' => false,
                'statusCode' => 422,
                'message' => 'Month and Year are required.'
            ], 422);
        }
    
        // Step 1: Get approved order IDs within the month & year
        $orderIds = \App\Models\Order::where('order_approved', '1')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->where('dealer_flag_order', '0')
                        ->whereHas('createdBy', function ($q2) {
                            $q2->where('employee_type_id', '!=', 1);
                        });
                })->orWhere('dealer_flag_order', '1');
            })
            ->pluck('id');
    
        // Step 2: Accumulate total quantity by product_type_id
        $productSales = [];
    
        $orderItems = \App\Models\OrderItem::whereIn('order_id', $orderIds)->get();
    
        foreach ($orderItems as $item) {
            foreach ($item->product_details as $detail) {
                $typeId = $detail['product_type_id'];
                $qty = $detail['quantity'];
    
                if (!isset($productSales[$typeId])) {
                    $productSales[$typeId] = 0;
                }
    
                $productSales[$typeId] += $qty;
            }
        }
    
        // Step 3: Determine highest and lowest selling items
        $highestSelling = null;
        $lowestSelling = null;
    
        if (!empty($productSales)) {
            $maxTypeId = array_keys($productSales, max($productSales))[0];
            $minTypeId = array_keys($productSales, min($productSales))[0];
    
            $highest = \App\Models\ProductType::find($maxTypeId);
            $lowest = \App\Models\ProductType::find($minTypeId);
    
            $highestSelling = [
                'product_type_id' => $highest->id ?? null,
                'product_type_name' => $highest->type_name ?? 'Unknown',
                'total_quantity' => $productSales[$maxTypeId],
            ];
    
            $lowestSelling = [
                'product_type_id' => $lowest->id ?? null,
                'product_type_name' => $lowest->type_name ?? 'Unknown',
                'total_quantity' => $productSales[$minTypeId],
            ];
        }
    
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'data' => [
                'highest_selling_item' => $highestSelling,
                'lowest_selling_item' => $lowestSelling,
            ]
        ]);
    }
    public function createDealerVisit(Request $request)
    {

        DB::beginTransaction();

        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $rules = [
                'dealer_id' => 'required|exists:dealers,id',
                'aso_id' => 'nullable|exists:employees,id',
                'purpose_of_visit' => 'required|string',
                'remarks' => 'nullable|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|string'
            ];

            $purpose = $request->input('purpose_of_visit');

            if ($purpose === 'Gift/Marketing Activity') {
                $rules['item_type'] = 'required|string';
            }

            if ($purpose === 'Stock Taking') {
                $rules['stocks'] = 'required|array';
                $rules['stocks.*.type'] = 'required|in:TATA,Other Brands';
                $rules['stocks.*.quantity'] = 'required|numeric|min:0';
                $rules['stocks.*.brand'] = 'nullable|string';
            }

            if ($purpose === 'Casual Visit') {
                $rules['new_order'] = 'required|in:Yes,No';
                if ($request->input('new_order') === 'Yes') {
                    $rules = array_merge($rules, $this->getOrderValidationRules($employee));
                }
            }

            if ($purpose === 'Order Taking') {
                $rules = array_merge($rules, $this->getOrderValidationRules($employee));
            }

            $validatedData = $request->validate($rules);

            // Handle file uploads
            $uploadedAttachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $uploadedAttachments[] = $file->store('dealer_visits', 'public');
                }
            }

            // Create Dealer Visit
            $dealerVisit = DealerVisit::create([
                'dealer_id' => $validatedData['dealer_id'],
                'aso_id' => $validatedData['aso_id'] ?? null,
                'purpose_of_visit' => $validatedData['purpose_of_visit'],
                'item_type' => $validatedData['item_type'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
                'attachments' => !empty($uploadedAttachments)
                ? $uploadedAttachments
                : ($validatedData['attachments'] ?? []),
                'stock_details' => $validatedData['stocks'] ?? null,
                'new_order' => $validatedData['new_order'] ?? null,
                'created_by' => $employee->id,
            ]);

            $order = null;

            if (
                ($purpose === 'Order Taking') ||
                ($purpose === 'Casual Visit' && $request->input('new_order') === 'Yes')
            ) {
                $orderData = $request->only([
                    'order_type', 'customer_type_id', 'order_category', 'lead_id', 'dealer_id','product_id',
                    'payment_terms_id', 'credit_days', 'advance_amount', 'payment_date', 'utr_number',
                    'billing_date', 'delivery_date', 'total_amount', 'additional_information', 'status', 'source',
                    'vehicle_category_id', 'vehicle_type', 'vehicle_number', 'driver_name', 'driver_phone',
                    'scheme', 'order_items'
                ]);

                $orderData['created_by'] = $employee->id;

                if ($employee->employee_type_id != 1) {
                    $orderData['order_approved'] = '0';
                }

                // Format dates
                if (!empty($orderData['payment_date'])) {
                    $orderData['payment_date'] = Carbon::createFromFormat('d-m-Y', $orderData['payment_date'])->format('Y-m-d');
                }
                if (!empty($orderData['billing_date'])) {
                    $orderData['billing_date'] = Carbon::createFromFormat('d-m-Y', $orderData['billing_date'])->format('Y-m-d');
                }
                if (!empty($orderData['delivery_date'])) {
                    $orderData['delivery_date'] = Carbon::createFromFormat('d-m-Y', $orderData['delivery_date'])->format('Y-m-d');
                }

                // Create Order
                $orderData['dealer_visit_id'] = $dealerVisit->id;
                $order = Order::create($orderData);

                if (!empty($orderData['order_items'])) {
                    foreach ($orderData['order_items'] as $item) {
                        $totalQty = 0;
                        $totalPieces = 0;
                        $totalTon = 0;

                        if (!empty($item['product_details'])) {
                            foreach ($item['product_details'] as $detail) {
                                // Flexible quantity key handling
                                if (isset($detail['quantity'])) {
                                    $totalQty += $detail['quantity'];
                                }
                                if (isset($detail['pieces'])) {
                                    $totalPieces += $detail['pieces'];
                                }
                                if (isset($detail['tonnage'])) {
                                    $totalTon += $detail['tonnage'];
                                }
                            }
                        }

                        // Assign totals based on available data
                        $item['total_quantity'] = round($totalQty, 6);
                        $item['total_pieces'] = round($totalPieces, 6);
                        $item['total_ton'] = round($totalTon, 6);

                        $order->orderItems()->create($item);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer Visit created successfully.',
                'data' => $dealerVisit,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    private function getOrderValidationRules($employee)
    {
        $rules = [
            'order_type' => 'nullable|exists:order_types,id',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'order_category' => 'nullable|string',
            'lead_id' => 'nullable|exists:leads,id',
            'dealer_id' => 'required|exists:dealers,id',
            'payment_terms_id' => 'required|exists:payment_terms,id',
            'credit_days' => 'nullable|string',
            'advance_amount' => 'nullable|numeric',
            'payment_date' => 'nullable|string',
            'utr_number' => 'nullable|string',
            'billing_date' => 'required|string',
            'total_amount' => 'nullable|numeric',
            'additional_information' => 'nullable|string',
            'status' => 'nullable|in:Pending,Dispatched,Delivered',
            'vehicle_category_id' => 'nullable|integer',
            'vehicle_type' => 'nullable|string',
            'vehicle_number' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'scheme' => 'nullable|string',
            'driver_phone' => 'nullable|string',
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.product_details' => 'nullable|array',
        ];

        $rules['delivery_date'] = $employee->employee_type_id != 1 ? 'required|string' : 'nullable|string';

        return $rules;
    }
    public function dealerVisitListing(Request $request)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $visits = DealerVisit::with('dealer:id,dealer_name')
            ->where('created_by', $employee->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($visit) {
                    return [
                        'dealer_visit_id' => $visit->id,
                        'dealer_name' => $visit->dealer->dealer_name ?? 'N/A',
                        'date' => Carbon::parse($visit->created_at)->format('d/m/Y'),
                        'purpose_of_visit' => $visit->purpose_of_visit,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer Visit List retrieved successfully.',
                'data' => $visits,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
     
    public function dealerVisitDetails($id)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $dealerVisit = DealerVisit::with([
            'dealer:id,dealer_name,dealer_code,address,district',
            'aso:id,employee_code,name',
            'creator:id,name',
            'order.orderItems.product:id,product_name,product_code',
            'order.orderType:id,name',
            'order.customerType:id,name',
            'order.paymentTerm:id,name',
            'order.vehicleCategory:id,vehicle_category_name'
        ])->findOrFail($id);

        $data = [
            'dealer_visit_id' => $dealerVisit->id,
            'dealer_name' => $dealerVisit->dealer->dealer_name,
            'dealer_code' => $dealerVisit->dealer->dealer_code,
            'district'    => $dealerVisit->dealer->district,
            'aso'         => $dealerVisit->aso ? $dealerVisit->aso->name : null,
            'purpose_of_visit' => $dealerVisit->purpose_of_visit,
        ];

        // Add details based on purpose of visit
        switch ($dealerVisit->purpose_of_visit) {
            case 'Gift/Marketing Activity':
                $data['item_type'] = $dealerVisit->item_type;
                $data['remarks'] = $dealerVisit->remarks;
                $data['attachments'] = $dealerVisit->attachments ?? [];
                break;

            case 'Stock Taking':
                $data['stock_details'] = $dealerVisit->stock_details ?? [];
                $data['remarks'] = $dealerVisit->remarks;
                $data['attachments'] = $dealerVisit->attachments ?? [];
                break;

            case 'Casual Visit':
                $data['new_order'] = $dealerVisit->new_order;
                if ($dealerVisit->new_order === 'Yes' && $dealerVisit->order) {
                    $data['order'] = $this->formatOrderDetails($dealerVisit->order);
                    $data['attachments'] = $dealerVisit->attachments ?? [];
                }
                break;

            case 'Order Taking':
                if ($dealerVisit->order) {
                    $data['order'] = $this->formatOrderDetails($dealerVisit->order);
                    $data['attachments'] = $dealerVisit->attachments ?? [];
                }
                break;
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Dealer Visit details fetched successfully.',
            'data' => $data
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatOrderDetails($order)
    {
        return [
            'order_type' => $order->orderType?->name,
            'customer_type' => $order->customerType?->name,
            'customer_phone' => $order->lead?->phone,
            'customer_name' => $order->lead?->customer_name,
            'address' => $order->dealer?->address ?? $order->lead?->address,
            'payment_type' => $order->paymentTerm?->name,
            'credit_days' => $order->credit_days,
            'billing_date' => $order->billing_date->format("d/M/Y"),
            'delivery_date' => $order->delivery_date->format("d/M/Y"),

            'product_details' => $order->orderItems->map(function ($item) {
                $productDetails = collect($item->product_details)->map(function ($detail) {
                    $productType = \App\Models\ProductType::find($detail['product_type_id']);

                    return [
                        'product_type_id' => $detail['product_type_id'],
                        'type_name' => $productType->type_name ?? null,
                        'quantity' => isset($detail['quantity']) ? (float)$detail['quantity'] : null,
                        'pieces' => isset($detail['pieces']) ? (float)$detail['pieces'] : null,
                        'tonnage' => isset($detail['tonnage']) ? (float)$detail['tonnage'] : null,
                        'rate' => $detail['rate'] ?? null,
                        'quantity_type' => $detail['quantity_type'] ?? null,
                    ];
                });

                // Compute total pieces and total tonnage
                $totalPieces = $productDetails->sum('pieces');
                $totalTonnage = $productDetails->sum(function ($detail) {
                    return ($detail['pieces'] ?? 0) * ($detail['tonnage'] ?? 0);
                });
                $totalQty = $productDetails->sum('quantity');

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? null,
                    'product_code' => $item->product->product_code ?? null,
                    'total_quantity' => (float)$totalQty,
                    'total_pieces' => (float)$totalPieces,
                    'total_ton' => $totalTonnage > 0 ? $totalTonnage : null,
                    'product_details' => $productDetails,
                ];
            }),

            // Calculate overall totals across all order items
            // 'total_quantity' => $order->orderItems->map(function ($item) {
            //     return collect($item->product_details)->sum('quantity');
            // })->sum(),

            // 'total_pieces' => $order->orderItems->map(function ($item) {
            //     return collect($item->product_details)->sum('pieces');
            // })->sum(),

            // 'total_ton' => $order->orderItems->map(function ($item) {
            //     return collect($item->product_details)->sum('tonnage');
            // })->sum(),

            'total_amount' => $order->total_amount,
            'vehicle_category' => $order->vehicleCategory?->vehicle_category_name,
            'vehicle_number' => $order->vehicle_number,
            'driver_name' => $order->driver_name,
            'driver_phone' => $order->driver_phone,
            'additional_info' => $order->additional_information,
        ];
    }
    
    public function SalesReportExport(Request $request)
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

            if ($employee->employee_type_id != 5) { // Ensure only SM accesses
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Access denied. Only Sales Managers can export this report.",
                ], 403);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4])->get();
            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No employees found.",
                ], 404);
            }

            $exportData = [];

            foreach ($employees as $emp) {
                $orders = Order::with(['orderItems', 'paymentTerm', 'createdBy'])
                    ->where('created_by', $emp->id)
                    ->where('order_approved', '1')
                    // ->where('status', 'Delivered')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->get();

                foreach ($orders as $order) {
                    $flattenedItems = [];

                    foreach ($order->orderItems as $item) {
                        foreach ($item->product_details as $detail) {
                            $productType = \App\Models\ProductType::find($detail['product_type_id']);
                            $flattenedItems[] = $item->product->product_name . ' (' . ($productType->type_name ?? 'Unknown') . '): ' . $detail['quantity'] . ' - Rate: ' . $detail['rate'];
                        }
                    }
    
                    $orderItemsString = implode('; ', $flattenedItems);
    
                    $exportData[] = [
                        'employee_name'     => $emp->name,
                        'employee_code'     => $emp->employee_code,
                        'invoice_total'     => (float) $order->invoice_total,
                        'invoice_quantity'  => (float) $order->invoice_quantity,
                        'invoice_date'      => $order->Invoice_date ? $order->Invoice_date->format('d/m/Y') : null,
                        'invoice_number'    => $order->invoice_number,
                        'order_created_at'  => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                        'payment_terms'     => $order->paymentTerm->name ?? null,
                        'credit_days'       => $order->credit_days,
                        'order_items'       => $orderItemsString,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales export report fetched successfully for $month/$year.",
                'data' => $exportData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function leadsReportExport(Request $request)
    {
        $employee = Auth::user();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }

        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $leadStatus = $request->input('lead_status', 'All');
        $customerType = $request->input('customer_type', 'All');

        $employees = Employee::whereIn('employee_type_id', [1, 2, 3, 4, 5])->pluck('id');

        $leadsQuery = Lead::with([
            'customerType',
            'district',
            'assignRoute',
            'createdBy',
            'orders.orderItems',
            'orders.dealer',
            'orders.paymentTerm'
        ])
        ->whereIn('created_by', $employees)
        ->whereYear('created_at', $year)
        ->whereMonth('created_at', $month);

        if (strtolower($leadStatus) !== 'all') {
            $leadsQuery->where('status', $leadStatus);
        }

        if (strtolower($customerType) !== 'all') {
            $leadsQuery->where('customer_type', intval($customerType));
        }

        $leads = $leadsQuery->get();

        $formattedLeads = [];

        foreach ($leads as $index => $lead) {
            $order = $lead->orders->first(); // assuming the first order is the relevant one

            $flattenedItems = [];

            if ($lead->status === 'Won' && $order) {
                $orderItems = OrderItem::where('order_id', $order->id)->get();

                foreach ($orderItems as $item) {
                    foreach ($item->product_details as $detail) {
                        $productType = ProductType::find($detail['product_type_id']);
                        $flattenedItems[] = $item->product->product_name . ' (' . ($productType->type_name ?? 'Unknown') . '): ' . $detail['quantity'] . ' - Rate: ' . $detail['rate'];
                    }
                }
            }

            $formattedLeads[] = [
                'serial_no' => $index + 1,
                'customer_type' => optional($lead->customerType)->name,
                'customer_name' => $lead->customer_name,
                'city' => $lead->city,
                'location' => $lead->location,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'district' => optional($lead->district)->name,
                'type_of_visit' => $lead->type_of_visit,
                'construction_type' => $lead->construction_type,
                'stage_of_construction' => $lead->stage_of_construction,
                'follow_up_date' => $lead->follow_up_date,
                'lead_score' => $lead->lead_score,
                'lead_source' => $lead->lead_source,
                'source_name' => $lead->source_name,
                'total_quantity' => (float) $lead->total_quantity,
                'status' => $lead->status,

                // Fields for Lost
                'lost_volume' => $lead->status === 'Lost' ? (float) $lead->lost_volume : null,
                'lost_to_competitor' => $lead->status === 'Lost' ? $lead->lost_to_competitor : null,
                'reason_for_lost' => $lead->status === 'Lost' ? $lead->reason_for_lost : null,

                // Fields for Won
                'order_total_amount' => $lead->status === 'Won' ? optional($order)->total_amount : null,
                'dealer_name' => $lead->status === 'Won' ? optional($order?->dealer)->dealer_name : null,
                'payment_term' => $lead->status === 'Won' ? optional($order?->paymentTerm)->name : null,
                'order_items' => $lead->status === 'Won' ? implode(' | ', $flattenedItems) : null,

                'employee_name' => optional($lead->createdBy)->name,
                'employee_code' => optional($lead->createdBy)->employee_code,
		        'created_at' => optional($lead->created_at),
            ];
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => $formattedLeads
        ]);
    }
    public function exportOutstandingPayments(Request $request)
    {
        $employee = Auth::user();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }

        $outstandings = OutstandingNew::all();
        $grouped = $outstandings->groupBy('dealer_id');

        $data = [];
        $serialNo = 1;

        foreach ($grouped as $dealerId => $dealerOutstandings) {
            $dealer = Dealer::find($dealerId);

            $data[] = [
                's_no' => $serialNo++,
                'dealer_code' => $dealer->dealer_code ?? 'N/A',
                'dealer_name' => $dealer->dealer_name ?? 'N/A',
                'outstanding_amount' => $dealerOutstandings->sum('outstanding_amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Outstanding payment summary fetched successfully.',
            'data' => $data,
        ]);
    }
    public function exportTotalOPCollection(Request $request)
    {
        $employee = Auth::user();

        // Authentication check
        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        // Optional: restrict by employee type, example: Sales Manager
        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager (SM) can access this summary.",
            ], 403);
        }

        $today = Carbon::today();
        $startYear = $today->month < 4 ? $today->year - 1 : $today->year;
        $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
        $endDate = Carbon::createFromDate($startYear + 1, 3, 31)->endOfDay();

        // Get payments only within this financial year
        $payments = Payment::with('dealer')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date')
            ->get();
        $data = [];
        $serialNo = 1;
        
        foreach ($payments as $payment) {	    
        $row = [
            's_no' => $serialNo++,
            'dealer_code' => $payment->dealer->dealer_code ?? 'N/A',
            'dealer_name' => $payment->dealer->dealer_name ?? 'N/A',
            'invoice_number' => $payment->invoice_number,
            'payment_amount' => $payment->payment_amount,
            'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
            'payment_document_no' => $payment->payment_document_no,
        ];

    


        $data[] = $row;
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Total outstanding payment collection fetched successfully.',
            'data' => $data,
        ]);
    }
	public function exportCreditNotes(Request $request)
    {
        $employee = Auth::user();

        // Check if user is authenticated
        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        // Optional: Restrict to SMs
        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager can access this summary.",
            ], 403);
        }

        // Get month and year from request or default to current
        $month = $request->input('month', now()->month); // 1-12
        $year  = $request->input('year', now()->year);

        // Fetch credit notes with dealer data
        $creditNotes = CreditNote::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', 'open')
            ->with('dealer')
            ->orderBy('date')
            ->get();

        $data = [];
        $serial = 1;

        foreach ($creditNotes as $note) {
            $data[] = [
                's_no' => $serial++,
                'dealer_code' => $note->dealer->dealer_code ?? 'N/A',
                'dealer_name' => $note->dealer->dealer_name ?? 'N/A',
                'credit_note_number' => $note->credit_note_number,
                'date' => optional($note->date)->format('Y-m-d'),
                'returned_items' => $note->returned_items, // raw array or use json_encode if preferred
                'total_return_qty' => $note->total_return_quantity,
                'total_row_amount' => $note->total_row_amount,
            ];
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Credit notes for selected month/year fetched successfully.',
            'data' => $data,
        ]);
	}

	public function getUniqueLeads(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\UniqueLeadsExport($year, $month);

        // Get all leads
        $collection = $export->collection();

        // Headings
        $headings = $export->headings();

        // Map data
        $rows = $collection->map(fn($lead) => $export->map($lead))->toArray();

        // Combine into list of associative arrays
        $resultList = [];

        foreach ($rows as $row) {
            $resultList[] = array_combine($headings, $row);
        }

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
	}

    public function getInfluencerVisits(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\InfluencerVisitsExport($year, $month);
        $collection = $export->collection();
        $headings = $export->headings();

        $rows = $collection->map(fn($route) => $export->map($route))->toArray();
        $resultList = array_map(fn($row) => array_combine($headings, $row), $rows);

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
    }
	public function getAashiyanaOrders(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\AashiyanaOrdersExport($year, $month);
        $collection = $export->collection();
        $headings = $export->headings();

        $rows = $collection->map(fn($order) => $export->map($order))->toArray();
        $resultList = array_map(fn($row) => array_combine($headings, $row), $rows);

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
    }
    public function getTisconOrders(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\TisconOrdersExport($year, $month);
        $collection = $export->collection();
        $headings = $export->headings();

        $rows = $collection->map(fn($employee) => $export->map($employee))->toArray();
        $resultList = array_map(fn($row) => array_combine($headings, $row), $rows);

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
    }
    public function getDealerVisits(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\DealerVisitExport($month, $year); // Note: order of params
        $collection = $export->collection();
        $headings = $export->headings();

        $rows = $collection->map(fn($visit) => $export->map($visit))->toArray();
        $resultList = array_map(fn($row) => array_combine($headings, $row), $rows);

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
    }
    public function getInfluencerVisitDetails(Request $request)
    {
        if ($authError = $this->authorizeSalesManager()) {
            return $authError;
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month - 1);

        $export = new \App\Exports\InfluencerVisitExport($month, $year); // Note: order of params
        $collection = $export->collection();
        $headings = $export->headings();

        $rows = $collection->map(fn($visit) => $export->map($visit))->toArray();
        $resultList = array_map(fn($row) => array_combine($headings, $row), $rows);

        return response()->json([
            'success' => true,
            'data' => $resultList,
        ]);
    }


    private function authorizeSalesManager()
    {
        $employee = Auth::user();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not authenticated.",
            ], 401);
        }

        if ($employee->employee_type_id !== 5) {
            return response()->json([
                'success' => false,
                'statusCode' => 403,
                'message' => "Unauthorized. Only Sales Manager can access this summary.",
            ], 403);
        }

        return null; // No issues
    }
	public function getDealerVisitData(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Month and year are required.',
                'data' => null
            ], 400);
        }

        $totalDealerVisit = DealerVisit::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Dealer visit data retrieved successfully.',
            'data' => [
                'totalDealerVisit' => $totalDealerVisit
            ]
        ], 200);
    }

    public function getInfluencerVisitData(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Month and year are required.',
                'data' => null
            ], 400); 
        }

        $totalInfluencerVisit = InfluencerVisit::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Influencer visit data retrieved successfully.',
            'data' => [
                'totalInfluencerVisit' => $totalInfluencerVisit
            ]
        ], 200);
    }
}
