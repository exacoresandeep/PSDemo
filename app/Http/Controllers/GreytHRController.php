<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GreytHRService;
use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GreytHRController extends Controller
{
    protected $greytHRService;

    public function __construct(GreytHRService $greytHRService)
    {
        $this->greytHRService = $greytHRService;
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code'    => 'required|string',
                'password'         => 'required|string',
                'employee_type_id' => 'required|integer',
            ]);

            // Check Local DB First
            $employee = Employee::where('employee_code', $validated['employee_code'])
                ->where('employee_type_id', $validated['employee_type_id'])
                ->first();

            if ($employee) {
                if (!Hash::check($validated['password'], $employee->password)) {
                    return response()->json([
                        'success'    => false,
                        'statusCode' => 400,
                        'message'    => 'Invalid credentials',
                    ], 400);
                }

                $token = $employee->createToken('API Token')->plainTextToken;

                return response()->json([
                    'success'    => true,
                    'statusCode' => 200,
                    'message'    => 'Login successful',
                    'data'       => [
                        'employee'      => $employee,
                        'employee_type' => [
                            'id'        => $employee->employee_type_id,
                            'type_name' => $employee->employeeType->type_name ?? 'Unknown',
                        ],
                        'token' => $token,
                        'status' => 'active',
                    ],
                ], 200);
            }

            // Authenticate via GreytHR if not found locally
            $accessToken = $this->greytHRService->getAccessToken();
           
            if (!$accessToken) {
                return response()->json([
                    'success'    => false,
                    'statusCode' => 500,
                    'message'    => 'Failed to get access token from GreytHR',
                ], 500);
            }

            $greythrData = $this->greytHRService->getEmployeeDetails($accessToken, $validated['employee_code']);
            if (!$greythrData || !isset($greythrData['employees'][0])) {
                return response()->json([
                    'success'    => false,
                    'statusCode' => 404,
                    'message'    => 'Employee not found in GreytHR',
                ], 404);
            }

            $greythrEmployee = $greythrData['employees'][0];

            // Store Employee in Local DB
            $newEmployee = Employee::create([
                'employee_code'    => $greythrEmployee['employeeNo'],
                'name'             => $greythrEmployee['name'],
                'email'            => $greythrEmployee['email'] ?? null,
                'password'         => Hash::make($validated['password']),
                'designation'      => $greythrEmployee['designation'] ?? null,
                'phone'            => $greythrEmployee['phone'] ?? null,
                'address'          => $greythrEmployee['address'] ?? null,
                'photo'            => $greythrEmployee['photo'] ?? null,
                'emergency_contact'=> $greythrEmployee['emergencyContact'] ?? null,
                'employee_type_id' => $validated['employee_type_id'],
                'status'           => 1,
            ]);

            // Generate Token
            $token = $newEmployee->createToken('API Token')->plainTextToken;

            return response()->json([
                'success'    => true,
                'statusCode' => 200,
                'message'    => 'Login successful via GreytHR',
                'data'       => [
                    'employee'      => $newEmployee,
                    'employee_type' => [
                        'id'        => $validated['employee_type_id'],
                        'type_name' => 'Unknown',
                    ],
                    'token'  => $token,
                    'status' => 'active',
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("GreytHR Login Error: " . $e->getMessage());

            return response()->json([
                'success'    => false,
                'statusCode' => 500,
                'message'    => 'Internal Server Error',
                'error'      => $e->getMessage(),
            ], 500);
        }
    }
 
    // public function getAccessToken()
    // {
    //     $apiUser = 'demo1';
    //     $apiKey = 'b223954f-bdc9-406f-b87f-62f158d9734e';

    //     $response = Http::asForm()->withHeaders([
    //         'Authorization' => 'Basic ' . base64_encode("$apiUser:$apiKey"),
    //         'Content-Type'  => 'application/x-www-form-urlencoded',
    //     ])->withOptions([
    //         'verify' => false, 
    //     ])->post('https://tousifapisso.greythr.com/uas/v1/oauth2/client-token', [
    //         'grant_type' => 'client_credentials',
    //     ]);
    //     // dd($response->json());

    //     if ($response->failed()) {
    //         Log::error("GreytHR Token API Error:", [
    //             'status' => $response->status(),
    //             'body' => $response->body()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => $response->status(),
    //             'message' => 'Failed to get access token from GreytHR',
    //             'error_details' => $response->body(),
    //         ], $response->status());
    //     }

    //     return $response->json();
    // }

    // public function login(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'employee_code' => 'required|string',
    //             'password' => 'required|string',
    //             'employee_type_id' => 'required|integer',
    //         ]);

    //         $employee = Employee::join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
    //             ->where('employee_code', $validated['employee_code'])
    //             ->where('employees.employee_type_id', $validated['employee_type_id'])
    //             ->select('employees.*', 'employee_types.id as type_id', 'employee_types.type_name')
    //             ->first();

    //         if ($employee) {
    //             if (!Hash::check($validated['password'], $employee->password)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 400,
    //                     'message' => 'Invalid credentials',
    //                 ], 400);
    //             }

    //             $token = $employee->createToken('API Token')->plainTextToken;

    //             return response()->json([
    //                 'success' => true,
    //                 'statusCode' => 200,
    //                 'message' => 'Login successful',
    //                 'data' => [
    //                     'employee' => [
    //                         'id' => $employee->id,
    //                         'employee_code' => $employee->employee_code,
    //                         'name' => $employee->name,
    //                         'designation' => $employee->designation,
    //                         'email' => $employee->email,
    //                         'phone' => $employee->phone,
    //                         'address' => $employee->address,
    //                         'photo' => $employee->photo,
    //                         'emergency_contact' => $employee->emergency_contact,
    //                     ],
    //                     'employee_type' => [
    //                         'id' => $employee->type_id,
    //                         'type_name' => $employee->type_name,
    //                     ],
    //                     'token' => $token,
    //                     'status' => 'active',
    //                 ],
    //             ], 200);
    //         }

    //         // If employee does NOT exist in local DB, authenticate via GreytHR
    //         $tokenResponse = $this->getAccessToken();
    //         if (!is_array($tokenResponse) || !isset($tokenResponse['access_token'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 500,
    //                 'message' => 'Failed to get access token from GreytHR',
    //             ], 500);
    //         }

    //         $token = $tokenResponse['access_token'];
    //         $greythrResponse = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $token, 
    //             'x-greythr-domain' => 'tousifapisso.greythr.com',// ✅ FIXED: Use Bearer token
    //             'Content-Type'  => 'application/json',
    //         ])->withOptions([
    //             'verify' => false, 
    //         ])->get('https://api.greythr.com/employee/v2/employees');
    //         // Handle failed GreytHR login
    //         if ($greythrResponse->failed()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => $greythrResponse->status(),
    //                 'message' => 'Failed to authenticate with GreytHR',
    //                 'errorDetails' => $greythrResponse->body(),
    //             ], 400);
    //         }

    //         // Fetch employee details from GreytHR
    //         $greythrData = $greythrResponse->json();
    //         dd($greythrData);
    //         if (!isset($greythrData['employeeNo'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Employee data not found in GreytHR',
    //             ], 404);
    //         }

    //         // Store Employee Data in Local DB
    //         $newEmployee = Employee::create([
    //             'employee_code' => $greythrData['employee']['employee_code'],
    //             'name' => $greythrData['employee']['name'],
    //             'email' => $greythrData['employee']['email'],
    //             'password' => Hash::make($validated['password']), // Hash the password
    //             'designation' => $greythrData['employee']['designation'] ?? null,
    //             'phone' => $greythrData['employee']['phone'] ?? null,
    //             'address' => $greythrData['employee']['address'] ?? null,
    //             'photo' => $greythrData['employee']['photo'] ?? null,
    //             'emergency_contact' => $greythrData['employee']['emergency_contact'] ?? null,
    //             'employee_type_id' => $validated['employee_type_id'],
    //             'status' => 1 // Active
    //         ]);

    //         // Generate Token
    //         $token = $newEmployee->createToken('API Token')->plainTextToken;

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Login successful via GreytHR',
    //             'data' => [
    //                 'employee' => [
    //                     'id' => $newEmployee->id,
    //                     'employee_code' => $newEmployee->employee_code,
    //                     'name' => $newEmployee->name,
    //                     'designation' => $newEmployee->designation,
    //                     'email' => $newEmployee->email,
    //                     'phone' => $newEmployee->phone,
    //                     'address' => $newEmployee->address,
    //                     'photo' => $newEmployee->photo,
    //                     'emergency_contact' => $newEmployee->emergency_contact,
    //                 ],
    //                 'employee_type' => [
    //                     'id' => $validated['employee_type_id'],
    //                     'type_name' => 'Unknown', // You may update this later
    //                 ],
    //                 'token' => $token,
    //                 'status' => 'active',
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
}
