<?php

namespace Tests\Unit;

use App\Enums\BudgetResetType;
use App\Http\Resources\BudgetLogResource;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BudgetServicePeriodBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_date_fixed_log_boundaries_store_as_utc_and_format_in_user_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04 12:00:00', 'Asia/Manila'));

        $user = User::factory()->create([
            'timezone' => 'Asia/Manila',
        ]);

        $budget = Budget::query()->create([
            'user_id' => $user->id,
            'budget_type_id' => 1,
            'name' => 'Home Budget',
            'amount' => '5000.00',
            'reset_type' => BudgetResetType::DateFixed->value,
            'reset_days' => [1, 16],
            'rollover' => false,
        ]);

        $budget->load('user');
        $log = app(BudgetService::class)->ensureCurrentCycleLog($budget);

        $this->assertSame('2026-06-30 16:00:00', $log->getRawOriginal('start_date'));
        $this->assertSame('2026-07-15 15:59:59', $log->getRawOriginal('end_date'));

        $log->setRelation('budget', $budget);
        $payload = (new BudgetLogResource($log))->toArray(Request::create('/'));

        $this->assertSame('2026-07-01', $payload['start_date']);
        $this->assertSame('2026-07-15', $payload['end_date']);
    }
}
