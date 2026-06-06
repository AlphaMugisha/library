<?php
session_start();
require '../config/db.php';

// Security Check: Only Teachers allowed here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Fetch Teacher's Specific Library Data ---
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

// Fetch their actual borrowing history
$my_books = [];
try {
    $query = "
        SELECT br.*, b.title, b.author 
        FROM borrowings br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? 
        ORDER BY FIELD(br.status, 'Issued') DESC, br.due_date ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $my_books = $stmt->fetchAll();
} catch (PDOException $e) {}

// Setup Header
$page_title = "Educator Desk | NGA System";
$header_title = "My Educator Desk";
require '../components/header.php'; 
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-blue-500">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500">
                <i class='bx bxs-briefcase-alt-2 text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['active_loans']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Active Reference Materials</p>
    </div>

    <div class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 <?php echo ($stats['overdue'] > 0) ? 'border-l-red-500' : 'border-l-green-500'; ?>">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 <?php echo ($stats['overdue'] > 0) ? 'bg-red-100 dark:bg-red-500/10 text-red-500' : 'bg-green-100 dark:bg-green-500/10 text-green-500'; ?> rounded-xl flex items-center justify-center">
                <i class='bx <?php echo ($stats['overdue'] > 0) ? 'bxs-error-circle animate-pulse' : 'bxs-check-shield'; ?> text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['overdue']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Pending Returns</p>
    </div>

    <a href="history.php" class="card-hover relative bg-white dark:bg-slate-900/50 p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-purple-500">
        <div class="flex justify-between items-start mb-2">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500">
                <i class='bx bx-history text-xl'></i>
            </div>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['total_read']; ?></h3>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 font-semibold">Historical Borrows</p>
    </a>
</div>

<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 mt-8 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">My Resource Log</h2>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100/50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                    <th class="p-4 pl-6">Resource Title</th>
                    <th class="p-4">Borrowed On</th>
                    <th class="p-4">Due Date</th>
                    <th class="p-4">Status</th>
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
                                <?php elseif ($book['status'] === 'Pending'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-100/50 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/20 rounded-full text-xs font-bold">
                                        <i class='bx bx-time-five'></i> Pending Approval
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100/50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded-full text-xs font-bold">
                                        <i class='bx bx-bookmark'></i> Active Loan
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class='bx bx-ghost text-3xl text-slate-400'></i>
                            </div>
                            <p class="font-bold text-slate-900 dark:text-white">No borrowing history yet.</p>
                            <p class="text-sm text-slate-500 mt-1">Visit the catalog to request reference materials!</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../components/footer.php'; ?>