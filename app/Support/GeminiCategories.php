<?php

namespace App\Support;

use App\Models\Category;

final class GeminiCategories
{
    /**
     * @param  iterable<int, Category|array{id:int|string, name:string, description?:string|null}>  $categories
     * @return list<array{id: int, name: string, description?: string}>
     */
    public static function payload(iterable $categories): array
    {
        $out = [];

        foreach ($categories as $row) {
            if ($row instanceof Category) {
                $out[] = self::entryFromScalars((int) $row->id, (string) $row->name, $row->description);

                continue;
            }

            if (is_array($row) && array_key_exists('id', $row) && array_key_exists('name', $row)) {
                $out[] = self::entryFromScalars(
                    (int) $row['id'],
                    (string) $row['name'],
                    $row['description'] ?? null,
                );
            }
        }

        return $out;
    }

    /**
     * @param  list<array{id: int, name: string, description?: string}>  $payload
     * @return list<int>
     */
    public static function allowedIds(array $payload): array
    {
        return array_values(array_unique(array_map(static fn (array $c): int => $c['id'], $payload)));
    }

    /**
     * @param  list<int>  $allowedIds
     */
    public static function normalizeId(mixed $raw, array $allowedIds): ?int
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
     * @return array{id: int, name: string, description?: string}
     */
    private static function entryFromScalars(int $id, string $name, mixed $description): array
    {
        $entry = [
            'id' => $id,
            'name' => $name,
        ];

        $text = trim((string) ($description ?? ''));
        if ($text !== '') {
            $entry['description'] = $text;
        }

        return $entry;
    }
}
