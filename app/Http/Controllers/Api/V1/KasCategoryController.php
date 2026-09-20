<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KasCategoryResource;
use App\Models\KasCategory;

class KasCategoryController extends Controller
{
    public function index()
    {
        $categories = KasCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return KasCategoryResource::collection($categories);
    }
}
