<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;

class KanbanController extends Controller
{
    public function index()
    {
        return view('vendor.orders.kanban');
    }
}
