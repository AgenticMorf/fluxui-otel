<?php

namespace AgenticMorf\FluxUIOtel\DTOs;

class LogEntry
{
    /**
     * @param  array<string, string>  $labels
     */
    public function __construct(
        public int $timestamp,
        public array $labels,
        public string $line,
    ) {}

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function fromArray(array $entry): self
    {
        return new self(
            timestamp: (int) ($entry['timestamp'] ?? 0),
            labels: is_array($entry['labels'] ?? null) ? $entry['labels'] : [],
            line: (string) ($entry['line'] ?? ''),
        );
    }

    /**
     * @return array{timestamp: int, labels: array<string, string>, line: string}
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'labels' => $this->labels,
            'line' => $this->line,
        ];
    }
}
