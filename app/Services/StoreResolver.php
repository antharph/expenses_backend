<?php

namespace App\Services;

use App\Models\Store;

class StoreResolver
{
    /**
     * @param  array{name: string, legal_name?: string|null, address?: string|null}|null  $store
     */
    public function resolve(?array $store): ?int
    {
        if ($store === null || ! isset($store['name'])) {
            return null;
        }

        $name = trim($store['name']);
        if ($name === '') {
            return null;
        }

        $legalName = isset($store['legal_name']) ? trim((string) $store['legal_name']) : null;
        $legalName = $legalName === '' ? null : $legalName;

        $address = isset($store['address']) ? trim((string) $store['address']) : null;
        $address = $address === '' ? null : $address;

        $existing = Store::query()
            ->where('name', $name)
            ->when(
                $legalName === null,
                static fn ($query) => $query->whereNull('legal_name'),
                static fn ($query) => $query->where('legal_name', $legalName),
            )
            ->when(
                $address === null,
                static fn ($query) => $query->whereNull('address'),
                static fn ($query) => $query->where('address', $address),
            )
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        return Store::query()->create([
            'name' => $name,
            'legal_name' => $legalName,
            'address' => $address,
        ])->id;
    }
}
