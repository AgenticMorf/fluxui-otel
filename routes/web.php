<?php

use AgenticMorf\FluxUIOtel\Livewire\MetricsViewer;
use AgenticMorf\FluxUIOtel\Livewire\OtelDashboard;
use AgenticMorf\FluxUIOtel\Livewire\ServicesViewer;
use AgenticMorf\FluxUIOtel\Livewire\TracesViewer;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::middleware(config('fluxui-otel.middleware', ['web', 'auth']))
    ->prefix(config('fluxui-otel.route_prefix', 'otel'))
    ->name(config('fluxui-otel.route_name_prefix', 'otel.'))
    ->group(function (): void {
        Route::get('/', OtelDashboard::class)->name('dashboard');
        Route::get('/traces', TracesViewer::class)->name('traces');
        Route::get('/metrics', MetricsViewer::class)->name('metrics');
        Route::get('/services', ServicesViewer::class)->name('services');
    });

Route::middleware('web')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->post('/api/otel/ingest', function (Request $request): Response {
        $collectorBaseUrl = rtrim((string) config('fluxui-otel.otel_collector_url', 'http://otel-collector:4318'), '/');

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => $request->header('Content-Type', 'application/json'),
            ])
            ->withBody($request->getContent(), $request->header('Content-Type', 'application/json'))
            ->send('POST', $collectorBaseUrl.'/v1/traces');

        return response(
            content: $response->body(),
            status: $response->status(),
            headers: ['Content-Type' => (string) $response->header('Content-Type', 'application/json')],
        );
    })
    ->name('otel.ingest');
