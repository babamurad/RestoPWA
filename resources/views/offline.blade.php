<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Офлайн режим - RestoPWA</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            color: #212529;
        }
        .offline-container {
            max-width: 500px;
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .icon {
            font-size: 80px;
            color: #FF6B35;
            margin-bottom: 20px;
        }
        .icon svg {
            width: 80px;
            height: 80px;
            fill: currentColor;
        }
        h1 {
            font-size: 1.5rem;
            margin: 0 0 16px;
            font-weight: 600;
        }
        p {
            color: #6c757d;
            margin: 0 0 20px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background-color: #FF6B35;
            color: #fff;
            width: 100%;
            margin-bottom: 10px;
        }
        .btn-primary:hover {
            background-color: #e55a29;
        }
        .btn-outline-secondary {
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #6c757d;
            width: 100%;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M23.64 7c-.45-.34-4.93-4-11.64-4-1.5 0-2.89.19-4.15.48L18.18 13.8 23.64 7zm-3.22 6.84l-2.76 2.74-3.77-3.77-3.77 3.77-2.74-2.76 3.77-3.77-3.77-3.76 2.74-2.74 3.77 3.77 3.77-3.77 2.74 2.74-3.77 3.77 3.77 3.76-2.74 2.76zM3.36 7l-.48.48C5.44 10.9 8.53 13.5 12 13.5c3.04 0 5.69-1.98 7.16-4.8l.4-.34.46.46 2.78 2.78C21.3 13.2 18.52 15.5 15 15.5c-3.47 0-6.55-2.6-8.64-6.5-.2-.38-.38-.78-.38-1.15 0-.55.45-1 1-1 .2 0 .39.06.55.16L12.48 12.2c.5.5 1.21.67 1.82.38l3.2-1.5-4.8-4.8-3.2 1.5c-.4-.1-.82-.02-1.14.3L2.27 13.27c-.28.46-.22 1.08.14 1.46l2.69 2.7c.46.46 1.08.59 1.71.3l3.2-1.5L4.1 20.9l-2.69-2.69c-.28-.46-.22-1.08.14-1.46l2.65-2.65L.77 17.36c-.31.15-.5.47-.5.81 0 .55.45 1 1 1 .34 0 .66-.19.81-.5l2.28-2.28z"/>
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
            </svg>
        </div>
        <h1>Соединение потеряно</h1>
        <p>Похоже, вы находитесь в офлайн-режиме. К сожалению, запрашиваемая страница еще не была закэширована.</p>
        <div>
            <button onclick="window.location.reload()" class="btn btn-primary">
                Попробовать обновить
            </button>
            <a href="/" class="btn btn-outline-secondary">
                На главную (если закэширована)
            </a>
        </div>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>
