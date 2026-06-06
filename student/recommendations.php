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

// Get user's academic information
$user_info = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
} catch (PDOException $e) {}

// Get department-based recommendations
$recommendations = [];
$department_books = [];
$level_books = [];

if (!empty($user_info['academic_department'])) {
    try {
        // Department-specific books
        $deptQuery = $pdo->prepare("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity,
                   (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating
            FROM books b 
            WHERE b.category LIKE ? OR b.subject LIKE ? OR b.description LIKE ?
            ORDER BY popularity DESC, avg_rating DESC, b.title ASC 
            LIMIT 10
        ");
        $deptQuery->execute([
            '%' . $user_info['academic_department'] . '%',
            '%' . $user_info['academic_department'] . '%',
            '%' . $user_info['academic_department'] . '%'
        ]);
        $department_books = $deptQuery->fetchAll();
    } catch (PDOException $e) {}
}

if (!empty($user_info['academic_level'])) {
    try {
        // Level-appropriate books
        $levelQuery = $pdo->prepare("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id) as popularity,
                   (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating
            FROM books b 
            WHERE b.difficulty_level = ? OR b.target_audience LIKE ?
            ORDER BY popularity DESC, avg_rating DESC, b.title ASC 
            LIMIT 8
        ");
        $levelQuery->execute([
            $user_info['academic_level'],
            '%' . $user_info['academic_level'] . '%'
        ]);
        $level_books = $levelQuery->fetchAll();
    } catch (PDOException $e) {}
}

// Get trending books in library
$trending_books = [];
try {
    $trendingQuery = $pdo->prepare("
        SELECT b.*, 
               (SELECT COUNT(*) FROM borrowings WHERE book_id = b.id AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_popularity,
               (SELECT AVG(rating) FROM book_reviews WHERE book_id = b.id) as avg_rating
        FROM books b 
        WHERE b.available_copies > 0
        ORDER BY recent_popularity DESC, avg_rating DESC, b.title ASC 
        LIMIT 6
    ");
    $trendingQuery->execute();
    $trending_books = $trendingQuery->fetchAll();
} catch (PDOException $e) {}

// Combine and deduplicate recommendations
$all_recommendations = array_merge($department_books, $level_books, $trending_books);
$seen_ids = [];
$recommendations = [];

foreach ($all_recommendations as $book) {
    if (!in_array($book['id'], $seen_ids)) {
        $seen_ids[] = $book['id'];
        $recommendations[] = $book;
    }
}

$page_title = "Book Recommendations | NGA Student";
$header_title = "Personalized Book Recommendations";
require '../components/header.php'; 
?>

<!-- Academic Profile Banner -->
<?php if (!empty($user_info['academic_department'])): ?>
<div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-2xl p-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-2xl flex items-center justify-center">
            <i class='bx bx-brain text-white text-2xl'></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-purple-900">AI-Powered Recommendations</h2>
            <p class="text-purple-700">
                Personalized for 
                <strong><?php echo htmlspecialchars($user_info['academic_department']); ?></strong> 
                <?php if (!empty($user_info['academic_level'])): ?>
                    - <?php echo htmlspecialchars($user_info['academic_level']); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 border-b border-slate-200 dark:border-slate-700">
    <button onclick="showCategory('all')" id="tab-all" class="px-4 py-2 font-semibold text-blue-600 border-b-2 border-blue-600 transition-colors">
        All Recommendations
    </button>
    <?php if (!empty($user_info['academic_department'])): ?>
        <button onclick="showCategory('department')" id="tab-department" class="px-4 py-2 font-semibold text-slate-600 hover:text-blue-600 transition-colors">
            <i class='bx bx-building'></i> Your Department
        </button>
    <?php endif; ?>
    <?php if (!empty($user_info['academic_level'])): ?>
        <button onclick="showCategory('level')" id="tab-level" class="px-4 py-2 font-semibold text-slate-600 hover:text-blue-600 transition-colors">
            <i class='bx bx-graduation'></i> Your Level
        </button>
    <?php endif; ?>
    <button onclick="showCategory('trending')" id="tab-trending" class="px-4 py-2 font-semibold text-slate-600 hover:text-blue-600 transition-colors">
        <i class='bx bx-trending-up'></i> Trending
    </button>
</div>

<!-- All Recommendations -->
<div id="category-all" class="recommendation-category">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (count($recommendations) > 0): ?>
            <?php foreach (array_slice($recommendations, 0, 12) as $book): ?>
                <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-4 hover:shadow-lg transition-all duration-300 group cursor-pointer">
                    <div class="relative mb-4">
                        <div class="w-full h-48 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                            <?php if ($book['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover rounded-xl">
                            <?php else: ?>
                                <i class='bx bx-book text-white text-4xl'></i>
                            <?php endif; ?>
                        </div>
                        <?php if ($book['avg_rating']): ?>
                            <div class="absolute top-2 right-2 bg-white/90 dark:bg-slate-900/90 px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class='bx bx-star text-yellow-500 text-sm'></i>
                                <span class="text-xs font-bold"><?php echo number_format($book['avg_rating'], 1); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($book['recent_popularity'] > 5): ?>
                            <div class="absolute top-2 left-2 bg-orange-500 text-white px-2 py-1 rounded-lg text-xs font-bold">
                                <i class='bx bx-fire'></i> Hot
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="font-bold text-slate-900 dark:text-white mb-1 group-hover:text-blue-600 transition-colors">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">
                        by <?php echo htmlspecialchars($book['author']); ?>
                    </p>
                    
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-slate-500">
                            <?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> available
                        </span>
                        <div class="flex items-center gap-1">
                            <?php if ($book['popularity'] > 0): ?>
                                <i class='bx bx-book-reader text-blue-500 text-sm'></i>
                                <span class="text-xs text-slate-500"><?php echo $book['popularity']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button onclick="borrowBook(<?php echo $book['id']; ?>)" 
                            class="w-full px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-300 flex items-center justify-center gap-2">
                        <i class='bx bx-book-plus'></i>
                        Borrow Now
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class='bx bx-book-open text-4xl text-slate-400'></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No recommendations yet</h3>
                <p class="text-slate-500 mb-4">
                    <?php if (empty($user_info['academic_department'])): ?>
                        Set your academic department to get personalized recommendations
                    <?php else: ?>
                        We're finding the perfect books for you...
                    <?php endif; ?>
                </p>
                <a href="academic_profile.php" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <i class='bx bx-user-voice'></i>
                    Update Academic Profile
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Department Recommendations -->
<?php if (!empty($user_info['academic_department'])): ?>
<div id="category-department" class="recommendation-category hidden">
    <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 rounded-xl">
        <h3 class="font-bold text-purple-900 dark:text-purple-100 mb-2">
            <i class='bx bx-building'></i> Books for <?php echo htmlspecialchars($user_info['academic_department']); ?>
        </h3>
        <p class="text-purple-700 dark:text-purple-300 text-sm">
            Curated recommendations based on your department of study
        </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach (array_slice($department_books, 0, 9) as $book): ?>
            <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-4 hover:shadow-lg transition-all duration-300">
                <div class="w-full h-40 bg-gradient-to-br from-purple-400 to-blue-500 rounded-xl flex items-center justify-center mb-4">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover rounded-xl">
                    <?php else: ?>
                        <i class='bx bx-book text-white text-3xl'></i>
                    <?php endif; ?>
                </div>
                <h4 class="font-bold text-slate-900 dark:text-white mb-1"><?php echo htmlspecialchars($book['title']); ?></h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><?php echo htmlspecialchars($book['author']); ?></p>
                <button onclick="borrowBook(<?php echo $book['id']; ?>)" class="w-full px-4 py-2 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors">
                    Borrow Book
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Level Recommendations -->
<?php if (!empty($user_info['academic_level'])): ?>
<div id="category-level" class="recommendation-category hidden">
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 rounded-xl">
        <h3 class="font-bold text-green-900 dark:text-green-100 mb-2">
            <i class='bx bx-graduation'></i> Books for <?php echo htmlspecialchars($user_info['academic_level']); ?>
        </h3>
        <p class="text-green-700 dark:text-green-300 text-sm">
            Age-appropriate and difficulty-matched recommendations
        </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach (array_slice($level_books, 0, 9) as $book): ?>
            <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-4 hover:shadow-lg transition-all duration-300">
                <div class="w-full h-40 bg-gradient-to-br from-green-400 to-blue-500 rounded-xl flex items-center justify-center mb-4">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover rounded-xl">
                    <?php else: ?>
                        <i class='bx bx-book text-white text-3xl'></i>
                    <?php endif; ?>
                </div>
                <h4 class="font-bold text-slate-900 dark:text-white mb-1"><?php echo htmlspecialchars($book['title']); ?></h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><?php echo htmlspecialchars($book['author']); ?></p>
                <button onclick="borrowBook(<?php echo $book['id']; ?>)" class="w-full px-4 py-2 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-colors">
                    Borrow Book
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif;
 Trending Books 
<div id="category-trending" class="recommendation-category hidden">
    <div class="mb-6 p-4 bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl">
        <h3 class="font-bold text-orange-900 dark:text-orange-100 mb-2">
            <i class='bx bx-trending-up'></i> Trending This Month
        </h3>
        <p class="text-orange-700 dark:text-orange-300 text-sm">
            Most popular books in the library right now
        </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($trending_books as $book): ?>
            <div class="glass rounded-[24px] border border-slate-200/50 dark:border-slate-800/50 p-4 hover:shadow-lg transition-all duration-300">
                <div class="w-full h-40 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center mb-4 relative">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover rounded-xl">
                    <?php else: ?>
                        <i class='bx bx-book text-white text-3xl'></i>
                    <?php endif; ?>
                    <div class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded-lg text-xs font-bold">
                        <i class='bx bx-fire'></i> <?php echo $book['recent_popularity']; ?>
                    </div>
                </div>
                <h4 class="font-bold text-slate-900 dark:text-white mb-1"><?php echo htmlspecialchars($book['title']); ?></h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3"><?php echo htmlspecialchars($book['author']); ?></p>
                <button onclick="borrowBook(<?php echo $book['id']; ?>)" class="w-full px-4 py-2 bg-orange-600 text-white rounded-xl font-semibold hover:bg-orange-700 transition-colors">
                    Borrow Book
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function showCategory(category) {
    // Hide all categories
    document.querySelectorAll('.recommendation-category').forEach(cat => {
        cat.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(tab => {
        tab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        tab.classList.add('text-slate-600');
    });
    
    // Show selected category
    document.getElementById('category-' + category).classList.remove('hidden');
    
    // Add active state to selected tab
    const activeTab = document.getElementById('tab-' + category);
    activeTab.classList.remove('text-slate-600');
    activeTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
}

function borrowBook(bookId) {
    // This would open a borrow modal or navigate to borrow page
    alert('Borrow functionality coming soon! Book ID: ' + bookId);
}

// Show all recommendations by default
showCategory('all');
</script>

<?php require '../components/footer.php'; ?>
