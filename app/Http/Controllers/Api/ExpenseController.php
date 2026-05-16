<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReceiptInterpretationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Category;
use App\Services\GeminiReceiptInterpreter;
use App\Support\ExpenseAmounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly GeminiReceiptInterpreter $geminiReceiptInterpreter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) config('app.pagination_per_page', 15)));

        $paginator = $request->user()
            ->expenses()
            ->with('category')
            ->latest('id')
            ->paginate($perPage);

        return ExpenseResource::collection($paginator)->response();
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            try {
                $categories = Category::query()->orderBy('name')->get(['id', 'name']);
                $records = $this->geminiReceiptInterpreter->interpret($request->file('receipt'), $categories);

                $created = DB::transaction(function () use ($request, $records, $data) {
                    $userCategoryId = $data['category_id'] ?? null;
                    $out = collect();
                    foreach ($records as $row) {
                        $attributes = ExpenseAmounts::fromParsedAmounts(
                            $row['item'],
                            $row['quantity'] ?? null,
                            $row['price'] ?? null,
                            $row['total'] ?? null,
                            $userCategoryId ?? $row['category_id'],
                        );
                        $out->push($request->user()->expenses()->create($attributes));
                    }

                    return $out;
                });

                $created = $created->map(static fn ($expense) => $expense->fresh(['category']));

                return ExpenseResource::collection($created)->response()->setStatusCode(201);
            } catch (ReceiptInterpretationException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $expense = $request->user()->expenses()->create(
            ExpenseAmounts::fromUnitPrice(
                (string) $data['item'],
                $data['price'],
                $quantity,
                isset($data['category_id']) ? (int) $data['category_id'] : null,
            ),
        );

        return ExpenseResource::make($expense->fresh(['category']))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $expense): Response
    {
        $deleted = $request->user()->expenses()->whereKey($expense)->delete();

        if ($deleted === 0) {
            abort(404);
        }

        return response()->noContent();
    }
}
