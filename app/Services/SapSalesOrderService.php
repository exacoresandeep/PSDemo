<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class SapSalesOrderService
{
protected $sapApiUrl;

    public function __construct()
    {
        $this->sapApiUrl = config('services.sap.api_url'); // Store URL in config/services.php
    }

    public function sendSalesOrder(array $orderData)
    {
        try {
		$response = Http::timeout(60)->post("{$this->sapApiUrl}/api/SalesOrderDetails", $orderData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::error('SAP Sales Order Error:', $response->json());
                return [
                    'success' => false,
                    'error' => 'SAP API request failed',
                    'details' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('SAP API Exception:', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Exception occurred while sending Sales Order',
                'details' => $e->getMessage()
            ];
        }
    }
}
