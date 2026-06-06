<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- LOGIC: APPROVE OR REJECT PENDING REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['handle_request'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $action = $_POST['handle_request']; 
    $book_id = (int)$_POST['book_id'];

    try {
        $pdo->beginTransaction();
        if ($action === 'Approve') {
            $stmt = $pdo->prepare("SELECT u.role, b.available_copies FROM borrowings br JOIN users u ON br.user_id = u.id JOIN books b ON br.book_id = b.id WHERE br.id = ?");
            $stmt->execute([$borrowing_id]);
            $data = $stmt->fetch();

            if ($data && $data['available_copies'] > 0) {
                $days = ($data['role'] === 'teacher') ? 30 : 14;
                $due_date = date('Y-m-d', strtotime("+$days days"));

                $updateBr = $pdo->prepare("UPDATE borrowings SET status = 'Issued', issue_date = CURDATE(), due_date = ? WHERE id = ?");
                $updateBr->execute([$due_date, $borrowing_id]);

                $updateBook = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1, borrow_count = borrow_count + 1 WHERE id = ?");
                $updateBook->execute([$book_id]);

                $success_msg = "Request approved! Issued for $days days.";
            } else {
                $error_msg = "Book is out of stock or data missing.";
            }
        } else {
            $pdo->prepare("UPDATE borrowings SET status = 'Rejected' WHERE id = ?")->execute([$borrowing_id]);
            $success_msg = "Request rejected.";
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error: " . $e->getMessage();
    }
}

// --- LOGIC: MANUAL ISSUE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_issue'])) {
    $user_id = (int)$_POST['user_id'];
    $book_id = (int)$_POST['book_id'];

    try {
        $user_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $role = $user_stmt->fetchColumn();

        $days = ($role === 'teacher') ? 30 : 14;
        $due_date = date('Y-m-d', strtotime("+$days days"));

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, book_id, issue_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'Issued')");
        $stmt->execute([$user_id, $book_id, $due_date]);

        $pdo->prepare("UPDATE books SET available_copies = available_copies - 1, borrow_count = borrow_count + 1 WHERE id = ?")->execute([$book_id]);
        $pdo->commit();
        $success_msg = "Book issued manually for $days days.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Manual issue failed: " . $e->getMessage();
    }
}

// --- LOGIC: MARK BOOK AS LOST/DAMAGED ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_lost'])) {
    $id = (int)$_POST['borrowing_id'];
    $book_id = (int)$_POST['book_id'];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT user_id FROM borrowings WHERE id = ?");
        $stmt->execute([$id]);
        $user_id = $stmt->fetchColumn();

        $replacement_fee = 15000; // 15,000 RWF Replacement Fine

        $pdo->prepare("UPDATE borrowings SET status = 'Overdue', return_date = CURDATE(), fine_amount = ? WHERE id = ?")->execute([$replacement_fee, $id]);
        $pdo->prepare("UPDATE books SET total_copies = total_copies - 1 WHERE id = ?")->execute([$book_id]);
        $pdo->prepare("UPDATE users SET total_fines = total_fines + ? WHERE id = ?")->execute([$replacement_fee, $user_id]);
        
        $pdo->commit();
        $success_msg = "Book marked as LOST. Inventory reduced and 15,000 RWF fee charged.";
    } catch (Exception $e) { $pdo->rollBack(); $error_msg = "Action failed."; }
} 

// --- LOGIC: RETURN BOOK ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $id = (int)$_POST['borrowing_id'];
    $book_id = (int)$_POST['book_id'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT br.*, u.role FROM borrowings br JOIN users u ON br.user_id = u.id WHERE br.id = ?");
        $stmt->execute([$id]);
        $loan = $stmt->fetch();

        $fine = 0;
        if ($loan['role'] === 'student' && strtotime($loan['due_date']) < strtotime(date('Y-m-d'))) {
            $days_late = ceil((time() - strtotime($loan['due_date'])) / 86400);
            $fine = $days_late * 200; 
        }

        $update = $pdo->prepare("UPDATE borrowings SET status = 'Returned', return_date = CURDATE(), fine_amount = ? WHERE id = ?");
        $update->execute([$fine, $id]);

        if ($fine > 0) {
            $pdo->prepare("UPDATE users SET total_fines = total_fines + ? WHERE id = ?")->execute([$fine, $loan['user_id']]);
        }

        $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?")->execute([$book_id]);
        $pdo->commit();
        $success_msg = "Book returned. " . ($fine > 0 ? "Fine of $fine RWF applied." : "");
    } catch (Exception $e) { $pdo->rollBack(); $error_msg = "Return failed."; }
}

// --- BULLETPROOF DATA FETCHING ---
try {
    $pending_query = "
        SELECT br.*, COALESCE(u.full_name, 'Unknown User') as full_name, COALESCE(u.role, 'Student') as role, COALESCE(b.title, 'Unknown Book') as title 
        FROM borrowings br 
        LEFT JOIN users u ON br.user_id = u.id 
        LEFT JOIN books b ON br.book_id = b.id 
        WHERE LOWER(br.status) = 'pending' 
        ORDER BY br.id DESC
    ";
    $pending = $pdo->query($pending_query)->fetchAll();

    $active_query = "
        SELECT br.*, COALESCE(u.full_name, 'Unknown User') as full_name, COALESCE(u.role, 'Student') as role, COALESCE(b.title, 'Unknown Book') as title 
        FROM borrowings br 
        LEFT JOIN users u ON br.user_id = u.id 
        LEFT JOIN books b ON br.book_id = b.id 
        WHERE LOWER(br.status) = 'issued' 
        ORDER BY br.due_date ASC
    ";
    $active = $pdo->query($active_query)->fetchAll();

    $users_list = $pdo->query("SELECT id, full_name, role FROM users WHERE role != 'librarian' AND library_status = 'active' ORDER BY full_name ASC")->fetchAll();
    $books_list = $pdo->query("SELECT id, title, available_copies FROM books WHERE available_copies > 0 ORDER BY title ASC")->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Critical Database Error: " . $e->getMessage();
    $pending = [];
    $active = [];
    $users_list = [];
    $books_list = [];
}

$page_title = "Transactions | NGA Library";
$header_title = "Issue & Return Center";
require '../components/header.php';
?>

<?php if($success_msg): ?>
    <div class="bg-green-500 text-white p-4 rounded-xl mb-6 font-bold flex items-center gap-2 shadow-lg animate-fade-in">
        <i class='bx bxs-check-circle'></i> <?php echo $success_msg; ?>
    </div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="bg-red-500 text-white p-4 rounded-xl mb-6 font-bold flex items-center gap-2 shadow-lg animate-fade-in">
        <i class='bx bxs-error-circle'></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<div class="glass rounded-[32px] border border-slate-200 dark:border-slate-800 p-8 mb-8">
    <h2 class="text-xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
        <i class='bx bxs-bell-ring text-orange-500'></i> Pickup Requests
    </h2>
    <?php if ($pending): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($pending as $p): ?>
                <div class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl flex justify-between items-center shadow-sm">
                    <div>
                        <p class="font-black text-slate-900 dark:text-white"><?php echo htmlspecialchars($p['title']); ?></p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase"><?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['role']; ?>)</p>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="borrowing_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="book_id" value="<?php echo $p['book_id']; ?>">
                            <button name="handle_request" value="Approve" class="w-10 h-10 bg-green-500 text-white rounded-xl flex items-center justify-center hover:bg-green-600 transition-all shadow-md tooltip" title="Approve Request"><i class='bx bx-check text-xl'></i></button>
                            <button name="handle_request" value="Reject" class="w-10 h-10 bg-red-500 text-white rounded-xl flex items-center justify-center hover:bg-red-600 transition-all shadow-md tooltip" title="Reject Request"><i class='bx bx-x text-xl'></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-slate-500 text-sm italic">No pending requests at the moment.</p>
    <?php endif; ?>
</div>

<div class="glass rounded-[32px] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/20">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Active Loans</h2>
        <button onclick="toggleModal('manualIssueModal')" class="px-5 py-2.5 bg-[#FF6600] text-white rounded-xl font-bold text-xs shadow-lg shadow-orange-500/20 hover:-translate-y-0.5 transition-all">+ Manual Issue</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] uppercase font-black text-slate-500 tracking-widest">
                <tr>
                    <th class="p-4 pl-8">Borrower</th>
                    <th class="p-4">Book</th>
                    <th class="p-4">Due Date</th>
                    <th class="p-4 text-center pr-8">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                <?php if($active): foreach($active as $a): 
                    $is_overdue = strtotime($a['due_date']) < strtotime(date('Y-m-d'));
                ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="p-4 pl-8">
                            <p class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($a['full_name']); ?></p>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 font-black uppercase"><?php echo $a['role']; ?></span>
                        </td>
                        <td class="p-4 text-slate-700 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($a['title']); ?></td>
                        <td class="p-4">
                            <span class="font-bold <?php echo $is_overdue ? 'text-red-500 animate-pulse' : 'text-slate-600 dark:text-slate-400'; ?>">
                                <?php echo date('M j, Y', strtotime($a['due_date'])); ?>
                            </span>
                        </td>
                        <td class="p-4 text-center pr-8">
                            <form method="POST" class="flex flex-col gap-2">
                                <input type="hidden" name="borrowing_id" value="<?php echo $a['id']; ?>">
                                <input type="hidden" name="book_id" value="<?php echo $a['book_id']; ?>">
                                
                                <button type="submit" name="return_book" onclick="return confirm('Confirm return?');" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg text-xs font-black shadow-md hover:-translate-y-0.5 transition-transform">
                                    Mark Returned
                                </button>
                                
                                <button type="submit" name="mark_lost" onclick="return confirm('WARNING: This will permanently remove the book from inventory and charge the user 15,000 RWF. Proceed?');" class="px-4 py-1.5 bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-lg text-xs font-bold hover:bg-red-200 dark:hover:bg-red-500/20 transition-colors shadow-sm">
                                    Mark as Lost
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No active loans found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="manualIssueModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay">
    <div class="modal-content w-full max-w-md bg-white dark:bg-[#0f172a] rounded-[24px] shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden relative">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-[#020617]/50">
            <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2"><i class='bx bx-plus-circle text-[#FF6600]'></i> Manual Issue</h3>
            <button onclick="toggleModal('manualIssueModal')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-red-500 transition-colors"><i class='bx bx-x text-xl'></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Member</label>
                <select name="user_id" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                    <?php if($users_list): foreach($users_list as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)</option>
                    <?php endforeach; else: ?>
                        <option value="" disabled>No active members available</option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Book</label>
                <select name="book_id" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                    <?php if($books_list): foreach($books_list as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?> (<?php echo $b['available_copies']; ?> left)</option>
                    <?php endforeach; else: ?>
                        <option value="" disabled>No books available</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3 mt-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="toggleModal('manualIssueModal')" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                <button type="submit" name="manual_issue" class="px-6 py-2.5 bg-[#FF6600] text-white font-bold rounded-xl shadow-lg shadow-orange-500/30">Confirm Issue</button>
            </div>
        </form>
    </div>
</div>

<?php require '../components/footer.php'; ?>