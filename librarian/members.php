<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: ../login.php");
    exit;
}

$success_msg = ''; $error_msg = '';

// --- 1. HANDLE REGISTRATION & SEND REAL EMAIL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role']; 
    $token = bin2hex(random_bytes(16));

    try {
        // Insert user as unactivated
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, library_status, total_fines, is_activated, activation_token) VALUES (?, ?, '', ?, 'active', 0, 0, ?)");
        $stmt->execute([$full_name, $email, $role, $token]);
        
        // Setup Email Variables
        // IMPORTANT: If your folder is named something other than "nga_library", change the URL below!
        $activation_link = "http://localhost/nga_library/activate.php?token=" . $token; 
        
        $subject = "🔒 Activate your NGA Library Account";
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: NGA Library <noreply@ngaportal.sowiseafrica.org>\r\n";
        
        $html = "
        <div style='font-family: sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #0f172a;'>Welcome to NGA Library, $full_name!</h2>
            <p style='color: #64748b;'>Your $role account has been created. Click the button below to set your secure password and activate your account.</p>
            <a href='$activation_link' style='display: inline-block; padding: 12px 24px; background: #FF6600; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 10px;'>Activate Account</a>
        </div>";
        
        // Suppress email errors on localhost using @
        @mail($email, $subject, $html, $headers);
        
        // DEV MODE FIX: Print the link directly to the screen so you can test it locally
        $success_msg = ucfirst($role) . " registered successfully! <br><br>
        <span class='bg-white/30 text-white px-2 py-1 rounded text-xs font-black uppercase tracking-widest'>DEV MODE</span> 
        <a href='$activation_link' target='_blank' class='underline font-bold ml-2 text-white hover:text-slate-200 transition-colors'>Click here to simulate the Activation Email link</a>";
        
    } catch (PDOException $e) {
        $error_msg = ($e->getCode() == 23000) ? "Email already exists in the system." : "Error: " . $e->getMessage();
    }
}

// --- 2. HANDLE SUSPENSIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $target_user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];
    $pdo->prepare("UPDATE users SET library_status = ? WHERE id = ?")->execute([$new_status, $target_user_id]);
    $success_msg = "Member status updated to " . strtoupper($new_status) . "!";
}

// --- 3. HANDLE CLEARING FINES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_fines'])) {
    $target_user_id = (int)$_POST['user_id'];
    $pdo->prepare("UPDATE users SET total_fines = 0 WHERE id = ?")->execute([$target_user_id]);
    $success_msg = "Fines successfully cleared. The cash register rings!";
}

// Fetch Stats & Members
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'librarian'")->fetchColumn(),
    'suspended' => $pdo->query("SELECT COUNT(*) FROM users WHERE library_status = 'suspended' AND role != 'librarian'")->fetchColumn(),
    'total_fines' => $pdo->query("SELECT SUM(total_fines) FROM users WHERE role != 'librarian'")->fetchColumn() ?? 0
];
$members = $pdo->query("SELECT * FROM users WHERE role != 'librarian' ORDER BY full_name ASC")->fetchAll();

$page_title = "Member Management | NGA Library";
$header_title = "Library Members";
require '../components/header.php'; 
?>

<?php if ($success_msg): ?>
    <div class='bg-green-500 text-white p-5 rounded-xl mb-6 shadow-lg animate-fade-in'>
        <div class="flex items-center gap-2 font-bold mb-1">
            <i class='bx bxs-check-circle text-xl'></i> Success
        </div>
        <div class="text-sm"><?php echo $success_msg; ?></div>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class='bg-red-500 text-white p-4 rounded-xl mb-6 font-bold shadow-lg flex items-center gap-2 animate-fade-in'>
        <i class='bx bxs-error-circle text-xl'></i> <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="glass p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo $stats['total']; ?></h3>
        <p class="text-sm text-slate-500 font-semibold">Total Members</p>
    </div>
    <div class="glass p-6 rounded-[24px] border border-slate-200 dark:border-slate-800">
        <h3 class="text-3xl font-black text-red-500"><?php echo $stats['suspended']; ?></h3>
        <p class="text-sm text-slate-500 font-semibold">Suspended</p>
    </div>
    <div class="glass p-6 rounded-[24px] border border-slate-200 dark:border-slate-800 border-l-4 border-l-[#FF6600]">
        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($stats['total_fines']); ?> RWF</h3>
        <p class="text-sm text-slate-500 font-semibold">Total Owed Fines</p>
    </div>
</div>

<div class="flex flex-col sm:flex-row justify-between items-center gap-4 glass p-4 rounded-2xl border border-slate-200 dark:border-slate-800 mb-6">
    <div class="relative w-full sm:w-96">
        <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl'></i>
        <input type="text" id="searchInput" onkeyup="searchMembers()" placeholder="Search members by name..." class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl py-2.5 pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-[#FF6600] text-slate-800 dark:text-white">
    </div>
    <button onclick="toggleModal('addMemberModal')" class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-[#FF6600] to-orange-500 text-white font-bold rounded-xl shadow-lg hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
        <i class='bx bx-user-plus'></i> Register Member
    </button>
</div>

<div class="glass rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="membersTable">
            <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] uppercase font-black text-slate-500 tracking-widest">
                <tr>
                    <th class="p-4 pl-6">Member Profile</th>
                    <th class="p-4">Contact Info</th>
                    <th class="p-4">Library Status</th>
                    <th class="p-4 text-right">Owed Fines</th>
                    <th class="p-4 text-center pr-6">Manage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                <?php if($members): foreach ($members as $member): 
                    $is_suspended = ($member['library_status'] === 'suspended'); 
                ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="p-4 pl-6">
                            <p class="font-black text-slate-900 dark:text-white"><?php echo htmlspecialchars($member['full_name']); ?></p>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-[#FF6600] font-black uppercase tracking-wider">
                                <?php echo htmlspecialchars($member['role']); ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-500 font-medium">
                            <?php echo htmlspecialchars($member['email']); ?><br>
                            <?php if($member['is_activated'] == 0): ?>
                                <span class='inline-block mt-1 text-[9px] font-black bg-yellow-100 dark:bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 px-2 py-0.5 rounded uppercase tracking-wider'>
                                    <i class='bx bx-time-five'></i> Pending Activation
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $is_suspended ? 'bg-red-100 dark:bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-green-100 dark:bg-green-500/10 text-green-600 dark:text-green-400'; ?>">
                                <?php echo $is_suspended ? 'Suspended' : 'Active'; ?>
                            </span>
                        </td>
                        <td class="p-4 text-right font-black <?php echo ($member['total_fines'] > 0) ? 'text-red-500' : 'text-slate-500 dark:text-slate-400'; ?>">
                            <?php echo number_format($member['total_fines']); ?> RWF
                        </td>
                        <td class="p-4 pr-6 text-center space-x-1">
                            <form method="POST" class="inline" onsubmit="return confirm('Toggle this member\'s access?');">
                                <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $is_suspended ? 'active' : 'suspended'; ?>">
                                <button type="submit" name="toggle_status" class="px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-colors <?php echo $is_suspended ? 'bg-green-100 dark:bg-green-500/10 text-green-600 hover:bg-green-200' : 'bg-red-100 dark:bg-red-500/10 text-red-600 hover:bg-red-200'; ?>">
                                    <?php echo $is_suspended ? 'Restore' : 'Suspend'; ?>
                                </button>
                            </form>
                            
                            <?php if($member['total_fines'] > 0): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Confirm payment received?');">
                                <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                <button type="submit" name="clear_fines" class="px-3 py-1.5 rounded-lg bg-[#FF6600]/10 text-[#FF6600] text-xs font-bold shadow-sm hover:bg-[#FF6600] hover:text-white transition-colors">
                                    Clear Fines
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No members found in the system.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addMemberModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay">
    <div class="modal-content w-full max-w-md bg-white dark:bg-[#0f172a] rounded-[24px] shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden relative">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-[#020617]/50">
            <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2"><i class='bx bx-user-plus text-[#FF6600]'></i> Register Member</h3>
            <button onclick="toggleModal('addMemberModal')" class="text-slate-500 hover:text-red-500 transition-colors"><i class='bx bx-x text-xl'></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Full Name</label>
                <input type="text" name="full_name" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600]">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Role</label>
                <select name="role" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600]">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-[#FF6600]">
            </div>
            
            <div class="pt-4 flex justify-end gap-3 mt-6 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="toggleModal('addMemberModal')" class="px-5 py-2.5 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                <button type="submit" name="add_member" class="px-6 py-2.5 bg-[#FF6600] text-white font-bold rounded-xl shadow-lg shadow-orange-500/30 hover:bg-orange-600 transition-colors">Send Invite</button>
            </div>
        </form>
    </div>
</div>

<script>
    function searchMembers() {
        let input = document.getElementById("searchInput").value.toUpperCase();
        let tr = document.getElementById("membersTable").getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) { 
            let tdName = tr[i].getElementsByTagName("td")[0]; 
            if (tdName) {
                if (tdName.textContent.toUpperCase().indexOf(input) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>

<?php require '../components/footer.php'; ?>