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

        if (!$user) {
            echo json_encode(['success' => false, 'message' => "User not found with this email."]); exit;
        }

        if (password_verify($password, $user['password'])) {
            
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
            echo json_encode(['success' => false, 'message' => "Incorrect password. Please try again."]); exit;
        }
    } 

    // 2. REQUEST ACTIVATION LINK LOGIC
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
    
    // 3. SSO LOGIN LOGIC (NEW)
    if ($action === 'sso_login') {
        $token = trim($_POST['token'] ?? '');
        
        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Academic token is required']); exit;
        }
        
        $integration = getAcademicIntegration($pdo);
        
        // Check if we have a valid session already
        $existingSession = $integration->validateAcademicSession($token);
        if ($existingSession) {
            // User already logged in, set session and redirect
            $_SESSION['user_id'] = $existingSession['user_id'];
            $_SESSION['role'] = $existingSession['role'];
            $_SESSION['name'] = $existingSession['full_name'];
            $_SESSION['academic_login'] = true;
            
            $redirect_url = getDashboardUrl($existingSession['role']);
            echo json_encode(['success' => true, 'step' => 'redirect', 'url' => $redirect_url]); exit;
        }
        
        // Validate token with academic system
        $academicUser = $integration->validateToken($token);
        
        if (!$academicUser) {
            echo json_encode(['success' => false, 'message' => 'Invalid academic token. Please try logging in again.']); exit;
        }
        
        // Sync user data from academic system
        try {
            $localUser = $integration->updateLocalUser($academicUser);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to sync user data: ' . $e->getMessage()]); exit;
        }
        
        if (!$localUser) {
            echo json_encode(['success' => false, 'message' => 'Failed to sync user data']); exit;
        }
        
        // Check user status
        if ($localUser['library_status'] === 'suspended') {
            echo json_encode(['success' => false, 'message' => 'Library access suspended. Contact administration.']); exit;
        }
        
        if ($localUser['is_activated'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Account not activated. Please activate your account first.']); exit;
        }
        
        // Store academic session
        $integration->storeAcademicSession($localUser['id'], $token, $academicUser);
        
        // Set local session
        $_SESSION['user_id'] = $localUser['id'];
        $_SESSION['role'] = $localUser['role'];
        $_SESSION['name'] = $localUser['full_name'];
        $_SESSION['academic_login'] = true;
        $_SESSION['academic_id'] = $localUser['academic_id'];
        
        $redirect_url = getDashboardUrl($localUser['role']);
        echo json_encode(['success' => true, 'step' => 'redirect', 'url' => $redirect_url]); exit;
    }
}

// Helper function to get dashboard URL
function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    
    switch ($role) {
        case 'librarian':
            return 'librarian/dashboard.php';
        case 'student':
            return 'student/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        default:
            return 'index.php';
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
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); }
        .left-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); position: relative; overflow: hidden; }
        .form-control { width: 100%; padding: 16px 16px 16px 50px; background: rgba(255, 255, 255, 0.9); border: 2px solid transparent; border-radius: 16px; font-weight: 600; transition: all 0.3s ease; backdrop-filter: blur(10px); }
        .dark .form-control { background: rgba(30, 41, 59, 0.9); color: white; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 8px 25px -5px rgba(102, 126, 234, 0.25); outline: none; transform: translateY(-2px); background: rgba(255, 255, 255, 1); }
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 16px; font-weight: 800; transition: all 0.3s ease; box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3); position: relative; overflow: hidden; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4); background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); }
        .btn-submit::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: left 0.5s; }
        .btn-submit:hover::before { left: 100%; }
        .btn-submit:active { transform: translateY(-1px); }
        .alert { background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fecaca; color: #dc2626; padding: 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease-out; }
        .success-alert { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; color: #16a34a; padding: 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .left-gradient::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: float 20s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 33% { transform: translate(30px, -30px) rotate(120deg); } 66% { transform: translate(-20px, 20px) rotate(240deg); } }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#020617] overflow-hidden">
    <canvas id="canvas-container" class="fixed inset-0 pointer-events-none -z-10"></canvas>

    <div class="min-h-screen flex flex-col lg:flex-row">
        <div class="hidden lg:flex lg:w-1/2 left-gradient relative flex-col justify-between p-12 overflow-hidden">
             <div class="relative z-10">
                <div class="flex items-center gap-3 text-white text-2xl font-black w-max">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center border-2 border-[#667eea]">
                        <i class='bx bx-book-reader text-2xl text-[#667eea]'></i>
                    </div>
                    <span>NGA <span class="text-[#667eea]">Library</span></span>
                </div>
            </div>
            <div class="relative z-10">
                <h1 class="text-6xl font-black text-white mb-6 leading-tight">Welcome to your <br><span class="text-[#667eea]">Digital Library</span></h1>
                <p class="text-slate-400 text-lg font-medium max-w-md">Access your academic workspace and manage your library resources seamlessly.</p>
                <div class="mt-8 p-4 bg-white/10 backdrop-blur-md rounded-xl border border-white/20">
                    <div class="flex items-center gap-3 text-white">
                        <i class='bx bx-link text-xl'></i>
                        <span class="text-sm">Integrated with Academic System</span>
                    </div>
                </div>
            </div>
            <div></div>
        </div>

        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-8 relative z-10">
            <div class="w-full max-w-[400px]">
                <div id="dynamic-alert-container"></div>

                <div id="loginSection">
                    <div class="mb-8">
                        <h2 class="text-4xl font-black text-slate-900 dark:text-white mb-2">Sign In</h2>
                        <p class="text-slate-500 font-medium">Log in to your library dashboard.</p>
                    </div>

                    <form id="standardLoginForm">
                        <input type="hidden" name="action" value="standard_login">
                        <div class="mb-4 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-300 uppercase tracking-widest">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="user@nga.rw" required>
                            <i class='bx bx-envelope absolute left-4 top-[42px] text-slate-400 text-xl'></i>
                        </div>
                        <div class="mb-8 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-300 uppercase tracking-widest">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                            <i class='bx bx-lock-alt absolute left-4 top-[42px] text-slate-400 text-xl'></i>
                        </div>
                        <button type="submit" id="btnSubmit" class="btn-submit">Log In</button>
                    </form>

                    <div class="mt-6 flex items-center gap-4">
                        <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                        <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Or</span>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                    </div>

                    <form id="ssoLoginForm">
                        <input type="hidden" name="action" value="sso_login">
                        <input type="hidden" name="token" id="academicToken">
                        <button type="button" id="btnSSO" class="w-full btn-submit bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 flex items-center justify-center gap-3">
                            <i class='bx bx-graduation-cap text-xl'></i>
                            Login with Academic System
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                        <p class="text-sm text-slate-500 font-medium">
                            First time here? 
                            <button onclick="toggleView('activationSection')" class="text-[#667eea] font-bold hover:underline ml-1">Activate your account</button>
                        </p>
                    </div>
                </div>

                <div id="activationSection" class="hidden">
                    <div class="mb-8">
                        <button onclick="toggleView('loginSection')" class="text-slate-400 hover:text-[#667eea] font-bold text-sm mb-4 flex items-center gap-1">
                            <i class='bx bx-arrow-back'></i> Back to Login
                        </button>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-2">Activate Account</h2>
                        <p class="text-slate-500 font-medium">Enter your email to get your activation link.</p>
                    </div>

                    <form id="activationForm">
                        <input type="hidden" name="action" value="request_activation">
                        <div class="mb-6 relative">
                            <label class="block text-xs font-bold mb-2 text-slate-600 dark:text-slate-300 uppercase tracking-widest">Registered Email</label>
                            <input type="email" name="email" class="form-control" placeholder="yourname@nga.rw" required>
                            <i class='bx bx-envelope absolute left-4 top-[42px] text-slate-400 text-xl'></i>
                        </div>
                        <button type="submit" id="btnActivate" class="btn-submit">Get Link</button>
                    </form>
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

        // Handle Standard Login
        document.getElementById('standardLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true; btn.innerHTML = "Authenticating...";
            
            fetch('login_enhanced.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { window.location.href = data.url; } 
                else { 
                    btn.disabled = false; btn.innerHTML = "Log In";
                    document.getElementById('dynamic-alert-container').innerHTML = `<div class="alert"><i class='bx bx-error-circle'></i> ${data.message}</div>`;
                }
            });
        });

        // Handle SSO Login
        document.getElementById('btnSSO').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true; btn.innerHTML = '<i class=\'bx bx-loader-alt animate-spin\'></i> Connecting to Academic System...';
            
            // Simulate getting token from academic system
            // In production, this would redirect to academic system or open popup
            setTimeout(() => {
                // For demo purposes, we'll simulate a token
                // In real implementation, you'd get this from Levi's academic system
                const mockToken = 'demo_academic_token_' + Date.now();
                
                document.getElementById('academicToken').value = mockToken;
                
                // Submit SSO form
                const formData = new FormData(document.getElementById('ssoLoginForm'));
                
                fetch('login_enhanced.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) { 
                        // Show success message before redirect
                        document.getElementById('dynamic-alert-container').innerHTML = `<div class="success-alert"><i class='bx bx-check-circle'></i> ${data.message || 'Successfully logged in via Academic System!'}</div>`;
                        setTimeout(() => {
                            window.location.href = data.url;
                        }, 1500);
                    } else { 
                        btn.disabled = false; btn.innerHTML = '<i class=\'bx bx-graduation-cap text-xl\'></i> Login with Academic System';
                        document.getElementById('dynamic-alert-container').innerHTML = `<div class="alert"><i class='bx bx-error-circle'></i> ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    btn.disabled = false; btn.innerHTML = '<i class=\'bx bx-graduation-cap text-xl\'></i> Login with Academic System';
                    document.getElementById('dynamic-alert-container').innerHTML = `<div class="alert"><i class='bx bx-error-circle'></i> Connection failed. Please try again.</div>`;
                });
            }, 2000);
        });

        // Handle Activation Request
        document.getElementById('activationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnActivate');
            btn.disabled = true; btn.innerHTML = "Searching...";
            
            fetch('login_enhanced.php', { method: 'POST', body: new FormData(this) })
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

        // Canvas Particle Logic
        const canvas = document.getElementById('canvas-container');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function init() {
            canvas.width = window.innerWidth; canvas.height = window.innerHeight;
            for(let i=0; i<15; i++) { particles.push({ x: Math.random()*canvas.width, y: Math.random()*canvas.height, r: Math.random()*20+10, sy: Math.random()*0.5+0.1 }); }
        }
        function draw() {
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.fillStyle = "rgba(255, 255, 255, 0.05)";
            particles.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2); ctx.fill(); p.y -= p.sy; if(p.y < -p.r) p.y = canvas.height+p.r; });
            requestAnimationFrame(draw);
        }
        init(); draw();
    </script>
</body>
</html>
