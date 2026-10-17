<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title }} — {{ config('app.name', 'RestoPWA') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-8 sm:py-12">
        <a href="{{ url('/') }}" class="text-sm font-semibold text-orange-500 hover:text-orange-600">&larr; На главную</a>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Документы">
            @foreach ($documents as $key => $name)
                <a href="{{ route('legal.show', $key) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold border {{ $key === $document ? 'bg-orange-500 text-white border-orange-500' : 'border-slate-300 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-900' }}">
                    {{ $name }}
                </a>
            @endforeach
        </nav>

        <article class="mt-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-4 text-sm leading-relaxed">
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $title }}</h1>
            <p class="text-xs text-slate-500">Редакция от {{ $version }}</p>

            {{-- DRAFT: remove this banner once the lawyer-approved text is in place --}}
            <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4 text-amber-800 dark:text-amber-200">
                Черновик. Текст документа будет опубликован после согласования с юристом.
            </div>

            @include('legal.documents.'.$document)
        </article>
    </main>
</body>
</html>
