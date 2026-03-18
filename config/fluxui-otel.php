<?php

return [
    'jaeger_url' => env('JAEGER_URL', 'http://jaeger:16686'),

    'loki_url' => env('LOKI_URL', 'http://loki:3100'),

    'prometheus_url' => env('PROMETHEUS_URL', 'http://prometheus:9090'),

    'otel_collector_url' => env('OTEL_COLLECTOR_URL', 'http://otel-collector:4318'),

    'layout' => 'components.layouts.app.sidebar',

    'middleware' => ['web', 'auth'],

    'route_prefix' => 'otel',

    'route_name_prefix' => 'otel.',

    'poll_interval' => (int) env('FLUXUI_OTEL_POLL_INTERVAL', 5),

    'default_time_range' => env('FLUXUI_OTEL_DEFAULT_TIME_RANGE', '1h'),

    'default_metrics_query' => env('FLUXUI_OTEL_DEFAULT_METRICS_QUERY', 'up'),

    'default_logs_query' => env('FLUXUI_OTEL_DEFAULT_LOGS_QUERY', '{compose_service=~".+"}'),
];
