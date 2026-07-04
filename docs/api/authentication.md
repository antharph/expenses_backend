# Authentication & session API

Base path: **`/api/v1`**. JSON request bodies should set `Content-Type: application/json` and `Accept: application/json`.

---

## User object

Auth and dashboard responses include a `user` object:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | User ID |
| `name` | string | Display name |
| `email` | string | Email address |
| `password_auth_enabled` | boolean | `true` for email/password accounts; `false` for Google-only sign-in (password change unavailable) |

---

## POST `/api/v1/register`

Creates a user and returns a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 255 |
| `email` | string | Required, valid email, unique |
| `password` | string | Required, minimum 6 characters, must match confirmation |
| `password_confirmation` | string | Required, must equal `password` |
| `timezone` | string | Optional. IANA timezone identifier (e.g. `Asia/Manila`). When omitted, defaults to `UTC`. |

**201 Created**

```json
{
  "message": "Registered successfully.",
  "token": "1|plainTextTokenValue",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "password_auth_enabled": true
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
| `timezone` | string | Optional. IANA timezone identifier (e.g. `Asia/Manila`). **Updated** on the user record when sent. |

**200 OK** — same envelope as register (`message`, `token`, `token_type`, `user`).

**422** — invalid credentials (`errors.email`).

---

## POST `/api/v1/auth/google`

Exchanges a **Firebase Auth ID token** (from `FirebaseAuth.instance.currentUser.getIdToken()` after Google sign-in on the client) for a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `id_token` | string | Required; Firebase JWT |
| `timezone` | string | Optional. IANA timezone identifier (e.g. `Asia/Manila`). Applied on sign-up and updated on later sign-ins when sent. When omitted on create, defaults to `UTC`. |

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
    "email": "ada@example.com",
    "password_auth_enabled": true
  }
}
```

**401** — missing or invalid token.

---

## PATCH `/api/v1/user/profile`

Updates the authenticated user's display name.

**Headers**

- `Authorization: Bearer {token}`

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | Required, max 255 |

**200 OK**

```json
{
  "message": "Profile updated.",
  "user": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "password_auth_enabled": true
  }
}
```

**401** — missing or invalid token.

**422** — validation errors.

---

## PUT `/api/v1/user/password`

Updates the authenticated user's password. Only available when `user.password_auth_enabled` is `true` (email/password accounts).

**Headers**

- `Authorization: Bearer {token}`

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `current_password` | string | Required; must match the user's current password |
| `password` | string | Required; Laravel default password rules; must match confirmation |
| `password_confirmation` | string | Required; must equal `password` |

**200 OK**

```json
{
  "message": "Password updated."
}
```

**403 Forbidden** — `password_auth_enabled` is `false` (Google-only account).

**422** — validation errors (e.g. wrong `current_password`).

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
