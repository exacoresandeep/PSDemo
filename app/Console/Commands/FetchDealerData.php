<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dealer;
use App\Models\DealerAddress;
use App\Models\AssignRoute;
use App\Models\District;
use PDO;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FetchDealerData extends Command
{
     //	Log::info('✅ Running FetchDealerData ' . now());
    protected $signature = 'app:fetch-dealer-data';
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

    $allData = [];

    foreach ($districts as $district) {
        $sql = "CALL \"PRABHU_NEW\".\"MobileApp_DealersDatas_District_param\"('$district')";
        $result = odbc_exec($conn, $sql);

        if (!$result) {
            Log::error("SAP HANA Query Failed for district: $district. Error: " . odbc_errormsg($conn));
            continue;
        }

        while ($row = odbc_fetch_array($result)) {
		    $allData[] = array_map('trim', $row);
		//$row = array_map('trim', $row);
		//print_r($allData); // This will print each row on the console
//		$row = array_map('trim', $row);
//    $allData[] = $row;
//		    $this->line("Row fetched: " . json_encode($row));
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

            $location = $first['location'] ?? null;

           // $assignedRoute = $location
           //     ? AssignRoute::whereRaw("FIND_IN_SET(?, REPLACE(locations, ' ', ''))", [$location])->first()
            //    : null;
		$assignedRoute = null;

            if ($location) {
                $routes = AssignRoute::all(); // or add other where filters if needed

                foreach ($routes as $route) {
                    $locationList = array_map('trim', explode(',', $route->locations)); // split + trim spaces
                    if (in_array($location, $locationList, true)) {
                        $assignedRoute = $route;
                        break;
                    }
                }
            }
            $assignedRouteId = $assignedRoute?->id;
	
	    $generatedPassword = 'D00' . $dealerCode;
            $hashedPassword = Hash::make($generatedPassword);

            $dealer = Dealer::updateOrCreate(
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
                    'district' => $districtName ?? null,
                    'assigned_route_id' => $assignedRouteId,
                    'location' => $first['location'] ?? null,
		    'password' => $hashedPassword,
		    'address_id' => $first['AddressID'] ?? null,
                ]
            );
	
            foreach ($addresses as $addr) {
                DealerAddress::updateOrCreate(
                    [
                        'dealer_id' => $dealer->id,
                        'address_type' => $addr['Address Type'] ?? 'Billing Address',
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
    } catch (\Exception $e) {
        DB::rollBack();
        $this->error('Error syncing dealer data: ' . $e->getMessage());
        return 1;
    }
}

}
