<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';
$user_id = $_SESSION['user_id'];

// 1. Syllabus Completion
$stmt = $pdo->prepare("
    SELECT s.name, s.code,
           LEAST(100, (SUM(CASE WHEN sa.activity_type = 'view_note' THEN 10 ELSE 0 END) +
            SUM(CASE WHEN sa.activity_type = 'complete_assignment' THEN 25 ELSE 0 END) +
            SUM(CASE WHEN sa.activity_type = 'study_time_minutes' THEN sa.activity_value/5 ELSE 0 END))) as completion
    FROM subjects s
    LEFT JOIN student_activity sa ON s.id = sa.subject_id AND sa.user_id = :uid
    WHERE s.type = 'theory'
    GROUP BY s.id
");
$stmt->execute(['uid' => $user_id]);
$syllabus_data = $stmt->fetchAll();

// 2. Upcoming deadlines for timeline
$stmt = $pdo->prepare("
    SELECT a.*, s.name as subject_name, DATEDIFF(a.deadline, NOW()) as days_left
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.deadline >= DATE(NOW())
    ORDER BY a.deadline ASC
    LIMIT 10
");
$stmt->execute();
$timeline_data = $stmt->fetchAll();

$colors = ['blue', 'indigo', 'emerald', 'orange', 'purple', 'rose'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Study Planner - ClassConnecto</title>
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
        <header class="mb-8 flex justify-between items-center bg-white/50 dark:bg-slate-900/50 p-6 rounded-2xl glass shadow-sm border border-gray-100 dark:border-gray-800">
             <div class="flex items-center gap-4">
                 <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(168,85,247,0.4)]">
                    <i class="fa-solid fa-calendar-check text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-600">Smart Study Planner</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1 font-medium">AI-generated schedule based on your deadlines and mastery data.</p>
                </div>
            </div>
            
            <button class="bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-purple-200 transition shadow-sm">
                <i class="fa-solid fa-arrows-rotate"></i> Re-generate Plan
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Overall Subject Progress Tracking -->
            <div class="lg:col-span-1 space-y-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bars-progress text-blue-500"></i> Syllabus Completion
                </h3>
                
                <?php foreach($syllabus_data as $index => $sub): 
                    $comp = min(100, max(0, round($sub['completion'])));
                    $c = $colors[$index % count($colors)];
                    
                    $warnMsg = '';
                    if($comp < 15) {
                        $warnMsg = '<p class="text-[10px] text-red-500 mt-2 font-medium">⚠️ Critical: Just Started</p>';
                    }
                ?>
                <div class="glass p-4 rounded-xl shadow-sm border border-<?= $c ?>-100 dark:border-<?= $c ?>-900/40 hover:scale-[1.02] transition-transform">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-sm text-gray-800 dark:text-white truncate" title="<?= htmlspecialchars($sub['name']) ?>"><?= htmlspecialchars($sub['name']) ?></span>
                        <span class="text-xs font-bold text-<?= $c ?>-600 dark:text-<?= $c ?>-400"><?= $comp ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="bg-<?= $c ?>-500 h-2 rounded-full transition-all duration-1000" style="width: <?= $comp ?>%"></div>
                    </div>
                    <?= $warnMsg ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right: Timeline / Calendar view -->
            <div class="lg:col-span-2">
                 <div class="glass p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                     <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-timeline text-indigo-500"></i> Upcoming Deadlines</h3>
                        <div class="flex gap-2">
                            <span class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer transition-colors"><i class="fa-solid fa-chevron-left"></i></span>
                            <span class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer transition-colors"><i class="fa-solid fa-chevron-right"></i></span>
                        </div>
                     </div>

                     <!-- Timeline list -->
                     <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-indigo-500 before:via-purple-300 pt-4 dark:before:from-indigo-900 dark:before:via-purple-900 before:to-transparent">
                          
                          <?php if(empty($timeline_data)): ?>
                              <div class="text-center text-gray-500 italic relative z-10 bg-white dark:bg-slate-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700 w-max mx-auto shadow-sm">No upcoming deadlines found. You are all caught up!</div>
                          <?php endif; ?>

                          <?php foreach($timeline_data as $index => $t): 
                              $daysLeft = $t['days_left'];
                              $isToday = $daysLeft == 0;
                              $isUrgent = $daysLeft <= 2;
                              
                              $dateStr = date('M d', strtotime($t['deadline']));
                              if($isToday) $dateStr .= ' (Today)';
                              else if($daysLeft == 1) $dateStr .= ' (Tomorrow)';

                              $iconBg = $isUrgent ? 'bg-red-500' : 'bg-indigo-500';
                              $cardBorder = $isUrgent ? 'border-red-200 dark:border-red-900/50' : 'border-indigo-200 dark:border-indigo-900/50';
                              $cardBg = $isUrgent ? 'bg-red-50/50 dark:bg-red-900/20' : 'glass dark:bg-slate-800/80';
                              $textColor = $isUrgent ? 'text-red-700 dark:text-red-400' : 'text-gray-800 dark:text-gray-200';
                          ?>
                          <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group <?= $isToday ? 'is-active' : '' ?>">
                               <!-- Icon -->
                               <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white dark:border-[#0f172a] <?= $iconBg ?> text-white shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 shadow-lg z-10 text-xs font-bold">
                                   <?= date('d', strtotime($t['deadline'])) ?>
                               </div>
                               <!-- Card -->
                               <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] <?= $cardBg ?> p-4 rounded-xl border <?= $cardBorder ?> shadow-sm hover:-translate-y-1 transition-transform cursor-default">
                                   <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-bold <?= $textColor ?> text-sm"><?= $dateStr ?></h4>
                                        <span class="bg-gray-100 dark:bg-gray-800 text-gray-500 text-[10px] px-2 py-1 rounded border border-gray-200 dark:border-gray-700 shadow-sm">Focus: <?= htmlspecialchars($t['subject_name']) ?></span>
                                   </div>
                                    <?php if($isUrgent): ?>
                                        <span class="inline-block bg-red-100 text-red-700 text-[10px] px-2 py-0.5 rounded font-bold border border-red-200 mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Upcoming Deadline!</span>
                                    <?php endif; ?>
                                   <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 mt-1">
                                       <li class="flex items-start gap-2 <?= $isUrgent ? 'font-medium' : '' ?>">
                                           <i class="fa-solid fa-thumbtack mt-1 <?= $isUrgent ? 'text-red-400' : 'text-indigo-400' ?>"></i> <?= htmlspecialchars($t['title']) ?>
                                       </li>
                                   </ul>
                               </div>
                          </div>
                          <?php endforeach; ?>

                     </div>
                 </div>
            </div>
        </div>
        <br><br><br>
    </main>

    <!-- AI Panel -->
    <?php include 'components/ai_assistant.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
