<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDO;
use PDOException;

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
}

