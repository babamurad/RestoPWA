<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Livewire\Order\KanbanBoard;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index()
    {
        return view('vendor.orders.kanban');
    }
}
