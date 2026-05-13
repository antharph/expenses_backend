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

Architecture expectations for this repo (controllers thin, services/repositories where complexity grows, `/api`-prefixed routes for Flutter) are summarized in `AGENTS.md`.

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

- **Mobile client:** Flutter (or equivalent) consuming **authenticated JSON APIs** under `/api`.
- **AI:** **Google Gemini** (or the same product family) for **receipt image parsing** into structured fields (item name, category, quantity, total), with inference for missing fields where appropriate.

## Quality and tooling (development)

| Tool | Purpose |
|------|---------|
| **Pest** | PHP feature and application tests. |
| **Laravel Pint** | PHP code style. |
| **ESLint / Prettier** | JS/TS/Vue lint and format. |

## Deployment shape (conceptual)

```
[Mobile app] ──HTTPS──► [Apache] ──► [PHP / Laravel]
                                         │
                                         ▼
                                    [MySQL]

[Super admin browser] ──► [Apache] ──► Laravel + Inertia/Vue (same app, restricted routes/roles)
```

This stack keeps **one Laravel codebase**: JSON **API** for mobile, **Inertia + Vue** for the small super-admin web area, and **MySQL** as the shared database behind both.
