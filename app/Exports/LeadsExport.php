<?php

namespace App\Exports;

use App\Models\Lead;
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
        ->whereYear('created_at', $this->year)
        ->whereMonth('created_at', $this->month)
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
     	//   $productType  = $orderItem->product_details['type'] ?? '';  // if stored in JSON
       $productType = $orderItem && $orderItem->product 
        ? optional($orderItem->product->productTypes->first())->type_name 
        : '';
       	$quantity     = $orderItem->total_quantity ?? '';
        // $price        = $orderItem->product_details['price'] ?? '';
        //$price = $orderItem && $orderItem->product 
        //  ? optional($orderItem->product->productTypes->first())->rate 
        // : '';
	    $price = $order ? $order->total_amount : '';
         $latestFollowUp = $lead->followUps->sortByDesc('follow_up_date')->first();

        $newFollowUpDate = optional($latestFollowUp)->follow_up_date
            ? optional($latestFollowUp->follow_up_date)->format('Y-m-d')
            : 'NA';
    
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
            //routes
            $lead->type_of_visit,
            $lead->construction_type,
            $lead->stage_of_construction,
            $lead->follow_up_date 
                ? \Carbon\Carbon::parse($lead->follow_up_date)->format('Y-m-d') 
                : 'NA',

            $lead->lead_score,
            $lead->lead_source,
            $lead->source_name,
            $lead->total_quantity,
            $lead->status,
            $newFollowUpDate,
            $newFollowUpReason,
            $dealerName,
            $paymentTerm,
            $productName,   // ✅ Product Name
            $productType,   // Product Type (from product_details JSON)
            $quantity,
            $price,          // Lost-only fields
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
        ];
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
            'Created By'
        ];
    }
}

