<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk | SIM Alat CSSD RS Kemenkes Surabaya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-brand-800 via-brand-600 to-leaf-500 p-4 antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
