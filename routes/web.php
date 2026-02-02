<?php
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Models\Product;
Route::get('/', function () {
    return view('welcome');
});
Route::get('storage/uploads/{filename}', function ($filename) {
    $path = 'uploads/' . $filename;
return response()->file(storage_path("app/public/$path")); 
});
Route::get('storage/drivers/adhar/{filename}', function ($filename) {
    $path = storage_path('app/public/drivers/adhar/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});
Route::get('storage/drivers/license/{filename}', function ($filename) {
    $path = storage_path('app/public/drivers/license/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});
Route::get('storage/drivers/photo/{filename}', function ($filename) {
    $path = storage_path('app/public/drivers/photo/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

Route::get('/get-products', function () {
    $user = auth()->user();
    $productIds = $user->product_ids ?? [];

    $products = Product::whereIn('id', $productIds)
        ->select('id', 'product_name', 'product_code')
        ->get();

    return response()->json($products);
})->name('get.products');

Route::post('/set-product', function (\Illuminate\Http\Request $request) {
    $productId = $request->input('product_id');
    
    Session::put('selected_product_code', $productId);
    return response()->json(['success' => true]);
})->name('set.product');
Route::get('/sales/getEmployeesAjax', [AttendanceController::class, 'getEmployeesAjax'])->name('sales.getEmployeesAjax');

require __DIR__.'/admin.php';
