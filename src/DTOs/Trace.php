<?php

namespace AgenticMorf\FluxUIOtel\DTOs;

class Trace
{
    public function __construct(
        public string $id,
        public string $service,
        public string $operation,
        public int $startTimeMicros,
        public float $durationMs,
        public int $spanCount
    ) {}

    /**
     * @param  array<string, mixed>  $trace
     */
    public static function fromArray(array $trace): self
    {
        $id = (string) ($trace['traceID'] ?? $trace['traceId'] ?? $trace['id'] ?? '');
        $spans = is_array($trace['spans'] ?? null) ? $trace['spans'] : [];
        $firstSpan = is_array($spans[0] ?? null) ? $spans[0] : [];

        $processes = is_array($trace['processes'] ?? null) ? $trace['processes'] : [];
        $processId = (string) ($firstSpan['processID'] ?? $firstSpan['processId'] ?? '');
        $process = is_array($processes[$processId] ?? null) ? $processes[$processId] : [];

        $service = (string) ($process['serviceName'] ?? $firstSpan['serviceName'] ?? 'unknown');
        $operation = (string) ($firstSpan['operationName'] ?? $firstSpan['name'] ?? 'unknown');
        $startTimeMicros = (int) ($firstSpan['startTime'] ?? $firstSpan['startTimeUnixNano'] ?? 0);
        $durationMicros = (int) ($firstSpan['duration'] ?? 0);

        return new self(
            id: $id,
            service: $service,
            operation: $operation,
            startTimeMicros: $startTimeMicros,
            durationMs: $durationMicros > 0 ? round($durationMicros / 1000, 2) : 0.0,
            spanCount: count($spans),
        );
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service' => $this->service,
            'operation' => $this->operation,
            'start_time_micros' => $this->startTimeMicros,
            'duration_ms' => $this->durationMs,
            'span_count' => $this->spanCount,
        ];
    }
}
