# Authentication & session API

Base path: **`/api/v1`**. JSON request bodies should set `Content-Type: application/json` and `Accept: application/json`.

---

## POST `/api/v1/register`

Creates a user and returns a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 255 |
| `email` | string | Required, valid email, unique |
| `password` | string | Required, Laravel default password rules, must match confirmation |
| `password_confirmation` | string | Required, must equal `password` |

**201 Created**

```json
{
  "message": "Registered successfully.",
  "token": "1|plainTextTokenValue",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  }
}
```

**422 Unprocessable Entity** — validation errors in Laravel format (`errors` object).

---

## POST `/api/v1/login`

Authenticates with email and password and returns a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `email` | string | Required, valid email |
| `password` | string | Required |

**200 OK** — same envelope as register (`message`, `token`, `token_type`, `user`).

**422** — invalid credentials (`errors.email`).

---

## POST `/api/v1/auth/google`

Exchanges a **Firebase Auth ID token** (from `FirebaseAuth.instance.currentUser.getIdToken()` after Google sign-in on the client) for a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `id_token` | string | Required; Firebase JWT |

**200 OK** — same envelope as login (`message`, `token`, `token_type`, `user`). If the email already exists, the account is linked by storing `firebase_uid`.

**422** — invalid or unverifiable token, missing email claim, or misconfigured `FIREBASE_PROJECT_ID`.

---

## GET `/api/v1/dashboard`

Returns the welcome payload for the authenticated user.

**Headers**

- `Authorization: Bearer {token}`

**200 OK**

```json
{
  "message": "Welcome",
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com"
  }
}
```

**401** — missing or invalid token.

---

## POST `/api/v1/logout`

Revokes the **current** Sanctum token (the one sent in `Authorization`).

**Headers**

- `Authorization: Bearer {token}`

**200 OK**

```json
{
  "message": "Logged out successfully."
}
```

**401** — missing or invalid token.
