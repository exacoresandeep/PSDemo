<?php

namespace App\Exports;

use App\Models\InfluencerVisit;
use App\Models\ProductType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InfluencerVisitExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return InfluencerVisit::with(['district', 'order.dealer', 'order.paymentTerm', 'followUps', 'order.orderItems'])
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get();
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Date',
            'Time',
            'Influencer Name',
            'Phone',
            'Place',
            'Influencer Type',
            'Visit Type',
            'Purpose',
            'District',
            'Lead Type',
            'Current Project',
            'Upcoming Project',
            'Steel Used',
            'Total Deal Volume',
            'Status',

            'Follow Up Date',
            'Follow Up Reason',

            'Lost Volume',
            'Lost To Competitor',
            'Reason For Lost',

            'Dealer (Won)',
            'Payment Terms',
            'Credit Days',
            'Order Total Amount',

            'Product Details',
            'Created By',
            'Created At',
        ];
    }

    public function map($visit): array
    {
        $order = $visit->order;
        $productSummary = '';

        if ($order && $order->orderItems) {
            $productSummary = $order->orderItems->map(function ($item) {
                $details = collect($item->product_details)->map(function ($detail) {
                    $productType = \App\Models\ProductType::find($detail['product_type_id']);
                    return $productType->type_name . ' (Qty: ' . $detail['quantity'] . ', Rate: ' . $detail['rate'] . ')';
                });

                return $details->implode(' | ');
            })->implode(' || '); 
        }

        return [
            $this->row++,
            optional($visit->created_at)->format('Y-m-d'),
            optional($visit->created_at)->format('H:i:s'),
            $visit->influencer_name,
            $visit->phone,
            $visit->place,
            $visit->influencer_type,
            $visit->visit_type,
            $visit->purpose,
            optional($visit->district)->name,
            $visit->lead_type,
            $visit->current_project,
            $visit->upcoming_project,
            is_array($visit->steel_used) ? implode(', ', $visit->steel_used) : '',
            $visit->total_deal_volume,
            $visit->status,

            $visit->status === 'Follow Up' ? optional($visit->follow_up_date)->format('Y-m-d') : '',
            $visit->status === 'Follow Up' ? optional($visit->followUps->first())->reason : '',

            $visit->status === 'Lost' ? $visit->lost_volume : '',
            $visit->status === 'Lost' ? $visit->lost_to_competitor : '',
            $visit->status === 'Lost' ? $visit->reason_for_lost : '',

            $visit->status === 'Won' ? optional($order->dealer)->dealer_name : '',
            $visit->status === 'Won' ? optional($order->paymentTerm)->name : '',
            $visit->status === 'Won' ? $order->credit_days : '',
            $visit->status === 'Won' ? $order->total_amount : '',

            $productSummary,
            optional($visit->creator)->name ?? '',
            optional($visit->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}