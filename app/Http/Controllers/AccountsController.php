<?php

namespace App\Http\Controllers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Employee;
use App\Models\AssignRoute;
use App\Models\Price;
use App\Models\OutstandingPayment;
use App\Models\OutstandingNew;
use App\Models\DealerRouteAssignment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Collection;

class AccountsController extends Controller
{
    public function index()
    {
        return view('accounts.order-request.index');
    }
    
    // public function orderList(Request $request)
    // {
	//     $statusFilter = $request->get('status');       
    //     $orders = Order::with(['dealer', 'dealers', 'createdBy.employeeType', 'sendForApprovalBy'])
    //         ->where(function ($query) {
                
    //             $query->where(function ($subQuery) {
    //                 $subQuery->where('dealer_flag_order', '1')
    //                     ->whereNotIn('status', ['Pending', 'Rejected'])
    //                     ->where(function ($approvalQuery) {
    //                         $approvalQuery->where('send_for_approval', '0') 
    //                                       ->orWhere('send_for_approval', '1');
    //                     });
    //             })
                
    //             ->orWhere(function ($subQuery) {
    //                 $subQuery->whereHas('createdBy', function ($employeeQuery) {
    //                     $employeeQuery->whereIn('employee_type_id', [2, 3, 4, 5]);
    //                 })->where('dealer_flag_order', '!=', '1') 
    //                   ->where(function ($sourceQuery) {
    //                     $sourceQuery->whereNull('source')
    //                         ->orWhereNotIn('source', ['lead_won', 'influencer_won']); 
    //                 });
    //             });
    //         })->orderByRaw("
    //             CASE
    //                 WHEN order_approved = '1' THEN accepted_time
    //                 WHEN order_approved = '2' THEN rejected_time
    //                 ELSE created_at
    //             END DESC
    //         ");
    //         // ->latest();
    
    //     	if ($statusFilter === 'Approved') {
    //             $orders->where('order_approved', '1');
    //         } elseif ($statusFilter === 'Rejected') {
    //             $orders->where('order_approved', '2');
    //         } elseif ($statusFilter === 'Pending') {
    //             $orders->where(function ($query) {
    //                 $query->whereNull('order_approved')->orWhereNotIn('order_approved', ['1', '2']);
    //             });
    //         }         
   
    //         return DataTables::of($orders)
    //             ->addIndexColumn()
    //             ->addColumn('date', function ($order) {
    //                 if ($order->order_approved == 1 && $order->accepted_time) {
    //                     return $order->accepted_time->format('d/m/Y h:i A');
    //                 } elseif ($order->order_approved == 2 && $order->rejected_time) {
    //                     return $order->rejected_time->format('d/m/Y h:i A');
    //                 } else {
    //                     return $order->created_at->format('d/m/Y h:i A');
    //                 }
    //             })
    //             ->addColumn('order_id', function ($order) {
    //                 return 'OD00' . $order->id;
    //             })
    //             ->addColumn('dealer_name', function ($order) {
    //                 if ($order->created_by_dealer) {
    //                     return $order->dealers?->dealer_name ?? 'N/A';
    //                 }
    //                 return $order->dealer?->dealer_name ?? 'N/A';
    //             })
    //             ->addColumn('dealer_code', function ($order) {
    //                 if ($order->created_by_dealer) {
    //                     return $order->dealers?->dealer_code ?? 'N/A';
    //                 }
    //                 return $order->dealer?->dealer_code ?? 'N/A';
    //             })
    //             ->addColumn('employee_type', function ($order) {
    //                 if ($order->dealer_flag_order == "1") {

    //                     if ($order->send_for_approval == "0") {
    //                         return 'Area Sales Officer';
    //                     }
                
    //                     if ($order->send_for_approval == "1" && !empty($order->send_for_approval_by)) {
    //                         return 'Sales Manager';
    //                     }
                
    //                     return 'N/A';
    //                 }
        
    //                 elseif ($order->createdBy?->employeeType) {
    //                     return $order->createdBy->employeeType->type_name;
    //                 }
                
    //                 return 'N/A';
    //             })
          
    //             ->addColumn('employee_name_code', function ($order) {
    //                 if ($order->dealer_flag_order == '1') {
    //                     if ($order->send_for_approval == 1 && $order->sendForApprovalBy) {
                            
    //                         return 'Dhanesh Kamath - PS007';
    //                     }
    //                     if ($order->send_for_approval == 0 && $order->dealers) {
    //                         $assignedRouteId = $order->dealers->assigned_route_id;
                
    //                         if ($assignedRouteId) {
    //                             $assignedRoute = \App\Models\AssignRoute::with('employee')->find($assignedRouteId);
                
    //                             if ($assignedRoute && $assignedRoute->employee) {
    //                                 return $assignedRoute->employee->name . ' - ' . $assignedRoute->employee->employee_code;
    //                             }
    //                         }
    //                     }
    //                     return $order->dealer?->dealer_name ?? '-';
    //                 } elseif ($order->createdBy) {
                       
    //                     return $order->createdBy->name . ' - ' . $order->createdBy->employee_code;
    //                 }
    //                 return 'N/A';
    //             })
    //             ->addColumn('amount', function ($order) {
    //                 return (float) ($order->total_amount);
    //             })
    //             ->addColumn('status', function ($order) {
    //                 if ($order->order_approved == 1) {
    //                     return '<span class="badge bg-success">Approved</span>';
    //                 } elseif ($order->order_approved == 2) {
    //                     return '<span class="badge bg-danger">Rejected</span>';
    //                 }
    //                 return '<span class="badge bg-warning">Pending</span>';
    //             })
    //             ->addColumn('action', function ($order) {
                   
    //                 return '<button class="btn btn-info btn-sm view-order" data-id="' . $order->id . '" title="View">
    //                             <i class="fa fa-eye"></i>
    //                         </button>';
    //             })
    //             ->rawColumns(['status','action'])
    //             ->make(true);
    // }
    public function orderList(Request $request)
    {
        // dd(1);
        $selectedProductCode = session('selected_product_code');$products=[];
        if ($selectedProductCode) {
            $products = Product::where('product_code', $selectedProductCode)->first();
        }else{
            $user = auth()->user();
            $productIds = $user->product_ids ?? [];

            $products = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'product_code')
                ->first();       
        }
        $productID=$products->id;
        $statusFilter = $request->get('status');       

        $orders = Order::with(['orderItems','dealer', 'dealers', 'createdBy.employeeType', 'sendForApprovalBy'])
            ->whereHas('orderItems', function ($q) use ($productID) {
                $q->where('product_id', $productID);
            })
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('dealer_flag_order', '1')
                        ->whereNotIn('status', ['Pending', 'Rejected'])
                        ->where(function ($approvalQuery) {
                            $approvalQuery->where('send_for_approval', '0')
                                        ->orWhere('send_for_approval', '1');
                        });
                })
                ->orWhere(function ($subQuery) {
                    $subQuery->whereHas('createdBy', function ($employeeQuery) {
                        $employeeQuery->whereIn('employee_type_id', [2, 3, 4, 5]);
                    })
                    ->where('dealer_flag_order', '!=', '1')
                    ->where(function ($sourceQuery) {
                        $sourceQuery->whereNull('source')
                                    ->orWhereNotIn('source', ['lead_won', 'influencer_won']);
                    });
                });
            })
            ->orderByRaw("
                CASE
                    WHEN order_approved = '1' THEN accepted_time
                    WHEN order_approved = '2' THEN rejected_time
                    ELSE created_at
                END DESC
            ");

        if ($statusFilter === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($statusFilter === 'Rejected') {
            $orders->where('order_approved', '2');
        } elseif ($statusFilter === 'Pending') {
            $orders->where(function ($query) {
                $query->whereNull('order_approved')->orWhereNotIn('order_approved', ['1', '2']);
            });
        }         

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('date', function ($order) {
                if ($order->order_approved == 1 && $order->accepted_time) {
                    return $order->accepted_time->format('d/m/Y h:i A');
                } elseif ($order->order_approved == 2 && $order->rejected_time) {
                    return $order->rejected_time->format('d/m/Y h:i A');
                } else {
                    return $order->created_at->format('d/m/Y h:i A');
                }
            })
            ->addColumn('order_id', fn($order) => 'OD00' . $order->id)
            ->addColumn('dealer_name', fn($order) =>
                $order->created_by_dealer ? ($order->dealers?->dealer_name ?? 'N/A') : ($order->dealer?->dealer_name ?? 'N/A')
            )
            ->addColumn('dealer_code', fn($order) =>
                $order->created_by_dealer ? ($order->dealers?->dealer_code ?? 'N/A') : ($order->dealer?->dealer_code ?? 'N/A')
            )
            ->addColumn('employee_type', function ($order) {
                if ($order->dealer_flag_order == "1") {
                    if ($order->send_for_approval == "0") return 'Area Sales Officer';
                    if ($order->send_for_approval == "1" && !empty($order->send_for_approval_by)) return 'Sales Manager';
                    return 'N/A';
                }
                return $order->createdBy?->employeeType?->type_name ?? 'N/A';
            })
            ->addColumn('employee_name_code', function ($order) {
                if ($order->dealer_flag_order == '1') {
                    if ($order->send_for_approval == 1 && $order->sendForApprovalBy) {
                        return 'Dhanesh Kamath - PS007';
                    }

                    if ($order->send_for_approval == 0 && $order->dealers) {
                        $dealerId = $order->dealers->id;

                        $assignment = \App\Models\DealerRouteAssignment::where('dealer_id', $dealerId)
                            ->where('employee_type_id', 2) // ASO
                            ->with('employee')
                            ->first();

                        if ($assignment && $assignment->employee) {
                            return $assignment->employee->name . ' - ' . $assignment->employee->employee_code;
                        }
                    }

                    return $order->dealer?->dealer_name ?? '-';
                }

                if ($order->createdBy) {
                    return $order->createdBy->name . ' - ' . $order->createdBy->employee_code;
                }

                return 'N/A';
            })
            ->addColumn('amount', fn($order) => (float) ($order->total_amount))
            ->addColumn('status', function ($order) {
                return match ($order->order_approved) {
                    1 => '<span class="badge bg-success">Approved</span>',
                    2 => '<span class="badge bg-danger">Rejected</span>',
                    default => '<span class="badge bg-warning">Pending</span>'
                };
            })
            ->addColumn('action', fn($order) =>
                '<button class="btn btn-info btn-sm view-order" data-id="' . $order->id . '" title="View">
                    <i class="fa fa-eye"></i>
                </button>'
            )
            ->rawColumns(['status', 'action'])
            ->make(true);
    }


    public function approveOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->order_approved != 0) {
            return response()->json(['success' => false, 'message' => 'This order has already been processed.'], 400);
        }

        $request->validate([
            'payment_term' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        $paymentTermMap = [
            '- Cash Basic -' => '-1',
            'Net-04'         => '1',
            'Net-30'         => '2',
            'Net-12'         => '3',
            'Net-15'         => '4',
            'Advance'        => '5',
            'Credit DP'      => '6',
            'DP (-5000)'     => '7',
            '*'              => '8',
            'Net-25'         => '9',
            'Net-03'         => '10',
        ];

        $paymentTermId = $paymentTermMap[$request->payment_term] ?? null;

        if ($paymentTermId === null) {
            return response()->json(['success' => false, 'message' => 'Invalid payment term provided.'], 400);
        }

        $details = [];

        foreach ($order->orderItems as $orderItem) {
            foreach ($orderItem->product_details as $detail) {
                $productType = ProductType::find($detail['product_type_id']);

                if ($productType) {
                    $details[] = [
                        'ItemCode' => $productType->type_name,
                        'Quantity' => round((float) $detail['quantity'], 6),
                    'Price'    => round((float) $detail['rate'] / 1.18, 6),
                    ];
                }
            }
        }

        $orderTypeName = optional($order->orderType)->name;
        $orderTypeMap = [
            'Retail'          => '1',
            'Retail Project'  => '2',
            'Project'         => '3',
            'Own Sales'       => '4',
        ];
        $orderType = $orderTypeMap[$orderTypeName] ?? null;
        // Get Employee Info
        $empId = null;
        $empName = null;
        $employee = null;

        // if ($order->dealer_flag_order == '0') {
        //     $employee = Employee::find($order->created_by);
        // } else {
        //     $dealer = $order->created_by_dealer;
        //     $assignedRoute = AssignRoute::where('id', $dealer->assigned_route_id)->first();

        //     if ($assignedRoute) {
        //         if (is_null($assignedRoute->parent_id)) {
        //             $employee = Employee::find($assignedRoute->employee_id);
        //         } else {
        //             $employee = Employee::find($assignedRoute->parent_id); // ASO
        //         }
        //     }
        // }
        if ($order->dealer_flag_order == '0') {
            $employee = Employee::find($order->created_by);
        } else {
            $dealer = Dealer::find($order->created_by_dealer);

            $employee = null;

            if ($dealer) {
                $dealerRoute = DealerRouteAssignment::where('dealer_id', $dealer->id)->first();

                if ($dealerRoute) {
                    $assignRoute = AssignRoute::find($dealerRoute->assign_route_id);

                    if ($assignRoute) {
                        $employee = Employee::find($assignRoute->employee_id);
                    }
                }
            }
        }


        if (!empty($employee)) {
            $empId = $employee->employee_code;
            $empName = $employee->name;
        }

        $sapPayload = [
            'CardCode'      => $order->dealer->dealer_code,
            'PaymentTerm'   => $paymentTermId,
            'BillTo'        => $order->dealer->dealer_name,
            'ShipTo'        => $order->dealer->dealer_name,
            'SO_No'      => $order->id,
            'SO_Date'       => $order->created_at->format('d-m-Y'),
            'Delivery_Date' => optional($order->delivery_date)->format('d-m-Y') ?? now()->addDays(7)->format('d-m-Y'),
            'BPL_ID'        => '1',
            'Series'        => '1032',
            'OrderType'     => $orderType,
            'EmpID'         => $empId,
            'EmpName'        => $empName,
            'Scheme'        => $order->scheme,
            'Details'       => $details,
        ];

        try {
            // Log payload for debugging
        
    
        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('http://192.168.0.3:8081/api/SalesOrderDetails', $sapPayload);

            $responseBody = trim($response->body(), "\" \n\r\t");

            if ($response->successful() && strtolower($responseBody) === 'success') {
                // Only save if SAP push was successful
                $order->order_approved = '1';
                $order->status = 'Accounts Approved';
                $order->order_approved_by = Auth::id();
                $order->order_payment_terms = $request->payment_term;
                $order->order_remarks = $request->remarks;
                $order->accepted_time = now();
                $order->save();

                return response()->json(['success' => true, 'message' => 'Order approved and pushed to SAP successfully.']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'SAP Error: ' . $responseBody,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order approved, but SAP push failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    

    public function rejectOrder(Request $request, $id)
    {
        // dd($request->all());
        $order = Order::findOrFail($id);
        $order->status = 'Accounts Rejected';
        $order->order_approved = '2'; 
        $order->order_approved_by = Auth::id();
        $order->reason_for_rejection = $request->reason_for_rejection;
        $order->rejected_time = now();
        $order->save();

        return response()->json(['success' => true, 'message' => 'Order rejected successfully']);
    }

    // public function viewOrder($id)
    // {
      
    //     $order = Order::with([
    //         'dealer',
    //         'dealers.assignRoute.employee.employeeType',
    //         'createdBy.employeeType',
    //         'sendForApprovalBy.employeeType',
    //         'orderItems',
    //         'orderType',
    //         'paymentTerm'
    //     ])->where('id', $id)->first();

    //     if (!$order) {
    //         return response()->json(['success' => false, 'message' => 'Order not found!'], 404);
    //     }
    //     $dealerId = $order->dealer_flag_order == 1 ? $order->created_by_dealer : $order->dealer_id;
  
    //     $totalOutstandingAmount = OutstandingNew::where('dealer_id', $dealerId)
    //         ->sum('outstanding_amount');

    //     $employeeType = '-';
    //     $employeeNameCode = '-';
    //     $dealerName = 'N/A';
    //     $dealerCode = 'N/A';
    //     $dealerPhone = 'N/A';
    //     $dealerAddress = 'N/A';

    //      if ($order->dealer_flag_order == 1) {
    //         $dealer = $order->dealers;

    //         if ($order->send_for_approval == 0) {
            
    //             $employeeType = 'Area Sales Officer';

    //             if ($dealer && $dealer->assigned_route_id) {
    //                 $assignedRoute = \App\Models\AssignRoute::with('employee')->find($dealer->assigned_route_id);

    //                 if ($assignedRoute && $assignedRoute->employee) {
    //                     $employee = $assignedRoute->employee;
    //                     $employeeNameCode = $employee->name . ' - ' . $employee->employee_code;
    //                 }
    //             }
    //         } elseif ($order->send_for_approval == 1 && $order->send_for_approval_by) {
           
    //             $employeeType = 'Sales Manager';
    //             $employee = $order->sendForApprovalBy;
    //             $employeeNameCode = 'Dhanesh Kamath - PS007';
    //         }

       
    //         $dealerName = $dealer?->dealer_name ?? 'N/A';
    //         $dealerCode = $dealer?->dealer_code ?? 'N/A';
    //         $dealerPhone = $dealer?->phone ?? 'N/A';
    //         $dealerAddress = $dealer?->address ?? 'N/A';
    //     } else {
           
    //         $employee = $order->createdBy;
    //         $employeeType = $employee?->employeeType?->type_name ?? 'N/A';
    //         $employeeNameCode = $employee ? ($employee->name . ' - ' . $employee->employee_code) : 'N/A';

    //         $dealerName = $order->dealer?->dealer_name ?? 'N/A';
    //         $dealerCode = $order->dealer?->dealer_code ?? 'N/A';
    //         $dealerPhone = $order->dealer?->phone ?? 'N/A';
    //         $dealerAddress = $order->dealer?->address ?? 'N/A';
    //     }
	//     $orderCreatedAt = $order->created_at->format('Y-m-d');	

    //     $orderItems = $order->orderItems->map(function ($item) use ($orderCreatedAt) {
    //         $product = $item->product;

    //         $productDetails = collect($item->product_details)->map(function ($detail) use ($product, $orderCreatedAt) {
    //             $productName = 'TATA TISCON';
    //             $productType = isset($detail['product_type_id']) ? ProductType::find($detail['product_type_id']) : null;

    //             $typeName = $productType?->type_name ?? 'N/A';
    //             $quantity = (float) ($detail['quantity'] ?? 0);
    //             $rate = (float) $detail['rate'] ?? 0;

    //             $price = null;
    //             $adpPrice = null;
    //             $dpPrice = null;

    //             if (isset($detail['product_type_id'])) {
    //                 $price = Price::where('product_id', $product->id)
    //                     ->where('product_type_id', $detail['product_type_id'])
    //                     ->where('start_date', '<=', $orderCreatedAt)
    //                     ->where('end_date', '>=', $orderCreatedAt)
    //                     ->orderBy('created_at', 'desc')
    //                     ->first();

    //                 $adpPrice = $price?->advance_dealer_price;
    //                 $dpPrice = $price?->dealer_price;
    //             }

    //             return [
    //                 'product_name' => $productName,
    //                 'type_name' => $typeName,
    //                 'quantity' => $quantity,
    //                 'rate' => $rate,
    //                 'adp_price' => (float) $adpPrice,
    //                 'dp_price' => (float) $dpPrice,
    //             ];
    //         });

    //         return $productDetails;
    //     })->flatten(1);

    //     $orderData = [
    //         'order_id' => 'OD00' . $order->id,
    //         'date' => $order->created_at->format('d/m/Y'),
    //         'employee_type' => $employeeType,
    //         'employee_name_code' => $employeeNameCode,
    //         'dealer_name' => $dealerName,
    //         'dealer_code' => $dealerCode,
    //         'dealer_phone' => $dealerPhone,
    //         'dealer_address' => $dealerAddress,
    //         'order_type' => $order->orderType?->name ?? 'N/A',
    //         'payment_type' => $order->paymentTerm?->name ?? 'N/A',
            
    //         'billing_date' => optional($order->billing_date)->format('d/m/Y') ?? 'N/A',
    //         'status_badge' => $order->status,
    //         'scheme' => $order->scheme,
    //         'reason_for_rejection' => $order->reason_for_rejection,
    //         'remarks' => $order->order_remarks,
    //         'order_approved' => $order->order_approved,
    //         'payment_term' => $order->order_payment_terms,
    //         'order_status' => $order->order_approved == '1'
    //             ? '<span class="badge bg-success">Approved</span>'
    //             : ($order->order_approved == '2'
    //                 ? '<span class="badge bg-danger">Rejected</span>'
    //                 : '<span class="badge bg-warning">Pending</span>'),
    //         'order_items' => $orderItems,
    //         'total_outstanding' => (float) $totalOutstandingAmount ?? 0.00,
    //     ];
    //     $attachment = $order->attachment;

    //     // Decode attachment if stored as JSON string
    //     if (is_string($attachment)) {
    //         $decodedAttachment = json_decode($attachment, true);
    //     } else {
    //         $decodedAttachment = $attachment;
    //     }
        
    //     // Ensure it's always an array
    //     $attachments = is_array($decodedAttachment) ? $decodedAttachment : [];
    
        
    //     $orderData['attachments'] = $attachments;

    //     return response()->json(['success' => true, 'order' => $orderData]);
    // }
    public function viewOrder($id)
    {
        try {
            $order = Order::with([
                'dealer',
                'createdBy.employeeType',
                'sendForApprovalBy.employeeType',
                'orderItems',
                'orderType',
                'paymentTerm'
            ])->find($id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found!'], 404);
            }

            $dealerId = $order->dealer_flag_order == 1 ? $order->created_by_dealer : $order->dealer_id;
            $dealer = Dealer::find($dealerId);

            $totalOutstandingAmount = OutstandingNew::where('dealer_id', $dealerId)->sum('outstanding_amount');

            $employeeType = '-';
            $employeeNameCode = '-';
            $dealerName = $dealer?->dealer_name ?? 'N/A';
            $dealerCode = $dealer?->dealer_code ?? 'N/A';
            $dealerPhone = $dealer?->phone ?? 'N/A';
            $dealerAddress = $dealer?->address ?? 'N/A';

            if ($order->dealer_flag_order == 1) {
                if ($order->send_for_approval == 0) {
                    $employeeType = 'Area Sales Officer';

                    $dealerRoute = \App\Models\DealerRouteAssignment::where('dealer_id', $dealer->id)->first();
                    if ($dealerRoute) {
                        $assignRoute = \App\Models\AssignRoute::with('employee')->find($dealerRoute->assign_route_id);
                        if ($assignRoute && $assignRoute->employee) {
                            $employee = $assignRoute->employee;
                            $employeeNameCode = $employee->name . ' - ' . $employee->employee_code;
                        }
                    }
                } elseif ($order->send_for_approval == 1 && $order->send_for_approval_by) {
                    $employeeType = 'Sales Manager';
                    $employee = $order->sendForApprovalBy;
                    $employeeNameCode = $employee ? ($employee->name . ' - ' . $employee->employee_code) : 'N/A';
                }
            } else {
                $employee = $order->createdBy;
                $employeeType = $employee?->employeeType?->type_name ?? 'N/A';
                $employeeNameCode = $employee ? ($employee->name . ' - ' . $employee->employee_code) : 'N/A';
            }

            $orderCreatedAt = $order->created_at->format('Y-m-d');

            $orderItems = $order->orderItems->flatMap(function ($item) use ($orderCreatedAt) {
                $product = $item->product;

                return collect($item->product_details)->map(function ($detail) use ($product, $orderCreatedAt) {
                    $productName = 'TATA TISCON';
                    $productType = isset($detail['product_type_id'])
                        ? ProductType::find($detail['product_type_id'])
                        : null;

                    $typeName = $productType?->type_name ?? 'N/A';
                    $quantity = (float) ($detail['quantity'] ?? 0);
                    $rate = (float) ($detail['rate'] ?? 0);

                    $price = Price::where('product_id', $product->id)
                        ->where('product_type_id', $detail['product_type_id'])
                        ->where('start_date', '<=', $orderCreatedAt)
                        ->where('end_date', '>=', $orderCreatedAt)
                        ->latest()
                        ->first();

                    return [
                        'product_name' => $productName,
                        'type_name' => $typeName,
                        'quantity' => $quantity,
                        'rate' => $rate,
                        'adp_price' => (float) ($price?->advance_dealer_price ?? 0),
                        'dp_price' => (float) ($price?->dealer_price ?? 0),
                    ];
                });
            });

            $orderData = [
                'order_id' => 'OD00' . $order->id,
                'date' => $order->created_at->format('d/m/Y'),
                'employee_type' => $employeeType,
                'employee_name_code' => $employeeNameCode,
                'dealer_name' => $dealerName,
                'dealer_code' => $dealerCode,
                'dealer_phone' => $dealerPhone,
                'dealer_address' => $dealerAddress,
                'order_type' => $order->orderType?->name ?? 'N/A',
                'payment_type' => $order->paymentTerm?->name ?? 'N/A',
                'billing_date' => optional($order->billing_date)->format('d/m/Y') ?? 'N/A',
                'status_badge' => $order->status,
                'scheme' => $order->scheme,
                'reason_for_rejection' => $order->reason_for_rejection,
                'remarks' => $order->order_remarks,
                'order_approved' => $order->order_approved,
                'payment_term' => $order->order_payment_terms,
                'order_status' => match ($order->order_approved) {
                    '1' => '<span class="badge bg-success">Approved</span>',
                    '2' => '<span class="badge bg-danger">Rejected</span>',
                    default => '<span class="badge bg-warning">Pending</span>',
                },
                'order_items' => $orderItems,
                'total_outstanding' => (float) $totalOutstandingAmount ?? 0.00,
            ];

            $decodedAttachment = is_string($order->attachment)
                ? json_decode($order->attachment, true)
                : $order->attachment;

            $orderData['attachments'] = is_array($decodedAttachment) ? $decodedAttachment : [];

            return response()->json(['success' => true, 'order' => $orderData]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProductTypes(Request $request)
    {
        $selectedProductCode = session('selected_product_code');$products=[];
        if ($selectedProductCode) {
            $products = Product::where('product_code', $selectedProductCode)->first();
        }else{
            $user = auth()->user();
            $productIds = $user->product_ids ?? [];

            $products = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'product_code')
                ->first();       
        }
        // dd($products );
        $productTypes = ProductType::where('product_id', $products->id)->get();
        return response()->json([
            'product' => $products,
            'productTypes' => $productTypes
        ]);
    }

    public function priceIndex()
    {
        return view('accounts.price-management.index');
    }
    public function priceStore(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'product_id' => 'required|exists:products,id',
            'types' => 'required|array|min:1',
            'types.*.product_type_id' => 'required|exists:product_types,id',
            'types.*.dealer_price' => 'nullable|numeric',
            'types.*.advance_dealer_price' => 'nullable|numeric',
        ]);

        foreach ($validated['types'] as $type) {
            Price::where('product_id', $validated['product_id'])
                ->where('product_type_id', $type['product_type_id'])
                ->where('status', '1')
                ->update(['status' => '0']);

            Price::create([
                'product_id' => $validated['product_id'],
                'product_type_id' => $type['product_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'dealer_price' => $type['dealer_price'],
                'advance_dealer_price' => $type['advance_dealer_price'],
                'status' => '1', 
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Prices created successfully']);
    }
    public function priceShow(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

    
        $prices = Price::with(['product', 'productType'])
            ->where('start_date', $request->start_date)
            ->where('end_date', $request->end_date)
            // ->where('Status', "1")
            
            ->get();
    
        $groupInfo = $prices->first();
    
        return response()->json([
            'start_date' => $groupInfo?->start_date,
            'end_date' => $groupInfo?->end_date,
            'product_name' => $groupInfo?->product?->product_name,
            'types' => $prices->map(function ($price) {
                return [
                    'product_type' => $price->productType?->type_name ?? '-',
                    'dealer_price' => (float) $price->dealer_price,
                    'advance_dealer_price' => (float) $price->advance_dealer_price,
                ];
            }),
        ]);
    }
    
    public function priceList()
    {
        $selectedProductCode = session('selected_product_code');

        if (!$selectedProductCode) {
            $user = auth()->user();
            $productIds = $user->product_ids ?? [];

            $firstProduct = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'product_code')
                ->first();
                // $firstProduct = Product::first();
                if ($firstProduct) {
                    $selectedProductCode = $firstProduct->product_code;
                    session(['selected_product_code' => $selectedProductCode]);
                } else {
                    return datatables()->of(collect())->make(true); 
                }
            }
            // dd( $firstProduct);
            $groupedPrices = Price::with('productType.product')
            ->whereHas('productType.product', function ($q) use ($selectedProductCode) {
                $q->where('product_code', $selectedProductCode);
            })
            ->select('start_date', 'end_date', DB::raw('MAX(status) as status'))
            ->groupBy('start_date', 'end_date')
            ->orderByDesc('status')
            ->get();
            
            // dd( $groupedPrices);
        return datatables()->of($groupedPrices)
            ->addIndexColumn()
            ->editColumn('start_date', fn($row) => \Carbon\Carbon::parse($row->start_date)->format('d-m-Y'))
            ->editColumn('end_date', fn($row) => \Carbon\Carbon::parse($row->end_date)->format('d-m-Y'))
            ->addColumn('product_name', function ($row) {
                $price = Price::with('product')
                    ->where('start_date', $row->start_date)
                    ->where('end_date', $row->end_date)
                    ->first();

                return $price->product->product_name ?? '-';
            })
            ->addColumn('status', function ($row) {
                $active = Price::where('start_date', $row->start_date)
                    ->where('end_date', $row->end_date)
                    ->where('status', '1')
                    ->exists();
                return $active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                // return '<button class="btn btn-sm btn-info viewPrice" data-start="'.$row->start_date.'" data-end="'.$row->end_date.'"><i class="fa fa-eye"></i></button> <button class="btn btn-sm btn-warning editPrice" data-start="'.$row->start_date.'" data-end="'.$row->end_date.'"><i class="fa fa-pencil"></i></button>';
                // Check dealer and advance prices
                $hasMissingPrice = Price::where('start_date', $row->start_date)
                    ->where('end_date', $row->end_date)
                    ->where(function ($q) {
                        $q->whereNull('dealer_price')
                        ->orWhereNull('advance_dealer_price');
                    })
                    ->exists();

                // View button is always shown
                $viewBtn = '<button class="btn btn-sm btn-info viewPrice"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'">
                                <i class="fa fa-eye"></i>
                            </button> ';

                // Edit button only when at least one NULL field exists
                $editBtn = $hasMissingPrice
                    ? '<button class="btn btn-sm btn-warning editPrice"
                            data-start="'.$row->start_date.'"
                            data-end="'.$row->end_date.'">
                                <i class="fa fa-pencil"></i>
                            </button>'
                    : '';

                return $viewBtn . $editBtn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function editPrice(Request $request)
    {
        $request->validate([
            'start_date' => 'required',
            'end_date'   => 'required'
        ]);

        $selectedProductCode = session('selected_product_code');
        $products=[];
        if ($selectedProductCode) {
            $products = Product::where('product_code', $selectedProductCode)->first();
        }else{

            $user = auth()->user();
            $productIds = $user->product_ids ?? [];

            $products = Product::whereIn('id', $productIds)
                ->select('id', 'product_name', 'product_code')
                ->first();

            // $products = Product::first();          
        }
        // dd($products->id);
        // Get the price master row
        $price = Price::with(['product','productType'])->where('start_date', $request->start_date)
            ->where('end_date', $request->end_date)
            ->where('product_id', $products->id)
            ->get();

        if (!$price) {
            return response()->json(['message' => 'Price not found'], 404);
        }

        return response()->json([
            'data'   => $price
        ]);
    }

    public function updatePrice(Request $request)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*.id' => 'required',
            'prices.*.dealer_price' => 'nullable|numeric',
            'prices.*.advance_dealer_price' => 'nullable|numeric',
        ]);

        foreach ($request->prices as $row) {

            Price::where('id', $row['id'])->update([
                'dealer_price'         => $row['dealer_price'],
                'advance_dealer_price' => $row['advance_dealer_price'],
            ]);
        }

        return response()->json([
            'message' => 'Price updated successfully',
            'status'  => 'success'
        ]);
    }



    public function export(Request $request)
    {
        $statusFilter = $request->get('status');

        $orders = Order::with(['dealer', 'dealers', 'createdBy.employeeType'])
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('dealer_flag_order', '1')
                        ->where('status', 'Accepted')
                        ->where(function ($approvalQuery) {
                            $approvalQuery->where('send_for_approval', '0')
                                        ->orWhere('send_for_approval', '1');
                        });
                })
                ->orWhere(function ($subQuery) {
                    $subQuery->whereHas('createdBy', function ($employeeQuery) {
                        $employeeQuery->whereIn('employee_type_id', [2, 3, 4, 5]);
                    })->where('dealer_flag_order', '!=', '1')
                    ->where(function ($sourceQuery) {
                        $sourceQuery->whereNull('source')->orWhere('source', '!=', 'lead_won');
                    });
                });
            });

        if ($statusFilter === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($statusFilter === 'Rejected') {
            $orders->where('order_approved', '2');
        } elseif ($statusFilter === 'Pending') {
            $orders->where(function ($query) {
                $query->whereNull('order_approved')->orWhereNotIn('order_approved', ['1', '2']);
            });
        }

        $data = $orders->latest()->get()->map(function ($order) {
            return [
                'Date' => $order->created_at->format('d/m/Y'),
                'Order ID' => 'OD00' . $order->id,
                'Dealer Name' => $order->created_by_dealer ? $order->dealers?->dealer_name : $order->dealer?->dealer_name,
                'Dealer Code' => $order->created_by_dealer ? $order->dealers?->dealer_code : $order->dealer?->dealer_code,
                'Employee Type' => $order->dealer_flag_order == 1 ? '-' : ($order->createdBy?->employeeType?->type_name ?? 'N/A'),
                'Employee Name - Code' => $order->dealer_flag_order == 1
                    ? ($order->dealer?->dealer_name ?? '-')
                    : ($order->createdBy->name ?? '-') . ' - ' . ($order->createdBy->employee_code ?? '-'),
                'Amount' => number_format($order->total_amount, 2),
                'Status' => match ($order->order_approved) {
                    '1' => 'Approved',
                    '2' => 'Rejected',
                    default => 'Pending',
                },
            ];
        });

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return collect($this->data);
            }

            public function headings(): array
            {
                return [
                    'Date',
                    'Order ID',
                    'Dealer Name',
                    'Dealer Code',
                    'Employee Type',
                    'Employee Name - Code',
                    'Amount',
                    'Status',
                ];
            }
        }, 'orders.xlsx');
    }
    public function getTypesByProduct(Request $request)
    {
        $productId = $request->product_id;

        $types = ProductType::where('product_id', $productId)->get();

        return response()->json($types);
    }


}
