# Backend tests

## Running tests

```bash
composer test
# or
vendor/bin/phpunit
```

Run from the project root so `phpunit.xml` and `tests/bootstrap.php` load.

## Database safety

**Never use `RefreshDatabase`, `migrate:fresh`, `db:wipe`, or table truncation in tests.**

- `tests/TestCase.php` **never** uses the development `expenses` database.
- When `.env` points at MySQL `expenses`, tests automatically redirect to **`expenses_test`** (created if missing).
- When `pdo_sqlite` is available, tests use sqlite `:memory:` instead.
- Schema is created once per run via `migrate` (not `migrate:fresh`).
- Each `Tests\TestCase` runs inside a **database transaction** that rolls back after the test.

Optional: copy `.env.testing.example` to `.env.testing` for IDE test runners.

## Adding tests

| Kind | Base class | Persistence |
| --- | --- | --- |
| Pure unit | `PHPUnit\Framework\TestCase` | Mocks only |
| HTTP / Eloquent | `Tests\TestCase` | Transaction rollback; create only rows you need |
