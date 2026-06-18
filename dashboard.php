<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db/connection.php';
$user_id = $_SESSION['user_id'];

// Get next deadline
$stmt = $pdo->prepare("
    SELECT a.title, s.name as subject_name, a.deadline, 
    DATEDIFF(a.deadline, NOW()) as days_left,
    TIMESTAMPDIFF(HOUR, NOW(), a.deadline) % 24 as hours_left
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.id 
    WHERE a.deadline > NOW() 
    ORDER BY a.deadline ASC 
    LIMIT 1
");
$stmt->execute();
$next_assignment = $stmt->fetch();

// Get study focus
$stmt = $pdo->prepare("
    SELECT a.title, s.name as subject_name
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.id 
    WHERE a.deadline > NOW() 
    ORDER BY a.deadline ASC 
    LIMIT 2
");
$stmt->execute();
$study_focus = $stmt->fetchAll();

// Get recent program activity
$stmt = $pdo->prepare("
    SELECT p.title, s.name as subject_name, s.code, p.id
    FROM student_activity sa
    JOIN subjects s ON sa.subject_id = s.id
    LEFT JOIN programs p ON p.subject_id = s.id
    WHERE sa.user_id = :uid AND sa.activity_type = 'practice_program'
    ORDER BY sa.created_at DESC LIMIT 1
");
$stmt->execute(['uid' => $user_id]);
$resume_work = $stmt->fetch();

// Get user details
$bg_gradient = $_SESSION['role'] === 'admin' ? 'from-red-500 to-orange-600' : 'from-blue-600 to-indigo-600';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ClassConnecto</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Three.js for 3D Graph -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- OrbitControls -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
</head>
<body class="flex  transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Sidebar Component -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Workspace Area -->
    <main class="ml-64 flex-1 h-screen overflow-y-auto relative z-10 w-full pl-8 pr-8 pt-6">
        
        <!-- Header / Search -->
        <header class="glass rounded-2xl p-4 mb-6 flex justify-between items-center shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Welcome back, <?= explode(' ', $_SESSION['full_name'])[0] ?>! 👋</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Here is your academic universe today.</p>
            </div>
            
            <div class="w-96">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </div>
                    <input type="text" class="input-glass pl-10" placeholder="Search notes, concepts, assignments... (Ctrl+K)">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-xs font-semibold text-gray-400 border border-gray-300 dark:border-gray-600 rounded px-1">⌘K</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- 3D Graph Container Placeholder (Behind the main content via CSS or rendered as part of layout?) -->
        <!-- We will put the 3D canvas inside this visible block to act as the "Notion Workspace Header" -->
        <div class="w-full h-96 glass rounded-2xl mb-8 relative overflow-hidden shadow-sm group">
            <div id="canvas-container" class="absolute inset-0 cursor-move"></div>
            
            <div class="absolute inset-0 pointer-events-none bg-gradient-to-t from-white/80 dark:from-slate-900/80 to-transparent flex flex-col justify-end p-6 z-10">
                 <div class="flex items-center gap-3">
                     <span class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-xs font-bold rounded-full border border-blue-200 dark:border-blue-800 backdrop-blur-md">Interactive Node Map</span>
                     <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">Drag to rotate • Scroll to zoom • Click nodes to view subject</p>
                 </div>
            </div>
        </div>

        <!-- Dashboard Cards Layout -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Assignment Deadline -->
            <div class="glass dark:bg-slate-800/80 p-6 rounded-2xl hover-lift shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-red-400/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                <div class="flex justify-between items-start mb-4">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-stopwatch w-4 text-red-500"></i> Next Deadline
                    </h3>
                    <span class="animate-pulse bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full font-bold">Soon</span>
                </div>
                <?php if($next_assignment): ?>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white truncate" title="<?= htmlspecialchars($next_assignment['title']) ?>"><?= htmlspecialchars($next_assignment['title']) ?></h4>
                <p class="text-sm text-gray-500 mt-1 mb-4"><?= htmlspecialchars($next_assignment['subject_name']) ?></p>
                <div class="flex gap-2">
                    <div class="bg-white dark:bg-gray-800 rounded px-2 py-1 text-center shadow-sm w-12 border border-red-100 dark:border-red-900/50 text-red-600 dark:text-red-400">
                        <span class="block text-xl font-bold"><?= str_pad(max(0, $next_assignment['days_left']), 2, '0', STR_PAD_LEFT) ?></span><span class="text-[10px] uppercase text-red-400 dark:text-red-500">Days</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded px-2 py-1 text-center shadow-sm w-12 border border-red-100 dark:border-red-900/50 text-red-600 dark:text-red-400">
                        <span class="block text-xl font-bold"><?= str_pad(max(0, $next_assignment['hours_left']), 2, '0', STR_PAD_LEFT) ?></span><span class="text-[10px] uppercase text-red-400 dark:text-red-500">Hrs</span>
                    </div>
                </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic mt-6">No upcoming deadlines! 🎉</p>
                <?php endif; ?>
            </div>

            <!-- AI Study Planner -->
            <div class="glass dark:bg-slate-800/80 p-6 rounded-2xl hover-lift shadow-sm relative overflow-hidden">
                 <div class="absolute top-0 right-0 w-24 h-24 bg-purple-400/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                 <div class="flex justify-between items-start mb-4">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check w-4 text-purple-500"></i> Study Focus Today
                    </h3>
                </div>
                <ul class="space-y-3">
                    <?php if(count($study_focus) > 0): ?>
                        <?php foreach($study_focus as $index => $focus): ?>
                        <li class="flex items-center gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full <?= $index % 2 == 0 ? 'bg-purple-500' : 'bg-blue-500' ?>"></div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium flex-1 truncate" title="<?= htmlspecialchars($focus['title']) ?>"><?= htmlspecialchars($focus['title']) ?></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">Todo</span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-xs text-gray-500 italic">All caught up!</li>
                    <?php endif; ?>
                </ul>
                <a href="planner.php" class="block mt-4 w-full text-center text-xs font-semibold text-purple-600 hover:text-purple-700 bg-purple-50 dark:bg-purple-900/20 py-2 rounded-lg transition-colors">
                    View Full Plan <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>

            <!-- Quick Access / Resume -->
            <div class="glass dark:bg-slate-800/80 p-6 rounded-2xl hover-lift shadow-sm relative overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 w-24 h-24 bg-green-400/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                <div class="flex justify-between items-start mb-4">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left w-4 text-green-500"></i> Resume Work
                    </h3>
                </div>
                
                <?php if($resume_work && isset($resume_work['title'])): ?>
                <a href="lab.php?id=<?= $resume_work['code'] ?>" class="block bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-3 hover:border-green-300 dark:hover:border-green-600 transition-colors group mt-auto">
                    <div class="flex justify-between items-center">
                        <div class="w-5/6">
                            <p class="text-xs text-green-600 dark:text-green-400 font-bold mb-1">PROGRAM</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" title="<?= htmlspecialchars($resume_work['title']) ?>"><?= htmlspecialchars($resume_work['title']) ?></p>
                            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($resume_work['subject_name']) ?></p>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 group-hover:bg-green-100 group-hover:text-green-600 transition-colors shrink-0">
                            <i class="fa-solid fa-play text-xs"></i>
                        </div>
                    </div>
                </a>
                <?php else: ?>
                 <div class="flex-1 flex flex-col items-center justify-center text-center opacity-50 mt-2">
                      <i class="fa-solid fa-ghost text-2xl text-gray-400 mb-2"></i>
                      <p class="text-xs font-medium text-gray-500">No recent activity detected.</p>
                 </div>
                <?php endif; ?>
            </div>
        </div>
        <br><br><br> <!-- Bottom spacing for smooth scroll -->
    </main>

    <!-- AI Assistant Panel Component -->
    <?php include 'components/ai_assistant.php'; ?>

    <!-- Toggle Dark Mode (Moved to Sidebar) -->

    <script src="assets/js/main.js"></script>
    <script src="assets/js/three_nodes.js"></script>
</body>
</html>
