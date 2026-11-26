<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SportCategory;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CategoryResource::collection(
                SportCategory::where('is_active', true)->orderBy('name')->get()
            ),
        ]);
    }
}
