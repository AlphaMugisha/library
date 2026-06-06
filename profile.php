<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_enhanced.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    
    try {
        // Handle Profile Picture Upload
        $profile_pic = $user['profile_picture'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileSize = $_FILES['profile_pic']['size'];
            $fileType = $_FILES['profile_pic']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = './uploads/profile_pics/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Delete old pic if not default
                    if ($user['profile_picture'] !== 'default_avatar.png' && file_exists($uploadFileDir . $user['profile_picture'])) {
                        unlink($uploadFileDir . $user['profile_picture']);
                    }
                    $profile_pic = $newFileName;
                }
            }
        }

        // Update basic info
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $hashed_password, $profile_pic, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $profile_pic, $user_id]);
        }

        $_SESSION['name'] = $full_name;
        $message = "Profile updated successfully!";
        $message_type = "success";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
    } catch (PDOException $e) {
        $message = "Update failed: " . $e->getMessage();
        $message_type = "error";
    }
}

$page_title = "Edit Profile | NGA Library";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .profile-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 102, 0, 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8 flex items-center justify-between">
            <a href="<?php echo $user['role']; ?>/dashboard.php" class="flex items-center gap-2 text-slate-500 hover:text-orange-600 transition-colors font-bold">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
            <h1 class="text-2xl font-black text-slate-900">Manage Profile</h1>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl font-bold flex items-center gap-3 <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <i class='bx <?php echo $message_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?> text-xl'></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card rounded-[32px] p-8 shadow-xl">
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="flex flex-col items-center mb-10">
                    <div class="relative group">
                        <img src="uploads/profile_pics/<?php echo $user['profile_picture']; ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=FF6600&color=fff'"
                             class="w-32 h-32 rounded-3xl object-cover border-4 border-white shadow-lg mb-4">
                        <label class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-3xl opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                            <i class='bx bx-camera text-white text-3xl'></i>
                            <input type="file" name="profile_pic" class="hidden" accept="image/*" onchange="this.form.submit()">
                        </label>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Click photo to change</p>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                               class="w-full p-4 rounded-2xl border-2 border-slate-100 focus:border-orange-500 outline-none transition-all font-semibold" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="w-full p-4 rounded-2xl border-2 border-slate-100 focus:border-orange-500 outline-none transition-all font-semibold" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" placeholder="••••••••" 
                               class="w-full p-4 rounded-2xl border-2 border-slate-100 focus:border-orange-500 outline-none transition-all font-semibold">
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full p-4 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-orange-200 transition-all active:scale-95">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
