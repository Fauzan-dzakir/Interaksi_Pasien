<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIM Alat CSSD' }} — RS Kemenkes Surabaya</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-192.png') }}" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-linear-to-br from-slate-50 via-slate-100 to-teal-50/50 text-slate-800 antialiased">

<div class="min-h-full flex flex-col md:flex-row">
    @auth
        @include('partials.navbar')
    @endauth

    <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-8 print:max-w-none print:p-0">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 print:hidden">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</div>

<!-- TomSelect JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@livewireScripts
@stack('scripts')
</body>
</html>
