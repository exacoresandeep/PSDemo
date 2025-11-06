<?php

namespace App\Exports;

use App\Models\Lead;
use App\Models\Target;
use App\Models\Employee;
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
        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('unique_lead', 'employee_id')
            ->toArray();

        $this->achievedByEmployee = Lead::whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->selectRaw('created_by, COUNT(*) as total')
            ->groupBy('created_by')
            ->pluck('total', 'created_by')
            ->toArray();

        return Lead::with("district")->with(['createdBy', 'followUps', 'orders.orderItems','customerType', 'assignRoute'])
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get();
    }

    public function map($lead): array
    {
        $followUpDate = null;
        $followUpReason = null;

        if ($lead->status === 'Follow Up' && $lead->followUps->isNotEmpty()) {
            $latestFollowUp = $lead->followUps->last();
            $followUpDate = $latestFollowUp->follow_up_date;
            $followUpReason = $latestFollowUp->reason;
        }

        $orderDetails = null;
        if ($lead->status === 'Won' && $lead->orders->isNotEmpty()) {
            $firstOrder = $lead->orders->first();
            $orderDetails = 'Amount: ' . $firstOrder->total_amount . ', Items: ' . $firstOrder->orderItems->count();
        }

        $lostDetails = null;
        if ($lead->status === 'Lost') {
            $lostDetails = 'Volume: ' . $lead->lost_volume . ', Competitor: ' . $lead->lost_to_competitor . ', Reason: ' . $lead->reason_for_lost;
        }
        $employeeId = $lead->created_by;

        $target = $this->targetsByEmployee[$employeeId] ?? 0;
        $achieved = $this->achievedByEmployee[$employeeId] ?? 0;

        return [
            $this->row++,
            $lead->customer_name,
    	    optional($lead->customerType)->name,
    	    $lead->phone,
            $lead->city,
            $lead->address,
    	    $lead->district->name,
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
            $lead->total_quantity,
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
            'Customer Name',
    	    'Customer Type',
    	    'Phone',
            'City',
            'Address',
            'District','Location',
            'Route',
            'Type of Visit',
            'Construction Type',
            'Stage of Construction',
            'Follow Up Date',
            'Follow Up Reason',
            'Lead Score',
            'Lead Source',
            'Source Name',
            'Total Quantity',
            'Status',
            'Created By',
            'Target',
            'Achieved',
            'Created Date',
            'Order Details (if Won)',
            'Lost Details (if Lost)',
        ];
    }
}

