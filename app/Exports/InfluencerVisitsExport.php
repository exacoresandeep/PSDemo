<?php

namespace App\Exports;

use App\Models\InfluencerVisit;
use App\Models\Target;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InfluencerVisitsExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $year, $month, $employee_type_id,$row = 1;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];
    protected $employees = [];
protected $product_id;

    public function __construct($year, $month,$employee_type_id,$product_id)
    {
	    $this->employee_type_id = $employee_type_id;
	     $this->product_id = $product_id;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
	    $productID=  $this->product_id ?? \App\Helpers\ProductHelper::getSelectedProductID();
    	    $this->employees = Employee::select('id', 'name', 'employee_code')
            ->where('employee_type_id', $this->employee_type_id)
            ->whereRaw("JSON_CONTAINS(products, ?)", ['["' . $productID . '"]'])->get();

        $this->targetsByEmployee = Target::where('month', $this->month)
	     ->where('year', $this->year)
     ->where('product_id', $productID)
            ->pluck('customer_visit', 'employee_id')
            ->toArray();

        $this->achievedByEmployee = InfluencerVisit::whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
	    ->whereNotNull('phone')
    ->whereHas('order', function ($query) use ($productID) {  //push
                $query->where('product_id', $productID);
            })
            ->select('created_by', DB::raw('COUNT(DISTINCT phone) as count'))
            ->groupBy('created_by')
            ->pluck('count', 'created_by')
            ->toArray();

        //return $this->employees;
        return $this->employees->filter(function ($employee) {
            $employeeId = $employee->id;
            return ($this->achievedByEmployee[$employeeId] ?? 0) > 0;
        })->values();
        
    }

    public function map($employee): array
    {
        $employeeId = $employee->id;

        return [
            $this->row++, 
            $employee->name,
            $employee->employee_code,
            $this->targetsByEmployee[$employeeId] ?? 0,
            $this->achievedByEmployee[$employeeId] ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'Sl. No',
            'Employee Name',
            'Employee Code',
            'Target',
            'Achieved'
        ];
    }
}