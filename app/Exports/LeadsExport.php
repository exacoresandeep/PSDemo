<?php

namespace App\Exports;

use App\Models\Lead;
use App\Models\OrderItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class LeadsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    protected $month;
    protected $year;
    protected $row = 1;

    // Preloaded chain totals
    protected array $chainTotals = [];

    public function __construct($month, $year)
    {
        $this->month = $month + 1;
        $this->year  = $year;

        // Preload chain totals to avoid N+1 queries
        $this->chainTotals = OrderItem::selectRaw(
                'leads.lead_chain_id, SUM(order_items.total_quantity) as ordered_qty'
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('leads', 'leads.id', '=', 'orders.lead_id')
            ->whereNotNull('leads.lead_chain_id')
            ->groupBy('leads.lead_chain_id')
            ->pluck('ordered_qty', 'lead_chain_id')
            ->toArray();
    }

    /**
     * Use query instead of collection for streaming
     */
    public function query()
    {
        $productID = \App\Helpers\ProductHelper::getSelectedProductID();

        return Lead::query()
            ->with([
                'customerType',
                'district',
                'assignRoute',
                'createdBy',
                'orders.orderItems.product',
                'orders.dealer',
                'orders.paymentTerm',
                'followUps' => function ($q) {
                    $q->latest('follow_up_date')->limit(1);
                }
            ])
            ->whereHas('createdBy', function ($q) use ($productID) {
                $q->whereJsonContains('products', (string)$productID);
            })
            ->whereYear('updated_at', $this->year)
            ->whereMonth('updated_at', $this->month);
    }

    /**
     * Chunk size for memory-efficient export
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Map each row to Excel
     */
    public function map($lead): array
    {
        $order = $lead->orders->first();

        $dealerName  = $order ? optional($order->dealer)->dealer_name : '';
        $paymentTerm = $order ? optional($order->paymentTerm)->name : '';

        $orderItem = $order && $order->orderItems->isNotEmpty()
            ? $order->orderItems->first()
            : null;

        $productName = $orderItem ? optional($orderItem->product)->product_name : '';
        $previousBrandQty = optional($lead)->previous_brand_quantity ?? '';

        // Product Type
        $productType = '';
        if ($orderItem && !empty($orderItem->product_details)) {
            $productType = collect($orderItem->product_details)
                ->pluck('type_name')
                ->implode(',');
        }

        $quantity = $orderItem->total_quantity ?? '';
        $totalDealVolume = $lead->lead_chain_id
            ? Lead::where('lead_chain_id', $lead->lead_chain_id)
                ->oldest()
                ->value('total_quantity') ?? 0
            : 0;

        $chainOrderedQuantity = $this->chainTotals[$lead->lead_chain_id] ?? 0;
        $bal_quantity = max(0, $totalDealVolume - $chainOrderedQuantity);

        $price = $order ? $order->total_amount : '';

        $latestFollowUp = $lead->followUps->first();
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
            $lead->previous_brand,
            $lead->brand_name,
            $previousBrandQty,
            $lead->customer_meet ?? '',
            $lead->ring_test ?? '',
            $lead->further_requirement ?? '',
            $lead->further_volume ?? '',
            optional($lead->createdBy)->name,
            optional($lead->updated_at)->format('Y-m-d'),
            optional($lead->updated_at)->format('H:i:s'),
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
            'Balance Quantity',
            'Price',
            'Lost Volume',
            'Lost To Competitor',
            'Reason For Lost',
            'Previous Brand',
            'Others',
            'Previous Quantity',
            'Consumer Meet Conducted',
            'Ring Test Conducted',
            'Further Requirement',
            'Further Volume',
            'Created By',
            'Updated Date',
            'Updated Time',
        ];
    }
}
