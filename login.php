<?php
session_start();
require 'config/db.php';
require 'config/academic_integration.php';

// --- LOGIC: HANDLE BOTH LOGIN AND ACTIVATION REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); 
    $action = $_POST['action'];

    // 1. STANDARD LOGIN LOGIC
    if ($action === 'standard_login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // Check if Suspended
            if($user['library_status'] === 'suspended') {
                echo json_encode(['success' => false, 'message' => "Account suspended. Contact administration."]); exit;
            }
            
            // Check if Activated
            if($user['is_activated'] == 0) {
                echo json_encode(['success' => false, 'message' => "Account not activated! Click 'Activate Account' below."]); exit;
            }
            
            // Set session variables
            $role = strtolower(trim($user['role']));
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $user['full_name'];

            if ($role === 'librarian') { $redirect_url = 'librarian/dashboard.php'; } 
            elseif ($role === 'student') { $redirect_url = 'student/dashboard.php'; } 
            elseif ($role === 'teacher') { $redirect_url = 'teacher/dashboard.php'; } 
            else { $redirect_url = 'index.php'; }

            echo json_encode(['success' => true, 'step' => 'redirect', 'url' => $redirect_url]); exit;
            
        } else {
            echo json_encode(['success' => false, 'message' => "Invalid email or password."]); exit;
        }
    } 

    // 2. NEW: REQUEST ACTIVATION LINK LOGIC
    if ($action === 'request_activation') {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT activation_token, is_activated FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => "Email not found in our records."]); exit;
        } elseif ($user['is_activated'] == 1) {
            echo json_encode(['success' => false, 'message' => "This account is already active. Just log in!"]); exit;
        } else {
            $link = "activate.php?token=" . $user['activation_token'];
            echo json_encode(['success' => true, 'message' => "Account found! <a href='$link' class='underline font-bold ml-1'>Click here to activate now</a>"]); exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Login | NGA Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary: #FF6600;
            --primary-hover: #e65c00;
            --secondary: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        .dark {
            --primary: #FF6600;
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body { font-family: 'Inter', sans-serif; transition: background-color 0.4s ease, color 0.4s ease; }
        .glass { background: var(--glass-bg); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border: 1px solid var(--glass-border); box-shadow: 0 20px 40px rgba(255, 102, 0, 0.08); }
        .left-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, var(--primary) 100%); position: relative; overflow: hidden; }
        
        .form-control { width: 100%; padding: 16px 16px 16px 50px; background: rgba(255, 255, 255, 0.8); border: 1px solid var(--glass-border); border-radius: 16px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .dark .form-control { background: rgba(15, 23, 42, 0.4); color: white; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(255, 102, 0, 0.1); outline: none; transform: translateY(-1px); background: rgba(255, 255, 255, 1); }
        
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%); color: white; border-radius: 16px; font-weight: 800; transition: all 0.3s ease; box-shadow: 0 10px 15px -3px rgba(255, 102, 0, 0.3); position: relative; overflow: hidden; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(255, 102, 0, 0.4); }
        .btn-submit:active { transform: translateY(0); }

        .alert { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; padding: 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease-out; }
        .success-alert { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; padding: 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease-out; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Grid Pattern */
        .grid-pattern {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255, 102, 0, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#020617] overflow-hidden">
    <div class="fixed inset-0 w-full h-full pointer-events-none overflow-hidden -z-10">
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-orange-400/10 dark:bg-orange-600/5 rounded-full filter blur-[100px]"></div>
        <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-orange-400/10 dark:bg-orange-600/5 rounded-full filter blur-[100px]"></div>
    </div>

    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Sidebar -->
        <div class="hidden lg:flex lg:w-1/2 left-gradient relative flex-col justify-between p-16 overflow-hidden">
            <div class="grid-pattern"></div>
            <div class="relative z-10">
                <a href="index.php" class="flex items-center gap-3 text-white text-2xl font-black w-max group">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center border-2 border-transparent group-hover:border-orange-400 transition-all shadow-lg p-1.5">
                        <i class='bx bx-book-reader text-2xl text-[#FF6600]'></i>
                    </div>
                    <span>NGA <span class="text-orange-400">Library</span></span>
                </a>
            </div>
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md mb-8 border border-white/10">
                    <span class="w-2 h-2 rounded-full bg-orange-400 animate-pulse"></span>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Digital Hub</span>
                </div>
                <h1 class="text-6xl md:text-7xl font-black text-white mb-6 leading-[1.1] tracking-tighter">
                    Unlock Your <br>
                    <span class="text-orange-400">Knowledge</span>
                </h1>
                <p class="text-slate-300 text-xl font-medium max-w-md leading-relaxed">
                    Access your academic workspace, manage resources, and track your reading journey.
                </p>
            </div>
            <div class="relative z-10 flex items-center gap-8 text-white/50 text-xs font-bold uppercase tracking-widest">
                <span>Integrated System</span>
                <span>•</span>
                <span>NGA Portal</span>
            </div>
        </div>

        <!-- Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-8 relative z-10">
            <div class="w-full max-w-[440px]">
                <div id="dynamic-alert-container"></div>

                <div id="loginSection" class="glass p-8 md:p-10 rounded-[40px]">
                    <div class="mb-10">
                        <h2 class="text-4xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">Sign In</h2>
                        <p class="text-slate-500 font-medium">Log in to your library dashboard.</p>
                    </div>

                    <form id="standardLoginForm">
                        <input type="hidden" name="action" value="standard_login">
                        <div class="mb-5 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-400 uppercase tracking-widest pl-1">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="user@nga.rw" required>
                            <i class='bx bx-envelope absolute left-4 top-[43px] text-slate-400 text-xl'></i>
                        </div>
                        <div class="mb-8 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-400 uppercase tracking-widest pl-1">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            <i class='bx bx-lock-alt absolute left-4 top-[43px] text-slate-400 text-xl'></i>
                        </div>
                        <button type="submit" id="btnSubmit" class="btn-submit">
                            <span class="flex items-center justify-center gap-2">
                                <i class='bx bx-log-in-circle text-xl'></i>
                                <span>Access Library Portal</span>
                            </span>
                        </button>
                    </form>

                    <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-800/50 text-center">
                        <p class="text-sm text-slate-500 font-medium">
                            First time here? 
                            <button onclick="toggleView('activationSection')" class="text-orange-600 dark:text-orange-400 font-bold hover:underline ml-1">Activate your account</button>
                        </p>
                    </div>
                </div>

                <div id="activationSection" class="hidden glass p-8 md:p-10 rounded-[40px]">
                    <div class="mb-10">
                        <button onclick="toggleView('loginSection')" class="text-slate-400 hover:text-orange-600 font-bold text-sm mb-4 flex items-center gap-1 transition-colors">
                            <i class='bx bx-arrow-back'></i> Back to Login
                        </button>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">Activate Account</h2>
                        <p class="text-slate-500 font-medium">Enter your email to get your activation link.</p>
                    </div>

                    <form id="activationForm">
                        <input type="hidden" name="action" value="request_activation">
                        <div class="mb-8 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-400 uppercase tracking-widest pl-1">Registered Email</label>
                            <input type="email" name="email" class="form-control" placeholder="yourname@nga.rw" required>
                            <i class='bx bx-envelope absolute left-4 top-[43px] text-slate-400 text-xl'></i>
                        </div>
                        <button type="submit" id="btnActivate" class="btn-submit">
                            <span class="flex items-center justify-center gap-2">
                                <i class='bx bx-mail-send text-xl'></i>
                                <span>Get Activation Link</span>
                            </span>
                        </button>
                    </form>
                </div>

                <div class="mt-10 text-center">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.2em]">
                        &copy; <?php echo date("Y"); ?> NGA Library System
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleView(viewId) {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('activationSection').classList.add('hidden');
            document.getElementById(viewId).classList.remove('hidden');
            document.getElementById('dynamic-alert-container').innerHTML = '';
        }

        // Handle Login
        document.getElementById('standardLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true; btn.innerHTML = "Authenticating...";
            
            fetch('login.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { window.location.href = data.url; } 
                else { 
                    btn.disabled = false; btn.innerHTML = "Log In";
                    document.getElementById('dynamic-alert-container').innerHTML = `<div class="alert"><i class='bx bx-error-circle'></i> ${data.message}</div>`;
                }
            });
        });

        // Handle Activation Request
        document.getElementById('activationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnActivate');
            btn.disabled = true; btn.innerHTML = "Searching...";
            
            fetch('login.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false; btn.innerHTML = "Get Link";
                if(data.success) {
                    document.getElementById('dynamic-alert-container').innerHTML = `<div class="success-alert"><i class='bx bx-check-circle'></i> ${data.message}</div>`;
                } else {
                    document.getElementById('dynamic-alert-container').innerHTML = `<div class="alert"><i class='bx bx-error-circle'></i> ${data.message}</div>`;
                }
            });
        });

        // Canvas Particle Logic (Simplified for brevity)
        const canvas = document.getElementById('canvas-container');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function init() {
            canvas.width = window.innerWidth; canvas.height = window.innerHeight;
            for(let i=0; i<15; i++) { particles.push({ x: Math.random()*canvas.width, y: Math.random()*canvas.height, r: Math.random()*20+10, sy: Math.random()*0.5+0.1 }); }
        }
        function draw() {
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.fillStyle = "rgba(255, 102, 0, 0.05)";
            particles.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2); ctx.fill(); p.y -= p.sy; if(p.y < -p.r) p.y = canvas.height+p.r; });
            requestAnimationFrame(draw);
        }
        init(); draw();
    </script>
</body>
</html>