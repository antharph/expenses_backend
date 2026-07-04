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
| `password_auth_enabled` | boolean | `true` when the user can change password in the app; `false` for social-only accounts |
| `auth_provider` | string | `email`, `google`, `apple`, or `facebook` — used for Account security messaging when `password_auth_enabled` is `false` |

See [auth-providers.md](../auth-providers.md) for linking rules, Firebase provider mapping, and Flutter behavior.

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
    "password_auth_enabled": true,
    "auth_provider": "email"
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

## POST `/api/v1/auth/firebase`

Exchanges a **Firebase Auth ID token** (from `FirebaseAuth.instance.currentUser.getIdToken()` after Google, Apple, or Facebook sign-in on the client) for a Sanctum token. The backend reads `firebase.sign_in_provider` from the verified JWT to set `user.auth_provider`.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `id_token` | string | Required; Firebase JWT |
| `timezone` | string | Optional. IANA timezone identifier (e.g. `Asia/Manila`). Applied on sign-up and updated on later sign-ins when sent. When omitted on create, defaults to `UTC`. |

**200 OK** — same envelope as login (`message`, `token`, `token_type`, `user`). If the email already exists and `password_auth_enabled` is `true`, the account is linked by storing `firebase_uid` without disabling in-app password changes.

**422** — invalid or unverifiable token, missing email claim, unsupported sign-in provider, or misconfigured `FIREBASE_PROJECT_ID`.

---

## POST `/api/v1/auth/google`

**Deprecated.** Alias for `POST /api/v1/auth/firebase`. Existing clients may continue using this path during transition.

Exchanges a **Firebase Auth ID token** (from `FirebaseAuth.instance.currentUser.getIdToken()` after Google sign-in on the client) for a Sanctum token.

**Body**

| Field | Type | Rules |
| --- | --- | --- |
| `id_token` | string | Required; Firebase JWT |
| `timezone` | string | Optional. IANA timezone identifier (e.g. `Asia/Manila`). Applied on sign-up and updated on later sign-ins when sent. When omitted on create, defaults to `UTC`. |

**200 OK** — same envelope as login (`message`, `token`, `token_type`, `user`). If the email already exists and `password_auth_enabled` is `true`, the account is linked by storing `firebase_uid` without disabling in-app password changes.

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
    "password_auth_enabled": true,
    "auth_provider": "email"
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
    "password_auth_enabled": true,
    "auth_provider": "email"
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
