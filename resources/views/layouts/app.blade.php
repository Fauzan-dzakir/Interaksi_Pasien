<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIM Alat CSSD' }} | RS Kemenkes Surabaya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">

@auth
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        {{-- Laci menu untuk layar kecil --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden print:hidden">
            <div class="fixed inset-0 bg-slate-900/60" @click="sidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 w-72 max-w-[85%] shadow-xl">
                @include('partials.sidebar', ['mobile' => true])
            </div>
        </div>

        {{-- Sidebar tetap untuk layar lebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 lg:block print:hidden">
            @include('partials.sidebar', ['mobile' => false])
        </aside>

        <div class="flex min-h-full flex-col lg:pl-72 print:pl-0">
            {{-- Bilah atas hanya untuk layar kecil --}}
            <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden print:hidden">
                <button type="button" @click="sidebarOpen = true"
                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="Buka menu">
                    <x-icon name="bars" />
                </button>

                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-leaf-500 text-xs font-bold text-white">CS</span>
                <span class="text-sm font-bold text-slate-900">SIM Alat CSSD</span>

                <a href="{{ route('notifications') }}" wire:navigate
                   class="relative ml-auto rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" title="Notifikasi">
                    <x-icon name="bell" />
                    @if (($unread = auth()->user()->unreadNotifications()->count()) > 0)
                        <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                            {{ $unread > 9 ? '9+' : $unread }}
                        </span>
                    @endif
                </a>
            </header>

            <main class="mx-auto w-full max-w-[1500px] flex-1 px-4 py-6 sm:px-6 lg:px-8 print:max-w-none print:p-0">
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-leaf-300 bg-leaf-50 px-4 py-3 text-sm text-leaf-800 print:hidden">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
@else
    <main class="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>
@endauth

@livewireScripts
@stack('scripts')
</body>
</html>
