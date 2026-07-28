<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIM Alat CSSD' }} — RS Kemenkes Surabaya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">

<div class="min-h-full">
    @auth
        <div class="print:hidden">
            @include('partials.navbar')
        </div>
    @endauth

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 print:max-w-none print:p-0">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
