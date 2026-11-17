<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->supportTickets()->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $ticket = $request->user()->supportTickets()->create([
            'subject' => $validated['subject'] ?? 'Обращение в поддержку',
            'message' => $validated['message'],
            'status' => 'open'
        ]);

        return response()->json([
            'message' => 'Сообщение отправлено. Мы ответим вам в ближайшее время!',
            'data' => $ticket
        ], 201);
    }
}
