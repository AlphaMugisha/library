<?php
session_start();
// require 'config/db.php';
// require 'config/academic_integration.php';

// --- LOGIC: HANDLE BOTH LOGIN AND ACTIVATION REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); 
    $action = $_POST['action'];

    // 1. STANDARD LOGIN LOGIC
    if ($action === 'standard_login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        // --- MOCK DATABASE BEHAVIOR FOR DEMONSTRATION ---
        // Replace this block with your actual $pdo->prepare() logic
        /*
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        */
        $user = null; // Mocking a failed login for safety in this template

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
            // Added a slight delay to prevent brute-force timing attacks
            sleep(1);
            echo json_encode(['success' => false, 'message' => "Invalid email or password."]); exit;
        }
    } 

    // 2. REQUEST ACTIVATION LINK LOGIC
    if ($action === 'request_activation') {
        $email = trim($_POST['email']);
        
        // --- MOCK DATABASE BEHAVIOR FOR DEMONSTRATION ---
        /*
        $stmt = $pdo->prepare("SELECT activation_token, is_activated FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        */
        $user = null; // Mocking behavior

        sleep(1); // UX delay
        if (!$user) {
            echo json_encode(['success' => false, 'message' => "Email not found in our academic records."]); exit;
        } elseif ($user['is_activated'] == 1) {
            echo json_encode(['success' => false, 'message' => "This account is already active. Just log in!"]); exit;
        } else {
            $link = "activate.php?token=" . $user['activation_token'];
            echo json_encode(['success' => true, 'message' => "Account found! <a href='$link' class='underline font-black ml-1 text-nga-orange hover:text-orange-700'>Activate now</a>"]); exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Access | NGA Library Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', 
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 
                        nga: { 
                            orange: '#FF6600', 
                            orangeHover: '#e65c00',
                            dark: '#0f172a' 
                        } 
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease-out forwards',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-15px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.6);
            --nga-orange: #FF6600;
        }

        .dark {
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body { font-family: 'Inter', sans-serif; }
        
        /* Glassmorphism Panel */
        .glass-panel { 
            background: var(--glass-bg); 
            backdrop-filter: blur(24px) saturate(180%); 
            -webkit-backdrop-filter: blur(24px) saturate(180%); 
            border: 1px solid var(--glass-border); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(255, 102, 0, 0.05); 
        }
        
        .dark .glass-panel {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Left Branding Area */
        .left-gradient { 
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 40%, var(--nga-orange) 150%); 
            position: relative; 
            overflow: hidden; 
        }
        
        .grid-pattern {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255, 102, 0, 0.2) 1.5px, transparent 1.5px);
            background-size: 35px 35px;
            mask-image: radial-gradient(circle at center, black, transparent 80%);
            z-index: 0;
        }

        /* Input Styling */
        .form-control { 
            width: 100%; 
            padding: 18px 16px 18px 54px; 
            background: rgba(255, 255, 255, 0.9); 
            border: 2px solid transparent; 
            border-radius: 20px; 
            font-weight: 600; 
            color: #1e293b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .dark .form-control { 
            background: rgba(15, 23, 42, 0.6); 
            color: white; 
            border-color: rgba(255,255,255,0.05);
        }
        
        .form-control:focus { 
            border-color: var(--nga-orange); 
            box-shadow: 0 0 0 4px rgba(255, 102, 0, 0.15); 
            outline: none; 
            transform: translateY(-2px); 
            background: #ffffff; 
        }
        .dark .form-control:focus { background: rgba(15, 23, 42, 0.9); }
        
        /* Interactive Input Icons */
        .input-icon {
            transition: color 0.3s ease;
        }
        .form-control:focus + .input-icon {
            color: var(--nga-orange);
        }

        /* Submit Button */
        .btn-submit { 
            width: 100%; 
            padding: 18px; 
            background: linear-gradient(135deg, var(--nga-orange) 0%, #fb923c 100%); 
            color: white; 
            border-radius: 20px; 
            font-weight: 900; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease; 
            box-shadow: 0 10px 25px -5px rgba(255, 102, 0, 0.4); 
            position: relative; 
            overflow: hidden; 
        }
        .btn-submit:hover:not(:disabled) { 
            transform: translateY(-3px); 
            box-shadow: 0 20px 30px -5px rgba(255, 102, 0, 0.5); 
            filter: brightness(1.05);
        }
        .btn-submit:active:not(:disabled) { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* Alerts */
        .alert-box { 
            padding: 16px; 
            border-radius: 16px; 
            font-size: 0.875rem; 
            font-weight: 700; 
            margin-bottom: 24px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; }
        
        @keyframes slideIn { 
            from { opacity: 0; transform: translateY(-15px) scale(0.95); } 
            to { opacity: 1; transform: translateY(0) scale(1); } 
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#020617] text-slate-800 dark:text-slate-200 min-h-screen selection:bg-nga-orange selection:text-white">
    
    <canvas id="canvas-container" class="fixed inset-0 w-full h-full pointer-events-none -z-10"></canvas>

    <div class="fixed inset-0 w-full h-full pointer-events-none overflow-hidden -z-20">
        <div class="absolute top-0 left-1/4 w-[600px] h-[600px] bg-orange-500/10 dark:bg-orange-600/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 right-1/4 w-[600px] h-[600px] bg-blue-500/10 dark:bg-blue-600/5 rounded-full blur-[120px]"></div>
    </div>

    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <div class="hidden lg:flex lg:w-[45%] left-gradient relative flex-col justify-between p-16 overflow-hidden">
            <div class="grid-pattern"></div>
            
            <div class="relative z-10">
                <a href="index.php" class="flex items-center gap-4 text-white text-3xl font-black w-max group hover:scale-105 transition-transform">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-[0_0_30px_rgba(255,102,0,0.3)] p-2">
                        <i class='bx bx-book-reader text-3xl text-nga-orange'></i>
                    </div>
                    <span>NGA <span class="text-orange-400 font-medium">Library</span></span>
                </a>
            </div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-full bg-white/10 backdrop-blur-md mb-8 border border-white/20 shadow-lg">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-nga-orange"></span>
                    </span>
                    <span class="text-xs font-black text-white uppercase tracking-[0.2em]">Secure Portal</span>
                </div>
                
                <h1 class="text-6xl xl:text-7xl font-black text-white mb-6 leading-[1.05] tracking-tighter">
                    Unlock Your <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-nga-orange">Academic Edge</span>
                </h1>
                
                <p class="text-slate-300 text-lg font-medium max-w-md leading-relaxed mb-10">
                    Your gateway to thousands of curated resources. Log in to manage your reservations and track your reading journey.
                </p>

                <div class="w-72 h-32 bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-6 flex items-center gap-5 animate-float shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-nga-orange rounded-2xl flex items-center justify-center shadow-inner">
                        <i class='bx bx-fingerprint text-3xl text-white'></i>
                    </div>
                    <div>
                        <p class="text-white font-black text-lg">Verified Access</p>
                        <p class="text-slate-400 text-sm font-medium">Academic Bridge Sync</p>
                    </div>
                </div>
            </div>
            
            <div class="relative z-10 flex items-center gap-6 text-white/40 text-xs font-black uppercase tracking-widest">
                <span>Integrated System</span>
                <span class="w-1 h-1 rounded-full bg-white/40"></span>
                <span>NGA Portal &copy; <?php echo date("Y"); ?></span>
            </div>
        </div>

        <div class="w-full lg:w-[55%] flex flex-col justify-center items-center px-6 py-12 relative z-10">
            
            <div class="lg:hidden flex items-center gap-3 mb-10">
                <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-xl flex items-center justify-center shadow-md">
                    <i class='bx bx-book-reader text-3xl text-nga-orange'></i>
                </div>
                <span class="text-2xl font-black text-slate-900 dark:text-white">NGA Library</span>
            </div>

            <div class="w-full max-w-[460px]">
                <div id="dynamic-alert-container"></div>

                <div id="loginSection" class="glass-panel p-8 md:p-12 rounded-[40px] animate-fade-in">
                    <div class="mb-10 text-center">
                        <h2 class="text-4xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Welcome Back</h2>
                        <p class="text-slate-500 font-medium text-sm">Enter your credentials to access your dashboard.</p>
                    </div>

                    <form id="standardLoginForm">
                        <input type="hidden" name="action" value="standard_login">
                        
                        <div class="mb-6 relative flex flex-col-reverse group">
                            <input type="email" name="email" id="email" class="form-control peer" placeholder="student@nga.rw" required>
                            <i class='bx bx-envelope absolute left-5 top-[47px] text-slate-400 text-xl input-icon peer-focus:text-nga-orange'></i>
                            <label for="email" class="text-[11px] font-black mb-2 text-slate-500 uppercase tracking-widest pl-2 transition-colors peer-focus:text-nga-orange">Email Address</label>
                        </div>
                        
                        <div class="mb-10 relative flex flex-col-reverse group">
                            <input type="password" name="password" id="password" class="form-control peer" placeholder="••••••••" required>
                            <i class='bx bx-lock-alt absolute left-5 top-[47px] text-slate-400 text-xl input-icon peer-focus:text-nga-orange'></i>
                            <label for="password" class="text-[11px] font-black mb-2 text-slate-500 uppercase tracking-widest pl-2 transition-colors peer-focus:text-nga-orange">Password</label>
                        </div>
                        
                        <button type="submit" id="btnSubmit" class="btn-submit flex justify-center items-center">
                            <span id="btnSubmitText" class="flex items-center gap-2">
                                <i class='bx bx-log-in-circle text-2xl'></i>
                                <span>Sign In</span>
                            </span>
                            <i id="btnSubmitLoader" class='bx bx-loader-alt bx-spin text-2xl hidden'></i>
                        </button>
                    </form>

                    <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-800/80 text-center">
                        <p class="text-sm text-slate-500 font-medium">
                            Account not activated yet? 
                            <button onclick="toggleView('activationSection', 'loginSection')" class="text-nga-orange font-black hover:text-orange-700 ml-1 transition-colors">Get your link</button>
                        </p>
                    </div>
                </div>

                <div id="activationSection" class="hidden glass-panel p-8 md:p-12 rounded-[40px]">
                    <div class="mb-10">
                        <button onclick="toggleView('loginSection', 'activationSection')" class="text-slate-400 hover:text-nga-orange font-black text-xs uppercase tracking-widest mb-6 flex items-center gap-2 transition-colors">
                            <i class='bx bx-left-arrow-alt text-lg'></i> Back to Sign In
                        </button>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Activate Account</h2>
                        <p class="text-slate-500 font-medium text-sm">Enter the email registered with Academic Bridge to receive your activation link.</p>
                    </div>

                    <form id="activationForm">
                        <input type="hidden" name="action" value="request_activation">
                        
                        <div class="mb-10 relative flex flex-col-reverse group">
                            <input type="email" name="email" id="act_email" class="form-control peer" placeholder="student@nga.rw" required>
                            <i class='bx bx-envelope absolute left-5 top-[47px] text-slate-400 text-xl input-icon peer-focus:text-nga-orange'></i>
                            <label for="act_email" class="text-[11px] font-black mb-2 text-slate-500 uppercase tracking-widest pl-2 transition-colors peer-focus:text-nga-orange">Registered Email</label>
                        </div>
                        
                        <button type="submit" id="btnActivate" class="btn-submit flex justify-center items-center">
                            <span id="btnActivateText" class="flex items-center gap-2">
                                <i class='bx bx-mail-send text-2xl'></i>
                                <span>Find My Account</span>
                            </span>
                            <i id="btnActivateLoader" class='bx bx-loader-alt bx-spin text-2xl hidden'></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Check Dark Mode Preference (Inherited from main page logic if applicable)
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }

        // View Toggling Logic
        function toggleView(showId, hideId) {
            const hideEl = document.getElementById(hideId);
            const showEl = document.getElementById(showId);
            
            // Clear alerts on switch
            document.getElementById('dynamic-alert-container').innerHTML = '';
            
            hideEl.classList.add('hidden');
            hideEl.classList.remove('animate-fade-in');
            
            showEl.classList.remove('hidden');
            // Re-trigger animation
            void showEl.offsetWidth; 
            showEl.classList.add('animate-fade-in');
        }

        // --- Standard Login AJAX ---
        document.getElementById('standardLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const text = document.getElementById('btnSubmitText');
            const loader = document.getElementById('btnSubmitLoader');
            const alertBox = document.getElementById('dynamic-alert-container');
            
            btn.disabled = true; 
            text.classList.add('hidden');
            loader.classList.remove('hidden');
            alertBox.innerHTML = ''; // Clear previous
            
            fetch('login.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) { 
                    window.location.href = data.url; 
                } else { 
                    btn.disabled = false; 
                    text.classList.remove('hidden');
                    loader.classList.add('hidden');
                    alertBox.innerHTML = `<div class="alert-box alert-error"><i class='bx bx-error-circle text-xl'></i> ${data.message}</div>`;
                }
            })
            .catch(err => {
                btn.disabled = false; 
                text.classList.remove('hidden');
                loader.classList.add('hidden');
                alertBox.innerHTML = `<div class="alert-box alert-error"><i class='bx bx-wifi-off text-xl'></i> Connection error. Please try again.</div>`;
            });
        });

        // --- Activation Request AJAX ---
        document.getElementById('activationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnActivate');
            const text = document.getElementById('btnActivateText');
            const loader = document.getElementById('btnActivateLoader');
            const alertBox = document.getElementById('dynamic-alert-container');

            btn.disabled = true; 
            text.classList.add('hidden');
            loader.classList.remove('hidden');
            alertBox.innerHTML = '';
            
            fetch('login.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false; 
                text.classList.remove('hidden');
                loader.classList.add('hidden');
                
                if(data.success) {
                    alertBox.innerHTML = `<div class="alert-box alert-success"><i class='bx bx-check-circle text-xl'></i> <div>${data.message}</div></div>`;
                } else {
                    alertBox.innerHTML = `<div class="alert-box alert-error"><i class='bx bx-error-circle text-xl'></i> ${data.message}</div>`;
                }
            })
            .catch(err => {
                btn.disabled = false; 
                text.classList.remove('hidden');
                loader.classList.add('hidden');
                alertBox.innerHTML = `<div class="alert-box alert-error"><i class='bx bx-wifi-off text-xl'></i> Connection error. Please try again.</div>`;
            });
        });

        // --- Background Canvas Animation ---
        const canvas = document.getElementById('canvas-container');
        if(canvas) {
            const ctx = canvas.getContext('2d');
            let particles = [];
            
            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            window.addEventListener('resize', resize);
            resize();

            class Particle {
                constructor() { this.init(); }
                init() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 8 + 2; // Smaller, subtler particles
                    this.speedY = Math.random() * 0.5 + 0.1;
                    this.opacity = Math.random() * 0.15 + 0.05;
                }
                update() {
                    this.y -= this.speedY;
                    if(this.y < -this.size) {
                        this.y = canvas.height + this.size;
                        this.x = Math.random() * canvas.width;
                    }
                }
                draw() {
                    ctx.fillStyle = `rgba(255, 102, 0, ${this.opacity})`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            // Create fewer particles on smaller screens
            const pCount = window.innerWidth < 768 ? 10 : 25;
            for(let i=0; i<pCount; i++) particles.push(new Particle());

            function animate() {
                ctx.clearRect(0,0,canvas.width, canvas.height);
                particles.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animate);
            }
            animate();
        }
    </script>
</body>
</html>