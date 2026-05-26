<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetTypeResource;
use App\Models\BudgetType;
use Illuminate\Http\JsonResponse;

class BudgetTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $types = BudgetType::query()->orderBy('id')->get(['id', 'code', 'name']);

        return BudgetTypeResource::collection($types)->response();
    }
}
