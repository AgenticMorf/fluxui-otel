<?php

namespace AgenticMorf\FluxUIOtel\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class JaegerClient
{
    public function __construct(
        protected string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        $url = rtrim((string) config('fluxui-otel.jaeger_url', 'http://jaeger:16686'), '/');

        return new self($url);
    }

    /**
     * @return array<int, string>
     */
    public function getServices(): array
    {
        $payload = $this->requestWithFallback('/api/v3/services', '/api/services');

        $services = $payload['data'] ?? $payload['services'] ?? [];
        if (! is_array($services)) {
            return [];
        }

        $services = array_values(array_filter($services, static fn (mixed $value): bool => is_string($value) && $value !== ''));
        sort($services);

        return $services;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTraces(array $filters = []): array
    {
        $lookback = (string) ($filters['lookback'] ?? '1h');
        $seconds = $this->lookbackToSeconds($lookback);
        $end = now();
        $start = $end->copy()->subSeconds($seconds);

        $limit = (int) ($filters['limit'] ?? 20);
        $service = ! empty($filters['service']) && is_string($filters['service'])
            ? $filters['service']
            : (string) config('opentelemetry.service_name', config('app.name', 'app'));

        $params = [
            'limit' => $limit,
            'lookback' => $lookback,
            'service' => $service,
            'query.service_name' => $service,
            'query.start_time_min' => $start->utc()->format('Y-m-d\TH:i:s\Z'),
            'query.start_time_max' => $end->utc()->format('Y-m-d\TH:i:s\Z'),
            'query.search_depth' => $limit,
        ];

        if (! empty($filters['operation']) && is_string($filters['operation'])) {
            $params['operation'] = $filters['operation'];
            $params['query.operation_name'] = $filters['operation'];
        }

        try {
            $payload = $this->requestWithFallback('/api/traces', '/api/v3/traces', $params);
        } catch (RequestException $e) {
            if ($e->response?->status() === 404) {
                return [];
            }
            throw $e;
        }

        $traces = $payload['data'] ?? $payload['traces'] ?? [];

        return is_array($traces) ? array_values($traces) : [];
    }

    protected function lookbackToSeconds(string $lookback): int
    {
        return match ($lookback) {
            '15m' => 15 * 60,
            '30m' => 30 * 60,
            '6h' => 6 * 3600,
            '12h' => 12 * 3600,
            '24h', '1d' => 24 * 3600,
            '7d' => 7 * 24 * 3600,
            default => 3600,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrace(string $traceId): array
    {
        $payload = $this->requestWithFallback("/api/traces/{$traceId}", "/api/v3/traces/{$traceId}");
        $trace = $payload['data'] ?? $payload['trace'] ?? $payload['result'] ?? $payload;

        if (! is_array($trace)) {
            return [];
        }

        if (isset($trace[0]) && is_array($trace[0])) {
            return $trace[0];
        }

        return $trace;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function request(string $path, array $query = []): array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get($this->baseUrl.$path, $query);

        $response->throw();

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function requestWithFallback(string $primaryPath, string $fallbackPath, array $query = []): array
    {
        try {
            return $this->request($primaryPath, $query);
        } catch (\Throwable) {
            return $this->request($fallbackPath, $query);
        }
    }
}
