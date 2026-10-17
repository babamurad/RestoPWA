<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function show(string $document): View
    {
        $documents = config('legal.documents');
        abort_unless(array_key_exists($document, $documents), 404);

        return view('legal.show', [
            'document' => $document,
            'title' => $documents[$document],
            'documents' => $documents,
            'version' => config('legal.version'),
        ]);
    }
}
