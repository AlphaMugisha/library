<?php
session_start();
// We will add the DB config and redirect logic later once the login is built
// require 'config/db.php'; 
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGA Library Portal | Academic Bridge</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', 
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { nga: { brand: '#FF6600', navy: '#0f172a' } },
                    animation: {
                        'blob': 'blob 20s infinite ease-in-out alternate',
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 3s infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* 
            NGA Library Portal - Premium Academic UI Overhaul
            Design System: Modern SaaS / Academic 
        */

        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #0f172a;
            --accent: #0d9488;
            --bg-light: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.4);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .dark {
            --primary: #3b82f6;
            --primary-hover: #60a5fa;
            --secondary: #020617;
            --bg-light: #020617;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            --card-shadow-hover: 0 25px 50px -12px rgba(59, 130, 246, 0.15);
        }

        /* Base Styles & Typography */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            transition: background-color 0.5s ease, color 0.5s ease;
            scroll-behavior: smooth;
            color: var(--text-main);
            background-color: var(--bg-light);
        }

        /* Typography Polish */
        h1, h2, h3 {
            letter-spacing: -0.02em;
            font-weight: 800;
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Navbar Enhancements */
        #navbar {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #navbar .glass {
            background: var(--glass-bg);
            border-bottom: 1px solid var(--glass-border);
            backdrop-filter: blur(12px) saturate(180%);
        }

        #navbar.shadow-md {
            height: 70px;
        }

        #navbar.shadow-md .max-w-\[1400px\] {
            height: 70px;
        }

        /* Glassmorphism Refined */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid var(--glass-border);
        }

        /* Premium Button Styling (Overrides) */
        .bg-gradient-to-r.from-\[\#FF6600\] {
            background: linear-gradient(to right, var(--primary), var(--primary-hover)) !important;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3) !important;
        }

        .bg-gradient-to-r.from-\[\#FF6600\]:hover {
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4) !important;
            transform: translateY(-2px);
        }

        .bg-slate-900.dark\:bg-white {
            transition: all 0.3s ease;
        }

        .bg-slate-900.dark\:bg-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.3);
        }

        /* Card Improvements */
        .card-hover {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--glass-border) !important;
            border-radius: 24px !important;
        }

        .card-hover:hover {
            transform: translateY(-12px) scale(1.01);
            box-shadow: var(--card-shadow-hover);
            border-color: rgba(37, 99, 235, 0.3) !important;
        }

        /* Feature Icon Gradients */
        .from-orange-500.to-\[\#FF6600\] {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover)) !important;
            box-shadow: 0 8px 20px -5px rgba(37, 99, 235, 0.4) !important;
        }

        .from-blue-500.to-blue-600 {
            background: linear-gradient(135deg, var(--accent), #0f766e) !important;
            box-shadow: 0 8px 20px -5px rgba(13, 148, 136, 0.4) !important;
        }

        .from-purple-500.to-purple-600 {
            background: linear-gradient(135deg, #6366f1, #4338ca) !important;
            box-shadow: 0 8px 20px -5px rgba(99, 102, 241, 0.4) !important;
        }

        /* Hero Section Visuals */
        .bg-orange-400\/10, .bg-orange-600\/5 {
            background-color: rgba(37, 99, 235, 0.08) !important;
        }

        .bg-blue-400\/10, .bg-blue-600\/5 {
            background-color: rgba(13, 148, 136, 0.08) !important;
        }

        /* Floating Elements in Hero */
        .from-\[\#FF6600\].to-orange-400 {
            background: linear-gradient(135deg, var(--primary), #60a5fa) !important;
        }

        .text-\[\#FF6600\] {
            color: var(--primary) !important;
        }

        .bg-\[\#FF6600\] {
            background-color: var(--primary) !important;
        }

        .border-\[\#FF6600\]\/50:hover {
            border-color: var(--primary) !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-light);
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            border: 2px solid var(--bg-light);
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
            border: 2px solid #020617;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Animations */
        @keyframes soft-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .animate-float {
            animation: soft-float 6s ease-in-out infinite;
        }

        .animate-float-delayed {
            animation: soft-float 6s ease-in-out 3s infinite;
        }

        /* Footer Polish */
        footer.glass {
            border-top: 1px solid var(--glass-border);
            margin-top: 5rem;
            padding: 3rem 0;
        }

        /* Interactive Elements Micro-interactions */
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Selection */
        ::selection {
            background: var(--primary);
            color: white;
        }

        /* Grid Pattern Background for Hero */
        section.relative.min-h-screen::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--glass-border) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, black 50%, transparent);
            -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent);
            z-index: -1;
            opacity: 0.5;
        }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-800 dark:bg-[#020617] dark:text-slate-200 selection:bg-nga-brand selection:text-white overflow-x-hidden">
    
    <canvas id="canvas-container"></canvas>
    
    <div class="fixed inset-0 w-full h-full pointer-events-none overflow-hidden -z-10" style="will-change: transform;">
        <div class="absolute top-0 left-1/4 w-[300px] h-[300px] bg-orange-400/10 dark:bg-orange-600/5 rounded-full filter blur-[80px] animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-[300px] h-[300px] bg-blue-400/10 dark:bg-blue-600/5 rounded-full filter blur-[80px] animate-blob" style="animation-delay: -7s;"></div>
    </div>

    <nav class="fixed w-full top-0 z-50 transition-all duration-300" id="navbar">
        <div class="glass border-b border-slate-200/50 dark:border-slate-800/30">
            <div class="max-w-[1400px] mx-auto px-6 h-20 flex items-center justify-between">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md border border-slate-100 dark:border-slate-700">
                        <i class='bx bx-book-reader text-2xl text-[#FF6600]'></i>
                    </div>
                    <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white group-hover:text-[#FF6600] transition-colors">NGA <span class="text-slate-400 dark:text-slate-500 font-medium">Library</span></span>
                </a>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#" class="font-bold text-slate-600 dark:text-slate-300 hover:text-[#FF6600] dark:hover:text-white transition-colors">Home</a>
                    <a href="#catalog" class="font-bold text-slate-600 dark:text-slate-300 hover:text-[#FF6600] dark:hover:text-white transition-colors">Public Catalog</a>
                    
                    <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center justify-center">
                        <i class='bx bxs-sun text-lg hidden dark:block'></i>
                        <i class='bx bxs-moon text-lg block dark:hidden'></i>
                    </button>

                    <a href="login.php" class="group relative flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-[#FF6600] to-[#e65c00] text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transform hover:-translate-y-0.5 overflow-hidden">
                        <i class='bx bx-log-in-circle text-lg'></i> Student Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative min-h-screen pt-32 pb-20 px-6 overflow-hidden flex items-center">
        <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center relative z-10">
            
            <div data-aos="fade-right" data-aos-duration="1200" class="text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass mb-8 shadow-sm border border-slate-200/50 dark:border-slate-700/50">
                    <span class="w-2 h-2 rounded-full bg-[#FF6600] animate-pulse-slow"></span>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Digital Resource Center</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black tracking-tighter mb-6 leading-[1.05] text-slate-900 dark:text-white">
                    Unlock Your <br>
                    <span class="text-gradient">Next Great Read</span>
                </h1>
                
                <p class="text-lg md:text-xl text-slate-600 dark:text-slate-400 mb-10 font-medium leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Welcome to the New Generation Academy Library Portal. Browse thousands of digital and physical books, track your reading history, and manage your borrowed resources all in one place.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="#catalog" class="w-full sm:w-auto px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-2xl transition-all shadow-xl hover:-translate-y-1 flex items-center justify-center gap-2">
                        <i class='bx bx-search-alt-2 text-xl'></i> Browse Catalog
                    </a>
                    <a href="login.php" class="w-full sm:w-auto px-8 py-4 glass border border-slate-200/50 dark:border-slate-700/50 text-slate-800 dark:text-white font-bold rounded-2xl transition-all hover:border-[#FF6600]/50 hover:bg-white/50 flex items-center justify-center gap-2">
                        <i class='bx bx-user-circle text-xl'></i> Access My Account
                    </a>
                </div>
            </div>

            <div class="relative h-[500px] flex items-center justify-center" data-aos="fade-left" data-aos-duration="1200">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-[400px] h-[400px] rounded-full bg-gradient-to-br from-[#FF6600]/20 to-blue-500/20 blur-3xl animate-pulse-slow"></div>
                </div>
                
                <div class="relative w-full max-w-md">
                    <div class="absolute -top-6 -left-6 w-32 h-32 bg-gradient-to-br from-[#FF6600] to-orange-400 rounded-3xl shadow-2xl rotate-[-12deg] opacity-90 animate-float z-0"></div>
                    <div class="absolute -bottom-8 -right-8 w-40 h-40 bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl shadow-2xl rotate-[8deg] opacity-90 animate-float-delayed z-0"></div>
                    
                    <div class="relative glass rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden z-10 p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-20 bg-slate-200 dark:bg-slate-700 rounded-lg shadow-inner flex items-center justify-center">
                                <i class='bx bxs-book text-3xl text-slate-400'></i>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">Advanced Web Development</h3>
                                <p class="text-sm font-bold text-[#FF6600]">Available Now</p>
                                <p class="text-xs text-slate-500 mt-1">Tech / Programming</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="h-2 w-full bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-2 w-5/6 bg-slate-200 dark:bg-slate-700 rounded"></div>
                            <div class="h-2 w-4/6 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-500">ISBN: 978-3-16-1484</span>
                            <button class="px-4 py-2 bg-[#FF6600] text-white text-xs font-bold rounded-lg shadow-lg">Reserve Book</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-20 px-6 relative z-10">
        <div class="max-w-[1400px] mx-auto">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-black mb-4 text-slate-900 dark:text-white tracking-tight">Smart Library Features</h2>
                <p class="text-slate-600 dark:text-slate-400 font-medium max-w-2xl mx-auto">Everything you need to manage your academic reading journey seamlessly.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="card-hover group relative glass p-8 rounded-[32px] border border-slate-200 dark:border-slate-800" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-[#FF6600] rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-orange-500/25">
                        <i class='bx bx-search-alt text-white text-2xl'></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Live Digital Catalog</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">Search the entire NGA library database in real-time. Check availability, locate shelves, and reserve copies instantly.</p>
                </div>

                <div class="card-hover group relative glass p-8 rounded-[32px] border border-slate-200 dark:border-slate-800" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/25">
                        <i class='bx bx-history text-white text-2xl'></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Automated Tracking</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">Keep track of your borrowed books, upcoming due dates, and past reading history directly from your student dashboard.</p>
                </div>

                <div class="card-hover group relative glass p-8 rounded-[32px] border border-slate-200 dark:border-slate-800" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-purple-500/25">
                        <i class='bx bx-laptop text-white text-2xl'></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">E-Resource Hub</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">Access PDFs, research papers, and digital study materials provided by the NGA academic staff.</p>
                </div>

            </div>
        </div>
    </section>

    <footer class="border-t border-slate-200 dark:border-slate-800/80 py-10 mt-10 relative z-10 glass">
        <div class="max-w-[1400px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3 opacity-60 hover:opacity-100 transition-opacity">
                <i class='bx bxs-graduation text-2xl text-slate-800 dark:text-white'></i>
                <span class="text-xs font-bold tracking-widest uppercase text-slate-800 dark:text-white">
                    New Generation Academy
                </span>
            </div>
            <p class="text-xs text-slate-500 font-bold">
                &copy; <?php echo date("Y"); ?> NGA Library System. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize animations
            setTimeout(() => {
                if (typeof AOS !== 'undefined') {
                    AOS.init({ duration: 800, offset: 50, once: true, easing: 'ease-out-cubic' });
                }
            }, 100);

            // Theme Toggle Logic (Forced Light Mode Default)
            const htmlElement = document.documentElement;
            const themeToggleBtn = document.getElementById('theme-toggle');

            if (localStorage.getItem('theme') === 'dark') {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
                localStorage.setItem('theme', 'light'); 
            }

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    htmlElement.classList.toggle('dark');
                    if (htmlElement.classList.contains('dark')) {
                        localStorage.setItem('theme', 'dark');
                    } else {
                        localStorage.setItem('theme', 'light');
                    }
                });
            }

            // Navbar shadow on scroll
            const nav = document.getElementById('navbar');
            if (nav) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 20) {
                        nav.classList.add('shadow-md');
                        nav.classList.remove('shadow-sm');
                    } else {
                        nav.classList.remove('shadow-md');
                        nav.classList.add('shadow-sm');
                    }
                });
            }

            // Canvas Particles Logic (Exactly from your original code)
            const canvas = document.getElementById('canvas-container');
            const ctx = canvas.getContext('2d');
            let particles = [];
            
            function resizeCanvas() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            class Particle {
                constructor() { this.reset(); }
                reset() {
                    this.x = Math.random() * canvas.width;
                    this.y = canvas.height + Math.random() * 100;
                    this.size = Math.random() * 20 + 15;
                    this.speedY = Math.random() * 0.3 + 0.15;
                    this.speedX = Math.random() * 0.5 - 0.25;
                    this.opacity = Math.random() * 0.25 + 0.08;
                    this.color = this.getRandomColor();
                    this.shape = Math.floor(Math.random() * 4);
                    this.rotation = Math.random() * Math.PI * 2;
                    this.rotationSpeed = Math.random() * 0.01 - 0.005;
                }
                getRandomColor() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const colors = [
                        'rgba(255, 102, 0, OPACITY)', 'rgba(59, 130, 246, OPACITY)',
                        'rgba(168, 85, 247, OPACITY)', 'rgba(34, 197, 94, OPACITY)', 'rgba(251, 146, 60, OPACITY)'
                    ];
                    return colors[Math.floor(Math.random() * colors.length)].replace('OPACITY', this.opacity);
                }
                update() {
                    this.y -= this.speedY;
                    this.x += this.speedX;
                    this.rotation += this.rotationSpeed;
                    if (this.y < -this.size * 2) {
                        this.reset();
                        this.y = canvas.height + this.size * 2;
                    }
                }
                draw() {
                    ctx.save();
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.rotation);
                    ctx.fillStyle = this.color;
                    if (this.shape === 0) { ctx.beginPath(); ctx.arc(0, 0, this.size, 0, Math.PI * 2); ctx.fill(); } 
                    else if (this.shape === 1) { ctx.fillRect(-this.size/2, -this.size/2, this.size, this.size); } 
                    else if (this.shape === 2) {
                        ctx.beginPath(); ctx.moveTo(0, -this.size); ctx.lineTo(this.size * 0.866, this.size * 0.5); ctx.lineTo(-this.size * 0.866, this.size * 0.5); ctx.closePath(); ctx.fill();
                    } else {
                        ctx.beginPath(); ctx.moveTo(0, -this.size); ctx.lineTo(this.size, 0); ctx.lineTo(0, this.size); ctx.lineTo(-this.size, 0); ctx.closePath(); ctx.fill();
                    }
                    ctx.restore();
                }
            }

            function initParticles() {
                particles = [];
                const particleCount = Math.min(12, Math.floor(canvas.width / 80));
                for (let i = 0; i < particleCount; i++) {
                    const p = new Particle();
                    p.y = Math.random() * canvas.height;
                    particles.push(p);
                }
            }

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animate);
            }

            initParticles();
            animate();
        });
    </script>
</body>
</html>