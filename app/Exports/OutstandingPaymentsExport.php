<?php

namespace App\Exports;

use App\Models\OutstandingNew;
use App\Models\Dealer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OutstandingPaymentsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
     public function collection()
    {
        $outstandings = OutstandingNew::all();
        $grouped = $outstandings->groupBy('dealer_id');

        $data = new Collection();
        $serialNo = 1;

        foreach ($grouped as $dealerId => $dealerOutstandings) {
            $dealer = Dealer::find($dealerId);

            $data->push([
                'S.No' => $serialNo++,
                'Dealer Code' => $dealer->dealer_code ?? 'N/A',
                'Dealer Name' => $dealer->dealer_name ?? 'N/A',
                'Outstanding Amount' => $dealerOutstandings->sum('outstanding_amount'),
            ]);
        }

        return $data;
    }


    public function headings(): array
    {
        return ['S.No', 'Dealer Code', 'Dealer Name', 'Outstanding Amount'];
    }
}

