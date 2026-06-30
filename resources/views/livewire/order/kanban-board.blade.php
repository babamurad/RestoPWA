<div class="flex gap-4 overflow-x-auto pb-4" id="kanban-board" style="min-height: calc(100vh - 300px);">
    @foreach($columns as $columnId => $column)
        <div class="flex-shrink-0 w-72 flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" data-column="{{ $columnId }}">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-white">
                    {{ $column['title'] }}
                </h3>
                <span class="inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 min-w-[22px]">
                    {{ count($column['orders']) }}
                </span>
            </div>

            <div class="flex-1 space-y-2 min-h-[200px]">
                @foreach($column['orders'] as $order)
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700 cursor-move hover:shadow-md transition-shadow" draggable="true" data-order-id="{{ $order['id'] }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                #{{ substr($order['id'], -6) }}
                            </span>
                            <span class="text-[11px] text-gray-400">
                                {{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}
                            </span>
                        </div>
                        <div class="text-base font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($order['total'], 0, ',', ' ') }} ₽
                        </div>
                        @if(!empty($order['address']))
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 line-clamp-1">
                                {{ $order['address']['address'] ?? $order['address']['manual_address'] ?? $order['address']['street'] ?? '' }}
                            </div>
                        @endif
                        <a href="{{ route('filament.vendor.resources.orders.view', ['record' => $order['id'], 'tenant' => $vendorId]) }}" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Подробнее &rarr;
                        </a>
                    </div>
                @endforeach

                @if(empty($column['orders']))
                    <div class="flex items-center justify-center py-8 text-gray-300 dark:text-gray-600">
                        <span class="text-xs">Нет заказов</span>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
<script>
    document.addEventListener('livewire:initialized', function() {
        let draggedOrder = null;
        let container = document.querySelector('#kanban-board');
        if (!container) container = document.querySelector('[wire\\:id] [style]');

        document.addEventListener('dragstart', function(e) {
            let card = e.target.closest('[draggable]');
            if (!card) return;
            draggedOrder = { id: card.dataset.orderId };
            card.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        document.addEventListener('dragend', function(e) {
            let card = e.target.closest('[draggable]');
            if (!card) return;
            card.style.opacity = '';
            draggedOrder = null;
            document.querySelectorAll('[data-column]').forEach(col => col.classList.remove('ring-2', 'ring-primary-500'));
        });

        document.addEventListener('dragover', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column) return;
            e.preventDefault();
            column.classList.add('ring-2', 'ring-primary-500');
        });

        document.addEventListener('dragleave', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column) return;
            column.classList.remove('ring-2', 'ring-primary-500');
        });

        document.addEventListener('drop', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column || !draggedOrder) return;
            e.preventDefault();
            column.classList.remove('ring-2', 'ring-primary-500');
            $wire.moveOrder(draggedOrder.id, column.dataset.column);
        });
    });
</script>
