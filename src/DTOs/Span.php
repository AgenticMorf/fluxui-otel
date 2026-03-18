<?php

namespace AgenticMorf\FluxUIOtel\DTOs;

class Span
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $id,
        public string $parentId,
        public string $service,
        public string $operation,
        public int $startTimeMicros,
        public float $durationMs,
        public array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $span
     * @param  array<string, mixed>  $processes
     */
    public static function fromArray(array $span, array $processes = []): self
    {
        $processId = (string) ($span['processID'] ?? $span['processId'] ?? '');
        $process = is_array($processes[$processId] ?? null) ? $processes[$processId] : [];

        $parentId = (string) ($span['parentSpanId'] ?? '');
        if (isset($span['references']) && is_array($span['references']) && isset($span['references'][0])) {
            $ref = $span['references'][0];
            $parentId = (string) ($ref['spanID'] ?? $ref['spanId'] ?? $parentId);
        }

        return new self(
            id: (string) ($span['spanID'] ?? $span['spanId'] ?? $span['id'] ?? ''),
            parentId: $parentId,
            service: (string) ($process['serviceName'] ?? $span['serviceName'] ?? 'unknown'),
            operation: (string) ($span['operationName'] ?? $span['name'] ?? 'unknown'),
            startTimeMicros: (int) ($span['startTime'] ?? $span['startTimeUnixNano'] ?? 0),
            durationMs: round(((int) ($span['duration'] ?? 0)) / 1000, 2),
            attributes: self::normalizeAttributes($span),
        );
    }

    /**
     * @param  array<string, mixed>  $span
     * @return array<string, mixed>
     */
    protected static function normalizeAttributes(array $span): array
    {
        $attributes = [];
        $sources = [
            $span['tags'] ?? [],
            $span['attributes'] ?? [],
        ];

        foreach ($sources as $items) {
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $key = $item['key'] ?? null;
                if (! is_string($key) || $key === '') {
                    continue;
                }

                $value = $item['value'] ?? $item['stringValue'] ?? $item['intValue'] ?? $item['doubleValue'] ?? $item['boolValue'] ?? null;
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, int|float|string|array<string, mixed>>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'service' => $this->service,
            'operation' => $this->operation,
            'start_time_micros' => $this->startTimeMicros,
            'duration_ms' => $this->durationMs,
            'attributes' => $this->attributes,
        ];
    }
}
