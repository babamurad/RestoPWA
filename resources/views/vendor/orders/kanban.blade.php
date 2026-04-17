@extends('vendor.layout.app')

@section('title', 'Kanban заказов')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kanban заказов</h1>
        <p class="text-gray-600">Перетаскивайте заказы между колонками для изменения статуса</p>
    </div>

    @livewire('order.kanban-board')
</div>
@endsection
