<?php

namespace App\Exports;

use App\Models\Lead;
use App\Models\OrderItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LeadsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return Lead::with([
            'customerType',
            'district',
            'assignRoute',
            'createdBy',
            'orders.orderItems',
            'orders.dealer',
            'orders.paymentTerm',
            'followUps'
	])
	->whereHas('createdBy', function($q) use ($productID) {
            $q->whereJsonContains('products', (string)$productID);
	})
	//->where(function ($q) {
        //    $q->where('status', '!=', 'Follow Up')
        //    ->orWhere(function ($q2) {
        //        $q2->where('status', 'Follow Up')
       //             ->whereNotNull('follow_up_date');
       //     });
      //  })
        ->whereYear('updated_at', $this->year)
        ->whereMonth('updated_at', $this->month)
        ->get();
    }

    public function map($lead): array
    {
        $order = $lead->orders->first();
        //       $dealerName = optional($order->dealer)->name ?? '';
        //       $paymentTerm = optional($order->paymentTerm)->name ?? '';
        //$dealerName  = $order ? optional($order->dealer)->name : '';
	    $dealerName  = $order ? optional($order->dealer)->dealer_name : '';
      	$paymentTerm = $order ? optional($order->paymentTerm)->name : '';

        // Order Item

        $orderItem = $order && $order->orderItems->isNotEmpty()
            ? $order->orderItems->first()
	    : null;
        $productName  = $orderItem ? optional($orderItem->product)->product_name : '';
        $previousBrandQty = optional($lead)->previous_brand_quantity ?? '';

     	//   $productType  = $orderItem->product_details['type'] ?? '';  // if stored in JSO
	$productType = '';

if ($orderItem && !empty($orderItem->product_details)) {
    $productType = collect($orderItem->product_details)
        ->pluck('type_name')
        ->implode(',');
}
        //$productType = $orderItem && $orderItem->product 
        // ? optional($orderItem->product->productTypes->first())->type_name 
        //: '';
       	$quantity     = $orderItem->total_quantity ?? '';
	$totalDealVolume = $this->getTotalDealVolume($lead);
        $chainOrderedQuantity = $this->getChainOrderedQuantity($lead);
        $bal_quantity = max(0, $totalDealVolume - $chainOrderedQuantity);
        // $price        = $orderItem->product_details['price'] ?? '';
        //$price = $orderItem && $orderItem->product 
        //  ? optional($orderItem->product->productTypes->first())->rate 
        // : '';

	    $price = $order ? $order->total_amount : '';
        $latestFollowUp = $lead->followUps->sortByDesc('follow_up_date')->first();

        $newFollowUpDate = $latestFollowUp?->follow_up_date ?? '';

        $newFollowUpReason = optional($latestFollowUp)->reason ?? '';

        return [
            $this->row++,
            optional($lead->created_at)->format('Y-m-d'),
            optional($lead->created_at)->format('H:i:s'),
            optional($lead->customerType)->name,
            $lead->customer_name,
            $lead->address,
            $lead->city,
            $lead->location,
            $lead->phone,
            optional($lead->district)->name,
            optional($lead->assignRoute)->locations,
            $lead->type_of_visit,
            $lead->construction_type,
            $lead->stage_of_construction,
            $lead->follow_up_date ?? '',

            $lead->lead_score,
            $lead->lead_source,
            $lead->source_name,
            $lead->total_quantity,
            $lead->status,
            $newFollowUpDate,
            $newFollowUpReason,
            $dealerName,
            $paymentTerm,
            $productName,  
            $productType,  
	    $quantity,
	    $bal_quantity,
            $price,    
            $lead->status === 'Lost' ? $lead->lost_volume : '',
            $lead->status === 'Lost' ? $lead->lost_to_competitor : '',
            $lead->status === 'Lost' ? $lead->reason_for_lost : '',
	        $lead->previous_brand,$lead->brand_name,
            $previousBrandQty,
            $lead->customer_meet ?? '',
            $lead->ring_test ?? '',
            $lead->further_requirement ?? '',
            $lead->further_volume ?? '',
            optional($lead->createdBy)->name,
	    //$lead->created_at,
	    optional($lead->updated_at)->format('Y-m-d'),
            optional($lead->updated_at)->format('H:i:s'),
        ];
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
    public function headings(): array
    {
        return [
		    'Sl.No',
	       	'Date',
            'Time',
            'Customer Type',
            'Customer Name',
            'Address',
            'City',
            'Location',
            'Phone',
            'District',
            'Routes',
            'Type of Visit',
            'Construction Type',
            'Stage of Construction',
            'Follow Up Date',
            'Lead Score',
            'Source',
            'Source Name',
            'Total Lead Volume',
            'Status',
            'New Follow up Date',
            'Reason for Change',
            'Assign to Dealers',
            'Payment Terms',
            'Product',
            'Product Type',
            'Won Quantity',
	    'Balance Quantity',
	    'Price',
            'Lost Volume',
            'Lost To Competitor',
            'Reason For Lost','Previous Brand',
            'Others',
        	'Previous Quantity',
            'Consumer Meet Conducted',
            'Ring Test Conducted',
            'Further Requirement',
            'Further Volume',
	    'Created By',
	     'Updated Date',
            'Updated Time'
        ];
    }
}
