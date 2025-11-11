<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CreditNote;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\ProductType;
use App\Models\OrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class FetchCreditNotes extends Command
{
    protected $signature = 'app:fetch-credit-notes';
    protected $description = 'Fetch Credit Notes from SAP using 2-month intervals';

    public function handle()
    {
	   Log::info('✅ Running FetchCreditNotes at ' . now());
        $start = Carbon::create(2021, 1, 1);
        $end = Carbon::now()->startOfMonth();

        try {
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('ODBC Connection Failed: ' . odbc_errormsg());
                return 1;
            }

            while ($start->lte($end)) {
                $fromDate = $start->copy()->format('Ymd');
                $toDate = $start->copy()->addMonths(2)->subDay()->format('Ymd');

                $this->info("Fetching Credit Notes from $fromDate to $toDate...");

                $sql = 'CALL "PRABHU_NEW"."MobileApp_CreditNote_Detail_Param"(\'' . $fromDate . '\', \'' . $toDate . '\')';
                $result = odbc_exec($conn, $sql);

                if (!$result) {
                    $this->error('ODBC Query Failed: ' . odbc_errormsg($conn));
                    $start->addMonths(2);
                    continue;
                }

                $response = [];
                while ($row = odbc_fetch_array($result)) {
                    $response[] = array_map('trim', $row);
                }

                if (empty($response)) {
                    $this->info("No credit note data received for $fromDate to $toDate.");
                    $start->addMonths(2);
                    continue;
                }

                DB::beginTransaction();

                $groupedData = [];
                foreach ($response as $data) {
                    $groupedData[$data['Credit Note Number']][] = $data;
                }

                foreach ($groupedData as $creditNoteNumber => $items) {
                    $invoiceNumber = trim($items[0]['AR Invoice Number'] ?? '');
                    $dealerCode = $items[0]['Customer Code'] ?? '';

                    $dealer = Dealer::where('dealer_code', $dealerCode)->first();

                    if (!$dealer) {
                        $this->warn("Dealer not found for credit note: $creditNoteNumber (Customer Code: $dealerCode)");
                        continue;
                    }

                    $order = null;

                    if ($invoiceNumber) {
                        $order = Order::where('invoice_number', $invoiceNumber)->first();

                        if (!$order) {
                            $order = Order::create([
                                'dealer_id' => $dealer->id,
                                'invoice_number' => $invoiceNumber,
                                'invoice_date' => $this->safeParseDate($items[0]['Date']),
                                'invoice_total' => $items[0]['Total'],
                                'status' => 'Delivered',
                            ]);
                        } else {
                            $order->update([
                                'invoice_date' => $this->safeParseDate($items[0]['Date']),
                                'invoice_total' => $items[0]['Total'],
                                'status' => 'Delivered',
                            ]);
                        }
                    }

                    $returnedItems = [];
                    $totalReturnQuantity = 0;
                    $totalRowAmount = 0;

                    foreach ($items as $item) {
                        $quantity = (float) $item['Quantity'];
                        $lineTotal = (float) $item['Total'];
                        $itemCode = $item['ItemCode'] ?? '';

                        $totalReturnQuantity += $quantity;
                        $totalRowAmount += $lineTotal;

                        if (empty($itemCode)) {
                            $this->warn("Missing product type for Credit Note $creditNoteNumber (ItemCode empty)");
                            continue;
                        }

                        $productType = ProductType::where('type_name', $itemCode)->first();

                        if (!$productType) {
                            $this->warn("Product type not found for ItemCode: " . $itemCode);
                            continue;
                        }

                        $returnedItems[] = [
                            'rate' => $lineTotal,
                            'quantity' => $quantity,
                            'product_type' => $itemCode,
                            'product_type_id' => $productType->id,
                            'totalAmount' => $lineTotal,
                        ];

                        if ($order) {
                            $productDetailsArray = [[
                                'product_type_id' => $productType->id,
                                'quantity' => $quantity,
                                'rate' => $lineTotal,
                                'typeName' => $itemCode,
                                'totalAmount' => $lineTotal,
                            ]];

                            $orderItem = OrderItem::where('order_id', $order->id)
                                ->whereJsonContains('product_details->0->product_type_id', $productType->id)
                                ->first();

                            if ($orderItem) {
                                $orderItem->update([
                                    'total_quantity' => $quantity,
                                    'balance_quantity' => 0,
                                    'product_details' => json_encode($productDetailsArray),
                                ]);
                            } else {
                                OrderItem::create([
                                    'order_id' => $order->id,
                                    'product_id' => 1,
                                    'total_quantity' => $quantity,
                                    'balance_quantity' => 0,
                                    'product_details' => json_encode($productDetailsArray),
                                ]);
                            }
                        }
                    }

                    CreditNote::updateOrCreate(
                        ['credit_note_number' => $creditNoteNumber],
                        [
                            'order_id' => $order ? $order->id : null,
                            'dealer_id' => $dealer->id,
                            'date' => $this->safeParseDate($items[0]['Date']),
                            'returned_items' => $returnedItems,
                            'total_return_quantity' => $totalReturnQuantity,
                            'total_row_amount' => $totalRowAmount,
                            'status' => ($items[0]['Status'] === 'C') ? 'closed' : 'open',
                        ]
                    );
                }

                DB::commit();
                $this->info("Credit Notes processed successfully.");
                $start->addMonths(2);
            }

            odbc_close($conn);
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($conn)) odbc_close($conn);
            $this->error("Error fetching credit notes: " . $e->getMessage());
        }
    }

    private function safeParseDate($value)
    {
        try {
            $cleaned = $this->cleanDateString($value);
            if (empty($cleaned)) {
                throw new \Exception("Cleaned date is empty");
            }
            return Carbon::parse($cleaned)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->warn("Invalid date: $value (cleaned: $cleaned)");
            return now()->format('Y-m-d');
        }
    }

    private function cleanDateString($value)
    {
        $value = preg_replace('/[^\d\-:\s\.]/', '', $value);
        if (strpos($value, '.') !== false) {
            $value = substr($value, 0, strpos($value, '.'));
        }
        return trim($value);
    }
}

