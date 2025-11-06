<?php

namespace App\Http\Controllers\Api;

use App\Models\LeaveEntry;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }
            $currentYear = now()->year;
            $leaveEntries = LeaveEntry::where('employee_id', $user->id)
                ->with('leaveType')
                ->whereYear('from_date', $currentYear)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave entries retrieved successfully!',
                'data' => $leaveEntries,
            ], 200);
        } catch (\Exception $e) {
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
            $validated = $request->validate([
                'leave_type_id' => 'required|exists:leave_types,id',
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'leave_duration' => 'required|in:half_day,full_day',
                'reason' => 'required|max:255',  
            ]);

            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $leaveEntry = LeaveEntry::create([
                'employee_id' => $user->id,
                'leave_type_id' => $validated['leave_type_id'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'leave_duration' => $validated['leave_duration'],
                'reason' => $validated['reason'],
                'status' => 'Pending', 
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave entry created successfully!',
                'data' => $leaveEntry,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function LeaveByMonth($month)
    {
        try {
            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $currentYear = now()->year;

            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid month.',
                ], 400);
            }

            $leaveEntries = LeaveEntry::where('employee_id', $user->id)
                ->whereYear('from_date', $currentYear)
                ->whereMonth('from_date', $month)
                ->with('leaveType')
                ->orderBy('from_date', 'desc')
                ->get();

            if ($leaveEntries->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'No leave entries found for the selected month.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave entries for the selected month retrieved successfully!',
                'data' => $leaveEntries,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateClaim(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'claim_image' => 'required|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $leaveEntry = LeaveEntry::where('id', $id)
                ->where('employee_id', $user->id)
                ->first();

            if (!$leaveEntry) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Leave entry not found.',
                ], 404);
            }

            $filePath = $request->file('claim_image')->store('claims', 'public');

            $leaveEntry->update([
                'claim_image' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Claim submitted successfully!',
                'data' => $leaveEntry,
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
