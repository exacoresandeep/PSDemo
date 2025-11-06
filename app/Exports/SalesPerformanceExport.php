<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Employee;
use App\Models\District;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class SalesPerformanceExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $region_id, $employee_type_id, $month, $year;

    public function __construct($region_id, $employee_type_id, $month, $year)
    {
        $this->region_id = $region_id;
        $this->employee_type_id = $employee_type_id;
        $this->month = $month + 1;
        $this->year = $year;
    }

    public function collection()
    {
        $districtIds = District::where('regions_id', $this->region_id)->pluck('id');

        $employees = Employee::where('employee_type_id', $this->employee_type_id)
            ->whereIn('district_id', $districtIds)
            ->with(['employeeType', 'district.region'])
            ->get();

        $fromDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $toDate = $fromDate->copy()->endOfMonth();

        $data = new Collection();
        $serial = 1;

        foreach ($employees as $employee) {
            $orders = Order::where('created_by', $employee->id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            $totalQty = (float) ($orders->sum('invoice_quantity') ?? 0);
            $totalAmount = (float) ($orders->sum('invoice_total') ?? 0);
            $data->push([
                'S.No' => $serial++,
                'Region' => $employee->region?->name ?? 'N/A',
                'Employee Type' => $employee->employeeType?->type_name ?? 'N/A',
                'Employee Name' => $employee->name,
                'Selling Quantity (TON)' => $totalQty,
                'Total Amount' => $totalAmount,
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Region',
            'Employee Type',
            'Employee Name',
            'Selling Quantity (TON)',
            'Total Amount',
        ];
    }
}
