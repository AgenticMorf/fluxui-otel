<?php

namespace AgenticMorf\FluxUIOtel\Livewire;

use AgenticMorf\FluxUIOtel\Services\OtelGateway;
use Illuminate\Contracts\View\View;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use Livewire\Attributes\Title;
use Livewire\Component;
use OpenTelemetry\API\Trace\StatusCode;

#[Title('Services')]
class ServicesViewer extends Component
{
    public array $services = [];

    public string $selectedService = '';

    public array $serviceSpans = [];

    public ?string $error = null;

    public function mount(): void
    {
        $this->loadServices();
    }

    public function loadServices(): void
    {
        $span = Tracer::newSpan('livewire.services-viewer.loadServices')->start();

        try {
            $this->error = null;
            $this->services = $this->gateway()->getServices();
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->services = [];
        } finally {
            $span->end();
        }
    }

    public function viewServiceSpans(string $service): void
    {
        $span = Tracer::newSpan('livewire.services-viewer.viewServiceSpans')->start();

        try {
            $this->error = null;
            $this->selectedService = $service;
            $this->serviceSpans = $this->gateway()->getTraces([
                'service' => $service,
                'limit' => 20,
                'lookback' => '1h',
            ]);
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);
            $this->error = $exception->getMessage();
            $this->serviceSpans = [];
        } finally {
            $span->end();
        }
    }

    public function render(): View
    {
        return view('fluxui-otel::livewire.services-viewer')
            ->layout(config('fluxui-otel.layout', 'components.layouts.app.sidebar'));
    }

    protected function gateway(): OtelGateway
    {
        return OtelGateway::fromConfig();
    }
}
