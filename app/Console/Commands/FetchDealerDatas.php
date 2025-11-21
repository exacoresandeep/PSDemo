<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dealers;
use App\Models\DealerAddresses;
use App\Models\District;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FetchDealerDatas extends Command
{
    protected $signature = 'app:fetch-dealer-datas';
    protected $description = 'Fetch and sync dealer data from SAP HANA';

    public function handle()
    {
        Log::info('✅ Running FetchDealerData ' . now());

        $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

        if (!$conn) {
            $this->error('ODBC Connection Failed: ' . odbc_errormsg());
            return 1;
        }

        $districts = DB::table('districts')->pluck('name')->toArray();

        $products = Product::pluck('product_name')->toArray();

        $allData = [];
        foreach ($products as $productName) {
            foreach ($districts as $district) {

                $sql = "CALL \"PRABHU_NEW\".\"MobileApp_Dealers_Type_param\"('$productName','$district')";
                $result = odbc_exec($conn, $sql);

                if (!$result) {
                    Log::error("❌ SAP HANA Query Failed for product: $productName, district: $district. Error: " . odbc_errormsg($conn));
                    continue;
                }

                echo "-------------------------------------------\n";
                echo "PRODUCT: $productName | DISTRICT: $district\n";
                echo "-------------------------------------------\n";

                while ($row = odbc_fetch_array($result)) {
                    print_r($row);
                    echo "-------------------------------------------\n";
                    $allData[] = array_map('trim', $row);
                }
            }
        }

        if (empty($allData)) {
            $this->info('No dealer data received from SAP.');
            return 0;
        }

        $groupedDealers = collect($allData)->groupBy(function ($item) {
            return strtoupper($item['Code']);
        });

        DB::beginTransaction();

        try {
            foreach ($groupedDealers as $dealerCode => $addresses) {

                $first = $addresses->first();
                $districtName = $first['district'] ?? null;

                $district = $districtName
                    ? District::where('name', $districtName)->first()
                    : null;

                $districtId = $district?->id;

                $productId = Product::where('product_name', $first['product'])->value('id');

                $dealer = Dealers::where('dealer_code', $dealerCode)->first();

                $existingProductIds = [];

                if ($dealer && $dealer->product_id) {
                    $existingProductIds = is_array($dealer->product_id)
                        ? $dealer->product_id
                        : explode(',', $dealer->product_id);
                }

                $newProductIds = array_unique(array_merge($existingProductIds, [$productId]));

                $dealer = Dealers::updateOrCreate(
                    ['dealer_code' => $dealerCode],
                    [
                        'dealer_name' => $first['Name'] ?? 'unknown',
                        'phone' => $first['Phone'] ?? null,
                        'email' => $first['Email'] ?? null,
                        'gst_no' => $first['GST No.'] ?? null,
                        'pan_no' => $first['PAN Number'] ?? null,
                        'status' => ($first['Status'] === 'Active') ? '1' : '0',
                        'address' => $first['Address'] ?? null,
                        'user_zone' => $first['user_zone'] ?? null,
                        'pincode' => $first['pincode'] ?? null,
                        'state' => $first['state'] ?? null,
                        'district_id' => $districtId,
                        'district' => $districtName,
                        'location' => $first['location'] ?? null,
                        'password' => Hash::make('D00' . $dealerCode),
                        'address_id' => $first['AddressID'] ?? null,

                        'product_id' => json_encode($newProductIds),
                    ]
                );

                foreach ($addresses as $addr) {
                    $addressType = strtolower(trim($addr['Address Type'] ?? 'Billing Address'));
                    $addressType = ucwords($addressType); 
                    DealerAddresses::updateOrCreate(
                        [
                            'dealer_id' => $dealer->id,
                            'address_type' => $addressType,
                        ],
                        [
                            'address' => $addr['Address'] ?? null,
                        ]
                    );
                }
            }

            DB::commit();

            $this->info('Dealers synced successfully.');
            return 0;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Error syncing dealer data: ' . $e->getMessage());
            return 1;
        }
    }
}
