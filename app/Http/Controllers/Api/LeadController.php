<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\Order;
use App\Models\LeadFollowUp;
use App\Models\OrderItem;
use App\Models\TripRoute;
use App\Models\ProductType;
use App\Models\InfluencerVisit;
use App\Models\InfluencerVisitFollowUp;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $leads = Lead::with(['customerType', 'district', 'tripRoute'])
                            ->where('created_by', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->get();
    
                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found',
                        'data' => [],
                    ], 200);
                }
    
                $formattedLeads = $leads->map(function ($lead) {
                    return [
                        'lead_id' => $lead->id,
                        'status' => $lead->status,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => $lead->customerType ? [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ] : null,  
                        'district' => $lead->district ? [
                            'id' => $lead->district->id,
                            'name' => $lead->district->name,
                        ] : null,  
                        'route_name' => $lead->tripRoute ? $lead->tripRoute->route_name : null, 
                        'location_name' => $lead->location ? $lead->location : null,
                        'created_at' => $lead->created_at->format('d/M/Y h:i A'),
                        'updated_at' => $lead->updated_at->format('d/M/Y h:i A'),
                        'follow_up_date' => $lead->follow_up_date,
                        ];
                });
    
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Leads retrieved successfully!',
                    'data' => $formattedLeads,
                ], 200);
    
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
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

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_type' => 'required|exists:customer_types,id',
                'customer_name' => 'required|string',
                'phone' => 'required|string',
                'address' => 'required|string',
                'city' => 'required|string',
                'location' => 'required|string',
                'district_id' => 'required|exists:districts,id',
                'assigned_route_id' => 'required|exists:assigned_routes,id',
            ]);
            $existingLead = Lead::where('phone', $request->phone)->first();
            if ($existingLead) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Lead with the same phone number already exists!',
                    'data' =>[],
                ], 400);
            }
         

            $validatedData['created_by'] = Auth::id();

            $lead = Lead::create($validatedData);
            $leadData = [
                'customer_type' => $lead->customerType->name ?? null,
                'customer_name' => $lead->customer_name,
                'address' => $lead->address,
                'city' => $lead->city,
                'sub_location' => $lead->location,
                'phone' => $lead->phone,
                'district' => $lead->district->name ?? null,
                'assigned_route_id' => $lead->assigned_route_id,
                'status' => 'Opened',
                'created_at' => $lead->created_at->format('Y-m-d H:i:s'),

            ];
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead created successfully!',
                'data' => $leadData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function show($leadId)
    {
        try {
            $employee = Auth::user();
            $allowedEmployeeTypes = [];

            switch ($employee->employee_type_id) {
                case 1: 
                    $allowedEmployeeTypes = [1]; 
                    break;
                case 2: 
                    $allowedEmployeeTypes = [2]; 
                    break;
                case 3: 
                    $allowedEmployeeTypes = [1,2,3]; 
                    break;
                case 4: 
                    $allowedEmployeeTypes = [1, 2, 3, 4]; 
                    break;
                case 5:
                    $allowedEmployeeTypes = [1, 2, 3, 4, 5]; 
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'statusCode' => 403,
                        'message' => "Unauthorized access.",
                    ], 403);
            }

            $lead = Lead::with(['customerType', 'district', 'assignRoute', 'dealer', 'orders.orderItems.product', 'orders.paymentTerm', 'orders.dealer', 'followUps'])
                ->whereHas('createdBy', function ($query) use ($allowedEmployeeTypes) {
                    $query->whereIn('employee_type_id', $allowedEmployeeTypes);
                })
                ->findOrFail($leadId);
            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Lead not found!',
                ], 404);
            }

            $leadWonOrders = $lead->orders->where('source', 'lead_won');
            $relatedLeads = Lead::with('orders.orderItems')
                ->where('lead_chain_id', $lead->lead_chain_id)
                ->get();
            $wonVolume = $relatedLeads->flatMap(function ($relatedLead) {
                return $relatedLead->orders->flatMap(function ($order) {
                    return $order->orderItems;
                });
		        })->sum('total_quantity'); 
            $lostVolume = $relatedLeads->sum(function ($relatedLead) {
                return $relatedLead->lost_volume ?? 0;
	        });
            if ((float) $lead->total_quantity === 0.0) {
                $wonVolume = 0;
                $lostVolume = 0;
            }
            $paymentTerms = $leadWonOrders
                ->pluck('paymentTerm')
                ->unique('id')
                ->filter()
                ->map(function ($paymentTerm) {
                    return [
                        'id' => $paymentTerm->id,
                        'name' => $paymentTerm->name,
                    ];
                })
                ->values();
            $paymentTerms = $paymentTerms->count() === 1 ? $paymentTerms->first() : ($paymentTerms->isEmpty() ? null : $paymentTerms);
            
            $dealers = $leadWonOrders
                ->pluck('dealer')
                ->unique('id')
                ->filter()
                ->map(function ($dealer) {
                    return [
                        'id' => $dealer->id,
                        'name' => $dealer->dealer_name,
                    ];
                })
                ->values();
            $dealers = $dealers->count() === 1 ? $dealers->first() : ($dealers->isEmpty() ? null : $dealers);
             $latestOrder = $leadWonOrders->last();
            $attachments = [
                'attachment' => $latestOrder && $latestOrder->attachment ? $latestOrder->attachment : null,
              
            ];
            $leadData = [
                'id' => $lead->id,
                'customer_type' => $lead->customerType ? [
                    'id' => $lead->customerType->id,
                    'name' => $lead->customerType->name,
                ] : null,
                'customer_name' => $lead->customer_name,
                'city' => $lead->city,
                'location' => $lead->location,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'district' => $lead->district ? [
                    'id' => $lead->district->id,
                    'name' => $lead->district->name,
                ] : null,
                'trip_route' => $lead->assignRoute ? [
                    'id' => $lead->assignRoute->id,
                    'route_name' => $lead->assignRoute->route_name,
                    'location_name' => $lead->assignRoute->locations,
                ] : null,
                'type_of_visit' => $lead->type_of_visit,
                'construction_type' => $lead->construction_type,
                'construction_type_name' => $lead->construction_type_name,
                'stage_of_construction' => $lead->stage_of_construction,
                'follow_up_date' => $lead->follow_up_date,
                'lead_score' => $lead->lead_score,
                'lead_source' => $lead->lead_source,
                'source_name' => $lead->source_name,
                'total_volume' => (float) ($lead->total_deal_volume ?? $lead->total_volume),
                'total_quantity' => (float) $lead->total_quantity,
                'current_deal_volume' => (float) $lead->total_deal_volume - $wonVolume - $lostVolume,
                // 'current_deal_volume' => (float) $lead->current_deal_volume,
                'won_volume' => (float) $wonVolume,
                'lost_v' => (float) $lostVolume,
                // 'total_deal_volume' => (float) $wonVolume + $lostVolume + $lead->current_deal_volume,
                // 'volume' => (float) $lead->previous_quantity,
                'previous_brand' => $lead->previous_brand,
                'brand_name' => $lead->brand_name,
                'previous_brand_quantity' => (float) $lead->previous_brand_quantity,
                'customer_meet' => $lead->customer_meet,
                'ring_test' => $lead->ring_test,
                'further_requirement' => $lead->further_requirement,
                'further_volume' => (float) $lead->further_volume,
                'lost_volume' => (float) $lead->lost_volume,
                'lost_to_competitor' => $lead->lost_to_competitor,
                'competitor_name' =>$lead->competitor_name,
                'reason_for_lost' => $lead->reason_for_lost,
                'status' => $lead->status,
                'dealer' => $lead->dealer ? [
                    'id' => $lead->dealer->id,
                    'name' => $lead->dealer->dealer_name,
                ] : null,
                'created_by' => $lead->created_by,
                'created_at' => $lead->created_at->format('d/M/Y'),
                'updated_at' => $lead->updated_at,
                'follow_ups' => $lead->followUps->map(function ($followUp) {
                    return [
                        'id' => $followUp->id,
                        'follow_up_date' => $followUp->follow_up_date,
                        'follow_up_reason' => $followUp->reason,
                        
                    ];
                })->values(),
                
                'payment_terms' => $paymentTerms,
                'dealers' => $dealers,
                 'attachment' => $attachments['attachment'],
                'orders' => $leadWonOrders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'billing_date' => $order->billing_date,
                        'credit_days'  => $order->credit_days,
                       
                        'order_items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'product_name' => $item->product ? $item->product->product_name : null,
                                'total_quantity' => $item->total_quantity,
                                'balance_quantity' => (float) $item->balance_quantity,
                                'product_details' => $item->product_details,
                            ];
                        })->values(),
                    ];
                })->values(),

            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead retrieved successfully!',
                'data' => $leadData,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchLead(Request $request)
    {
      
        try {
            $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
                ], 401);
            }
    
            $query = Lead::with(['customerType', 'district', 'tripRoute'])
                        ->where('created_by', $user->id);
                        
            if ($request->has('status')) {
                $status = $request->input('status');

                if (is_string($status)) {
                    $status = explode(',', $status);
                }
    
                $query->whereIn('status', $status);
            }
    
            if ($request->has('customer_name')) {
                $query->where('customer_name', 'like', '%' . $request->input('customer_name') . '%');
            }
    
            $leads = $query->orderBy('customer_name', 'asc')->get();
    
            if ($leads->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No leads found.',
                    'data' => [],
                ], 200);
            }
    
            $formattedLeads = $leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'status' => $lead->status,
                    'customer_name' => $lead->customer_name,
                    'customer_type' => $lead->customerType ? [
                        'id' => $lead->customerType->id,
                        'name' => $lead->customerType->name,
                    ] : null,
                    'district' => $lead->district ? [
                        'id' => $lead->district->id,
                        'name' => $lead->district->name,
                    ] : null,
                    'route_name' => $lead->tripRoute->route_name ?? null,
                    'location_name' => $lead->location,
                    'created_at' => $lead->created_at->format('d/M/Y'),
                    'updated_at' => $lead->updated_at,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Filtered leads retrieved successfully!',
                'data' => $formattedLeads,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLead(Request $request, $leadId)
    {
        try {
            // ✅ Step 1: Validate request
            $validatedData = $request->validate([
                'type_of_visit' => 'required|string',
                'construction_type' => 'required|string',
                'construction_type_name' => 'nullable|string',
                'stage_of_construction' => 'required|string',
                'follow_up_date' => 'nullable|date',
                'follow_up_reason' => 'nullable|string',
                'lead_score' => 'required|string',
                'lead_source' => 'required|string',
                'source_name' => 'nullable|string',
                'total_quantity' => 'required|numeric',
                'total_volume' => 'required|numeric',
                'status' => 'required|in:Opened,Follow Up,Won,Lost',
                'dealer_id' => 'nullable|numeric',
    
                // Lost details
                'lost_details.lost_volume' => 'required_if:status,Lost|nullable|numeric',
                'lost_details.lost_to_competitor' => 'required_if:status,Lost|nullable|string',
                'lost_details.competitor_name' => 'nullable|string',
                'lost_details.reason_for_lost' => 'required_if:status,Lost|nullable|string',
                'previous_brand' => 'required_if:status,Lost|nullable|string',
                'brand_name' => 'nullable|string',
                'previous_brand_quantity' => 'required_if:status,Lost|nullable|numeric',
                'customer_meet' => 'required_if:status,Lost|nullable|in:Yes,No',
                'ring_test' => 'required_if:status,Lost|nullable|in:Yes,No',
                'further_requirement' => 'required_if:status,Lost|nullable|in:Yes,No',
                'further_volume' => 'required_if:status,Lost|nullable|numeric',
    
                // Won order details
                'order_details.customer_type_id' => 'required_if:status,Won|nullable|exists:customer_types,id',
                'order_details.dealer_id' => 'nullable|exists:dealers,id',
                'order_details.dealer_flag_order' => 'nullable|numeric',
                'order_details.payment_terms_id' => 'required_if:status,Won|nullable|exists:payment_terms,id',
                'order_details.total_amount' => 'required_if:status,Won|nullable|numeric',
                'order_details.order_items' => 'required_if:status,Won|nullable|array',
                'order_details.order_items.*.product_id' => 'required_with:order_details.order_items|exists:products,id',
                'order_details.order_items.*.total_quantity' => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.balance_quantity' => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.product_details' => 'nullable|array',
                'order_details.attachment' => 'nullable|array',
                'order_details.attachment.*' => 'nullable|string',
            ]);
    
            // ✅ Step 2: Find the lead
            $lead = Lead::where('id', $leadId)
                ->where('created_by', Auth::id())
                ->firstOrFail();
    
            if (!$lead->lead_chain_id) {
                $lead->update(['lead_chain_id' => (string) Str::uuid()]);
            }
    
            // ✅ Determine total deal volume
            if ($lead->lead_chain_id) {
                $originalLead = Lead::where('lead_chain_id', $lead->lead_chain_id)
                    ->orderBy('created_at', 'asc')
                    ->first();
                $totalDealVolume = $originalLead ? $originalLead->total_volume : $lead->total_volume;
            } else {
                $totalDealVolume = $lead->total_volume;
            }
    
            DB::beginTransaction();
    
            // ✅ Step 3: Notification status
            switch ($request->status) {
                case 'Follow Up':
                    $notification_status = 'approved';
                    break;
                case 'Won':
                case 'Lost':
                default:
                    $notification_status = 'pending';
                    break;
            }
    
            // ✅ Step 4: Handle Follow Up
            if ($request->status === 'Follow Up') {
                LeadFollowUp::create([
                    'lead_id' => $lead->id,
                    'follow_up_date' => $request->follow_up_date,
                    'reason' => $request->follow_up_reason,
                    'created_by' => Auth::id(),
                ]);
                $lead->update(['follow_up_date' => $request->follow_up_date]);
            }
    
            // ✅ Step 5: Prepare lead data
            $leadData = [
                'type_of_visit' => $request->type_of_visit,
                'construction_type' => $request->construction_type,
                'construction_type_name' => $request->construction_type_name,
                'stage_of_construction' => $request->stage_of_construction,
                'follow_up_date' => $request->follow_up_date,
                'lead_score' => $request->lead_score,
                'lead_source' => $request->lead_source,
                'source_name' => $request->source_name,
                'total_quantity' => $request->total_quantity,
                'total_deal_volume' => $totalDealVolume,
                'total_volume' => $request->total_volume,
                'status' => $request->status,
                'notification_status' => $notification_status,
                'updated_by' => Auth::id(),
            ];
    
            if (!empty($request->dealer_id)) {
                $leadData['dealer_id'] = $request->dealer_id;
            }
    
            // ✅ Step 6: Add Lost details if status is Lost
            if ($request->status === 'Lost' && !empty($request->lost_details)) {
                $lost = $request->lost_details;
                $leadData = array_merge($leadData, [
                    'lost_volume' => $lost['lost_volume'] ?? null,
                    'lost_to_competitor' => $lost['lost_to_competitor'] ?? null,
                    'competitor_name' => $lost['competitor_name'] ?? null,
                    'reason_for_lost' => $lost['reason_for_lost'] ?? null,
                    'previous_brand' => $request->previous_brand ?? null,
                    'brand_name' => $request->brand_name ?? null,
                    'previous_brand_quantity' => $request->previous_brand_quantity ?? null,
                    'customer_meet' => $request->customer_meet ?? null,
                    'ring_test' => $request->ring_test ?? null,
                    'further_requirement' => $request->further_requirement ?? null,
                    'further_volume' => $request->further_volume ?? null,
                ]);
            }
    
            $lead->update($leadData);
    
            $order = null;
    
            // ✅ Step 7: Handle Won status
            if ($request->status === 'Won' && !empty($request->order_details)) {
            $orderDetails = $request->order_details;

            $order = Order::create([
                'customer_type_id' => $orderDetails['customer_type_id'],
                'lead_id' => $lead->id,
                'dealer_id' => $orderDetails['dealer_id'] ?? null,
                'dealer_flag_order' => $orderDetails['dealer_flag_order'] ?? 0,
                'payment_terms_id' => $orderDetails['payment_terms_id'],
                'credit_days' => $orderDetails['credit_days'] ?? null,
                'total_amount' => (float) $orderDetails['total_amount'],
                'billing_date' => now()->format('Y-m-d'),
                'status' => 'Pending',
                'source' => 'lead_won',
                'created_by' => Auth::id(),
                'attachment' => $orderDetails['attachment'] ?? $request->attachment ?? [],
            ]);

            if (!empty($orderDetails['order_items']) && is_array($orderDetails['order_items'])) {
                $orderItems = [];
                foreach ($orderDetails['order_items'] as $item) {
                    $orderItems[] = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'total_quantity' => $item['total_quantity'],
                        'balance_quantity' => $item['balance_quantity'],
                        'product_details' => isset($item['product_details']) ? json_encode($item['product_details']) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                OrderItem::insert($orderItems);
            }
        }

        // Step 9: Calculate total Won/Lost volume across lead_chain_id
        $totalWonVolume = Lead::where('lead_chain_id', $lead->lead_chain_id)
            ->where('status', 'Won')
            ->with('orders.orderItems')
            ->get()
            ->sum(function ($l) {
                return $l->orders->sum(function ($order) {
                    return $order->orderItems->sum('total_quantity');
                });
            });

        $totalLostVolume = Lead::where('lead_chain_id', $lead->lead_chain_id)
            ->where('status', 'Lost')
            ->sum('lost_volume');

        $handledVolume = $totalWonVolume + $totalLostVolume;

        // Step 10: Create new Opened lead if all volume handled
        if ($handledVolume >= $totalDealVolume) {
            Lead::create([
                'customer_type' => $lead->customer_type,
                'customer_name' => $lead->customer_name,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'city' => $lead->city,
                'location' => $lead->location,
                'district_id' => $lead->district_id,
                'assigned_route_id' => $lead->assigned_route_id,
                'status' => 'Opened',
                'created_by' => Auth::id(),
            ]);
        }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
                'order_details' => $order,
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function leadsList(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $leads = Lead::with('customerType')
                            ->where('created_by', $user->id)
                            ->orderBy('customer_name', 'asc')
                            ->get();
                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found for the logged-in user.',
                        'data' => [],
                    ], 200);
                }

                $formattedLeads = $leads->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'customer_type' => [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ],
                        'customer_name' => $lead->customer_name,
                        'phone' => $lead->phone,
                        'address' => $lead->address,
                        'instructions' => $lead->instructions,
                        'record_details' => $lead->record_details,
                        'attachments' => $lead->attachments,
                        'latitude' => $lead->latitude,
                        'longitude' => $lead->longitude,
                        'status' => $lead->status,
                        'created_by' => $lead->created_by,
                        'created_at' => $lead->created_at->format('d/M/Y'),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Leads retrieved successfully!',
                    'data' => $formattedLeads,
                ], 200);

            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
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

    public function getleadsFilter($customer_type_id, Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = Lead::with('customerType')
                            ->where('created_by', $user->id)
                            ->where('customer_type', $customer_type_id);

                if ($request->has('search_key') && !empty($request->search_key)) {
                    $searchKey = $request->search_key;

                    $query->where(function ($q) use ($searchKey) {
                        $q->where('customer_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('phone', 'like', '%' . $searchKey . '%');
                    });
                }

                $leads = $query->orderBy('customer_name', 'asc')->get();

                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found matching the filter.',
                        'data' => [],
                    ], 200);
                }

                $formattedLeads = $leads->map(function ($lead) {
                    return [
                        'lead_id' => $lead->id,
                        'status' => $lead->status,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => $lead->customerType ? [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ] : null,
                        'city' => $lead->city,
                        'location' => $lead->location,
                        'phone' => $lead->phone,
                        'address' => $lead->address,
                        'district' => $lead->district ? [
                            'id' => $lead->district->id,
                            'name' => $lead->district->name,
                        ] : null,
                        'route_name' => $lead->tripRoute ? $lead->tripRoute->route_name : null,
                        'location_name' => $lead->tripRoute ? $lead->tripRoute->location_name : null,
                        'created_at' => $lead->created_at->format('d/M/Y'),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Leads retrieved successfully!',
                    'data' => $formattedLeads,
                ], 200);

            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
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
    public function updateOpenLeads($leadId, Request $request)
    {
        try {
            $validated = $request->validate([
                'type_of_visit' => 'required|string',
                'construction_type' => 'required|string',
                'construction_type_name' => 'nullable|string',
                'stage_of_construction' => 'required|string',
                'follow_up_date' => 'required|date',
                'lead_score' => 'required|string',
                'lead_source' => 'required|string',
                'source_name' => 'nullable|string',
                'total_quantity' => 'required|integer',
                'status' => 'required|string',
            ]);

            $lead = Lead::where('id', $leadId)
                        ->where('created_by', Auth::id())
                        ->firstOrFail();

            $lead->update([
                'type_of_visit' => $validated['type_of_visit'],
                'construction_type' => $validated['construction_type'],
                'construction_type_name' => $validated['construction_type_name'],
                'stage_of_construction' => $validated['stage_of_construction'],
                'follow_up_date' => $validated['follow_up_date'],
                'lead_score' => $validated['lead_score'],
                'lead_source' => $validated['lead_source'],
                'source_name' => $validated['source_name'],
                'total_quantity' => $validated['total_quantity'],
                'status' => $validated['status'],
            ]);
            

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateLostLeads($leadId, Request $request)
    {
        try {
            $validated = $request->validate([
                'lost_volume' => 'required|numeric', 
                'lost_to_competitor' => 'required|string',
                 'competitor_name' => 'nullable|string',
                'reason_for_lost' => 'required|string',
            ]);

            $lead = Lead::where('id', $leadId)
                        ->where('created_by', Auth::id())
                        ->firstOrFail();

            $lead->update($validated);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function createInfluencerVisit(Request $request)
    {
        try {
            $validated = $request->validate([
                'influencer_name'     => 'required|string|max:255',
                'phone'               => 'required|string|max:20',
                'place'               => 'required|string|max:255',
                'influencer_type'     => 'required|string|max:255',
                'visit_type'          => 'required|string|max:255',
                'purpose'             => 'required|string|max:255',
                'district_id'         => 'required|integer',
                'lead_type'           => 'required|string|max:255',
                'current_project'     => 'nullable|string|max:255',
                'upcoming_project'    => 'nullable|string|max:255',
                'steel_used'          => 'nullable|array',
                'other_steels'	      => 'nullable|string|max:255',
                'total_deal_volume'   => 'required|numeric',
                'status'              => 'nullable|in:Opened,Follow Up,Won,Lost',

                // Follow Up
                'follow_up_date'      => 'required_if:status,Follow Up|nullable|date',
                'follow_up_reason'    => 'required_if:status,Follow Up|nullable|string',
    
                // Lost
                'lost_details.lost_volume' => 'required_if:status,Lost|nullable|numeric',
                'lost_details.lost_to_competitor' => 'required_if:status,Lost|nullable|string',
                'lost_details.competitor_name' => 'nullable|string',
                'lost_details.reason_for_lost' => 'required_if:status,Lost|nullable|string',
    
                // Won
                'order_details.dealer_id'        => 'nullable|exists:dealers,id',
                'order_details.dealer_flag_order'=> 'nullable|numeric',
                'order_details.payment_terms_id' => 'required_if:status,WON|nullable|exists:payment_terms,id',
                'order_details.credit_days'      => 'nullable|string|max:255',
                'order_details.total_amount'     => 'required_if:status,Won|nullable|numeric',
                'order_details.order_items'      => 'required_if:status,Won|nullable|array',
                'order_details.order_items.*.product_id'        => 'required_with:order_details.order_items|exists:products,id',
                'order_details.order_items.*.total_quantity'    => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.balance_quantity'  => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.product_details'   => 'nullable|array',
                'order_details.attachment' => 'nullable|array',
                'order_details.attachment.*' => 'nullable|string',
             
            ]);
            if (isset($validated['steel_used']) && is_array($validated['steel_used'])) {
                $validated['steel_used'] = $validated['steel_used']; 
            } else {
                $validated['steel_used'] = null;
            }
            $validated['created_by'] = Auth::id();
            $validated['status'] = $validated['status'] ?? 'Opened';
    
             DB::beginTransaction();

        $visit = InfluencerVisit::create($validated);

        $order = null;

        // Follow Up
        if ($validated['status'] === 'Follow Up') {
            InfluencerVisitFollowUp::create([
                'influencer_visit_id' => $visit->id,
                'follow_up_date'      => $request->follow_up_date,
                'reason'              => $request->follow_up_reason,
                'created_by'          => Auth::id(),
            ]);

            $visit->update([
                'follow_up_date' => $request->follow_up_date,
            ]);
        }

        // Lost
        if ($validated['status'] === 'Lost' && !empty($request->lost_details)) {
            $visit->update([
                'lost_volume'        => $request->lost_details['lost_volume'],
                'lost_to_competitor' => $request->lost_details['lost_to_competitor'],
                'competitor_name' => $request->lost_details['competitor_name'],
                'reason_for_lost'    => $request->lost_details['reason_for_lost'],
            ]);
        }

        // Won
        if ($validated['status'] === 'Won' && !empty($request->order_details)) {
            $details = $request->order_details;
            
            $order = Order::create([
                'influencer_visit_id' => $visit->id,
                'dealer_id'           => $details['dealer_id'] ?? null,
                'dealer_flag_order'   => $details['dealer_flag_order'] ?? '0',
                'payment_terms_id'    => $details['payment_terms_id'],
                'credit_days'         => $details['credit_days'] ?? 0,
                'total_amount'        => (float)$details['total_amount'],
                'status'              => 'Pending',
                'source'              => 'influencer_won',
                'created_by'          => Auth::id(),
                'attachment'          => $details['attachment'] ?? $request->attachment ?? [],
            ]);

            if (!empty($details['order_items'])) {
                $items = array_map(function ($item) use ($order) {
                    return [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'total_quantity' => $item['total_quantity'],
                        'balance_quantity' => $item['balance_quantity'],
                        'product_details' => json_encode($item['product_details']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $details['order_items']);

                OrderItem::insert($items);
            }
        }

        DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Influencer visit created successfully.',
                'data' => $visit
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function updateInfluencerVisit(Request $request, $visitId)
    {
        try {
            $validated = $request->validate([
                'status'              => 'required|in:Opened,Follow Up,Won,Lost',
    
                // Follow Up
                'follow_up_date'      => 'required_if:status,Follow Up|nullable|date',
                'follow_up_reason'    => 'required_if:status,Follow Up|nullable|string',
    
                // Lost
                'lost_details.lost_volume' => 'required_if:status,Lost|nullable|numeric',
                'lost_details.lost_to_competitor' => 'required_if:status,Lost|nullable|string',
                'lost_details.competitor_name' => 'nullable|string',
                'lost_details.reason_for_lost' => 'required_if:status,Lost|nullable|string',
    
                // Won
                // 'order_details.customer_type_id' => 'required_if:status,Won|nullable|exists:customer_types,id',
                'order_details.dealer_id'        => 'nullable|exists:dealers,id',
                'order_details.dealer_flag_order'=> 'nullable|numeric',
                'order_details.payment_terms_id' => 'required_if:status,Won|nullable|exists:payment_terms,id',
                'order_details.credit_days'      => 'nullable|string',
                'order_details.total_amount'     => 'required_if:status,Won|nullable|numeric',
                'order_details.order_items'      => 'required_if:status,Won|nullable|array',
                'order_details.order_items.*.product_id'        => 'required_with:order_details.order_items|exists:products,id',
                'order_details.order_items.*.total_quantity'    => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.balance_quantity'  => 'required_with:order_details.order_items|numeric',
                'order_details.order_items.*.product_details'   => 'nullable|array',
                'order_details.attachment' => 'nullable|array',
                'order_details.attachment.*' => 'nullable|string',
            ]);
    
            $visit = InfluencerVisit::findOrFail($visitId);
    
            DB::beginTransaction();
    
            // Store follow up
            if ($request->status === 'Follow Up') {
                InfluencerVisitFollowUp::create([
                    'influencer_visit_id' => $visit->id,
                    'follow_up_date' => $request->follow_up_date,
                    'reason' => $request->follow_up_reason,
                    'created_by' => Auth::id(),
                ]);
            }
    
            // Update influencer visit
            $visit->update([
                'follow_up_date' => $request->follow_up_date,
                'status'              => $request->status,
            ]);
    
            $order = null;
    
            // Store order if status is WON
            if ($request->status === 'Won' && !empty($request->order_details)) {
                $details = $request->order_details;
                
                $order = Order::create([
                    'influencer_visit_id' => $visit->id,
                    'dealer_id'           => $details['dealer_id'] ?? null,
                    'dealer_flag_order'   => $details['dealer_flag_order'] ?? '0',
                    'payment_terms_id'    => $details['payment_terms_id'],
                    'credit_days'         => $details['credit_days'] ?? 0,
                    'total_amount'        => (float)$details['total_amount'],
                    // 'billing_date'        => now()->format('Y-m-d'),
                    'status'              => 'Pending',
                    'source'              => 'influencer_won',
                    'created_by'          => Auth::id(),
                    'attachment'          => $details['attachment'] ?? $request->attachment ?? [],
                ]);
    
                if (!empty($details['order_items'])) {
                    $items = array_map(function ($item) use ($order) {
                        return [
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'total_quantity' => $item['total_quantity'],
                            'balance_quantity' => $item['balance_quantity'],
                            'product_details' => json_encode($item['product_details']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $details['order_items']);
    
                    OrderItem::insert($items);
                }
            }
    
            // Store lost reason if status is LOST
            if ($request->status === 'Lost' && !empty($request->lost_details)) {
                $visit->update([
                    'lost_volume' => $request->lost_details['lost_volume'],
                    'lost_to_competitor' => $request->lost_details['lost_to_competitor'],
                    'competitor_name' => $request->lost_details['competitor_name'],
                    'reason_for_lost' => $request->lost_details['reason_for_lost'],
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Influencer visit updated successfully.',
                'data' => $visit,
                'order_details' => $order,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function influencerVisitListing(Request $request)
    {
        try {
            $employee = Auth::user(); // Get the logged-in employee

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
            $visits = InfluencerVisit::select(
                    'id',
                    'influencer_name',
                    'purpose',
                    'created_at',
                    'follow_up_date',
                    'status'
                )
                ->where('created_by', $employee->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'influencer_name' => $visit->influencer_name,
                        'purpose' => $visit->purpose,
                        'created_at' => $visit->created_at ? $visit->created_at->format('d/m/Y') : null,
                        'follow_up_date' => $visit->follow_up_date ? date('d/m/Y', strtotime($visit->follow_up_date)) : null,
                        'status' => $visit->status,
                    ];
                });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Influencer visit list fetched successfully.',
                'data' => $visits,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function influencerVisitDetails($visitId)
    {
        try {
            $visit = InfluencerVisit::with([
                'district',
                'followUps',
                'order.orderItems.product',
                'order.paymentTerm',
                'order.dealer',
            ])->findOrFail($visitId);
    
            $data = [
                'id' => $visit->id,
                'influencer_name' => $visit->influencer_name,
                'phone' => $visit->phone,
                'place' => $visit->place,
                'influencer_type' => $visit->influencer_type,
                'visit_type' => $visit->visit_type,
                'purpose' => $visit->purpose,
                'lead_type' => $visit->lead_type,
                'district' => $visit->district ? [
                    'id' => $visit->district->id,
                    'name' => $visit->district->name,
                ] : null,
                'status' => $visit->status,
                'created_at' => optional($visit->created_at)->format('d/m/Y'),
            ];
    
            // Common fields for all except "Opened"
            // if ($visit->status !== 'Opened') {
                $data['follow_up_date'] = optional($visit->follow_up_date)->format('d/m/Y');
                $data['current_project'] = $visit->current_project;
                $data['upcoming_project'] = $visit->upcoming_project;
                $data['steel_used'] = $visit->steel_used;
                $data['other_steels'] = $visit->other_steels;
                $data['total_deal_volume'] = $visit->total_deal_volume;
            // }
    
            // Status-specific data
            if ($visit->status === 'Follow Up') {
                $data['follow_ups'] = $visit->followUps->map(function ($followUp) {
                    return [
                        'id' => $followUp->id,
                        'follow_up_date' => $followUp->follow_up_date,
                        'follow_up_reason' => $followUp->reason,
                    ];
                })->values();
            }
    
            if ($visit->status === 'Lost') {
                $data['lost_volume'] = (float) $visit->lost_volume;
                $data['lost_to_competitor'] = $visit->lost_to_competitor;
                $data['competitor_name'] = $visit->competitor_name;
                $data['reason_for_lost'] = $visit->reason_for_lost;
            }
    
            if ($visit->status === 'Won' && $visit->order) {
                $order = $visit->order;
                $orderItems = $order->orderItems->map(function ($item) {
                    $productDetails = collect($item->product_details)->map(function ($detail) {
                        $productType = ProductType::find($detail['product_type_id']);
                        // dd($productType);
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
                        'total_quantity' => (float) $item->total_quantity,
                        'product_details' => $productDetails,
                    ];
                });
                $data['order'] = [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'credit_days' => $order->credit_days,
                    // 'billing_date' => optional($order->billing_date)->format('d/m/Y'),
                    'dealer' => $order->dealer ? [
                        'id' => $order->dealer->id,
                        'name' => $order->dealer->dealer_name,
                        'code' => $order->dealer->dealer_code,
                    ] : null,
                    'payment_terms' => $order->paymentTerm ? [
                        'id' => $order->paymentTerm->id,
                        'name' => $order->paymentTerm->name,
                    ] : null,
                    'attachment' => $order->attachment 
                    ? $order->attachment 
                    : null,
                   
                    'order_items' => $orderItems
                ];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Influencer visit details fetched successfully.',
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

    public function LeadOpenList(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
                ], 401);
            }
    
            // Fetch all leads created by the user that are not Won or Lost
            $leads = Lead::with('customerType')
                ->where('created_by', $user->id)
                ->whereNotIn('status', ['Won', 'Lost'])
                ->orderBy('customer_name', 'asc')
                ->get();
    
            if ($leads->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No leads found.',
                    'data' => [],
                ], 200);
            }
    
            // Format the leads
            $formattedLeads = $leads->map(function ($lead) {
                $status = $lead->status === 'Follow Up' ? 'Follow Up' : 'Opened';
    
                return [
                    'id' => $lead->id,
                    'customer_type' => [
                        'id' => $lead->customerType->id ?? null,
                        'name' => $lead->customerType->name ?? null,
                    ],
                    'customer_name' => $lead->customer_name,
                    'phone' => $lead->phone,
                    'address' => $lead->address,
                    'status' => $status,
                    'location' => $lead->location,
                    'lead_source' => $lead->lead_source,
                    'lead_score' => $lead->lead_score,
                    'created_by' => $lead->created_by,
                    'created_at' => $lead->created_at->format('d/M/Y'),
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Opened and Follow Up leads retrieved successfully!',
                'data' => $formattedLeads,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function LeadWonList(Request $request)
    {
        return $this->getLeadsByStatus('Won', 'Won leads retrieved successfully!');
    }

    public function LeadFollowupList(Request $request)
    {
        return $this->getLeadsByStatus('Follow Up', 'Follow up leads retrieved successfully!');
    }

    public function LeadLostList(Request $request)
    {
        return $this->getLeadsByStatus('Lost', 'Lost leads retrieved successfully!');
    }
    private function getLeadsByStatus($status, $message)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
                ], 401);
            }

            $leads = Lead::with('customerType')
                ->where('created_by', $user->id)
                ->where('status', $status)
                ->orderBy('customer_name', 'asc')
                ->get();

            return $this->formatLeadsResponse($leads, $message);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    private function formatLeadsResponse($leads, $message)
    {
        if ($leads->isEmpty()) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'No leads found.',
                'data' => [],
            ], 200);
        }

        $formattedLeads = $leads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'customer_type' => [
                    'id' => $lead->customerType->id ?? null,
                    'name' => $lead->customerType->name ?? null,
                ],
                'customer_name' => $lead->customer_name,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'status' => $lead->status,
                'lead_source' => $lead->lead_source,
                'lead_score' => $lead->lead_score,
                'created_by' => $lead->created_by,
                'created_at' => $lead->created_at->format('d/M/Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => $message,
            'data' => $formattedLeads,
        ], 200);
    }

    private function handleException($e)
    {
        return response()->json([
            'success' => false,
            'statusCode' => 500,
            'message' => $e->getMessage(),
        ], 500);
    }



}
