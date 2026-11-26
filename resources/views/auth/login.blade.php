<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin &middot; Booking Lapangan</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand-dot"><span>&#9917;</span> Booking Lapangan</div>
        <h1>Masuk Admin</h1>
        <p class="sub">Panel pengelolaan lapangan, jadwal, dan booking.</p>

        @if ($errors->any())
            <div class="alert-inline">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
                <label class="check">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya
                </label>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </div>
        </form>

        <p class="hint" style="margin-top:18px;">Hanya akun dengan role admin yang dapat masuk.</p>
    </div>
</div>
</body>
</html>
