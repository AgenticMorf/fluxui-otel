<section class="w-full">
    <x-otel.layout>
<div class="flex min-h-0 flex-col gap-6">
    @if(count($chartData) > 0 && count($chartSeries) > 0)
        <div @if($polling) wire:poll.5s="refreshData" @endif>
        <flux:card>
            <flux:heading size="lg">{{ __('Metrics') }}</flux:heading>
            <flux:chart :value="$chartData" class="mt-4">
                <flux:chart.viewport class="aspect-[12/1]">
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
        </flux:card>
        </div>
    @endif

    <flux:card>
        <flux:heading>{{ __('Prometheus') }}</flux:heading>
        <flux:subheading>{{ __('Query Prometheus metrics from Sail services') }}</flux:subheading>
        <div class="mt-4 flex flex-wrap items-end gap-4">
            <flux:input wire:model="metricsQuery" label="{{ __('Prometheus query') }}" class="min-w-[280px] flex-1" />
            <flux:select wire:model="timeRange" label="{{ __('Range') }}" class="w-36">
                <option value="15m">15m</option>
                <option value="30m">30m</option>
                <option value="1h">1h</option>
                <option value="6h">6h</option>
                <option value="24h">24h</option>
                <option value="7d">7d</option>
            </flux:select>
            <flux:button
                wire:click="togglePolling"
                variant="{{ $polling ? 'primary' : 'ghost' }}"
                icon="refresh-cw"
                square
                class="shrink-0 self-end"
                aria-label="{{ $polling ? __('Pause auto-refresh') : __('Auto-refresh') }}"
            />
            <flux:button wire:click="refreshData" variant="primary" class="shrink-0 self-end">{{ __('Refresh') }}</flux:button>
        </div>
    </flux:card>

    @if($error)
        <flux:card class="border-red-500 dark:border-red-600">
            <flux:heading size="sm" class="text-red-600 dark:text-red-400">{{ __('Error') }}</flux:heading>
            <p class="mt-2 font-mono text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
        </flux:card>
    @endif

    @if(count($chartData) === 0 || count($chartSeries) === 0)
        <flux:card>
            <flux:subheading>{{ __('No data for this query. Try') }} <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">up</code> {{ __('to see scrape targets.') }}</flux:subheading>
        </flux:card>
    @endif

    <flux:card>
        <flux:heading size="lg">{{ __('Recent Traces') }}</flux:heading>
        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>{{ __('Trace ID') }}</flux:table.column>
                <flux:table.column>{{ __('Service') }}</flux:table.column>
                <flux:table.column>{{ __('Operation') }}</flux:table.column>
                <flux:table.column>{{ __('Duration (ms)') }}</flux:table.column>
                <flux:table.column>{{ __('') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($recentTraces as $trace)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $trace['id'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['service'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['operation'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['duration_ms'] ?? 0 }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link href="{{ route('otel.traces', ['trace' => $trace['id'] ?? '']) }}" wire:navigate variant="ghost" size="sm">{{ __('View') }}</flux:link>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500 dark:text-zinc-400">{{ __('No traces found') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
    </x-otel.layout>
</section>
