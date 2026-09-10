{{-- resources/views/auth/mobile-redirect.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signing you in</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: linear-gradient(180deg, #005740 0%, #003d2c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        a.overlay-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            height: 100%;
            text-decoration: none;
            padding: 40px;
        }

        .logo {
            width: 140px;
            height: 140px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .title {
            color: #ffffff;
            font-weight: 600;
            font-size: 1.5rem;
            text-align: center;
        }

        .message {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            text-align: center;
        }

        .dots {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffffff;
            animation: bounce 1.2s infinite ease-in-out;
        }

        .dot:nth-child(1) { animation-delay: 0ms; }
        .dot:nth-child(2) { animation-delay: 150ms; }
        .dot:nth-child(3) { animation-delay: 300ms; }

        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.6; }
            40% { transform: translateY(-10px); opacity: 1; }
        }

        .hint {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <a href="{{ $deepLink }}" class="overlay-link" id="returnLink">
        <img src="/images/UP-System-UP-Cebu-Logo.png" alt="Logo" class="logo">
        <p class="title">Sign-in successful</p>
        <p class="message">Returning you to the app…</p>
        <div class="dots">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
        <p class="hint">Tap anywhere if you're not redirected automatically</p>
    </a>

    <script>
        // Some Android/Chrome versions allow this to fire without a tap.
        // Where it's blocked, the full-screen tap above satisfies the gesture requirement instantly.
        window.addEventListener("load", function () {
            document.getElementById("returnLink").click();
        });
    </script>
</body>
</html>