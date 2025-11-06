<?php
use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('storage/uploads/{filename}', function ($filename) {
    $path = 'uploads/' . $filename;
return response()->file(storage_path("app/public/$path")); 
});

Route::get('/sales/getEmployeesAjax', [AttendanceController::class, 'getEmployeesAjax'])->name('sales.getEmployeesAjax');

require __DIR__.'/admin.php';
