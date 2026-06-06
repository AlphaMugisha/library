<?php
session_start();
require '../config/db.php';

// Security Check: Only Students allowed here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch complete borrowing history
$history = [];
try {
    $query = "
        SELECT br.*, b.title, b.author, b.cover_image, b.category
        FROM borrowings br 
        JOIN books b ON br.book_id = b.id 
        WHERE br.user_id = ? 
        ORDER BY br.issue_date DESC, br.id DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {}

// Setup Header
$page_title = "Reading History | NGA Student";
$header_title = "My Reading History";
require '../components/header.php'; 
?>

<div class="glass rounded-[32px] border border-slate-200/50 dark:border-slate-800/50 p-8 shadow-sm">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Complete History</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium">A record of all books you've interacted with.</p>
        </div>
        
        <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/50 p-2 rounded-2xl">
            <div class="px-4 py-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm text-xs font-bold text-slate-900 dark:text-white">
                Total: <?php echo count($history); ?> Books
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700 text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-black">
                    <th class="p-4 pl-6">Book Details</th>
                    <th class="p-4">Category</th>
                    <th class="p-4">Issue Date</th>
                    <th class="p-4">Return Date</th>
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
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-14 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 rounded-lg flex items-center justify-center flex-shrink-0 shadow-inner">
                                        <?php if ($item['cover_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['cover_image']); ?>" class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <i class='bx bxs-book text-slate-400 text-xl'></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-900 dark:text-white leading-tight"><?php echo htmlspecialchars($item['title']); ?></p>
                                        <p class="text-xs text-slate-500 font-semibold mt-0.5"><?php echo htmlspecialchars($item['author']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="text-[10px] font-bold text-nga-brand uppercase tracking-wider bg-orange-50 dark:bg-orange-500/10 px-2 py-1 rounded-md">
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-bold">
                                <?php echo date('M j, Y', strtotime($item['issue_date'])); ?>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-bold">
                                <?php echo $item['return_date'] ? date('M j, Y', strtotime($item['return_date'])) : '<span class="text-slate-400 italic">Not returned</span>'; ?>
                            </td>
                            <td class="p-4">
                                <?php if ($item['status'] === 'Returned'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100/50 dark:bg-green-500/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/20 rounded-full text-[10px] font-black uppercase tracking-wider">
                                        <i class='bx bx-check-circle'></i> Returned
                                    </span>
                                <?php elseif ($item['status'] === 'Rejected'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-wider">
                                        <i class='bx bx-x-circle'></i> Rejected
                                    </span>
                                <?php elseif ($is_overdue): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100/50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 rounded-full text-[10px] font-black uppercase tracking-wider animate-pulse">
                                        <i class='bx bx-error-circle'></i> Overdue
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-100/50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20 rounded-full text-[10px] font-black uppercase tracking-wider">
                                        <i class='bx bx-book-reader'></i> Active
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <i class='bx bx-history text-3xl text-slate-400'></i>
                            </div>
                            <p class="font-bold text-slate-900 dark:text-white">No history found.</p>
                            <p class="text-sm text-slate-500 mt-1">Your borrowing history will appear here once you start using the library.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../components/footer.php'; ?>