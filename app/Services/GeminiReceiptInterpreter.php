<?php

namespace App\Services;

use App\Exceptions\ReceiptInterpretationException;
use App\Models\Category;
use App\Support\ExpenseAmounts;
use App\Support\GeminiCategories;
use App\Support\ReceiptMetadata;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiReceiptInterpreter
{
    public function __construct(
        private readonly ReceiptImageCompressor $imageCompressor,
    ) {}

    /**
     * Read a receipt image once (in memory / temp upload path) and return one or more
     * expense rows with reconciled quantity, unit price, and line total.
     *
     * @param  iterable<int, Category|array{id:int|string, name:string, description?:string|null}>  $categories
     */
    public function interpret(UploadedFile $file, iterable $categories = []): ReceiptInterpretationResult
    {
        $apiKey = (string) config('gemini.api_key');
        if ($apiKey === '') {
            throw new ReceiptInterpretationException('Receipt interpretation is not configured (missing GEMINI_AI_KEY).');
        }

        $model = (string) config('gemini.model', 'gemini-2.5-flash-lite');
        if ($model === '') {
            throw new ReceiptInterpretationException('Receipt interpretation is not configured (missing GEMINI_MODEL).');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $binary = $file->get();
        if ($binary === '') {
            throw new ReceiptInterpretationException('The receipt file was empty.');
        }

        $prepared = $this->imageCompressor->compress($binary, $mime);
        $binary = $prepared['binary'];
        $mime = $prepared['mime'];

        $base64 = base64_encode($binary);

        $categoryPayload = GeminiCategories::payload($categories);
        $allowedIds = GeminiCategories::allowedIds($categoryPayload);

        $categoriesJson = json_encode(
            array_values($categoryPayload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $prompt = <<<PROMPT
You are parsing a purchase receipt or invoice image for an expense tracker.

You will be given a JSON array of allowed categories. Each element has "id" (integer), "name" (string), and optionally "description" (string) with extra guidance for when to use that category. You MUST NOT invent category ids: every category_id you output must either be null or exactly match one of the provided ids.

For every expense row (top-level summary and each line item), extract or infer:
- "quantity": integer count of units (minimum 1). Use 1 when the receipt shows a single unit or only a line total with no count.
- "price": unit price per item (before tax on that line, when shown). Numbers only, no currency symbol.
- "total": line extension amount for that row. Numbers only, no currency symbol.

These three fields MUST be mathematically consistent: total = quantity × price, rounded to two decimal places. When the receipt shows only one or two of them, compute the missing value(s) before you respond. Examples:
- Qty 3 @ \$4.50 each → quantity 3, price 4.50, total 13.50
- Qty 2, line total \$10.00, no unit price → quantity 2, price 5.00, total 10.00
- Line total \$12.99 only → quantity 1, price 12.99, total 12.99

Return a JSON object with:
- "item": short summary of the whole purchase (merchant + scope), max ~200 characters.
- "quantity", "price", "total": for the overall receipt when there is no per-line breakdown (see line_items rule below).
- "category_id": integer or null — the single best category from the allowed list for the overall receipt, or null if none clearly fits.
- "line_items": array (may be empty). For each distinct purchasable line on the receipt, include an object with:
  - "item": line description (what was bought on that line).
  - "quantity", "price", "total" as defined above (include every field you can read or derive).
  - "category_id": integer from the allowed list for that line, or null if no category clearly fits that line.

If the receipt clearly lists multiple product or service lines with individual amounts, fill "line_items" with one entry per line and assign category_id per line when possible. Skip rows that are only tax, tips, or duplicate subtotals that are not themselves line items. If there is only a single total with no meaningful per-line breakdown, use an empty array for "line_items" and put quantity, price, and total on the top-level object together with item and category_id.

If multiple currencies appear, use the currency and amounts that correspond to the total to pay.

Also extract receipt-level merchant and transaction details when visible on the receipt:
- "store_name": the customer-facing brand or location name on the receipt (e.g. "Jollibee").
- "legal_name": the registered business or franchisee entity when shown separately (e.g. "Golden Lion Food" on a Jollibee receipt). Omit or null when not shown.
- "address": store or branch address as printed. Omit or null when not shown.
- "transaction_number": receipt or transaction / control number when printed. Omit or null when not shown.
- "invoice_number": invoice or OR number when printed. Omit or null when not shown.
- "transaction_at": date and time of the purchase transaction in ISO 8601 (wall-clock time as printed on the receipt; omit timezone offset). Omit or null when no transaction date is visible.

Receipts often print many dates or none at all. Only extract the date/time of the actual purchase—the one paired with the OR/invoice number, transaction number, line items, or printed clock time. Do NOT use PTU/permit issued dates, BIR/tax registration dates, machine serial or installation dates, copyright years, expiry dates, or other administrative dates. When several candidates exist, choose the one clearly tied to this sale; when none clearly indicates the purchase moment, omit or null. The API stores this instant in UTC using the authenticated user's timezone; dates more than 15 days before upload are replaced with the user's current local datetime.

Allowed categories:
{$categoriesJson}
PROMPT;

        $amountFields = [
            'quantity' => [
                'type' => 'INTEGER',
                'nullable' => true,
                'description' => 'Unit count (minimum 1). Omit only if unknown; derive when possible.',
            ],
            'price' => [
                'type' => 'NUMBER',
                'nullable' => true,
                'description' => 'Unit price. Omit only if unknown; derive from quantity and total when possible.',
            ],
            'total' => [
                'type' => 'NUMBER',
                'nullable' => true,
                'description' => 'Line extension (quantity × unit price). Omit only if unknown; derive when possible.',
            ],
        ];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($model),
        );

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mime,
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'item' => [
                            'type' => 'STRING',
                            'description' => 'Short summary of the whole receipt.',
                        ],
                        ...$amountFields,
                        'category_id' => [
                            'type' => 'INTEGER',
                            'nullable' => true,
                            'description' => 'Best category id from the allowed list for the whole receipt, or null.',
                        ],
                        'store_name' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Customer-facing store or brand name on the receipt.',
                        ],
                        'legal_name' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Registered business or franchisee entity when shown separately from the brand name.',
                        ],
                        'address' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Store or branch address as printed on the receipt.',
                        ],
                        'transaction_number' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Receipt or transaction / control number.',
                        ],
                        'invoice_number' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Invoice or official receipt number.',
                        ],
                        'transaction_at' => [
                            'type' => 'STRING',
                            'nullable' => true,
                            'description' => 'Purchase transaction date/time in ISO 8601 (wall-clock as printed; no timezone offset). Ignore permit, registration, and other non-transaction dates. Null when no clear purchase date/time is visible.',
                        ],
                        'line_items' => [
                            'type' => 'ARRAY',
                            'description' => 'Per-line items when the receipt lists multiple lines with amounts.',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'item' => [
                                        'type' => 'STRING',
                                        'description' => 'Line item description.',
                                    ],
                                    ...$amountFields,
                                    'category_id' => [
                                        'type' => 'INTEGER',
                                        'nullable' => true,
                                        'description' => 'Category id from the allowed list for this line, or null.',
                                    ],
                                ],
                                'required' => ['item'],
                            ],
                        ],
                    ],
                    'required' => ['item', 'line_items'],
                ],
            ],
        ];

        try {
            $response = Http::timeout(90)
                ->connectTimeout(15)
                ->acceptJson()
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, $payload);
        } catch (Throwable $e) {
            throw new ReceiptInterpretationException('Unable to reach the receipt interpretation service.', previous: $e);
        }

        if (! $response->successful()) {
            $this->throwFromHttpResponse($response);
        }

        $decoded = $this->decodeModelJson($response);

        return $this->resultFromDecoded($decoded, $allowedIds);
    }

    private function resultFromDecoded(array $decoded, array $allowedIds): ReceiptInterpretationResult
    {
        $records = $this->recordsFromDecoded($decoded, $allowedIds);

        return new ReceiptInterpretationResult(
            records: $records,
            metadata: ReceiptMetadata::fromDecoded($decoded),
        );
    }

    /**
     * @return list<array{item: string, quantity: int, price: string, total: string, category_id: int|null}>
     */
    private function recordsFromDecoded(array $decoded, array $allowedIds): array
    {
        $lineItems = $decoded['line_items'] ?? null;
        $records = [];

        if (is_array($lineItems)) {
            foreach ($lineItems as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $record = $this->recordFromLine($line, $allowedIds);
                if ($record !== null) {
                    $records[] = $record;
                }
            }
        }

        if ($records !== []) {
            return $records;
        }

        $item = isset($decoded['item']) ? trim((string) $decoded['item']) : '';
        if ($item === '') {
            throw new ReceiptInterpretationException('The receipt did not yield a usable item description.');
        }

        $record = $this->recordFromAmounts(
            Str::limit($item, 255, ''),
            $decoded,
            GeminiCategories::normalizeId($decoded['category_id'] ?? null, $allowedIds),
        );

        if ($record === null) {
            throw new ReceiptInterpretationException('The receipt did not yield usable quantity, price, or total.');
        }

        return [$record];
    }

    /**
     * @param  list<int>  $allowedIds
     * @return array{item: string, quantity: int, price: string, total: string, category_id: int|null}|null
     */
    private function recordFromLine(array $line, array $allowedIds): ?array
    {
        $item = isset($line['item']) ? trim((string) $line['item']) : '';
        if ($item === '') {
            return null;
        }

        return $this->recordFromAmounts(
            Str::limit($item, 255, ''),
            $line,
            GeminiCategories::normalizeId($line['category_id'] ?? null, $allowedIds),
        );
    }

    /**
     * @return array{item: string, quantity: int, price: string, total: string, category_id: int|null}|null
     */
    private function recordFromAmounts(string $item, array $source, ?int $categoryId): ?array
    {
        try {
            $amounts = ExpenseAmounts::reconcile(
                $source['quantity'] ?? null,
                $source['price'] ?? null,
                $source['total'] ?? null,
            );
        } catch (\InvalidArgumentException) {
            return null;
        }

        return [
            'item' => $item,
            'quantity' => $amounts['quantity'],
            'price' => $amounts['price'],
            'total' => $amounts['total'],
            'category_id' => $categoryId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeModelJson(Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            throw new ReceiptInterpretationException('Unexpected response from the interpretation service.');
        }

        $finish = (string) data_get($json, 'candidates.0.finishReason', '');
        if ($finish !== '' && $finish !== 'STOP') {
            throw new ReceiptInterpretationException('The receipt could not be interpreted (blocked or incomplete).');
        }

        $text = data_get($json, 'candidates.0.content.parts.0.text');
        if (is_string($text) && $text !== '') {
            $parsed = json_decode($text, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        throw new ReceiptInterpretationException('Could not read structured data from the interpretation service.');
    }

    private function throwFromHttpResponse(Response $response): void
    {
        $body = $response->json();
        $message = is_array($body) ? (string) data_get($body, 'error.message', '') : '';
        if ($message === '') {
            $message = 'The receipt interpretation service returned an error.';
        }

        throw new ReceiptInterpretationException($message);
    }
}
