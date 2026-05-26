<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBudgetRequest;
use App\Http\Requests\Api\UpdateBudgetCategoriesRequest;
use App\Http\Resources\BudgetLogResource;
use App\Http\Resources\BudgetProgressResource;
use App\Models\Budget;
use App\Models\BudgetLog;
use App\Services\BudgetService;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = $request->user()
            ->budgets()
            ->with(['user', 'categories:id,name', 'budgetType'])
            ->orderBy('name')
            ->get();

        return BudgetProgressResource::collection($budgets)->response();
    }

    public function store(StoreBudgetRequest $request, BudgetService $budgetService): JsonResponse
    {
        $validated = $request->validated();
        $resetType = $validated['reset_type'];

        $budget = $request->user()->budgets()->create([
            'name' => $validated['name'],
            'amount' => $validated['amount'] ?? null,
            'budget_type_id' => $validated['budget_type_id'],
            'reset_type' => $resetType,
            'reset_days' => $resetType === 'manual'
                ? null
                : array_values($validated['reset_days'] ?? []),
            'rollover' => (bool) ($validated['rollover'] ?? false),
        ]);

        $budget->categories()->sync($validated['category_ids']);
        $budget->load(['user', 'categories:id,name', 'budgetType']);

        if ($resetType === 'manual') {
            $budgetService->createInitialManualCycleLog(
                $budget,
                Carbon::parse($validated['start_date'], $request->user()->displayTimezone()),
            );
        } else {
            $budgetService->ensureCurrentCycleLog($budget);
        }

        return (new BudgetProgressResource($budget))
            ->response()
            ->setStatusCode(201);
    }

    public function syncCycles(Request $request, BudgetService $budgetService): JsonResponse
    {
        $budgetService->syncBudgetCyclesForUser($request->user());

        $budgets = $request->user()
            ->budgets()
            ->with(['user', 'categories:id,name', 'budgetType'])
            ->orderBy('name')
            ->get();

        return BudgetProgressResource::collection($budgets)->response();
    }

    public function logs(Request $request, Budget $budget): JsonResponse
    {
        abort_unless($budget->user_id === $request->user()->id, 404);

        $budget->load([
            'user',
            'categories:id',
            'logs' => static fn ($query) => $query
                ->with('categories:id,name')
                ->orderByDesc('start_date'),
        ]);

        $budget->logs->each(
            static fn (BudgetLog $log) => $log->setRelation('budget', $budget),
        );

        return BudgetLogResource::collection($budget->logs)->response();
    }

    public function finalizeManual(Request $request, Budget $budget, BudgetService $budgetService): JsonResponse
    {
        abort_unless($budget->user_id === $request->user()->id, 404);
        $resetType = $budget->reset_type instanceof BackedEnum
            ? $budget->reset_type->value
            : $budget->reset_type;
        abort_unless(
            $resetType === 'manual',
            422,
            'Only manual budgets can be finalized manually.',
        );

        $budget->load(['user', 'categories:id,name', 'budgetType']);
        $budgetService->finalizeManualCycle($budget);

        return (new BudgetProgressResource($budget->refresh()->load(['user', 'categories:id,name', 'budgetType'])))->response();
    }

    public function updateCategories(
        UpdateBudgetCategoriesRequest $request,
        Budget $budget,
    ): JsonResponse {
        abort_unless($budget->user_id === $request->user()->id, 404);

        $budget->categories()->sync($request->validated('category_ids'));
        $budget->load(['user', 'categories:id,name', 'budgetType']);

        return (new BudgetProgressResource($budget))->response();
    }

    public function destroy(Request $request, Budget $budget): Response
    {
        abort_unless($budget->user_id === $request->user()->id, 404);

        $budget->delete();

        return response()->noContent();
    }
}
