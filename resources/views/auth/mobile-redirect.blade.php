{{-- resources/views/auth/mobile-redirect.blade.php --}}
<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="font-family: sans-serif; text-align: center; padding-top: 60px;">
    <h3>Sign-in successful</h3>
    <p><a href="{{ $deepLink }}" style="font-size: 18px;">Tap here to return to the app</a></p>

    <script>
        // Attempt automatic redirect first — works on some Android/Chrome versions
        window.location.href = "{{ $deepLink }}";
    </script>
</body>
</html>