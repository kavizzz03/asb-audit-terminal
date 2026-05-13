<?php
session_start();
// Set Timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

// 1. SESSION ACCESS CONTROL
$is_logged_in = isset($_SESSION['user_id']);
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

// Redirect logged-in non-admin users to dashboard
if ($is_logged_in && $role_id != 1) {
    header("Location: dashboard.php");
    exit();
}

$error_message = isset($_GET['auth_error']) ? "Security Protocol Violation: Unauthorized Access Attempt" : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="logo.png">
    <title>ASB Group | Enterprise Access Terminal</title>
    <link rel="stylesheet" href="css\index.css">
</head>
<body>
    <div class="bg-mesh"></div>
    <div class="noise-overlay"></div>

    <main class="main-container">
        
        <!-- LEFT COLUMN: CORPORATE BRANDING -->
        <div class="branding-sidebar">
            <div class="branding-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L3 7L12 12L21 7L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M3 12L12 17L21 12" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M3 17L12 22L21 17" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="brand-subtitle">Secure Enterprise</h2>
                        <p class="brand-tag">Global Operations Node</p>
                    </div>
                </div>

                <div class="hero-section">
                    <h1 class="hero-title">
                        ASB <span class="gradient-text">Group Of Companies</span>
                    </h1>
                    <p class="hero-description">
                        Authorized Personnel Portal for access to <span class="highlight">Circulars</span>, 
                        <span class="highlight">Standing Orders</span>, and internal <span class="highlight">Instruction Directives</span>.
                    </p>
                </div>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 4H20V20H4V4Z" stroke="currentColor" stroke-linejoin="round"/>
                            <path d="M8 7H16" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 12H16" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 17H13" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Directives</h3>
                    <p class="feature-badge">Latest Updates v2.4</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <?php if ($role_id == 1 && $is_logged_in): ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2C8.13 2 5 5.13 5 9C5 13.17 9 18 12 18C15 18 19 13.17 19 9C19 5.13 15.87 2 12 2Z"/>
                            <path d="M12 12C10.34 12 9 10.66 9 9C9 7.34 10.34 6 12 6C13.66 6 15 7.34 15 9C15 10.66 13.66 12 12 12Z"/>
                            <path d="M5 15L3 22H21L19 15"/>
                        </svg>
                        <?php else: ?>
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 2C8.13 2 5 5.13 5 9C5 13.17 9 18 12 18C15 18 19 13.17 19 9C19 5.13 15.87 2 12 2Z"/>
                            <circle cx="12" cy="9" r="3"/>
                            <path d="M8 21L12 18L16 21"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="feature-title">
                        <?php echo ($role_id == 1 && $is_logged_in) ? 'Admin Master' : 'Evidence'; ?>
                    </h3>
                    <p class="feature-badge">
                        <?php echo ($role_id == 1 && $is_logged_in) ? 'Root Access Active' : 'Identity Logging Active'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: LOGIN TERMINAL -->
        <div class="login-terminal">
            <div class="terminal-inner">
                <!-- Mobile Branding -->
                <div class="mobile-branding">
                    <h1 class="mobile-title">ASB<span class="accent">GROUP</span></h1>
                    <p class="mobile-subtitle">Enterprise Directive Portal</p>
                </div>

                <!-- Error Notification -->
                <?php if($error_message): ?>
                <div class="error-alert">
                    <div class="error-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4M12 16h.01"/>
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        </svg>
                    </div>
                    <p class="error-text"><?php echo $error_message; ?></p>
                </div>
                <?php endif; ?>

                <div class="terminal-header">
                    <h3 class="terminal-title">Initialize Access</h3>
                    <p class="terminal-subtitle">Personnel Authentication Required</p>
                </div>

                <form action="auth.php" method="POST" class="login-form">
                    <div class="input-group">
                        <label class="input-label">Operator ID</label>
                        <div class="input-field">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <input type="text" name="username" required placeholder="USERNAME"
                                class="input-control"
                                value="<?php echo isset($_COOKIE['remember_user']) ? htmlspecialchars($_COOKIE['remember_user']) : ''; ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Security Key</label>
                        <div class="input-field">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                class="input-control password-input">
                            <button type="button" onclick="togglePassword()" class="toggle-password">
                                <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" class="checkbox-hidden">
                            <div class="checkbox-custom">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" class="check-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                            <span class="checkbox-text">Remember Node</span>
                        </label>
                        <a href="#" class="recovery-link">Recovery</a>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span>Authorize Identity</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- FOOTER META -->
            <div class="terminal-footer">
                <div>
                    <p class="footer-label">Gateway Provider</p>
                    <p class="footer-value">Vexel <span class="accent">IT</span></p>
                </div>
                <div class="text-right">
                    <p class="footer-label">Lead Engineer</p>
                    <p class="footer-value">Kavizz <span class="accent">SL</span></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.querySelector('.password-input');
            const eyeSvg = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeSvg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                passwordInput.type = 'password';
                eyeSvg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }

        // Custom checkbox styling
        document.querySelectorAll('.checkbox-hidden').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const customCheckbox = this.nextElementSibling;
                const checkIcon = customCheckbox?.querySelector('.check-icon');
                if (this.checked) {
                    customCheckbox?.classList.add('checked');
                    if (checkIcon) checkIcon.style.opacity = '1';
                } else {
                    customCheckbox?.classList.remove('checked');
                    if (checkIcon) checkIcon.style.opacity = '0';
                }
            });
        });
    </script>
</body>
</html>