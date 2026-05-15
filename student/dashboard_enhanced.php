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

// --- Fetch Student's Enhanced Data ---
// 1. Get basic library stats
$stats = ['active_loans' => 0, 'overdue' => 0, 'total_read' => 0];
try {
    $activeQuery = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'Issued'");
    $activeQuery->execute([$user_id]);
    $stats['active_loans'] = $activeQuery->fetchColumn();

    $overdueQuery = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'Issued' AND due_date < CURDATE()");
    $overdueQuery->execute([$user_id]);
    $stats['overdue'] = $overdueQuery->fetchColumn();

    $readQuery = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'Returned'");
    $readQuery->execute([$user_id]);
    $stats['total_read'] = $readQuery->fetchColumn();
} catch (PDOException $e) {}

// 2. Get user's academic information
$user_academic_info = [];
try {
    $userQuery = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userQuery->execute([$user_id]);
    $user_academic_info = $userQuery->fetch();
} catch (PDOException $e) {}

// 3. Get department-based book recommendations
$recommended_books = [];
if (!empty($user_academic_info['academic_department'])) {
    try {
        $recQuery = $pdo->prepare("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity
            FROM books b 
            WHERE b.category LIKE ? OR b.subject LIKE ? 
            ORDER BY popularity DESC, b.title ASC 
            LIMIT 5
        ");
        $recQuery->execute([
            '%' . $user_academic_info['academic_department'] . '%',
            '%' . $user_academic_info['academic_department'] . '%'
        ]);
        $recommended_books = $recQuery->fetchAll();
    } catch (PDOException $e) {}
}

// 4. Get recent borrowing history
$my_books = [];
try {
    $query = "
        SELECT br.*, b.title, b.author, b.cover_image 
        FROM borrowings br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? 
        ORDER BY FIELD(br.status, 'Issued') DESC, br.due_date ASC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $my_books = $stmt->fetchAll();
} catch (PDOException $e) {}

// 5. Get integration status
$integration_status = [
    'is_academic_user' => !empty($user_academic_info['academic_id']),
    'sync_status' => $user_academic_info['sync_status'] ?? 'unknown',
    'last_sync' => $user_academic_info['last_sync'] ?? null,
    'academic_login' => $_SESSION['academic_login'] ?? false
];

// Setup Header
$page_title = "My Library | NGA Student";
$header_title = "My Academic Reading Workspace";
require '../components/header.php'; 
?>

<!-- Academic Integration Status Banner -->
<?php if ($integration_status['is_academic_user']): ?>
<div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-2xl p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-xl flex items-center justify-center">
                <i class='bx bx-graduation-cap text-white text-xl'></i>
            </div>
            <div>
                <p class="font-semibold text-purple-900">Academic System Connected</p>
                <p class="text-sm text-purple-700">
                    <?php if ($integration_status['academic_login']): ?>
                        <i class='bx bx-check-circle'></i> Logged in via Academic System
                    <?php else: ?>
                        <i class='bx bx-link'></i> Synced with Academic ID: <?php echo htmlspecialchars($user_academic_info['academic_id']); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs text-purple-600">Last Sync</p>
            <p class="text-sm font-semibold text-purple-900">
                <?php echo $integration_status['last_sync'] ? date('M j, Y H:i', strtotime($integration_status['last_sync'])) : 'Never'; ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Enhanced Stats Grid with Academic Info -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Active Loans -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-blue-500">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500">
                <i class='bx bxs-book-reader text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['active_loans']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Active Loans</p>
    </div>

    <!-- Overdue Books -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 <?php echo ($stats['overdue'] > 0) ? 'border-l-red-500' : 'border-l-green-500'; ?>">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 <?php echo ($stats['overdue'] > 0) ? 'bg-red-100 dark:bg-red-500/10 text-red-500' : 'bg-green-100 dark:bg-green-500/10 text-green-500'; ?> rounded-xl flex items-center justify-center">
                <i class='bx <?php echo ($stats['overdue'] > 0) ? 'bxs-error-circle animate-pulse' : 'bxs-check-shield'; ?> text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['overdue']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Overdue Books</p>
    </div>

    <!-- Books Completed -->
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-purple-500">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500">
                <i class='bx bxs-trophy text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['total_read']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Books Completed</p>
    </div>

    <!-- Academic Level -->
    <?php if (!empty($user_academic_info['academic_level'])): ?>
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-orange-500">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-500/10 rounded-xl flex items-center justify-center text-orange-500">
                <i class='bx bxs-graduation text-xl'></i>
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white"><?php echo htmlspecialchars($user_academic_info['academic_level']); ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Academic Level</p>
    </div>
    <?php endif; ?>
</div>

<!-- Academic Information Section -->
<?php if ($integration_status['is_academic_user']): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Academic Profile Card -->
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class='bx bx-user-voice text-purple-500'></i>
            Academic Profile
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <span class="text-sm text-slate-600 dark:text-slate-400">Department</span>
                <span class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user_academic_info['academic_department'] ?? 'Not Set'); ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <span class="text-sm text-slate-600 dark:text-slate-400">Academic Role</span>
                <span class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user_academic_info['academic_role'] ?? 'Not Set'); ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                <span class="text-sm text-slate-600 dark:text-slate-400">Sync Status</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold 
                    <?php 
                    $status = $user_academic_info['sync_status'] ?? 'unknown';
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
        </div>
    </div>

    <!-- Department Recommendations -->
    <div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class='bx bx-bookmark-star text-orange-500'></i>
            Recommended for Your Department
        </h2>
        <?php if (count($recommended_books) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($recommended_books as $book): ?>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors cursor-pointer">
                        <div class="w-12 h-16 bg-gradient-to-br from-orange-400 to-purple-500 rounded-lg flex items-center justify-center">
                            <i class='bx bx-book text-white text-xl'></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($book['title']); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-xs text-orange-600 dark:text-orange-400 font-semibold mt-1">
                                <?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> available
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i class='bx bx-book-open text-3xl text-slate-400'></i>
                </div>
                <p class="font-semibold text-slate-900 dark:text-white">No recommendations yet</p>
                <p class="text-sm text-slate-500 mt-1">Books for your department will appear here</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Enhanced Book History -->
<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 mt-8 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class='bx bx-history text-blue-500'></i>
            My Book History
        </h2>
        <?php if ($integration_status['is_academic_user']): ?>
            <button class="px-4 py-2 bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 rounded-xl text-sm font-semibold hover:bg-purple-200 dark:hover:bg-purple-500/20 transition-colors flex items-center gap-2">
                <i class='bx bx-sync'></i>
                Sync from Academic System
            </button>
        <?php endif; ?>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                    <th class="p-4 pl-6">Book Title</th>
                    <th class="p-4">Borrowed On</th>
                    <th class="p-4">Due Date</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50 text-sm">
                <?php if (count($my_books) > 0): ?>
                    <?php foreach ($my_books as $book): 
                        $is_overdue = ($book['status'] === 'Issued' && $book['due_date'] < date('Y-m-d'));
                    ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6">
                                <p class="font-black text-slate-900 dark:text-white text-base"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5"><?php echo htmlspecialchars($book['author']); ?></p>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-medium">
                                <?php echo date('M j, Y', strtotime($book['issue_date'])); ?>
                            </td>
                            <td class="p-4 font-bold <?php echo $is_overdue ? 'text-red-500' : 'text-slate-600 dark:text-slate-400'; ?>">
                                <?php echo date('M j, Y', strtotime($book['due_date'])); ?>
                            </td>
                            <td class="p-4">
                                <?php if ($book['status'] === 'Returned'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100/50 dark:bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/20 rounded-full text-xs font-bold">
                                        <i class='bx bx-check-double'></i> Returned on <?php echo date('M j', strtotime($book['return_date'])); ?>
                                    </span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100/50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 rounded-full text-xs font-bold animate-pulse">
                                        <i class='bx bx-error-circle'></i> Overdue! Return ASAP
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-100/50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20 rounded-full text-xs font-bold">
                                        <i class='bx bx-book-reader'></i> Currently Reading
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($book['status'] === 'Issued'): ?>
                                    <button class="px-3 py-1 bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-200 dark:hover:bg-blue-500/20 transition-colors">
                                        <i class='bx bx-refresh'></i> Renew
                                    </button>
                                <?php else: ?>
                                    <button class="px-3 py-1 bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-semibold hover:bg-purple-200 dark:hover:bg-purple-500/20 transition-colors">
                                        <i class='bx bx-book'></i> Borrow Again
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class='bx bx-ghost text-3xl text-slate-400'></i>
                            </div>
                            <p class="font-bold text-slate-900 dark:text-white">No borrowing history yet.</p>
                            <p class="text-sm text-slate-500 mt-1">
                                <?php if ($integration_status['is_academic_user']): ?>
                                    Visit the library to check out your first book, or explore recommendations for your department!
                                <?php else: ?>
                                    Visit the library to check out your first book!
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../components/footer.php'; ?>
