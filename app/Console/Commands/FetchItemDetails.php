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
use PhpParser\Node\Expr\Print_;

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

            $sql = 'select * from "PRABHU_NEW"."MOBILEAPP_ITEMDETAIL"';
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

            // $warehouses = Warehouse::pluck('id', 'warehouse_name')->toArray();

            foreach ($items as $item) {
                DB::beginTransaction();
                try {
                    $brand = trim($item['Type'] ?? '');
                    $brandCode = strtolower($brand); 

                    $product = \App\Models\Product::where('product_code', $brandCode)->first();

                    if (!$product) {
                        $this->error("❌ No product found for brand: {$brandCode}");
                        DB::rollBack();
                        continue; 
                    }

                    $product_id = $product->id;

                    $productTypeName = trim($item['Product Code'] ?? '');

                    $productType = ProductType::firstOrCreate(
                        ['type_name' => $productTypeName],
                        ['product_id' => $product_id] 
                    );

                    ProductDetails::updateOrCreate(
                        ['product_name' => trim($item['Product'])],
                        [
                            'product_id' => $product_id,
                            'item_profile' => $item['Item Profile'] ?? null,
                            'item_thickness' => $item['Item Thickness'] ?? null,
                            'type_id' => $productType->id,
                            'primary_group' => $item['Primary Group'] ?? null,
                            'weight' => $item['Weight'] ?? null,
                            'total_available_quantity' => 0,
                            'availability_status' => ($item['Availability Status'] ?? '') === 'Available' ? 'Available' : 'Unavailable',
                            'stock_updated_at' => Carbon::now(),
                            'rate' => 0,
                            'updated_at' => now(),
                        ]
                    );

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error('Database Error: ' . $e->getMessage());
                }
            }
            odbc_close($conn);
            $this->info('✅ Item details successfully updated from SAP.');

        } catch (\Exception $e) {
            if (isset($conn)) {
                odbc_close($conn);
            }
            $this->error('Error: ' . $e->getMessage());
        }
    }
}

