---
title: Installation
---

# Installation

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Livewire ^3.0 or ^4.0
- livewire/flux ^2.0
- agenticmorf/fluxui-loki ^1.0
- keepsuit/laravel-opentelemetry ^1.0
- Jaeger, Loki, Prometheus (e.g. via Docker Compose / Sail)

## Composer

```bash
composer require agenticmorf/fluxui-otel
```

[Packagist](https://packagist.org/packages/agenticmorf/fluxui-otel)

## Environment

Set in `.env` (defaults assume Sail/Docker):

```
JAEGER_URL=http://jaeger:16686
LOKI_URL=http://loki:3100
PROMETHEUS_URL=http://prometheus:9090
OTEL_COLLECTOR_URL=http://otel-collector:4318
```
