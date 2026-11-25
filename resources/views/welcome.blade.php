<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tamboer Gadget — POS & Inventaris</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Zero-FOUC Theme Initialization -->
    <script>
        (function () {
            var stored = localStorage.getItem('tamboer_dark_theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'true' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('app-dark');
            }
        })();
    </script>

    @vite('resources/js/app.js')

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f7;
            color: #111113;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        html.app-dark body {
            background-color: #0d0d0f;
            color: #f5f5f7;
        }
    </style>
</head>
<body class="antialiased selection:bg-amber-400 selection:text-zinc-950">
    <div id="app"></div>
</body>
</html>
