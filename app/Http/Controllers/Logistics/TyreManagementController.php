<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TyreManagement;
use App\Models\Vehicle;
use App\Models\TyreChangeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TyreManagementController extends Controller
{
    public function getTyreDetailsByStencilCode(Request $request)
    {
        $stencilCode = $request->input('stencil_number');

        if (!$stencilCode) {
            return response()->json([
                'success' => false,
                'successCode' => 400,
                'message' => 'Stencil number is required',
                'data' => null
            ], 400);
        }

        $tyres = TyreManagement::where('stencil_number', 'LIKE', "%{$stencilCode}%")
            ->with(['tyreCategory', 'vehicle'])
            ->whereHas('vehicle', function($q) {
                $q->where('vehicle_category_id', 2); 
            })
            ->get();

        if ($tyres->isEmpty()) {
            return response()->json([
                'success' => false,
                'successCode' => 404,
                'message' => 'No tyres found for given stencil code',
                'data' => null
            ], 404);
        }

        $data = $tyres->map(function ($tyre) {
            if ($tyre->tyre_category_id == 1) {
                return [
                    'tyre_management_id' => $tyre->id,
                    'tyre_dimension' => $tyre->tyre_dimension,
                    'stencil_number' => $tyre->stencil_number,
                    'tyre_pattern' => $tyre->tyre_pattern,
                    'tyre_brand' => $tyre->tyre_brand,
                    'tyre_category_id' => $tyre->tyre_category_id,
                ];
            } elseif ($tyre->tyre_category_id == 2) {
                return [
                    'tyre_management_id' => $tyre->id,
                    'tyre_dimension' => $tyre->tyre_dimension,
                    'tyre_pattern' => $tyre->tyre_pattern,
                    'tyre_brand' => $tyre->tyre_brand,
                    'stencil_number' => $tyre->stencil_number,
                    'last_resoled_date' => $tyre->last_resoled_date 
                        ? Carbon::parse($tyre->last_resoled_date)->format('d/m/Y') 
                        : null,
                    'resoled_count' => $tyre->resoled_count,
                    'km_run' => $tyre->km_run,
                    'tyre_category_id' => $tyre->tyre_category_id,
                ];
            }
            return null;
        })->filter();

        return response()->json([
            'success' => true,
            'successCode' => 200,
            'message' => 'Tyre details fetched successfully',
            'data' => $data
        ], 200);
    }
    public function tyreManagementList()
    {
        try {
            $requests = TyreChangeRequest::with(['vehicle', 'tyreCategory'])
                ->select('id', 'vehicle_id', 'tyre_category_id', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($req) {
                    $statusMap = [
                        0 => 'Pending',
                        1 => 'Approved',
                        2 => 'Rejected',
                        3 => 'Completed',
                    ];

                    return [
                        'id' => $req->id,
                        'vehicle_number' => $req->vehicle->vehicle_no ?? null,
                        'status' => $statusMap[$req->status] ?? 'Pending',
                        'requested_date' => $req->created_at
                            ? $req->created_at->format('d/m/Y')
                            : null,
                        'tyre_category' => $req->tyreCategory->name ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'successCode' => 200,
                'statusCode' => 200,
                'message' => 'Tyre change requests fetched successfully',
                'data' => $requests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'successCode' => 500,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function requestTyreChange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'required|exists:vehicles,id',
                'current_kilometer' => 'required|numeric',
                'reason' => 'required|string',
                'tyre_category_id' => 'required|exists:tyre_categories,id',
                'approx_durability_km' => 'required|numeric',
                'tyre_type_id' => 'required|exists:tyre_types,id',
                'axle_type_id' => 'nullable|exists:axle_types,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'successCode' => 400,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $vehicle = Vehicle::find($request->vehicle_id);

            $data = [
                'vehicle_id' => $request->vehicle_id,
                'current_kilometer' => $request->current_kilometer,
                'reason' => $request->reason,
                'tyre_management_id' => $request->tyre_management_id,
                'tyre_category_id' => $request->tyre_category_id,
                'approx_durability_km' => $request->approx_durability_km,
                'tyre_type_id' => $request->tyre_type_id,
            ];

            // If vehicle_type_id = 5, store axle_type_id also
            if ($vehicle->vehicle_type_id == 5) {
                $data['axle_type_id'] = $request->axle_type_id;
            }

            if ($request->tyre_category_id == 1) {
                // New Tyre
                $request->validate([
                    'tyre_management_id' => 'required|exists:tyre_management,id',
                ]);

                $data['tyre_management_id'] = $request->tyre_management_id;
            } 
            elseif ($request->tyre_category_id == 2) {
                // Resoled Tyre
                $request->validate([
                    'stencil_number' => 'required|string',
                    'tyre_dimension' => 'required|string',
                    'tyre_pattern' => 'required|string',
                    'tyre_brand' => 'required|string',
                ]);

                $data['stencil_number'] = $request->stencil_number;
                $data['tyre_dimension'] = $request->tyre_dimension;
                $data['tyre_pattern'] = $request->tyre_pattern;
                $data['tyre_brand'] = $request->tyre_brand;
            }

            // Save into RequestTyreChange model
            $tyreChange = TyreChangeRequest::create($data);

            return response()->json([
                'success' => true,
                'successCode' => 200,
                'message' => 'Tyre change request submitted successfully',
                'data' => $tyreChange
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'successCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function viewTyreChangeRequest($id)
    {
        try {
            $request = TyreChangeRequest::with([
                'vehicle.vehicleType',
                'tyreCategory', 
                'tyreManagement', 
                'tyreType', 
                'axleType'
            ])->find($id);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'successCode' => 404,
                    'message' => 'Tyre change request not found',
                    'data' => null
                ], 404);
            }

            // Map status number to string
            $statusMap = [
                0 => 'Pending',
                1 => 'Approved',
                2 => 'Rejected',
                3 => 'Completed',
            ];
        $tyreManagement = $request->tyre_management_id 
            ? TyreManagement::find($request->tyre_management_id) 
            : null;

            $data = [
                'id' => $request->id,
                'vehicle_number' => $request->vehicle->vehicle_no ?? null,
                'vehicle_type_id' => $request->vehicle->vehicle_type_id ?? null,
                'vehicle_type_name' => $request->vehicle->vehicleType->vehicle_type_name ?? null,
                'current_kilometer' => $request->current_kilometer,
                'reason' => $request->reason,
                'tyre_category' => $request->tyreCategory->name ?? null,
                'tyre_category_id' => $request->tyre_category_id,
                'tyre_management_id' => $request->tyre_management_id,
                'stencil_number' => $request->stencil_number,
                'tyre_dimension' => $request->tyre_dimension,
                'tyre_pattern' => $request->tyre_pattern,
                'tyre_brand' => $request->tyre_brand,
                'approx_durability' => $request->approx_durability,
                'tyre_type_id' => $request->tyre_type_id,
                'tyre_type_name' => $request->tyreType->name ?? null,
                'axle_type_id' => $request->axle_type_id,
                'axle_type_name' => $request->axleType->name ?? null,
                'status' => $statusMap[$request->status] ?? 'Pending',
                'requested_date' => $request->created_at 
                    ? $request->created_at->format('d/m/Y') 
                    : null,
                'updated_date' => $request->updated_at 
                    ? $request->updated_at->format('d/m/Y') 
                    : null,
                'last_resoled_date' => $tyreManagement && $tyreManagement->last_resoled_date
                ? $tyreManagement->last_resoled_date->format('d/m/Y')
                : null,
            'resoled_count' => $tyreManagement->resoled_count ?? 0,
            'km_run' => $tyreManagement->km_run ?? 0,
            'approx_durability_km' => $tyreManagement->approx_durability_km ?? null,
            ];
            // exit;

            return response()->json([
                'success' => true,
                'successCode' => 200,
                'message' => 'Tyre change request details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'successCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function markComplete(Request $request, $id)
    {
        try {
            $tyreRequest = TyreChangeRequest::find($id);

            if (!$tyreRequest) {
                return response()->json([
                    'success' => false,
                    'successCode' => 404,
                    'message' => 'Tyre change request not found',
                    'data' => null
                ], 404);
            }

            $tyreRequest->status = 3; 
            $tyreRequest->save();

            return response()->json([
                'success' => true,
                'successCode' => 200,
                'message' => 'Tyre change request marked as Completed successfully',
                'data' => [
                    'id' => $tyreRequest->id,
                    'status' => 'Completed'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'successCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}