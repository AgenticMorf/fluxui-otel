<?php

namespace AgenticMorf\FluxUIOtel\Livewire;

use AgenticMorf\FluxUIOtel\Services\OtelGateway;
use Illuminate\Contracts\View\View;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use OpenTelemetry\API\Trace\StatusCode;

#[Title('Observability')]
class OtelDashboard extends Component
{
    public array $chartData = [];

    public array $chartSeries = [];

    public array $recentTraces = [];

    public string $metricsQuery = '';

    public string $timeRange = '1h';

    #[Session]
    public bool $polling = false;

    public int $pollInterval = 5;

    public ?string $error = null;

    public function togglePolling(): void
    {
        $this->polling = ! $this->polling;
    }

    public function mount(): void
    {
        $span = Tracer::newSpan('livewire.otel-dashboard.mount')->start();

        try {
            $this->metricsQuery = (string) config('fluxui-otel.default_metrics_query', 'up');
            $this->timeRange = (string) config('fluxui-otel.default_time_range', '1h');
            $this->pollInterval = (int) config('fluxui-otel.poll_interval', 5);
            $this->refreshData();
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
        $span = Tracer::newSpan('livewire.otel-dashboard.loadMetrics')->start();

        try {
            $metrics = $this->gateway()->getMetricsChartData($this->metricsQuery, $this->timeRange);
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

    public function loadRecentTraces(): void
    {
        $span = Tracer::newSpan('livewire.otel-dashboard.loadRecentTraces')->start();

        try {
            $this->recentTraces = $this->gateway()->getTraces([
                'service' => (string) config('opentelemetry.service_name', config('app.name', 'app')),
                'limit' => 10,
                'lookback' => '1h',
            ]);
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->recentTraces = [];
        } finally {
            $span->end();
        }
    }

    public function refreshData(): void
    {
        $span = Tracer::newSpan('livewire.otel-dashboard.refresh')->start();

        try {
            $this->error = null;
            $this->loadMetrics();
            $this->loadRecentTraces();
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
        return view('fluxui-otel::livewire.otel-dashboard')
            ->layout(config('fluxui-otel.layout', 'components.layouts.app.sidebar'));
    }

    protected function gateway(): OtelGateway
    {
        return OtelGateway::fromConfig();
    }
}
