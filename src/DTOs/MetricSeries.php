<?php

namespace AgenticMorf\FluxUIOtel\DTOs;

class MetricSeries
{
    /**
     * @param  array<int, array{timestamp: int, value: float}>  $points
     */
    public function __construct(
        public string $name,
        public array $points,
    ) {}

    /**
     * @param  array<string, mixed>  $series
     */
    public static function fromPrometheus(array $series): self
    {
        $metric = is_array($series['metric'] ?? null) ? $series['metric'] : [];
        $name = (string) ($metric['service_name'] ?? $metric['service'] ?? $metric['job'] ?? $metric['__name__'] ?? 'series');
        $values = is_array($series['values'] ?? null) ? $series['values'] : [];

        $points = [];

        foreach ($values as $value) {
            if (! is_array($value) || count($value) < 2) {
                continue;
            }

            $points[] = [
                'timestamp' => (int) $value[0],
                'value' => (float) $value[1],
            ];
        }

        return new self($name, $points);
    }

    /**
     * @return array{name: string, points: array<int, array{timestamp: int, value: float}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'points' => $this->points,
        ];
    }
}
