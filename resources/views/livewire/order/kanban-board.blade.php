<div class="fi-section-content-ctn">
    <div class="flex gap-6 overflow-x-auto pb-6 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" id="kanban-board" style="min-height: calc(100vh - 250px);">
        @foreach($columns as $columnId => $column)
            <div 
                class="flex-shrink-0 w-80 flex flex-col gap-4 rounded-xl bg-gray-50/50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
                data-column="{{ $columnId }}"
            >
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ $column['title'] }}
                    </h3>
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        {{ count($column['orders']) }}
                    </span>
                </div>

                <div class="flex-1 space-y-4 min-h-[300px]" data-column-orders="{{ $columnId }}">
                    @foreach($column['orders'] as $order)
                        <div 
                            class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 cursor-move hover:shadow-md transition-shadow kanban-card"
                            draggable="true"
                            data-order-id="{{ $order['id'] }}"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    #{{ substr($order['id'], -8) }}
                                </span>
                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}
                                </span>
                            </div>
                            
                            <div class="text-lg font-bold text-gray-950 dark:text-white mb-2">
                                {{ number_format($order['total'], 0, ',', ' ') }} ₽
                            </div>

                            @if(!empty($order['address']))
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                    {{ $order['address']['address'] ?? $order['address']['street'] ?? 'Адрес не указан' }}
                                </div>
                            @endif

                            <div class="flex items-center gap-2 mt-auto">
                                <a 
                                    href="{{ route('filament.vendor.resources.orders.view', ['record' => $order['id'], 'tenant' => $vendorId]) }}"
                                    class="fi-btn fi-btn-size-sm fi-color-gray bg-white px-3 py-1.5 text-xs font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10 rounded-lg flex-1 text-center"
                                >
                                    Детали
                                </a>
                            </div>
                        </div>
                    @endforeach

                    @if(empty($column['orders']))
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg class="w-8 h-8 opacity-20 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <span class="text-xs uppercase tracking-widest font-bold opacity-30">Пусто</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let draggedOrder = null;
            const cards = document.querySelectorAll('.kanban-card');
            const columns = document.querySelectorAll('.kanban-column');

            cards.forEach(card => {
                card.addEventListener('dragstart', function(e) {
                    draggedOrder = {
                        id: this.dataset.orderId
                    };
                    this.classList.add('opacity-50');
                    e.dataTransfer.effectAllowed = 'move';
                });

                card.addEventListener('dragend', function() {
                    this.classList.remove('opacity-50');
                    draggedOrder = null;
                    columns.forEach(col => col.classList.remove('ring-2', 'ring-blue-500'));
                });
            });

            columns.forEach(column => {
                column.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('ring-2', 'ring-blue-500');
                });

                column.addEventListener('dragleave', function(e) {
                    this.classList.remove('ring-2', 'ring-blue-500');
                });

                column.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('ring-2', 'ring-blue-500');
                    
                    if (draggedOrder) {
                        const columnId = this.dataset.column;
                        @this.moveOrder(draggedOrder.id, columnId);
                    }
                });
            });

            if (typeof window.Echo !== 'undefined') {
                const vendorId = '{{ $vendorId }}';
                
                window.Echo.private('restaurant.' + vendorId)
                    .listen('OrderStatusUpdated', function(data) {
                        console.log('Order status updated, refreshing...');
                        @this.loadOrders();
                    });
            }
        });
    </script>
</div>
