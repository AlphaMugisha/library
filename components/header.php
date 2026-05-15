<?php
// components/header.php

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_title) ? $page_title : "NGA Library";
$header_title = isset($header_title) ? $header_title : "Dashboard";
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class', 
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { nga: { brand: '#2563eb', navy: '#0f172a' } },
                    animation: { 'blob': 'blob 20s infinite ease-in-out alternate' },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.4);
        }

        .dark {
            --primary: #3b82f6;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body { transition: background-color 0.4s ease, color 0.4s ease; }
        .glass { background: var(--glass-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--glass-border); }
        
        .card-hover { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -12px rgba(37, 99, 235, 0.15); }

        .modal-overlay { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-content { transform: scale(0.95) translateY(20px); opacity: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-overlay.active .modal-content { transform: scale(1) translateY(0); opacity: 1; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-800 dark:bg-[#020617] dark:text-slate-200 selection:bg-nga-brand selection:text-white flex h-screen overflow-hidden">

    <div class="fixed inset-0 w-full h-full pointer-events-none overflow-hidden -z-10" style="will-change: transform;">
        <div class="absolute top-0 left-1/4 w-[300px] h-[300px] bg-blue-400/10 dark:bg-blue-600/5 rounded-full filter blur-[80px] animate-blob"></div>
        <div class="absolute bottom-0 right-1/4 w-[300px] h-[300px] bg-indigo-400/10 dark:bg-indigo-600/5 rounded-full filter blur-[80px] animate-blob" style="animation-delay: -7s;"></div>
    </div>

    <aside class="w-72 h-full glass border-r border-slate-200 dark:border-slate-800/80 flex flex-col z-20">
        <div class="h-20 flex items-center px-6 border-b border-slate-200/50 dark:border-slate-800/50">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1.5 shadow-md border border-slate-100 dark:border-slate-700 mr-3">
                <i class='bx bx-book-reader text-2xl text-blue-600'></i>
            </div>
            <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white">NGA <span class="text-blue-600">Library</span></span>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'librarian'): ?>
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Librarian Menu</p>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bxs-dashboard text-xl'></i> Overview
                </a>
                <a href="books.php" class="<?php echo ($current_page == 'books.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bx-book text-xl'></i> Book Catalog
                </a>
                <a href="transactions.php" class="<?php echo ($current_page == 'transactions.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bx-transfer-alt text-xl'></i> Issue & Return
                </a>
                <a href="members.php" class="<?php echo ($current_page == 'members.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bx-group text-xl'></i> Member Directory
                </a>
            
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Student Menu</p>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bxs-book-reader text-xl'></i> My Reading List
                </a>
                <a href="catalog.php" class="<?php echo ($current_page == 'catalog.php') ? 'bg-gradient-to-r from-blue-600/10 to-transparent text-blue-600 border-l-2 border-blue-600' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bx-search-alt text-xl'></i> Browse Library
                </a>

            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                <p class="px-4 text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Educator Menu</p>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'bg-gradient-to-r from-blue-500/10 to-transparent text-blue-500 border-l-2 border-blue-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bxs-briefcase-alt-2 text-xl'></i> My Desk
                </a>
                <a href="catalog.php" class="<?php echo ($current_page == 'catalog.php') ? 'bg-gradient-to-r from-blue-500/10 to-transparent text-blue-500 border-l-2 border-blue-500' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'; ?> flex items-center gap-3 px-4 py-3 rounded-r-xl font-bold transition-all">
                    <i class='bx bx-search-alt text-xl'></i> Browse Library
                </a>
            <?php endif; ?>

        </nav>
        
        <div class="p-4 border-t border-slate-200/50 dark:border-slate-800/50">
            <a href="../login.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl font-semibold transition-all">
                <i class='bx bx-log-out text-xl'></i> Log Out
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10">
        <header class="h-20 glass border-b border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between px-8">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?php echo $header_title; ?></h1>
            
            <div class="flex items-center gap-6">
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center justify-center">
                    <i class='bx bxs-sun text-lg hidden dark:block'></i>
                    <i class='bx bxs-moon text-lg block dark:hidden'></i>
                </button>

                <div class="flex items-center gap-3 pl-6 border-l border-slate-200 dark:border-slate-700">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest'; ?></p>
                        <p class="text-xs text-slate-500 font-medium uppercase"><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'User'; ?></p>
                    </div>
                    <?php 
                        // Teacher blue, everyone else brand blue 
                        $avatar_color = (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') ? 'from-blue-500 to-blue-600 shadow-blue-500/20' : 'from-blue-600 to-indigo-500 shadow-blue-500/20'; 
                    ?>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br <?php echo $avatar_color; ?> flex items-center justify-center shadow-lg text-white font-bold">
                        <?php echo isset($_SESSION['name']) ? substr($_SESSION['name'], 0, 1) : 'U'; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto space-y-8">