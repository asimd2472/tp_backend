<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#102b3f">
    <title>Admin sign in · TATA Pravesh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-story" aria-label="TATA Pravesh administration">
            <a class="brand-lockup brand-lockup-light" href="{{ route('admin.login') }}" aria-label="TATA Pravesh home">
                <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="brand-wordmark"><strong>TATA</strong><span>PRAVESH</span></span>
            </a>

            <div class="story-copy">
                <span class="eyebrow"><span class="eyebrow-dot"></span> ADMINISTRATION PORTAL</span>
                <h1>Good decisions<br>start with a<br><em>clear view.</em></h1>
                <p>A considered space to manage your people, protect your platform and move work forward.</p>
            </div>

            <div class="story-footer">
                <span class="story-rule"></span>
                <span>Trusted access. Thoughtful oversight.</span>
                <span class="story-year">TATA PRAVESH <span aria-hidden="true">·</span> ADMIN</span>
            </div>
            <div class="story-orbit story-orbit-one" aria-hidden="true"></div>
            <div class="story-orbit story-orbit-two" aria-hidden="true"></div>
            <div class="story-glow" aria-hidden="true"></div>
        </section>

        <section class="login-content">
            <div class="login-mobile-brand">
                <a class="brand-lockup" href="{{ route('admin.login') }}" aria-label="TATA Pravesh home">
                    <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="brand-wordmark"><strong>TATA</strong><span>PRAVESH</span></span>
                </a>
            </div>

            <div class="login-card-wrap">
                <div class="login-heading">
                    <span class="login-overline">WELCOME BACK</span>
                    <h2>Sign in to your<br>workspace<span>.</span></h2>
                    <p>Enter your administrator credentials to continue.</p>
                </div>

                <div class="alert login-alert d-none" id="loginAlert" role="alert" aria-live="polite"></div>

                <form id="adminLoginForm" action="{{ route('admin.login.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="email">Work email</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3.5" y="5" width="17" height="14" rx="2.5"></rect>
                                <path d="m4.5 7 7.5 5.5L19.5 7"></path>
                            </svg>
                            <input class="form-control login-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@company.com" autocomplete="username" required autofocus aria-describedby="emailError">
                        </div>
                        <div class="field-error" id="emailError"></div>
                    </div>

                    <div class="mb-3">
                        <div class="password-label-row">
                            <label class="form-label mb-0" for="password">Password</label>
                        </div>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"></path>
                            </svg>
                            <input class="form-control login-input" id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required aria-describedby="passwordError">
                            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="2.5"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="field-error" id="passwordError"></div>
                    </div>

                    <div class="login-options">
                        <label class="remember-option">
                            <input class="form-check-input" type="checkbox" name="remember" value="1">
                            <span>Keep me signed in</span>
                        </label>
                        <span class="secure-note">
                            <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 7V5a4 4 0 1 1 8 0v2M3 7h10v7H3z"></path></svg>
                            Secure access
                        </span>
                    </div>

                    <button class="btn login-submit w-100" type="submit" id="loginSubmit">
                        <span class="button-label">Sign in securely</span>
                        <span class="button-loader spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <svg class="button-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5"></path></svg>
                    </button>
                </form>

                <div class="login-security">
                    <span class="security-shield" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none"><path d="m10 2 6 2.5v4.8c0 4.1-2.5 6.9-6 8.7-3.5-1.8-6-4.6-6-8.7V4.5L10 2Z"></path><path d="m7.5 9.8 1.7 1.7 3.5-3.5"></path></svg>
                    </span>
                    <p>Your account is protected with encrypted sign-in and secure sessions.</p>
                </div>
            </div>

            <footer class="login-legal">
                <span>© {{ now()->year }} TATA Pravesh</span>
                <span class="legal-divider"></span>
                <span>Administrator access only</span>
            </footer>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}" defer></script>
</body>
</html>
