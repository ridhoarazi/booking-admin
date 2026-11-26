<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FieldResource;
use App\Models\Field;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $category = trim((string) ($filters['category'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Field::where('is_active', true)->with('sportCategory');

        if ($category !== '') {
            $query->whereHas('sportCategory', fn ($q) => $q->where('slug', $category));
        }

        if ($search !== '') {
            $term = '%'.$search.'%';

            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        }

        return response()->json([
            'data' => FieldResource::collection($query->latest()->get()),
        ]);
    }

    public function show(Field $field): JsonResponse
    {
        abort_if(! $field->is_active, 404);

        return response()->json([
            'data' => new FieldResource($field->load('sportCategory')),
        ]);
    }
}
