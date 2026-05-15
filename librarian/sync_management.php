<?php
session_start();
require '../config/db.php';
require '../config/academic_integration.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

// Handle sync actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $integration = getAcademicIntegration($pdo);
    
    switch ($_POST['action']) {
        case 'sync_all':
            try {
                // Get all users with academic_id that need syncing
                $stmt = $pdo->prepare("SELECT academic_id FROM users WHERE academic_id IS NOT NULL AND sync_status != 'synced'");
                $stmt->execute();
                $users = $stmt->fetchAll();
                
                $synced_count = 0;
                foreach ($users as $user) {
                    if ($integration->syncUser($user['academic_id'])) {
                        $synced_count++;
                    }
                }
                
                $message = "Successfully synced {$synced_count} users with academic system";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Sync failed: " . $e->getMessage();
                $message_type = 'error';
            }
            break;
            
        case 'sync_user':
            $academic_id = $_POST['academic_id'] ?? '';
            try {
                if ($integration->syncUser($academic_id)) {
                    $message = "User {$academic_id} synced successfully";
                    $message_type = 'success';
                } else {
                    $message = "Failed to sync user {$academic_id}";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = "Sync failed: " . $e->getMessage();
                $message_type = 'error';
            }
            break;
            
        case 'reset_sync':
            try {
                $stmt = $pdo->prepare("UPDATE users SET sync_status = 'pending' WHERE academic_id IS NOT NULL");
                $stmt->execute();
                $affected = $stmt->rowCount();
                $message = "Reset sync status for {$affected} users";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Reset failed: " . $e->getMessage();
                $message_type = 'error';
            }
            break;
    }
}

// Get users with academic information
$academic_users = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, full_name, email, academic_id, sync_status, 
            last_sync, academic_role, academic_department, academic_level,
            created_at
        FROM users 
        WHERE academic_id IS NOT NULL 
        ORDER BY last_sync DESC, created_at DESC
    ");
    $stmt->execute();
    $academic_users = $stmt->fetchAll();
} catch (PDOException $e) {}

// Get sync statistics
$sync_stats = [];
try {
    $stmt = $pdo->prepare("
        SELECT sync_status, COUNT(*) as count
        FROM users 
        WHERE academic_id IS NOT NULL
        GROUP BY sync_status
    ");
    $stmt->execute();
    $sync_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {}

$page_title = "Sync Management | NGA Library";
$header_title = "Academic System Sync Management";
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

<!-- Sync Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <div class="flex items-center justify-between mb-2">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-500/10 rounded-xl flex items-center justify-center">
                <i class='bx bx-user text-blue-500 text-xl'></i>
            </div>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white"><?php echo array_sum($sync_stats); ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Total Academic Users</p>
    </div>

    <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <div class="flex items-center justify-between mb-2">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-500/10 rounded-xl flex items-center justify-center">
                <i class='bx bx-check-circle text-green-500 text-xl'></i>
            </div>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $sync_stats['synced'] ?? 0; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Synced</p>
    </div>

    <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <div class="flex items-center justify-between mb-2">
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-500/10 rounded-xl flex items-center justify-center">
                <i class='bx bx-time text-yellow-500 text-xl'></i>
            </div>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $sync_stats['pending'] ?? 0; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Pending Sync</p>
    </div>

    <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <div class="flex items-center justify-between mb-2">
            <div class="w-12 h-12 bg-red-100 dark:bg-red-500/10 rounded-xl flex items-center justify-center">
                <i class='bx bx-error text-red-500 text-xl'></i>
            </div>
        </div>
        <h3 class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $sync_stats['error'] ?? 0; ?></h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Sync Errors</p>
    </div>
</div>

<!-- Sync Actions -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <i class='bx bx-cog text-blue-500'></i>
        Sync Actions
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <form method="POST" class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
            <input type="hidden" name="action" value="sync_all">
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-500/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-sync text-blue-500 text-xl'></i>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Sync All Users</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Sync all pending academic users</p>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                    Start Full Sync
                </button>
            </div>
        </form>

        <form method="POST" class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
            <input type="hidden" name="action" value="reset_sync">
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-500/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-refresh text-orange-500 text-xl'></i>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Reset Sync Status</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Mark all users as pending sync</p>
                <button type="submit" class="w-full px-4 py-2 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition-colors">
                    Reset All Status
                </button>
            </div>
        </form>

        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-500/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-download text-purple-500 text-xl'></i>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Import Users</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Import users from academic system</p>
                <button onclick="alert('Import feature coming soon!')" class="w-full px-4 py-2 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors">
                    Import Users
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Academic Users Table -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class='bx bx-group text-purple-500'></i>
            Academic Users
        </h2>
        <div class="flex gap-2">
            <input type="text" id="searchInput" placeholder="Search users..." class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm">
            <select id="statusFilter" class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                <option value="">All Status</option>
                <option value="synced">Synced</option>
                <option value="pending">Pending</option>
                <option value="error">Error</option>
                <option value="manual">Manual</option>
            </select>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="usersTable">
            <thead>
                <tr class="bg-slate-100/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                    <th class="p-4 pl-6">User</th>
                    <th class="p-4">Academic ID</th>
                    <th class="p-4">Department</th>
                    <th class="p-4">Academic Role</th>
                    <th class="p-4">Sync Status</th>
                    <th class="p-4">Last Sync</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50 text-sm">
                <?php if (count($academic_users) > 0): ?>
                    <?php foreach ($academic_users as $user): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors user-row" data-status="<?php echo $user['sync_status']; ?>">
                            <td class="p-4 pl-6">
                                <div>
                                    <p class="font-black text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                    <p class="text-xs text-slate-500 font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </td>
                            <td class="p-4 font-mono text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($user['academic_id']); ?>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($user['academic_department'] ?? 'Not Set'); ?>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($user['academic_role'] ?? 'Not Set'); ?>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold 
                                    <?php 
                                    echo match($user['sync_status']) {
                                        'synced' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'error' => 'bg-red-100 text-red-700',
                                        'manual' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    ?>">
                                    <?php echo ucfirst($user['sync_status']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400">
                                <?php echo $user['last_sync'] ? date('M j, Y H:i', strtotime($user['last_sync'])) : 'Never'; ?>
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <?php if ($user['sync_status'] !== 'synced'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="sync_user">
                                            <input type="hidden" name="academic_id" value="<?php echo $user['academic_id']; ?>">
                                            <button type="submit" class="px-3 py-1 bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-200 dark:hover:bg-blue-500/20 transition-colors">
                                                <i class='bx bx-sync'></i> Sync
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button onclick="viewUserDetails(<?php echo $user['id']; ?>)" class="px-3 py-1 bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-semibold hover:bg-purple-200 dark:hover:bg-purple-500/20 transition-colors">
                                        <i class='bx bx-eye'></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class='bx bx-user-x text-3xl text-slate-400'></i>
                            </div>
                            <p class="font-bold text-slate-900 dark:text-white">No academic users found</p>
                            <p class="text-sm text-slate-500 mt-1">Users will appear here after they login via the academic system</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search and filter functionality
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#usersTable .user-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.dataset.status;
        
        const matchesSearch = text.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

function viewUserDetails(userId) {
    // This would open a modal or navigate to user details page
    alert('User details view coming soon! User ID: ' + userId);
}
</script>

<?php require '../components/footer.php'; ?>
