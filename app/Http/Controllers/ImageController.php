<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function show($filename)
    {
        try {
            $path = Storage::disk('public')->path($filename);
            
            if (!file_exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $file = file_get_contents($path);
            $type = mime_content_type($path);

            return response($file)
                ->header('Content-Type', $type)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', '*')
                ->header('Access-Control-Allow-Headers', '*');
        } catch (\Exception $e) {
            \Log::error('Image error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
