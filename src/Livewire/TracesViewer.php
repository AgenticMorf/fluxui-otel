<?php

namespace AgenticMorf\FluxUIOtel\Livewire;

use AgenticMorf\FluxUIOtel\Services\OtelGateway;
use Illuminate\Contracts\View\View;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Livewire\Attributes\Title;
use Livewire\Component;
use OpenTelemetry\API\Trace\StatusCode;

#[Title('Traces')]
class TracesViewer extends Component
{
    public array $traces = [];

    public array $services = [];

    public string $selectedService = '';

    public int $limit = 20;

    public ?string $selectedTraceId = null;

    public array $selectedTrace = [];

    public array $selectedSpans = [];

    public array $expandedSpans = [];

    public ?string $error = null;

    public function mount(): void
    {
        $span = Tracer::newSpan('livewire.traces-viewer.mount')->start();

        try {
            $this->services = $this->gateway()->getServices();
            $this->searchTraces();

            $traceId = request()->query('trace');
            if (is_string($traceId) && $traceId !== '') {
                $this->loadTrace($traceId);
            }
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
        } finally {
            $span->end();
        }
    }

    public function searchTraces(): void
    {
        $span = Tracer::newSpan('livewire.traces-viewer.searchTraces')->start();

        try {
            $this->error = null;
            $this->traces = $this->gateway()->getTraces([
                'service' => $this->selectedService,
                'limit' => $this->limit,
                'lookback' => '1h',
            ]);
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->traces = [];
        } finally {
            $span->end();
        }
    }

    public function loadTrace(string $traceId): void
    {
        $span = Tracer::newSpan('livewire.traces-viewer.loadTrace')->start();

        try {
            $this->error = null;
            $traceData = $this->gateway()->getTrace($traceId);
            $this->selectedTraceId = $traceId;
            $this->selectedTrace = $traceData['trace'] ?? [];
            $this->selectedSpans = $traceData['spans'] ?? [];
            $this->expandedSpans = [];

            $traceRow = $traceData['trace'] ?? [];
            if (! empty($traceRow) && ! $this->traceInList($traceId)) {
                $this->traces = array_merge([$traceRow], $this->traces);
            }
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->selectedTrace = [];
            $this->selectedSpans = [];
            $this->selectedTraceId = null;
        } finally {
            $span->end();
        }
    }

    /**
     * Build waterfall data for selected spans: left %, width %, depth for nesting.
     *
     * @return array<int, array{id: string, operation: string, service: string, duration_ms: float, left: float, width: float, depth: int}>
     */
    public function getWaterfallSpansProperty(): array
    {
        $spans = $this->selectedSpans;
        if (empty($spans)) {
            return [];
        }

        $startMin = min(array_column($spans, 'start_time_micros')) ?: 0;
        $endMax = 0;
        foreach ($spans as $s) {
            $end = $s['start_time_micros'] + (int) round(($s['duration_ms'] ?? 0) * 1000);
            $endMax = max($endMax, $end);
        }
        $totalMicros = max(1, $endMax - $startMin);

        $byId = [];
        foreach ($spans as $s) {
            $byId[$s['id'] ?? ''] = $s;
        }

        $depth = [];
        foreach ($spans as $s) {
            $pid = $s['parent_id'] ?? '';
            $depth[$s['id'] ?? ''] = isset($byId[$pid]) ? (($depth[$pid] ?? 0) + 1) : 0;
        }

        $result = [];
        foreach ($spans as $s) {
            $start = $s['start_time_micros'] ?? 0;
            $durMicros = (int) round(($s['duration_ms'] ?? 0) * 1000);
            $result[] = [
                'id' => $s['id'] ?? '',
                'operation' => $s['operation'] ?? '-',
                'service' => $s['service'] ?? '-',
                'duration_ms' => (float) ($s['duration_ms'] ?? 0),
                'left' => 100 * ($start - $startMin) / $totalMicros,
                'width' => 100 * $durMicros / $totalMicros,
                'depth' => $depth[$s['id'] ?? ''] ?? 0,
            ];
        }

        usort($result, static fn (array $a, array $b): int => ($a['depth'] <=> $b['depth']) ?: (int) (($a['left'] ?? 0) <=> ($b['left'] ?? 0)));

        return $result;
    }

    public function toggleSpan(string $spanId): void
    {
        $span = Tracer::newSpan('livewire.traces-viewer.toggleSpan')->start();

        try {
            if (in_array($spanId, $this->expandedSpans, true)) {
                $this->expandedSpans = array_values(array_filter(
                    $this->expandedSpans,
                    static fn (string $id): bool => $id !== $spanId
                ));
            } else {
                $this->expandedSpans[] = $spanId;
            }
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
        } finally {
            $span->end();
        }
    }

    public function render(): View
    {
        return view('fluxui-otel::livewire.traces-viewer')
            ->layout(config('fluxui-otel.layout', 'components.layouts.app.sidebar'));
    }

    protected function gateway(): OtelGateway
    {
        return OtelGateway::fromConfig();
    }

    protected function traceInList(string $traceId): bool
    {
        foreach ($this->traces as $t) {
            if (($t['id'] ?? '') === $traceId) {
                return true;
            }
        }

        return false;
    }
}
