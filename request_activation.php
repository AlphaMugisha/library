<?php
session_start();
require 'config/db.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_link'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT full_name, activation_token, is_activated FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error_msg = "We couldn't find an account with that email.";
    } elseif ($user['is_activated'] == 1) {
        $error_msg = "This account is already active. Please log in.";
    } else {
        $token = $user['activation_token'];
        $link = "activate.php?token=" . $token;
        
        // In Dev Mode, we show the link. In Production, you'd email this.
        $success_msg = "Account found! <br><br> <a href='$link' class='bg-white text-[#FF6600] px-4 py-2 rounded-lg font-black inline-block mt-2 shadow-sm'>Click here to set your password</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Activation | NGA Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#020617] min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md glass p-8 rounded-[32px] shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-[#FF6600] rounded-2xl flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg shadow-orange-500/20">
                <i class='bx bx-key'></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">Activate Account</h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Enter your email to receive your activation link</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-red-500 text-white p-4 rounded-xl mb-6 font-bold text-sm flex items-center gap-2">
                <i class='bx bxs-error-circle'></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="bg-green-600 text-white p-6 rounded-xl mb-6 font-bold text-sm shadow-inner">
                <div class="flex items-center gap-2 mb-2"><i class='bx bxs-check-circle'></i> <span>Success!</span></div>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="relative">
                <i class='bx bx-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl'></i>
                <input type="email" name="email" placeholder="Your Registered Email" required 
                class="w-full bg-slate-100 dark:bg-slate-800/50 border-none rounded-2xl py-4 pl-12 pr-4 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600] transition-all">
            </div>
            <button type="submit" name="request_link" class="w-full py-4 bg-[#FF6600] text-white font-black rounded-2xl shadow-lg shadow-orange-500/30 hover:scale-[1.02] active:scale-95 transition-all">
                Get Activation Link
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="login.php" class="text-slate-500 dark:text-slate-400 font-bold text-sm hover:text-[#FF6600]">
                <i class='bx bx-arrow-back'></i> Back to Login
            </a>
        </div>
    </div>

</body>
</html>