<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FetchInvoiceLayouts extends Command
{
    protected $signature = 'app:fetch-invoice-layouts';
    protected $description = 'Fetch and display invoice details from SAP HANA (for debugging only)';

    public function handle()
    {
        Log::info('✅ Running FetchInvoiceLayouts at ' . now());
        $this->info('✅ Running FetchInvoiceLayouts at ' . now());

        try {
            $start = Carbon::createFromFormat('Ymd', '20210101');
            $end = Carbon::now();

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
            if (!$conn) {
                $this->error('❌ ODBC Connection Failed: ' . odbc_errormsg());
                Log::error('ODBC Connection Failed: ' . odbc_errormsg());
                return 1;
            }

            while ($start < $end) {
                $fromDate = $start->copy()->format('Ymd');
                $toDate = $start->copy()->addMonths(2)->format('Ymd');

                $sql = 'CALL "PRABHU_NEW"."MobileApp_Invoice_Layout"(\'10263018\', \'20250804\')';
                $result = odbc_exec($conn, $sql);

                if (!$result) {
                    $this->warn("⚠️ ODBC Query Failed for range $fromDate - $toDate: " . odbc_errormsg($conn));
                    Log::warning("ODBC Query Failed for range $fromDate - $toDate: " . odbc_errormsg($conn));
                    $start->addMonths(2);
                    continue;
                }

                $response = [];
                while ($row = odbc_fetch_array($result)) {
                    $response[] = array_map('trim', $row);
                }

                $this->info("🧾 SAP Invoice Layout Response for range $fromDate - $toDate");
                if (empty($response)) {
                    $this->warn("No data found for range $fromDate - $toDate");
                } else {
                    $this->line(json_encode($response, JSON_PRETTY_PRINT));

                    Log::info("🧾 SAP Invoice Layout Response", [
                        'range' => "$fromDate - $toDate",
                        'count' => count($response),
                        'sample' => array_slice($response, 0, 5)
                    ]);
                }

                $this->info("✅ Displayed invoice layout response successfully.");
                break; 
            }

            odbc_close($conn);
            $this->info('✅ SAP connection closed.');

        } catch (Exception $e) {
            if (isset($conn)) {
                odbc_close($conn);
            }
            Log::error('❌ Error in FetchInvoiceLayouts: ' . $e->getMessage());
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}

