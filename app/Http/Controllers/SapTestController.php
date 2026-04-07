<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SapController extends Controller
{
    
    public function fetchOutstanding(Request $request)
    {
        $fromDate = $request->from_date; // format: Ymd
        $toDate   = $request->to_date;   // format: Ymd
        $sapId    = $request->sap_id;

        $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

        if (!$conn) {
            return response()->json([
                'status' => false,
                'message' => 'ODBC Connection Failed'
            ]);
        }

        $sql = "CALL \"PRABHU_NEW\".\"MobileApp_OutstandingPayment_Param_F\"(
            '$fromDate',
            '$toDate',
            $sapId
        )";
        $result = odbc_exec($conn, $sql);

        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => odbc_errormsg($conn)
            ]);
        }

        $response = [];
        while ($row = odbc_fetch_array($result)) {
            $response[] = $row;
        }

        odbc_close($conn);

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }

    private function callSapProcedure($procedure, array $params)
    {
        $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

        if (!$conn) {
            throw new \Exception('SAP Connection Failed: ' . odbc_errormsg());
        }

        $paramString = "'" . implode("','", $params) . "'";

        $sql = "CALL \"PRABHU_NEW\".\"{$procedure}\"($paramString)";

        $result = odbc_exec($conn, $sql);

        if (!$result) {
            $error = odbc_errormsg($conn);
            odbc_close($conn);
            throw new \Exception("SAP Query Failed: $error");
        }

        $data = [];
        while ($row = odbc_fetch_array($result)) {
            $data[] = array_map('trim', $row);
        }

        odbc_close($conn);

        return $data;
    }
    
    public function downloadLedger(Request $request)
    {
        try {

            $request->validate([
                'from_date'  => 'required|date',
                'to_date'    => 'required|date',
                'dealer_code'=> 'required',
                'sap_id'     => 'required'
            ]);

            $response = $this->callSapProcedure(
                '@DealerStatements_F',
                [
                    $request->from_date,
                    $request->to_date,
                    $request->dealer_code,
                    $request->sap_id
                ]
            );

            return response()->json([
                'status' => true,
                'count'  => count($response),
                'data'   => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error'  => $e->getMessage()
            ], 500);
        }
    }
}

