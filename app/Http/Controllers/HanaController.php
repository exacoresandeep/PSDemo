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

    public function getCreditNoteForInvoice(Request $request)
    {
        $date = $request->input('date');
        $credit_note_no = $request->input('credit_note_no');
        if (empty($credit_note_no)) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Credit Note No is required',
            ], 400);
        }
        if (empty($date)) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Date parameter is required',
            ], 400);
        }
        
        $dateParts = explode('/', $date);
        if (count($dateParts) === 3) {
            $date = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0]; // 2025-09-22
        } else {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Invalid date format, expected dd/mm/YYYY',
            ], 400);
        }
        $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
        if (!$conn) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'ODBC Connection Failed',
            ], 500);
        }

        try {
            $sql = 'CALL "PRABHU_NEW"."MobileApp_CreditNote_New_Param_v2"(' . $credit_note_no . ', \'' . $date . '\')';
            $result = odbc_exec($conn, $sql);
            $items = [];
            $data = [];

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);
                $data[] = $row;
                $quantity = (float)$row["Quantity"] == 0 ? 1 : (float)$row["Quantity"];

                $unit_total = (float)$row["UnitPrice"] * $quantity;

                $line_total = $unit_total + (float)$row["CGSTAmount"] + (float)$row["SGSTAmount"] + (float)$row["IGSTAmount"];
                $items[] = [
                    "item_code"   => $row["ItemCode"],
                    "item_name"   => $row["ItemName"],
                    "quantity"    => (float)$row["Quantity"],
                    "unit_price"  => (float)$row["UnitPrice"],
		            "unit_total"  => $unit_total,
                    "line_total"  => $line_total,
                    "cgst_rate"   => (float)$row["CGSTRate"],
                    "cgst_amount" => (float)$row["CGSTAmount"],
                    "sgst_rate"   => (float)$row["SGSTRate"],
                    "sgst_amount" => (float)$row["SGSTAmount"],
                    "igst_rate"   => (float)$row["IGSTRate"],
                    "igst_amount" => (float)$row["IGSTAmount"]
                ];
            }

            if (empty($data)) {
                return response()->json([
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => "No credit notes found for date $date.",
                    'data'    => [],
                ], 200);
            }

            $first = $data[0];

            // ---- Calculate summary ----
            $subTotal   = array_sum(array_column($items, 'line_total'));
            $cgstTotal  = array_sum(array_column($items, 'cgst_amount'));
            $sgstTotal  = array_sum(array_column($items, 'sgst_amount'));
            $igstTotal  = array_sum(array_column($items, 'igst_amount'));

            $discountPercent = 0.00;
            $discountAmount  = 0.00;

            $grossTotal = $subTotal + $cgstTotal + $sgstTotal + $igstTotal - $discountAmount;

            // Apply custom rounding rule (.50 and above rounds up)
            if (($grossTotal - floor($grossTotal)) >= 0.5) {
                $roundedTotal = ceil($grossTotal);
            } else {
                $roundedTotal = floor($grossTotal);
            }

            $roundOff = $roundedTotal - $grossTotal;


            $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
            $amountInWords = ucfirst($f->format($roundedTotal)) . " only";

            // ---- Build final response ----
            $allData = [
                "company" => [
                    "company_name"    => "Prabhu Steels",
                    "company_address" => "VI/953, Pookattupady Road, Thrikkakara P.O, Cochin-21",
                    "pan"             => "AADFP1492J",
                    "email"           => "sap@prabhusteels.com",
                    "phone"           => "04842575933",
                    "gst_no"          => "32AADFP1492J1ZA",
                    "gst_type"        => "Regular/TDS/ISD"
                ],
                "qr_code" => $first["QRCODE"],
                "credit_memo" => [
                    "number"          => $first["SeriesName"]."/".$first["Credit Memo Number"],
                    "date"            => $first["Date"],
                    "customer_ref_no" => null,
                    "ack_no"        => $first["Ack No"],
                    "ack_date"        => $first["U_AckDt"],
                    "irn_no"          => $first["IRNNo"]
                ],
                "customer_details" => [
                    "customer_name"    => $first["Customer Name"],
                    "customer_code"    => $first["Customer Code"],
                    "billing_address"  => $first["Billing Address"],
                    "delivery_address" => $first["DeliveryAddress"],
                    "gst_no"           => $first["GST No"]
                ],
                "eway_bill" => [
                    "number" => $first["U_EWayBill"],
                    "date"   => $first["U_EWayDate"]
                ],
                "original_reference" => [
                    "number" => $first["U_EWayBill"],
                    "date"   => $first["U_RefDate"]
                ],
                "contact_details" => [
                    "name"           => "Praise",
                    "contact_number" => null,
                    "email_id"       => "sreejith@prahusteels.com"
                ],
                //"gst_no" => $first["GST No"],
                "items"  => $items,
                "summary" => [
                    "sub_total"        => round($subTotal, 2),
                    "cgst_total"       => round($cgstTotal, 2),
                    "sgst_total"       => round($sgstTotal, 2),
                    "igst_total"       => round($igstTotal, 2),
                    "gross_total"      => round($grossTotal, 2),
                    "discount_percent" => $discountPercent,
                    "discount_amount"  => $discountAmount,
                    "round_off"        => round($roundOff, 2),
                    "total"            => number_format($roundedTotal, 2, '.', ''),
                    "amount_in_words"  => $amountInWords,
                    "remarks"          => $first["Remarks"]
                ]
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Credit notes fetched successfully.',
                'data'    => $allData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
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

