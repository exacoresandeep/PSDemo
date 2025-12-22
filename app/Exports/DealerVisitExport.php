<?php

namespace App\Exports;

use App\Models\DealerVisit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DealerVisitExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $month;
    protected $year;
    protected $row = 1;

    public function __construct($month, $year)
    {
        $this->month = $month + 1; 
        $this->year = $year;
    }

    public function collection()
    {
        $productID= \App\Helpers\ProductHelper::getSelectedProductID();
        return DealerVisit::with(['dealer', 'aso', 'creator', 'order.orderItems'])
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
             ->whereHas('createdBy', function($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->get();
    }

    public function map($visit): array
    {
        $order = $visit->order;
        $productSummary = '';

        if ($order && $order->orderItems) {
            // Loop through each order item
            $productSummary = $order->orderItems->map(function ($item) {
                // Parse product_details (array of product info)
                $details = collect($item->product_details)->map(function ($detail) {
                    $productType = \App\Models\ProductType::find($detail['product_type_id']);
                    return $productType->type_name . ' (Qty: ' . $detail['quantity'] . ', Rate: ' . $detail['rate'] . ')';
                });

                return $details->implode(' | ');
            })->implode(' || '); // Separate multiple order items with double pipe
        }

        return [
            $this->row++,
            // $visit->created_at,
	        //$visit->created_at ? $visit->created_at->format('Y-m-d') : "NA",
            //$visit->created_at ? $visit->created_at->format('H:i:s') : "NA",
            $visit->created_at ? \Carbon\Carbon::parse($visit->created_at)->format('Y-m-d'):"NA",
            $visit->created_at ? \Carbon\Carbon::parse($visit->created_at)->format('H:i:s') : "NA",
            optional($visit->dealer)->dealer_name,
            optional($visit->dealer)->dealer_code,
            optional($visit->dealer)->district,
            optional($visit->aso)->name,
            $visit->purpose_of_visit,
            $visit->remarks,

            // Conditional fields
            $visit->purpose_of_visit === 'Gift/Marketing Activity' ? $visit->item_type : '',

            $visit->purpose_of_visit === 'Stock Taking' ? json_encode($visit->stock_details) : '',

            $visit->purpose_of_visit === 'Casual Visit' ? $visit->new_order : '',
            ($visit->purpose_of_visit === 'Casual Visit' && $visit->new_order === 'Yes') || $visit->purpose_of_visit === 'Order Taking'
                ? optional($order)->total_amount : '',
            // ($visit->purpose_of_visit === 'Casual Visit' && $visit->new_order === 'Yes') || $visit->purpose_of_visit === 'Order Taking'
            //     ? optional($order)->payment_date : '',
            $productSummary,
            optional($visit->creator)->name,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Date',
            'Time',           
            'Dealer Name',
            'Dealer Code',
	        'District',
	        'ASO Name',
            'Purpose of Visit',
            'Remarks',

            'Item Type (for Gift/Marketing)',
            'Stock Details (for Stock Taking)',

            'New Order (for Casual)',
            'Order Amount (for Order Taking / Casual + Yes)',
            // 'Payment Date (for Order Taking / Casual + Yes)',
            'Order Items',
            'Created By',
        ];
    }
}

