# Expenses API (HTTP)

All endpoints are prefixed with **`/api`**. The current version lives under **`/api/v1`**.

| Topic | File |
| --- | --- |
| Registration, login, Google (Firebase), dashboard, logout | [authentication.md](./authentication.md) |
| Auth providers, linking, password capability (`auth_provider`, `password_auth_enabled`) | [../auth-providers.md](../auth-providers.md) |
| Categories (list for pickers) | [categories.md](./categories.md) |
| Expenses (list, by week, create, receipt upload) | [expenses.md](./expenses.md) |
| Budgets (current progress, period history) | [budgets.md](./budgets.md) |

## Base URL

Use your deployed app origin (for example `https://api.example.com`) or local Docker host. There is no trailing slash in client base URLs; paths always start with `/api/...`.

## Authentication (Sanctum)

After login, registration, or Google sign-in, the API returns a **Bearer** token (`token` field). Send it on protected routes:

```http
Authorization: Bearer {token}
```

Tokens are **Laravel Sanctum** personal access tokens.

## Firebase (Google, Apple, Facebook)

Social sign-in uses **Firebase Auth ID tokens** (issuer `https://securetoken.google.com/{projectId}`). Clients should call **`POST /api/v1/auth/firebase`** with the ID token after any Firebase social sign-in. Configure `FIREBASE_PROJECT_ID` in `.env` to match the Firebase project used by the Flutter app.

See [auth-providers.md](../auth-providers.md) for how `auth_provider` and `password_auth_enabled` are set, account linking rules, and adding Apple/Facebook later.
