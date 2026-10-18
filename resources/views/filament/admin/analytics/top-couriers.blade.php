<x-filament-widgets::widget>
    <x-filament::section heading="Курьеры" description="По числу доставок за период">
        @if (empty($couriers))
            <p style="font-size: 0.875rem; color: var(--gray-500);">За этот период доставок не было.</p>
        @else
            <ol style="display: flex; flex-direction: column; gap: 0.5rem; margin: 0; padding: 0; list-style: none; font-size: 0.875rem;">
                @foreach ($couriers as $i => $courier)
                    <li style="display: flex; justify-content: space-between; gap: 0.75rem;">
                        <span style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $i + 1 }}. {{ $courier['name'] }}</span>
                        <span style="flex-shrink: 0; color: var(--gray-500); font-variant-numeric: tabular-nums;">
                            {{ $courier['deliveries'] }} доставок · {{ number_format($courier['earned'], 0, ',', ' ') }} TMT
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
