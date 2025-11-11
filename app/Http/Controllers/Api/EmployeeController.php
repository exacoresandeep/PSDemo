<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\District;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\EmployeeType;
use App\Models\Product;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Str;


class EmployeeController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string|unique:employees,employee_code',
                'name' => 'required|string',
                'designation' => 'required|string',
                'area' => 'nullable|string',
                'email' => 'required|email|unique:employees,email',
                'phone' => 'required|string',
                'password' => 'required|string',
                'address' => 'nullable|string',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'emergency_contact' => 'nullable|string',
                'employee_type_id' => 'required|exists:employee_types,id',
                'reporting_manager' => 'nullable|exists:employees,id',
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
            } else {
                $photoPath = null; 
            }

            $employee = Employee::create([
                'employee_code' => $validated['employee_code'],
                'name' => $validated['name'],
                'designation' => $validated['designation'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'address' => $validated['address'],
                'photo' => $photoPath,
                'emergency_contact' => $validated['emergency_contact'],
                'employee_type_id' => $validated['employee_type_id'],
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employee created successfully',
                'data' => $employee,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'employee_code' => 'required|string|unique:employees,employee_code',
                'employee_type_id' => 'required',
                'email' => 'unique:employees,email',
                'employee_sap_code' => 'required|string|unique:employees,employee_sap_code',
            ]);

            $namePart = Str::upper(substr($request->name, 0, 3)); // first 3 letters uppercase
            $password = $namePart . $request->employee_code . '@';
             $managerName = $request->reporting_manager 
            ? Employee::find($request->reporting_manager)?->name 
            : null;
            $designation = $request->employee_type_id 
            ? EmployeeType::find($request->employee_type_id)?->type_name 
            : null;
            $employee = Employee::create([
                'name' => $request->name,
                'employee_code' => $request->employee_code,
                'employee_sap_code' => $request->employee_sap_code,
                'employee_type_id' => $request->employee_type_id,
                'email' => $request->email,
                'designation' => $designation ?? '',
                'phone' => $request->phone,
                'district_id' => $request->district_id,
                'district' => $request->district_id 
                                            ? District::find($request->district_id)?->name 
                                            : null,
                'products' => $request->products ? json_encode($request->products) : null,
                'reporting_manager' => $request->reporting_manager,
                'reporting_manager_name' => $managerName,
                'password' => Hash::make($password),
            ]);
           
            return response()->json(['message' => 'Employee created successfully', 'employee' => $employee]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'employee_code' => 'required|string|max:50|unique:employees,employee_code,' . $id,
                'employee_sap_code' => 'required|string|max:22|unique:employees,employee_sap_code,' . $id,
                'employee_type_id' => 'required|integer',
                'email' => 'required|email|unique:employees,email,' . $id,
                'phone' => 'nullable|string|max:15',
                'district_id' => 'nullable|integer',
                'reporting_manager' => 'nullable|integer',
            ]);

            $employee = Employee::findOrFail($id);
            $designation = $request->employee_type_id 
            ? EmployeeType::find($request->employee_type_id)?->type_name 
            : null;
            $employee->update([
                'name' => $request->name,
                'employee_code' => $request->employee_code,
                'employee_sap_code' => $request->employee_sap_code,
                'employee_type_id' => $request->employee_type_id,
                'email' => $request->email,
                'phone' => $request->phone,
                'district_id' => $request->district_id,
                'district' => $request->district_id 
                                            ? District::find($request->district_id)?->name 
                                            : null,
                'reporting_manager' => $request->reporting_manager,
                'reporting_manager_name' => $request->reporting_manager 
                                            ? Employee::find($request->reporting_manager)?->name 
                                            : null,
                'designation' => $designation,
                'products' => $request->products ? json_encode($request->products) : null,
                'area' => $request->area ?? $employee->area,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully!',
                'data' => $employee
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update employee!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        // Get filters
        $designation = $request->designation;
        $employee_id = $request->employee_id;
        $district    = $request->district;

        // Query employees
        $query = Employee::query();

        if ($designation) {
            $query->where('employee_type_id', $designation);
        }
        if ($employee_id) {
            $query->where('id', $employee_id);
        }
        if ($district) {
            $query->where('district_id', $district);
        }

        $employees = $query->get();

        // Prepare CSV
        $filename = "employees_" . date('Y-m-d_H-i-s') . ".csv";
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $columns = ['ID', 'Name', 'Employee Code', 'Designation', 'Email', 'Phone', 'District', 'Reporting Manager', 'SAP Code'];

        $callback = function() use ($employees, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->id,
                    $emp->name,
                    $emp->employee_code,
                    $emp->designation ?? '',
                    $emp->email,
                    $emp->phone,
                    $emp->district ?? '',
                    $emp->reporting_manager_name ?? '',
                    $emp->employee_sap_code
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function importSAP(Request $request)
    {
        $request->validate([
            'sap_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $data = Excel::toArray([], $request->file('sap_file'));
            $rows = $data[0] ?? [];

            $updatedCount = 0;
            $skipped = 0;
            $invalid = 0;

            $skippedCodes = [];
            $invalidCodes = [];

            foreach ($rows as $key => $row) {
                if ($key === 0) continue; // skip header

                $employeeCode = trim($row[0] ?? '');
                $sapCode      = trim($row[1] ?? '');

                if ($employeeCode && $sapCode) {
                    $employee = Employee::where('employee_code', $employeeCode)->first();

                    if ($employee) {
                        // ✅ check duplicate SAP code
                        $exists = Employee::where('employee_sap_code', $sapCode)
                            ->where('id', '!=', $employee->id)
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            $skippedCodes[] = $employeeCode;
                            continue;
                        }

                        $employee->update(['employee_sap_code' => $sapCode]);
                        $updatedCount++;
                    } else {
                        $invalid++;
                        $invalidCodes[] = $employeeCode;
                    }
                }
            }

            return response()->json([
                'status'        => true,
                'updated_count' => $updatedCount,
                'skipped_count' => $skipped,
                'invalid_count' => $invalid,
                'skipped_codes' => $skippedCodes,
                'invalid_codes' => $invalidCodes,
                'message'       => "Import finished"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to import SAP Codes.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function show(Request $request)
    {
        try {
            $employee = $request->user();
            $employee = Employee::with('employeeType', 'reportingManager:id,name')->where('id', $employee->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Employee not found',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employee details retrieved successfully',
                'data' => $employee,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEmployeesByType($employeeTypeId)
    {
        $employees = Employee::where('employee_type_id', $employeeTypeId)->get();
        $employees = $employees->map(function ($employee) {
            $customerVisitCount = Lead::where('created_by', $employee->id)->count();
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'customer_visit' => $customerVisitCount
            ];
        });
        return response()->json($employees);
    }

    public function filterEmployeesByType(Request $request)
    {
        try {
            $request->validate([
                'employee_type_id' => 'required|integer|in:1,2,3,4',
                'search_key' => 'nullable|string|max:255'
            ]);

            $employees = Employee::with('district:id,name')
                ->where('employee_type_id', $request->employee_type_id)
                ->when($request->search_key, function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->search_key . '%')
                        ->orWhere('employee_code', 'LIKE', '%' . $request->search_key . '%');
                    });
                })
                ->select('id as employee_id', 'employee_code', 'name', 'email', 'phone', 'designation', 'employee_type_id', 'district_id')
                ->get()
                ->map(function ($employee) {
                    return [
                        'employee_id' => $employee->employee_id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'designation' => $employee->designation,
                        'employee_type_id' => $employee->employee_type_id,
                        'district_id' => $employee->district_id,
                        'district_name' => $employee->district->name ?? null
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employees fetched successfully',
                'data' => $employees,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(){
        $districts = District::select('id', 'name')->get();
        $employees = Employee::select('id', 'name')->get();
        $products = Product::select('id', 'product_name')->get();
        $employeeTypes = EmployeeType::select('id', 'type_name')->get();
        return view('sales.employee.index', compact('employeeTypes','districts','employees','products'));
    }

    public function getEmployeesAjax(Request $request)
    {
        $search = $request->q ?? '';

        $employees = Employee::where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($employees);
    }
    public function list(Request $request)
    {
        $query = Employee::with(['employeeType', 'district', 'reportingManager']);

        // Filters
        if (!empty($request->designation)) {
            $query->where('employee_type_id', $request->designation);
        }

        if (!empty($request->employee_id)) {
            $query->where('id', $request->employee_id);
        }

        if (!empty($request->district)) {
            $query->where('district_id', $request->district);
        }

        // if (!empty($request->status)) {
        //     $query->where('status', $request->status);
        // }

        $query->orderBy('id', 'desc');
        $allProducts = Product::pluck('product_name', 'id');
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', fn($t) => $t->name ?? '-')
            ->addColumn('employee_code', fn($t) => $t->employee_code ?? '-')
            ->addColumn('designation', fn($t) => optional($t->employeeType)->type_name ?? '-')
            ->addColumn('phone', fn($t) => !empty($t->phone) ? $t->phone : '-')
            ->addColumn('email', fn($t) => $t->email ?? '-')
            ->addColumn('district', fn($t) => optional($t->district)->name ?? $t->district ?? '-')
            ->addColumn('reporting_manager_name', fn($t) => $t->reporting_manager_name ?? '-')
            ->addColumn('employee_sap_code', fn($t) => $t->employee_sap_code ?? '-')
            // ->addColumn('status', function ($t) {
            //     return $t->status === 'Active'
            //         ? '<span class="badge badge-success">Active</span>'
            //         : '<span class="badge badge-warning">Inactive</span>';
            // })
            ->addColumn('action', function ($t) {
                $editUrl = route('sales.employee.edit', $t->id);
                $deleteUrl = route('sales.employee.delete', $t->id); // your delete route

                return '
                    <button class="btn btn-sm btn-info editEmployeeBtn" data-id="'.$t->id.'">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteEmployeeBtn" data-url="'.$deleteUrl.'">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })

            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('employee_code', function($query, $keyword) {
                $query->where('employee_code', 'like', "%{$keyword}%");
            })
            ->filterColumn('designation', function($query, $keyword) {
                $query->whereHas('employeeType', function($q) use ($keyword) {
                    $q->where('type_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('phone', function($query, $keyword) {
                $query->where('phone', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function($query, $keyword) {
                $query->where('email', 'like', "%{$keyword}%");
            })
            ->filterColumn('district', function($query, $keyword) {
                $query->whereHas('district', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('reporting_manager_name', function($query, $keyword) {
                $query->whereHas('reportingManager', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
           ->filterColumn('employee_sap_code', function($query, $keyword) {
                $query->where('employee_sap_code', 'like', "%{$keyword}%");
            })
            ->addColumn('products', function ($t) use ($allProducts) {
                if (empty($t->products)) {
                    return '-';
                }

                $productIds = json_decode($t->products, true);

                if (empty($productIds) || !is_array($productIds)) {
                    return '-';
                }

                $productNames = collect($productIds)
                    ->map(fn($id) => $allProducts[$id] ?? null)
                    ->filter()
                    ->join(', ');

                return $productNames ?: '-';
            })

            ->rawColumns(['action'])
            ->make(true);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deleted successfully.'
        ]);
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $employee
        ]);
    }

    


}



