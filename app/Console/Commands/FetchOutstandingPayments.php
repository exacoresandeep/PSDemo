<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

use App\Models\Dealer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\OutstandingPayment;
use App\Models\Payment;

class FetchOutstandingPayments extends Command
{
    protected $signature = 'app:fetch-outstanding-payments';
    protected $description = 'Fetch and store outstanding payments from SAP HANA (Product wise)';

    /* -------------------- Helpers -------------------- */

    private function cleanDateString($value)
    {
        if (empty($value)) return null;

        $cleaned = preg_replace('/[^\x20-\x7E]/', '', $value);
        $cleaned = preg_replace('/\.\d+.*/', '', $cleaned);

        return trim($cleaned);
    }

    private function safeParseDate($value)
    {
        try {
            if (empty($value)) return null;
            return Carbon::parse($this->cleanDateString($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }

    /* -------------------- Command -------------------- */

    public function handle()
    {
        Log::info('FetchOutstandingPayments started');

        try {
            $start = Carbon::createFromFormat('Ymd', '20210101');
            $end   = Carbon::now();

            $products = Product::whereNotNull('sap_id')->get();
            if ($products->isEmpty()) {
                $this->error('No products with sap_id found');
                return 1;
            }

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('ODBC Connection Failed');
                return 1;
            }

            while ($start < $end) {

                $fromDate = $start->format('Ymd');
                $toDate   = $start->copy()->addMonths(2)->format('Ymd');

                foreach ($products as $product) {

                    $this->info("Processing {$product->product_name} ($fromDate → $toDate)");

                    $sql = "CALL \"PRABHU_NEW\".\"MobileApp_OutstandingPayment_Param_F\"(
                        '$fromDate',
                        '$toDate',
                        {$product->sap_id}
                    )";

                    $result = odbc_exec($conn, $sql);
                    if (!$result) {
                        Log::error(odbc_errormsg($conn));
                        continue;
                    }

                    $response = [];
                    while ($row = odbc_fetch_array($result)) {
                        $response[] = array_map('trim', $row);
                    }

                    if (!count($response)) continue;

                    $this->processData($response, $product);
                }

                $start->addMonths(2);
            }

            odbc_close($conn);
            $this->info('Outstanding payments sync completed');

        } catch (Exception $e) {
            Log::error($e);
            $this->error($e->getMessage());
        }
    }

    /* -------------------- Processor -------------------- */

    private function processData(array $response, Product $product)
    {
        $groupedInvoices = collect($response)->groupBy('Invoice Number');

        DB::beginTransaction();

        try {

            foreach ($groupedInvoices as $invoiceNumber => $rows) {

                $first = $rows->first();

                $dealer = Dealer::where('dealer_code', $first['Customer Code'])->first();
                if (!$dealer) continue;

                /* -------------------- Order -------------------- */

                $order = Order::updateOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'dealer_id'        => $dealer->id,
                        'product_id'       => $product->id,
                        'invoice_date'     => $this->safeParseDate($first['Invoice Date']),
                        'invoice_total'    => $this->cleanNumber($first['Invoice Total']),
                        'invoice_quantity' => $this->cleanNumber($first['Quantity']),
                        'status'           => 'Delivered',
                    ]
                );

                /* -------------------- Order Items (JSON STRUCTURE) -------------------- */

                $productDetails = [];
                $totalQuantity = 0;

                foreach ($rows as $item) {

                    $productType = ProductType::where('product_id', $product->id)
                        ->where('type_name', $item['ItemCode'])
                        ->first();

                    $qty = $this->cleanNumber($item['Quantity']);
                    $lineTotal = $this->cleanNumber($item['LineTotal']);

                    $totalQuantity += $qty;

                    $productDetails[] = [
                        'product_type_id' => $productType?->id,
                        'quantity'        => $qty,
                        'rate'            => $lineTotal,
                        'typeName'        => $item['ItemCode'],
                        'totalAmount'     => $lineTotal,
                    ];
                }

                // OrderItem::updateOrCreate(
                //     [
                //         'order_id'   => $order->id,
                //         'product_id' => $product->id,
                //     ],
                //     [
                //         'total_quantity'   => $totalQuantity,
                //         'balance_quantity' => 0,
                //         'product_details'  => $productDetails, // ✅ JSON stored
                //     ]
                // );
                $orderItem = OrderItem::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if ($orderItem) {
                    $orderItem->update([
                        'total_quantity'   => $totalQuantity,
                        'balance_quantity' => 0,
                        'product_details'  => $productDetails,
                    ]);
                } else {
                    OrderItem::create([
                        'order_id'         => $order->id,
                        'product_id'       => $product->id,
                        'total_quantity'   => $totalQuantity,
                        'balance_quantity' => 0,
                        'product_details'  => $productDetails,
                    ]);
                }

                /* -------------------- Outstanding -------------------- */

                OutstandingPayment::updateOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'dealer_id'          => $dealer->id,
                        'order_id'           => $order->id,
                        'invoice_total'      => $this->cleanNumber($first['Invoice Total']),
                        'invoice_date'       => $this->safeParseDate($first['Invoice Date']),
                        'due_date'           => $this->safeParseDate($first['Due Date']),
                        'paid_amount'        => $this->cleanNumber($first['Paid Amount']),
                        'outstanding_amount' => $this->cleanNumber($first['Outstanding Amount']),
                        'status'             => $first['Status'] === 'C' ? 'closed' : 'open',
                    ]
                );

                /* -------------------- Payments -------------------- */

                foreach ($rows as $item) {

                    if (!empty($item['Payment Doc Number']) &&
                        $this->cleanNumber($item['Payment Amount Applied']) > 0) {

                        Payment::updateOrCreate(
                            [
                                'invoice_number'      => $invoiceNumber,
                                'payment_document_no' => $item['Payment Doc Number'],
                            ],
                            [
                                'order_id'       => $order->id,
                                'dealer_id'      => $dealer->id,
                                'payment_date'   => $this->safeParseDate($item['Payment Date']),
                                'invoice_date'   => $this->safeParseDate($item['Invoice Date']),
                                'payment_amount' => $this->cleanNumber($item['Payment Amount Applied']),
                            ]
                        );
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
