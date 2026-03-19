---
title: Configuration
---

# Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=fluxui-otel-config
```

Edit `config/fluxui-otel.php`:

- **jaeger_url** — Jaeger UI URL
- **loki_url** — Loki API URL
- **prometheus_url** — Prometheus API URL
- **otel_collector_url** — Otel collector URL for ingest
- **layout** — Livewire layout (default: `components.layouts.app.sidebar`)
- **middleware** — Route middleware (default: `web`, `auth`)
- **route_prefix** — URL prefix (default: `otel`)
- **route_name_prefix** — Route name prefix (default: `otel.`)
- **poll_interval** — Dashboard poll interval in seconds
- **default_time_range** — Default time range for queries
- **default_metrics_query** — Default Prometheus query
- **default_logs_query** — Default Loki query
