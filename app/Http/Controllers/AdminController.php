<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\District;
use App\Models\Order;
use App\Models\Regions;
use App\Models\ProductDetails;
use App\Models\Product;
use Yajra\DataTables\Facades\DataTables;
use Redirect;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;


class AdminController extends Controller
{

    
    
    public function login()
    {
        return view('login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            switch ($user->role_id) {
                case 1: // Super Admin
                    return redirect()->route('admin.dashboard');
                case 2: // Sales
                    return redirect()->route('sales.dashboard');
                case 3: // Accounts
                    return redirect()->route('accounts.dashboard');
                case 4: // Logistics
                    return redirect()->route('logistics.dashboard');
                case 5: // MD
                    return redirect()->route('md.dashboard'); 
                case 6: // MD
                    return redirect()->route('operations.dashboard'); 
                default:
                    Auth::logout();
                    return back()->with('error', 'Unauthorized role access');
            }
        }

        return back()->with('error', 'Invalid Username or Password');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        switch ($user->role_id) {
            case 1: return view('admin.dashboard', compact('user')); 
            case 2: 
                $employeeTypes = EmployeeType::select('id', 'type_name')->get();
                $regions = Regions::select('id', 'name')->get();
                return view('sales.dashboard',compact('employeeTypes', 'regions'));
            case 3: return view('accounts.dashboard', compact('user')); 
            case 4: return view('logistics.dashboard', compact('user')); 
            case 5: 
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
                        
                        // $stocks = ProductDetails::select('type_id')
                        // ->where('product_id', $products->id)
                        // ->selectRaw('SUM(total_available_quantity) as total_stock_quantity')
                        // ->groupBy('type_id')
                        // ->with('productType:id,type_name')
                        // ->get()
                        // ->map(function ($item) {
                        //     return [
                        //         'type_id' => $item->type_id,
                        //         'type_name' => $item->productType->type_name ?? 'Unknown',
                        //         'total_stock_quantity' => (float) $item->total_stock_quantity,
                        //     ];
                        // });
                        $stocks = [];

                        if ($products) {
                            $stocks = ProductDetails::select('type_id')
                                ->where('product_id', $products->id)
                                ->selectRaw('SUM(total_available_quantity) as total_stock_quantity')
                                ->groupBy('type_id')
                                ->with('productType:id,type_name')
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        'type_id' => $item->type_id,
                                        'type_name' => $item->productType->type_name ?? 'Unknown',
                                        'total_stock_quantity' => (float) $item->total_stock_quantity,
                                    ];
                                });
                        }
                        $employeeTypes = EmployeeType::select('id', 'type_name')->get();
                        $regions = Regions::select('id', 'name')->get();

                        $currentMonth = now()->month - 1;
            $currentYear = now()->year;
            $startYear = $currentYear - 3;
            $endYear = $currentYear + 5;

            $months = [
                'January','February','March','April','May','June',
                'July','August','September','October','November','December'
            ];

            return view('md.dashboard', [
                'months'        => $months,
                'currentMonth'  => $currentMonth,
                'currentYear'   => $currentYear,
                'startYear'     => $startYear,
                'endYear'       => $endYear,
                'stocks'        => $stocks ?? [],   // supply your data here
                'employeeTypes'        => $employeeTypes ?? [],   // supply your data here
                'regions'       => $regions ?? []   // supply your data here
            ]);
                // return view('md.dashboard',compact('stocks', 'employeeTypes', 'regions'));
            case 6: return view('operations.dashboard', compact('user')); 
            default:
                Auth::logout();
                return redirect()->route('login')->with('error', 'Unauthorized role access');
        }
    }

    public function loadProduct()
    {
        $sessionProductCode = session('selected_product_code');
        if ($sessionProductCode) {

            $products = Product::where('product_code', $sessionProductCode)
                ->select('id', 'product_name', 'product_code')
                ->get();

            // If product not found → fallback to first row
            if ($products->count() === 0) {
                $products = Product::select('id', 'product_name', 'product_code')
                    ->orderBy('id')
                    ->limit(1)
                    ->get();
            }

        } else {
            // No session → return first product only
           
            $products = Product::select('id', 'product_name', 'product_code')
                ->orderBy('id')
                ->limit(1)
                ->get();
        }

        return response()->json([
            'products' => $products
        ]);
    }
    public function logout(Request $request)
    {
        Cookie::queue(Cookie::forget('selectedLink'));

        if (Auth::check()) {
            Auth::logout();
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
    public function employeeIndex()
    {
        return view('admin.users.employee-index');
    }
    public function employeeList(Request $request)
    {
        $employees = Employee::with('reportingManager') // Load the reporting manager relationship
            ->select([
                'id', 'employee_code', 'name', 'email', 'phone', 
                'district', 'area', 'designation', 'reporting_manager', 
                'address', 'emergency_contact'
            ]);

        return DataTables::of($employees)
            ->addColumn('reporting_manager', function ($employee) {
                return $employee->reportingManager ? $employee->reportingManager->name : 'N/A';
            })
            ->make(true);
    }
    public function importEmployees(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }
    
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
    
        // Allowed file types
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            return response()->json(['message' => 'Invalid file format. Upload CSV or Excel file.'], 400);
        }
    
        try {
            DB::transaction(function () use ($file) {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
    
                unset($rows[0]);
    
                foreach ($rows as $row) {
                    $employeeCode = $row[0]; 
                    $name = $row[1];
                    $email = $row[2];
                    $phone = $row[3];
                    $districtName = $row[4]; 
                    $area = $row[5];
                    $designation = trim($row[6]);
                    $reportingManagerName = $row[7];
                    $address = $row[8];
                    $emergencyContact = $row[9];

                    if (empty($designation)) {
                        continue; 
                    }
                    if (Employee::where('employee_code', $employeeCode)->exists()) {
                        continue;
                    }
                    $district = District::where('name', $districtName)->first();
                    $districtId = $district ? $district->id : null;
    
                    $employeeType = EmployeeType::firstOrCreate(
                        ['type_name' => $designation],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                   
                    $reportingManager = Employee::where('name', $reportingManagerName)->first();
                    $reportingManagerId = $reportingManager ? $reportingManager->id : null;
    
                    $passwordString = strtoupper(substr($name, 0, 3)) . $employeeCode;
                    
                    $hashedPassword = Hash::make($passwordString);
    
                    // Insert Employee
                    Employee::create([
                        'employee_code' => $employeeCode,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'district_id' => $districtId,
                        'district' => $districtName,
                        'area' => $area,
                        'designation' => $designation,
                        'employee_type_id' => $employeeType->id,
                        'reporting_manager' => $reportingManagerId,
                        'reporting_manager_name' => $reportingManagerName, 
                        'address' => $address,
                        'emergency_contact' => $emergencyContact,
                        'password' => $hashedPassword, // Store encrypted password
                        'status' => '1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    
            return response()->json(['message' => 'Employees imported successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function usersIndex(){
        return view('admin.users.user');
    }
}
