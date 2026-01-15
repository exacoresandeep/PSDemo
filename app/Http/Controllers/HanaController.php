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
            'invoice_date'   => 'required|date_format:d/m/Y',
        ]);

        $invoiceNumber = $request->invoice_number;
       // $invoiceDate   = Carbon::parse($request->invoice_date)->format('Ymd');
$invoiceDate = Carbon::createFromFormat('d/m/Y', $request->invoice_date)
                    ->format('Ymd');
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

            $rows = [];
            while ($row = odbc_fetch_array($result)) {
                $rows[] = array_map('trim', $row);
            }

            odbc_close($conn);

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No invoice data found'
                ], 404);
            }

            $first = $rows[0];
$branches = [];
 $cgstSum = 0;
            $sgstSum = 0;
            $igstSum = 0;
            $tcsSum  = 0;
            $totalQty = 0;
            $DiscTotal = 0;
            foreach ($rows as $row) {

                $branches[] = implode(', ', [
                    $row['Loc Street'] ?? '',
                    $row['Loc Block'] ?? '',
                    $row['Loc Building'] ?? '',
                    $row['Loc City'] ?? '',
                    $row['Loc State Name'] ?? '',
                    $row['Loc Zipcode'] ?? '',
		]);
		$cgstSum += (float) $row['CGSTAmount'];
                $sgstSum += (float) $row['SGSTAmount'];
                $igstSum += (float) $row['IGSTAmount'];
                $tcsSum  += (float) $row['TCSAmount'];
                $totalQty += (float) $row['U_AQty'];
                $DiscTotal += (float) $row['Disc Total'];
	    }
	    $cgstFreight = (float) $first['CGSTFreightAmt'];
            $sgstFreight = (float) $first['SGSTFreightAmt'];
            $igstFreight = (float) $first['IGSTFreightAmt'];
            $tcsFreight  = (float) $first['TCSFreightAmt'];

            $cgst = ($cgstSum / 4) + $cgstFreight;
            $sgst = ($sgstSum / 4) + $sgstFreight;
            $igst = ($igstSum / 4) + $igstFreight;

            $tcsAmount = $tcsSum - $tcsFreight;
            $docTotal = (float) $first['Doc Total'];
            $wtSum    = (float) $first['WTSum'];

            $gross = $docTotal + $wtSum;
            $roundedGross = round($gross, 2);

            $roundDiff = $gross - $roundedGross;
            $taxInvoice = $gross - $roundDiff;
            $payable = $docTotal;

            $invoice = [
                'company_name'            => $first['Company name'], //
                'company_address'         => $first['Company addr'], //
                'company_gst'             => $first['LocGSTN'], //
                'company_pan'             => $first['Loc PAN No'], //
                'company_state'           => $first['Loc State Name'], //
                'company_state_code'      => $first['LocStaGSTN'], //
                'company_email'      => $first['Company Email'], //
                'company_ph'      => $first['Company Ph'], //
		"branches" => $branches,

                'invoice_number'          => $first['DocNum'], //
                'invoice_date'            => Carbon::parse($first['DocDate'])->format('d-m-Y'), //

                'ref_no'           => $first['U_RefNo'], //
                'ref_date'           => $first['U_RefDate'], //

                'customer_code'           => $first['CardCode'], //
                'customer_name'    => $first['CardName'], //
                'customer_number'    => $first['Cust Mobile No'], //
                'customer_gst'     => $first['BpGSTN'], //
                'customer_pan'    => $first['BPPANNo'], //
                'billing_address'  => $first['BillToAddrs'], //
                'shipping_address' => $first['ShipToAddrs'], //
                'eway_bill_number' => $first['U_EWayBill'], //
                'eway_bill_date'   => Carbon::parse($first['U_EWayDate'])->format('d-m-Y'), //

                'vehicle_no'       => $first['U_VehicleNo'], //
                'transporter_name'       => $first['U_Transporter'], //
                'driver_number'       => $first['U_DriverNo'], //
                'payment_due_date'   => Carbon::parse($first['InvDueDate'])->format('d-m-Y'), //
                'payment_term'     => $first['PaymentTerm'], //
                'shipping_terms'     => $first['Shipping Term'], //
                'segment'     => $first['Segment'], //

                'remarks'     => $first['Comments'], //
                'qr_code'     => $first['QRCODE'], //
                'ack_no'     => $first['U_IRNAckNo'], //
                'ack_dt'     => $first['U_AckDt'], //
                'irn_no'     => $first['U_Irn'], //
                'qr_path'     => $first['U_IRNQRPath'], 
		"Branch" => $first['Branch'],
                "IFSC" => $first['IFSC'],
                "AccNo" => $first['AccNo'],
                "BankName" => $first['BankName'],
                "BankAcc" => $first['BankAcc'],
		'calculations' => [
                    'cgst' => round($cgst, 2),
                    'sgst' => round($sgst, 2),
                    'igst' => round($igst, 2),
                    'tcs_amount' => round($tcsAmount, 2),
                    'doc_total' => round($docTotal, 2),
                    'wt_sum' => round($wtSum, 2),
		    'round_diff' => round($roundDiff, 2),
		    "Freight" => $first['Freight'],
		    "DiscountTotal" => $DiscTotal,
                'doc_total'        => (float) $first['Doc Total'], //
                'round_off'        => (float) $first['RoundDif'], //

                    'tax_invoice' => round($taxInvoice, 2),
		    'payable' => round($payable, 2),
		    'payable_in_words' => $this->amountInWordsINR($payable),
                ]
            ];


            $items = [];

            foreach ($rows as $row) {
                $items[] = [
                    'item_code'   => $row['ItemCode'],
                    'description' => $row['Dscription'],
                    'hsn_code'    => $row['ChapterID'],
                    'U_AQty'    => $row['U_AQty'],
                    'quantity'    => (float) $row['Quantity'],
                    'uom'         => $row['UomCode'],
                    'unit_price'  => (float) $row['INRPrice'],
                    'line_total'  => (float) $row['Line Total'],
                    'cgst_rate' => (float) $row['CGSTRate'],
                    'cgst_amount' => (float) $row['CGSTAmount'],
                    'sgst_rate' => (float) $row['SGSTRate'],
                    'sgst_amount' => (float) $row['SGSTAmount'],
                    'igst_rate' => (float) $row['IGSTRate'],
                    'igst_amount' => (float) $row['IGSTAmount'],
                    'tcs_rate' => (float) $row['TCSRate'],
                    'tcs_amount' => (float) $row['TCSAmount'],
                    'discount' => (float) $row['Disc Total'],
                ];
            }


            return response()->json([
                'success'    => true,
                'statusCode'=> 200,
                'message'    => 'Invoice layout fetched successfully',
                'data'       => [
                'invoice' => $invoice,
                'items'   => $items,
               // "rowresult" => $rows
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    private function amountInWordsINR($amount)
    {
        if (!class_exists(\NumberFormatter::class)) {
            return '';
        }

        $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);

        $amount = round($amount, 2);

        $rupees = floor($amount);
        $paise  = round(($amount - $rupees) * 100);

        $words = ucfirst($formatter->format($rupees)) . ' rupees';

        if ($paise > 0) {
            $words .= ' and ' . $formatter->format($paise) . ' paise';
        }

        return $words . ' only';
    }
public function getCreditNoteForInvoice(Request $request)
    {
 $date           = $request->input('date');
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

    // Convert dd/mm/yyyy → yyyymmdd (SAP HANA safe)
    $dateParts = explode('/', $date);
    if (count($dateParts) !== 3) {
        return response()->json([
            'status'  => 'error',
            'code'    => 400,
            'message' => 'Invalid date format, expected dd/mm/YYYY',
        ], 400);
    }

    $date = $dateParts[2] . $dateParts[1] . $dateParts[0]; // 20210728

    $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');
    if (!$conn) {
        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'ODBC Connection Failed',
        ], 500);
    }

    try {
        // ✅ FIXED: credit note quoted + date format corrected
        $sql = 'CALL "PRABHU_NEW"."MobileApp_CreditNote_New_Param_v2"('
             . '\'' . $credit_note_no . '\', '
             . '\'' . $date . '\')';

        $result = odbc_exec($conn, $sql);

        $items = [];
        $data  = [];

        while ($row = odbc_fetch_array($result)) {
            $row = array_map('trim', $row);
            $data[] = $row;

            $quantity   = (float)$row["Quantity"] ?: 1;
            $unitTotal  = (float)$row["UnitPrice"] * $quantity;
            $lineTotal  = $unitTotal
                        + (float)$row["CGSTAmount"]
                        + (float)$row["SGSTAmount"]
                        + (float)$row["IGSTAmount"];

            $items[] = [
                "item_code"   => $row["ItemCode"],
                "item_name"   => $row["ItemName"],
                "quantity"    => (float)$row["Quantity"],
                "unit_price"  => (float)$row["UnitPrice"],
                "unit_total"  => $unitTotal,
                "line_total"  => $lineTotal,
                "cgst_rate"   => (float)$row["CGSTRate"],
                "cgst_amount" => (float)$row["CGSTAmount"],
                "sgst_rate"   => (float)$row["SGSTRate"],
                "sgst_amount" => (float)$row["SGSTAmount"],
                "igst_rate"   => (float)$row["IGSTRate"],
                "igst_amount" => (float)$row["IGSTAmount"],
            ];
        }

        if (empty($data)) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'No credit notes found.',
                'data'    => [],
            ], 200);
        }

        $first = $data[0];

        // Totals
        $subTotal  = array_sum(array_column($items, 'unit_total'));
        $cgstTotal = array_sum(array_column($items, 'cgst_amount'));
        $sgstTotal = array_sum(array_column($items, 'sgst_amount'));
        $igstTotal = array_sum(array_column($items, 'igst_amount'));

        $grossTotal = $subTotal + $cgstTotal + $sgstTotal + $igstTotal;

        $roundedTotal = ($grossTotal - floor($grossTotal) >= 0.5)
            ? ceil($grossTotal)
            : floor($grossTotal);

        $roundOff = $roundedTotal - $grossTotal;

        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($f->format($roundedTotal)) . " only";

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Credit notes fetched successfully.',
            'data'    => [
                "items"   => $items,
                "summary" => [
                    "sub_total"       => round($subTotal, 2),
                    "cgst_total"      => round($cgstTotal, 2),
                    "sgst_total"      => round($sgstTotal, 2),
                    "igst_total"      => round($igstTotal, 2),
                    "gross_total"     => round($grossTotal, 2),
                    "round_off"       => round($roundOff, 2),
                    "total"           => number_format($roundedTotal, 2, '.', ''),
                    "amount_in_words" => $amountInWords,
                    "remarks"         => $first["Remarks"] ?? null,
                ]
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }    
}

}
