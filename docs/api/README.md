# Expenses API (HTTP)

All endpoints are prefixed with **`/api`**. The current version lives under **`/api/v1`**.

| Topic | File |
| --- | --- |
| Registration, login, Google (Firebase), dashboard, logout | [authentication.md](./authentication.md) |

## Base URL

Use your deployed app origin (for example `https://api.example.com`) or local Docker host. There is no trailing slash in client base URLs; paths always start with `/api/...`.

## Authentication (Sanctum)

After login, registration, or Google sign-in, the API returns a **Bearer** token (`token` field). Send it on protected routes:

```http
Authorization: Bearer {token}
```

Tokens are **Laravel Sanctum** personal access tokens.

## Firebase (Google Sign-In)

The backend verifies **Firebase Auth ID tokens** (issuer `https://securetoken.google.com/{projectId}`). Configure `FIREBASE_PROJECT_ID` in `.env` to match the Firebase project used by the Flutter app.
