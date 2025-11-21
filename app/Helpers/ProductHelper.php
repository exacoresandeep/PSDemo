<?php

namespace App\Helpers;

use App\Models\Product;

class ProductHelper
{
    public static function getSelectedProductId()
    {
        // 1. Check session product code
        $selectedProductCode = session('selected_product_code');

        if ($selectedProductCode) {
            $product = Product::where('product_code', $selectedProductCode)->first();
            return $product ? $product->id : null;
        }

        // 2. Otherwise use logged-in user's product_ids
        $user = auth()->user();
        $productIds = $user->product_ids ?? [];

        $product = Product::whereIn('id', $productIds)
            ->select('id', 'product_name', 'product_code')
            ->first();

        return $product ? $product->id : null;
    }
}
