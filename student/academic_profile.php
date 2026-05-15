<?php
session_start();
require '../config/db.php';
require '../config/academic_integration.php';

// Security Check: Only Students allowed here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle profile updates
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        academic_department = ?,
                        academic_level = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['academic_department'] ?? null,
                    $_POST['academic_level'] ?? null,
                    $user_id
                ]);
                $message = 'Profile updated successfully!';
                $message_type = 'success';
                break;
                
            case 'sync_now':
                $integration = getAcademicIntegration($pdo);
                
                // Get user's academic ID
                $userQuery = $pdo->prepare("SELECT academic_id FROM users WHERE id = ?");
                $userQuery->execute([$user_id]);
                $user = $userQuery->fetch();
                
                if ($user && $user['academic_id']) {
                    if ($integration->syncUser($user['academic_id'])) {
                        $message = 'Profile synced with academic system!';
                        $message_type = 'success';
                    } else {
                        $message = 'Sync failed. Please try again later.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'No academic ID found. Please login via academic system first.';
                    $message_type = 'error';
                }
                break;
                
            case 'disconnect_academic':
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        academic_id = NULL,
                        sync_status = 'manual',
                        academic_role = NULL,
                        academic_department = NULL,
                        academic_level = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$user_id]);
                
                // Clear academic sessions
                $stmt = $pdo->prepare("DELETE FROM academic_sessions WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $message = 'Disconnected from academic system. You can still use the library with your email/password.';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get user's current information
$user_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
} catch (PDOException $e) {}

// Get integration statistics
$integration_stats = [];
if (!empty($user_info['academic_id'])) {
    try {
        $integration = getAcademicIntegration($pdo);
        $integration_stats = $integration->getIntegrationStats();
    } catch (PDOException $e) {}
}

$page_title = "Academic Profile | NGA Student";
$header_title = "My Academic Profile";
require '../components/header.php'; 
?>

<!-- Message Display -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-xl border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
    <div class="flex items-center gap-2">
        <i class='bx <?php echo $message_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?> text-xl'></i>
        <span class="font-semibold"><?php echo $message; ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Academic Connection Status -->
<div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-2xl flex items-center justify-center">
                <?php if (!empty($user_info['academic_id'])): ?>
                    <i class='bx bx-link text-white text-2xl'></i>
                <?php else: ?>
                    <i class='bx bx-unlink text-white text-2xl'></i>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-purple-900">
                    <?php if (!empty($user_info['academic_id'])): ?>
                        Connected to Academic System
                    <?php else: ?>
                        Not Connected to Academic System
                    <?php endif; ?>
                </h2>
                <p class="text-purple-700 mt-1">
                    <?php if (!empty($user_info['academic_id'])): ?>
                        Academic ID: <code class="bg-purple-100 px-2 py-1 rounded"><?php echo htmlspecialchars($user_info['academic_id']); ?></code>
                    <?php else: ?>
                        Login via academic system to connect your accounts
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <?php if (!empty($user_info['academic_id'])): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="sync_now">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <i class='bx bx-sync'></i>
                        Sync Now
                    </button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect from the academic system? You can still login with email/password.')">
                    <input type="hidden" name="action" value="disconnect_academic">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-colors flex items-center gap-2">
                        <i class='bx bx-unlink'></i>
                        Disconnect
                    </button>
                </form>
            <?php else: ?>
                <a href="login_enhanced.php" class="px-4 py-2 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors flex items-center gap-2">
                    <i class='bx bx-link'></i>
                    Connect Now
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Academic Information -->
    <div class="lg:col-span-2">
        <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                <i class='bx bx-user-voice text-purple-500'></i>
                Academic Information
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" disabled 
                               class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-500">
                        <p class="text-xs text-slate-500 mt-1">Cannot be changed here</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled 
                               class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-500">
                        <p class="text-xs text-slate-500 mt-1">Cannot be changed here</p>
                    </div>
                </div>

                <!-- Academic Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Academic Department</label>
                        <select name="academic_department" 
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">Select Department</option>
                            <option value="Computer Science" <?php echo ($user_info['academic_department'] === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Business" <?php echo ($user_info['academic_department'] === 'Business') ? 'selected' : ''; ?>>Business</option>
                            <option value="Engineering" <?php echo ($user_info['academic_department'] === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Arts" <?php echo ($user_info['academic_department'] === 'Arts') ? 'selected' : ''; ?>>Arts</option>
                            <option value="Science" <?php echo ($user_info['academic_department'] === 'Science') ? 'selected' : ''; ?>>Science</option>
                            <option value="Medicine" <?php echo ($user_info['academic_department'] === 'Medicine') ? 'selected' : ''; ?>>Medicine</option>
                            <option value="Education" <?php echo ($user_info['academic_department'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                            <option value="Law" <?php echo ($user_info['academic_department'] === 'Law') ? 'selected' : ''; ?>>Law</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Used for book recommendations</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Academic Level</label>
                        <select name="academic_level" 
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white">
                            <option value="">Select Level</option>
                            <option value="Year 1" <?php echo ($user_info['academic_level'] === 'Year 1') ? 'selected' : ''; ?>>Year 1</option>
                            <option value="Year 2" <?php echo ($user_info['academic_level'] === 'Year 2') ? 'selected' : ''; ?>>Year 2</option>
                            <option value="Year 3" <?php echo ($user_info['academic_level'] === 'Year 3') ? 'selected' : ''; ?>>Year 3</option>
                            <option value="Year 4" <?php echo ($user_info['academic_level'] === 'Year 4') ? 'selected' : ''; ?>>Year 4</option>
                            <option value="Graduate" <?php echo ($user_info['academic_level'] === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                            <option value="Postgraduate" <?php echo ($user_info['academic_level'] === 'Postgraduate') ? 'selected' : ''; ?>>Postgraduate</option>
                            <option value="PhD" <?php echo ($user_info['academic_level'] === 'PhD') ? 'selected' : ''; ?>>PhD</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Helps with appropriate book suggestions</p>
                    </div>
                </div>

                <!-- Academic Role (if connected) -->
                <?php if (!empty($user_info['academic_role'])): ?>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Academic Role</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_info['academic_role']); ?>" disabled 
                           class="w-full px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-500">
                    <p class="text-xs text-slate-500 mt-1">Synced from academic system</p>
                </div>
                <?php endif; ?>

                <div class="flex justify-end gap-3 pt-4">
                    <a href="dashboard_enhanced.php" class="px-6 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors flex items-center gap-2">
                        <i class='bx bx-save'></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sync Status & Statistics -->
    <div class="space-y-6">
        <!-- Sync Status Card -->
        <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class='bx bx-sync text-blue-500'></i>
                Sync Status
            </h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Status</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                        <?php 
                        $status = $user_info['sync_status'] ?? 'unknown';
                        echo match($status) {
                            'synced' => 'bg-green-100 text-green-700',
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'error' => 'bg-red-100 text-red-700',
                            'manual' => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700'
                        };
                        ?>">
                        <?php echo ucfirst($status); ?>
                    </span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Last Sync</span>
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">
                        <?php echo $user_info['last_sync'] ? date('M j, Y H:i', strtotime($user_info['last_sync'])) : 'Never'; ?>
                    </span>
                </div>
                
                <?php if (!empty($user_info['sync_error_message'])): ?>
                <div class="p-3 bg-red-50 dark:bg-red-500/10 rounded-xl">
                    <p class="text-xs text-red-700 dark:text-red-400 font-semibold">Last Error:</p>
                    <p class="text-sm text-red-600 dark:text-red-300"><?php echo htmlspecialchars($user_info['sync_error_message']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Benefits Card -->
        <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class='bx bx-gift text-orange-500'></i>
                Integration Benefits
            </h3>
            
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class='bx bx-bookmark text-blue-600 dark:text-blue-400 text-sm'></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Smart Recommendations</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Books based on your department</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class='bx bx-shield-check text-purple-600 dark:text-purple-400 text-sm'></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Single Sign-On</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">One login for both systems</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class='bx bx-sync text-green-600 dark:text-green-400 text-sm'></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Auto Sync</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Profile stays updated</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../components/footer.php'; ?>
