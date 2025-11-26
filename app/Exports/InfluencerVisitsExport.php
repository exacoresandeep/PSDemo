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
    protected $year, $month, $row = 1;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];
    protected $employees = [];

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month + 1;
    }

    public function collection()
    {
        $this->employees = Employee::select('id', 'name', 'employee_code')->get();

        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('customer_visit', 'employee_id')
            ->toArray();

        $this->achievedByEmployee = InfluencerVisit::whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->whereNotNull('phone')
            ->select('created_by', DB::raw('COUNT(DISTINCT phone) as count'))
            ->groupBy('created_by')
            ->pluck('count', 'created_by')
            ->toArray();

        return $this->employees;
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
