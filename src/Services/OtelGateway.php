<?php

namespace AgenticMorf\FluxUIOtel\Services;

use AgenticMorf\FluxUIOtel\DTOs\LogEntry;
use AgenticMorf\FluxUIOtel\DTOs\Span;
use AgenticMorf\FluxUIOtel\DTOs\Trace;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;

class OtelGateway
{
    /** @var array<int, string> */
    protected const LINE_COLORS = [
        'text-blue-500 dark:text-blue-400',
        'text-red-500 dark:text-red-400',
        'text-green-500 dark:text-green-400',
        'text-amber-500 dark:text-amber-400',
        'text-purple-500 dark:text-purple-400',
        'text-pink-500 dark:text-pink-400',
        'text-cyan-500 dark:text-cyan-400',
        'text-emerald-500 dark:text-emerald-400',
    ];

    /** @var array<int, string> */
    protected const INDICATOR_COLORS = [
        'bg-blue-500',
        'bg-red-500',
        'bg-green-500',
        'bg-amber-500',
        'bg-purple-500',
        'bg-pink-500',
        'bg-cyan-500',
        'bg-emerald-500',
    ];

    public function __construct(
        protected JaegerClient $jaegerClient,
        protected LokiClient $lokiClient,
        protected PrometheusClient $prometheusClient,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            jaegerClient: JaegerClient::fromConfig(),
            lokiClient: LokiClient::fromConfig(),
            prometheusClient: PrometheusClient::fromConfig(),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, int|float|string>>
     */
    public function getTraces(array $filters = []): array
    {
        return Tracer::newSpan('otel.api.traces')->measure(function () use ($filters): array {
            $traces = $this->jaegerClient->getTraces($filters);

            return array_values(array_map(
                static fn (array $trace): array => Trace::fromArray($trace)->toArray(),
                array_filter($traces, 'is_array')
            ));
        });
    }

    /**
     * @return array{trace: array<string, int|float|string>, spans: array<int, array<string, mixed>>}
     */
    public function getTrace(string $traceId): array
    {
        return Tracer::newSpan('otel.api.trace.detail')->measure(function () use ($traceId): array {
            $trace = $this->jaegerClient->getTrace($traceId);
            $traceDto = Trace::fromArray($trace)->toArray();

            $processes = is_array($trace['processes'] ?? null) ? $trace['processes'] : [];
            $rawSpans = is_array($trace['spans'] ?? null) ? $trace['spans'] : [];

            $spans = array_values(array_map(
                static fn (array $span): array => Span::fromArray($span, $processes)->toArray(),
                array_filter($rawSpans, 'is_array')
            ));

            usort($spans, static fn (array $left, array $right): int => (int) ($left['start_time_micros'] <=> $right['start_time_micros']));

            return [
                'trace' => $traceDto,
                'spans' => $spans,
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function getServices(): array
    {
        return Tracer::newSpan('otel.api.services')->measure(fn (): array => $this->jaegerClient->getServices());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{timestamp: int, labels: array<string, string>, line: string}>
     */
    public function getLogs(array $filters = []): array
    {
        return Tracer::newSpan('otel.api.logs')->measure(function () use ($filters): array {
            $response = $this->lokiClient->queryRange(
                query: (string) ($filters['query'] ?? config('fluxui-otel.default_logs_query', '{compose_service=~".+"}')),
                limit: (int) ($filters['limit'] ?? 200),
                start: isset($filters['start']) ? (int) $filters['start'] : null,
                end: isset($filters['end']) ? (int) $filters['end'] : null,
                direction: (string) ($filters['direction'] ?? 'backward'),
            );

            return array_values(array_map(
                static fn (array $entry): array => LogEntry::fromArray($entry)->toArray(),
                $this->lokiClient->parseEntries($response)
            ));
        });
    }

    /**
     * @return array{
     *     chart_data: array<int, array<string, float|string>>,
     *     chart_series: array<int, array{field: string, label: string, line: string, indicator: string}>,
     *     range: array{start: int, end: int, step: int}
     * }
     */
    public function getMetricsChartData(string $query, string $range = '1h'): array
    {
        return Tracer::newSpan('otel.api.metrics.chart')->measure(function () use ($query, $range): array {
            ['start' => $start, 'end' => $end, 'step' => $step] = $this->resolveRange($range);

            $payload = $this->prometheusClient->queryRange(
                query: $query,
                start: $start,
                end: $end,
                step: $step,
            );

            return [
                'chart_data' => $this->toFluxChartData($payload),
                'chart_series' => $this->toChartSeries($payload),
                'range' => [
                    'start' => $start,
                    'end' => $end,
                    'step' => $step,
                ],
            ];
        });
    }

    /**
     * @return array{start: int, end: int, step: int}
     */
    protected function resolveRange(string $range): array
    {
        $end = now()->timestamp;
        $seconds = match ($range) {
            '15m' => 15 * 60,
            '30m' => 30 * 60,
            '6h' => 6 * 3600,
            '12h' => 12 * 3600,
            '24h', '1d' => 24 * 3600,
            '7d' => 7 * 24 * 3600,
            default => 3600,
        };

        $start = $end - $seconds;
        $step = (int) max(15, round($seconds / 100));

        return [
            'start' => $start,
            'end' => $end,
            'step' => $step,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, float|string>>
     */
    protected function toFluxChartData(array $payload): array
    {
        $rowsByDate = [];

        foreach ($this->normalizedSeries($payload) as $series) {
            $field = (string) $series['field'];
            $values = is_array($series['values'] ?? null) ? $series['values'] : [];

            foreach ($values as $point) {
                if (! is_array($point) || count($point) < 2) {
                    continue;
                }

                $date = gmdate('Y-m-d\TH:i:s', (int) $point[0]);
                if (! isset($rowsByDate[$date])) {
                    $rowsByDate[$date] = ['date' => $date];
                }

                $rowsByDate[$date][$field] = (float) $point[1];
            }
        }

        $rows = array_values($rowsByDate);
        usort($rows, static fn (array $left, array $right): int => strcmp((string) $left['date'], (string) $right['date']));

        foreach ($this->normalizedSeries($payload) as $series) {
            $field = (string) $series['field'];
            foreach ($rows as $index => $row) {
                if (! array_key_exists($field, $row)) {
                    $rows[$index][$field] = 0.0;
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{field: string, label: string, line: string, indicator: string}>
     */
    protected function toChartSeries(array $payload): array
    {
        $series = [];

        foreach ($this->normalizedSeries($payload) as $index => $item) {
            $series[] = [
                'field' => (string) $item['field'],
                'label' => (string) $item['label'],
                'line' => self::LINE_COLORS[$index % count(self::LINE_COLORS)],
                'indicator' => self::INDICATOR_COLORS[$index % count(self::INDICATOR_COLORS)],
            ];
        }

        return $series;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{field: string, label: string, values: array<int, array<int, int|string>>}>
     */
    protected function normalizedSeries(array $payload): array
    {
        $result = $payload['data']['result'] ?? [];

        if (! is_array($result)) {
            return [];
        }

        $normalized = [];
        $usedFields = [];

        foreach ($result as $series) {
            if (! is_array($series)) {
                continue;
            }

            $metric = is_array($series['metric'] ?? null) ? $series['metric'] : [];
            $label = (string) ($metric['service_name'] ?? $metric['service'] ?? $metric['job'] ?? $metric['__name__'] ?? 'series');
            $baseField = trim((string) preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($label)), '_');
            $field = $baseField !== '' ? $baseField : 'series';

            if (isset($usedFields[$field])) {
                $usedFields[$field]++;
                $field .= '_'.$usedFields[$field];
            } else {
                $usedFields[$field] = 1;
            }

            $values = [];

            if (is_array($series['values'] ?? null)) {
                $values = $series['values'];
            } elseif (is_array($series['value'] ?? null)) {
                $values = [$series['value']];
            }

            $normalized[] = [
                'field' => $field,
                'label' => $label,
                'values' => $values,
            ];
        }

        return $normalized;
    }
}
