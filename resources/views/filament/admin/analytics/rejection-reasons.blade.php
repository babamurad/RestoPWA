<x-filament-widgets::widget>
    <x-filament::section heading="Причины отказов" description="Почему сервер не принял заказ">
        @if (empty($reasons))
            <p style="font-size: 0.875rem; color: var(--gray-500);">За этот период отказов не было.</p>
        @else
            <ol style="display: flex; flex-direction: column; gap: 0.75rem; margin: 0; padding: 0; list-style: none;">
                @foreach ($reasons as $reason)
                    <li>
                        <div style="display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem;">
                            <span style="font-weight: 500;">{{ $reason['label'] }}</span>
                            <span style="flex-shrink: 0; color: var(--gray-500); font-variant-numeric: tabular-nums;">{{ $reason['count'] }} · {{ $reason['share'] }}%</span>
                        </div>
                        <div style="margin-top: 0.25rem; height: 0.375rem; border-radius: 9999px; background: color-mix(in oklab, var(--gray-500) 15%, transparent);">
                            <div style="height: 100%; border-radius: 9999px; background: var(--danger-500); width: {{ max(4, $reason['share']) }}%;"></div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
