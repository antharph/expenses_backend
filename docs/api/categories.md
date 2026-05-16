# Categories

All paths require **`Authorization: Bearer {token}`** (Sanctum).

## List categories

**`GET /api/v1/categories`**

Returns every category ordered by **`name`**, as a Laravel resource collection:

**`200`** — `{ "data": [ { "id": 1, "name": "Groceries" }, ... ] }`

### Errors

- **`401`** — missing or invalid token.
