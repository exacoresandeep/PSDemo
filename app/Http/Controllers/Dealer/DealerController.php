<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DealerController extends Controller
{
   
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'dealer_code' => 'required|string',
                'password' => 'required|string',
                'type' => 'required|string|in:Dealer',
            ]);

	        $dealer = Dealer::where('dealer_code', $validated['dealer_code'])
		    ->where('status', '1')
                        ->first();

            if (!$dealer || !Hash::check($validated['password'], $dealer->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid credentials',
                ], 400);
            }

            $token = $dealer->createToken('Dealer API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Login successful',
                'data' => [
                    'dealer' => [
                        'id' => $dealer->id,
                        'dealer_code' => $dealer->dealer_code,
                        'name' => $dealer->dealer_name,
                        'email' => $dealer->email,
            			'password_reset_flag'=>$dealer->password_reset_flag == 0 ? false : true,
            			'phone' => $dealer->phone,
                        'address' => $dealer->address,
                    ],
                    'token' => $token,
                    'status' => 'active',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'dealer_code' => 'required|string|unique:dealers,dealer_code',
                'dealer_name' => 'required|string',
                'gst_no' => 'nullable|string|max:15|unique:dealers,gst_no',
                'pan_no' => 'nullable|string|max:10|unique:dealers,pan_no',
                'phone' => 'required|string|max:15|unique:dealers,phone',
                'email' => 'nullable|email|unique:dealers,email',
                'address' => 'nullable|string',
                'user_zone' => 'nullable|string',
                'pincode' => 'nullable|string|max:6',
                'state' => 'nullable|string',
                'district' => 'nullable|string',
                'taluk' => 'nullable|string',
                'location' => 'nullable|string',
                'assigned_route_id' => 'required|integer',
            ]);

            
            $district = District::where('name', $validated['district'])->first();
            if (!$district) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid district. The district does not exist in the system.',
                ], 400);
            }

    
            $dealerCode = strtoupper($validated['dealer_code']);
            $dealerPrefix = substr($dealerCode, 0, 3);
            $gstNumber = $validated['gst_no'] ?? null;
            $gstSuffix = $gstNumber ? substr($gstNumber, -3) : "#2025";
            $password = $dealerPrefix . $gstSuffix;

            $hashedPassword = Hash::make($password);

            $dealer = Dealer::create(array_merge($validated, [
                'password' => $hashedPassword,
                'district_id' => $district->id,
            ]));

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer created successfully',
                'data' => $dealer
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getDealerProfile(Request $request)
    {
        try {
            $dealer = $request->user();
    
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer profile retrieved successfully',
                'data' => [
                    'id' => $dealer->id,
                    'dealer_code' => $dealer->dealer_code,
                    'name' => $dealer->dealer_name,
                    'email' => $dealer->email,
                    'phone' => $dealer->phone,
                    'address' => $dealer->address,
                    'gst_no' => $dealer->gst_no,
                    'pan_no' => $dealer->pan_no,
                    'status' => $dealer->status,
                    'created_at' => $dealer->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function downloadLedgerNew(Request $request)
    {    
        try {
            $dealer = $request->user();
            if (!$dealer || !$dealer->dealer_code) {
                return response()->json(['error' => 'Dealer not found or unauthorized.'], 401);
            }
            $request->validate([
                'from_date' => 'required|date_format:d/m/Y',
                'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
            ]);
            $from_date = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $to_date   = $request->to_date 
            ? Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d')
            : now()->toDateString();
            $dealerCode = $dealer->dealer_code;
            $year = (date('m') < 4) ? date('Y') - 1 : date('Y');
            $fyStart = $year . '0401';
            $today = Carbon::now()->format('Ymd');

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                return response()->json(['error' => 'SAP Connection Failed: ' . odbc_errormsg()], 500);
            }

            $sql = "CALL \"PRABHU_NEW\".\"@DealerStatements\"('$from_date', '$to_date', '$dealerCode')";
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                odbc_close($conn);
                return response()->json(['error' => 'SAP Query Failed: ' . odbc_errormsg()], 500);
            }

            $ledgerData = [];
            $openingBalance = 0.0;
            $closingBalance = 0.0;

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);
                unset($row['ContraAct']);

                if (!empty($row['RefDate'])) {
                    $rawDate  = Carbon::parse($row['RefDate']);
                    $row['RefDate'] = $rawDate->format('d/m/Y');
                }

                $row['Debit'] = isset($row['Debit']) ? (float) $row['Debit'] : 0.0;
                $row['Credit'] = isset($row['Credit']) ? (float) $row['Credit'] : 0.0;
                $row['OB'] = isset($row['OB']) ? (float) $row['OB'] : 0.0;

                // Check TransType and store OB/CL separately
                if (isset($row['TransType']) && $row['TransType'] === 'OB') {
                    $openingBalance = $row['OB'];
                    continue;
                }

                if (isset($row['TransType']) && $row['TransType'] === 'CL') {
                    $closingBalance = $row['OB'];
                    continue;
                }
                $row['__rawDate'] = $rawDate ? $rawDate->timestamp : null;
                $ledgerData[] = $row;
            }

            odbc_close($conn);

            // Sort by raw date (ascending)
            usort($ledgerData, function ($a, $b) {
                return ($a['__rawDate'] <=> $b['__rawDate']);
            });

            // Remove helper field before returning
            $ledgerData = array_map(function ($row) {
                unset($row['__rawDate']);
                return $row;
            }, $ledgerData);

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Ledger fetched successfully',
                'data' => [
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'ledger' => $ledgerData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Error: ' . $e->getMessage()], 500);
        }
    }
    public function downloadLedger(Request $request)
    {
        try {
            $dealer = $request->user();
            if (!$dealer || !$dealer->dealer_code) {
                return response()->json(['error' => 'Dealer not found or unauthorized.'], 401);
            }
            $request->validate([
                'from_date' => 'required|date_format:d/m/Y',
                'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
                'product_code'   => 'required',
            ]);
            $products = [
                1 => "tata tiscon",
                3 => "structura",
                5 => "durashine",
                6 => "PC Wire",
            ];
            $sap_product_id = array_search($request->product_code, $products);
            if (!$sap_product_id) {
                return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Invalid Product Code',
                'data' => []
            ]);
            }

            $from_date = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $to_date   = $request->to_date 
            ? Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d')
            : now()->toDateString();
            $dealerCode = $dealer->dealer_code;
            $year = (date('m') < 4) ? date('Y') - 1 : date('Y');
            $fyStart = $year . '0401';
            $today = Carbon::now()->format('Ymd');

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                return response()->json(['error' => 'SAP Connection Failed: ' . odbc_errormsg()], 500);
            }

            $sql = "CALL \"PRABHU_NEW\".\"@DealerStatements\"('$from_date', '$to_date', '$dealerCode',$sap_product_id)";
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                odbc_close($conn);
                return response()->json(['error' => 'SAP Query Failed: ' . odbc_errormsg()], 500);
            }

            $ledgerData = [];
            $openingBalance = 0.0;
            $closingBalance = 0.0;

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);
                unset($row['ContraAct']);

                if (!empty($row['RefDate'])) {
                    $rawDate  = Carbon::parse($row['RefDate']);
                    $row['RefDate'] = $rawDate->format('d/m/Y');
                }

                $row['Debit'] = isset($row['Debit']) ? (float) $row['Debit'] : 0.0;
                $row['Credit'] = isset($row['Credit']) ? (float) $row['Credit'] : 0.0;
                $row['OB'] = isset($row['OB']) ? (float) $row['OB'] : 0.0;

                // Check TransType and store OB/CL separately
                if (isset($row['TransType']) && $row['TransType'] === 'OB') {
                    $openingBalance = $row['OB'];
                    continue;
                }

                if (isset($row['TransType']) && $row['TransType'] === 'CL') {
                    $closingBalance = $row['OB'];
                    continue;
                }
                $row['__rawDate'] = $rawDate ? $rawDate->timestamp : null;
                $ledgerData[] = $row;
            }

            odbc_close($conn);

            // Sort by raw date (ascending)
            usort($ledgerData, function ($a, $b) {
                return ($a['__rawDate'] <=> $b['__rawDate']);
            });

            // Remove helper field before returning
            $ledgerData = array_map(function ($row) {
                unset($row['__rawDate']);
                return $row;
            }, $ledgerData);

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Ledger fetched successfully',
                'data' => [
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'ledger' => $ledgerData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Error: ' . $e->getMessage()], 500);
        }
    }
    public function downloadLedgerF(Request $request)
    {    
        
        try {
            $dealer = $request->user();
            if (!$dealer || !$dealer->dealer_code) {
                return response()->json(['error' => 'Dealer not found or unauthorized.'], 401);
            }
            $request->validate([
                'from_date' => 'required|date_format:d/m/Y',
                'product_id' => 'required',
                'to_date'   => 'nullable|date_format:d/m/Y|after_or_equal:from_date',
            ]);
            $from_date = Carbon::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
            $to_date   = $request->to_date 
            ? Carbon::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d')
            : now()->toDateString();
            $dealerCode = $dealer->dealer_code;
            $year = (date('m') < 4) ? date('Y') - 1 : date('Y');
            $fyStart = $year . '0401';
            $today = Carbon::now()->format('Ymd');

            $sap_id= Product::where("id",$request->product_id)->value("sap_id"); 
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                return response()->json(['error' => 'SAP Connection Failed: ' . odbc_errormsg()], 500);
            }

            $sql = "CALL \"PRABHU_NEW\".\"@DealerStatements_F\"('$from_date', '$to_date', '$dealerCode','$sap_id')";  
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                odbc_close($conn);
                return response()->json(['error' => 'SAP Query Failed: ' . odbc_errormsg()], 500);
            }

            $ledgerData = [];
            $openingBalance = 0.0;
            $closingBalance = 0.0;

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);
                unset($row['ContraAct']);

                if (!empty($row['RefDate'])) {
                    $rawDate  = Carbon::parse($row['RefDate']);
                    $row['RefDate'] = $rawDate->format('d/m/Y');
                }

                $row['Debit'] = isset($row['Debit']) ? (float) $row['Debit'] : 0.0;
                $row['Credit'] = isset($row['Credit']) ? (float) $row['Credit'] : 0.0;
                $row['OB'] = isset($row['OB']) ? (float) $row['OB'] : 0.0;

                // Check TransType and store OB/CL separately
                if (isset($row['TransType']) && $row['TransType'] === 'OB') {
                    $openingBalance = $row['OB'];
                    continue;
                }

                if (isset($row['TransType']) && $row['TransType'] === 'CL') {
                    $closingBalance = $row['OB'];
                    continue;
                }
                $row['__rawDate'] = $rawDate ? $rawDate->timestamp : null;
                $ledgerData[] = $row;
            }

            odbc_close($conn);

            // Sort by raw date (ascending)
            usort($ledgerData, function ($a, $b) {
                return ($a['__rawDate'] <=> $b['__rawDate']);
            });

            // Remove helper field before returning
            $ledgerData = array_map(function ($row) {
                unset($row['__rawDate']);
                return $row;
            }, $ledgerData);

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Ledger fetched successfully',
                'data' => [
                    "dealer_code"=>$dealer->dealer_code, 
                    "dealer_name"=>$dealer->dealer_name, 
                    "gst_no"=>$dealer->gst_no, 
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'ledger' => $ledgerData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Error: ' . $e->getMessage()], 500);
        }
    }
}
