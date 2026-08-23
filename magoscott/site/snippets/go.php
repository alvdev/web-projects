<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abriendo…</title>
    <noscript><meta http-equiv="refresh" content="0;url=<?= $to ?>"></noscript>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1e1b4b;
            color: #f0abfc;
            font-family: system-ui, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .spinner {
            width: 2rem;
            height: 2rem;
            border: 3px solid rgba(240, 171, 252, .3);
            border-top-color: #f0abfc;
            border-radius: 9999px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .card {
            text-align: center;
            padding: 2rem;
        }

        .hint {
            display: block;
            margin-top: 1.5rem;
            font-size: .85rem;
            color: rgba(240, 171, 252, .6);
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="spinner"></div>
        <a href="<?= $to ?>">Abriendo…</a>
        <a class="hint"
           href="<?= $to ?>">Si no se abre, toca aquí</a>
    </div>
    <script>
        location.replace(<?= json_encode($to) ?>);
    </script>
</body>

</html>
