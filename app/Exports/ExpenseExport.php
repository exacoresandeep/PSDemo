<?php
namespace App\Exports;

use App\Models\DayExpense;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $from_date, $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    public function collection()
    {
        return DayExpense::when($this->from_date && $this->to_date, function ($query) {
            $query->whereBetween('date', [$this->from_date, $this->to_date]);
        })->get();
    }

    public function headings(): array
    {
        return [
            'Sl.No',
            'Employee Name/ID',
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
            $expense->employee->name . ' / ' . $expense->employee->emp_id,
            $expense->created_at ? $expense->created_at->format('d-m-Y h:i A') : '-',
            $expense->travel_method,
            $km,
            number_format($other, 2),
            $expense->remarks ?? '-',
            number_format($total, 2),
        ];
    }
}

