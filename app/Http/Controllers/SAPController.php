<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SapSalesOrderService;
use Carbon\Carbon;

class SAPController extends Controller
{
    protected $sapService;

    public function __construct(SapSalesOrderService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function sendSalesOrder(Request $request)
    {
        $validatedData = $request->validate([
            'CardCode' => 'required|string',
            'PaymentTerm' => 'required|string',
            'BillTo' => 'required|string',
            'ShipTo' => 'required|string',
            'SO_No' => 'required|string',
            'SO_Date' => 'required|date_format:d-m-Y',
            'Delivery_Date' => 'required|date_format:d-m-Y',
            'BPL_ID' => 'required|string',
            'Series' => 'required|string',
            'Details' => 'required|array|min:1',
            'Details.*.ItemCode' => 'required|string',
            'Details.*.Quantity' => 'required|numeric|min:1',
            'Details.*.Price' => 'required|numeric|min:0',
        ]);

        $response = $this->sapService->sendSalesOrder($validatedData);

        if ($response['success']) {
            return response()->json(['message' => 'Sales Order Sent Successfully!', 'data' => $response['data']]);
        } else {
            return response()->json(['error' => $response['error'], 'details' => $response['details']], 500);
        }
    }
}
