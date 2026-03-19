# agenticmorf/fluxui-otel

Documentation is available on [GitHub Pages](https://agenticmorf.github.io/fluxui-otel/).

Flux UI OpenTelemetry observability dashboards for Laravel Livewire. Dashboard, traces, metrics, and services viewers backed by Jaeger, Loki, and Prometheus.

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Livewire ^3.0 or ^4.0
- livewire/flux ^2.0
- agenticmorf/fluxui-loki ^1.0
- keepsuit/laravel-opentelemetry ^1.0
- Jaeger, Loki, Prometheus (e.g. via Docker Compose / Sail)

## Installation

```bash
composer require agenticmorf/fluxui-otel
```

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=fluxui-otel-config
```

Set environment variables in `.env`:

```
JAEGER_URL=http://jaeger:16686
LOKI_URL=http://loki:3100
PROMETHEUS_URL=http://prometheus:9090
OTEL_COLLECTOR_URL=http://otel-collector:4318
```

## Routes

With default config, these routes are registered (with `web` and `auth` middleware):

- `GET /otel` — Dashboard
- `GET /otel/traces` — Traces viewer
- `GET /otel/metrics` — Metrics viewer
- `GET /otel/services` — Services viewer

## License

MIT
