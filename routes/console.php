<?php

use App\Services\BudgetService;
use Database\Seeders\BudgetLogHistorySeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'budget:seed-log-history {userId : The user ID whose budget history should be backfilled}',
    function (BudgetService $budgetService): void {
        $userId = (int) $this->argument('userId');

        $seeder = app(BudgetLogHistorySeeder::class);
        $seeder->setCommand($this);
        $seeder->run($budgetService, $userId);

        $this->info("Budget log history seeded for user {$userId}.");
    },
)->purpose('Backfill budget_logs history for a single user');
