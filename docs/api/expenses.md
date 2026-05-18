# Expenses

All paths require **`Authorization: Bearer {token}`** (Sanctum).

## List expenses (paginated)

**`GET /api/v1/expenses`**

### Query parameters

| Name | Description |
| --- | --- |
| `page` | Optional. Page number (default `1`). Page size comes from **`PAGINATION_PER_PAGE`** in the API `.env`, clamped between 1 and 100, exposed as `config('app.pagination_per_page')`. |
| `from` | Optional. Inclusive start date, format **`Y-m-d`** (e.g. `2026-05-18`). Must be sent together with `to`. Filters on **`transaction_at`** (or **`created_at`** when `transaction_at` is null) using inclusive calendar-day boundaries in **`DEFAULT_TIMEZONE`** (`config('app.expenses_display_timezone')`). Stored instants are UTC; `from`/`to` are interpreted in the display timezone. |
| `to` | Optional. Inclusive end date, format **`Y-m-d`**. Must be sent together with `from` and must be on or after `from`. Same timezone and column rules as `from`. |

### Success

**`200`** — Laravel paginator JSON with `data`, `links`, and `meta`:

- **`data`**: array of expense objects.
- **`meta`**: includes `current_page`, `last_page`, `per_page`, `total`, `sum_total`, etc.
  - **`total`**: integer count of all expenses matching the query (across pages), including date filters when `from`/`to` are set.
  - **`sum_total`**: string decimal (two places) — sum of the `total` field for all matching expenses, not only the current page.

Each expense object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Primary key. |
| `item` | string | Description / line item name. |
| `quantity` | integer | Units purchased (minimum `1`). |
| `price` | string (decimal) | Unit price with two decimal places. |
| `total` | string (decimal) | Line total (`price` × `quantity`) with two decimal places. |
| `category_id` | integer or `null` | Optional foreign key to `categories.id`. |
| `category` | object or omitted | When the category relation is loaded and set: `id`, `code`, `name`. Omitted when uncategorized or not loaded. |
| `store_id` | integer or `null` | Optional foreign key to `stores.id` when the merchant was resolved from a receipt. |
| `transaction_number` | string or `null` | Receipt or transaction number when captured from a receipt. |
| `invoice_number` | string or `null` | Invoice or official receipt number when captured from a receipt. |
| `date` | string | Transaction instant (`transaction_at`, or `created_at` when unset) formatted as **`M/D`** (`n/j`), using **`DEFAULT_TIMEZONE`** from the API `.env`. Examples: `3/9`, `12/31`. |
| `receipt_url` | `null` | Receipt images are not stored; this field is reserved for clients and remains null. |

### Errors

- **`401`** — missing or invalid token.
- **`422`** — invalid or incomplete date filter (e.g. only `from` provided, bad format, or `to` before `from`).
- **`429`** — throttled (if rate limiting applies).

## Delete expense

**`DELETE /api/v1/expenses/{id}`**

- **`id`**: integer primary key of the expense.

Only expenses that belong to the authenticated user may be deleted. If no matching row exists for that user, the API responds with **`404`** (same as unknown id).

### Success

**`204`** — no response body.

### Errors

- **`401`** — unauthenticated.
- **`404`** — expense not found for this user.

## Create expense

**`POST /api/v1/expenses`**

### Request

**`multipart/form-data`** fields:

| Field | Rules |
| --- | --- |
| `receipt` | Optional image file (jpeg, png, gif, webp, etc.), max **5120 KB**. When present, the image is **not stored**. It is sent once to **Google Gemini** (`GEMINI_MODEL`, e.g. `gemini-2.5-flash-lite`) using `GEMINI_AI_KEY`. The API loads **all categories** (`id`, `name`, and `description` when set), sends them to Gemini with the image, and asks the model for a **`category_id` per line item** when a listed category clearly fits. Persisted rows use only ids that exist in `categories`. |
| `item` | Required **unless** `receipt` is uploaded; otherwise ignored (Gemini supplies values). Max 255 characters. |
| `quantity` | Required **unless** `receipt` is uploaded; otherwise ignored. Integer, minimum `1` (defaults to `1` when omitted). |
| `price` | Required **unless** `receipt` is uploaded; otherwise ignored. Unit price as a number, minimum 0. The API stores `total` as `price` × `quantity` (two decimal places). |
| `category_id` | Optional. Integer; must exist in `categories.id` when provided. With a **`receipt`**, if set it overrides Gemini’s inferred category for **every** expense row created from that upload; if omitted, Gemini’s per-line (or inferred) category is used. **Without a receipt**, when omitted the API calls Gemini with the `item` text and the same category list (including `description`) to infer `category_id`; if inference fails or is uncertain, the expense is stored with `category_id` `null`. |

Receipt uploads are parsed by Gemini for `quantity`, unit `price`, and line `total` per row, plus receipt-level merchant and transaction fields when visible: `store_name`, `legal_name`, `address`, `transaction_number`, `invoice_number`, and `transaction_at` (ISO 8601). Matching stores are looked up or created in `stores` by `name`, `legal_name`, and `address`; the resulting `store_id` is saved on each expense row from that upload. The API reconciles missing amount values so `total` = `price` × `quantity` (two decimal places). Manual entries (no receipt) set `transaction_at` to the same instant as `created_at` when no transaction date is provided.

### Success

**`201`** — response shape:

- **No `receipt`:** JSON object **`data`** is a single expense (same shape as list items).
- **With `receipt`:** JSON **`data`** is always an **array** of one or more created expenses (one per non-empty `line_items` entry when the model returns line breakdown; otherwise one row from the receipt total). Each element matches the list item shape (`category_id`, optional nested `category`, etc.).

### Errors

- **`401`** — unauthenticated.
- **`422`** — validation errors (`errors` object with field messages), or receipt interpretation failed (JSON body may include a top-level `message` string when Gemini could not produce usable data).
- **`429`** — throttled.
