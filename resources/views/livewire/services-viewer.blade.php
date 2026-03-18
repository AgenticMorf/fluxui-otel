<section class="w-full">
    <x-otel.layout>
<div class="flex min-h-0 flex-col gap-6">
    <flux:card>
        <div class="flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ __('Services') }}</flux:heading>
                <flux:subheading>{{ __('Services discovered from Jaeger traces') }}</flux:subheading>
            </div>
            <flux:button wire:click="loadServices" variant="primary">{{ __('Refresh services') }}</flux:button>
        </div>

        @if($error)
            <p class="mt-4 font-mono text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
        @endif

        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>{{ __('Service') }}</flux:table.column>
                <flux:table.column>{{ __('') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($services as $service)
                    <flux:table.row>
                        <flux:table.cell>{{ $service }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="viewServiceSpans('{{ $service }}')" variant="ghost" size="sm">{{ __('View traces') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2" class="text-center text-zinc-500 dark:text-zinc-400">{{ __('No services found') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    @if($selectedService !== '')
        <flux:card>
            <flux:heading size="lg">{{ __('Recent traces for') }} {{ $selectedService }}</flux:heading>
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Trace') }}</flux:table.column>
                    <flux:table.column>{{ __('Operation') }}</flux:table.column>
                    <flux:table.column>{{ __('Duration') }}</flux:table.column>
                    <flux:table.column>{{ __('Spans') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($serviceSpans as $trace)
                        <flux:table.row>
                            <flux:table.cell class="font-mono text-xs">{{ $trace['id'] ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $trace['operation'] ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $trace['duration_ms'] ?? 0 }} ms</flux:table.cell>
                            <flux:table.cell>{{ $trace['span_count'] ?? 0 }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 dark:text-zinc-400">{{ __('No traces found for this service') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
    </x-otel.layout>
</section>
