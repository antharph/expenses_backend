# Project specification: Expenses

## Overview

This project is an **expense tracking** system for authenticated users. The primary client is a **mobile app** where users interact through a **chat-style interface**. Parsed and manual expense entries are **persisted in a database** and can be **queried by date range** with aggregated totals.

## Goals

- Let users record expenses quickly, either from **receipt images** (AI-assisted) or **minimal manual input**.
- Keep a **durable history** of expenses per user.
- Support **natural-language (or chat-prompt) requests** to list expenses for a date range and show a **running or summary total** with the list.

## User journey

1. User **logs in** in the mobile app.
2. User opens the **chatbox** (main interaction surface).
3. User may:
   - **Upload a photo of a receipt**; the system sends the image to **Gemini** (or equivalent vision/LLM flow), which extracts structured fields.
   - **Type minimal data** (e.g. item name and total only); the system stores what is provided and leaves other fields empty or defaulted as defined by product rules.

## Data model (conceptual)

Each expense record is associated with the **authenticated user** and includes at least:

| Field        | Source                          | Notes                                      |
|-------------|----------------------------------|--------------------------------------------|
| Item name   | AI and/or user                  | Required for meaningful records            |
| Category    | AI (inferred) and/or user       | Optional if user only enters name + total |
| Quantity    | AI (inferred) and/or user       | Optional; AI infers when possible          |
| Total       | AI and/or user                  | Monetary amount for the line/expense       |
| Timestamps  | System                          | Created/updated; used for date-range views |

**AI behavior:** Gemini (or the chosen model) **infers** missing fields where reasonable from the receipt image (e.g. category, quantity, line vs. receipt total). Product rules should define validation, fallbacks, and when to prompt the user to confirm or edit.

**Manual minimum:** User can supply **only item name and total**; the app and API accept this subset and persist it consistently with full AI-extracted rows.

## Chat interactions

### Recording

- **Receipt path:** User attaches image in chat → backend (or client) calls vision/LLM → structured payload → validate → save.
- **Manual path:** User types or selects fields in chat → save.

### Querying

- User can request something equivalent to: **“Show expenses from [start] to [end]”** (natural language or structured date range, depending on implementation).
- **Response:** A **list of expenses** in that range, with a **total** shown **at the bottom** of the list (sum of totals or as defined by product rules for the selected period).

## Technical boundaries (high level)

- **Authentication:** Users must be logged in; all expense data is scoped to the user (or tenant, if extended later).
- **API:** HTTP APIs under `/api` (per project conventions) for mobile clients; stable JSON, predictable errors, and validation messages suitable for Flutter.
- **Persistence:** Relational (or project-standard) database as the source of truth for saved expenses and query results.

## Out of scope (unless added later)

- Multi-currency policy, tax splits, recurring expenses, budgets, and export formats are not specified here unless the product adds them explicitly.

## Success criteria

- Users can log in, add expenses via **receipt + AI** or **name + total**, see data **saved reliably**, and ask for **date-range lists with a clear total** at the bottom of the list.
