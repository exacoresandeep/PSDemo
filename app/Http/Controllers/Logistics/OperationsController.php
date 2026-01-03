<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Price;
use App\Models\OutstandingPayment;
use App\Models\OutstandingNew;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\Request;
use App\Helpers\ProductHelper;
use DB;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class OperationsController extends Controller
{
    public function index()
    {
        return view('operations.order-request.index');
    }
    

    public function new()
    {
        return view('operations.order-request.new');
    }
    public function orderListNew(Request $request)
    {
       
        $productID = ProductHelper::getSelectedProductId();

	    $statusFilter = $request->get('status');  
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');     
	    $search = $request->input('search.value');
        $orders = Order::with(['dealer', 'dealers', 'createdBy.employeeType', 'orderItems'])
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
                    })->where('dealer_flag_order', '!=', '1') 
                    ->where(function ($sourceQuery) {
                        $sourceQuery->whereNull('source')
                            ->orWhereNotIn('source', ['lead_won', 'influencer_won']); 
                    });
                });
            })
            ->where(function ($query) {
                $query->where('order_approved', '1') // Always include approved
                ->orWhere(function ($q) {
                    $q->where('order_approved', '!=', '2')
                        ->orWhereNull('order_approved');
                });
            })
            ->where(function ($q) {
                $q->whereNull('vehicle_status')
                  ->orWhereNotIn('vehicle_status', ['Despatch', 'Cancelled']);
            })
            ->orderByRaw("
                CASE 
                    WHEN order_approved = '1' THEN 1
                    WHEN order_approved IS NULL THEN 2
                    WHEN order_approved = '2' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc(DB::raw("
                CASE
                    WHEN order_approved = '1' THEN accepted_time
                    WHEN order_approved = '2' THEN rejected_time
                    ELSE created_at
                END
            "));

           

        if ($statusFilter === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($statusFilter === 'Pending') {
            $orders->where(function ($query) {
                $query->whereNull('order_approved')->orWhereNotIn('order_approved', ['1', '2']);
            });
	    }  
        
        if (!empty($fromDate) || !empty($toDate)) {

            if (!empty($fromDate) && !empty($toDate)) {
                $orders->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);
            } elseif (!empty($fromDate)) {
                $orders->whereDate('created_at', '>=', $fromDate);
            } elseif (!empty($toDate)) {
                $orders->whereDate('created_at', '<=', $toDate);
            }

        } else {
            // ✅ Default fallback → last 90 days
            $orders->whereDate('created_at', '>=', now()->subDays(90));
        }

	    
        // $orders->latest();
            return DataTables::of($orders)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value'] ?? false) {

                        $query->where(function ($q) use ($search) {
                            $q->where('orders.id', 'LIKE', "%{$search}%")
                            ->orWhere('orders.total_amount', 'LIKE', "%{$search}%")
                            ->orWhere('orders.created_at', 'LIKE', "%{$search}%")
                            ->orWhereHas('dealer', function ($d) use ($search) {
                                $d->where('dealer_name', 'LIKE', "%{$search}%")
                                    ->orWhere('dealer_code', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('dealers', function ($d) use ($search) {
                                $d->where('dealer_name', 'LIKE', "%{$search}%")
                                    ->orWhere('dealer_code', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('createdBy', function ($u) use ($search) {
                                $u->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('employee_code', 'LIKE', "%{$search}%");
                            });
                        });
                    }
                })
                ->addIndexColumn()
                ->addColumn('order_date', function ($order) {
                    return $order->created_at ? $order->created_at->format('d/m/Y h:i A') : '-';
                })
                ->addColumn('approved_rejected_date', function ($order) {
                    if ($order->order_approved == 1 && $order->accepted_time) {
                        return $order->accepted_time->format('d/m/Y h:i A');
                    } elseif ($order->order_approved == 2 && $order->rejected_time) {
                        return $order->rejected_time->format('d/m/Y h:i A');
                    } else {
                        return '-';
                    }
                })
                ->addColumn('order_id', function ($order) {
                    return 'OD00' . $order->id;
                })
                ->addColumn('dealer_name', function ($order) {
                    if ($order->created_by_dealer) {
                        return $order->dealers?->dealer_name ?? 'N/A';
                    }
                    return $order->dealer?->dealer_name ?? 'N/A';
                })
                ->addColumn('district', function ($order) {
                    if ($order->dealers) {
                        return $order->dealers->district ?? 'N/A';
                    }
                    return $order->dealer->district ?? 'N/A';
                })
                ->addColumn('vehicle_category', function ($order) {
                    if ($order->vehicleCategory) {
                        return $order->vehicleCategory->vehicle_category_name ?? 'N/A';
                    }
                    return $order->vehicleCategory->vehicle_category_name ?? 'N/A';
                })
                ->addColumn('employee_name_code', function ($order) {
                    if ($order->dealer_flag_order == 1) {

                        if ($order->send_for_approval == 1 && $order->sendForApprovalBy) {
                          
                            return 'Dhanesh Kamath - PS007';
                        }
                
                        if ($order->send_for_approval == 0 && $order->dealers) {
                          
                            $assignedRouteId = $order->dealers->assigned_route_id;
                            if ($assignedRouteId) {
                                $assignedRoute = \App\Models\AssignRoute::with('employee')->find($assignedRouteId);
                                if ($assignedRoute && $assignedRoute->employee) {
                                    return $assignedRoute->employee->name . ' - ' . $assignedRoute->employee->employee_code;
                                }
                            }
                        }
                
                        return $order->dealer?->dealer_name ?? '-';
                
                    } elseif ($order->createdBy) {
                      
                        return $order->createdBy->name . ' - ' . $order->createdBy->employee_code;
                    }
                
                    return 'N/A';
                })
                ->addColumn('quantity', function ($order) {
                    $totalQuantity = 0;

                    foreach ($order->orderItems as $item) {
                        if (is_array($item->product_details)) {
                            foreach ($item->product_details as $detail) {
                                $totalQuantity += (float) ($detail['quantity'] ?? 0);
                            }
                        }
                    }

                    return number_format($totalQuantity, 2, '.', '');
                })
                ->addColumn('status', function ($order) {
                    if ($order->order_approved == 1) {
                        return '<span class="badge bg-success">Approved</span>';
                    } elseif ($order->order_approved == 2) {
                        return '<span class="badge bg-danger">Rejected</span>';
                    }
                    return '<span class="badge bg-warning">Pending</span>';
                })
                ->addColumn('vehicle_status', function ($order) {
                   
                    return ($order->vehicle_status != "")? '<span class="badge bg-success">'.$order->vehicle_status.'</span>' : "NA";
                    
                })
                ->addColumn('vehicle_remarks', function ($order) {
                    if (!empty($order->vehicle_remarks)) {
                        $words = explode(' ', $order->vehicle_remarks);
                        if (count($words) > 6) {
                            return implode(' ', array_slice($words, 0, 6)) . '...';
                        }
                        return $order->vehicle_remarks;
                    }
                    return "NA";
                })
                 ->addColumn('action', function ($order) {
                   
                    return '<button class="btn btn-info btn-sm view-order" data-id="' . $order->id . '" title="View">
                                <i class="fa fa-eye"></i>
                            </button>';
                })
                ->rawColumns(['vehicle_status','status','action'])
                ->make(true);
    }
    public function viewOrder($id)
    {
        
        $order = Order::with(['dealer', 'createdBy.employeeType', 'orderItems','orderType','paymentTerm','vehicleCategory'])
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found!'], 404);
        }
        if ($order->dealer_flag_order == 1) {
            $dealerId = $order->created_by_dealer;
        } else {
            $dealerId = $order->dealer_id;
        }
	    $totalOutstandingAmount = OutstandingNew::where('dealer_id', $dealerId)
    		->where('outstanding_amount', '>', 0)
    		->sum('outstanding_amount');
        if ($order->dealer_flag_order == 1) {
            $dealer = Dealer::find($order->created_by_dealer);
        
            $employeeType = '-';
            $employeeNameCode = '-';
        
            if ($order->send_for_approval == 1 && $order->sendForApprovalBy) {
            
                $employeeType = 'Sales Manager';
                $employeeNameCode = 'Dhanesh Kamath - PS007';
            } elseif ($order->send_for_approval == 0 && $dealer?->assigned_route_id) {
              
                $assignedRoute = \App\Models\AssignRoute::with('employee')->find($dealer->assigned_route_id);
                if ($assignedRoute && $assignedRoute->employee) {
                    $employeeType = 'Area Sales Officer';
                    $employeeNameCode = $assignedRoute->employee->name . ' - ' . $assignedRoute->employee->employee_code;
                }
            }
        
            $dealerName = $dealer?->dealer_name ?? 'N/A';
            $dealerCode = $dealer?->dealer_code ?? 'N/A';
            $dealerDistrict = $dealer?->district ?? 'N/A';
            $dealerPhone = $dealer?->phone ?? 'N/A';
            $dealerAddress = $dealer?->address ?? 'N/A';
        } else {
            $employeeType = $order->createdBy?->employeeType?->type_name ?? 'N/A';
            $employeeNameCode = $order->createdBy ? ($order->createdBy->name . ' - ' . $order->createdBy->employee_code) : 'N/A';
        
            $dealer = $order->dealer;
            $dealerName = $dealer?->dealer_name ?? 'N/A';
            $dealerCode = $dealer?->dealer_code ?? 'N/A';
            $dealerDistrict = $dealer?->district ?? 'N/A';
            $dealerPhone = $dealer?->phone ?? 'N/A';
            $dealerAddress = $dealer?->address ?? 'N/A';
        }
	    $orderCreatedAt = $order->created_at->format('Y-m-d');	

        $orderItems = $order->orderItems->map(function ($item) use ($orderCreatedAt) {
            $product = $item->product;
        
            $productDetails = collect($item->product_details)->map(function ($detail) use ($product, $orderCreatedAt) {
                $productName = $product->product_name; 
                $productType = isset($detail['product_type_id']) ? ProductType::find($detail['product_type_id']) : null;
            
                $typeName = $productType?->type_name ?? 'N/A';
                $quantity = (float) ($detail['quantity'] ?? 0);
                $rate = $detail['rate'] ?? 0;
                $pieces=$detail['pieces'] ?? 0;
                $tonnage=$detail['tonnage'] ?? 0;
                $adpPrice = null;
                $dpPrice = null;
            
               
                if (isset($detail['product_type_id'])) {
                    $price = Price::where('product_id', $product->id)
                        ->where('product_type_id', $detail['product_type_id'])
                        ->where('start_date', '<=', $orderCreatedAt)
                        ->where('end_date', '>=', $orderCreatedAt)
                        ->orderBy('created_at', 'desc')
                        ->first();
        
                    $adpPrice = $price?->advance_dealer_price;
                    $dpPrice = $price?->dealer_price;
                }
            
                return [
                    'product_name' => $productName ?? 'N/A',
                    'type_name'    => $typeName,
                    'quantity'     => $quantity,
                    'pieces' => $pieces,
                    'tonnage' => $tonnage,
                    'rate'         =>(float) $rate,
                    'adp_price'    => $adpPrice,
                    'dp_price'     => $dpPrice,
                ];
            });
        
            return $productDetails;
        })->flatten(1);
        

        $orderData = [
            'order_id' => 'OD00' . $order->id,
            'date' => $order->created_at->format('d/m/Y'),
            'employee_type' => $employeeType,
            'employee_name_code' => $employeeNameCode,
            'dealer_name' => $dealerName,
            'dealer_code' => $dealerCode,
            'dealer_phone' => $dealerPhone,
            'product_id' => $order->product_id,
            'dealer_district' => $dealerDistrict,
            'driver_name' => $order->driver_name ?? "NA",
            'vehicle_number' => $order->vehicle_number ?? "NA",
            'driver_number' => $order->driver_phone ?? "NA",
            'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? "NA",

            'additional_information' => $order->additional_information,
            'dealer_address' => $dealerAddress,
            'order_type' => $order->orderType?->name ?? 'N/A', 
            'payment_type' => $order->paymentTerm?->name ?? 'N/A',
            'billing_date' => $order->billing_date ?? 'N/A',
            'status_badge' => $order->status,
            'scheme' => $order->scheme,
            'credit_days' => $order->credit_days,
            'reason_for_rejection' => $order->reason_for_rejection,
            'remarks' => $order->order_remarks,
            'order_approved' => $order->order_approved, 
            'vehicle_remarks' => $order->vehicle_remarks, 
            'vehicle_status' => $order->vehicle_status, 
            'payment_term' => $order->order_payment_terms, 
            'order_status' => $order->order_approved == 1 ? '<span class="badge bg-success">Approved</span>' : ($order->order_approved == 2 ? '<span class="badge bg-danger">Rejected</span>' : '<span class="badge bg-warning">Pending</span>'),
            'order_items' => $orderItems,
            'total_outstanding' => (float) $totalOutstandingAmount ?? '0.00',
        //    'due_in_days' => $dueInDays,
        ];
        $attachment = $order->attachment;

        // Decode attachment if stored as JSON string
        if (is_string($attachment)) {
            $decodedAttachment = json_decode($attachment, true);
        } else {
            $decodedAttachment = $attachment;
        }
        
        // Ensure it's always an array
        $attachments = is_array($decodedAttachment) ? $decodedAttachment : [];
    
        
        $orderData['attachments'] = $attachments;

        return response()->json(['success' => true, 'order' => $orderData]);
    }
    

    public function orderList(Request $request)
    {
        $productID = ProductHelper::getSelectedProductId();
        
	    $statusFilter = $request->get('status');  
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $vehicleStatus = $request->get('vehicle_status');       
	    $search = $request->input('search.value');
        $orders = Order::with(['dealer', 'dealers', 'createdBy.employeeType', 'orderItems', 'sendForApprovalBy'])
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
                    })->where('dealer_flag_order', '!=', '1') 
                    ->where(function ($sourceQuery) {
                        $sourceQuery->whereNull('source')
                            ->orWhereNotIn('source', ['lead_won', 'influencer_won']); 
                    });
                });
            })->orderByRaw("
                CASE
                    WHEN order_approved = '1' THEN accepted_time
                    WHEN order_approved = '2' THEN rejected_time
                    ELSE created_at
                END DESC
            ")
           
            // ->orderByRaw('FIELD(order_approved, 2,1)')
            ->orderBy('vehicle_status');

       if ($statusFilter === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($statusFilter === 'Rejected') {
                $orders->where('order_approved', '2');
        } elseif ($statusFilter === 'Pending') {
            $orders->where(function ($query) {
                $query->whereNull('order_approved')->orWhereNotIn('order_approved', ['1', '2']);
            });
	    }  
        
        if (!empty($fromDate) || !empty($toDate)) {

            if (!empty($fromDate) && !empty($toDate)) {
                $orders->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);
            } elseif (!empty($fromDate)) {
                $orders->whereDate('created_at', '>=', $fromDate);
            } elseif (!empty($toDate)) {
                $orders->whereDate('created_at', '<=', $toDate);
            }

        } else {
            $orders->whereDate('created_at', '>=', now()->subDays(90));
        }

         if (!empty($vehicleStatus)) {
            $orders->where('vehicle_status', $vehicleStatus);
        }  
        // $orders->latest();
            return DataTables::of($orders)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value'] ?? false) {

                        $query->where(function ($q) use ($search) {
                            $q->where('orders.id', 'LIKE', "%{$search}%")
                            ->orWhere('orders.total_amount', 'LIKE', "%{$search}%")
                            ->orWhere('orders.created_at', 'LIKE', "%{$search}%")
                            ->orWhereHas('dealer', function ($d) use ($search) {
                                $d->where('dealer_name', 'LIKE', "%{$search}%")
                                    ->orWhere('dealer_code', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('dealers', function ($d) use ($search) {
                                $d->where('dealer_name', 'LIKE', "%{$search}%")
                                    ->orWhere('dealer_code', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('createdBy', function ($u) use ($search) {
                                $u->where('name', 'LIKE', "%{$search}%")
                                    ->orWhere('employee_code', 'LIKE', "%{$search}%");
                            });
                        });
                    }
                })
                ->addIndexColumn()
                ->addColumn('order_date', function ($order) {
                    return $order->created_at ? $order->created_at->format('d/m/Y h:i A') : '-';
                })
                ->addColumn('approved_rejected_date', function ($order) {
                    if ($order->order_approved == 1 && $order->accepted_time) {
                        return $order->accepted_time->format('d/m/Y h:i A');
                    } elseif ($order->order_approved == 2 && $order->rejected_time) {
                        return $order->rejected_time->format('d/m/Y h:i A');
                    } else {
                        return '-';
                    }
                })
                ->addColumn('order_id', function ($order) {
                    return 'OD00' . $order->id;
                })
                ->addColumn('dealer_name', function ($order) {
                    if ($order->created_by_dealer) {
                        return $order->dealers?->dealer_name ?? 'N/A';
                    }
                    return $order->dealer?->dealer_name ?? 'N/A';
                })
                ->addColumn('district', function ($order) {
                    if ($order->dealers) {
                        return $order->dealers->district ?? 'N/A';
                    }
                    return $order->dealer->district ?? 'N/A';
                })
                ->addColumn('vehicle_category', function ($order) {
                    if ($order->vehicleCategory) {
                        return $order->vehicleCategory->vehicle_category_name ?? 'N/A';
                    }
                    return $order->vehicleCategory->vehicle_category_name ?? 'N/A';
                })
              
                ->addColumn('employee_name_code', function ($order) {
                    if ($order->dealer_flag_order == 1) {

                        if ($order->send_for_approval == 1 && $order->sendForApprovalBy) {
                          
                            return 'Dhanesh Kamath - PS007';
                        }
                
                        if ($order->send_for_approval == 0 && $order->dealers) {
                          
                            $assignedRouteId = $order->dealers->assigned_route_id;
                            if ($assignedRouteId) {
                                $assignedRoute = \App\Models\AssignRoute::with('employee')->find($assignedRouteId);
                                if ($assignedRoute && $assignedRoute->employee) {
                                    return $assignedRoute->employee->name . ' - ' . $assignedRoute->employee->employee_code;
                                }
                            }
                        }
                
                        return $order->dealer?->dealer_name ?? '-';
                
                    } elseif ($order->createdBy) {
                      
                        return $order->createdBy->name . ' - ' . $order->createdBy->employee_code;
                    }
                
                    return 'N/A';
                })
                ->addColumn('quantity', function ($order) {
                    $totalQuantity = 0;

                    foreach ($order->orderItems as $item) {
                        if (is_array($item->product_details)) {
                            foreach ($item->product_details as $detail) {
                                $totalQuantity += (float) ($detail['quantity'] ?? 0);
                            }
                        }
                    }

                    return number_format($totalQuantity, 2, '.', '');
                })
                ->addColumn('status', function ($order) {
                    if ($order->order_approved == 1) {
                        return '<span class="badge bg-success">Approved</span>';
                    } elseif ($order->order_approved == 2) {
                        return '<span class="badge bg-danger">Rejected</span>';
                    }
                    return '<span class="badge bg-warning">Pending</span>';
                })
                ->addColumn('vehicle_status', function ($order) {
                   
                    return ($order->vehicle_status != "")? '<span class="badge bg-success">'.$order->vehicle_status.'</span>' : "NA";
                    
                })
                ->addColumn('vehicle_remarks', function ($order) {
                    if (!empty($order->vehicle_remarks)) {
                        $words = explode(' ', $order->vehicle_remarks);
                        if (count($words) > 6) {
                            return implode(' ', array_slice($words, 0, 6)) . '...';
                        }
                        return $order->vehicle_remarks;
                    }
                    return "NA";
                })
                 ->addColumn('action', function ($order) {
                   
                    return '<button class="btn btn-info btn-sm view-order" data-id="' . $order->id . '" title="View">
                                <i class="fa fa-eye"></i>
                            </button>';
                })
                ->rawColumns(['vehicle_status','status','action'])
                ->make(true);
    }
    public function export(Request $request)
    {
        $productID   = ProductHelper::getSelectedProductId();
        $status      = $request->get('status');
        $fromDate    = $request->get('from_date');
        $toDate      = $request->get('to_date');
        $vehicleStatus = $request->get('vehicle_status');
        $orders = Order::with([
                'dealer',
                'dealers',
                'createdBy.employeeType',
                'orderItems',
                'vehicleCategory'
            ])
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
            ->where(function ($query) {
                $query->where('order_approved', '!=', '2')
                    ->orWhereNull('order_approved');
            })
            ->where(function ($q) {
                $q->whereNull('vehicle_status')
                ->orWhereNotIn('vehicle_status', ['Despatch', 'Cancelled']);
            });

        if ($status === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($status === 'Pending') {
            $orders->where(function ($q) {
                $q->whereNull('order_approved')
                ->orWhereNotIn('order_approved', ['1', '2']);
            });
        }

        if ($fromDate && $toDate) {
            $orders->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $orders->whereDate('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $orders->whereDate('created_at', '<=', $toDate);
        }

        $data = $orders->orderByDesc('created_at')->get()->map(function ($order) {

            $employee = $order->createdBy;
            $isDealerOrder = $order->dealer_flag_order == '1';

            return [
                    'Date' => $order->created_at->format('d/m/Y'),
                    'Order ID' => 'OD00' . $order->id,

                    'Dealer Name' => $order->created_by_dealer
                        ? $order->dealers?->dealer_name
                        : $order->dealer?->dealer_name,

                    'Dealer Code' => $order->created_by_dealer
                        ? $order->dealers?->dealer_code
                        : $order->dealer?->dealer_code,

                    'Employee Type' => $isDealerOrder ? '-' : ($employee?->employeeType?->type_name ?? 'N/A'),
                    'Employee Name' => $isDealerOrder ? '-' : ($employee->name ?? '-'),
                    'Employee Code' => $isDealerOrder ? '-' : ($employee->employee_code ?? '-'),

                    'District' => $order->dealer?->district ?? '-',
                    'Vehicle Category' => $order->vehicleCategory?->vehicle_category_name ?? 'NA',

                    // 🔹 NEW FIELDS ADDED
                    'Vehicle Number' => $order->vehicle_number ?? '-',
                    'Driver Number'  => $order->driver_phone ?? '-',
                    'Driver Name'    => $order->driver_name ?? '-',
                    'Yard Status'    => $order->vehicle_status ?? '-',
                    'Scheme'         => $order->scheme ?? '-',

                    'Order Type'         => $order->orderType->name ?? '-',
                    'Payment Type'         => $order->paymentTerm->name ?? '-',
                    'Billing Date'         => $order->billing_date ?? '-',

                    'Quantity' => $order->orderItems->sum('total_quantity'),
                    'Amount' => number_format($order->total_amount, 2),

                    'Status' => match ($order->order_approved) {
                        '1' => 'Approved',
                        '2' => 'Rejected',
                        default => 'Pending',
                    },
                ];

        });

        return Excel::download(
            new class($data) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithHeadings {

                public function __construct(public $data) {}

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
                            'Employee Name',
                            'Employee Code',
                            'District',
                            'Vehicle Category',

                            // 🔹 NEW HEADINGS
                            'Vehicle Number',
                            'Driver Number',
                            'Driver Name',
                            'Yard Status',
                            'Scheme',
                            'Order Type',
                            'Payment Type',
                            'Billing Date',

                            'Quantity',
                            'Amount',
                            'Status',
                        ];
                }
            },
            'orders.xlsx'
        );
    }

    public function exportAllOrder(Request $request)
    {
        $productID     = ProductHelper::getSelectedProductId();
        $status        = $request->get('status');
        $fromDate      = $request->get('from_date');
        $toDate        = $request->get('to_date');
        $vehicleStatus = $request->get('vehicle_status');

        $orders = Order::with([
                'dealer',
                'dealers',
                'createdBy.employeeType',
                'orderItems',
                'vehicleCategory'
            ])
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
            });

        /* -------- STATUS FILTER (SAME AS LIST) -------- */
        if ($status === 'Approved') {
            $orders->where('order_approved', '1');
        } elseif ($status === 'Rejected') {
            $orders->where('order_approved', '2');
        } elseif ($status === 'Pending') {
            $orders->where(function ($q) {
                $q->whereNull('order_approved')
                ->orWhereNotIn('order_approved', ['1', '2']);
            });
        }

        /* -------- DATE FILTER (SAME AS LIST) -------- */
        if (!empty($fromDate) || !empty($toDate)) {
            if ($fromDate && $toDate) {
                $orders->whereBetween(DB::raw('DATE(created_at)'), [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $orders->whereDate('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $orders->whereDate('created_at', '<=', $toDate);
            }
        } else {
            $orders->whereDate('created_at', '>=', now()->subDays(90));
        }

        /* -------- VEHICLE STATUS FILTER (SAME AS LIST) -------- */
        if (!empty($vehicleStatus)) {
            $orders->where('vehicle_status', $vehicleStatus);
        }

        $data = $orders
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {

                $employee = $order->createdBy;
                $isDealerOrder = $order->dealer_flag_order == '1';

                /* SAME QUANTITY LOGIC AS LIST */
                $totalQuantity = 0;
                foreach ($order->orderItems as $item) {
                    if (is_array($item->product_details)) {
                        foreach ($item->product_details as $detail) {
                            $totalQuantity += (float) ($detail['quantity'] ?? 0);
                        }
                    }
                }

                return [
                    'Date' => $order->created_at->format('d/m/Y'),
                    'Order ID' => 'OD00' . $order->id,

                    'Dealer Name' => $order->created_by_dealer
                        ? $order->dealers?->dealer_name
                        : $order->dealer?->dealer_name,

                    'Dealer Code' => $order->created_by_dealer
                        ? $order->dealers?->dealer_code
                        : $order->dealer?->dealer_code,

                    'Employee Type' => $isDealerOrder ? '-' : ($employee?->employeeType?->type_name ?? 'N/A'),
                    'Employee Name' => $isDealerOrder ? '-' : ($employee->name ?? '-'),
                    'Employee Code' => $isDealerOrder ? '-' : ($employee->employee_code ?? '-'),

                    'District' => $order->dealer?->district ?? '-',
                    'Vehicle Category' => $order->vehicleCategory?->vehicle_category_name ?? 'NA',

                    'Vehicle Number' => $order->vehicle_number ?? '-',
                    'Driver Number'  => $order->driver_phone ?? '-',
                    'Driver Name'    => $order->driver_name ?? '-',
                    'Yard Status'    => $order->vehicle_status ?? '-',
                    'Scheme'         => $order->scheme ?? '-',
                    
                    'Order Type'         => $order->orderType->name ?? '-',
                    'Payment Type'         => $order->paymentTerm->name ?? '-',
                    'Billing Date'         => $order->billing_date ?? '-',

                    'Quantity' => number_format($totalQuantity, 2, '.', ''),
                    'Amount'   => number_format($order->total_amount, 2),

                    'Status' => match ($order->order_approved) {
                        '1' => 'Approved',
                        '2' => 'Rejected',
                        default => 'Pending',
                    },
                ];
            });

        return Excel::download(
            new class($data) implements
                \Maatwebsite\Excel\Concerns\FromCollection,
                \Maatwebsite\Excel\Concerns\WithHeadings {

                public function __construct(public $data) {}

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
                        'Employee Name',
                        'Employee Code',
                        'District',
                        'Vehicle Category',
                        'Vehicle Number',
                        'Driver Number',
                        'Driver Name',
                        'Yard Status',
                        'Scheme',
                        'Order Type',
                        'Payment Type',
                        'Billing Date',
                        'Quantity',
                        'Amount',
                        'Status',
                    ];
                }
            },
            'orders.xlsx'
        );
    }


    public function changeStatus(Request $request, $id)
    {
        $order = Order::find($id);
    
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.']);
        }
    
        if ($order->order_approved != 1) {
            return response()->json(['success' => false, 'message' => 'Only approved orders can be updated.']);
        }
    
        $order->vehicle_status = $request->vehicle_status;
        $order->vehicle_remarks = $request->remarks;
        $order->save();
    
        return response()->json(['success' => true, 'message' => 'Vehicle status updated successfully.']);
    }


}

