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
    protected $year, $month, $row = 1, $productID;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];

    public function __construct($year, $month, $productID)
    {
        $this->year = $year;
        $this->month = $month + 1;
        $this->productID = $productID;
    }

    public function collection()
    {
        $productID= \App\Helpers\ProductHelper::getSelectedProductID(); //push
        // Get all employees who have target
        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('order_quantity', 'employee_id')
            ->where('product_id', $productID)//push
            ->toArray();

        $targetedEmployeeIds = array_keys(
            array_filter($this->targetsByEmployee, fn($t) => (float)$t > 0)
        );

        // 👉 Get employees whose products JSON contains productID
        $productEmployees = Employee::whereJsonContains('products', (string)$this->productID)
            ->pluck('id')
            ->toArray();

        // Merge both employee lists
        $finalEmployeeIds = array_unique(array_merge($targetedEmployeeIds, $productEmployees));

        // 👉 Achieved Quantity (Filtered by product_id also)
        $this->achievedByEmployee = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw('orders.created_by as employee_id, SUM(order_items.total_quantity) as achieved_qty')
            ->whereYear('orders.created_at', $this->year)
            ->whereMonth('orders.created_at', $this->month)
            ->where('orders.order_approved', '1')
            ->where('orders.product_id', $this->productID)
            ->whereNotNull('orders.created_by')
            ->groupBy('orders.created_by')
            ->pluck('achieved_qty', 'employee_id')
            ->toArray();

        // 👉 Return only employees matching target or product
        return Employee::whereIn('id', $finalEmployeeIds)->get();
    }

    public function map($employee): array
    {
        $empId = $employee->id;
        return [
            $this->row++,
            $employee->name,
            $employee->email,
            (float) ($this->targetsByEmployee[$empId] ?? 0),
            (float) ($this->achievedByEmployee[$empId] ?? 0),
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


