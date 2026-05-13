# Technical stack

This document describes the **runtime, frameworks, and major libraries** used by this project. For product behavior and user flows, see [PROJECT_SPEC.md](./PROJECT_SPEC.md).

## Runtime and server

| Layer | Choice | Notes |
|--------|--------|--------|
| Language | **PHP 8.4** | Target runtime; `composer.json` currently allows `^8.3`, which includes 8.4. |
| Web server | **Apache** | Serves the Laravel application (e.g. in LAMP / Docker LAMP deployments). |
| Database | **MySQL** | Primary relational store for users, expenses, and related data. |

## Backend application

| Component | Version / constraint | Role |
|-----------|----------------------|------|
| **Laravel** | **13.8.0** (locked in `composer.lock`; requirement `^13.7` in `composer.json`) | HTTP layer, routing, auth, queues, Eloquent, `/api` for mobile clients. |
| **Laravel Fortify** | `^1.34` | Authentication scaffolding (aligned with starter kit patterns). |
| **Inertia (server)** | `inertiajs/inertia-laravel` `^3.0` | Bridges Laravel to the Inertia admin UI (non-API HTML responses). |
| **Laravel Sanctum** | (package) | **API token** authentication for stateless access to `/api` routes (e.g. mobile clients send `Authorization: Bearer` tokens). |
| **kreait/laravel-firebase** | (package) | **Firebase Authentication** integration in Laravel: verify Firebase ID tokens, access Firebase Admin SDK features from PHP, and align app users with Firebase identities as needed. |

Architecture expectations for this repo (controllers thin, services/repositories where complexity grows, `/api`-prefixed routes for Flutter) are summarized in `AGENTS.md`.

## Authentication

| Concern | Stack choice | Role |
|---------|----------------|------|
| **Firebase Auth (mobile)** | **`kreait/laravel-firebase`** | Laravel verifies Firebase-issued credentials (typically ID tokens from the Flutter app) and maps or provisions local users tied to Firebase UIDs. |
| **API access tokens** | **Laravel Sanctum** | **Sanctum API / personal access tokens** authenticate `/api` requests (`Authorization: Bearer <token>`). The usual pattern is: verify a Firebase ID token once (via `kreait/laravel-firebase`), then issue a Sanctum token for ongoing API use. |

The **super admin** web area may continue to use **session-based** login (e.g. Fortify + web middleware) separate from the mobile Firebase + Sanctum path; keep route middleware and guards explicit per surface.

## Admin UI (super admin only)

The **web admin** is **not** the primary end-user surface; it is intended **only for super administrators** (operations, support, or internal tooling—as defined by your roles and policies).

| Technology | Role |
|------------|------|
| **Vue 3** | SPA-style pages built as Vue SFCs under `resources/`. |
| **Inertia.js** (`@inertiajs/vue3`, `@inertiajs/vite`) | Server-driven routing with client-side navigation; pages receive props from Laravel without building a separate JSON API for the admin shell. |
| **Vite** | Frontend build and dev server for Vue + Inertia assets. |
| **Tailwind CSS** | Styling for admin (and shared) UI components. |
| **TypeScript** | Type-checking for Vue/TS code (`vue-tsc`). |

## Mobile and external services

Per [PROJECT_SPEC.md](./PROJECT_SPEC.md):

- **Mobile client:** Flutter (or equivalent) signs in with **Firebase Authentication**, then calls **authenticated JSON APIs** under `/api` using **Laravel Sanctum**-issued API tokens (with **`kreait/laravel-firebase`** handling Firebase verification on the server as designed for your login/token exchange flow).
- **AI:** **Google Gemini** (or the same product family) for **receipt image parsing** into structured fields (item name, category, quantity, total), with inference for missing fields where appropriate.

## Quality and tooling (development)

| Tool | Purpose |
|------|---------|
| **Pest** | PHP feature and application tests. |
| **Laravel Pint** | PHP code style. |
| **ESLint / Prettier** | JS/TS/Vue lint and format. |

## Deployment shape (conceptual)

```
[Mobile app] ──► [Firebase Auth]
       │
       └──HTTPS (Bearer Sanctum token; Firebase used at login/exchange)──► [Apache] ──► [PHP / Laravel]
                                                                                │
                                                                                ▼
                                                                           [MySQL]

[Super admin browser] ──► [Apache] ──► Laravel + Inertia/Vue (same app, restricted routes/roles)
```

This stack keeps **one Laravel codebase**: JSON **API** for mobile (**Firebase** + **Sanctum**), **Inertia + Vue** for the small super-admin web area, and **MySQL** as the shared database behind both.
