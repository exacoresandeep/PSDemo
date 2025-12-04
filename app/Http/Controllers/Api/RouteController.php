<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignRoute;
use App\Models\Dealer;
use App\Models\TripRoute;
use App\Models\District;
use App\Models\EmployeeType;
use App\Models\Employee;
use App\Models\DealerTripActivity;
use App\Models\RescheduledRoute;
use App\Models\Lead;
use App\Models\DealerRouteAssignment;
use Carbon\Carbon;
use App\Helpers\ProductHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Api\AuthController;

class RouteController extends Controller
{

    public function getTodaysTrip(Request $request)
    {
        try {
            $employeeId = Auth::id(); 
            $todayDate = now()->toDateString();

            $trip = AssignRoute::with(['tripRoute', 'dealers'])
                ->where('employee_id', $employeeId)
                ->whereDate('assign_date', $todayDate)
                ->first();
            if (!$trip) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No trip assigned for today.',
                    'data' => null,
                ], 200);
            }
    
            $tripData = [
                'employee_id' => $trip->employee_id,
                'trip_route_id' => $trip->trip_route_id,
                'route_name' => $trip->tripRoute->route_name ?? null,
                'assign_date' => $trip->assign_date,
                'location_name' => $trip->tripRoute->location_name ?? null,
                'status' => $trip->status,
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Today's routes retrieved successfully.",
                'data' => $tripData, 
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function viewTripDetails(Request $request, $dealerId)
    {
        try {
            $employeeId = Auth::id();

            $tripDetails = DealerTripActivity::with(['assignRoute', 'dealer'])
                ->where('dealer_id', $dealerId)
                ->whereHas('assignRoute', function($query) use ($employeeId) {
                    $query->where('employee_id', $employeeId);
                })
                ->get();

            if ($tripDetails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No trip details found for the provided dealer and authenticated employee.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Trip details retrieved successfully.',
                'data' => $tripDetails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to retrieve trip details. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateDealerTripActivity(Request $request, $dealerId)
    {
        try {
            $request->validate([
                'assign_route_id' => 'required|exists:assign_route,id',
                'record_details' => 'required|string',
                'attachments' => 'nullable|array',
                'activity_status' => 'required|in:Pending,Completed', 
            ]);

            $dealerTripActivity = DealerTripActivity::firstOrNew([
                'assign_route_id' => $request->assign_route_id,
                'dealer_id' => $dealerId,
            ]);

            $dealerTripActivity->record_details = $request->record_details;
            $dealerTripActivity->activity_status = $request->activity_status;

            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $filePath = $file->store('Trip', 'public');
                    $attachments[] = $filePath;  
                }
                $dealerTripActivity->attachments = json_encode($attachments); 
            }

            if ($request->activity_status === 'Completed') {
                $dealerTripActivity->completed_date = now();
            }


            $dealerTripActivity->save();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer trip activity updated successfully.',
                'data' => $dealerTripActivity,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to update dealer trip activity. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addDealerToRoute(Request $request, $tripRouteId)
    {
        try {
            $request->validate([
                'dealer_code' => 'required|string|max:10|unique:dealers,dealer_code',
                'dealer_name' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'user_zone' => 'required|string|max:100',
                'pincode' => 'required|string|max:6',
                'state' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'taluk' => 'required|string|max:100',
            ]);

            $dealer = Dealer::create([
                'dealer_code' => $request->dealer_code,
                'dealer_name' => $request->dealer_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'user_zone' => $request->user_zone,
                'pincode' => $request->pincode,
                'state' => $request->state,
                'district' => $request->district,
                'taluk' => $request->taluk,
                'trip_route_id' => $tripRouteId, 
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer added successfully to the route.',
                'data' => $dealer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function todaysRouteSchedule()
    // {
    //     try {
    //         $employeeId = Auth::id();
    //         $today = Carbon::now();
    //         $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
    //         $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
    //         $todayName = $today->format('l'); 

    //         $routeMapping = [
    //             'Monday' => 'R1',
    //             'Tuesday' => 'R2',
    //             'Wednesday' => 'R3',
    //             'Thursday' => 'R4',
    //             'Friday' => 'R5',
    //             'Saturday' => 'R6',
    //         ];

    //         $scheduledCustomers = collect();
    //         $locations = [];
    //         $assignedRouteId = null;
    //         $routeName = $routeMapping[$todayName] ?? null;

    //         $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
    //             ->where('day', $todayName)
    //             ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
    //             ->first();

    //         if ($rescheduledRoute) {
    //             $routeName = $rescheduledRoute->route_name;
    //             $assignedRouteId = $rescheduledRoute->assigned_route_id;
    //             // $locations = $rescheduledRoute->locations;
    //             $locations = json_decode($rescheduledRoute->locations, true);

    //             $scheduledCustomers = collect(json_decode($rescheduledRoute->customers ?? '[]', true) ?? [])->map(function ($customer) {
    //                 return collect($customer)->merge(['scheduled' => true]);
    //             });
                
    //         } else {
    //             $trip = AssignRoute::where('employee_id', $employeeId)
    //                 ->where('route_name', $routeName)
    //                 ->first();

    //             if (!$trip) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => 'No route assigned for today.',
    //                 ], 404);
    //             }

    //             $locations = explode(', ', $trip->locations);
    //             $assignedRouteId = $trip->id;

    //             $dealers = Dealer::where('assigned_route_id', $assignedRouteId)
    //                 ->get(['id', 'dealer_name as customer_name', 'location'])
    //                 ->map(function ($dealer) {
    //                     return array_merge($dealer->toArray(), ['customer_type' => 'Dealer', 'scheduled' => false]);
    //                 });

    //             $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
    //                 ->where('leads.assigned_route_id', $assignedRouteId)
    //                 ->where(function ($query) {
    //                     $query->whereIn('leads.customer_type', [1, 2])
    //                         ->orWhere(function ($q) {
    //                             $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
    //                         });
    //                 })
    //                 ->get([
    //                     'leads.id',
    //                     'leads.customer_name',
    //                     'leads.location',
    //                     'customer_types.name as customer_type'
    //                 ])
    //                 ->map(function ($lead) {
    //                     return array_merge($lead->toArray(), ['scheduled' => false]);
    //                 });

    //             $scheduledCustomers = collect($dealers)->merge($leads);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Today\'s route schedule fetched successfully.',
    //             'data' => [
    //                 'day' => $todayName,
    //                 'route_name' => $routeName,
    //                 'locations' => $locations,
    //                 'customers' => $scheduledCustomers->values(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => 'Error fetching today\'s route schedule.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function todaysRouteSchedule()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
            $todayName = $today->format('l');

            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];

            $scheduledCustomers = collect();
            $locations = [];
            $assignedRouteId = null;
            $routeName = $routeMapping[$todayName] ?? null;

            $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                ->where('day', $todayName)
                ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->first();

            if ($rescheduledRoute) {
                $routeName = $rescheduledRoute->route_name;
                $assignedRouteId = $rescheduledRoute->assigned_route_id;
                $locations = json_decode($rescheduledRoute->locations, true);

                $scheduledCustomers = collect(json_decode($rescheduledRoute->customers ?? '[]', true) ?? [])->map(function ($customer) {
                    return collect($customer)->merge(['scheduled' => true]);
                });

            } else {
                $trip = AssignRoute::where('employee_id', $employeeId)
                    ->where('route_name', $routeName)
                    ->first();

                if (!$trip) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => 'No route assigned for today.',
                    ], 404);
                }

                $locations = explode(', ', $trip->locations);
                $assignedRouteId = $trip->id;

                $dealers = Dealer::join('dealer_route_assignments', 'dealer_route_assignments.dealer_id', '=', 'dealers.id')
                    ->where('dealer_route_assignments.assign_route_id', $assignedRouteId)
                    ->select('dealers.id', 'dealers.dealer_name as customer_name', 'dealers.location')
                    ->get()
                    ->map(function ($dealer) {
                        return array_merge($dealer->toArray(), [
                            'customer_type' => 'Dealer',
                            'scheduled' => false
                        ]);
                    });

             
                $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                    ->where('leads.assigned_route_id', $assignedRouteId)
                    ->where(function ($query) {
                        $query->whereIn('leads.customer_type', [1, 2])
                            ->orWhere(function ($q) {
                                $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
                            });
                    })
                    ->get([
                        'leads.id',
                        'leads.customer_name',
                        'leads.location',
                        'customer_types.name as customer_type'
                    ])
                    ->map(function ($lead) {
                        return array_merge($lead->toArray(), ['scheduled' => false]);
                    });

                $scheduledCustomers = collect($dealers)->merge($leads);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Today's route schedule fetched successfully.",
                'data' => [
                    'day' => $todayName,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $scheduledCustomers->values(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error fetching today\'s route schedule.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // public function currentWeekRoutes()
    // {
    //     try {
    //         $employeeId = Auth::id();
    //         $today = Carbon::now();
    //         $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
    //         $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
    
    //         $routeMapping = [
    //             'Monday' => 'R1',
    //             'Tuesday' => 'R2',
    //             'Wednesday' => 'R3',
    //             'Thursday' => 'R4',
    //             'Friday' => 'R5',
    //             'Saturday' => 'R6',
    //         ];
    
    //         $weeklyRoutes = [];
    
    //         foreach ($routeMapping as $day => $defaultRouteName) {
    //             $customers = collect();
    //             $locations = [];
    //             $routeName = $defaultRouteName;
    //             $assignedRouteId = null;
    
    //             $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
    //                 ->where('day', $day)
    //                 ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
    //                 ->first();
    
    //             if ($rescheduledRoute) {
    //                 $routeName = $rescheduledRoute->route_name;
    //                 $assignedRouteId = $rescheduledRoute->assigned_route_id;
    //                 $locations = json_decode($rescheduledRoute->locations, true) ?? [];
    
    //                 $rescheduledCustomers = collect(
    //                     is_string($rescheduledRoute->customers) 
    //                         ? json_decode($rescheduledRoute->customers, true) 
    //                         : (is_array($rescheduledRoute->customers) ? $rescheduledRoute->customers : [])
    //                 )->map(function ($customer) {
    //                     return (array) $customer + ['scheduled' => true]; 
    //                 });
                    
    
    //             } else {
    //                 $trip = AssignRoute::where('employee_id', $employeeId)
    //                     ->where('route_name', $routeName)
    //                     ->first();
    
    //                 if (!$trip) {
    //                     continue;
    //                 }
    
    //                 $locations = explode(', ', $trip->locations);
    //                 $assignedRouteId = $trip->id;
    //                 $rescheduledCustomers = collect([]);
    //             }
    
    //             $dealers = collect(Dealer::where('assigned_route_id', $assignedRouteId)
    //                 ->get(['id', 'dealer_name as customer_name', 'location'])
    //                 ->map(function ($dealer) {
    //                     return array_merge($dealer->toArray(), ['customer_type' => 'Dealer', 'scheduled' => false]);
    //                 }));

    //             $leads = collect(Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
    //                 ->where('leads.assigned_route_id', $assignedRouteId)
    //                 ->where(function ($query) {
    //                     $query->whereIn('leads.customer_type', [1, 2])
    //                         ->orWhere(function ($q) {
    //                             $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
    //                         });
    //                 })
    //                 ->get([
    //                     'leads.id',
    //                     'leads.customer_name',
    //                     'leads.location',
    //                     'customer_types.name as customer_type'
    //                 ])
    //                 ->map(function ($lead) {
    //                     return array_merge($lead->toArray(), ['scheduled' => false]);
    //                 }));

    //             $rescheduledCustomers = collect($rescheduledCustomers ?? []);

    //             $customers = $dealers->merge($leads)->map(function ($customer) use ($rescheduledCustomers) {
    //                 $rescheduled = collect($rescheduledCustomers)->firstWhere('id', (int) $customer['id']);

                
    //                 if ($rescheduled) {
    //                     return array_merge($customer, ['scheduled' => true]);
    //                 }
    //                 return $customer;
    //             });
                
    
    //             $dayIndex = array_search($day, array_keys($routeMapping));
    //             $date = $weekStart->copy()->addDays($dayIndex)->format('d/m/y');
    
    //             $weeklyRoutes[] = [
    //                 'day' => $day,
    //                 'date' => $date,
    //                 'assigned_route_id' => $assignedRouteId,
    //                 'route_name' => $routeName,
    //                 'locations' => $locations,
    //                 'customers' => $customers->values(),
    //             ];
    //         }
	//     $routes=RescheduledRoute::where('employee_id', $employeeId)->get();
    //         $authController = new AuthController();
    //         foreach($routes as $item){
    //             //......................notification..............
    //             $authController->changeNotificationStatus('assigned_routes', $item->id,"opened");
    //         }
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Weekly routes fetched successfully.',
    //             'data' => $weeklyRoutes,
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => 'Error fetching weekly routes.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function currentWeekRoutes()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);

            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];

            $weeklyRoutes = [];

            foreach ($routeMapping as $day => $defaultRouteName) {
                $customers = collect();
                $locations = [];
                $routeName = $defaultRouteName;
                $assignedRouteId = null;

                $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                    ->where('day', $day)
                    ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                    ->first();

                if ($rescheduledRoute) {
                    $routeName = $rescheduledRoute->route_name;
                    $assignedRouteId = $rescheduledRoute->assigned_route_id;
                    $locations = json_decode($rescheduledRoute->locations, true) ?? [];

                    $rescheduledCustomers = collect(
                        is_string($rescheduledRoute->customers)
                            ? json_decode($rescheduledRoute->customers, true)
                            : (is_array($rescheduledRoute->customers) ? $rescheduledRoute->customers : [])
                    )->map(function ($customer) {
                        return (array) $customer + ['scheduled' => true];
                    });
                } else {
                    $trip = AssignRoute::where('employee_id', $employeeId)
                        ->where('route_name', $routeName)
                        ->first();

                    if (!$trip) {
                        continue;
                    }

                    $locations = explode(', ', $trip->locations);
                    $assignedRouteId = $trip->id;
                    $rescheduledCustomers = collect([]);
                }

                $dealers = Dealer::join('dealer_route_assignments', 'dealer_route_assignments.dealer_id', '=', 'dealers.id')
                    ->where('dealer_route_assignments.assign_route_id', $assignedRouteId)
                    ->select('dealers.id', 'dealers.dealer_name as customer_name', 'dealers.location')
                    ->get()
                    ->map(function ($dealer) {
                        return array_merge($dealer->toArray(), [
                            'customer_type' => 'Dealer',
                            'scheduled' => false,
                        ]);
                    });

            
                $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                    ->where('leads.assigned_route_id', $assignedRouteId)
                    ->where(function ($query) {
                        $query->whereIn('leads.customer_type', [1, 2])
                            ->orWhere(function ($q) {
                                $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
                            });
                    })
                    ->get([
                        'leads.id',
                        'leads.customer_name',
                        'leads.location',
                        'customer_types.name as customer_type'
                    ])
                    ->map(function ($lead) {
                        return array_merge($lead->toArray(), ['scheduled' => false]);
                    });

                $rescheduledCustomers = collect($rescheduledCustomers ?? []);

                $customers = $dealers->merge($leads)->map(function ($customer) use ($rescheduledCustomers) {
                    $rescheduled = collect($rescheduledCustomers)->firstWhere('id', (int) $customer['id']);
                    if ($rescheduled) {
                        return array_merge($customer, ['scheduled' => true]);
                    }
                    return $customer;
                });

                $dayIndex = array_search($day, array_keys($routeMapping));
                $date = $weekStart->copy()->addDays($dayIndex)->format('d/m/y');

                $weeklyRoutes[] = [
                    'day' => $day,
                    'date' => $date,
                    'assigned_route_id' => $assignedRouteId,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $customers->values(),
                ];
            }

            $routes = RescheduledRoute::where('employee_id', $employeeId)->get();
            $authController = new AuthController();
            foreach ($routes as $item) {
                $authController->changeNotificationStatus('assigned_routes', $item->id, "opened");
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Weekly routes fetched successfully.',
                'data' => $weeklyRoutes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error fetching weekly routes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function routeReschedule(Request $request)
    {
        if (!is_array($request->input('routes'))) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Invalid data format. Expected an array of routes.',
                'data' => null
            ], 400);
        }
    
        $employeeId = $request->input('employee_id');
        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Employee ID is required.',
                'data' => null
            ], 400);
        }
    
        $rescheduledRoutes = [];
        $alreadyRescheduledRoutes = [];
    
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    
        foreach ($request->input('routes') as $route) {
            $validator = Validator::make($route, [
                'day' => 'required|string',
                'date' => 'required|string',
                'assigned_route_id' => 'required|integer',
                'route_name' => 'required|string',
                'locations' => 'required|array',
                'customers' => 'nullable|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => 'Validation error',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }
    
            $assignDate = Carbon::createFromFormat('d/m/y', $route['date'])->format('Y-m-d');
    
            $alreadyRescheduled = RescheduledRoute::where('employee_id', $employeeId)
                ->where('assigned_route_id', $route['assigned_route_id'])
                ->whereBetween('assign_date', [$startOfWeek, $endOfWeek])
                ->exists();
    
            if ($alreadyRescheduled) {
                $alreadyRescheduledRoutes[] = [
                    'route_name' => $route['route_name'],
                    'day' => $route['day'],
                    'date' => $assignDate
                ];
                continue; 
            }
    
            $reschedule = RescheduledRoute::create([
                'employee_id' => $employeeId,  
                'day' => $route['day'],
                'assign_date' => $assignDate,
                'assigned_route_id' => $route['assigned_route_id'],
                'route_name' => $route['route_name'],
                'locations' => json_encode($route['locations']), 
                'customers' => json_encode($route['customers'] ?? []),
            ]);
    
            $rescheduledRoutes[] = $reschedule;
        }
    
        if (!empty($alreadyRescheduledRoutes)) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'This Week Already Rescheduled',
                'data' => [
                    'already_rescheduled' => $alreadyRescheduledRoutes
                ]
            ], 400);
        }
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Routes rescheduled successfully.',
        ], 200);
    }
    

   
    public function changeRouteStatus(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer',
                'customer_type' => 'required|string',
            ]);

            $customerId = (int) $request->customer_id;
            $customerType = $request->customer_type;

            $isDealer = ($customerType === 'Dealer');

            $rescheduledRoute = RescheduledRoute::whereRaw("JSON_CONTAINS(customers, ?)", [json_encode(['id' => $customerId])])->first();


            if (!$rescheduledRoute) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Customer not found in rescheduled routes.',
                ], 404);
            }

            $customers = collect(json_decode($rescheduledRoute->customers, true)); 

            $updatedCustomers = $customers->map(function ($customer) use ($customerId, $isDealer) {
                if (isset($customer['id']) && $customer['id'] == $customerId &&
                    ($isDealer ? ($customer['customer_type'] === 'Dealer') : ($customer['customer_type'] !== 'Dealer'))) {
                    $customer['status'] = 'Completed';
                    $customer['visited_at'] = Carbon::now()->toDateTimeString();
                }
                return $customer;
            });

            $rescheduledRoute->update(['customers' => json_encode($updatedCustomers)]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Customer visit status updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error updating customer status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllRoutesByDistrict($district_id)
    {
        $routes = TripRoute::where('district_id', $district_id)->get();
        return response()->json($routes);
    }
 
    // public function getRoutesByDistrict($district_id)
    // {
    //     try {
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated.",
    //             ], 401);
    //         }

    //         $query = AssignRoute::where('district_id', $district_id)
    //             ->select('id as assign_route_id', 'route_name', 'locations');

    //         if (in_array($employee->employee_type_id, [1, 2])) {
    //             $query->where('employee_id', $employee->id);
    //         }

    //         $routes = $query->get();

    //         if ($routes->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'No routes found for the given district.',
    //                 'data' => [],
    //             ], 400);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Routes fetched successfully',
    //             'data' => $routes,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function getRoutesByDistrict($district_id = null)
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

            $query = AssignRoute::where('employee_id', $employee->id)
                ->select('id as assign_route_id', 'route_name', 'locations');

            // if ($district_id) {
            //     $query->whereHas('dealerRouteAssignments.dealer', function ($q) use ($district_id) {
            //         $q->where('district_id', $district_id);
            //     });
            // }

            $routes = $query->get();

            if ($routes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'No routes found for the selected district or employee.',
                    'data' => [],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Routes fetched successfully.',
                'data' => $routes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error fetching routes: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function getRoutesReport(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $routes = RescheduledRoute::whereMonth('assign_date', $month)
            ->whereYear('assign_date', $year)
            ->when($employeeId, function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->get();

        $formattedRoutes = $routes->map(function ($route) {
            $customers = json_decode($route->customers, true) ?? [];

            $status = collect($customers)->contains(function ($customer) {
                return isset($customer['scheduled']) && $customer['scheduled'] === true &&
                    isset($customer['status']) && $customer['status'] === 'Pending';
            }) ? 'Pending' : 'Completed';

            return [
                'id' => $route->id,
                'route_name' => $route->route_name,
                'locations' => json_decode($route->locations, true) ?? [],
                'day' => $route->day,
                'assign_date' => Carbon::parse($route->assign_date)->format('d/m/Y'),
                'status' => $status,
            ];
        });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => $formattedRoutes,
        ], 200);
    }
    public function getRouteDetails(Request $request, $routeId)
    {
        try {
            $route = RescheduledRoute::findOrFail($routeId);
    
            $customers = json_decode($route->customers, true) ?? [];
    
            $routeSummary = collect($customers)->map(function ($customer) {
                return [
                    'customer_name' => $customer['customer_name'] ?? null,
                    'location' => $customer['location'] ?? null,
                    'customer_type' => $customer['customer_type'] ?? null,
                    'status' => $customer['status'] ?? null,
                    'completed_at' => ($customer['status'] === 'Completed' && isset($customer['visited_at']))
                        ? Carbon::parse($customer['visited_at'])->format('d/m/Y H:i:s')
                        : null,
                ];
            });
    
            // Format response
            $response = [
                'day' => $route->day,
                'assign_date' => Carbon::parse($route->assign_date)->format('d/m/Y'),
                'month' => Carbon::parse($route->assign_date)->format('F'),
                'year' => Carbon::parse($route->assign_date)->format('Y'),
                'route_summary' => $routeSummary,
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
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
    

    public function routeIndex()
    {
        $districts = District::all(); 

        return view('sales.route.route-index', compact('districts'));
    }

    public function routeList(Request $request)
    {
        $routes = TripRoute::with('district')->orderBy('id', 'desc');

        return DataTables::of($routes)
            ->addIndexColumn()
            ->addColumn('district_name', function ($route) {
                return $route->district ? $route->district->name : 'N/A'; // Ensure district exists
            })
            ->editColumn('locations', function ($route) {
                return is_array($route->locations) ? implode(', ', $route->locations) : '';
            })
            ->addColumn('action', function ($route) {
                return '
                    <button class="btn btn-sm btn-warning editRoute" data-id="'.$route->id.'" title="Edit">
                    <i class="fa fa-edit"></i>
                </button>
                    <button class="btn btn-sm btn-danger deleteRoute" data-id="'.$route->id.'" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function routeStore(Request $request)
    {
        $validatedData = $request->validate([
            'district' => 'required|exists:districts,id',
            'locations' => 'required|array',
        ]);
        $existingRoute = TripRoute::where('district_id', $validatedData['district'])->first();

        if ($existingRoute) {
            return response()->json(['message' => 'A route already exists for this district!'], 409);
        }

        $route = TripRoute::create([
            'district_id' => $validatedData['district'],
            'locations' => array_values($validatedData['locations']), // Store as JSON
        ]);

        return response()->json(['message' => 'Route created successfully!', 'route' => $route]);
    }

    public function editRoute($route_id)
    {
        $route = TripRoute::findOrFail($route_id);
        $districts = District::all();
        return response()->json([
            'route' => [
                'id' => $route->id,
                'district_id' => $route->district_id,
                'locations' => $route->locations ?? [],
            ],
            'districts' => $districts
        ]);
    }

    public function updateRoute(Request $request, $route_id)
    {
        $validatedData = $request->validate([
            'district' => 'required|exists:districts,id',
            'locations' => 'required|array',
        ]);
        $route = TripRoute::findOrFail($route_id);

        $existingRoute = TripRoute::where('district_id', $validatedData['district'])
            ->where('id', '!=', $route_id)
            ->first();

        if ($existingRoute) {
            return response()->json(['message' => 'A route already exists for this district!'], 409);
        }

        $route->update([
            'district_id' => $validatedData['district'],
            'locations' => array_values($validatedData['locations']), // Store as JSON
        ]);

        return response()->json(['message' => 'Route updated successfully!', 'route' => $route]);
    }

    public function deleteRoute($route_id)
    {
        try {
            $route = TripRoute::findOrFail($route_id);
            $route->delete();

            return response()->json(['success' => true, 'message' => 'Route deleted successfully!']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Route not found!'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete route!'], 500);
        }
    }
    public function assignedIndex()
    {
        return view('sales.route.index');
    }

    public function assignedList()
    {
        $productId = ProductHelper::getSelectedProductId(); 
        $routes = AssignRoute::with(['employee', 'dealers'])
                    ->whereHas('employee', function($q) use ($productId) {
                    $q->whereRaw("JSON_CONTAINS(products, ?)", ['["' . $productId . '"]']);
                })
            ->get()
            ->groupBy('employee_id'); 
        $formattedRoutes = $routes->map(function ($routeGroup) {
            $firstRoute = $routeGroup->first(); 
            
            return [
                'DT_RowIndex'   => null, 
                'employee_type' => $this->getEmployeeType($firstRoute->employee->employee_type_id ?? null),
                'employee'      => $firstRoute->employee->name ?? 'N/A',

                'route_name'    => $routeGroup->map(function ($route) {
                    $dealerNames = $route->dealers->pluck('dealer_name')->implode(', ');
                    return "<strong>{$route->route_name}</strong> - {$route->locations}" 
                        . ($dealerNames ? "<br><small>Dealers: {$dealerNames}</small>" : '');
                })->implode('<hr>'),

                'action'        => '<button class="btn btn-sm btn-warning editRoute" data-id="'.$firstRoute->id.'">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger deleteRoute" data-id="'.$firstRoute->id.'">
                                        <i class="fa fa-trash"></i>
                                    </button>'
            ];
        })->values(); 

        return DataTables::of($formattedRoutes)
            ->addIndexColumn()
            ->rawColumns(['route_name', 'action']) 
            ->make(true);
    }


    private function getEmployeeType($employee_type_id)
    {
        $employeeTypes = [
            1 => 'Sales Executive',
            2 => 'Area Sales Officer',
            3 => 'District Sales Manager',
            4 => 'Regional Sales Manager',
            5 => 'Sales Manager'
        ];
        return $employeeTypes[$employee_type_id] ?? 'Unknown';
    }

    public function storeAssignedRoute(Request $request)
    {
        $request->validate([
            'employee_type_id' => 'required|integer',
            'employee_id' => 'required|exists:employees,id',
            'routes' => 'required|array|max:6',
            'routes.*.route_name' => 'required|string|max:255',
            'routes.*.locations' => 'nullable|array',
            'routes.*.locations.*' => 'nullable|string',
            'routes.*.dealers' => 'nullable|array',
            'routes.*.dealers.*' => 'nullable|integer|exists:dealers,id',
        ]);

        $employeeTypeId = $request->employee_type_id;
        $employeeId = $request->employee_id;

        // Check for duplicate assignment (same employee + type)
        $existing = AssignRoute::where('employee_type_id', $employeeTypeId)
            ->where('employee_id', $employeeId)
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'This employee already has assigned routes!'], 422);
        }

        foreach ($request->routes as $route) {
            $routeName = $route['route_name'];
            $locations = isset($route['locations']) && is_array($route['locations'])
                ? implode(', ', $route['locations'])
                : '';

            // Create assigned route entry
            $assignedRoute = AssignRoute::create([
                'employee_type_id' => $employeeTypeId,
                'employee_id' => $employeeId,
                'route_name' => $routeName,
                'locations' => $locations,
            ]);

            // Save linked dealers for that route (if provided)
            if (isset($route['dealers']) && is_array($route['dealers'])) {
                foreach ($route['dealers'] as $dealerId) {
                    DealerRouteAssignment::create([
                        'assign_route_id' => $assignedRoute->id,
                        'dealer_id' => $dealerId,
                        'location' => null, // optional, can fill if you store location per dealer
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Assigned routes stored successfully!']);
    }

    public function editAssignedRoute($id)
    {
        $route = AssignRoute::findOrFail($id);

        // Get all assigned routes for this employee
        $assignedRoutes = AssignRoute::where('employee_type_id', $route->employee_type_id)
            ->where('employee_id', $route->employee_id)
            ->with('dealerAssignments')
            ->get();

        $formattedData = [
            'id' => $route->id,
            'employee_type_id' => $route->employee_type_id,
            'employee_id' => $route->employee_id,
            'routes' => $assignedRoutes->map(function ($r) {
                return [
                    'route_name' => $r->route_name,
                    'locations' => !empty($r->locations) ? explode(', ', $r->locations) : [],
                    'dealers' => $r->dealerAssignments->pluck('dealer_id')->toArray(),
                ];
            }),
        ];

        return response()->json(['success' => true, 'data' => $formattedData]);
    }

    public function updateAssignedRoute(Request $request, $id)
    {
        $request->validate([
            'employee_type_id' => 'required|integer',
            'employee_id' => 'required|exists:employees,id',
            'routes' => 'required|array|max:6',
            'routes.*.route_name' => 'required|string|max:255',
            'routes.*.locations' => 'nullable|array',
            'routes.*.locations.*' => 'nullable|string',
            'routes.*.dealers' => 'nullable|array',
            'routes.*.dealers.*' => 'nullable|integer|exists:dealers,id',
        ]);

        $employeeTypeId = $request->employee_type_id;
        $employeeId = $request->employee_id;

        // Find reference route
        $existingRoute = AssignRoute::findOrFail($id);

        // Delete all current routes & dealer assignments for this employee
        $existingRoutes = AssignRoute::where('employee_type_id', $existingRoute->employee_type_id)
            ->where('employee_id', $existingRoute->employee_id)
            ->get();

        foreach ($existingRoutes as $route) {
            DealerRouteAssignment::where('assign_route_id', $route->id)->delete();
            $route->delete();
        }

        // Recreate updated routes
        foreach ($request->routes as $route) {
            $routeName = $route['route_name'];
            $locations = isset($route['locations']) && is_array($route['locations'])
                ? implode(', ', $route['locations'])
                : '';

            $assignedRoute = AssignRoute::create([
                'employee_type_id' => $employeeTypeId,
                'employee_id' => $employeeId,
                'route_name' => $routeName,
                'locations' => $locations,
            ]);

            // Reinsert dealer assignments
            if (isset($route['dealers']) && is_array($route['dealers'])) {
                foreach ($route['dealers'] as $dealerId) {
                    DealerRouteAssignment::create([
                        'assign_route_id' => $assignedRoute->id,
                        'dealer_id' => $dealerId,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Assigned routes updated successfully!']);
    }

    // public function deleteAssignedRoute($id)
    // {
    //     $route = AssignRoute::findOrFail($id);

    //     DealerRouteAssignment::where('assign_route_id', $route->id)->delete();

    //     $route->delete();

    //     return response()->json(['message' => 'Assigned route deleted successfully!']);
    // }
    public function deleteAssignedRoute($id)
    {
        $route = AssignRoute::findOrFail($id);

        // Get all routes for this employee
        $employeeRoutes = AssignRoute::where('employee_type_id', $route->employee_type_id)
            ->where('employee_id', $route->employee_id)
            ->get();

        foreach ($employeeRoutes as $r) {
            DealerRouteAssignment::where('assign_route_id', $r->id)->delete();
            $r->delete();
        }

        return response()->json(['message' => 'All assigned routes for this employee deleted successfully!']);
    }

    public function getDistricts()
    {
        return response()->json(District::select('id', 'name')->get());
    }

    public function getEmployees(Request $request)
    {
        $productId = ProductHelper::getSelectedProductId(); 
        $employees = Employee::where('district_id', $request->district_id)
            ->where('employee_type_id', $request->employee_type_id)
             ->whereRaw("JSON_CONTAINS(products, ?)", ['["' . $productId . '"]'])
            ->select('id', 'name')
            ->get();

        return response()->json($employees);
    }
    public function getLocations(Request $request)
    {
        $routes = TripRoute::where('district_id', $request->district_id)->get();

        $locations = $routes->pluck('locations')->flatten()->unique()->values();

        return response()->json($locations);
    }

    public function getEmployeesByType(Request $request)
    {
        return Employee::where('employee_type_id', $request->employee_type_id)
            ->select('id', 'name')
            ->get();
    }

    public function getAllLocations()
    {
        return TripRoute::pluck('locations')->flatten()->unique()->values();
    }

    public function getDealersByLocations(Request $request)
    {
        return Dealer::whereIn('location', $request->locations)
            ->select('id', 'dealer_name')
            ->get();
    }



}
