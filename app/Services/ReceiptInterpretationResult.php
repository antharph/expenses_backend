<?php

namespace App\Services;

use App\Support\ReceiptMetadata;

final class ReceiptInterpretationResult
{
    /**
     * @param  list<array{item: string, quantity: int, price: string, total: string, category_id: int|null}>  $records
     */
    public function __construct(
        public readonly array $records,
        public readonly ReceiptMetadata $metadata,
    ) {}
}
