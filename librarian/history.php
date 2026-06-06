<?php
session_start();
require '../config/db.php';

// Security Check: Only Librarians allowed here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

// Fetch complete system-wide borrowing history
$history = [];
try {
    $query = "
        SELECT br.*, b.title, b.author, u.full_name, u.role as user_role
        FROM borrowings br 
        JOIN books b ON br.book_id = b.id 
        JOIN users u ON br.user_id = u.id
        ORDER BY br.issue_date DESC, br.id DESC
    ";
    $stmt = $pdo->query($query);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {}

// Setup Header
$page_title = "Global History | NGA Librarian";
$header_title = "System Borrowing History";
require '../components/header.php'; 
?>

<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 shadow-sm">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Global History Log</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium">Monitoring all past and present book movements across the system.</p>
        </div>
        
        <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/50 p-2 rounded-2xl">
            <div class="px-4 py-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm text-xs font-bold text-slate-900 dark:text-white">
                Total Transactions: <?php echo count($history); ?>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-black">
                    <th class="p-4 pl-6">Member</th>
                    <th class="p-4">Book</th>
                    <th class="p-4">Issue Date</th>
                    <th class="p-4">Return Date</th>
                    <th class="p-4">Fine</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50 text-sm">
                <?php if (count($history) > 0): ?>
                    <?php foreach ($history as $item): 
                        $is_overdue = ($item['status'] === 'Issued' && $item['due_date'] < date('Y-m-d'));
                    ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-300">
                            <td class="p-4 pl-6">
                                <p class="font-black text-slate-900 dark:text-white"><?php echo htmlspecialchars($item['full_name']); ?></p>
                                <p class="text-[10px] text-slate-500 font-bold uppercase"><?php echo $item['user_role']; ?></p>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($item['title']); ?></p>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-bold">
                                <?php echo date('M j, Y', strtotime($item['issue_date'])); ?>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-bold">
                                <?php echo $item['return_date'] ? date('M j, Y', strtotime($item['return_date'])) : '<span class="text-slate-400 italic">N/A</span>'; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($item['fine_amount'] > 0): ?>
                                    <span class="text-red-500 font-black"><?php echo number_format($item['fine_amount']); ?> RWF</span>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($item['status'] === 'Returned'): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-600 rounded-lg text-[10px] font-black uppercase tracking-wider">Returned</span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-600 rounded-lg text-[10px] font-black uppercase tracking-wider animate-pulse">Overdue</span>
                                <?php elseif ($item['status'] === 'Issued'): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded-lg text-[10px] font-black uppercase tracking-wider">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-lg text-[10px] font-black uppercase tracking-wider"><?php echo $item['status']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="p-12 text-center text-slate-500">No system history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../components/footer.php'; ?>