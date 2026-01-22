<?php

namespace App\Exports;

use App\Models\Lead;
use App\Models\Target;
use App\Models\Employee;
use App\Models\OrderItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UniqueLeadsExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
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
        $productID= \App\Helpers\ProductHelper::getSelectedProductID(); //push

        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->where('product_id', $productID)//push
            ->pluck('unique_lead', 'employee_id')
            ->toArray();

        $this->achievedByEmployee = Lead::whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->selectRaw('created_by, COUNT(*) as total')
            ->groupBy('created_by')
            ->whereHas('orders', function ($query) use ($productID) {  //push
                                $query->where('product_id', $productID);
                            })
            
            
            ->pluck('total', 'created_by')
            ->toArray();

        return Lead::with("district")->with(['createdBy', 'followUps', 'orders.orderItems','customerType', 'assignRoute'])
            ->whereYear('created_at', $this->year)
            ->whereHas('orders', function ($query) use ($productID) {  //push
                                $query->where('product_id', $productID);
                            })
            ->groupBy('phone')                
            ->whereMonth('created_at', $this->month)
            ->get()->unique('phone')->values();
    }
    protected function getTotalDealVolume($lead)
    {
        if (!$lead->lead_chain_id) return 0;

        $firstLead = Lead::where('lead_chain_id', $lead->lead_chain_id)
            ->orderBy('created_at', 'asc')
            ->first();

        return $firstLead->total_quantity ?? 0;
    }

    protected function getChainOrderedQuantity($lead)
    {
        if (!$lead->lead_chain_id) return 0;

        return OrderItem::whereHas('order', function($q) use ($lead) {
            $q->whereHas('lead', function($sub) use ($lead) {
                $sub->where('lead_chain_id', $lead->lead_chain_id);
            });
        })->sum('total_quantity');
    }

    public function map($lead): array
    {
        /** FOLLOW UP DETAILS */
        $followUpDate   = null;
        $followUpReason = null;

        if ($lead->status === 'Follow Up' && $lead->followUps->isNotEmpty()) {
            $lastFU = $lead->followUps->last();
            $followUpDate   = $lastFU->follow_up_date;
            $followUpReason = $lastFU->reason;
        }

        /** ORDER DETAILS (PRODUCT-WISE) */
        $orderDetails = null;
        $totalOrderQty = 0;
        $totalOrderAmount = 0;

        if ($lead->orders->isNotEmpty()) {

            $details = [];

            foreach ($lead->orders as $order) {

                $totalOrderAmount += $order->total_amount;

                foreach ($order->orderItems as $item) {

                    // Loop product_details array
                    if (!empty($item->product_details)) {
                        foreach ($item->product_details as $pd) {

                            $typeName  = $pd['type_name'] ?? 'N/A';
                            $quantity  = $pd['quantity'] ?? 0;
                            $amount    = $pd['rate'] ?? 0;

                            $totalOrderQty += $quantity;

                            $details[] = "{$typeName}: Qty {$quantity} - Rate {$amount}";
                        }
                    }
                }
            }

            $orderDetails = implode(" | ", $details);
        }

        /** LOST DETAILS */
        $lostDetails = null;
        if ($lead->status === 'Lost') {
            $lostDetails =
                "Volume: {$lead->lost_volume}, " .
                "Competitor: {$lead->lost_to_competitor}, " .
                "Reason: {$lead->reason_for_lost}";
        }

        /** TARGET & ACHIEVED */
        $employeeId = $lead->created_by;

        $target   = $this->targetsByEmployee[$employeeId] ?? 0;
        $achieved = $this->achievedByEmployee[$employeeId] ?? 0;

        /** TOTAL DEAL VOLUME */
        $totalDealVolume = $this->getTotalDealVolume($lead);

        /** CHAIN ORDERED QUANTITY */
        $chainOrderedQuantity = $this->getChainOrderedQuantity($lead);

        /** BALANCE */
        $balanceQuantity = $totalDealVolume - $chainOrderedQuantity;

         return [
            $this->row++,
             $lead->id,
            $lead->customer_name,
            optional($lead->customerType)->name,
            $lead->phone,
            $lead->city,
            $lead->address,
            optional($lead->district)->name,
            $lead->location,
            optional($lead->assignRoute)->locations,
            $lead->type_of_visit,
            $lead->construction_type,
            $lead->stage_of_construction,
            $followUpDate,
            $followUpReason,
            $lead->lead_score,
            $lead->lead_source,
            $lead->source_name,

            /** NEW FIELDS */
            $totalDealVolume,
            $totalOrderQty,
            $totalOrderAmount,
            // $balanceQuantity,

            $lead->status,
            optional($lead->createdBy)->name,
            $target,
            $achieved,
            $lead->created_at->format('Y-m-d'),

            $orderDetails,
            $lostDetails,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Lead id',
            'Customer Name',
            'Customer Type',
            'Phone',
            'City',
            'Address',
            'District',
            'Location',
            'Route',
            'Type of Visit',
            'Construction Type',
            'Stage of Construction',
            'Follow Up Date',
            'Follow Up Reason',
            'Lead Score',
            'Lead Source',
            'Source Name',

            'Total Deal Volume',
            'Total Ordered Quantity',
            'Total Order Amount',
            // 'Balance Quantity',

            'Status',
            'Created By',
            'Target',
            'Achieved',
            'Created Date',

            'Order Details (Product Wise)',
            'Lost Details',
        ];
    }
}

