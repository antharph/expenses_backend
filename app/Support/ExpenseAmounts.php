<?php

namespace App\Support;

final class ExpenseAmounts
{
    /**
     * @return array{item: string, quantity: int, price: string, total: string, category_id?: int|null}
     */
    public static function fromUnitPrice(string $item, float|string $unitPrice, int $quantity = 1, ?int $categoryId = null): array
    {
        $quantity = max(1, $quantity);
        $price = self::formatMoney($unitPrice);
        $total = self::formatMoney((float) $price * $quantity);

        $attributes = [
            'item' => $item,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $total,
        ];

        if ($categoryId !== null) {
            $attributes['category_id'] = $categoryId;
        }

        return $attributes;
    }

    /**
     * @return array{item: string, quantity: int, price: string, total: string, category_id?: int|null}
     */
    public static function fromParsedAmounts(
        string $item,
        int|string|null $quantity,
        float|string|null $unitPrice,
        float|string|null $total,
        ?int $categoryId = null,
    ): array {
        $resolved = self::reconcile($quantity, $unitPrice, $total);

        $attributes = [
            'item' => $item,
            'quantity' => $resolved['quantity'],
            'price' => $resolved['price'],
            'total' => $resolved['total'],
        ];

        if ($categoryId !== null) {
            $attributes['category_id'] = $categoryId;
        }

        return $attributes;
    }

    /**
     * Derive missing quantity, unit price, or line total. Enforces total = unit price × quantity (2 dp).
     *
     * @return array{quantity: int, price: string, total: string}
     *
     * @throws \InvalidArgumentException
     */
    public static function reconcile(
        int|string|null $quantity,
        float|string|null $unitPrice,
        float|string|null $total,
    ): array {
        $quantityValue = self::normalizeQuantity($quantity);
        $priceValue = self::normalizeMoney($unitPrice);
        $totalValue = self::normalizeMoney($total);

        $present = ($quantityValue !== null ? 1 : 0)
            + ($priceValue !== null ? 1 : 0)
            + ($totalValue !== null ? 1 : 0);

        if ($present === 0) {
            throw new \InvalidArgumentException('At least one of quantity, price, or total is required.');
        }

        if ($quantityValue !== null && $priceValue !== null && $totalValue !== null) {
            return [
                'quantity' => $quantityValue,
                'price' => self::formatMoney($priceValue),
                'total' => self::formatMoney($priceValue * $quantityValue),
            ];
        }

        if ($quantityValue !== null && $priceValue !== null) {
            return [
                'quantity' => $quantityValue,
                'price' => self::formatMoney($priceValue),
                'total' => self::formatMoney($priceValue * $quantityValue),
            ];
        }

        if ($quantityValue !== null && $totalValue !== null) {
            $derivedPrice = $totalValue / $quantityValue;

            return [
                'quantity' => $quantityValue,
                'price' => self::formatMoney($derivedPrice),
                'total' => self::formatMoney($totalValue),
            ];
        }

        if ($priceValue !== null && $totalValue !== null) {
            $derivedQuantity = max(1, (int) round($totalValue / $priceValue));

            return [
                'quantity' => $derivedQuantity,
                'price' => self::formatMoney($priceValue),
                'total' => self::formatMoney($priceValue * $derivedQuantity),
            ];
        }

        if ($totalValue !== null) {
            return [
                'quantity' => 1,
                'price' => self::formatMoney($totalValue),
                'total' => self::formatMoney($totalValue),
            ];
        }

        if ($priceValue !== null) {
            $qty = $quantityValue ?? 1;

            return [
                'quantity' => $qty,
                'price' => self::formatMoney($priceValue),
                'total' => self::formatMoney($priceValue * $qty),
            ];
        }

        throw new \InvalidArgumentException('Quantity alone is not enough to determine price and total.');
    }

    public static function formatMoney(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private static function normalizeQuantity(int|string|null $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $value = (int) round((float) $raw);

        return $value >= 1 ? $value : null;
    }

    private static function normalizeMoney(float|string|null $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $value = (float) $raw;

        return $value >= 0 ? $value : null;
    }
}
