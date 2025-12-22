<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDO;
use PDOException;
use Carbon\Carbon;

class HanaController extends Controller
{
    private function connectToHana()
    {
        $dsn = 'odbc:HANAODBC';
        $username = 'INDUS';
        $password = 'Indus@123';

        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCreditNoteDetails()
    {
        return $this->executeProcedure('CALL "MOBILE_APPLICATION_TEST"."MobileApp_CreditNote_Detail"()');
    }

    public function getItemDetails()
    {
        return $this->executeProcedure('CALL "MOBILE_APPLICATION_TEST"."MobileApp_ItemDetail"()');
    }

    public function getOutstandingPayments()
    {
        return $this->executeProcedure('CALL "MOBILE_APPLICATION_TEST"."MobileApp_OutstandingPayment"()');
    }

    public function getDealerData()
    {
        return $this->executeProcedure('CALL "MOBILE_APPLICATION_TEST"."MobileApp_DealersDatas"()');
    }

    private function executeProcedure($query)
    {
        try {
            $pdo = $this->connectToHana();
            if (!($pdo instanceof PDO)) {
                return $pdo; // Return the error response if connection fails
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Data fetched successfully',
                'data' => $response
            ], 200);

        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Query execution failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function fetchInvoiceLayout(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'invoice_date'   => 'required|date',
        ]);

        $invoiceNumber = $request->invoice_number;

        $invoiceDate = Carbon::parse($request->invoice_date)->format('Ymd');

        try {
            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

            if (!$conn) {
                return response()->json([
                    'success' => false,
                    'message' => odbc_errormsg()
                ], 500);
            }

            $sql = sprintf(
                'CALL "PRABHU_NEW"."MobileApp_Invoice_Layout"(\'%s\', \'%s\')',
                $invoiceNumber,
                $invoiceDate
            );

            $result = odbc_exec($conn, $sql);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => odbc_errormsg($conn)
                ], 500);
            }

            $data = [];
            while ($row = odbc_fetch_array($result)) {
                $data[] = array_map('trim', $row);
            }

            odbc_close($conn);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Invoice layout fetched successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}

