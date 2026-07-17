<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>{{ config('app.name', 'BulkApply') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Apply saved theme + accent before first paint to avoid a flash.
        (function () {
            var t = localStorage.getItem('theme');
            if (t === 'light' || t === 'dark') {
                document.documentElement.setAttribute('data-theme', t);
            }
            var a = localStorage.getItem('accent');
            var accents = ['indigo', 'violet', 'emerald', 'ocean', 'sunset', 'rose', 'amber'];
            if (a && accents.indexOf(a) !== -1) {
                document.documentElement.setAttribute('data-accent', a);
            }
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
