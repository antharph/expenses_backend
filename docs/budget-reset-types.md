# Budget reset types and `reset_days`

Budget periods are driven by `budgets.reset_type` and `budgets.reset_days`. Period boundaries, status queries, and cycle logs are computed in [`app/Services/BudgetService.php`](../app/Services/BudgetService.php).

Allowed `reset_type` values are defined in [`app/Enums/BudgetResetType.php`](../app/Enums/BudgetResetType.php): `date_fixed`, `interval`, and `manual`.

---

## Reference

| `reset_type` | `reset_days` (example) | Use case |
| --- | --- | --- |
| `date_fixed` | `[1, 16]` | Semi-monthly paydays (resets on the 1st and 16th of each month). |
| `date_fixed` | `[1]` | Monthly budgeters (resets on the 1st of each month). |
| `interval` | `[7]` | Weekly tracking (resets every 7 days from the period anchor). |
| `manual` | `null` | User-controlled periods (e.g. refill whenever paid); no automatic next reset date. |

---

## Storage rules

- **`reset_days` is a JSON column** (nullable). Persist values as a **JSON array**, not a bare scalar.
- **`interval`:** use `[7]` for a 7-day cycle, not `7`. The service reads the **first** normalized value from the array as the interval length in days.
- **`date_fixed`:** each array element is a **day of month** (1–31). Invalid entries are dropped; at least one valid day is required when the type is `date_fixed`.
- **`manual`:** set `reset_days` to `null`. The service does not use `reset_days` for manual budgets.

The `Budget` model casts `reset_days` to `array` when loaded from the database. `BudgetService::resetDays()` also accepts a JSON string or a single string day for legacy/raw reads, but **new data should always use a JSON array** (or `null` for manual).

---

## Behavior by type

### `date_fixed`

- **Current period start:** the most recent reset day in `reset_days` that is on or before today (in the user’s display timezone).
- **Next reset:** the next calendar day in `reset_days` after the current period start (may be later in the same month or in a following month).
- **Period end:** the day before the next reset, end of day.
- **Month-end:** days like `31` are clamped to the last day of shorter months (e.g. April 30).

### `interval`

- **Interval length:** first value in `reset_days` after normalization (1–366 days).
- **Anchor:** latest `budget_logs.start_date` for the budget, or `budgets.created_at` if there is no log yet.
- **Current period start:** the start of the N-day window that contains “now”, counting forward from the anchor in steps of N days.
- **Next reset:** current period start plus N days.
- **Period end:** the day before the next reset, end of day.

### `manual`

- **Current period start:** latest log’s `start_date`, or `budgets.created_at` if there is no log.
- **Next reset:** none (`end_date` for status is open-ended until a log is closed or a new cycle is created).
- **Next cycle (logs):** when advancing with `createNextCycleLog`, the next period can start the day after the previous log’s `end_date` if set.

### Boot-time cycle sync

- A first `budget_logs` row is created when a budget is created.
- The mobile boot sync endpoint creates a log for the current active period if one is missing.
- For `date_fixed` and `interval`, when the latest log is from an expired period, the service finalizes that log’s `actual_spent` and creates **one** new log for the current period.
- Missed automatic periods are **not backfilled**. If a weekly budget is not opened for three cycles, sync does not create three historical logs and does **not** apply rollover from the old log.
- Rollover on sync applies only when advancing to the immediate next period (no skipped cycles between the latest log and the current period).
- `manual` budgets are not automatically advanced once they already have a log.

---

## Rollover (optional)

When `budgets.rollover` is `true`, `createNextCycleLog` adds unused funds from the previous log to the new cycle:

```text
rolloverAmount = max(0, last_log.allocated_amount - last_log.actual_spent)
next_log.allocated_amount = budgets.amount + rolloverAmount
```

Example: `amount = 5000`, last log `allocated_amount = 6000`, `actual_spent = 500` → `rolloverAmount = 5500` → next `allocated_amount = 10500.00`.

---

## Examples (database / API payloads)

```json
{
  "reset_type": "date_fixed",
  "reset_days": [1, 16]
}
```

```json
{
  "reset_type": "date_fixed",
  "reset_days": [1]
}
```

```json
{
  "reset_type": "interval",
  "reset_days": [7]
}
```

```json
{
  "reset_type": "manual",
  "reset_days": null
}
```

---

## Related code

| Piece | Location |
| --- | --- |
| Reset type enum | `app/Enums/BudgetResetType.php` |
| Period and status logic | `app/Services/BudgetService.php` |
| `budgets` schema | `database/migrations/2026_05_18_200000_create_budgets_table.php` |
| `rollover` column | `database/migrations/2026_05_19_000000_add_rollover_to_budgets_table.php` |
| Cycle history | `budget_logs` (`start_date`, `end_date`, `allocated_amount`, `actual_spent`) |
