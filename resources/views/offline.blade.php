<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - {{ config('app.name') }}</title>
    <meta name="theme-color" content="#8B4513">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <style>
        /* Minimal styles for offline page */
        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 90%;
            width: 24rem;
        }
        .icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem;
            color: #8B4513;
        }
        h1 {
            margin: 0 0 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        p {
            margin: 0 0 1.5rem;
            color: #4b5563;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .button:hover {
            background: #A0522D;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background: #1f2937;
                color: #f3f4f6;
            }
            p {
                color: #9ca3af;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h1>You're Offline</h1>
        <p>It looks like you've lost your internet connection. Some features may be unavailable until you're back online.</p>
        <a href="/" class="button">Try Again</a>
    </div>
    <script>
        // Check for online status
        window.addEventListener('online', () => {
            window.location.reload();
        });

        // Add to cache on load
        if ('serviceWorker' in navigator && 'caches' in window) {
            window.addEventListener('load', () => {
                caches.open('chama-app-v1').then((cache) => {
                    cache.add('/offline');
                });
            });
        }
    </script>
</body>
</html>