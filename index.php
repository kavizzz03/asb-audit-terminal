<?php
session_start();
// Set Timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

// 1. SESSION ACCESS CONTROL
// If a user is already logged in, we check their status.
$is_logged_in = isset($_SESSION['user_id']);
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;

// Logic: If already logged in as a standard user, redirect them to the dashboard 
// to prevent them from seeing the login terminal again.
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        body {
            background-color: #020617;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow-y: auto; 
        }

        .bg-mesh {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(190, 18, 60, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 20% 80%, rgba(15, 23, 42, 1) 0%, transparent 50%);
            z-index: -1;
        }

        .premium-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(40px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.7);
        }

        .crimson-gradient-text {
            background: linear-gradient(to right, #ffffff, #fb7185);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-field {
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
        }

        .input-field:focus-within {
            border-bottom: 1px solid #be123c;
            background: rgba(190, 18, 60, 0.05);
        }

        .btn-crimson {
            background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.2em;
        }

        .btn-crimson:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(190, 18, 60, 0.4);
        }

        .sidebar-info {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb { background: #be123c; border-radius: 10px; }
    </style>
</head>
<body class="py-10">

    <div class="bg-mesh"></div>

    <main class="w-full max-w-7xl min-h-[85vh] grid grid-cols-1 lg:grid-cols-12 gap-0 overflow-hidden lg:rounded-[3rem] premium-card">
        
        <!-- LEFT COLUMN: CORPORATE BRANDING -->
        <div class="hidden lg:flex lg:col-span-7 p-12 xl:p-16 flex-col justify-between sidebar-info animate__animated animate__fadeIn">
            <div>
                <div class="flex items-center gap-4 mb-12">
                    <div class="w-12 h-12 rounded-2xl bg-rose-600 flex items-center justify-center shadow-lg shadow-rose-900/40">
                        <i class="fa-solid fa-building-shield text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-black tracking-[0.4em] text-rose-500 uppercase">Secure Enterprise</h2>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Global Operations Node</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <h1 class="text-5xl xl:text-6xl font-extrabold leading-tight tracking-tighter">
                        ASB <span class="crimson-gradient-text">Group Of Companies</span>
                    </h1>
                    <p class="text-slate-400 text-lg max-w-md font-medium leading-relaxed">
                        Authorized Personnel Portal for access to <span class="text-white">Circulars</span>, 
                        <span class="text-white">Standing Orders</span>, and internal <span class="text-white">Instruction Directives</span>.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mt-12">
                <!-- Directives: Visible to authenticated users or Super Admins -->
                <div class="p-6 rounded-3xl bg-white/5 border border-white/5 hover:border-rose-500/30 transition-colors group">
                    <i class="fa-solid fa-file-contract text-rose-500 mb-4 text-2xl group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-sm font-bold text-white mb-1">Directives</h3>
                    <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Latest Updates v2.4</p>
                </div>

                <!-- Admin Node: Visual cue for Super Admin status -->
                <div class="p-6 rounded-3xl bg-white/5 border border-white/5 hover:border-rose-500/30 transition-colors group">
                    <i class="fa-solid <?php echo ($role_id == 1) ? 'fa-unlock-keyhole' : 'fa-fingerprint'; ?> text-rose-500 mb-4 text-2xl group-hover:scale-110 transition-transform"></i>
                    <h3 class="text-sm font-bold text-white mb-1">
                        <?php echo ($role_id == 1) ? 'Admin Master' : 'Evidence'; ?>
                    </h3>
                    <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">
                        <?php echo ($role_id == 1) ? 'Root Access Active' : 'Identity Logging Active'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: LOGIN TERMINAL -->
        <div class="col-span-1 lg:col-span-5 flex flex-col items-center p-8 lg:p-12 xl:p-16 relative bg-slate-950/40">
            
            <div class="w-full flex-grow flex flex-col justify-center">
                <!-- Mobile Branding -->
                <div class="lg:hidden text-center mb-12 animate__animated animate__fadeInDown">
                    <h1 class="text-4xl font-black text-white italic tracking-tighter">ASB<span class="text-rose-600">GROUP</span></h1>
                    <p class="text-[9px] text-slate-500 font-black uppercase tracking-[0.3em] mt-2">Enterprise Directive Portal</p>
                </div>

                <!-- Error Notification -->
                <?php if($error_message): ?>
                <div class="w-full mb-8 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-4 animate__animated animate__shakeX">
                    <div class="w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-500">
                        <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                    </div>
                    <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest leading-tight">
                        <?php echo $error_message; ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="mb-10">
                    <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Initialize Access</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Personnel Authentication Required</p>
                </div>

                <form action="auth.php" method="POST" class="space-y-6 xl:space-y-8">
                    <div class="input-field px-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Operator ID</label>
                        <div class="flex items-center">
                            <i class="fa-solid fa-id-badge text-slate-600 mr-3 text-sm"></i>
                            <input type="text" name="username" required placeholder="USERNAME"
                                class="w-full py-3 xl:py-4 bg-transparent outline-none text-sm font-bold text-white tracking-widest uppercase placeholder:text-slate-800"
                                value="<?php echo isset($_COOKIE['remember_user']) ? $_COOKIE['remember_user'] : ''; ?>">
                        </div>
                    </div>

                    <div class="input-field px-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Security Key</label>
                        <div class="flex items-center">
                            <i class="fa-solid fa-shield-halved text-slate-600 mr-3 text-sm"></i>
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                class="w-full py-3 xl:py-4 bg-transparent outline-none text-sm font-bold text-white tracking-[0.5em] placeholder:text-slate-800">
                            <button type="button" onclick="togglePassword()" class="text-slate-600 hover:text-rose-500 transition">
                                <i id="eye-icon" class="fa-solid fa-eye-low-vision text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="remember" class="hidden peer">
                            <div class="w-4 h-4 border border-slate-700 rounded bg-slate-900 peer-checked:bg-rose-600 peer-checked:border-rose-600 transition-all flex items-center justify-center">
                                <i class="fa-solid fa-check text-[8px] text-white"></i>
                            </div>
                            <span class="ml-3 text-[9px] font-black text-slate-500 uppercase tracking-tighter group-hover:text-slate-300">Remember Node</span>
                        </label>
                        <a href="#" class="text-[9px] font-black text-rose-500 uppercase tracking-tighter hover:text-rose-400">Recovery</a>
                    </div>

                    <button type="submit" class="btn-crimson w-full py-5 rounded-2xl text-white text-[10px] font-black uppercase shadow-xl shadow-rose-950/20">
                        Authorize Identity
                    </button>
                </form>
            </div>

            <!-- FOOTER META: Pinned to bottom of right column -->
            <div class="w-full pt-8 mt-8 border-t border-white/5 flex items-center justify-between">
                <div>
                    <p class="text-[7px] font-black text-slate-600 uppercase tracking-widest">Gateway Provider</p>
                    <p class="text-xs font-black text-white italic">Vexel <span class="text-rose-600">IT</span></p>
                </div>
                <div class="text-right">
                    <p class="text-[7px] font-black text-slate-600 uppercase tracking-widest">Lead Engineer</p>
                    <p class="text-[10px] font-black text-slate-300 uppercase italic">Kavizz <span class="text-rose-600">SL</span></p>
                </div>
            </div>
        </div>
    </main>


    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('fa-eye-low-vision', 'fa-eye');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-low-vision');
            }
        }
    </script>
</body>
</html>