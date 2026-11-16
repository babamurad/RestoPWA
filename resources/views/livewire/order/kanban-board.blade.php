{{-- Own CSS below: the panel's compiled stylesheet has no utility classes for custom layouts --}}
{{-- wire:poll: the board refreshes by itself even when websockets (Reverb) are not available --}}
<div class="kb-board" id="kanban-board" wire:poll.25s="loadOrders">
    @foreach($columns as $columnId => $column)
        <div class="kb-column" data-column="{{ $columnId }}">
            <div class="kb-column-head">
                <h3 class="kb-column-title">{{ $column['title'] }}</h3>
                <span class="kb-count">{{ count($column['orders']) }}</span>
            </div>

            <div class="kb-cards">
                @foreach($column['orders'] as $order)
                    <div class="kb-card {{ $order['status'] === 'pending' ? 'kb-card-new' : '' }}" draggable="true" data-order-id="{{ $order['id'] }}">
                        <div class="kb-card-head">
                            <span class="kb-number">#{{ $order['number'] ?? strtoupper(substr($order['id'], -6)) }}</span>
                            <span class="kb-time">{{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}</span>
                        </div>
                        <div class="kb-total">{{ number_format($order['total'], 0, ',', ' ') }} TMT</div>
                        @if(($order['delivery_type'] ?? 'delivery') === 'pickup')
                            <div class="kb-line"><span class="kb-badge">Самовывоз</span></div>
                        @elseif(!empty($order['address']))
                            <div class="kb-line kb-address">
                                {{ $order['address']['address'] ?? $order['address']['manual_address'] ?? $order['address']['street'] ?? '' }}
                            </div>
                        @endif
                        <a href="{{ route('filament.vendor.resources.orders.view', ['record' => $order['id'], 'tenant' => $vendorId]) }}" class="kb-link">
                            Подробнее &rarr;
                        </a>
                        @if(in_array($order['status'], ['pending', 'confirmed', 'preparing', 'ready_for_pickup'], true))
                            {{-- Decline with a reason: the customer is told why the order will not come --}}
                            <details class="kb-decline" draggable="false">
                                <summary>Отклонить</summary>
                                <div class="kb-decline-form" x-data="{ reason: '' }">
                                    <select x-model="reason" aria-label="Причина отказа">
                                        <option value="">Причина…</option>
                                        @foreach(\App\Enums\OrderCancelReason::restaurantOptions() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" :disabled="!reason" @click="$wire.declineOrder('{{ $order['id'] }}', reason)">
                                        Отклонить заказ
                                    </button>
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach

                @if(empty($column['orders']))
                    <div class="kb-empty">Нет заказов</div>
                @endif
            </div>
        </div>
    @endforeach

    <style>
        .kb-board { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; min-height: calc(100vh - 300px); align-items: flex-start; }
        .kb-column { flex: 0 0 18rem; width: 18rem; box-sizing: border-box; display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; border-radius: 0.75rem; background: #fff; border: 1px solid rgb(0 0 0 / 0.08); box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); transition: border-color 0.15s, box-shadow 0.15s; }
        .kb-column.kb-drop { border-color: var(--primary-500); box-shadow: 0 0 0 2px var(--primary-500); }
        .kb-column-head { display: flex; align-items: center; justify-content: space-between; }
        .kb-column-title { margin: 0; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); }
        .kb-count { min-width: 1.5rem; padding: 0.125rem 0.5rem; border-radius: 9999px; text-align: center; font-size: 0.75rem; font-weight: 500; background: var(--gray-100); color: var(--gray-600); }
        .kb-cards { display: flex; flex-direction: column; gap: 0.5rem; min-height: 200px; min-width: 0; }
        .kb-card { min-width: 0; padding: 0.75rem; border-radius: 0.5rem; cursor: move; background: var(--gray-50); border: 1px solid var(--gray-200); transition: box-shadow 0.15s; }
        .kb-card:hover { box-shadow: 0 4px 10px rgb(0 0 0 / 0.1); }
        .kb-card-new { background: #fffbeb; border: 2px solid #f59e0b; }
        .kb-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.375rem; }
        .kb-number { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; color: var(--gray-500); }
        .kb-time { font-size: 0.6875rem; color: var(--gray-500); }
        .kb-total { margin-bottom: 0.25rem; font-size: 1rem; font-weight: 700; color: var(--gray-950); }
        .kb-line { margin-bottom: 0.5rem; }
        .kb-address { font-size: 0.75rem; color: var(--gray-600); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .kb-badge { display: inline-block; padding: 0.125rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .kb-link { font-size: 0.75rem; color: var(--gray-500); text-decoration: none; }
        .kb-link:hover { color: var(--gray-800); text-decoration: underline; }
        .kb-decline { margin-top: 0.5rem; }
        .kb-decline summary { cursor: pointer; font-size: 0.75rem; color: #dc2626; }
        .kb-decline-form { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
        .kb-decline-form select { width: 100%; padding: 0.375rem 0.5rem; border-radius: 0.375rem; border: 1px solid var(--gray-300); font-size: 0.75rem; background: #fff; color: var(--gray-950); }
        .kb-decline-form button { width: 100%; padding: 0.375rem 0.5rem; border: 0; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; background: #dc2626; color: #fff; }
        .kb-decline-form button:hover { background: #b91c1c; }
        .kb-decline-form button:disabled { opacity: 0.5; cursor: not-allowed; }
        .kb-empty { padding: 2rem 0; text-align: center; font-size: 0.75rem; color: var(--gray-400); }

        .dark .kb-column { background: var(--gray-900); border-color: rgb(255 255 255 / 0.1); }
        .dark .kb-column-title, .dark .kb-total { color: #fff; }
        .dark .kb-count { background: var(--gray-800); color: var(--gray-300); }
        .dark .kb-card { background: var(--gray-800); border-color: var(--gray-700); }
        .dark .kb-card-new { background: rgb(245 158 11 / 0.12); border-color: #f59e0b; }
        .dark .kb-number, .dark .kb-time, .dark .kb-address, .dark .kb-link { color: var(--gray-400); }
        .dark .kb-link:hover { color: var(--gray-200); }
        .dark .kb-badge { background: rgb(59 130 246 / 0.15); color: #93c5fd; border-color: rgb(59 130 246 / 0.4); }
        .dark .kb-decline summary { color: #f87171; }
        .dark .kb-decline-form select { background: var(--gray-900); border-color: var(--gray-600); color: #fff; }
        .dark .kb-empty { color: var(--gray-600); }
    </style>
</div>
{{-- @script gives the component's $wire; a plain <script> has none, so moves used to fail --}}
@script
<script>
    (function() {
        // New order: a short double beep, so it is noticed while the tab is in the background.
        // Browsers allow sound only after the page was clicked at least once.
        $wire.on('kanban-new-order', function() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                const ctx = new Ctx();
                [0, 0.25].forEach(function(delay) {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.value = 880;
                    gain.gain.value = 0.2;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + delay);
                    osc.stop(ctx.currentTime + delay + 0.15);
                });
                setTimeout(function() { ctx.close(); }, 1000);
            } catch (e) {
                // No audio available: the highlighted card is still there
            }
        });

        // The listeners live on document: bind them once per page load
        if (window.__kanbanDragBound) return;
        window.__kanbanDragBound = true;

        let draggedOrder = null;

        document.addEventListener('dragstart', function(e) {
            let card = e.target.closest('.kb-card');
            if (!card) return;
            draggedOrder = { id: card.dataset.orderId };
            card.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        document.addEventListener('dragend', function(e) {
            let card = e.target.closest('.kb-card');
            if (!card) return;
            card.style.opacity = '';
            draggedOrder = null;
            document.querySelectorAll('[data-column]').forEach(col => col.classList.remove('kb-drop'));
        });

        document.addEventListener('dragover', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column) return;
            e.preventDefault();
            column.classList.add('kb-drop');
        });

        document.addEventListener('dragleave', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column) return;
            column.classList.remove('kb-drop');
        });

        document.addEventListener('drop', function(e) {
            let column = e.target.closest('[data-column]');
            if (!column || !draggedOrder) return;
            e.preventDefault();
            column.classList.remove('kb-drop');
            $wire.moveOrder(draggedOrder.id, column.dataset.column);
        });
    })();
</script>
@endscript
