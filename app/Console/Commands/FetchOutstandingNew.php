<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dealer;
use App\Models\OutstandingNew;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class FetchOutstandingNew extends Command 
{
    protected $signature = 'app:fetch-outstanding-new'; 
    protected $description = 'Fetch and store outstanding payments from SAP HANA';

    public function handle()
    {
	    Log::info('✅ Running FetchOutstandingNew at ' . now());
        $this->info("Starting Outstanding Payment Sync...");

        try {
            // Connect to HANA
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('ODBC Connection Failed: ' . odbc_errormsg());
                return 1;
            }

            // Get all dealers with dealer_code
            $dealers = Dealer::whereNotNull('dealer_code')->get();
	    
	    foreach ($dealers as $dealer) {
                $dealerCode = $dealer->dealer_code;
                $sql = "CALL \"PRABHU_NEW\".\"@CustomerBalance\"('{$dealerCode}')";
                $result = odbc_exec($conn, $sql);
//$this->info("Dealer code---".$dealerCode);  
                if (!$result) {
                    $this->error("Query failed for Dealer Code: {$dealerCode}");
                    continue;
       }
                while ($row = odbc_fetch_array($result)) {
                    $data = array_map('trim', $row);

                     if (isset($data['ShortName']) && isset($data['Balance'])) {
                        $matchedDealer = $dealers->firstWhere('dealer_code', $data['ShortName']);

                        if ($matchedDealer) {
                             OutstandingNew::updateOrCreate(
                                ['dealer_id' => $matchedDealer->id],
				[
     				'outstanding_amount' => $data['Balance'],
                  		 'due_balance' => (!empty($data['DueBalance']) || $data['DueBalance'] === 0) 
                            ? $data['DueBalance'] 
			    : 0
				]
                            );
Log::info('✅ FetchOutstandingNew fetched and stored successfully at ' . now());
                            $this->info("Stored outstanding for Dealer: {$data['ShortName']} - Amount: {$data['Balance']}");
                        } else {
                            $this->warn("No dealer found for ShortName: {$data['ShortName']}");
                        }
                    }
                }
            }

            odbc_close($conn);
            $this->info('All outstanding payments fetched and stored successfully.');
            return 0;

        } catch (Exception $e) {
            if (isset($conn)) {
                odbc_close($conn);
            }
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

