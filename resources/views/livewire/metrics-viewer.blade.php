<section class="w-full">
    <x-otel.layout>
<div class="flex min-h-0 flex-col gap-6">
    <flux:card>
        <div class="flex flex-wrap items-end gap-4">
            <flux:input wire:model="query" label="{{ __('Prometheus query') }}" class="min-w-[300px] flex-1" />
            <flux:select wire:model="timeRange" label="{{ __('Range') }}" class="w-36">
                <option value="15m">15m</option>
                <option value="30m">30m</option>
                <option value="1h">1h</option>
                <option value="6h">6h</option>
                <option value="12h">12h</option>
                <option value="24h">24h</option>
                <option value="7d">7d</option>
            </flux:select>
            <flux:button wire:click="loadMetrics" variant="primary">{{ __('Load') }}</flux:button>
        </div>
    </flux:card>

    @if($error)
        <flux:card class="border-red-500 dark:border-red-600">
            <flux:heading size="sm" class="text-red-600 dark:text-red-400">{{ __('Error') }}</flux:heading>
            <p class="mt-2 font-mono text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
        </flux:card>
    @endif

    <flux:card>
        <flux:heading size="lg">{{ __('Metrics Chart') }}</flux:heading>

        @if(count($chartData) === 0 || count($chartSeries) === 0)
            <flux:skeleton animate="shimmer" class="mt-4 h-64 w-full rounded-lg" />
        @else
            <flux:chart :value="$chartData" class="mt-4">
                <flux:chart.viewport class="aspect-[12/3]">
                    <flux:chart.svg>
                        @foreach($chartSeries as $series)
                            <flux:chart.line field="{{ $series['field'] }}" class="{{ $series['line'] }}" curve="none" />
                        @endforeach
                        <flux:chart.axis axis="x" field="date" tick-count="8">
                            <flux:chart.axis.line />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>
                </flux:chart.viewport>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" />
                    @foreach($chartSeries as $series)
                        <flux:chart.tooltip.value field="{{ $series['field'] }}" label="{{ $series['label'] }}" />
                    @endforeach
                </flux:chart.tooltip>
                <div class="flex flex-wrap justify-center gap-4 pt-4">
                    @foreach($chartSeries as $series)
                        <flux:chart.legend label="{{ $series['label'] }}">
                            <flux:chart.legend.indicator class="{{ $series['indicator'] }}" />
                        </flux:chart.legend>
                    @endforeach
                </div>
            </flux:chart>
        @endif
    </flux:card>
</div>
    </x-otel.layout>
</section>
