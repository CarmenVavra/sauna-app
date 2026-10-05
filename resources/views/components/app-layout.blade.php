@props(['title' => null])
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}Saunabuch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,400;0,600;1,400&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#inhalt">Zum Inhalt springen</a>

    <header class="container">
        <nav class="nav" aria-label="Hauptnavigation">
            <a class="nav-brand" href="{{ route('home') }}">Saunabuch <span class="nav-role">{{ $layout['roleLabel'] }}</span></a>
            <ul class="nav-links">
                @foreach ($layout['nav'] as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" @if (request()->routeIs(...explode('|', $item['active']))) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-small">Abmelden</button>
                    </form>
                </li>
            </ul>
        </nav>
        <div class="rule-double"></div>
        <div class="dateline">
            <span>{{ $layout['today'] }}</span>
            <span>{{ $layout['mid'] }}</span>
            <span>{{ $layout['right'] }}</span>
        </div>
        <div class="rule"></div>
    </header>

    <main id="inhalt" class="main" tabindex="-1">
        {{ $slot }}
    </main>

    <x-toast />
</body>
</html>
