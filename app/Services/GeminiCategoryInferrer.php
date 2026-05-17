<?php

namespace App\Services;

use App\Models\Category;
use App\Support\GeminiCategories;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiCategoryInferrer
{
    /**
     * Pick the best category id for an expense item description, or null when uncertain.
     *
     * @param  iterable<int, Category|array{id:int|string, name:string, description?:string|null}>  $categories
     */
    public function infer(string $item, iterable $categories = []): ?int
    {
        $item = trim($item);
        if ($item === '') {
            return null;
        }

        $categoryPayload = GeminiCategories::payload($categories);
        if ($categoryPayload === []) {
            return null;
        }

        $allowedIds = GeminiCategories::allowedIds($categoryPayload);

        $apiKey = (string) config('gemini.api_key');
        if ($apiKey === '') {
            return null;
        }

        $model = (string) config('gemini.model', 'gemini-2.5-flash-lite');
        if ($model === '') {
            return null;
        }

        $categoriesJson = json_encode(
            array_values($categoryPayload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $prompt = <<<PROMPT
You classify a single expense line item into one category for a personal expense tracker.

Expense item:
{$item}

Allowed categories (JSON). Each object has "id" (integer), "name" (string), and optionally "description" (string) with extra guidance for when to use that category. You MUST NOT invent category ids: category_id must be null or exactly one of the provided ids.

Choose the single best-fitting category when the item clearly belongs to one list entry (use name and description). Return null when no category is a clear fit.
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
                        ['text' => "Allowed categories:\n{$categoriesJson}"],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'category_id' => [
                            'type' => 'INTEGER',
                            'nullable' => true,
                            'description' => 'Best category id from the allowed list, or null.',
                        ],
                    ],
                    'required' => ['category_id'],
                ],
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->acceptJson()
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, $payload);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        try {
            $decoded = $this->decodeModelJson($response);
        } catch (Throwable) {
            return null;
        }

        return GeminiCategories::normalizeId($decoded['category_id'] ?? null, $allowedIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeModelJson(Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Unexpected response from the category inference service.');
        }

        $finish = (string) data_get($json, 'candidates.0.finishReason', '');
        if ($finish !== '' && $finish !== 'STOP') {
            throw new \RuntimeException('Category inference blocked or incomplete.');
        }

        $text = data_get($json, 'candidates.0.content.parts.0.text');
        if (is_string($text) && $text !== '') {
            $parsed = json_decode($text, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        throw new \RuntimeException('Could not read structured data from the category inference service.');
    }
}
