<?php
session_start();
require 'config/db.php';

// Fetch Trending Books (Popularity formula: views + 2*borrows)
$trending_books = [];
try {
    $stmt = $pdo->query("SELECT *, (view_count + (borrow_count * 2)) as score FROM books ORDER BY score DESC LIMIT 4");
    $trending_books = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGA Library | Academic Excellence Hub</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

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
                            navy: '#0f172a',
                            accent: '#f97316'
                        } 
                    },
                    animation: {
                        'blob': 'blob 15s infinite ease-in-out alternate',
                        'float': 'float 6s ease-in-out infinite',
                        'float-slow': 'float 8s ease-in-out 2s infinite',
                        'shimmer': 'shimmer 2.5s linear infinite',
                        'spin-slow': 'spin 12s linear infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(50px, -70px) scale(1.15)' },
                            '66%': { transform: 'translate(-40px, 40px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
                            '50%': { transform: 'translateY(-25px) rotate(3deg)' },
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --glass-light: rgba(255, 255, 255, 0.85);
            --glass-dark: rgba(15, 23, 42, 0.8);
            --nga-orange: #FF6600;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 80px;
            position: relative;
        }

        .glass {
            background: var(--glass-light);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 102, 0, 0.1);
        }
        .dark .glass {
            background: var(--glass-dark);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .text-gradient {
            background: linear-gradient(135deg, #FF6600 0%, #fb923c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 102, 0, 0.1) 1.5px, transparent 1.5px);
            background-size: 40px 40px;
            z-index: 0;
            mask-image: radial-gradient(circle at center, black, transparent 90%);
        }

        .btn-shimmer {
            background: linear-gradient(90deg, #FF6600, #ff8c40, #FF6600);
            background-size: 200% auto;
            transition: 0.5s;
        }
        .btn-shimmer:hover {
            background-position: right center;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(255, 102, 0, 0.4);
        }

        .card-perspective {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 40px;
        }
        .card-perspective:hover {
            transform: translateY(-15px) rotateX(5deg);
            border-color: var(--nga-orange);
            box-shadow: 0 40px 80px -20px rgba(255, 102, 0, 0.15);
        }

        #canvas-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .stat-card {
            background: linear-gradient(to bottom right, rgba(255, 102, 0, 0.05), transparent);
        }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-800 dark:bg-[#020617] dark:text-slate-200 overflow-x-hidden">
    
    <canvas id="canvas-bg"></canvas>

    <nav class="fixed w-full top-0 z-50 transition-all duration-500" id="navbar">
        <div class="glass border-b border-slate-200/50 dark:border-slate-800/50">
            <div class="max-w-[1400px] mx-auto px-8 h-20 flex items-center justify-between">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-lg border border-slate-100 dark:border-slate-700 group-hover:rotate-[15deg] transition-transform">
                        <i class='bx bx-book-reader text-3xl text-nga-orange'></i>
                    </div>
                    <span class="text-2xl font-black tracking-tighter text-slate-900 dark:text-white">
                        NGA <span class="text-slate-400 font-medium">Library</span>
                    </span>
                </a>
                
                <div class="hidden lg:flex items-center gap-12">
                    <a href="#" class="font-bold text-sm uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:text-nga-orange transition-colors">Home</a>
                    <a href="#stats" class="font-bold text-sm uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:text-nga-orange transition-colors">Impact</a>
                    <a href="#categories" class="font-bold text-sm uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:text-nga-orange transition-colors">Library</a>
                    
                    <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:scale-110 transition-all">
                        <i class='bx bxs-sun text-xl hidden dark:block text-amber-400'></i>
                        <i class='bx bxs-moon text-xl block dark:hidden text-slate-600'></i>
                    </button>

                    <a href="login.php" class="btn-shimmer px-8 py-3.5 text-white font-black text-xs rounded-2xl uppercase tracking-widest shadow-xl">
                        Student Access
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section overflow-hidden">
        <div class="bg-grid"></div>
        <div class="absolute top-0 left-0 w-full h-full pointer-events-none -z-10">
            <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-orange-500/10 rounded-full blur-[120px] animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px] animate-blob" style="animation-delay: -5s;"></div>
        </div>

        <div class="max-w-[1400px] mx-auto px-8 w-full relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-20 items-center">
                
                <div class="lg:col-span-7 text-center lg:text-left" data-aos="fade-right">
                    <div class="inline-flex items-center gap-3 px-6 py-2.5 rounded-full glass mb-10 shadow-sm border border-orange-500/20">
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-nga-orange"></span>
                        </span>
                        <span class="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-[0.3em]">Next-Gen Resource Hub</span>
                    </div>
                    
                    <h1 class="text-6xl md:text-8xl lg:text-[100px] font-black tracking-tighter mb-10 leading-[0.85] text-slate-900 dark:text-white">
                        Access Your <br>
                        <span class="text-gradient">Digital Legacy</span>
                    </h1>
                    
                    <p class="text-xl md:text-2xl text-slate-500 dark:text-slate-400 mb-14 font-medium leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Step into a smarter library experience. Reserve books, track history, and explore digital e-resources tailored for NGA excellence.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-6">
                        <a href="#categories" class="w-full sm:w-auto px-12 py-6 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-3xl shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-3">
                            <i class='bx bx-compass text-2xl'></i> Explore Catalog
                        </a>
                        <a href="login.php" class="w-full sm:w-auto px-12 py-6 glass border-2 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white font-black rounded-3xl hover:border-nga-orange hover:text-nga-orange transition-all flex items-center justify-center gap-3">
                            <i class='bx bx-lock-alt text-2xl'></i> Student Login
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5 relative hidden lg:flex items-center justify-center" data-aos="zoom-in" data-aos-delay="200">
                    <div class="relative w-full max-w-md">
                        <div class="absolute -top-16 -right-16 w-32 h-32 bg-nga-orange/20 rounded-full blur-3xl animate-pulse"></div>
                        
                        <div class="glass rounded-[60px] shadow-[0_50px_100px_-20px_rgba(255,102,0,0.2)] p-12 transform hover:rotate-3 transition-transform duration-700 border-white/40">
                            <div class="flex items-center gap-8 mb-12">
                                <div class="w-28 h-36 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 rounded-[30px] flex items-center justify-center shadow-inner relative overflow-hidden group">
                                    <i class='bx bxs-book-content text-6xl text-nga-orange group-hover:scale-110 transition-transform'></i>
                                </div>
                                <div>
                                    <span class="bg-orange-100 dark:bg-orange-900/40 text-nga-orange text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-[0.2em]">Live Tracking</span>
                                    <h3 class="font-black text-slate-900 dark:text-white text-3xl mt-4 leading-tight">Modern Web Architectures</h3>
                                    <p class="text-sm font-bold text-slate-400 mt-2">Engineering Dept.</p>
                                </div>
                            </div>
                            
                            <div class="space-y-6 mb-12">
                                <div class="flex justify-between items-end mb-2">
                                    <span class="text-[11px] font-black uppercase text-slate-500">Popularity</span>
                                    <span class="text-nga-orange font-black">94%</span>
                                </div>
                                <div class="h-3.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-nga-orange w-[94%] rounded-full shadow-[0_0_15px_rgba(255,102,0,0.5)]"></div>
                                </div>
                            </div>

                            <button class="w-full py-6 bg-nga-orange text-white font-black rounded-3xl shadow-xl shadow-orange-500/30 hover:brightness-110 transition-all flex items-center justify-center gap-3">
                                <i class='bx bx-bookmark-heart text-2xl'></i> Quick Reserve
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trending Books Section -->
    <?php if (count($trending_books) > 0): ?>
    <section id="trending" class="py-24 relative z-10">
        <div class="max-w-[1400px] mx-auto px-8">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8" data-aos="fade-up">
                <div class="max-w-2xl">
                    <span class="text-nga-orange font-black uppercase tracking-widest text-xs">Community Favorites</span>
                    <h2 class="text-5xl font-black text-slate-900 dark:text-white mt-4">Trending <span class="text-gradient">Resources</span></h2>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($trending_books as $t_book): ?>
                    <div class="group card-perspective glass p-8 relative overflow-hidden flex flex-col h-full" data-aos="fade-up">
                        <div class="absolute top-4 right-4 bg-orange-500/10 text-nga-orange text-[10px] font-black px-3 py-1 rounded-full uppercase">
                            🔥 Popular
                        </div>
                        
                        <div class="w-16 h-20 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 rounded-xl flex items-center justify-center shadow-inner mb-6 group-hover:scale-110 transition-transform">
                            <i class='bx bxs-book text-3xl text-nga-orange'></i>
                        </div>

                        <div class="flex-1">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 leading-tight"><?php echo htmlspecialchars($t_book['title']); ?></h3>
                            <p class="text-sm font-bold text-slate-500 mb-6"><?php echo htmlspecialchars($t_book['author']); ?></p>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-slate-200/50 dark:border-slate-800/50">
                            <div class="flex items-center gap-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">Views</span>
                                    <span class="font-black text-slate-900 dark:text-white"><?php echo $t_book['view_count']; ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">Borrows</span>
                                    <span class="font-black text-slate-900 dark:text-white"><?php echo $t_book['borrow_count']; ?></span>
                                </div>
                            </div>
                            <a href="book_details.php?id=<?php echo $t_book['id']; ?>" class="w-10 h-10 bg-nga-orange text-white rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-lg shadow-orange-500/20">
                                <i class='bx bx-right-arrow-alt text-xl'></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="stats" class="py-24 relative z-10">
        <div class="max-w-[1400px] mx-auto px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="stat-card p-10 rounded-[40px] text-center glass border-none" data-aos="fade-up" data-aos-delay="100">
                    <h4 class="text-5xl font-black text-nga-orange mb-2">15K+</h4>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Total Volumes</p>
                </div>
                <div class="stat-card p-10 rounded-[40px] text-center glass border-none" data-aos="fade-up" data-aos-delay="200">
                    <h4 class="text-5xl font-black text-nga-orange mb-2">2.5K</h4>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Active Students</p>
                </div>
                <div class="stat-card p-10 rounded-[40px] text-center glass border-none" data-aos="fade-up" data-aos-delay="300">
                    <h4 class="text-5xl font-black text-nga-orange mb-2">500+</h4>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">E-Resources</p>
                </div>
                <div class="stat-card p-10 rounded-[40px] text-center glass border-none" data-aos="fade-up" data-aos-delay="400">
                    <h4 class="text-5xl font-black text-nga-orange mb-2">24/7</h4>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-500">Portal Access</p>
                </div>
            </div>
        </div>
    </section>

    <section id="categories" class="py-32 relative z-10 bg-slate-100/50 dark:bg-slate-900/20">
        <div class="max-w-[1400px] mx-auto px-8">
            <div class="flex flex-col md:flex-row justify-between items-end mb-20 gap-8" data-aos="fade-up">
                <div class="max-w-2xl">
                    <h2 class="text-5xl md:text-6xl font-black text-slate-900 dark:text-white tracking-tighter mb-6 leading-tight">Explore Our <br> <span class="text-nga-orange">Diverse Catalog</span></h2>
                    <p class="text-slate-500 font-medium text-lg leading-relaxed">Curated academic collections specifically chosen to empower your learning journey at New Generation Academy.</p>
                </div>
                <a href="login.php" class="px-10 py-5 bg-nga-orange text-white font-black rounded-2xl shadow-lg hover:translate-x-2 transition-transform flex items-center gap-3">
                    View Full Catalog <i class='bx bx-right-arrow-alt text-2xl'></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="group card-perspective glass p-10 relative overflow-hidden" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-16 h-16 bg-orange-500/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class='bx bx-code-alt text-4xl text-nga-orange'></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4">Engineering</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed">From basic coding to advanced robotics and structural design.</p>
                    <span class="text-[10px] font-black uppercase tracking-widest text-nga-orange">1,240 Titles</span>
                </div>
                <div class="group card-perspective glass p-10 relative overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-16 h-16 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class='bx bx-line-chart text-4xl text-blue-500'></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4">Business</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed">Entrepreneurship, marketing, and global financial systems.</p>
                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-500">890 Titles</span>
                </div>
                <div class="group card-perspective glass p-10 relative overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-16 h-16 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class='bx bx-palette text-4xl text-purple-500'></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4">Liberal Arts</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed">Philosophy, social sciences, and creative communications.</p>
                    <span class="text-[10px] font-black uppercase tracking-widest text-purple-500">2,100 Titles</span>
                </div>
                <div class="group card-perspective glass p-10 relative overflow-hidden" data-aos="fade-up" data-aos-delay="400">
                    <div class="w-16 h-16 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class='bx bx-dna text-4xl text-emerald-500'></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4">Sciences</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed">Biological research, chemistry, and environmental physics.</p>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-500">1,560 Titles</span>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-32 relative z-10">
        <div class="max-w-[1400px] mx-auto px-8">
            <div class="text-center mb-24" data-aos="fade-up">
                <span class="text-nga-orange font-black uppercase tracking-widest text-xs">Seamless Process</span>
                <h2 class="text-5xl font-black mt-4">Simple. Fast. Digital.</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-16 relative">
                <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-slate-200 dark:bg-slate-800 -translate-y-1/2 z-0"></div>
                
                <div class="relative z-10 text-center flex flex-col items-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-20 h-20 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full flex items-center justify-center text-2xl font-black mb-8 border-8 border-slate-50 dark:border-[#020617] group hover:bg-nga-orange transition-colors">01</div>
                    <h3 class="text-xl font-black mb-4">Login to Portal</h3>
                    <p class="text-slate-500 text-sm font-medium">Use your NGA student credentials to gain full access.</p>
                </div>
                <div class="relative z-10 text-center flex flex-col items-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-20 h-20 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full flex items-center justify-center text-2xl font-black mb-8 border-8 border-slate-50 dark:border-[#020617] group hover:bg-nga-orange transition-colors">02</div>
                    <h3 class="text-xl font-black mb-4">Select Resources</h3>
                    <p class="text-slate-500 text-sm font-medium">Browse physical books or download digital PDFs instantly.</p>
                </div>
                <div class="relative z-10 text-center flex flex-col items-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-20 h-20 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full flex items-center justify-center text-2xl font-black mb-8 border-8 border-slate-50 dark:border-[#020617] group hover:bg-nga-orange transition-colors">03</div>
                    <h3 class="text-xl font-black mb-4">Study & Excel</h3>
                    <p class="text-slate-500 text-sm font-medium">Track your due dates and manage returns through your dashboard.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-20 relative z-10 glass border-t-0 bg-white/30 dark:bg-slate-950/30">
        <div class="max-w-[1400px] mx-auto px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                <div class="lg:col-span-2">
                    <a href="#" class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-nga-orange rounded-xl flex items-center justify-center text-white">
                            <i class='bx bx-book-reader text-2xl'></i>
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">NGA Library</span>
                    </a>
                    <p class="text-slate-500 font-medium max-w-sm mb-10 leading-relaxed">Empowering the next generation of leaders through seamless access to world-class academic knowledge.</p>
                    <div class="flex gap-4">
                        <a href="#" class="w-12 h-12 rounded-full glass flex items-center justify-center text-2xl hover:bg-nga-orange hover:text-white transition-all"><i class='bx bxl-facebook'></i></a>
                        <a href="#" class="w-12 h-12 rounded-full glass flex items-center justify-center text-2xl hover:bg-nga-orange hover:text-white transition-all"><i class='bx bxl-twitter'></i></a>
                        <a href="#" class="w-12 h-12 rounded-full glass flex items-center justify-center text-2xl hover:bg-nga-orange hover:text-white transition-all"><i class='bx bxl-linkedin'></i></a>
                    </div>
                </div>
                <div>
                    <h5 class="font-black text-sm uppercase tracking-widest mb-8">Portal Links</h5>
                    <ul class="space-y-4 font-bold text-slate-500 dark:text-slate-400">
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Digital Catalog</a></li>
                        <li><a href="#" class="hover:text-nga-orange transition-colors">E-Resource Hub</a></li>
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Student Login</a></li>
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Research Guides</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-black text-sm uppercase tracking-widest mb-8">Support</h5>
                    <ul class="space-y-4 font-bold text-slate-500 dark:text-slate-400">
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Library Policy</a></li>
                        <li><a href="#" class="hover:text-nga-orange transition-colors">Contact Librarian</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="pt-10 border-t border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">© <?php echo date('Y'); ?> New Generation Academy. Built for Excellence.</p>
                <div class="flex gap-8 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    <a href="#" class="hover:text-nga-orange">Privacy Policy</a>
                    <a href="#" class="hover:text-nga-orange">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // AOS Init
            AOS.init({ duration: 1000, once: true, easing: 'ease-out-expo' });

            // Theme Toggle
            const html = document.documentElement;
            const toggle = document.getElementById('theme-toggle');
            if(localStorage.getItem('theme') === 'dark') html.classList.add('dark');

            toggle.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
            });

            // Navbar Scroll Effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if(window.scrollY > 50) {
                    navbar.querySelector('.glass').style.margin = '10px 20px';
                    navbar.querySelector('.glass').style.borderRadius = '30px';
                    navbar.querySelector('.glass').style.boxShadow = '0 20px 40px rgba(0,0,0,0.1)';
                } else {
                    navbar.querySelector('.glass').style.margin = '0';
                    navbar.querySelector('.glass').style.borderRadius = '0';
                    navbar.querySelector('.glass').style.boxShadow = 'none';
                }
            });

            // Canvas Background Particles
            const canvas = document.getElementById('canvas-bg');
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
                    this.size = Math.random() * 15 + 5;
                    this.speedX = Math.random() * 0.5 - 0.25;
                    this.speedY = Math.random() * 0.5 - 0.25;
                    this.opacity = Math.random() * 0.1;
                }
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    if(this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                    if(this.y < 0 || this.y > canvas.height) this.speedY *= -1;
                }
                draw() {
                    ctx.fillStyle = `rgba(255, 102, 0, ${this.opacity})`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            for(let i=0; i<20; i++) particles.push(new Particle());

            function animate() {
                ctx.clearRect(0,0,canvas.width, canvas.height);
                particles.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animate);
            }
            animate();
        });
    </script>
</body>
</html>