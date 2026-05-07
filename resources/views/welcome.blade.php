<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome — Arugamai</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
                color: #111827;
                font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .page {
                text-align: center;
                padding: 36px 24px;
            }

            .logo {
                width: 300px;
                height: 300px;
                object-fit: contain;
                margin: 0 auto 24px;
                display: block;
            }

            h1 {
                margin: 0;
                font-size: clamp(2.25rem, 3.5vw, 3rem);
                letter-spacing: -0.04em;
            }

            p {
                margin: 16px auto 0;
                max-width: 36rem;
                color: #4b5563;
                font-size: 1rem;
                line-height: 1.75;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <img class="logo" src="{{ asset('splash-logo.png') }}" alt="Arugamai logo">
            <h1>Welcome to Arugamai App</h1>
            <p>Tamilnadu most trusted Hyperlocal App</p>
        </div>
    </body>
</html>
