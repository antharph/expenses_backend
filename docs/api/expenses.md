# Expenses

All paths require **`Authorization: Bearer {token}`** (Sanctum).

## List expenses (paginated)

**`GET /api/v1/expenses`**

### Query parameters

| Name | Description |
| --- | --- |
| `page` | Optional. Page number (default `1`). Page size comes from **`PAGINATION_PER_PAGE`** in the API `.env`, clamped between 1 and 100, exposed as `config('app.pagination_per_page')`. |

### Success

**`200`** — Laravel paginator JSON with `data`, `links`, and `meta`:

- **`data`**: array of expense objects.
- **`meta`**: includes `current_page`, `last_page`, `per_page`, `total`, etc.

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
| `date` | string | Created instant formatted as **`M/D`** (`n/j`), using **`DEFAULT_TIMEZONE`** from the API `.env` (stored in UTC in the database). Examples: `3/9`, `12/31`. |
| `receipt_url` | `null` | Receipt images are not stored; this field is reserved for clients and remains null. |

### Errors

- **`401`** — missing or invalid token.
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
| `receipt` | Optional image file (jpeg, png, gif, webp, etc.), max **5120 KB**. When present, the image is **not stored**. It is sent once to **Google Gemini** (`GEMINI_MODEL`, e.g. `gemini-2.5-flash-lite`) using `GEMINI_AI_KEY`. The API loads **all categories** (`id` and `name` only), sends them to Gemini with the image, and asks the model for a **`category_id` per line item** when a listed category clearly fits. Persisted rows use only ids that exist in `categories`. |
| `item` | Required **unless** `receipt` is uploaded; otherwise ignored (Gemini supplies values). Max 255 characters. |
| `quantity` | Required **unless** `receipt` is uploaded; otherwise ignored. Integer, minimum `1` (defaults to `1` when omitted). |
| `price` | Required **unless** `receipt` is uploaded; otherwise ignored. Unit price as a number, minimum 0. The API stores `total` as `price` × `quantity` (two decimal places). |
| `category_id` | Optional. Integer; must exist in `categories.id` when provided. With a **`receipt`**, if set it overrides Gemini’s inferred category for **every** expense row created from that upload; if omitted, Gemini’s per-line (or inferred) category is used. |

Receipt uploads are parsed by Gemini for `quantity`, unit `price`, and line `total` per row. The API reconciles missing values so `total` = `price` × `quantity` (two decimal places)—for example, quantity + line total derives unit price, or a lone line total implies `quantity` `1`.

### Success

**`201`** — response shape:

- **No `receipt`:** JSON object **`data`** is a single expense (same shape as list items).
- **With `receipt`:** JSON **`data`** is always an **array** of one or more created expenses (one per non-empty `line_items` entry when the model returns line breakdown; otherwise one row from the receipt total). Each element matches the list item shape (`category_id`, optional nested `category`, etc.).

### Errors

- **`401`** — unauthenticated.
- **`422`** — validation errors (`errors` object with field messages), or receipt interpretation failed (JSON body may include a top-level `message` string when Gemini could not produce usable data).
- **`429`** — throttled.
