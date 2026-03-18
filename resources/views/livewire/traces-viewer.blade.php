<section class="w-full">
    <x-otel.layout>
<div class="flex min-h-0 flex-col gap-6">
    <flux:card>
        <div class="flex flex-wrap items-end gap-4">
            <flux:select wire:model="selectedService" label="{{ __('Service') }}" class="min-w-[200px]">
                <option value="">{{ __('All services') }}</option>
                @foreach($services as $service)
                    <option value="{{ $service }}">{{ $service }}</option>
                @endforeach
            </flux:select>
            <flux:input type="number" wire:model="limit" label="{{ __('Limit') }}" min="1" max="100" class="w-28" />
            <flux:button wire:click="searchTraces" variant="primary">{{ __('Search traces') }}</flux:button>
        </div>
    </flux:card>

    @if($error)
        <flux:card class="border-red-500 dark:border-red-600">
            <flux:heading size="sm" class="text-red-600 dark:text-red-400">{{ __('Error') }}</flux:heading>
            <p class="mt-2 font-mono text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
        </flux:card>
    @endif

    <flux:card>
        <flux:heading size="lg">{{ __('Traces') }}</flux:heading>
        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>{{ __('Trace') }}</flux:table.column>
                <flux:table.column>{{ __('Service') }}</flux:table.column>
                <flux:table.column>{{ __('Operation') }}</flux:table.column>
                <flux:table.column>{{ __('Duration') }}</flux:table.column>
                <flux:table.column>{{ __('Spans') }}</flux:table.column>
                <flux:table.column>{{ __('') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($traces as $trace)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $trace['id'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['service'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['operation'] ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $trace['duration_ms'] ?? 0 }} ms</flux:table.cell>
                        <flux:table.cell>{{ $trace['span_count'] ?? 0 }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="loadTrace('{{ $trace['id'] ?? '' }}')" variant="ghost" size="sm">{{ __('View') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500 dark:text-zinc-400">{{ __('No traces found') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @if($selectedTraceId)
        <flux:card>
            <flux:heading size="lg">{{ __('Trace Detail') }}</flux:heading>
            <flux:subheading class="font-mono text-xs">{{ $selectedTraceId }}</flux:subheading>

            @php $waterfallSpans = $this->waterfallSpans; @endphp
            @if(count($waterfallSpans) > 0)
                <div class="mt-4">
                    <flux:subheading class="mb-2">{{ __('Timeline') }}</flux:subheading>
                    <div class="space-y-1.5">
                        @foreach($waterfallSpans as $ws)
                            <div class="flex items-center gap-3">
                                <div class="flex w-48 shrink-0 items-baseline gap-1 truncate" style="padding-left: {{ $ws['depth'] * 16 }}px" title="{{ $ws['operation'] }} ({{ $ws['service'] }})">
                                    <flux:text variant="strong" size="sm" inline>{{ $ws['operation'] }}</flux:text>
                                    <flux:text variant="subtle" size="sm" inline class="font-mono tabular-nums">{{ number_format($ws['duration_ms'], 2) }} ms</flux:text>
                                </div>
                                <div class="relative flex-1">
                                    <flux:progress value="0" class="h-6" />
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center px-0.5"
                                        style="left: {{ $ws['left'] }}%; width: max({{ $ws['width'] }}%, 4px);"
                                        title="{{ $ws['operation'] }} – {{ number_format($ws['duration_ms'], 2) }} ms"
                                    >
                                        <flux:progress value="100" class="h-4 w-full" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Span') }}</flux:table.column>
                    <flux:table.column>{{ __('Service') }}</flux:table.column>
                    <flux:table.column>{{ __('Operation') }}</flux:table.column>
                    <flux:table.column>{{ __('Duration') }}</flux:table.column>
                    <flux:table.column>{{ __('') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($selectedSpans as $span)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-xs">{{ $span['id'] ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $span['service'] ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $span['operation'] ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $span['duration_ms'] ?? 0 }} ms</flux:table.cell>
                            <flux:table.cell>
                                @php $attrCount = count($span['attributes'] ?? []); @endphp
                                <flux:button wire:click="toggleSpan('{{ $span['id'] ?? '' }}')" variant="subtle" size="sm">
                                    {{ __('Attributes') }}{{ $attrCount > 0 ? ' (' . $attrCount . ')' : '' }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                        @if(in_array($span['id'] ?? '', $expandedSpans, true))
                            <flux:table.row>
                                <flux:table.cell colspan="5">
                                    <pre class="overflow-x-auto rounded bg-zinc-100 p-3 text-xs dark:bg-zinc-900">{{ json_encode($span['attributes'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </flux:table.cell>
                            </flux:table.row>
                        @endif
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
    </x-otel.layout>
</section>
