<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReceiptInterpretationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexExpenseRequest;
use App\Http\Requests\Api\StoreExpenseRequest;
use App\Http\Requests\Api\WeeklyExpenseRequest;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ExpenseResource;
use App\Models\Category;
use App\Services\GeminiCategoryInferrer;
use App\Services\GeminiReceiptInterpreter;
use App\Services\StoreResolver;
use App\Support\ExpenseAmounts;
use App\Support\ExpenseDateRangeFilter;
use App\Support\ExpenseTimezone;
use App\Support\ExpenseWeek;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly GeminiReceiptInterpreter $geminiReceiptInterpreter,
        private readonly GeminiCategoryInferrer $geminiCategoryInferrer,
        private readonly StoreResolver $storeResolver,
    ) {}

    public function index(IndexExpenseRequest $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) config('app.pagination_per_page', 15)));
        $validated = $request->validated();
        $timezone = ExpenseTimezone::forUser($request->user());

        $query = $request->user()
            ->expenses()
            ->with(['category', 'store'])
            ->latest('id');

        ExpenseDateRangeFilter::apply(
            $query,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            $timezone,
        );

        $sumTotal = ExpenseAmounts::formatMoney((float) (clone $query)->sum('total'));
        $paginator = $query->paginate($perPage);

        return (new ExpenseCollection($paginator, $sumTotal))->response();
    }

    public function weekly(WeeklyExpenseRequest $request): JsonResponse
    {
        $year = (int) $request->validated('year');
        $week = (int) $request->validated('week');
        $timezone = ExpenseTimezone::forUser($request->user());
        [$startDate, $endDate] = ExpenseWeek::weekDateRange($year, $week, $timezone);

        $query = $request->user()
            ->expenses()
            ->with(['category', 'store'])
            ->latest('id');

        ExpenseDateRangeFilter::apply($query, $startDate, $endDate, $timezone);

        $sumTotal = ExpenseAmounts::formatMoney((float) (clone $query)->sum('total'));
        $expenses = $query->get();

        $prev = ExpenseWeek::previous($year, $week, $timezone);
        $next = ExpenseWeek::next($year, $week, $timezone);

        return ExpenseResource::collection($expenses)
            ->additional([
                'meta' => [
                    'year' => $year,
                    'week' => $week,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total' => $expenses->count(),
                    'sum_total' => $sumTotal,
                    'prev' => ExpenseWeek::weekUrl($prev['year'], $prev['week']),
                    'current' => ExpenseWeek::weekUrl($year, $week),
                    'next' => ExpenseWeek::weekUrl($next['year'], $next['week']),
                ],
            ])
            ->response();
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            try {
                $categories = $this->categoriesForGemini();
                $interpretation = $this->geminiReceiptInterpreter->interpret(
                    $request->file('receipt'),
                    $categories,
                );
                $storeId = $this->storeResolver->resolve($interpretation->metadata->store);
                $receiptAttributes = $interpretation->metadata->expenseAttributes($storeId);

                $created = DB::transaction(function () use ($request, $interpretation, $data, $receiptAttributes) {
                    $userCategoryId = $data['category_id'] ?? null;
                    $out = collect();
                    foreach ($interpretation->records as $row) {
                        $attributes = array_merge(
                            ExpenseAmounts::fromParsedAmounts(
                                $row['item'],
                                $row['quantity'] ?? null,
                                $row['price'] ?? null,
                                $row['total'] ?? null,
                                $userCategoryId ?? $row['category_id'],
                            ),
                            $receiptAttributes,
                        );
                        $out->push($request->user()->expenses()->create($attributes));
                    }

                    return $out;
                });

                $created = $created->map(static fn ($expense) => $expense->fresh(['category', 'store']));

                return ExpenseResource::collection($created)->response()->setStatusCode(201);
            } catch (ReceiptInterpretationException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        if ($categoryId === null) {
            $categoryId = $this->geminiCategoryInferrer->infer(
                (string) $data['item'],
                $this->categoriesForGemini(),
            );
        }

        $expense = $request->user()->expenses()->create(
            ExpenseAmounts::fromUnitPrice(
                (string) $data['item'],
                $data['price'],
                $quantity,
                $categoryId,
            ),
        );

        return ExpenseResource::make($expense->fresh(['category', 'store']))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $expense): Response
    {
        $deleted = $request->user()->expenses()->whereKey($expense)->delete();

        if ($deleted === 0) {
            abort(404);
        }

        return response()->noContent();
    }

    /**
     * @return Collection<int, Category>
     */
    private function categoriesForGemini(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name', 'description']);
    }
}
