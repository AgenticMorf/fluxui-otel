<?php

namespace AgenticMorf\FluxUIOtel\Services;

use Illuminate\Support\Facades\Http;

class LokiClient
{
    public function __construct(
        protected string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        $url = rtrim((string) config('fluxui-otel.loki_url', 'http://loki:3100'), '/');

        return new self($url);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryRange(
        string $query,
        int $limit = 100,
        ?int $start = null,
        ?int $end = null,
        string $direction = 'backward',
    ): array {
        $now = (int) (microtime(true) * 1e9);

        $params = [
            'query' => $query,
            'limit' => $limit,
            'direction' => $direction,
            'end' => $end ?? $now,
        ];

        if ($start !== null) {
            $params['start'] = $start;
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->get("{$this->baseUrl}/loki/api/v1/query_range", $params);

        $response->throw();

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<int, array{timestamp: int, labels: array<string, string>, line: string}>
     */
    public function parseEntries(array $response): array
    {
        $entries = [];
        $results = $response['data']['result'] ?? [];

        if (! is_array($results)) {
            return $entries;
        }

        foreach ($results as $stream) {
            if (! is_array($stream)) {
                continue;
            }

            $labels = is_array($stream['stream'] ?? null) ? $stream['stream'] : [];
            $values = is_array($stream['values'] ?? null) ? $stream['values'] : [];

            foreach ($values as $value) {
                if (! is_array($value) || count($value) < 2) {
                    continue;
                }

                $entries[] = [
                    'timestamp' => (int) $value[0],
                    'labels' => $labels,
                    'line' => (string) $value[1],
                ];
            }
        }

        return $entries;
    }
}
