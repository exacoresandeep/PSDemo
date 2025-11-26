<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Target;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TisconOrdersExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $year, $month, $row = 1;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month + 1;
    }

    public function collection()
    {
        // Get all targets for the month/year
        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('order_quantity', 'employee_id')
            ->toArray();

        // Get all delivered orders with valid created_by
        $orders = Order::whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->where('order_approved', '1')
            ->whereNotNull('created_by')
            ->get();

        // Aggregate achieved quantity per employee
        foreach ($orders as $order) {
            $empId = $order->created_by;
            $sumQuantity = $order->orderItems->sum('total_quantity');
            $this->achievedByEmployee[$empId] = ($this->achievedByEmployee[$empId] ?? 0) + (float) $sumQuantity;
        }

        // Filter employees who have a non-zero target
        $targetedEmployeeIds = array_keys(array_filter($this->targetsByEmployee, function ($target) {
            return (float) $target > 0;
        }));

        // Return only those employees
        return Employee::whereIn('id', $targetedEmployeeIds)->get();
    }

    public function map($employee): array
    {
        $empId = $employee->id;
        $target = (float) ($this->targetsByEmployee[$empId] ?? 0);
        $achieved = (float) ($this->achievedByEmployee[$empId] ?? 0);

        return [
            $this->row++,
            $employee->name,
            $employee->email,
            $target,
            $achieved,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Employee Name',
            'Employee Email',
            'Target (Tiscon)',
            'Achieved (Delivered Quantity)',
        ];
    }
}

