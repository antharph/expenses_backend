<?php

namespace Database\Seeders;

use App\Models\BudgetType;
use Illuminate\Database\Seeder;

class BudgetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'budget',
                'name' => 'Budget',
            ],
            [
                'code' => 'tracking',
                'name' => 'Tracking',
            ],
        ];

        foreach ($types as $type) {
            BudgetType::query()->firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name']]
            );
        }
    }
}
