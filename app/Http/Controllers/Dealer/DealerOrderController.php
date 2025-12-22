<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dealer;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\ProductType;
use App\Models\CreditNote;
use App\Models\CreditDays;
use App\Models\OutstandingPaymentCommitment;
use App\Models\OutstandingPayment;
use App\Models\AssignRoute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DealerOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
           
            $dealer = Auth::user();
            if($dealer)
            {
                $orders = Order::where('created_by_dealer', $dealer->id)
                ->where('dealer_flag_order', "1")
                ->with([
                    'dealer:id,dealer_name,dealer_code',
                    'orderItems:id,order_id,total_quantity' 
                ])
                ->select('id', 'total_amount', 'status', 'created_at', 'created_by_dealer')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($order) {
                    $order->total_amount = (float) sprintf("%.2f", $order->total_amount);
                    $order->total_quantity = $order->orderItems->sum('total_quantity'); 
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
                            'total_quantity' => $order->total_quantity,
                            'status' => $order->status,
                            'created_at' => $order->created_at->format('d/m/Y'),
			                'dealer' => [
                               'name' => $order->dealers->dealer_name,
                              'dealer_code' => $order->dealers->dealer_code, 
                            ],
                        ];
                    }),
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    // public function store(Request $request)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $validatedData = $request->validate([
    //             'order_type' => 'nullable|exists:order_types,id',
    //             'payment_terms_id' => 'required|exists:payment_terms,id',
    //             'credit_days' => 'nullable|string',
	// 	        'billing_date' => 'required|string',
	// 	        'delivery_date' => 'nullable|string',
	//             'scheme' => 'nullable',
    //             'total_amount' => 'nullable|numeric',
    //             'additional_information' => 'nullable|string',
    //             'status' => 'nullable|in:Pending,Dispatched,Delivered',
    //             'vehicle_category_id' => 'required|integer',
             
    //             'vehicle_number' => [
    //                 Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
    //             ],
    //             'driver_name' => [
    //                 Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
    //             ],
    //             'driver_phone' => [
    //                 Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
    //             ],
    //             // 'vehicle_type' => 'nullable|string',
    //             'order_items' => 'required|array',
    //             'order_items.*.product_id' => 'required|exists:products,id',
    //             'order_items.*.product_details' => 'nullable|array',
    //             'attachment' => 'nullable|array',
    //             'attachment.*' => 'nullable|string',
    //         ]);

    //         $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
        
    //         $validatedData['created_by'] = null;
    //         $validatedData['created_by_dealer'] = $dealer->id;
    //         $validatedData['dealer_flag_order'] = '1';
    //         $validatedData['order_Approved'] = '0';

    //         $order = Order::create($validatedData);
           
    //         if (!empty($validatedData['order_items'])) {
    //             foreach ($validatedData['order_items'] as $orderItem) {
    //                 $totalQuantity = 0;
    //                 if (!empty($orderItem['product_details'])) {
    //                     foreach ($orderItem['product_details'] as $productDetail) {
    //                         $totalQuantity += $productDetail['quantity'];
    //                     }
    //                 }
            
    //                 $orderItem['total_quantity'] = $totalQuantity;
            
    //                 $order->orderItems()->create($orderItem);
    //             }
    //         }
            

    //         $responseData = [
    //                 'order_type' => $order->order_type,
    //                 'payment_terms_id' => $order->payment_terms_id,
    //                 'credit_days' => $order->credit_days,
    //                 'billing_date' => Carbon::parse($order->billing_date)->format('d/m/Y'),
    //                 'total_amount' => round($order->total_amount, 2),
    //                 'additional_information' => $order->additional_information,
    //                 'status' => $order->status,
    //                 'created_by_dealer' => $order->created_by_dealer,
    //                 'dealer_flag_order' => $order->dealer_flag_order,
    //                 'vehicle_category_id' => $order->vehicle_category_id,
    //                 // 'vehicle_type' => $order->vehicle_type,
    //                 'vehicle_number' => $order->vehicle_number,
    //                 'driver_name' => $order->driver_name,
    //                 'driver_phone' => $order->driver_phone,
    //                 'updated_at' => Carbon::parse($order->updated_at)->format('d/m/Y'),
    //                 'created_at' => Carbon::parse($order->created_at)->format('d/m/Y'),
    //                 'id' => $order->id,

    //         ];
            
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order created successfully!',
    //             'data' => $responseData
    //         ], 200);
            

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // } 

    public function store(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $validatedData = $request->validate([
                'order_type' => 'nullable|exists:order_types,id',
                'payment_terms_id' => 'required|exists:payment_terms,id',
                'credit_days' => 'nullable|string',
                'billing_date' => 'required|string',
                'delivery_date' => 'nullable|string',
                'scheme' => 'nullable',
                'total_amount' => 'nullable|numeric',
                'additional_information' => 'nullable|string',
                'status' => 'nullable|in:Pending,Dispatched,Delivered',
                'vehicle_category_id' => 'required|integer',

                'vehicle_number' => [
                    Rule::requiredIf($request->vehicle_category_id == 1), 'nullable', 'string'
                ],
                'driver_name' => [
                    Rule::requiredIf($request->vehicle_category_id == 1), 'nullable', 'string'
                ],
                'driver_phone' => [
                    Rule::requiredIf($request->vehicle_category_id == 1), 'nullable', 'string'
                ],

                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.product_details' => 'nullable|array',

                'attachment' => 'nullable|array',
                'attachment.*' => 'nullable|string',
            ]);

            // $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
            $validatedData['billing_date'] = Carbon::createFromFormat('d/m/Y', $validatedData['billing_date'])->format('Y-m-d');

            if (!empty($validatedData['delivery_date'])) {
                $validatedData['delivery_date'] = Carbon::createFromFormat('d/m/Y', $validatedData['delivery_date'])->format('Y-m-d');
            }
            $validatedData['created_by'] = null;
            $validatedData['created_by_dealer'] = $dealer->id;
            $validatedData['dealer_flag_order'] = '1';
            $validatedData['order_approved'] = '0';

            $validatedData['product_id'] = $validatedData['order_items'][0]['product_id'] ?? null;

            $order = Order::create($validatedData);

            if (!empty($validatedData['order_items'])) {
                foreach ($validatedData['order_items'] as $orderItem) {

                    $totalQuantity = 0;

                    if (!empty($orderItem['product_details'])) {
                        $productDetailsArray = [];

                        foreach ($orderItem['product_details'] as $productDetail) {

                            if (isset($productDetail['pieces'])) {
                                $totalQuantity += (float)$productDetail['pieces'];
                            }
                            // if($validatedData['product_id']==)
                            if (isset($productDetail['tonnage'])) {
                                $totalQuantity += (float)$productDetail['tonnage'];
                            }

                            $typeName = \App\Models\ProductType::where('id', $productDetail['product_type_id'])
                                ->value('type_name');

                            $productDetail['type_name'] = $typeName ?? null;

                            $productDetailsArray[] = $productDetail;
                        }

                        $orderItem['product_details'] = $productDetailsArray;
                    } else {
                        $totalQuantity = (float)($orderItem['quantity'] ?? 0);
                        $orderItem['product_details'] = null;
                    }

                    $orderItem['total_quantity'] = round($totalQuantity, 6);

                    $order->orderItems()->create($orderItem);
                }
            }

            $responseData = [
                'order_type' => $order->order_type,
                'payment_terms_id' => $order->payment_terms_id,
                'credit_days' => $order->credit_days,
                'billing_date' => Carbon::parse($order->billing_date)->format('d/m/Y'),
                'total_amount' => round($order->total_amount, 2),
                'additional_information' => $order->additional_information,
                'status' => $order->status,
                'created_by_dealer' => $order->created_by_dealer,
                'dealer_flag_order' => $order->dealer_flag_order,
                'vehicle_category_id' => $order->vehicle_category_id,
                'vehicle_number' => $order->vehicle_number,
                'driver_name' => $order->driver_name,
                'driver_phone' => $order->driver_phone,
                'updated_at' => Carbon::parse($order->updated_at)->format('d/m/Y'),
                'created_at' => Carbon::parse($order->created_at)->format('d/m/Y'),
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
                'statusCode'=> 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function show($orderId)
    // {
    //     try {
    //         $user = Auth::user();
    
    //         if ($user === null) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'You must be logged in to view this order.'
    //             ], 400);
    //         }
    
    //         $order = Order::with([
    //             'orderType:id,name',
    //             'dealers:id,dealer_name,dealer_code',
    //             'orderItems.product:id,product_name',
    //             'orderItems',
    //             'paymentTerm:id,name',
    //             'vehicleCategory:id,vehicle_category_name'
    //         ])->findOrFail($orderId);
    
    //         $responseData = [
    //             'id' => $order->id,
    //             'order_type' => $order->orderType->name ?? null,
    //             'dealer' => [
    //                 'id' => $order->dealers->id ?? null,
    //                 'name' => $order->dealers->dealer_name ?? null,
    //                 'code' => $order->dealers->dealer_code ?? null,
    //             ],
    //             'payment_terms' => [
    //                 'id' => $order->paymentTerm->id ?? null,
    //                 'name' => $order->paymentTerm->name ?? null,
    //             ],
    //             'credit_days' => $order->credit_days,
    //             'billing_date' => $order->billing_date,
    //             'delivery_date' => $order->delivery_date,
    //             'total_amount' => round($order->total_amount, 2),
    //             'additional_information' => $order->additional_information,
    //             'status' => $order->status,
    //             'created_by_dealer' => $order->created_by_dealer,
    //             'dealer_flag_order' => $order->dealer_flag_order,
    //             'vehicle' => [
    //                 'category_id' => $order->vehicle_category_id,
    //                 'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
    //                 'vehicle_number' => $order->vehicle_number,
    //                 'driver_name' => $order->driver_name,
    //                 'driver_phone' => $order->driver_phone,
    //             ],
    //             'track_order' =>[
    //                 'accepted_time'   => $order->accepted_time,
    //                 'rejected_time'   => $order->rejected_time,
    //                 'dispatched_time' => $order->dispatched_time,
    //                 'intransit_time'  => $order->intransit_time,
    //                 'delivered_time'  => $order->delivered_time,
    //             ],
    //             'attachments' => $order->attachment ?? [],
                
    
    //             'order_items' => $order->orderItems->map(function ($item) {

    //                 return [
    //                     'product_id' => $item->product_id,
    //                     'product_name' => $item->product->product_name ?? 'N/A',
    //                     'total_quantity' => $item->total_quantity,
    //                     'balance_quantity' => $item->balance_quantity,
    //                     'product_details' => collect($item->product_details)->map(function ($detail) {
    //                         return [
    //                             'product_type_id' => $detail['product_type_id'],
    //                             'quantity' => $detail['quantity'],
    //                             'rate' => $detail['rate'],
    //                             'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
    //                         ];
    //                     }),
    //                 ];
    //             }),
    
    //             'created_at' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y') : null,
               
                
	//     ];
    //     if($order->dealer_flag_order!="1"){
    //         //......................notification..............
    //     $authController = new AuthController();
    //     $authController->changeNotificationStatus('orders', $orderId,'opened');
    //     }
    //     return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order details fetched successfully',
    //             'data' => $responseData,
    //         ], 200);
    
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
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
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name,product_code',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name',
            ])->findOrFail($orderId); 

            // --- Process Order Items ---
            // --- Process Order Items ---
            if ($order->orderItems && count($order->orderItems)) {
                foreach ($order->orderItems as $item) {

                    // Convert numeric fields
                    $item->total_quantity = (float) $item->total_quantity;

                    // Remove quantity_type if exists
                    unset($item->quantity_type);

                    // Calculate totals
                    $totalPieces = 0;
                    $totalTon = 0;

                    // Work on a copied array (Laravel casted attribute cannot be modified directly)
                    $productDetails = $item->product_details ?? [];

                    if (is_array($productDetails)) {
                        foreach ($productDetails as $key => $pd) {

                            // Total calculation
                            $totalPieces += isset($pd['pieces']) ? (float)$pd['pieces'] : 0;
                            $totalTon += isset($pd['tonnage']) ? (float)$pd['tonnage'] : 0;

                            // Add product type name
                            $productDetails[$key]['product_type'] = ProductType::where('id', $pd['product_type_id'])
                                ->value('type_name') ?? null;
                        }
                    }

                    // Assign modified array back to model
                    $item->product_details = $productDetails;

                    $item->total_pieces = $totalPieces;
                    $item->total_ton = $totalTon;

                    // Add product name
                    $item->product_name = $item->product->product_name ?? null;
                    $item->product_code = $item->product->product_code ?? null;
                }
            }


            // ---------------- RESPONSE ----------------
            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,

                'dealer' => [
                    'id' => $order->dealers->id ?? null,
                    'name' => $order->dealers->dealer_name ?? null,
                    'code' => $order->dealers->dealer_code ?? null,
                ],

                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],

                'credit_days' => $order->credit_days,
                'billing_date' => $order->billing_date->format('d/M/Y'),
                'delivery_date' => $order->delivery_date->format('d/M/Y'),
                'total_amount' => round((float)$order->total_amount, 6),
                'additional_information' => $order->additional_information,
                'status' => $order->status,

                'created_by_dealer' => $order->created_by_dealer,
                'dealer_flag_order' => $order->dealer_flag_order,

                'vehicle' => [
                    'category_id' => $order->vehicle_category_id,
                    'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
                    'vehicle_number' => $order->vehicle_number,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                ],

                'track_order' => [
                    'accepted_time'   => $order->accepted_time,
                    'rejected_time'   => $order->rejected_time,
                    'dispatched_time' => $order->dispatched_time,
                    'intransit_time'  => $order->intransit_time,
                    'delivered_time'  => $order->delivered_time,
                ],

                'attachments' => $order->attachment ?? [],

                'order_items' => $order->orderItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'product_code' => $item->product_code,
                        'total_quantity' => $item->total_quantity,
                        'total_pieces' => $item->total_pieces,
                        'total_ton' => $item->total_ton,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => $item->product_details,
                    ];
                }),

                'created_at' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y') : null,
            ];

            // Notification for Employee Reading Dealer Order
            if ($order->dealer_flag_order != "1") {
                $authController = new AuthController();
                $authController->changeNotificationStatus('orders', $orderId,'opened');
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);

        } catch (Exception $e) {
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
            'order_id' => 'required|exists:orders,id', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'statusCode' => 422,
                'data' => [],
                'success' => false,
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $order = Order::where('id', $request->order_id)
                ->where('created_by_dealer', $user->id) 
                ->select('id', 'status', 'created_at', 'accepted_time', 'rejected_time', 'dispatched_time', 'intransit_time', 'delivered_time')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found',
                    'data' => [],
                ], 404);
            }

            // Format timestamps
            $formattedOrder = [
                'id' => $order->id,
                'status' => $order->status,
                'timestamps' => [
                    'pending_time' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y H:i:s') : null,
                    'accepted_time' => $order->accepted_time ? Carbon::parse($order->accepted_time)->format('d/m/Y H:i:s') : null,
                    'rejected_time' => $order->rejected_time ? Carbon::parse($order->rejected_time)->format('d/m/Y H:i:s') : null,
                    'dispatched_time' => $order->dispatched_time ? Carbon::parse($order->dispatched_time)->format('d/m/Y H:i:s') : null,
                    'intransit_time' => $order->intransit_time ? Carbon::parse($order->intransit_time)->format('d/m/Y H:i:s') : null,
                    'delivered_time' => $order->delivered_time ? Carbon::parse($order->delivered_time)->format('d/m/Y H:i:s') : null,
                ]
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order tracking details fetched successfully',
                'data' => $formattedOrder,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // public function monthlySalesTransaction(Request $request)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $month = $request->input('month', Carbon::now()->format('m'));
    //         $year = $request->input('year', Carbon::now()->format('Y'));

    //         $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
    //                 $query->select('id')
    //                     ->from('employees')
    //                     ->where('employee_type_id', 1); 
    //             })->pluck('id')->toArray();

    //         if (empty($assignedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No assigned routes found for Sales Executives.",
    //                 'data' => []
    //             ], 404);
    //         }
          
    //         if (!in_array($dealer->assigned_route_id, $assignedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "Dealer is not in an assigned route of an SE.",
    //                 'data' => []
    //             ], 403);
    //         }

    //         // $salesData = Order::where('created_by_dealer', $dealer->id)
    //         //     ->where('status', 'Delivered')
    //         //     ->whereMonth('created_at', $month)
    //         //     ->whereYear('created_at', $year)
    //         //     ->selectRaw('SUM(invoice_quantity) as total_quantity, SUM(invoice_total) as total_transaction')
    //         //     ->first();
    //         $salesData = Order::where(function ($query) use ($dealer) {
    //             $query->where('created_by_dealer', $dealer->id)
    //                 ->orWhere('dealer_id', $dealer->id);
    //         })
    //             ->where('status', 'Delivered')
    //             ->whereMonth('created_at', $month)
    //             ->whereYear('created_at', $year)
    //             ->selectRaw('SUM(invoice_quantity) as total_quantity, SUM(invoice_total) as total_transaction')
    //             ->first();

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Monthly Sales Transaction Data',
    //             'data' => [
    //                 'month' => $month,
    //                 'year' => $year,
    //                 'total_quantity' => round((float) ($salesData->total_quantity ?? 0), 2),
    //                 'total_transaction' => round((float) ($salesData->total_transaction ?? 0), 2),
    //             ],
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function monthlySalesTransaction(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $month = $request->input('month', Carbon::now()->format('m'));
            $year = $request->input('year', Carbon::now()->format('Y'));
            $productId = $request->input('product_id'); // ✅ NEW

            /** 🔹 Optional validation */
            if ($productId && !is_numeric($productId)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => 'Invalid product_id'
                ], 422);
            }

            $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
                    $query->select('id')
                        ->from('employees')
                        ->where('employee_type_id', 1);
                })->pluck('id')->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for Sales Executives.",
                    'data' => []
                ], 404);
            }

            $dealerRouteIds = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->pluck('assign_route_id')
                ->toArray();

            if (empty($dealerRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No route assignments found for this dealer.",
                    'data' => []
                ], 404);
            }

            $matchedRouteIds = array_intersect($dealerRouteIds, $assignedRouteIds);

            if (empty($matchedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Dealer is not in any assigned route of a Sales Executive.",
                    'data' => []
                ], 403);
            }

            $salesData = Order::where(function ($query) use ($dealer) {
                    $query->where('created_by_dealer', $dealer->id)
                        ->orWhere('dealer_id', $dealer->id);
                })
                ->where('status', 'Delivered')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->when($productId, function ($query) use ($productId) {
                    $query->where('product_id', $productId); // ✅ FILTER
                })
                ->selectRaw('
                    SUM(invoice_quantity) as total_quantity,
                    SUM(invoice_total) as total_transaction
                ')
                ->first();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Monthly Sales Transaction Data',
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'product_id' => $productId,
                    'total_quantity' => round((float) ($salesData->total_quantity ?? 0), 2),
                    'total_transaction' => round((float) ($salesData->total_transaction ?? 0), 2),
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
    public function monthlyTargetAchievement(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $month = $request->input('month', Carbon::now()->format('m'));
            $year = $request->input('year', Carbon::now()->format('Y'));

            // 1️⃣ Get target for the dealer for that month/year
            // $target = DB::table('dealer_targets')
            //     ->where('dealer_id', $dealer->id)
            //     ->where('month', $month)
            //     ->where('year', $year)
            //     ->select('target_quantity')
            //     ->first();

            // 2️⃣ Get achieved sales (same logic as in your existing API)
            $salesData = Order::where(function ($query) use ($dealer) {
                    $query->where('created_by_dealer', $dealer->id)
                        ->orWhere('dealer_id', $dealer->id);
                })
                ->where('status', 'Delivered')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->selectRaw('
                    SUM(invoice_quantity) as achieved_quantity
                ')
                ->first();

            // 3️⃣ Format response
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Monthly Target vs Achieved Data',
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    // 'target_quantity' => round((float) ($target->target_quantity ?? 0), 2),
                    'target_quantity' => '0',
                    'achieved_quantity' => round((float) ($salesData->achieved_quantity ?? 0), 2),
                    // 'achievement_percentage' => $target && $target->target_quantity > 0
                    //     ? round(($salesData->achieved_quantity / $target->target_quantity) * 100, 2)
                    //     : 0,
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

    public function outstandingPaymentsList(Request $request)
    {
        try {
            $dealer = Auth::user(); 
            
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
            if (!$dealer->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => "Dealer ID not found",
                    'data' => []
                ], 400);
            }
            $productId = $request->input('product_id');
            $query = OutstandingPayment::where('dealer_id', $dealer->id)
                ->where('status', 'open');

            if ($productId) {
                $query->whereHas('order', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });
            }

            $outstandingPayments = $query
                ->select('id', 'order_id', 'due_date', 'outstanding_amount')
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'due_date' => $payment->due_date
                            ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y')
                            : null,
                        'outstanding_amount' => (float) $payment->outstanding_amount,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding Payments List',
                'data' => $outstandingPayments,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
  
    public function opDetails($outstandingPaymentId)
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

            $outstandingPayment = OutstandingPayment::with('order')->findOrFail($outstandingPaymentId);

            if (!$outstandingPayment->order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found for the given Outstanding Payment ID.'
                ], 404);
            }
            if ($outstandingPayment->dealer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Unauthorized: You do not have permission to view this order.'
                ], 403);
            }

            $order = Order::with([
                'orderType:id,name',
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name,product_code',
                'orderItems',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($outstandingPayment->order_id);

            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
            $order->updated_at = Carbon::parse($order->updated_at)->format('d-m-Y');

            $outstandingPayments = OutstandingPayment::where('order_id', $order->id)
                ->where('status', 'open')
                ->where('dealer_id', $user->id)
                ->select(
                    'id',
                    'order_id',
                    'invoice_number',
                    'invoice_date',
                    'invoice_total',
                    'due_date',
                    'paid_amount',
                    'outstanding_amount',
                    'payment_doc_number',
                    'payment_date',
                    'payment_amount_applied',
                    'status'
                )
                ->orderBy('due_date', 'asc')
                ->get();

            $outstandingPayments->each(function ($payment) {
                $payment->commitments = OutstandingPaymentCommitment::where('outstanding_payment_id', $payment->id)
                    ->select('id', 'committed_date', 'committed_amount')
                    ->orderBy('committed_date', 'asc')
                    ->get();
            });
            $payments = Payment::where('order_id', $order->id)
                ->where('dealer_id', $user->id) 
                ->select('payment_date', 'payment_amount', 'payment_document_no')
                ->orderBy('payment_date', 'asc')
                ->get();

            $totalPaidAmount = $payments->sum('payment_amount');

            $totalOutstandingAmount = $order->invoice_total - $totalPaidAmount;

            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],
                'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                'total_amount' => round($order->total_amount, 2),
              
                'attachment' => $order->attachment ?? [],
                

                'order_items' => $order->orderItems->map(function ($item) {

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'product_code' => $item->product->product_code ?? 'N/A',
                        'total_quantity' => $item->total_quantity,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => collect($item->product_details)->map(function ($detail) {
                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'quantity' => $detail['quantity'],
                                'rate' => $detail['rate'],
                                'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
                            ];
                        }),
                    ];
                }),

                'outstanding_payments' => $outstandingPayments->isNotEmpty() ? [
                    'id' => $outstandingPayments->first()->id,
                    'invoice_number' => $outstandingPayments->first()->invoice_number,
                    'invoice_amount' => (float) $outstandingPayments->first()->invoice_total,
                    'due_date' => $outstandingPayments->first()->due_date ? Carbon::parse($outstandingPayments->first()->due_date)->format('d/m/Y') : null,
                    'commitments' => $outstandingPayments->first()->commitments->map(function ($commitment) {
                        return [
                            'id' => $commitment->id,
                            'committed_date' => $commitment->committed_date ? Carbon::parse($commitment->committed_date)->format('d/m/Y') : null,
                            'committed_amount' => $commitment->committed_amount,
                        ];
                    })->toArray(),
                ] : null,
                'payment_summary' => [
                    // 'total_paid_amount' => round($totalPaidAmount, 2),
                    'total_outstanding_amount' => round($totalOutstandingAmount, 2),
                    'payments' => $payments->map(function ($payment) {
                        return [
                            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d/m/Y') : null,
                            'payment_amount' => round($payment->payment_amount, 2),
                            'payment_document_no' => $payment->payment_document_no,
                        ];
                    }),
                ],

                'created_at' => $order->created_at? Carbon::parse($order->created_at)->format('d/m/Y') : null,
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function orderRequestList(Request $request)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
    //                 $query->select('id')
    //                     ->from('employees')
    //                     ->where('employee_type_id', 1); 
    //             })->pluck('id')->toArray();

    //         if (empty($assignedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No assigned routes found for Sales Executives.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         if (!in_array($dealer->assigned_route_id, $assignedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "Dealer is not in an assigned route of an SE.",
    //                 'data' => []
    //             ], 403);
    //         }

    //         $salesExecutives = AssignRoute::where('id', $dealer->assigned_route_id)
    //             ->pluck('employee_id');
    //         if ($salesExecutives->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No Sales Executives found for this dealer's assigned route.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         $orders = Order::whereIn('created_by', $salesExecutives)
    //             ->where('dealer_id',$dealer->id)
    //             ->select('id', 'total_amount', 'status', 'created_at')
    //             ->orderBy('id', 'desc')
    //             ->get();
    //         $formattedOrders = $orders->map(function ($order) {
    //             return [
    //                 'id' => $order->id,
    //                 'created_at' => $order->created_at->format('d/m/Y'),
    //                 'total_amount' => round($order->total_amount, 2),
    //                 'status' => $order->status === 'Pending' ? 'Order Received' :
    //                             ($order->status === 'Accepted' ? 'Order Accepted' :
    //                             ($order->status === 'Rejected' ? 'Order Rejected' : ucfirst($order->status))),
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order Request List fetched successfully',
    //             'data' => $formattedOrders,
    //         ], 200);
            
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function orderRequestList(Request $request)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
    //                 $query->select('id')
    //                     ->from('employees')
    //                     ->where('employee_type_id', 1); 
    //             })->pluck('id')->toArray();
    //         if (empty($assignedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No assigned routes found for Sales Executives.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         $dealerRouteIds = DB::table('dealer_route_assignments')
    //             ->where('dealer_id', $dealer->id)
    //             ->pluck('assign_route_id')
    //             ->toArray();
   
    //         if (empty($dealerRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No route assignments found for this dealer.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         $matchedRouteIds = array_intersect($dealerRouteIds, $assignedRouteIds);

    //         if (empty($matchedRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "Dealer is not assigned under any Sales Executive's route.",
    //                 'data' => []
    //             ], 403);
    //         }

    //         $salesExecutives = AssignRoute::whereIn('id', $matchedRouteIds)
    //             ->pluck('employee_id');

    //         if ($salesExecutives->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No Sales Executives found for this dealer's assigned routes.",
    //                 'data' => []
    //             ], 404);
    //         }
           
    //         $orders = Order::where('dealer_id', $dealer->id)
    //             ->whereIn('created_by', $salesExecutives)
    //             ->select('id', 'total_amount', 'status', 'created_at')
    //             ->orderBy('id', 'desc')
    //             ->get();

    //         $formattedOrders = $orders->map(function ($order) {
    //             return [
    //                 'id' => $order->id,
    //                 'created_at' => $order->created_at->format('d/m/Y'),
    //                 'total_amount' => round($order->total_amount, 2),
    //                 'status' => match ($order->status) {
    //                     'Pending' => 'Order Received',
    //                     'Accepted' => 'Order Accepted',
    //                     'Rejected' => 'Order Rejected',
    //                     default => ucfirst($order->status),
    //                 },
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order Request List fetched successfully',
    //             'data' => $formattedOrders,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function orderRequestList(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $productId = $request->input('product_id');

            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => 'product_id is required',
                ], 422);
            }

            // 🔹 Sales Executives
            $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
                $query->select('id')
                    ->from('employees')
                    ->where('employee_type_id', 1);
            })->pluck('id')->toArray();

            $dealerRouteIds = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->pluck('assign_route_id')
                ->toArray();

            $matchedRouteIds = array_intersect($dealerRouteIds, $assignedRouteIds);

            $salesExecutives = AssignRoute::whereIn('id', $matchedRouteIds)
                ->pluck('employee_id');

            // ✅ Base order filter
            $baseOrderQuery = Order::where('dealer_id', $dealer->id)
                ->whereHas('orderItems', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });

            // 🔹 Normal Orders
            $orders = (clone $baseOrderQuery)
                ->whereIn('created_by', $salesExecutives)
                ->select('id', 'total_amount', 'status', 'created_at')
                ->get();

            // 🔹 Influencer Orders
            $influencerOrders = (clone $baseOrderQuery)
                ->whereNotNull('influencer_visit_id')
                ->whereHas('influencerVisit', fn ($q) => $q->where('status', 'Won'))
                ->select('id', 'total_amount', 'status', 'created_at')
                ->get();

            // 🔹 Lead Orders
            $leadOrders = (clone $baseOrderQuery)
                ->whereNotNull('lead_id')
                ->whereHas('lead', fn ($q) => $q->where('status', 'Won'))
                ->select('id', 'total_amount', 'status', 'created_at')
                ->get();

            // 🔹 Merge all
            $allOrders = $orders
                ->merge($influencerOrders)
                ->merge($leadOrders)
                ->unique('id')
                ->sortByDesc('id')
                ->values();

            $formattedOrders = $allOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'total_amount' => round($order->total_amount, 2),
                    'status' => match ($order->status) {
                        'Pending' => 'Order Received',
                        'Accepted' => 'Order Accepted',
                        'Rejected' => 'Order Rejected',
                        'Won' => 'Order Won',
                        default => ucfirst($order->status),
                    },
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'data' => $formattedOrders,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderRequestDetails($orderId)
    {
        try {
            $dealer = Auth::user();
    
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated",
                ], 401);
            }
    
            // Load the order with all possible relationships
            $order = Order::with([
                'createdBy:id,name,employee_code',
                'orderType:id,name',
                'customerType:id,name',
                'lead.customerType:id,name',
                'lead:id,customer_name,phone,address,construction_type,stage_of_construction,status',
                'influencerVisit:id,influencer_name,phone,status',
                'paymentTerm:id,name',
                'orderItems.product.productTypes:id,product_id,type_name',
                'orderItems.product:id,product_name,product_code',
                'dealers:id,dealer_name,dealer_code',
                'vehicleCategory:id,vehicle_category_name',
            ])
            ->where('dealer_id', $dealer->id)
            ->find($orderId);
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found or unauthorized access',
                ], 404);
            }
    
            // Determine which type of customer data to show
            $customerTypeName = ' ';
            $customerName = ' ';
            $phone = ' ';
            $address = ' ';
            $constructionType = ' ';
            $stageOfConstruction = ' ';
    
            if ($order->lead_id && optional($order->lead)->status === 'Won') {
                // Lead-based order
                $customerTypeName = $order->lead->customerType->name ?? ' ';
                $customerName = $order->lead->customer_name ?? ' ';
                $phone = $order->lead->phone ?? ' ';
                $address = $order->lead->address ?? ' ';
                $constructionType = $order->lead->construction_type ?? ' ';
                $stageOfConstruction = $order->lead->stage_of_construction ?? ' ';
            } elseif ($order->influencer_visit_id && optional($order->influencerVisit)->status === 'Won') {
                // Influencer-based order
                $customerTypeName = 'Influencer';
                $customerName = $order->influencerVisit->influencer_name ?? ' ';
                $phone = $order->influencerVisit->phone ?? ' ';
                $address = $order->influencerVisit->address ?? ' ';
                
            } else {
                // Dealer-based order
                $customerTypeName = $order->customerType->name ?? ' ';
                $customerName = $order->dealers->dealer_name ?? ' ';
                $address = $order->dealers->dealer_code ?? ' ';
            }
    
            // Format response
            $orderDetails = [
                'id' => $order->id,
                'order_placed_by' => [
                    'name' => $order->createdBy->name ?? ' ',
                    'employee_code' => $order->createdBy->employee_code ?? ' ',
                    'designation' => 'Sales Executive',
                ],
                'order_date' => $order->created_at->format('d/m/Y'),
                'order_type' => $order->orderType->name ?? ' ',
                'total_amount' => $order->total_amount,
                'customer_details' => [
                    'customer_type' => $customerTypeName,
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'address' => $address,
                    'construction_type' => $constructionType,
                    'stage_of_construction' => $stageOfConstruction,
                ],
                'billing_date' => $order->billing_date,
                'delivery_date' => $order->delivery_date,
                'payment_terms' => $order->paymentTerm->name ?? ' ',
                'additional_information' => $order->additional_information ?? ' ',
                'status' => $order->status,
                'attachments' => $order->attachment ?? [],
                'order_items' => $order->orderItems->map(function ($item) {

                    $totalPieces = 0;
                    $totalTon = 0;

                    $productDetails = [];

                    if (isset($item->product_details) && is_array($item->product_details)) {
                        foreach ($item->product_details as $pd) {

                            $pieces  = $pd['pieces'] ?? 0;
                            $tonnage = $pd['tonnage'] ?? 0;

                            $totalPieces += $pieces;
                            $totalTon += ($tonnage * $pieces);

                            $productDetails[] = [
                                'product_type_id' => $pd['product_type_id'] ?? null,
                                'pieces' => (float) $pieces,
                                'tonnage' => (float) $tonnage,
                                'rate' => $pd['rate'] ?? null,
                                'product_type' => ProductType::where('id', $pd['product_type_id'] ?? null)
                                    ->value('type_name') ?? 'N/A',
                            ];
                        }
                    }

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? null,
                        'product_code' => $item->product->product_code ?? null,
                        'total_quantity' => (float) $item->total_quantity,
                        'balance_quantity' => (float) $item->balance_quantity,
                        'total_pieces' => $totalPieces,
                        'total_ton' => round($totalTon, 3),
                        'product_details' => $productDetails,
                    ];
                }),

                'created_at' => $order->created_at->format('d/m/Y'),
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details retrieved successfully',
                'data' => $orderDetails,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // public function orderRequestDetails($orderId)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated",
    //             ], 401);
    //         }

    //         $order = Order::with([
    //             'createdBy:id,name,employee_code',
    //             'orderType:id,name',
    //             'customerType:id,name',
    //             'lead.customerType:id,name',
    //             'lead:id,customer_name,phone,address,construction_type,stage_of_construction',
    //             'paymentTerm:id,name',
    //             'orderItems.product.productTypes:id,product_id,type_name',
    //             'orderItems.product:id,product_name',
    //             'dealers:id,dealer_name,dealer_code',
    //             'vehicleCategory:id,vehicle_category_name',
    //         ])
    //         ->where('dealer_id', $dealer->id) 
    //         ->find($orderId);

    //         if (!$order) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Order not found or unauthorized access',
    //             ], 404);
    //         }

    //         // Format response data
    //         $orderDetails = [
    //             'id' => $order->id,
    //             'order_placed_by' => [
    //                 'name' => $order->createdBy->name ?? ' ',
    //                 'employee_code' => $order->createdBy->employee_code ?? ' ',
    //                 'designation' => 'Sales Executive', // Fixed designation
    //             ],
    //             'order_date' => $order->created_at->format('d/m/Y'),
    //             'order_type' => $order->orderType->name ?? ' ',
    //             'total_amount' => $order->total_amount,
    //             'customer_details' => [
    //                 'customer_type' => $order->customerType->name ?? ($order->lead->customerType->name ?? ' '),
    //                 'customer_name' => $order->lead->customer_name ?? ' ',
    //                 'phone' => $order->lead->phone ?? ' ',
    //                 'address' => $order->lead->address ?? ' ',
    //                 'construction_type' => $order->lead->construction_type ?? ' ',
    //                 'stage_of_construction' => $order->lead->stage_of_construction ?? ' ',
    //             ],

    //             'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : ' ',
    //             'payment_terms' => $order->paymentTerm->name ?? ' ',
    //             'additional_information' => $order->additional_information ?? ' ',
    //             'status' => $order->status,

                

    //             'attachments' => $order->attachment ?? [],

    //             // Order Items
    //             'order_items' => $order->orderItems->map(function ($item) {

    //                 return [
    //                     'product_id' => $item->product_id,
    //                     'product_name' => $item->product->product_name ?? 'N/A',
    //                     'total_quantity' => $item->total_quantity,
    //                     'balance_quantity' => (float) $item->balance_quantity,
    //                     'product_details' => collect($item->product_details)->map(function ($detail) {
    //                         return [
    //                             'product_type_id' => $detail['product_type_id'],
    //                             'quantity' => $detail['quantity'],
    //                             'rate' => $detail['rate'],
    //                             'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
    //                         ];
    //                     }),
    //                 ];
    //             }),

    //             'created_at' => $order->created_at->format('d/m/Y'),
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order details retrieved successfully',
    //             'data' => $orderDetails,
    //         ], 200);
            
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function orderRequestStatusUpdate(Request $request, $orderId)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $validatedData = $request->validate([
    //             'status' => 'required|in:Accepted,Rejected',
    //             'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
    //         ]);

    //         $order = Order::find($orderId);

    //         if (!$order) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Order not found",
    //             ], 404);
    //         }

    //         $salesExecutives = AssignRoute::where('id', $dealer->assigned_route_id)->pluck('employee_id');

    //         if (!$salesExecutives->contains($order->created_by)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "You do not have permission to update this order's status.",
    //             ], 403);
    //         }

    //         // Update order status
    //         $order->status = $validatedData['status'];
    //         if ($validatedData['status'] === 'Rejected') {
    //             $order->reason_for_rejection = $validatedData['reason_for_rejection'];
    //         } else {
    //             $order->reason_for_rejection = null;
    //         }..
    //         $order->save();
	// 	    $value="pending";
    //         if($validatedData['status']=="Rejected"){
    //             $value="rejected";
    //         }else{
    //            $value ="approved";
    //         }
    //         //.............$value.........notification..............
    //         $authController = new AuthController();
    //         $authController->changeNotificationStatus('orders', $orderId,$value);
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Order status updated successfully",
    //             'data' => [
    //                 'id' => $order->id,
    //                 'status' => $order->status,
    //                 'reason_for_rejection' => $order->reason_for_rejection,
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function orderRequestStatusUpdate(Request $request, $orderId)
    // {
    //     try {
    //         $dealer = Auth::user();

    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $validatedData = $request->validate([
    //             'status' => 'required|in:Accepted,Rejected',
    //             'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
    //         ]);

    //         $order = Order::find($orderId);

    //         if (!$order) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Order not found",
    //             ], 404);
    //         }

    //         $dealerRouteIds = DB::table('dealer_route_assignments')
    //             ->where('dealer_id', $dealer->id)
    //             ->pluck('assign_route_id')
    //             ->toArray();

    //         if (empty($dealerRouteIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No route assignments found for this dealer.",
    //             ], 404);
    //         }

    //         $salesExecutives = AssignRoute::whereIn('id', $dealerRouteIds)
    //             ->whereIn('employee_id', function ($query) {
    //                 $query->select('id')
    //                     ->from('employees')
    //                     ->where('employee_type_id', 1);
    //             })
    //             ->pluck('employee_id');

    //         if (!$salesExecutives->contains($order->created_by)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "You do not have permission to update this order's status.",
    //             ], 403);
    //         }

    //         $order->status = $validatedData['status'];
    //         $order->reason_for_rejection = $validatedData['status'] === 'Rejected'
    //             ? $validatedData['reason_for_rejection']
    //             : null;
    //         $order->save();

    //         $value = match ($validatedData['status']) {
    //             'Rejected' => 'rejected',
    //             'Accepted' => 'approved',
    //             default => 'pending',
    //         };

    //         $authController = new AuthController();
    //         $authController->changeNotificationStatus('orders', $orderId, $value);

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Order status updated successfully",
    //             'data' => [
    //                 'id' => $order->id,
    //                 'status' => $order->status,
    //                 'reason_for_rejection' => $order->reason_for_rejection,
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function orderRequestStatusUpdate(Request $request, $orderId)
    {
        try {
            $dealer = Auth::user();
    
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
    
            // Validate request
            $validatedData = $request->validate([
                'status' => 'required|in:Accepted,Rejected',
                'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
            ]);
    
            // Find order
            $order = Order::with(['lead', 'influencerVisit'])->find($orderId);
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Order not found",
                ], 404);
            }
    
            // Ensure this order belongs to the dealer
            if ($order->dealer_id !== $dealer->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You are not authorized to update this order.",
                ], 403);
            }
    
            // Check if dealer is assigned under a valid Sales Executive route
            $dealerRouteIds = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->pluck('assign_route_id')
                ->toArray();
    
            if (empty($dealerRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No route assignments found for this dealer.",
                ], 404);
            }
    
            // Get all Sales Executives assigned to dealer routes
            $salesExecutives = AssignRoute::whereIn('id', $dealerRouteIds)
                ->whereIn('employee_id', function ($query) {
                    $query->select('id')
                        ->from('employees')
                        ->where('employee_type_id', 1);
                })
                ->pluck('employee_id');
    
            // Verify the order was created by one of those Sales Executives
            // if (!$salesExecutives->contains($order->created_by)) {
            //     return response()->json([
            //         'success' => false,
            //         'statusCode' => 403,
            //         'message' => "You do not have permission to update this order's status.",
            //     ], 403);
            // }
    
            // --- Update order status ---
            $order->status = $validatedData['status'];
            $order->reason_for_rejection = $validatedData['status'] === 'Rejected'
                ? $validatedData['reason_for_rejection']
                : null;
    
            // Optionally update status timestamps
            if ($validatedData['status'] === 'Accepted') {
                $order->accepted_time = now();
            } elseif ($validatedData['status'] === 'Rejected') {
                $order->rejected_time = now();
            }
    
            $order->save();
    
            // Update notification status
            $notificationValue = match ($validatedData['status']) {
                'Rejected' => 'rejected',
                'Accepted' => 'approved',
                default => 'pending',
            };
    
            $authController = new AuthController();
            $authController->changeNotificationStatus('orders', $orderId, $notificationValue);
    
            // --- Unified Response ---
            $response = [
                'id' => $order->id,
                'status' => $order->status,
                'reason_for_rejection' => $order->reason_for_rejection,
                'updated_at' => $order->updated_at->format('d/m/Y H:i'),
                'order_type' => $order->orderType->name ?? '',
                'total_amount' => $order->total_amount,
                'customer_name' => $order->lead->customer_name 
                    ?? $order->influencerVisit->influencer_name 
                    ?? ($order->dealers->dealer_name ?? ' '),
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order status updated successfully",
                'data' => $response,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // public function getSupport(Request $request)
    // {
    //     try {
    //         $dealer = Auth::user();
            
    //         if (!$dealer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $seAssignedRoute = AssignRoute::where('id', $dealer->assigned_route_id)->first();

    //         if (!$seAssignedRoute) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Assigned route not found for this dealer.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         if (!$seAssignedRoute->employee_id) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No ASO assigned for this dealer's route.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         $aso = Employee::where('id', $seAssignedRoute->employee_id)
    //             ->where('employee_type_id', 2) 
    //             ->select('id', 'name', 'phone')
    //             ->first();

    //         if (!$aso) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No ASO found for this dealer's assigned route.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Support ASO fetched successfully",
    //             'data' => [
    //                 'aso_id' => $aso->id,
    //                 'name' => $aso->name,
    //                 'phone' => $aso->phone,
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function getSupport(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $dealerRouteIds = DB::table('dealer_route_assignments')
                ->where('dealer_id', $dealer->id)
                ->pluck('assign_route_id')
                ->toArray();

            if (empty($dealerRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No route assignments found for this dealer.",
                    'data' => []
                ], 404);
            }

            $aso = AssignRoute::whereIn('assigned_routes.id', $dealerRouteIds)
                ->join('employees', 'assigned_routes.employee_id', '=', 'employees.id')
                ->where('employees.employee_type_id', 2)
                ->select('employees.id as aso_id', 'employees.name', 'employees.phone')
                ->first();

            if (!$aso) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No ASO found for this dealer’s assigned routes.",
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Support ASO fetched successfully",
                'data' => [
                    'aso_id' => $aso->aso_id,
                    'name' => $aso->name,
                    'phone' => $aso->phone,
                    'address' => '953, Temple Road, opposite Thrikkkakara, Thrikkakara, Edappally, Kochi, Kerala 682021',

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


    // public function paymentHistoryList(Request $request)
    // {
    //     $dealer = Auth::user();

    //     if (!$dealer) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 401,
    //             'message' => "User not Authenticated",
    //         ], 401);
    //     }
    //     $dealerId = $dealer->id;
    //     $payments = Payment::where('dealer_id', $dealerId)
    //         ->whereHas('order', function ($query) {
    //             $query->where(function ($q) {
    //                 $q->whereIn('payment_terms_id', [1, 2])
    //                   ->orWhereNull('payment_terms_id');
    //             });
    //         })
    //         ->with(['order.paymentTerm'])
	//         ->orderBy('payment_date', 'desc')
    //         ->get()
    //         ->map(function ($payment) {
    //             return [
    //                 'order_id'       => $payment->order->id ?? null, 
    //                 'payment_date'   => $payment->payment_date->format('d/m/y'),
    //                 'payment_total'  => $payment->payment_amount,
    //                 'payment_terms'  => $payment->order->paymentTerm->name ?? 'N/A',
    //             ];
    //         });

    //     return response()->json([
    //         'success'  => true,
    //         'statusCode' => 200,
    //         'message' => 'Payment history retrieved successfully',
    //         'data'    => $payments,
    //     ], 200);
    // }

    // public function paymentHistoryOrderDetails($orderId)
    // {
    //     try {
    //         $user = Auth::user();
    
    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'You must be logged in to view this order.'
    //             ], 400);
    //         }
    
    //         $order = Order::with([
    //             'orderType:id,name',
    //             'dealers:id,dealer_name,dealer_code',
    //             'orderItems.product:id,product_name,product_code',
    //             'orderItems',
    //             'paymentTerm:id,name',
    //             'vehicleCategory:id,vehicle_category_name'
    //         ])->findOrFail($orderId);

    //         $invoiceNumber = $order->invoice_number;
    //         $invoiceTotal = round($order->invoice_total, 2);
    
    //         $totalQuantity = $order->orderItems->sum('total_quantity');
    
    //         $paidAmount = Payment::where('order_id', $orderId)->sum('payment_amount');
    
    //         $outstandingPayment = $invoiceTotal - $paidAmount;
    //         // $paidAmount = Payment::where('order_id', $orderId)->sum('payment_amount');
    
    //         // $outstandingPayment = OutstandingPayment::where('order_id', $orderId)
    //         //     ->select('invoice_number', 'invoice_total', 'due_date', 'paid_amount', 'outstanding_amount')
    //         //     ->first();
    
    //         $trackingStatus = [
    //             'pending_time' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y H:i:s') : null,
    //             'accepted_time' => $order->accepted_time ? Carbon::parse($order->accepted_time)->format('d/m/Y H:i:s') : null,
    //             'rejected_time' => $order->rejected_time ? Carbon::parse($order->rejected_time)->format('d/m/Y H:i:s') : null,
    //             'dispatched_time' => $order->dispatched_time ? Carbon::parse($order->dispatched_time)->format('d/m/Y H:i:s') : null,
    //             'intransit_time' => $order->intransit_time ? Carbon::parse($order->intransit_time)->format('d/m/Y H:i:s') : null,
    //             'delivered_time' => $order->delivered_time ? Carbon::parse($order->delivered_time)->format('d/m/Y H:i:s') : null,
    //         ];
    
    //         $responseData = [
    //             'id' => $order->id,
    //             'order_type' => $order->orderType->name ?? null,
    //             'additional_information' => $order->additional_information,
    //             'invoice_number' => $invoiceNumber,
    //             'invoice_total' => $invoiceTotal,
    //             'total_quantity' => $totalQuantity,
    //             'paid_amount' => round($paidAmount, 2),
    //             'outstanding_payment' => round($outstandingPayment, 2),
    //             'payment_terms' => [
    //                 'id' => $order->paymentTerm->id ?? null,
    //                 'name' => $order->paymentTerm->name ?? null,
    //             ],
    //             'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                
    //             'vehicle' => [
    //                 'category_id' => $order->vehicle_category_id,
    //                 'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
    //                 'vehicle_number' => $order->vehicle_number,
    //                 'driver_name' => $order->driver_name,
    //                 'driver_phone' => $order->driver_phone,
    //             ],
    //             'attachments' => $order->attachment ?? [],
    //             'tracking_status' => $trackingStatus,
    //             'order_items' => $order->orderItems->map(function ($item) {

    //                 return [
    //                     'product_id' => $item->product_id,
    //                     'product_name' => $item->product->product_name ?? 'N/A',
    //                     'product_code' => $item->product->product_code ?? 'N/A',
    //                     'total_quantity' => $item->total_quantity,
    //                     'balance_quantity' => (float) $item->balance_quantity,
    //                     'product_details' => collect($item->product_details)->map(function ($detail) {
    //                         return [
    //                             'product_type_id' => $detail['product_type_id'],
    //                             'quantity' => $detail['quantity'],
    //                             'rate' => $detail['rate'],
    //                             'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
    //                         ];
    //                     }),
    //                 ];
    //             }),
    //             'created_at' => Carbon::parse($order->created_at)->format('d/m/Y'),
    //         ];
    
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order details fetched successfully',
    //             'data' => $responseData,
    //         ], 200);
    
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function paymentHistoryList(Request $request)
    {
        $dealer = Auth::user();

        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not Authenticated",
            ], 401);
        }

        $productId = $request->product_id;

        $payments = Payment::where('dealer_id', $dealer->id)
            ->whereHas('order', function ($query) use ($productId) {

                $query->where(function ($q) {
                    $q->whereIn('payment_terms_id', [1, 2])
                    ->orWhereNull('payment_terms_id');
                });

                // ✅ FILTER BY PRODUCT
                if ($productId) {
                    $query->whereHas('orderItems', function ($q) use ($productId) {
                        $q->where('product_id', $productId);
                    });
                }
            })
            ->with(['order.paymentTerm'])
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'order_id'      => $payment->order->id ?? null,
                    'payment_date'  => $payment->payment_date?->format('d/m/Y'),
                    'payment_total' => (float) $payment->payment_amount,
                    'payment_terms' => $payment->order->paymentTerm->name ?? 'N/A',
                ];
            });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Payment history retrieved successfully',
            'data' => $payments,
        ]);
    }
    public function paymentHistoryOrderDetails(Request $request, $orderId)
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

            $productId = $request->query('product_id');

            $order = Order::with([
                'orderType:id,name',
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name,product_code',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($orderId);

            /* ========================
            CALCULATIONS
            ======================== */

            $invoiceNumber = $order->invoice_number;
            $invoiceDate = $order->Invoice_date;
            $invoiceTotal  = round($order->invoice_total, 2);

            $paidAmount = Payment::where('order_id', $orderId)->sum('payment_amount');
            $outstandingPayment = $invoiceTotal - $paidAmount;

            $totalQuantity = $order->orderItems
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->sum('total_quantity');

            /* ========================
            TRACKING STATUS
            ======================== */

            $trackingStatus = [
                'pending_time'    => optional($order->created_at)->format('d/m/Y H:i:s'),
                'accepted_time'   => optional($order->accepted_time)->format('d/m/Y H:i:s'),
                'rejected_time'   => optional($order->rejected_time)->format('d/m/Y H:i:s'),
                'dispatched_time' => optional($order->dispatched_time)->format('d/m/Y H:i:s'),
                'intransit_time'  => optional($order->intransit_time)->format('d/m/Y H:i:s'),
                'delivered_time'  => optional($order->delivered_time)->format('d/m/Y H:i:s'),
            ];

            /* ========================
            RESPONSE (UNCHANGED)
            ======================== */

            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'additional_information' => $order->additional_information,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'invoice_total' => $invoiceTotal,
                'total_quantity' => (float) $totalQuantity,
                'paid_amount' => round($paidAmount, 2),
                'outstanding_payment' => round($outstandingPayment, 2),
                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],
                'billing_date' => optional($order->billing_date)->format('d/m/Y'),

                'vehicle' => [
                    'category_id' => $order->vehicle_category_id,
                    'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
                    'vehicle_number' => $order->vehicle_number,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                ],

                'attachments' => $order->attachment ?? [],
                'tracking_status' => $trackingStatus,

                /* ========================
                ORDER ITEMS (UPDATED)
                ======================== */

                'order_items' => $order->orderItems
                    ->when($productId, fn ($q) => $q->where('product_id', $productId))
                    ->map(function ($item) {

                        $productDetails = collect($item->product_details)->map(function ($detail) {

                            $productType = ProductType::find($detail['product_type_id']);

                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'type_name'       => $productType->type_name ?? null,
                                'quantity'        => isset($detail['quantity']) ? (float) $detail['quantity'] : 0,
                                'pieces'          => isset($detail['pieces']) ? (float) $detail['pieces'] : 0,
                                'tonnage'         => isset($detail['tonnage']) ? (float) $detail['tonnage'] : 0,
                                'rate'            => $detail['rate'] ?? null,
                                'quantity_type'   => $detail['quantity_type'] ?? null,
                            ];
                        });

                        $totalQuantity = $productDetails->sum('quantity');
                        $totalPieces   = $productDetails->sum('pieces');

                        $totalTonnage = $productDetails->sum(function ($detail) {
                            return ($detail['pieces'] ?? 0) * ($detail['tonnage'] ?? 0);
                        });

                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->product_name ?? 'N/A',
                            'product_code' => $item->product->product_code ?? 'N/A',
                            'total_quantity' => (float) $totalQuantity,
                            'balance_quantity' => (float) $item->balance_quantity,
                            'total_pieces' => (float) $totalPieces,
                            'total_ton' => $totalTonnage > 0 ? (float) $totalTonnage : null,
                            'product_details' => $productDetails,
                        ];
                    }),

                'created_at' => $order->created_at->format('d/m/Y'),
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function creditNoteList(Request $request)
    {
        $dealer = Auth::user();
    
        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'You must be logged in to view this order.'
            ], 400);
        }
        $productId = $request->query('product_id'); // GET param

        if ($productId && !is_numeric($productId)) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Invalid product_id'
            ], 422);
        }

        $creditNotes = CreditNote::where('dealer_id', $dealer->id)
            ->when($productId, function ($query) use ($productId) {
                $query->where('product_id', $productId); // ✅ FILTER
            })
            ->select('order_id', 'credit_note_number', 'invoice_number', 'date', 'total_row_amount')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($creditNote) {
                return [
                    'order_id' => $creditNote->order_id,
                    'credit_note_number' => $creditNote->credit_note_number,
                    'invoice_number' => $creditNote->invoice_number,
                    'date' => $creditNote->date->format('d/m/Y'),
                    'total_row_amount' => $creditNote->total_row_amount,
                ];
            });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Credit Notes retrieved successfully',
            'data' => $creditNotes
        ], 200);
    }
    public function creditNoteDetails($order_id)
    {
        $dealer = Auth::user();

        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'You must be logged in to view this credit note.',
            ], 401);
        }

        $creditNote = CreditNote::where('order_id', $order_id)
            ->where('dealer_id', $dealer->id)
	    ->first();

        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Credit Note not found for this order or unauthorized access.',
                'data' => null,
            ], 404);
        }

            //  $order = Order::where('id', $creditNote->order_id)
            //    ->where('dealer_id', $dealer->id) 
            //  ->select('order_type', 'payment_terms_id', 'billing_date', 'invoice_number')
            // ->with('orderType:id,name', 'paymentTerm:id,name')
	        //  ->first();
        $order = Order::where('id', $creditNote->order_id)
            ->where(function ($query) use ($dealer) {
            $query->where('dealer_id', $dealer->id)
                  ->orWhere('created_by_dealer', $dealer->id);
        })
            ->with('orderType:id,name', 'paymentTerm:id,name')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Order details not found or unauthorized access.',
            ], 404);
        }

        $response = [
            'order_id' => $creditNote->order_id,
            'order_type' => $order->orderType->name ?? 'N/A',
            'payment_type' => $order->paymentTerm->name ?? 'N/A',
            'credit_note_date' => $creditNote->date->format('d/m/Y'), 
            'credit_note_number' => $creditNote->credit_note_number ?? 'N/A',
            'billing_date' => $order->billing_date ? $order->billing_date->format('d/m/Y') : 'N/A',
            'invoice_number' => $order->invoice_number,
            'return_products' => collect($creditNote->returned_items)->map(function ($item) {
                return [
                    'rate' => $item['rate'],
                    'quantity' => $item['quantity'],
                    'product_type_id' => $item['product_type_id'],
                    'product_type' => ProductType::where('id', $item['product_type_id'])->value('type_name') ?? 'N/A',
                ];
            }),
            'total_return_quantity' => $creditNote->total_return_quantity,
            'total_amount' => $creditNote->total_row_amount,
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Credit Note details retrieved successfully',
            'data' => $response,
        ], 200);
    }
    public function getCreditDays(Request $request)
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
}
