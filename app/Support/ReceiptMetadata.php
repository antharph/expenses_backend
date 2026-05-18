<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

final class ReceiptMetadata
{
    /**
     * @param  array{name: string, legal_name?: string|null, address?: string|null}|null  $store
     */
    public function __construct(
        public readonly ?array $store,
        public readonly ?string $transactionNumber,
        public readonly ?string $invoiceNumber,
        public readonly ?CarbonInterface $transactionAt,
    ) {}

    /**
     * @param  array<string, mixed>  $decoded
     */
    public static function fromDecoded(array $decoded): self
    {
        $storeName = self::nullableString($decoded['store_name'] ?? null);
        $legalName = self::nullableString($decoded['legal_name'] ?? null);
        $address = self::nullableString($decoded['address'] ?? null);

        $store = $storeName !== null
            ? [
                'name' => $storeName,
                'legal_name' => $legalName,
                'address' => $address,
            ]
            : null;

        return new self(
            store: $store,
            transactionNumber: self::nullableString($decoded['transaction_number'] ?? null),
            invoiceNumber: self::nullableString($decoded['invoice_number'] ?? null),
            transactionAt: self::parseTransactionAt($decoded['transaction_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function expenseAttributes(?int $storeId): array
    {
        $attributes = [];

        if ($storeId !== null) {
            $attributes['store_id'] = $storeId;
        }

        if ($this->transactionNumber !== null) {
            $attributes['transaction_number'] = $this->transactionNumber;
        }

        if ($this->invoiceNumber !== null) {
            $attributes['invoice_number'] = $this->invoiceNumber;
        }

        if ($this->transactionAt !== null) {
            $attributes['transaction_at'] = $this->transactionAt;
        }

        return $attributes;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function parseTransactionAt(mixed $value): ?CarbonInterface
    {
        $raw = self::nullableString($value);
        if ($raw === null) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
