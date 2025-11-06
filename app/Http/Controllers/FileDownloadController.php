<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileDownloadController extends Controller
{
    public function download($filename)
    {
        $path = storage_path(env('IMG_SERVER_PATH') . $filename); // Adjust the path if needed

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }
    
    public function show($filename)
    {
        $path = storage_path(env('IMG_SERVER_PATH') . $filename);
        
        if (file_exists($path)) {
            return response()->file($path);
        }
        
        abort(404);
    }
    public function view($filename)
    {
        $path = storage_path('uploads/' . $filename);

        if (file_exists($path)) {
            return response()->file($path);
        }

        abort(404);
    }
}