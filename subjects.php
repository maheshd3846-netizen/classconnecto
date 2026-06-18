<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';

$is_authorized = in_array($_SESSION['role'], ['admin', 'faculty', 'cr']);

// Handle Subject Creation for Authorized Users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_authorized && isset($_POST['create_subject'])) {
    $name = $_POST['name'];
    $code = strtoupper($_POST['code']);
    $type = $_POST['type'];
    $theory_link = !empty($_POST['theory_subject_id']) ? (int)$_POST['theory_subject_id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO subjects (name, code, type, theory_subject_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $code, $type, $theory_link]);
    header("Location: subjects.php");
    exit();
}

// Handle Subject Deletion for Authorized Users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_authorized && isset($_POST['delete_subject'])) {
    $subject_id = (int)$_POST['subject_id'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    header("Location: subjects.php");
    exit();
}

// Fetch lists
$stmt = $pdo->query("SELECT * FROM subjects WHERE type = 'theory' ORDER BY name ASC");
$theory_subjects = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM subjects WHERE type = 'lab' ORDER BY name ASC");
$lab_subjects = $stmt->fetchAll();

$colorMap = [
    ['icon' => 'bg-blue-500', 'border' => 'border-blue-200 dark:border-blue-900/50', 'text' => 'group-hover:text-blue-600 dark:group-hover:text-blue-400', 'ring' => 'bg-blue-50/20 dark:bg-blue-900/10'],
    ['icon' => 'bg-purple-500', 'border' => 'border-purple-200 dark:border-purple-900/50', 'text' => 'group-hover:text-purple-600 dark:group-hover:text-purple-400', 'ring' => 'bg-purple-50/20 dark:bg-purple-900/10'],
    ['icon' => 'bg-emerald-500', 'border' => 'border-emerald-200 dark:border-emerald-900/50', 'text' => 'group-hover:text-emerald-600 dark:group-hover:text-emerald-400', 'ring' => 'bg-emerald-50/20 dark:bg-emerald-900/10'],
    ['icon' => 'bg-orange-500', 'border' => 'border-orange-200 dark:border-orange-900/50', 'text' => 'group-hover:text-orange-600 dark:group-hover:text-orange-400', 'ring' => 'bg-orange-50/20 dark:bg-orange-900/10'],
    ['icon' => 'bg-rose-500', 'border' => 'border-rose-200 dark:border-rose-900/50', 'text' => 'group-hover:text-rose-600 dark:group-hover:text-rose-400', 'ring' => 'bg-rose-50/20 dark:bg-rose-900/10']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Subjects - ClassConnecto</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex transition-colors duration-300">

    <?php include 'components/background.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto w-full p-8 relative z-10">
        <header class="mb-8 flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Your Subjects</h1>
                    <?php if($is_authorized): ?>
                         <button onclick="document.getElementById('modal-add-subject').classList.remove('hidden')" class="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 hover:bg-blue-200 text-sm font-bold py-1.5 px-3 rounded-lg shadow-sm transition"><i class="fa-solid fa-plus mr-1"></i> Create Subject</button>
                    <?php endif; ?>
                </div>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Access all your enrolled theory and lab subjects.</p>
            </div>
            
        </header>

        <!-- Theory Subjects Section -->
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2 border-b-2 border-blue-500 inline-block pb-1"><i class="fa-solid fa-book-open text-blue-500"></i> Theory Subjects</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
            <?php foreach($theory_subjects as $sub): 
                $c = $colorMap[$sub['id'] % count($colorMap)];
                $link = "subject.php?id={$sub['code']}";
            ?>
            <div class="relative group">
                <a href="<?= $link ?>" class="block glass p-6 rounded-2xl hover-lift shadow-sm h-full border <?= $c['border'] ?> <?= $c['ring'] ?>">
                    <div class="w-12 h-12 rounded-xl <?= $c['icon'] ?> text-white flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-md">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white <?= $c['text'] ?> transition-colors truncate" title="<?= htmlspecialchars($sub['name']) ?>"><?= htmlspecialchars($sub['name']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($sub['code']) ?></p>
                    <div class="mt-4 flex items-center gap-2">
                         <span class="bg-white/50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs px-2 py-1 rounded font-bold border border-gray-200 dark:border-gray-700 shadow-sm">Theory</span>
                         <span class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 text-xs px-2 py-1 rounded font-bold">Actively Learning</span>
                    </div>
                </a>
                <?php if($is_authorized): ?>
                <form method="POST" class="absolute top-4 right-4 z-10" onsubmit="return confirm('Are you sure you want to delete this subject? All associated notes, assignments, and doubts will be permanently removed.');">
                    <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                    <button type="submit" name="delete_subject" class="w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm opacity-0 group-hover:opacity-100">
                        <i class="fa-solid fa-trash text-sm"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Lab Subjects Section -->
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2 border-b-2 border-emerald-500 inline-block pb-1"><i class="fa-solid fa-code text-emerald-500"></i> Practical Labs</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach($lab_subjects as $sub): 
                $c = $colorMap[$sub['id'] % count($colorMap)];
                $link = "lab.php?id={$sub['code']}";
            ?>
            <div class="relative group">
                <a href="<?= $link ?>" class="block glass p-6 rounded-2xl hover-lift shadow-sm h-full border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform shadow-md shadow-emerald-500/30">
                        <i class="fa-solid fa-flask text-sm -mr-2 mt-2 border-r border-white/30 pr-1"></i><i class="fa-solid fa-code ml-1 mt-0.5 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate" title="<?= htmlspecialchars($sub['name']) ?>"><?= htmlspecialchars($sub['name']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($sub['code']) ?></p>
                    <div class="mt-4 flex items-center gap-2">
                         <span class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-xs px-2 py-1 rounded font-bold border border-emerald-100 dark:border-emerald-900/50 shadow-sm">Lab</span>
                         <?php if($sub['theory_subject_id']): ?>
                            <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs px-2 py-1 rounded font-bold border border-indigo-100 dark:border-indigo-800/50 shadow-sm"><i class="fa-solid fa-link"></i> Linked</span>
                         <?php endif; ?>
                    </div>
                </a>
                <?php if($is_authorized): ?>
                <form method="POST" class="absolute top-4 right-4 z-10" onsubmit="return confirm('Are you sure you want to delete this lab subject? All associated programs will be permanently removed.');">
                    <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                    <button type="submit" name="delete_subject" class="w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm opacity-0 group-hover:opacity-100">
                        <i class="fa-solid fa-trash text-sm"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <br><br><br>
    </main>

    <!-- AI Panel -->
    <?php include 'components/ai_assistant.php'; ?>
    
    <?php if($is_authorized): ?>
    <!-- Add Subject Modal -->
    <div id="modal-add-subject" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="glass p-6 rounded-2xl w-full max-w-md relative border border-blue-200 dark:border-blue-900/50">
             <button onclick="document.getElementById('modal-add-subject').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-folder-plus text-blue-500 mr-2"></i> Create New Subject</h3>
             <form method="POST">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Subject Name</label>
                         <input type="text" name="name" required class="w-full input-glass bg-white dark:bg-gray-800" placeholder="e.g. Artificial Intelligence">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Subject Code</label>
                         <input type="text" name="code" required class="w-full input-glass bg-white dark:bg-gray-800 uppercase" placeholder="e.g. AI">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Type</label>
                         <select name="type" required class="w-full input-glass bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300" onchange="document.getElementById('theory-link').style.display = this.value === 'lab' ? 'block' : 'none'">
                             <option value="theory">Theory Subject</option>
                             <option value="lab">Practical Lab</option>
                         </select>
                     </div>
                     <div id="theory-link" style="display:none;">
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Link to Theory (Optional)</label>
                         <select name="theory_subject_id" class="w-full input-glass bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                             <option value="">-- None --</option>
                             <?php foreach($theory_subjects as $ts): ?>
                             <option value="<?= $ts['id'] ?>"><?= htmlspecialchars($ts['code']) ?> - <?= htmlspecialchars($ts['name']) ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                     <button type="submit" name="create_subject" class="w-full btn-primary font-bold py-2 rounded-lg mt-4 shadow-md transition-colors"><i class="fa-solid fa-plus"></i> Add Subject</button>
                 </div>
             </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Theme Toggle -->
    <button id="theme-toggle" class="fixed bottom-5 left-64 ml-5 w-10 h-10 rounded-full glass flex items-center justify-center text-gray-600 hover:text-blue-600 transition-colors z-50 shadow-sm border border-gray-200 dark:border-gray-700">
        <i class="fa-solid fa-moon"></i>
    </button>
    <script src="assets/js/main.js"></script>
</body>
</html>
