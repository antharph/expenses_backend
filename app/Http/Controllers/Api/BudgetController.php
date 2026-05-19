<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBudgetRequest;
use App\Http\Resources\BudgetLogResource;
use App\Http\Resources\BudgetProgressResource;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = $request->user()
            ->budgets()
            ->with('user')
            ->orderBy('name')
            ->get();

        return BudgetProgressResource::collection($budgets)->response();
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $resetType = $validated['reset_type'];

        $budget = $request->user()->budgets()->create([
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'reset_type' => $resetType,
            'reset_days' => $resetType === 'manual'
                ? null
                : array_values($validated['reset_days'] ?? []),
            'rollover' => (bool) ($validated['rollover'] ?? false),
        ]);

        $budget->categories()->sync($validated['category_ids'] ?? []);
        $budget->load('user');

        return (new BudgetProgressResource($budget))
            ->response()
            ->setStatusCode(201);
    }

    public function logs(Request $request, Budget $budget): JsonResponse
    {
        abort_unless($budget->user_id === $request->user()->id, 404);

        $logs = $budget->logs()
            ->with(['budget.user'])
            ->orderByDesc('start_date')
            ->get();

        return BudgetLogResource::collection($logs)->response();
    }
}
