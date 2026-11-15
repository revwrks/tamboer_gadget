<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tamboer Gadget — POS & Inventaris</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Instant Dark Mode Detection (Zero FOUC) -->
    <script>
        if (localStorage.getItem('tamboer_dark_theme') === 'true') {
            document.documentElement.classList.add('app-dark');
        }
    </script>

    @vite('resources/js/app.js')
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            margin: 0;
            background-color: #f6f5f0;
            color: #1c1917;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        html.app-dark body,
        body.app-dark {
            background-color: #121110 !important;
            color: #fafaf9 !important;
        }
    </style>
</head>
<body class="antialiased bg-[#f6f5f0] text-stone-900 selection:bg-amber-400 selection:text-stone-900">
    <div id="app">
        <!-- Vue components will mount here -->
    </div>
</body>
</html>
