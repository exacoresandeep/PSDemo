<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Target;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AashiyanaOrdersExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $year, $month,$employee_type_id, $row = 1;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];
    protected $product_id;
    public function __construct($year, $month,$employee_type_id,$product_id)
    {
        $this->year = $year;
        $this->month = $month;
        $this->employee_type_id = $employee_type_id;
        $this->product_id = $product_id;
    }

    public function collection()
    {
        $productID= $this->product_id;
        $orders = Order::with(['lead', 'dealer', 'createdBy'])
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->when($this->employee_type_id != "", function ($query) {
                $query->whereHas('createdBy', function ($q) {
                    $q->where('employee_type_id', $this->employee_type_id);
                });
            })
            ->where('product_id', $productID) //push
            ->where('payment_terms_id', 3)
            ->get();

        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('aashiyana', 'employee_id')
            ->where('product_id', $productID)//push
            ->toArray(); 
        foreach ($orders as $order) {
            $empId = $order->created_by;
            if ($empId) {
                if (!isset($this->achievedByEmployee[$empId])) {
                    $this->achievedByEmployee[$empId] = 0;
                }
                $this->achievedByEmployee[$empId] += 1; 
            }
        }

        return $orders;
    }

    public function map($order): array
    {
         $createdByDealerCode = '';
        $createdByDealerName = '';

        if ($order->dealer_flag_order == 1 && $order->dealers) {
            $createdByDealerCode = $order->dealers->dealer_code;
            $createdByDealerName = $order->dealers->dealer_name;
        }
        
         $empId = $order->created_by;
        $target = $this->targetsByEmployee[$empId] ?? 0;
        $achieved = $this->achievedByEmployee[$empId] ?? 0;

        return [
            $this->row++,
            // $order->invoice_number,
            $order->total_amount,
            optional($order->lead)->customer_name,
            optional($order->dealer)->dealer_code,
            optional($order->dealer)->dealer_name,
            optional($order->createdBy)->name,
            $order->created_at->format('Y-m-d'),
            $createdByDealerCode,
            $createdByDealerName,
            $target,
            $achieved,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            // 'Invoice No',
            'Invoice Total',
            'Lead Customer',
            'Dealer Code',
            'Dealer Name',
            'Created By',
            'Created Date',
            'Created By ID',
            'Created By Name',
            'Target',
            'Achieved',
        ];
    }
}
