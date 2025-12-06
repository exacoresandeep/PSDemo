<?php

namespace App\Exports;

use App\Models\OutstandingPayment;
use App\Models\Dealer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OutstandingPaymentsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $month;
    protected $year;
    protected $row = 1;

    public function __construct($month, $year)
    {
        // Month comes 0-indexed usually, so +1 like your other exports
        $this->month = $month + 1; 
        $this->year  = $year;
    }

    public function collection()
    {
        // Fetch outstanding payments based on month + year
        $outstandings = OutstandingPayment::with('dealer')
            ->whereYear('invoice_date', $this->year)
            ->whereMonth('invoice_date', $this->month)
            ->get();

        // Group by Dealer
        $grouped = $outstandings->groupBy('dealer_id');

        $data = new Collection();
        $serialNo = 1;

        foreach ($grouped as $dealerId => $dealerOutstanding) {

            $dealer = Dealer::find($dealerId);

            $data->push([
                'S.No'               => $serialNo++,
                'Dealer Code'        => $dealer->dealer_code ?? 'N/A',
                'Dealer Name'        => $dealer->dealer_name ?? 'N/A',
                'Total Invoices'     => $dealerOutstanding->count(),
                'Invoice Amount'     => $dealerOutstanding->sum('invoice_total'),
                'Paid Amount'        => $dealerOutstanding->sum('paid_amount'),
                'Outstanding Amount' => $dealerOutstanding->sum('outstanding_amount'),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Dealer Code',
            'Dealer Name',
            'Total Invoices',
            'Invoice Amount',
            'Paid Amount',
            'Outstanding Amount',
        ];
    }
}
