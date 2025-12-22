<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\ProductStock;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function stockList(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
    
        $latitude  = $request->latitude;
        $longitude = $request->longitude;
    
        $nearestWarehouse = Warehouse::select(
            'id',
            'warehouse_name',
            'latitude',
            'longitude',
            DB::raw("
                ( 6371 * acos( cos( radians($latitude) ) * cos( radians(latitude) ) 
                * cos( radians(longitude) - radians($longitude) ) + sin( radians($latitude) ) 
                * sin( radians(latitude) ) ) ) AS distance
            ")
        )
        ->orderBy('distance', 'ASC')
        ->first();
    
        if (!$nearestWarehouse) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No warehouse found nearby.',
            ], 404);
        }
    
        $stockItems = ProductStock::with(['productDetails.product', 'productDetails.productType'])
            ->where('warehouse_id', $nearestWarehouse->id)
            ->get();
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Stock list fetched successfully.",
            'data' => [
                'warehouse' => [
                    'id'            => $nearestWarehouse->id,
                    'name'          => $nearestWarehouse->warehouse_name,
                    'latitude'      => $nearestWarehouse->latitude,
                    'longitude'     => $nearestWarehouse->longitude,
                    'distance_km'   => round($nearestWarehouse->distance, 2),
                ],
                'stocks' => $stockItems->map(function ($stock) {
                    $stockQuantity = (float) $stock->quantity;
        
                    $availabilityStatus = 'Out of Stock';
                    if ($stockQuantity > 0 && $stockQuantity < 10) {
                        $availabilityStatus = 'Low Stock';
                    } elseif ($stockQuantity >= 10) {
                        $availabilityStatus = 'In Stock';
                    }
        
                    return [
                        'product_details_id'  => $stock->product_details_id,
                        'product_name'        => $stock->productDetails->product_name,
                        'product_type'        => optional($stock->productDetails->productType)->type_name,
                        'item_profile'        => $stock->productDetails->item_profile,
                        'item_thickness'      => $stock->productDetails->item_thickness,
                        'primary_group'       => $stock->productDetails->primary_group,
                        'total_available_qty' => $stock->productDetails->total_available_quantity,
                        'stock_quantity'      => number_format($stockQuantity, 5, '.', ''),
                        'availability_status' => $availabilityStatus,
                    ];
                }),
            ],
        ], 200);
    }

    // public function stockDetails(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'product_item' => 'required|string',
    //             'search_key'   => 'nullable|string|min:3'
    //         ]);

    //         $productItem = Product::where("product_code",$request->product_item)->value('sap_id');
    //         $searchKey   = $request->search_key ?? '';  

    //         $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

    //         if (!$conn) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'SAP Connection Failed: ' . odbc_errormsg()
    //             ], 500);
    //         }

    //         if (!empty($searchKey) && strlen($searchKey) >= 1) {
    //             $sql = "CALL \"PRABHU_NEW\".\"Mobile_App_GetStock\"('$productItem', '$searchKey')";
    //         } else {
    //             $sql = "CALL \"PRABHU_NEW\".\"Mobile_App_GetStock\"('$productItem','')";
    //         }
    //         $result = odbc_exec($conn, $sql);

    //         if (!$result) {
    //             odbc_close($conn);
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'SAP Query Failed: ' . odbc_errormsg()
    //             ], 500);
    //         }

    //        $stockData = [];

    //         while ($row = odbc_fetch_array($result)) {

    //             $row = array_map('trim', $row);

    //             foreach ($row as $key => $value) {
    //                 $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    //             }

    //             $qty = isset($row['OnHand']) ? floatval($row['OnHand']) : 0;

    //             if ($qty > 10) {
    //                 $status = "In-Stock";
    //             } elseif ($qty >= 1 && $qty <= 10) {
    //                 $status = "Low Stock";
    //             } else {
    //                 $status = "Out of Stock";
    //             }

    //             $row['status'] = $status;

    //             $stockData[] = $row;
    //         }


    //         odbc_close($conn);

    //         return response()->json([
    //             'status' => 'success',
    //             'statusCode' => 200,
    //             'message' => 'Stock fetched successfully',
    //             'data' => $stockData
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Internal Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function stockDetails(Request $request)
    {
        try {
            $request->validate([
                'product_item' => 'required|string',
                'search_key'   => 'nullable|string'
            ]);

            $sapId = Product::where('product_code', $request->product_item)
                            ->value('sap_id');

            if (!$sapId) {
                return response()->json([
                    'status' => 'error',
                    'statusCode' => 404,
                    'message' => 'Invalid product item'
                ], 404);
            }

            $searchKey = trim($request->search_key ?? '');

            $conn = odbc_connect('HANAODBC', 'INDUS', 'Indus@123');

            if (!$conn) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'SAP Connection Failed: ' . odbc_errormsg()
                ], 500);
            }

            $sql = "CALL \"PRABHU_NEW\".\"Mobile_App_GetStock\"($sapId, '$searchKey')";
            $result = odbc_exec($conn, $sql);

            if (!$result) {
                odbc_close($conn);
                return response()->json([
                    'status' => 'error',
                    'message' => 'SAP Query Failed: ' . odbc_errormsg()
                ], 500);
            }

            $stockData = [];

            while ($row = odbc_fetch_array($result)) {
                $row = array_map('trim', $row);

                foreach ($row as $key => $value) {
                    $row[$key] = mb_convert_encoding(
                        $value,
                        'UTF-8',
                        'UTF-8, ISO-8859-1, Windows-1252'
                    );
                }

                $qty = isset($row['OnHand']) ? (float)$row['OnHand'] : 0;

                if ($qty > 10) {
                    $status = "In-Stock";
                } elseif ($qty >= 1) {
                    $status = "Low Stock";
                } else {
                    $status = "Out of Stock";
                }

                $row['status'] = $status;
                $stockData[] = $row;
            }

            if (empty($stockData) && $searchKey !== '') {

                $sql = "CALL \"PRABHU_NEW\".\"Mobile_App_GetStock\"($sapId, '')";
                $result = odbc_exec($conn, $sql);

                while ($row = odbc_fetch_array($result)) {
                    $row = array_map('trim', $row);

                    foreach ($row as $key => $value) {
                        $row[$key] = mb_convert_encoding(
                            $value,
                            'UTF-8',
                            'UTF-8, ISO-8859-1, Windows-1252'
                        );
                    }

                    $qty = isset($row['OnHand']) ? (float)$row['OnHand'] : 0;

                    if ($qty > 10) {
                        $status = "In-Stock";
                    } elseif ($qty >= 1) {
                        $status = "Low Stock";
                    } else {
                        $status = "Out of Stock";
                    }

                    $row['status'] = $status;
                    $stockData[] = $row;
                }
            }

            odbc_close($conn);

            return response()->json([
                'status' => 'success',
                'statusCode' => 200,
                'message' => 'Stock fetched successfully',
                'data' => $stockData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Error: ' . $e->getMessage()
            ], 500);
        }
    }



    public function getProductStockDetails($product_details_id)
    {
        $stockRecords = ProductStock::with(['warehouse', 'productDetails.product', 'productDetails.productType'])
            ->where('product_details_id', $product_details_id)
            ->get();

        if ($stockRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No stock records found for this product.',
            ], 404);
        }
        $firstStock = $stockRecords->first();
        $stockQuantity = (float) $firstStock->quantity;

        // Determine availability status
        $availabilityStatus = 'Out of Stock';
        if ($stockQuantity > 0 && $stockQuantity < 10) {
            $availabilityStatus = 'Low Stock';
        } elseif ($stockQuantity >= 10) {
            $availabilityStatus = 'In Stock';
        }
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Product stock details fetched successfully.',
            'data' => [
                'product_details_id' => $product_details_id,
                'product_name'       => optional($firstStock->productDetails)->product_name,
                'product_type'       => optional($firstStock->productDetails->productType)->type_name,
                'item_profile'       => optional($firstStock->productDetails)->item_profile,
                'item_thickness'     => optional($firstStock->productDetails)->item_thickness,
                'primary_group'      => optional($firstStock->productDetails)->primary_group,
                'stock_updated_at'   => optional($firstStock->productDetails)->stock_updated_at_formatted,
                'stocks' => $stockRecords->count() > 0 ? [
                    'warehouse_id'       => $firstStock->warehouse_id,
                    'warehouse_name'     => $firstStock->warehouse->warehouse_name,
                    'stock_quantity'     => number_format((float) $firstStock->quantity, 5, '.', ''),
                    'availability_status' => $availabilityStatus,
                ] : null,
            ],
        ], 200);
    }
    public function stockFilter(Request $request)
    {
        $request->validate([
            'search_key' => 'required|string|in:All,In Stock,Out of Stock,Low Stock',
        ]);

        $searchKey = $request->search_key;

        $stockQuery = ProductStock::with(['productDetails.product', 'productDetails.productType', 'warehouse']);

        if ($searchKey == 'In Stock') {
            $stockQuery->where('quantity', '>=', 10);
        } elseif ($searchKey == 'Low Stock') {
            $stockQuery->where('quantity', '>', 0)->where('quantity', '<', 10);
        } elseif ($searchKey == 'Out of Stock') {
            $stockQuery->where('quantity', '=', 0);
        }

        $stockItems = $stockQuery->get();

        if ($stockItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No stock found for the given filter.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Stock filter applied successfully for '$searchKey'.",
            'data' => [
                'search_key' => $searchKey,
                'stocks' => $stockItems->map(function ($stock) {
                    $stockQuantity = (float) $stock->quantity;
        
                    $availabilityStatus = 'Out of Stock';
                    if ($stockQuantity > 0 && $stockQuantity < 10) {
                        $availabilityStatus = 'Low Stock';
                    } elseif ($stockQuantity >= 10) {
                        $availabilityStatus = 'In Stock';
                    }
        
                    return [
                        'product_details_id'  => $stock->product_details_id,
                        'product_name'        => optional($stock->productDetails)->product_name ?? 'N/A',
                        'product_type'        => optional(optional($stock->productDetails)->productType)->type_name ?? 'N/A',
                        'warehouse_id'        => $stock->warehouse_id,
                        'warehouse_name'      => optional($stock->warehouse)->warehouse_name ?? 'N/A',
                        'stock_quantity'      => number_format($stockQuantity, 5, '.', ''),
                        'availability_status' => $availabilityStatus,
                    ];
                }),
            ],
        ], 200);
    }
    public function getTotalStocks()
    {
        $stocks = ProductDetails::select('type_id')
            ->selectRaw('SUM(total_available_quantity) as total_stock_quantity')
            ->groupBy('type_id')
            ->with('productType:id,type_name')
            ->get()
            ->map(function ($item) {
                return [
                    'type_id' => $item->type_id,
                    'type_name' => $item->productType->type_name ?? 'Unknown',
                    'total_stock_quantity' => (float) $item->total_stock_quantity,
                ];
            });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'data' => $stocks
        ]);
    }

}
