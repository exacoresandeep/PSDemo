<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Assistance;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AssistanceController extends Controller
{
    public function getAssistanceList($tripId)
    {
        $driver = Auth::user();
        $trip = Trip::where('driver_id', $driver->id)->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to you.',
                'data' => []
            ], 404);
        }

        $assistances = Assistance::with('type')
            ->where('trip_id', $tripId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'assistance_id'   => $a->id,
                    'assistance_type' => $a->type->name,
                    'support_date'    => Carbon::parse($a->support_date)->format('d/m/Y'),
                    'support_time'    => Carbon::parse($a->support_date)->format('h:i A'),
                    'close_date'      => $a->close_date ? Carbon::parse($a->close_date)->format('d/m/Y') : null,
                    'close_time'      => $a->close_date ? Carbon::parse($a->close_date)->format('h:i A') : null,
                ];
            });

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Assistance list fetched successfully.',
            'data' => $assistances
        ]);
    }

    public function addAssistance(Request $request, $tripId)
    {
        $request->validate([
            'assistance_type_id' => 'required|exists:assistance_types,id',
            'remarks' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $driver = Auth::user();
        $trip = Trip::where('driver_id', $driver->id)->find($tripId);

        if (!$trip) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Trip not found or not assigned to you.',
                'data' => []
            ], 404);
        }

        $assistance = Assistance::create([
            'trip_id' => $tripId,
            'assistance_type_id' => $request->assistance_type_id,
            'remarks' => $request->remarks,
            'support_date' => now(),
            'lat' => $request->latitude,
            'lon' => $request->longitude,
        ]);

        if ($request->has('images')) {
            foreach ($request->images as $imagePath) {
                $assistance->images()->create([
                    'image_path' => $imagePath
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'statusCode' => 201,
            'message' => 'Assistance request submitted successfully.',
            'data' => $assistance->load('images')
        ], 201);
    }

    public function viewAssistanceDetails($assistanceId)
    {
        $assistance = Assistance::with(['type', 'maintenanceReports.images'])->find($assistanceId);

        if (!$assistance) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'Assistance not found.',
                'data' => []
            ], 404);
        }

        $maintenance = $assistance->maintenanceReports->first();

        $maintenanceData = null;
        if ($maintenance) {
            $maintenanceData = [
                'maintenance_type' => $maintenance->maintenance_type,
                'remarks' => $maintenance->remarks,
                'employee_name' => $maintenance->employee_name,
                'phone_number' => $maintenance->phone_number,
                'status' => $maintenance->status,
                'images' => $maintenance->images->pluck('image')
            ];
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Assistance details fetched successfully.',
            'data' => [
                'assistance_report' => [
                    'assistance_type' => $assistance->type->name,
                    'remarks' => $assistance->remarks,
                    'image' => $assistance->image
                ],
                'maintenance_report' => $maintenanceData
            ]
        ]);
    }
}
