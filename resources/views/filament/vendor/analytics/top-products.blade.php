<x-filament-widgets::widget>
    <x-filament::section heading="Популярные блюда" description="По количеству в выполненных заказах">
        {{-- Inline styles: the panel's compiled CSS has no classes for custom layouts --}}
        @if (empty($products))
            <p style="font-size: 0.875rem; color: var(--gray-500);">За этот период выполненных заказов нет.</p>
        @else
            @php($max = max(array_column($products, 'quantity')))
            <ol style="display: flex; flex-direction: column; gap: 0.75rem; margin: 0; padding: 0; list-style: none;">
                @foreach ($products as $i => $product)
                    <li>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; font-size: 0.875rem;">
                            <span style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $i + 1 }}. {{ $product['name'] }}</span>
                            <span style="flex-shrink: 0; color: var(--gray-500); font-variant-numeric: tabular-nums;">
                                {{ $product['quantity'] }} шт · {{ number_format($product['revenue'], 0, ',', ' ') }} TMT
                            </span>
                        </div>
                        <div style="margin-top: 0.25rem; height: 0.375rem; border-radius: 9999px; background: color-mix(in oklab, var(--gray-500) 15%, transparent);">
                            <div style="height: 100%; border-radius: 9999px; background: var(--primary-500); width: {{ max(4, round($product['quantity'] / $max * 100)) }}%;"></div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
