<?php

namespace App\Exports;

use App\Models\InfluencerVisit;
use App\Models\ProductType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

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
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        return InfluencerVisit::with([
                'district',
                'order.dealer',
                'order.paymentTerm',
                'followUps',
                'order.orderItems',
                'createdBy'
            ])
            ->whereHas('createdBy', function ($q) use ($productID) {
                $q->whereJsonContains('products', (string) $productID);
            })
            ->get()
            ->map(function ($visit) {

                /**
                 * 🔑 SAME SORT DATE LOGIC AS API
                 */
                if ($visit->status === 'Follow Up') {
                    $sortDate = $visit->follow_up_date
                        ? Carbon::parse($visit->follow_up_date)
                        : $visit->updated_at;
                } else {
                    $sortDate = $visit->created_at;
                }
             $visit->_sort_date = $sortDate;
                return $visit;
            })
            ->filter(function ($visit) {

                if (!$visit->_sort_date) {
                    return false;
                }

                /**
                 * 📅 Month / Year filter (EXPORT)
                 */
                return $visit->_sort_date->year == $this->year
                    && $visit->_sort_date->month == $this->month;
            })
            ->sortByDesc('_sort_date')
            ->values();
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
            'Dealer (Won)',
            'Payment Terms',

            'Product Details',

            'Won Quantity', //notdone
            'Balance Quantity',
            'Total Order Amount',
            'Lost Volume',
            'Lost To Competitor',
            'Reason For Lost',
        ];
    }

    public function map($visit): array
    {
        $order = $visit->order;

        $productSummary = '';
        $totalOrderedQty = 0;
        $totalOrderAmount = 0;

        if ($order && $order->orderItems) {

            $productSummary = $order->orderItems->map(function ($item) use (&$totalOrderedQty, &$totalOrderAmount) {

                $details = collect($item->product_details)->map(function ($detail) use (&$totalOrderedQty, &$totalOrderAmount) {

                    $typeName = \App\Models\ProductType::find($detail['product_type_id'])->type_name ?? 'N/A';
                    $qty = $detail['quantity'] ?? 0;
                    $amt = $detail['quantity'] * $detail['rate'] ?? 0;
                    $rate = $detail['rate'] ?? 0;

                    // Add totals
                    $totalOrderedQty += $qty;
                    $totalOrderAmount += $amt;

                    return "{$typeName} (Qty: {$qty}, Amount: {$amt}, Rate: {$rate})";
                });

                return $details->implode(' | ');

            })->implode(' || ');
        }

        // Balance Quantity = Total Deal Volume - Ordered Quantity
        $balanceQty = $visit->total_deal_volume - $totalOrderedQty;

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

            $visit->total_deal_volume,              // TOTAL DEAL VOLUME (FROM DB)
            $visit->status,
            $visit->status === 'Follow Up' ? optional($visit->follow_up_date)->format('Y-m-d') : '',
            $visit->status === 'Follow Up' ? optional($visit->followUps->first())->reason : '',
            $visit->status === 'Won' ? optional(optional($order)->dealer)->dealer_name : '',
            $visit->status === 'Won' ? optional(optional($order)->paymentTerm)->name : '',
            $productSummary,
            
            
            $visit->status === 'Won' ? $visit->won_volume : '',
            // $totalOrderedQty,                       // TOTAL ORDERED QTY
            $balanceQty,                            // BALANCE QTY
            $totalOrderAmount,                      // TOTAL ORDER AMOUNT



            $visit->status === 'Lost' ? $visit->lost_volume : '',
            $visit->status === 'Lost' ? $visit->lost_to_competitor : '',
            $visit->status === 'Lost' ? $visit->reason_for_lost : '',
        ];
    }
}