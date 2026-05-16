<?php

namespace App\Services;

use App\Exceptions\ReceiptInterpretationException;
use App\Models\Category;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiReceiptInterpreter
{
    /**
     * Read a receipt image once (in memory / temp upload path) and return one or more
     * expense rows (item, price, optional category_id from the allowed list).
     *
     * @param  iterable<int, Category|array{id:int|string, name:string}>  $categories
     * @return list<array{item: string, price: string, category_id: int|null}>
     */
    public function interpret(UploadedFile $file, iterable $categories = []): array
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

        $base64 = base64_encode($binary);

        $categoryPayload = $this->categoryPayload($categories);
        $allowedIds = array_values(array_unique(array_map(static fn (array $c): int => $c['id'], $categoryPayload)));

        $categoriesJson = json_encode(
            array_values($categoryPayload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $prompt = <<<PROMPT
You are parsing a purchase receipt or invoice image for an expense tracker.

You will be given a JSON array of allowed categories. Each element has only "id" (integer) and "name" (string). You MUST NOT invent category ids: every category_id you output must either be null or exactly match one of the provided ids.

Return a JSON object with:
- "item": short summary of the whole purchase (merchant + scope), max ~200 characters.
- "price": numeric total amount to pay for the receipt in the primary currency (no currency symbol, decimal point).
- "category_id": integer or null — the single best category from the allowed list for the overall receipt, or null if none clearly fits.
- "line_items": array (may be empty). For each distinct purchasable line on the receipt, include an object with:
  - "item": line description (what was bought on that line).
  - "price": that line's amount as a number.
  - "category_id": integer from the allowed list for that line, or null if no category clearly fits that line.

If the receipt clearly lists multiple product or service lines with individual amounts, fill "line_items" with one entry per line and assign category_id per line when possible. Skip rows that are only tax, tips, or duplicate subtotals that are not themselves line items. If there is only a single total with no meaningful per-line breakdown, use an empty array for "line_items" and rely on the top-level item, price, and category_id.

If multiple currencies appear, use the currency and amounts that correspond to the total to pay.

Allowed categories (id and name only):
{$categoriesJson}
PROMPT;

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
                        'price' => [
                            'type' => 'NUMBER',
                            'description' => 'Total numeric amount from the receipt.',
                        ],
                        'category_id' => [
                            'type' => 'INTEGER',
                            'nullable' => true,
                            'description' => 'Best category id from the allowed list for the whole receipt, or null.',
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
                                    'price' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Line amount.',
                                    ],
                                    'category_id' => [
                                        'type' => 'INTEGER',
                                        'nullable' => true,
                                        'description' => 'Category id from the allowed list for this line, or null.',
                                    ],
                                ],
                                'required' => ['item', 'price'],
                            ],
                        ],
                    ],
                    'required' => ['item', 'price', 'line_items'],
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

        return $this->recordsFromDecoded($decoded, $allowedIds);
    }

    /**
     * @param  iterable<int, Category|array{id:int|string, name:string}>  $categories
     * @return list<array{id: int, name: string}>
     */
    private function categoryPayload(iterable $categories): array
    {
        $out = [];
        foreach ($categories as $row) {
            if ($row instanceof Category) {
                $out[] = [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                ];

                continue;
            }

            if (is_array($row) && array_key_exists('id', $row) && array_key_exists('name', $row)) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{item: string, price: string, category_id: int|null}>
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

                $item = isset($line['item']) ? trim((string) $line['item']) : '';
                $priceRaw = $line['price'] ?? null;

                if ($item === '' || ! is_numeric($priceRaw) || (float) $priceRaw < 0) {
                    continue;
                }

                $records[] = [
                    'item' => Str::limit($item, 255, ''),
                    'price' => (string) $priceRaw,
                    'category_id' => $this->normalizeCategoryId($line['category_id'] ?? null, $allowedIds),
                ];
            }
        }

        if ($records !== []) {
            return $records;
        }

        $item = isset($decoded['item']) ? trim((string) $decoded['item']) : '';
        $priceRaw = $decoded['price'] ?? null;

        if ($item === '') {
            throw new ReceiptInterpretationException('The receipt did not yield a usable item description.');
        }

        if (! is_numeric($priceRaw)) {
            throw new ReceiptInterpretationException('The receipt did not yield a usable price.');
        }

        if ((float) $priceRaw < 0) {
            throw new ReceiptInterpretationException('The interpreted price was invalid.');
        }

        return [[
            'item' => Str::limit($item, 255, ''),
            'price' => (string) $priceRaw,
            'category_id' => $this->normalizeCategoryId($decoded['category_id'] ?? null, $allowedIds),
        ]];
    }

    /**
     * @param  list<int>  $allowedIds
     */
    private function normalizeCategoryId(mixed $raw, array $allowedIds): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $id = (int) $raw;

        if (! in_array($id, $allowedIds, true)) {
            return null;
        }

        return $id;
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
