<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- TITLE -->
    <title>{{ $title ?? 'Ariatyx Gaming' }}</title>

    <!-- ✅ FAVICON (LOGO FOR AUTH PAGES) -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#ebebeb]">
            <div class="w-full">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>