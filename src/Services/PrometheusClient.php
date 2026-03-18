<?php

namespace AgenticMorf\FluxUIOtel\Services;

use Illuminate\Support\Facades\Http;

class PrometheusClient
{
    public function __construct(
        protected string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        $url = rtrim((string) config('fluxui-otel.prometheus_url', 'http://prometheus:9090'), '/');

        return new self($url);
    }

    /**
     * @return array<string, mixed>
     */
    public function query(string $query, ?int $time = null): array
    {
        $params = [
            'query' => $query,
        ];

        if ($time !== null) {
            $params['time'] = $time;
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/v1/query", $params);

        $response->throw();

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function queryRange(string $query, int $start, int $end, int|string $step): array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/v1/query_range", [
                'query' => $query,
                'start' => $start,
                'end' => $end,
                'step' => $step,
            ]);

        $response->throw();

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
