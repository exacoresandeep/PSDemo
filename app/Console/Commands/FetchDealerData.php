<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dealer;
use App\Models\DealerAddress;
use App\Models\AssignRoute;
use App\Models\District;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FetchDealerData extends Command
{
    protected $signature = 'app:fetch-dealer-data';
    protected $description = 'Fetch and sync dealer data from SAP HANA';

    public function handle()
    {
        Log::info('✅ Running FetchDealerData ' . now());

        $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

        if (!$conn) {
            $this->error('❌ ODBC Connection Failed: ' . odbc_errormsg());
            return 1;
        }

     
        $productMap = DB::table('products')
            ->whereNotNull('sap_id')
            ->pluck('id', 'sap_id')
            ->toArray();

        if (empty($productMap)) {
            $this->error('❌ No SAP products found.');
            return 1;
        }

        $districts = DB::table('districts')->pluck('name')->toArray();
        $allData = [];

       
        foreach ($districts as $district) {
            foreach ($productMap as $sapId => $productId) {

                $sql = "CALL \"PRABHU_NEW\".\"MobileApp_Dealers_Type_param\"('$sapId', '$district')";
                $result = odbc_exec($conn, $sql);

                if (!$result) {
                    Log::error("❌ SAP Failed | SAP_ID: $sapId | District: $district | " . odbc_errormsg($conn));
                    continue;
                }

                while ($row = odbc_fetch_array($result)) {
                    $row = array_map('trim', $row);

                    $row['_product_id'] = (string) $productId;

                    $allData[] = $row;
                }
            }
        }

        if (empty($allData)) {
            $this->info('⚠️ No dealer data received from SAP.');
            return 0;
        }

        $groupedDealers = collect($allData)->groupBy(function ($item) {
            return strtoupper($item['Code']);
        });

        DB::beginTransaction();

        try {
            foreach ($groupedDealers as $dealerCode => $rows) {

                $first = $rows->first();

             
                $products = $rows
                    ->pluck('_product_id')
                    ->unique()
                    ->values()
                    ->toArray();

                $districtName = $first['district'] ?? null;
                $district = $districtName
                    ? District::where('name', $districtName)->first()
                    : null;

                $districtId = $district?->id;

                $assignedRouteId = null;
                $location = $first['location'] ?? null;

                if ($location) {
                    foreach (AssignRoute::all() as $route) {
                        $locations = array_map('trim', explode(',', $route->locations));
                        if (in_array($location, $locations, true)) {
                            $assignedRouteId = $route->id;
                            break;
                        }
                    }
                }

                $generatedPassword = 'D00' . $dealerCode;
                $hashedPassword = Hash::make($generatedPassword);

             
                $dealer = Dealer::updateOrCreate(
                    ['dealer_code' => $dealerCode],
                    [
                        'dealer_name'       => $first['Name'] ?? 'unknown',
                        'phone'             => $first['Phone'] ?? null,
                        'email'             => $first['Email'] ?? null,
                        'gst_no'            => $first['GST No.'] ?? null,
                        'pan_no'            => $first['PAN Number'] ?? null,
                        'status'            => ($first['Status'] === 'Active') ? '1' : '0',
                        'address'           => $first['Address'] ?? null,
                        'user_zone'         => $first['user_zone'] ?? null,
                        'pincode'           => $first['pincode'] ?? null,
                        'state'             => $first['state'] ?? null,
                        'district_id'       => $districtId,
                        'district'          => $districtName,
                        'assigned_route_id' => $assignedRouteId,
                        'location'          => $location,
                        'password'          => $hashedPassword,
                        'address_id'        => $first['AddressID'] ?? null,

                        'products'          => $products,
                    ]
                );

                foreach ($rows as $addr) {
                    DealerAddress::updateOrCreate(
                        [
                            'dealer_id'    => $dealer->id,
                            'address_type' => $addr['Address Type'] ?? 'Billing Address',
                        ],
                        [
                            'address' => $addr['Address'] ?? null,
                        ]
                    );
                }
            }

            DB::commit();
            $this->info('✅ Dealers synced successfully.');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error syncing dealer data: ' . $e->getMessage());
            Log::error($e);
            return 1;
        } finally {
            odbc_close($conn);
        }
    }
}
