<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Klinik Grand Warden</title>

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-10">
                <div class="card shadow-lg border-0" style="border-radius: 1rem;">
                    <div class="row g-0">

                        <div class="col-lg-6 d-none d-lg-block">
                            <img src="{{ asset('images/officebldg.jpg') }}"
                                 alt="Gedung Klinik" class="img-fluid"
                                 style="height: 100%; object-fit: cover; border-radius: 1rem 0 0 1rem;">
                        </div>

                        <div class="col-lg-6">
                            <div class="card-body p-4 p-md-5">
                                <h3 class="fw-bold mb-4">LOGIN</h3>

                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <form action="/login" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-0">
                                                <i class="bi bi-person"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-0"
                                                   placeholder="Username" name="username" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-0">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" class="form-control bg-light border-0"
                                                   placeholder="Password" name="password" required id="passwordInput">
                                            <span class="input-group-text bg-light border-0" style="cursor: pointer;" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="ingatSaya">
                                            <label class="form-check-label" for="ingatSaya">
                                                Ingat Saya
                                            </label>
                                        </div>
                                        </div>

                                    <button class="btn btn-primary btn-lg w-100 mt-3" type="submit">
                                        Login
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const passwordInput = document.querySelector('#passwordInput');
            const eyeIcon = togglePassword.querySelector('i');

            togglePassword.addEventListener('click', function () {
                // Toggle the input type
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the icon class
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html>
