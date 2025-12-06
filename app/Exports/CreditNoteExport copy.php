<?php

namespace App\Exports;

use App\Models\CreditNote;
use App\Models\Dealer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CreditNoteExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $month, $year;

    public function __construct($month, $year)
    {
        $this->month = $month + 1; // Convert 0-based to 1-based
        $this->year = $year;
    }

    public function collection()
    {
        $creditNotes = CreditNote::whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('status', 'open')
            ->with('dealer') // Eager load dealer to avoid N+1
            ->get();

        $data = new Collection();
         $serial = 1;

        foreach ($creditNotes as $note) {
            $data->push([
                'S.No'                => $serial++,
                'Dealer Code'         => $note->dealer->dealer_code ?? 'N/A',
                'Dealer Name'         => $note->dealer->dealer_name ?? 'N/A',
                'Credit Note Number'  => $note->credit_note_number,
                'Date'                => $note->date->format('Y-m-d'),
                'Returned Items'      => json_encode($note->returned_items),
                'Total Return Qty'    => $note->total_return_quantity,
                'Total Row Amount'    => $note->total_row_amount,
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
            'Credit Note Number',
            'Date',
            'Returned Items',
            'Total Return Qty',
            'Total Row Amount',
        ];
    }
}