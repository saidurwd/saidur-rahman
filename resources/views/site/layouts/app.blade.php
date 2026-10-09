<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body class="bg-stone-50 text-slate-900">
    @yield('content')
</body>
</html>
