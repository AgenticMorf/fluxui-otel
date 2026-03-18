<?php

namespace AgenticMorf\FluxUIOtel\Livewire;

use AgenticMorf\FluxUIOtel\Services\OtelGateway;
use Illuminate\Contracts\View\View;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Livewire\Attributes\Title;
use Livewire\Component;
use OpenTelemetry\API\Trace\StatusCode;

#[Title('Metrics')]
class MetricsViewer extends Component
{
    public string $query = '';

    public string $timeRange = '1h';

    public array $services = [];

    public array $chartData = [];

    public array $chartSeries = [];

    public ?string $error = null;

    public function mount(): void
    {
        $span = Tracer::newSpan('livewire.metrics-viewer.mount')->start();

        try {
            $this->query = (string) config('fluxui-otel.default_metrics_query', 'up');
            $this->timeRange = (string) config('fluxui-otel.default_time_range', '1h');
            $this->services = $this->gateway()->getServices();
            $this->loadMetrics();
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
        } finally {
            $span->end();
        }
    }

    public function loadMetrics(): void
    {
        $span = Tracer::newSpan('livewire.metrics-viewer.loadMetrics')->start();

        try {
            $this->error = null;
            $metrics = $this->gateway()->getMetricsChartData($this->query, $this->timeRange);
            $this->chartData = $metrics['chart_data'] ?? [];
            $this->chartSeries = $metrics['chart_series'] ?? [];
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->chartData = [];
            $this->chartSeries = [];
        } finally {
            $span->end();
        }
    }

    public function changeRange(string $range): void
    {
        $span = Tracer::newSpan('livewire.metrics-viewer.changeRange')->start();

        try {
            $this->timeRange = $range;
            $this->loadMetrics();
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
        return view('fluxui-otel::livewire.metrics-viewer')
            ->layout(config('fluxui-otel.layout', 'components.layouts.app.sidebar'));
    }

    protected function gateway(): OtelGateway
    {
        return OtelGateway::fromConfig();
    }
}
