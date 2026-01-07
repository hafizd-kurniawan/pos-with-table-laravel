<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //get all
    public function index()
    {
        $categories = \App\Models\Category::all();
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }
}
