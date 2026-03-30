<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Офлайн режим - RestoPWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .offline-container {
            max-width: 500px;
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .icon {
            font-size: 80px;
            color: #FF6B35;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #FF6B35;
            border-color: #FF6B35;
        }
        .btn-primary:hover {
            background-color: #e55a29;
            border-color: #e55a29;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon">
            <i class="fas fa-wifi-slash"></i>
        </div>
        <h1 class="h3 mb-3">Соединение потеряно</h1>
        <p class="text-muted mb-4">
            Похоже, вы находитесь в офлайн-режиме. К сожалению, запрашиваемая страница еще не была закэширована.
        </p>
        <div class="d-grid gap-2">
            <button onclick="window.location.reload()" class="btn btn-primary">
                Попробовать обновить
            </button>
            <a href="/" class="btn btn-outline-secondary">
                На главную (если закэширована)
            </a>
        </div>
    </div>
</body>
</html>
