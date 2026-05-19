# Budgets API

Base path: **`/api/v1`**. Requires **`Authorization: Bearer {token}`** (Sanctum).

Amount fields are decimal strings with two fractional digits (e.g. `"5000.00"`). Dates in `period` and log entries are **calendar dates** in the user’s display timezone (`YYYY-MM-DD`).

---

## GET `/api/v1/budgets`

Lists the authenticated user’s budgets with **current pay-period progress** for the dashboard.

**200 OK**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Home Budget",
      "rollover_enabled": true,
      "period": {
        "start_date": "2026-05-16",
        "end_date": "2026-05-31"
      },
      "base_amount": "5000.00",
      "rollover_amount": "1000.00",
      "allocated_amount": "6000.00",
      "spent_amount": "1500.00",
      "remaining_amount": "4500.00",
      "percentage_spent": 25.0,
      "is_over_budget": false
    }
  ]
}
```

| Field | Meaning |
| --- | --- |
| `base_amount` | Configured budget amount for the period (`budgets.amount`). |
| `rollover_amount` | Portion of `allocated_amount` above `base_amount` (from the current period log when present). |
| `allocated_amount` | Spendable total for the period (`base` + `rollover` when a log exists). |
| `spent_amount` | Sum of linked category expenses in the current period. |
| `remaining_amount` | `allocated_amount − spent_amount` (may be negative when over budget). |
| `is_over_budget` | `true` when `spent_amount` exceeds `allocated_amount`. |

**401 Unauthenticated** — missing or invalid token.

---

## POST `/api/v1/budgets/sync-cycles`

Synchronizes the authenticated user’s budget cycle logs. The Flutter app should call this after a session is restored or a sign-in/register flow succeeds.

For each budget:

- If no log exists, creates the current log for the active period.
- For `date_fixed` and `interval`, if the latest log is from an expired period, finalizes that log’s `spent_amount` from linked category expenses and creates **one** new log for the current period.
- Missed periods are **not backfilled**. Rollover is applied once from the finalized latest log.
- `manual` budgets are not automatically advanced once they already have a log.

**200 OK**

Returns the same budget progress collection shape as `GET /api/v1/budgets`.

**401 Unauthenticated** — missing or invalid token.

---

## POST `/api/v1/budgets`

Creates a budget for the authenticated user.

**Body**

```json
{
  "name": "Home Budget",
  "amount": "5000.00",
  "reset_type": "date_fixed",
  "reset_days": [1, 16],
  "rollover": true,
  "category_ids": [1, 2]
}
```

| Field | Rules |
| --- | --- |
| `name` | Required string, max 255. |
| `amount` | Required numeric amount greater than 0. |
| `reset_type` | Required. One of `date_fixed`, `interval`, or `manual`. |
| `reset_days` | Required for `date_fixed` and `interval`; omit or `null` for `manual`. Fixed-date days must be `1`–`31`; interval budgets require exactly one day count. |
| `rollover` | Optional boolean, defaults to `false`. |
| `category_ids` | Optional category IDs to attach to the budget. |

**201 Created**

Returns the same budget progress resource shape as `GET /api/v1/budgets`. A first `budget_logs` entry is created for the budget’s current active period.

**422 Unprocessable Entity** — validation errors in Laravel format (`errors` object).

**401 Unauthenticated** — missing or invalid token.

---

## GET `/api/v1/budgets/{budget}/logs`

Returns **budget cycle history** (`budget_logs`) for one budget, newest period first.

**200 OK**

```json
{
  "data": [
    {
      "id": 12,
      "start_date": "2026-05-01",
      "end_date": "2026-05-15",
      "allocated_amount": "6000.00",
      "spent_amount": "5500.00",
      "rollover_amount": "500.00"
    }
  ]
}
```

| Field | Meaning |
| --- | --- |
| `rollover_amount` | Unused funds from the period: `max(0, allocated_amount − spent_amount)`. |

**404 Not Found** — budget does not exist or belongs to another user.

**401 Unauthenticated** — missing or invalid token.

---

## DELETE `/api/v1/budgets/{budget}`

Permanently deletes a budget owned by the authenticated user. Related `budget_logs` and category links are removed via database cascades.

**204 No Content** — budget deleted.

**404 Not Found** — budget does not exist or belongs to another user.

**401 Unauthenticated** — missing or invalid token.
