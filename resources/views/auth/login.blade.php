<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SSO</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #f4efe6;
            --panel: #fffaf2;
            --accent: #0f4c5c;
            --accent-strong: #07323d;
            --text: #1b1b18;
            --muted: #6a645b;
            --danger-bg: #fde8e8;
            --danger-text: #8a1c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at top left, rgba(15, 76, 92, 0.14), transparent 35%),
                linear-gradient(135deg, #f4efe6, #efe7d7);
            color: var(--text);
            font-family: Georgia, "Times New Roman", serif;
            padding: 24px;
        }

        .card {
            width: min(100%, 440px);
            background: var(--panel);
            border: 1px solid rgba(15, 76, 92, 0.12);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(27, 27, 24, 0.12);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 2rem;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .error {
            margin: 20px 0 0;
            padding: 14px 16px;
            border-radius: 12px;
            background: var(--danger-bg);
            color: var(--danger-text);
            font-size: 0.95rem;
        }

        .actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .button {
            appearance: none;
            border: none;
            border-radius: 999px;
            padding: 14px 22px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: transform 120ms ease, background 120ms ease;
        }

        .button-primary {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: rgba(15, 76, 92, 0.08);
            color: var(--accent-strong);
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .meta {
            margin-top: 22px;
            font-size: 0.9rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Masuk ke Bunker Dashboard</h1>
        <p>
            Aplikasi ini menggunakan Single Sign-On portal perusahaan. Gunakan akun SSO Anda untuk membuka dashboard.
        </p>

        @if ($ssoError)
            <div class="error">{{ $ssoError }}</div>
        @endif

        <div class="actions">
            <a class="button button-primary" href="{{ route('sso.redirect', ['client_id' => $clientId]) }}">
                Login dengan SSO
            </a>

            @if ($devBypassEnabled)
                <form method="POST" action="{{ route('sso.dev-bypass') }}">
                    @csrf
                    <button class="button button-secondary" type="submit">
                        Masuk mode local dev
                    </button>
                </form>
            @endif
        </div>

        <p class="meta">
            Client ID terdaftar: <strong>{{ $clientId ?: 'belum dikonfigurasi' }}</strong>
        </p>

        @if ($devBypassEnabled)
            <p class="meta">
                Bypass local aktif hanya untuk environment <strong>local</strong>.
            </p>
        @endif
    </main>

    @if ($shouldAutoRedirect && $clientId)
        <script>
            window.location.replace(@json(route('sso.redirect', ['client_id' => $clientId])));
        </script>
    @endif
</body>
</html>
