<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogisticsController extends Controller
{
    public function index()
    {
        return view('logistics.orders.index');
    }
    public function getSalesOrders(Request $request)
    {
        $orders = Order::with(['dealer', 'dealers', 'orderItems.product'])
            ->whereIn('vehicle_category_id', ['2', '3'])
            ->where('order_approved', '1')
            ->orderBy('id', 'desc')
            ->get();

        return DataTables::of($orders)
            ->addColumn('checkbox', function ($order) {
                return '<input type="checkbox" class="order-checkbox" value="' . $order->id . '">';
            })
            ->addIndexColumn()
            ->addColumn('order_id', function ($order) {
                return 'OD00' . $order->id;
            })
            ->addColumn('date', function ($order) {
                return $order->created_at->format('d/m/Y');
            })
            ->addColumn('dealer_name', function ($order) {
                if ($order->dealer_flag_order == 1) {
                    return $order->dealers?->dealer_name ?? 'N/A'; 
                }
                return $order->dealer?->dealer_name ?? 'N/A';
            })
            ->addColumn('dealer_code', function ($order) {
                if ($order->dealer_flag_order == 1) {
                    return $order->dealers?->dealer_code ?? 'N/A';
                }
                return $order->dealer?->dealer_code ?? 'N/A';
            })
            ->addColumn('address', function ($order) {
                if ($order->dealer_flag_order == 1) {
                    return $order->dealers?->address ?? 'N/A';
                }
                return $order->dealer?->address ?? 'N/A';
            })
            ->addColumn('product_name', function ($order) {
                return $order->orderItems->isNotEmpty() && $order->orderItems->first()->product
                    ? $order->orderItems->first()->product->product_name
                    : 'N/A';
            })
            ->addColumn('product_details', function ($order) {
                if ($order->orderItems->isEmpty()) {
                    return 'N/A';
                }
            
                $details = collect($order->orderItems->first()->product_details)
                    ->map(function ($detail) {
                        $productType = \App\Models\ProductType::find($detail['product_type_id']);
                        return 'Type Name: ' . ($productType ? $productType->type_name : 'Unknown Type') .
                               ', Quantity: ' . $detail['quantity'];
                    })
                    ->implode('<br>'); 
            
                return $details;
            })
            ->addColumn('status', function ($order) {
                return '<span class="badge bg-success">Approved</span>';
            })
            ->rawColumns(['checkbox','product_details','status'])
            ->make(true);
    }
}
