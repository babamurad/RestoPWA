<x-filament-widgets::widget>
    <x-filament::section heading="Рестораны" description="С заказами за период, по обороту">
        {{-- Inline styles: the panel's compiled CSS has no classes for custom layouts --}}
        @if (empty($restaurants))
            <p style="font-size: 0.875rem; color: var(--gray-500);">За этот период заказов нет.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; color: var(--gray-500); border-bottom: 1px solid color-mix(in oklab, var(--gray-500) 25%, transparent);">
                            <th style="padding: 0.5rem 0.75rem 0.5rem 0; font-weight: 500;">Ресторан</th>
                            <th style="padding: 0.5rem 0.75rem; font-weight: 500; text-align: right;">Заказов</th>
                            <th style="padding: 0.5rem 0.75rem; font-weight: 500; text-align: right;">Выполнено</th>
                            <th style="padding: 0.5rem 0.75rem; font-weight: 500; text-align: right;">Отмены</th>
                            <th style="padding: 0.5rem 0.75rem; font-weight: 500; text-align: right;">Оборот</th>
                            <th style="padding: 0.5rem 0 0.5rem 0.75rem; font-weight: 500; text-align: right;">Комиссия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($restaurants as $row)
                            <tr style="border-bottom: 1px solid color-mix(in oklab, var(--gray-500) 12%, transparent); font-variant-numeric: tabular-nums;">
                                <td style="padding: 0.5rem 0.75rem 0.5rem 0; font-weight: 500;">{{ $row['name'] }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $row['orders'] }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $row['finished'] }}</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right; {{ $row['cancel_rate'] >= 10 ? 'color: var(--danger-600);' : '' }}">{{ $row['cancel_rate'] }}%</td>
                                <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ number_format($row['gmv'], 0, ',', ' ') }} TMT</td>
                                <td style="padding: 0.5rem 0 0.5rem 0.75rem; text-align: right;">{{ number_format($row['commission'], 2, ',', ' ') }} TMT</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
