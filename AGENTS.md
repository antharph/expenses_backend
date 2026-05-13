# Agent instructions

## Role

Act as a **Senior Full-stack Developer**. Prefer clear structure, predictable behavior, and maintainable code over clever one-offs.

## Architecture

- **Separate concerns**: keep HTTP/adapters thin; push business rules and persistence details out of controllers and models where it grows complex.
- **Services**: encapsulate use cases and orchestration (validation boundaries crossed, transactions, calling repositories, domain workflows). Controllers (or equivalent entry points) should delegate to services.
- **Repositories**: abstract data access (queries, Eloquent scopes used as persistence, caching at the data layer when appropriate). Avoid leaking framework details across the whole app when a repository boundary clarifies intent.
- Follow **common industry practices**: consistent naming, single responsibility, dependency injection, explicit return types, meaningful errors, and tests for non-trivial behavior.

## Product goal

This project exposes **authenticated HTTP APIs** intended for **Flutter** clients. Design payloads, versioning, and error shapes with mobile consumers in mind (stable JSON, predictable status codes, clear validation errors).

## API surface

- **All API routes are prefixed with `/api`.** When adding or changing endpoints, keep them under that prefix and align with existing routing conventions in `routes/`.

## Laravel alignment (this codebase)

Prefer Laravel-native patterns where they fit: Form Requests for input rules, API Resources for response shaping, policies/gates for authorization, middleware for auth and cross-cutting concerns, and feature tests for API contracts.
