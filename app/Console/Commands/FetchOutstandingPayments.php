<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Dealer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductType;
use App\Models\OutstandingPayment;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class FetchOutstandingPayments extends Command
{
    protected $signature = 'app:fetch-outstanding-payments';
    protected $description = 'Fetch and store outstanding payments from SAP HANA';

    private function cleanDateString($value)
    {
        $cleaned = preg_replace('/[^\x20-\x7E]/', '', $value);
        $cleaned = preg_replace('/\.\d+.*/', '', $cleaned);
        return trim($cleaned);
    }

    private function safeParseDate($value)
    {
        try {
            $cleaned = $this->cleanDateString($value);
            return Carbon::parse($cleaned)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->warn("Invalid date: $value");
            return null;
        }
    }

    public function handle()
    {
     Log::info('✅ Running FetchOutstandingPayments at ' . now());

        try {
            $start = Carbon::createFromFormat('Ymd', '20210101');
            $end = Carbon::now();
            
            $products = Product::whereNotNull('sap_id')->get();

            if ($products->isEmpty()) {
                $this->error('No products with sap_id found.');
                return 1;
            }

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('ODBC Connection Failed: ' . odbc_errormsg());
                return 1;
            }

            while ($start < $end) {
                $fromDate = $start->copy()->format('Ymd');
                $toDate = $start->copy()->addMonths(2)->format('Ymd');

                $sql = "CALL \"PRABHU_NEW\".\"MobileApp_OutstandingPayment_Param\"('$fromDate', '$toDate')";
		        $result = odbc_exec($conn, $sql);
		
                if (!$result) {
                    $this->warn("ODBC Query Failed for range $fromDate - $toDate: " . odbc_errormsg($conn));
                    $start->addMonths(2);
                    continue;
                }

                $response = [];
                while ($row = odbc_fetch_array($result)) {
                    $response[] = array_map('trim', $row);
                }
                if (empty($response)) {
                    $this->warn("No data found for range $fromDate - $toDate");
                    $start->addMonths(2);
                    continue;
                }

                $groupedData = collect($response)->groupBy('Invoice Number');
                DB::beginTransaction();

                foreach ($groupedData as $invoiceNumber => $items) {
                    $first = $items->first();
                    $dealer = Dealer::where('dealer_code', $first['Customer Code'])->first();
                    if (!$dealer) {
                        $this->warn("Dealer not found for code: " . $first['Customer Code']);
                        continue;
                    }

                    $order = Order::where('dealer_id', $dealer->id)
                        ->where('invoice_number', $invoiceNumber)
                        ->first();

                    if (!$order) {
                        $order = Order::create([
                            'dealer_id' => $dealer->id,
                            'invoice_number' => $invoiceNumber,
                            'invoice_date' => $this->safeParseDate($first['Invoice Date']),
                            'invoice_quantity' => $first['Quantity'],
                            'invoice_total' => $first['Invoice Total'],
                            'status' => 'Delivered',
                        ]);

                        foreach ($items as $item) {
                            $productType = ProductType::where('type_name', $item['ItemCode'])->first();
                            $product_type_id = $productType ? $productType->id : null;

                            OrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => 1,
                                'total_quantity' => (float) ($item['Quantity'] ?? 0),
                                'balance_quantity' => 0,
                                'product_details' => [
                                    [
                                        'product_type_id' => $product_type_id,
                                        'quantity' => (float) ($item['Quantity'] ?? 0),
                                        'rate' => (float) $item['LineTotal'],
                                        'typeName' => $item['ItemCode'],
                                        'totalAmount' => (float) ($item['LineTotal'] ?? 0),
                                    ]
                                ],
                            ]);
                        }
                    }

                    OutstandingPayment::updateOrCreate(
                        ['invoice_number' => $invoiceNumber],
                        [
                            'dealer_id' => $dealer->id,
                            'order_id' => $order->id,
                            'invoice_total' => $first['Invoice Total'],
                            'invoice_date' => $this->safeParseDate($first['Invoice Date']),
                            'due_date' => $this->safeParseDate($first['Due Date']),
                            'paid_amount' => $first['Paid Amount'],
                            'outstanding_amount' => $first['Outstanding Amount'],
                            'status' => ($first['Status'] === 'C') ? 'closed' : 'open',
                        ]
                    );

                    foreach ($items as $item) {
                        if (!empty($item['Payment Doc Number']) && !empty($item['Payment Amount Applied'])) {
                            $existingPayment = Payment::where('invoice_number', $invoiceNumber)
                                ->where('payment_document_no', $item['Payment Doc Number'])
                                ->exists();

                            if (!$existingPayment) {
                                Payment::create([
                                    'order_id' => $order->id,
                                    'dealer_id' => $dealer->id,
                                    'invoice_number' => $invoiceNumber,
                                    'payment_document_no' => $item['Payment Doc Number'],
                                    'payment_date' => $this->safeParseDate($item['Payment Date']),
                                    'invoice_date' => $this->safeParseDate($item['Invoice Date']),
                                    'payment_amount' => $item['Payment Amount Applied'],
                                ]);
                            }
                        }
                    }
                }

                DB::commit();
                $this->info("Completed processing for $fromDate to $toDate");

                $start->addMonths(2);
            }

            odbc_close($conn);
            $this->info('All outstanding payments fetched and stored successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            if (isset($conn)) {
                odbc_close($conn);
            }
            $this->error('Error: ' . $e->getMessage());
        }
    }
}

