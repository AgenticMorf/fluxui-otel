---
title: Usage
---

# Usage

## Routes

The package registers (with `web` and `auth` middleware):

- `GET /otel` — Dashboard (named `otel.dashboard`)
- `GET /otel/traces` — Traces viewer (`otel.traces`)
- `GET /otel/metrics` — Metrics viewer (`otel.metrics`)
- `GET /otel/services` — Services viewer (`otel.services`)

## Sidebar

Add an Otel nav item in your sidebar pointing to `route('otel.dashboard')` (e.g. under a "System" or "Observability" group).

## Otel Ingest

The package exposes `POST /api/otel/ingest` for browser telemetry. It proxies trace payloads to the Otel collector. Disable CSRF for this route via `withoutMiddleware([ValidateCsrfToken::class])` (already configured).

## Publishing Views

To customize the dashboard views:

```bash
php artisan vendor:publish --tag=fluxui-otel-views
```
