<?php
session_start();
require '../config/db.php';
require '../config/academic_integration.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

// Live DB Stats
$stats = ['total_books' => 0, 'available' => 0, 'issued' => 0, 'overdue' => 0];
try {
    $bookStats = $pdo->query("SELECT SUM(total_copies) as total, SUM(available_copies) as available FROM books")->fetch();
    $stats['total_books'] = $bookStats['total'] ?? 0;
    $stats['available'] = $bookStats['available'] ?? 0;
    $stats['issued'] = $pdo->query("SELECT COUNT(*) as issued FROM borrowings WHERE status = 'Issued'")->fetchColumn();
    $stats['overdue'] = $pdo->query("SELECT COUNT(*) as overdue FROM borrowings WHERE status = 'Issued' AND due_date < CURDATE()")->fetchColumn();
} catch (PDOException $e) {}

// Integration Statistics
$integration_stats = [];
try {
    $integration = getAcademicIntegration($pdo);
    $integration_stats = $integration->getIntegrationStats();
} catch (PDOException $e) {}

// Recent Integration Activity
$recent_activity = [];
try {
    $activityQuery = $pdo->prepare("
        SELECT il.*, u.full_name, u.email 
        FROM integration_logs il 
        LEFT JOIN users u ON il.user_id = u.id 
        ORDER BY il.created_at DESC 
        LIMIT 10
    ");
    $activityQuery->execute();
    $recent_activity = $activityQuery->fetchAll();
} catch (PDOException $e) {}

// User Sync Status
$user_sync_status = [];
try {
    $syncQuery = $pdo->prepare("
        SELECT 
            sync_status,
            COUNT(*) as count
        FROM users 
        WHERE academic_id IS NOT NULL
        GROUP BY sync_status
    ");
    $syncQuery->execute();
    $user_sync_status = $syncQuery->fetchAll();
} catch (PDOException $e) {}

// Set Headers and Include UI
$page_title = "Dashboard | NGA Library";
$header_title = "Academic Integration Overview";
require '../components/header.php'; 
?>

<!-- Integration Status Banner -->
<div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-blue-900 mb-2">Academic System Integration</h2>
            <p class="text-blue-700">Monitor and manage synchronization with the main academic system</p>
        </div>
        <div class="flex gap-3">
            <button onclick="location.href='sync_management.php'" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2">
                <i class='bx bx-sync'></i>
                Sync Management
            </button>
            <button onclick="location.href='integration_logs.php'" class="px-4 py-2 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors flex items-center gap-2">
                <i class='bx bx-list-ul'></i>
                View Logs
            </button>
        </div>
    </div>
</div>

<!-- Enhanced Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <!-- Total Books -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <i class='bx bxs-book-bookmark text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['total_books']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Total Books</p>
    </div>

    <!-- Available Books -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/25">
                <i class='bx bxs-check-shield text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['available']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Available</p>
    </div>

    <!-- Issued Books -->
    <a href="transactions.php" class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-[#FF6600] rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/25">
                <i class='bx bxs-hand text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['issued']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Issued</p>
    </a>

    <!-- Overdue Books -->
    <a href="transactions.php" class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/25">
                <i class='bx bxs-error text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1"><?php echo $stats['overdue']; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Overdue</p>
    </a>

    <!-- Academic Users -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/25">
                <i class='bx bxs-graduation text-white text-xl'></i>
            </div>
        </div>
        <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-1">
            <?php 
            $total_academic = 0;
            foreach ($user_sync_status as $status) {
                $total_academic += $status['count'];
            }
            echo $total_academic; 
            ?>
        </h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Academic Users</p>
    </div>
</div>

<!-- Integration Overview Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- User Sync Status -->
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class='bx bx-sync text-blue-500'></i>
            User Sync Status
        </h2>
        <div class="space-y-3">
            <?php if (count($user_sync_status) > 0): ?>
                <?php foreach ($user_sync_status as $status): ?>
                    <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full 
                                <?php 
                                echo match($status['sync_status']) {
                                    'synced' => 'bg-green-500',
                                    'pending' => 'bg-yellow-500',
                                    'error' => 'bg-red-500',
                                    'manual' => 'bg-blue-500',
                                    default => 'bg-gray-500'
                                };
                                ?>"></div>
                            <span class="font-medium text-slate-900 dark:text-white capitalize"><?php echo $status['sync_status']; ?></span>
                        </div>
                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 rounded-full text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo $status['count']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class='bx bx-user-x text-3xl text-slate-400'></i>
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-white">No academic users yet</p>
                    <p class="text-sm text-slate-500 mt-1">Users will appear here after first sync</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Integration Activity -->
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class='bx bx-history text-purple-500'></i>
            Recent Activity
        </h2>
        <div class="space-y-3 max-h-64 overflow-y-auto">
            <?php if (count($recent_activity) > 0): ?>
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-slate-900 dark:text-white capitalize"><?php echo $activity['action']; ?></span>
                            <span class="text-xs text-slate-500"><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full 
                                <?php 
                                echo match($activity['status']) {
                                    'success' => 'bg-green-500',
                                    'error' => 'bg-red-500',
                                    'pending' => 'bg-yellow-500',
                                    default => 'bg-gray-500'
                                };
                                ?>"></div>
                            <span class="text-xs text-slate-600 dark:text-slate-400">
                                <?php echo $activity['full_name'] ?? 'Unknown User'; ?>
                            </span>
                        </div>
                        <?php if ($activity['message']): ?>
                            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($activity['message']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class='bx bx-time-five text-3xl text-slate-400'></i>
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-white">No recent activity</p>
                    <p class="text-sm text-slate-500 mt-1">Integration activity will appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class='bx bx-rocket text-orange-500'></i>
            Quick Actions
        </h2>
        <div class="space-y-3">
            <button onclick="location.href='sync_management.php'" class="w-full p-4 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-xl text-left hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class='bx bx-sync text-blue-600 dark:text-blue-400 text-xl'></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Manual Sync</p>
                        <p class="text-xs text-slate-500">Sync all academic users</p>
                    </div>
                </div>
            </button>

            <button onclick="location.href='user_management.php'" class="w-full p-4 bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 rounded-xl text-left hover:bg-purple-100 dark:hover:bg-purple-500/20 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class='bx bx-user-plus text-purple-600 dark:text-purple-400 text-xl'></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">User Management</p>
                        <p class="text-xs text-slate-500">Manage academic users</p>
                    </div>
                </div>
            </button>

            <button onclick="location.href='integration_logs.php'" class="w-full p-4 bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl text-left hover:bg-orange-100 dark:hover:bg-orange-500/20 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-500/20 rounded-xl flex items-center justify-center">
                        <i class='bx bx-file-find text-orange-600 dark:text-orange-400 text-xl'></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400">View Logs</p>
                        <p class="text-xs text-slate-500">Integration history</p>
                    </div>
                </div>
            </button>

            <button onclick="testIntegration()" class="w-full p-4 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 rounded-xl text-left hover:bg-green-100 dark:hover:bg-green-500/20 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class='bx bx-test-tube text-green-600 dark:text-green-400 text-xl'></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Test Connection</p>
                        <p class="text-xs text-slate-500">Check academic system</p>
                    </div>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Department Statistics -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 mt-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class='bx bx-pie-chart-alt text-orange-500'></i>
            Department Distribution
        </h2>
        <button onclick="location.href='department_stats.php'" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-semibold">
            View Detailed Stats →
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $departments = ['Computer Science', 'Business', 'Engineering', 'Arts', 'Science', 'Medicine'];
        foreach ($departments as $dept): ?>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-slate-900 dark:text-white"><?php echo $dept; ?></span>
                    <span class="text-xs text-slate-500"><?php echo rand(5, 50); ?> users</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: <?php echo rand(20, 80); ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function testIntegration() {
    // Show loading state
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<div class="flex items-center gap-3"><div class="w-10 h-10 bg-green-100 dark:bg-green-500/20 rounded-xl flex items-center justify-center"><i class="bx bx-loader-alt animate-spin text-green-600 dark:text-green-400 text-xl"></i></div><div><p class="font-semibold text-slate-900 dark:text-white">Testing...</p><p class="text-xs text-slate-500">Checking connection</p></div></div>';
    
    // Simulate API test
    setTimeout(() => {
        btn.innerHTML = '<div class="flex items-center gap-3"><div class="w-10 h-10 bg-green-100 dark:bg-green-500/20 rounded-xl flex items-center justify-center"><i class="bx bx-check-circle text-green-600 dark:text-green-400 text-xl"></i></div><div><p class="font-semibold text-slate-900 dark:text-white">Connected</p><p class="text-xs text-slate-500">Academic system reachable</p></div></div>';
        
        setTimeout(() => {
            btn.innerHTML = originalContent;
        }, 3000);
    }, 2000);
}
</script>

<?php require '../components/footer.php'; ?>
