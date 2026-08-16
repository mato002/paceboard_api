<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email verified — PaceBoard</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { background: #fff; color: #0f172a; padding: 2rem; border-radius: 16px; max-width: 420px; text-align: center; }
        h1 { margin: 0 0 .5rem; font-size: 1.4rem; }
        p { color: #64748b; line-height: 1.5; }
        .ok { font-size: 3rem; margin-bottom: .5rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="ok">✓</div>
    <h1>{{ ($already ?? false) ? 'Already verified' : 'Email verified!' }}</h1>
    <p>You can close this page and sign in to the PaceBoard app.</p>
</div>
</body>
</html>
