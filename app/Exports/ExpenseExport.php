<?php
namespace App\Exports;

use App\Models\DayExpense;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $from_date, $to_date, $employee_type, $employee_id, $travel_method;

    public function __construct($from_date, $to_date, $employee_type, $employee_id, $travel_method)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
         $this->employee_type = $employee_type;
        $this->employee_id = $employee_id;
        $this->travel_method = $travel_method;
    }

    public function collection()
    {
        return DayExpense::with('employee')
            ->when($this->from_date && $this->to_date, function ($query) {
                $query->whereBetween('created_at', [$this->from_date, $this->to_date]);
            })
            ->when($this->employee_type, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('employee_type_id', $this->employee_type);
                });
            })
            ->when($this->employee_id, function ($query) {
                $query->where('employee_id', $this->employee_id);
            })
            ->when($this->travel_method, function ($query) {
                $query->where('travel_method', $this->travel_method);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function headings(): array
    {
        return [
            'Sl.No',
            'Employee Name',
            'Employee Code',
            'Date & Time',
            'Travel Method',
            'Kilometer Travelled',
            'Other Expense',
            'Remarks',
            'Total Amount',
        ];
    }
    
    protected $methodPrices = [
        "Bike"    => 4.0,
        "Car"     => 5.6,
        "Own Car" => 9.0,
    ];

    public function map($expense): array
    {
        static $count = 0;
        $count++;

        // calculate total amount dynamically
        $pricePerKm = $this->methodPrices[$expense->travel_method] ?? 0;
        $km         = $expense->km_traveled ?? 0;
        $other      = $expense->other_expense ?? 0;
        $total      = ($km * $pricePerKm) + $other;

        return [
            $count,
            $expense->employee->name ?? '-',
            $expense->employee->employee_code ?? '-',
            $expense->created_at ? $expense->created_at->format('d-m-Y h:i A') : '-',
            $expense->travel_method,
            $km,
            number_format($other, 2),
            $expense->remarks ?? '-',
            number_format($total, 2),
        ];
    }
}

