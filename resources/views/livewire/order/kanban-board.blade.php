<div>
    <div class="flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-200px)]" id="kanban-board">
        @foreach($columns as $columnId => $column)
            <div 
                class="flex-shrink-0 w-72 bg-gray-100 rounded-lg p-3 kanban-column"
                data-column="{{ $columnId }}"
            >
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-700">{{ $column['title'] }}</h3>
                    <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full order-count" data-column="{{ $columnId }}">
                        {{ count($column['orders']) }}
                    </span>
                </div>

                <div class="space-y-3 min-h-[200px]" data-column-orders="{{ $columnId }}">
                    @foreach($column['orders'] as $order)
                        <div 
                            class="bg-white rounded-lg shadow p-3 cursor-move hover:shadow-md transition-shadow kanban-card"
                            draggable="true"
                            data-order-id="{{ $order['id'] }}"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-gray-900">#{{ substr($order['id'], -8) }}</span>
                                <span class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}
                                </span>
                            </div>
                            
                            <div class="text-lg font-bold text-green-600 mb-2">
                                {{ number_format($order['total'], 0, ',', ' ') }} ₽
                            </div>

                            @if(!empty($order['address']))
                                <div class="text-sm text-gray-600 mb-2 truncate">
                                    {{ $order['address']['address'] ?? $order['address']['street'] ?? 'Адрес не указан' }}
                                </div>
                            @endif

                            @if(!empty($order['payment_status']))
                                <div class="text-xs mb-2">
                                    @if($order['payment_status'] === 'paid')
                                        <span class="text-green-600">Оплачен</span>
                                    @elseif($order['payment_status'] === 'pending')
                                        <span class="text-yellow-600">Ожидает оплаты</span>
                                    @else
                                        <span class="text-red-600">{{ $order['payment_status'] }}</span>
                                    @endif
                                </div>
                            @endif

                            <a 
                                href="{{ route('vendor.orders.show', $order['id']) }}"
                                class="block text-center text-sm bg-blue-500 text-white py-1.5 rounded hover:bg-blue-600 transition-colors"
                            >
                                Детали
                            </a>
                        </div>
                    @endforeach

                    @if(empty($column['orders']))
                        <div class="text-center text-gray-400 py-8 text-sm empty-message">
                            Нет заказов
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
