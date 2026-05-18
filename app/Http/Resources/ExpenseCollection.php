<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ExpenseCollection extends ResourceCollection
{
    public $collects = ExpenseResource::class;

    public function __construct(
        $resource,
        private readonly string $sumTotal,
    ) {
        parent::__construct($resource);
    }

    /**
     * @param  \Illuminate\Pagination\AbstractPaginator<int, \App\Models\Expense>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, $paginated, array $default): array
    {
        $default['meta']['sum_total'] = $this->sumTotal;

        return $default;
    }
}
