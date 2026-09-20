<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Manajemen Keuangan Bantuan (MKB)</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f766e 100%);
            --card-glass: rgba(255, 255, 255, 0.92);
            --accent-color: #0d9488;
            --accent-hover: #0f766e;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Decorative background elements */
        .bg-circle-1 {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.25) 0%, rgba(0,0,0,0) 70%);
            top: -100px;
            left: -100px;
            z-index: 0;
        }

        .bg-circle-2 {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, rgba(0,0,0,0) 70%);
            bottom: -150px;
            right: -150px;
            z-index: 0;
        }

        .login-card {
            background: var(--card-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            padding: 40px 36px;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
            color: #ffffff;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.3);
            margin-bottom: 16px;
        }

        .brand-title {
            font-weight: 700;
            font-size: 22px;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .form-floating > .form-control {
            border-radius: 14px;
            border: 1px solid #cbd5e1;
            padding-left: 44px;
            padding-right: 48px;
            height: 56px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-floating > label {
            padding-left: 44px;
            color: #64748b;
            font-size: 14px;
        }

        .form-floating > .input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            z-index: 4;
            transition: color 0.2s;
        }

        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            padding: 6px;
            cursor: pointer;
            z-index: 5;
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: var(--accent-color);
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
        }

        .form-control:focus ~ .input-icon {
            color: var(--accent-color);
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border-color: #cbd5e1;
        }

        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .form-check-label {
            font-size: 13px;
            color: #475569;
            font-weight: 500;
            user-select: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            border: none;
            border-radius: 14px;
            height: 52px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.25);
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            box-shadow: 0 12px 24px rgba(13, 148, 136, 0.35);
            transform: translateY(-1px);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        .alert-custom {
            border-radius: 12px;
            font-size: 13px;
            padding: 12px 16px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="bg-circle-1"></div>
    <div class="bg-circle-2"></div>

    <div class="login-card">
        <div class="brand-header">
            <div class="brand-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="brand-title">MKB GRIK</div>
            <div class="brand-subtitle">Manajemen Keuangan Bantuan</div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 10px;"></button>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="form-floating mb-3 position-relative">
                <i class="fa-solid fa-user input-icon"></i>
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" id="usernameInput" placeholder="Username" value="{{ old('username') }}" required autofocus autocomplete="username">
                <label for="usernameInput">Username</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="passwordInput" placeholder="Kata Sandi" required autocomplete="current-password">
                <label for="passwordInput">Kata Sandi</label>
                <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Tampilkan/Sembunyikan Kata Sandi">
                    <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                </button>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberCheck" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="rememberCheck">
                        Ingat Saya
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                Masuk ke Aplikasi <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
            </button>
        </form>

        <div class="footer-text">
            &copy; {{ date('Y') }} Manajemen Keuangan Bantuan. All rights reserved.
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    
                    togglePasswordIcon.classList.toggle('fa-eye', !isPassword);
                    togglePasswordIcon.classList.toggle('fa-eye-slash', isPassword);
                });
            }
        });
    </script>
</body>
</html>