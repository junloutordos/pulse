<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="description" content="PULSE: geospatial decision support for NCE outreach planning at PSHS-CRC. Staff only.">
        <meta name="robots" content="noindex, nofollow">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>try{var t=localStorage.getItem('pulse-theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
