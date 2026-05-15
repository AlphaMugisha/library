<?php
session_start();
require 'config/db.php';

$error_msg = '';
$success_msg = '';
$valid_token = false;
$user_email = '';

// 1. Verify the Token from the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE activation_token = ? AND is_activated = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $valid_token = true;
        $user_email = $user['email'];
        $user_name = explode(' ', $user['full_name'])[0]; // Get first name
    } else {
        $error_msg = "This activation link is invalid or has already been used.";
    }
} else {
    $error_msg = "No activation token provided.";
}

// 2. Handle Password Setup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activate_account']) && $valid_token) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Activate the account and clear the token
        $update = $pdo->prepare("UPDATE users SET password = ?, is_activated = 1, activation_token = NULL WHERE activation_token = ?");
        $update->execute([$hashed_password, $token]);
        
        $valid_token = false; // Hide the form
        $success_msg = "Account successfully activated! You can now log in.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Account | NGA Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .left-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        .form-control { width: 100%; padding: 16px 16px 16px 50px; background: #f1f5f9; border: 2px solid transparent; border-radius: 16px; font-weight: 600; outline: none; transition: all 0.3s ease; }
        .form-control:focus { border-color: #FF6600; box-shadow: 0 8px 25px -5px rgba(255, 102, 0, 0.15); }
        .input-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 20px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 overflow-x-hidden min-h-screen flex flex-col lg:flex-row">

    <div class="hidden lg:flex lg:w-1/2 left-gradient relative flex-col justify-between p-12 overflow-hidden">
        <div class="relative z-10">
            <div class="flex items-center gap-3 text-white text-2xl font-black w-max">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg border-2 border-[#FF6600]">
                    <i class='bx bx-book-reader text-2xl text-[#FF6600]'></i>
                </div>
                <span>NGA <span class="text-[#FF6600]">Library</span></span>
            </div>
        </div>
        <div class="relative z-10 -mt-20">
            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-400 rounded-3xl flex items-center justify-center mb-8 shadow-2xl shadow-green-500/30">
                <i class='bx bx-check-shield text-white text-4xl'></i>
            </div>
            <h1 class="text-5xl font-black text-white mb-6 leading-tight">Secure your <br><span class="text-green-400">Workspace</span></h1>
            <p class="text-slate-400 text-lg font-medium max-w-md">Activate your account by setting a private, secure password to access the Academic Bridge.</p>
        </div>
        <div></div>
    </div>

    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-8 bg-white/50 backdrop-blur-sm">
        <div class="w-full max-w-[400px]">
            
            <?php if ($error_msg): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl flex items-center gap-2 font-bold mb-6">
                    <i class='bx bxs-error-circle text-xl'></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="text-center py-10">
                    <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">
                        <i class='bx bx-check'></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 mb-2">You're all set!</h2>
                    <p class="text-slate-500 mb-8"><?php echo $success_msg; ?></p>
                    <a href="login.php" class="px-8 py-3 bg-slate-900 text-white font-bold rounded-xl shadow-lg hover:bg-slate-800 transition-colors">Go to Login</a>
                </div>
            <?php elseif ($valid_token): ?>
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-900 mb-2">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p class="text-slate-500 font-medium">Create a password for <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
                </div>
                
                <form method="POST">
                    <div class="mb-4 relative">
                        <label class="block text-xs font-bold mb-2 text-slate-600 uppercase tracking-widest">New Password</label>
                        <div class="relative">
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                            <i class='bx bx-lock-alt input-icon'></i>
                        </div>
                    </div>
                    <div class="mb-8 relative">
                        <label class="block text-xs font-bold mb-2 text-slate-600 uppercase tracking-widest">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
                            <i class='bx bx-check-shield input-icon'></i>
                        </div>
                    </div>
                    <button type="submit" name="activate_account" class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-green-500/30 hover:-translate-y-0.5 transition-transform flex items-center justify-center gap-2">
                        <i class='bx bx-key text-xl'></i> Activate Account
                    </button>
                </form>
            <?php endif; ?>
            
        </div>
    </div>

</body>
</html>