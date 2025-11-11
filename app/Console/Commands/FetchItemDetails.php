<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductDetails;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDO;
use PDOException;
use Illuminate\Support\Facades\Log;

class FetchItemDetails extends Command
{
    protected $signature = 'app:fetch-item-details';
    protected $description = 'Fetch item details from SAP and update products_details table';

    public function handle()
    {
	    Log::info('✅ Running FetchItemDetails at ' . now());
        try {
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('ODBC Connection Failed: ' . odbc_errormsg());
                return 1;
            }

            $sql = 'CALL "PRABHU_NEW"."MobileApp_ItemDetail"()';
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                $this->error('ODBC Query Failed: ' . odbc_errormsg($conn));
                return 1;
            }

            $items = [];
            while ($row = odbc_fetch_array($result)) {
                $items[] = array_map('trim', $row);
            }

            if (empty($items)) {
                $this->warn('No item details found in SAP response.');
                return 0;
            }

            $warehouses = Warehouse::pluck('id', 'warehouse_name')->toArray();

            foreach ($items as $item) {
                DB::beginTransaction();
                try {
                    $productTypeName = trim($item['Product Code'] ?? '');
                    $productType = ProductType::firstOrCreate(
                        ['type_name' => $productTypeName],
                        ['product_id' => 1]
                    );

                    $productDetails = ProductDetails::updateOrCreate(
                        ['product_name' => $item['Product']],
                        [
                            'product_id' => 1,
                            'item_profile' => $item['Item Profile'] ?? null,
                            'item_thickness' => $item['Item Thickness'] ?? null,
                            'type_id' => $productType->id,
                            'primary_group' => $item['Primary Group'] ?? null,
                            'total_available_quantity' => $item['Total Available Quantity'] ?? 0,
                            'availability_status' => ($item['Availability Status'] ?? '') === 'Available' ? 'Available' : 'Unavailable',
                            'stock_updated_at' => Carbon::now(),
                            'rate' => 0,
                            'updated_at' => now(),
                        ]
                    );

                    if ($productDetails) {
                        foreach ($warehouses as $warehouseName => $warehouseId) {
                            if (isset($item[$warehouseName])) {
                                $rawQty = $item[$warehouseName];
                                $cleanQty = preg_replace('/[^\d.-]/', '', trim($rawQty));
                                $stockQuantity = round((float)$cleanQty, 5);

                                ProductStock::updateOrInsert(
                                    [
                                        'product_details_id' => $productDetails->id,
                                        'warehouse_id' => $warehouseId,
                                    ],
                                    [
                                        'quantity' => $stockQuantity,
                                        'updated_at' => now(),
                                    ]
                                );
                            }
                        }
                    } else {
                        $this->error("❌ Error: ProductDetails not found for product '{$item['Product']}'");
                    }

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    $this->error('Database Error: ' . $e->getMessage());
                }
            }

            odbc_close($conn);
            $this->info('✅ Item details successfully updated from SAP.');

        } catch (Exception $e) {
            if (isset($conn)) {
                odbc_close($conn);
            }
            $this->error('Error: ' . $e->getMessage());
        }
    }
}

