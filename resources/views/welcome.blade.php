<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Vite: Load CSS --}}
    @vite('resources/css/app.css')
</head>
<body class="antialiased">
    <div id="app">
        <!-- Vue components will mount here -->
    </div>

    {{-- Vite: Load JS --}}
    @vite('resources/js/app.js')

</body>
</html>
