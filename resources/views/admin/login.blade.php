<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaceBoard Admin — Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
        }

        .login-visual {
            flex: 1.1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
            color: #fff;
            min-height: 100vh;
        }
        .login-visual img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .login-visual-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(11,18,32,.75) 0%, rgba(11,18,32,.45) 50%, rgba(37,99,235,.35) 100%);
        }
        .login-visual-content {
            position: relative;
            z-index: 1;
            max-width: 480px;
        }
        .login-visual-content .brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin-bottom: 2rem;
        }
        .login-visual-content .brand img {
            position: static;
            width: 48px; height: 48px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,.3);
        }
        .login-visual-content h1 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.2;
            margin-bottom: .75rem;
        }
        .login-visual-content p { opacity: .85; font-size: 1rem; line-height: 1.6; }
        .login-features {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .login-features li {
            list-style: none;
            display: flex;
            align-items: center;
            gap: .65rem;
            font-size: .9rem;
            opacity: .9;
        }
        .login-features i { color: #60a5fa; width: 20px; }

        .login-form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #f8fafc;
        }
        .login-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 24px rgba(15,23,42,.08);
            border: 1px solid #e2e8f0;
        }
        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .35rem;
            letter-spacing: -.02em;
        }
        .login-card .subtitle { color: #64748b; font-size: .9rem; margin-bottom: 2rem; }

        label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: .4rem;
            color: #334155;
        }
        .input-wrap {
            position: relative;
            margin-bottom: 1.15rem;
        }
        .input-wrap i {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: .9rem;
        }
        .input-wrap input {
            width: 100%;
            padding: .75rem .9rem .75rem 2.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            font-size: .95rem;
            font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }

        .btn-signin {
            width: 100%;
            padding: .85rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: .95rem;
            font-weight: 600;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: background .15s;
            margin-top: .5rem;
        }
        .btn-signin:hover { background: #1d4ed8; }

        .error {
            color: #dc2626;
            font-size: .875rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: .7rem .85rem;
            border-radius: 10px;
            margin-bottom: 1.15rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: .75rem;
            color: #94a3b8;
        }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .login-visual { min-height: 240px; flex: none; padding: 2rem; }
            .login-visual-content h1 { font-size: 1.4rem; }
            .login-features { display: none; }
        }
    </style>
</head>
<body>
<div class="login-visual">
    <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1600&q=80"
         alt="Car on road">
    <div class="login-visual-overlay"></div>
    <div class="login-visual-content">
        <div class="brand">
            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=96&h=96&q=80" alt="PaceBoard">
            <div>
                <strong style="font-size:1.1rem">PaceBoard</strong>
                <div style="font-size:.7rem;opacity:.7;letter-spacing:.08em;text-transform:uppercase">Admin Console</div>
            </div>
        </div>
        <h1>Enterprise fleet &amp; driver management</h1>
        <p>Monitor trips, safety reports, challenges, and emergency SOS alerts from a single control center.</p>
        <ul class="login-features">
            <li><i class="fa-solid fa-gauge-high"></i> Real-time analytics dashboard</li>
            <li><i class="fa-solid fa-shield-halved"></i> Safety &amp; compliance monitoring</li>
            <li><i class="fa-solid fa-triangle-exclamation"></i> Emergency SOS alert center</li>
        </ul>
    </div>
</div>

<div class="login-form-side">
    <div class="login-card">
        <h2>Sign in</h2>
        <p class="subtitle">Enter your credentials to access the admin panel</p>
        <form method="POST" action="/admin/login">
            @csrf
            @if($errors->any())
                <div class="error"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
            @endif
            <label for="email">Email address</label>
            <div class="input-wrap">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@paceboard.com">
            </div>
            <label for="password">Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-signin">
                <i class="fa-solid fa-right-to-bracket"></i> Sign in to dashboard
            </button>
        </form>
        <p class="footer"><i class="fa-regular fa-copyright"></i> {{ date('Y') }} PaceBoard Technologies</p>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const error = document.getElementById('loginError')?.value;
    if (error) {
        Swal.fire({
            icon: 'error',
            title: 'Sign in failed',
            text: error,
            confirmButtonText: 'Try again',
            confirmButtonColor: '#2563eb',
        });
    }

    document.getElementById('loginForm')?.addEventListener('submit', () => {
        Swal.fire({
            title: 'Signing in…',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });
    });
});
</script>
</body>
</html>
