<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';
$user_id = $_SESSION['user_id'];

// Get aggregate stats
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN activity_type = 'study_time_minutes' THEN activity_value ELSE 0 END) as total_study_minutes,
        SUM(CASE WHEN activity_type = 'complete_assignment' THEN 1 ELSE 0 END) as assignments_completed,
        SUM(CASE WHEN activity_type = 'practice_program' THEN 1 ELSE 0 END) as programs_practiced,
        COUNT(DISTINCT DATE(created_at)) as streak_days
    FROM student_activity WHERE user_id = :uid
");
$stmt->execute(['uid' => $user_id]);
$stats = $stmt->fetch();

$study_hours = round(($stats['total_study_minutes'] ?? 0) / 60, 1);
$assignments_completed = $stats['assignments_completed'] ?? 0;
$programs_practiced = $stats['programs_practiced'] ?? 0;
// Note: real streak calculation is more complex involving consecutive dates.
$streak = max(1, $stats['streak_days'] ?? 0);

// Get Subject Mastery Radar Data
$stmt = $pdo->prepare("
    SELECT s.name, s.code,
           SUM(CASE WHEN sa.activity_type = 'view_note' THEN 5 ELSE 0 END) +
           SUM(CASE WHEN sa.activity_type = 'complete_assignment' THEN 30 ELSE 0 END) +
           SUM(CASE WHEN sa.activity_type = 'practice_program' THEN 20 ELSE 0 END) +
           SUM(CASE WHEN sa.activity_type = 'study_time_minutes' THEN sa.activity_value/2 ELSE 0 END) as raw_score
    FROM subjects s
    LEFT JOIN student_activity sa ON s.id = sa.subject_id AND sa.user_id = :uid
    WHERE s.type = 'theory' OR s.type = 'lab'
    GROUP BY s.id
");
$stmt->execute(['uid' => $user_id]);
$mastery_data = $stmt->fetchAll();

$subject_labels = [];
$mastery_scores = [];
$lowest_score_subject = '';
$lowest_score = 100;

foreach($mastery_data as $m) {
    if(in_array($m['code'], ['HCIL', 'ADSAL'])) continue; // Group labs with theory for simplicity
    
    $subject_labels[] = $m['code'];
    $score = min(100, max(15, floatval($m['raw_score']))); // Cap at 100%, baseline 15%
    $mastery_scores[] = $score;
    
    if($score < $lowest_score) {
        $lowest_score = $score;
        $lowest_score_subject = $m['code'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Twin & Analytics - ClassConnecto</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="flex transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto w-full p-8 relative z-10">
        <header class="mb-8 flex justify-between items-center bg-white/50 dark:bg-slate-900/50 p-6 rounded-2xl glass shadow-sm">
            <div class="flex items-center gap-4">
                 <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]">
                    <i class="fa-solid fa-user-astronaut text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-600">AI Academic Digital Twin</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1 font-medium">Real-time mapping of your academic profile and mastery.</p>
                </div>
            </div>
            <button class="bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 font-bold px-4 py-2 rounded-lg border border-indigo-200 dark:border-indigo-800 shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Report
            </button>
        </header>

        <!-- KPI Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass p-6 rounded-2xl border border-blue-200 dark:border-blue-900/50 shadow-sm relative overflow-hidden group">
                 <div class="absolute -right-4 -bottom-4 text-blue-100 dark:text-blue-900/30 text-8xl group-hover:scale-110 transition-transform duration-500 z-0"><i class="fa-solid fa-clock"></i></div>
                 <div class="relative z-10">
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Total Study Time</p>
                    <p class="text-4xl font-black text-gray-800 dark:text-white"><?= $study_hours ?> <span class="text-xl text-blue-500">Hrs</span></p>
                 </div>
            </div>
            
            <div class="glass p-6 rounded-2xl border border-green-200 dark:border-green-900/50 shadow-sm relative overflow-hidden group">
                 <div class="absolute -right-4 -bottom-4 text-green-100 dark:text-green-900/30 text-8xl group-hover:scale-110 transition-transform duration-500 z-0"><i class="fa-solid fa-check-double"></i></div>
                 <div class="relative z-10">
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Assignments</p>
                    <p class="text-4xl font-black text-gray-800 dark:text-white"><?= $assignments_completed ?> <span class="text-xl text-green-500">Done</span></p>
                 </div>
            </div>

            <div class="glass p-6 rounded-2xl border border-orange-200 dark:border-orange-900/50 shadow-sm relative overflow-hidden group">
                 <div class="absolute -right-4 -bottom-4 text-orange-100 dark:text-orange-900/30 text-8xl group-hover:scale-110 transition-transform duration-500 z-0"><i class="fa-solid fa-code"></i></div>
                 <div class="relative z-10">
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Lab Mastery</p>
                    <p class="text-4xl font-black text-gray-800 dark:text-white"><?= $programs_practiced ?> <span class="text-xl text-orange-500">Prg</span></p>
                 </div>
            </div>

            <div class="glass p-6 rounded-2xl border border-red-200 dark:border-red-900/50 shadow-sm relative overflow-hidden group">
                 <div class="absolute -right-4 -bottom-4 text-red-100 dark:text-red-900/30 text-8xl group-hover:scale-110 transition-transform duration-500 z-0"><i class="fa-solid fa-fire"></i></div>
                 <div class="relative z-10">
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Activity Streak</p>
                    <p class="text-4xl font-black text-gray-800 dark:text-white"><?= $streak ?> <span class="text-xl text-red-500">Days</span></p>
                 </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Radar Chart: Subject Mastery -->
            <div class="glass p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col items-center">
                 <h3 class="font-bold text-gray-800 dark:text-white w-full mb-6 flex items-center gap-2"><i class="fa-solid fa-spider"></i> Subject Mastery Matrix</h3>
                 <div class="w-full max-w-[400px] aspect-square relative">
                     <canvas id="masteryChart"></canvas>
                 </div>
            </div>

            <div class="flex flex-col gap-8">
                <!-- Bar Chart: Weekly Activity -->
                <div class="glass p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm h-[300px] flex flex-col">
                     <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-chart-column"></i> Activity Trend</h3>
                     <div class="flex-1 relative w-full">
                         <canvas id="activityChart"></canvas>
                     </div>
                </div>

                <!-- AI Recommendations -->
                <div class="glass p-6 rounded-2xl border-2 border-indigo-200 dark:border-indigo-900 flex-1 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl blob"></div>
                    <div class="flex items-center gap-3 mb-4">
                        <i class="fa-solid fa-robot text-2xl text-indigo-500"></i>
                         <h3 class="font-bold text-lg text-gray-800 dark:text-white">AI Twin Insights</h3>
                    </div>
                    
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 p-3 rounded-xl border border-red-100 dark:border-red-900/50">
                            <i class="fa-solid fa-triangle-exclamation text-red-500 mt-1"></i>
                            <div>
                                <p class="text-sm font-bold pl-1 text-red-800 dark:text-red-400">Low Mastery Detected</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 pl-1 mt-1">Your mastery in <strong><?= $lowest_score_subject ?></strong> is currently the lowest at <?= $lowest_score ?>%. Consider prioritizing this subject's assignments.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3 bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                            <i class="fa-solid fa-lightbulb text-yellow-500 mt-1"></i>
                            <div>
                                <p class="text-sm font-bold pl-1 text-indigo-800 dark:text-indigo-400">Consistency Notice</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 pl-1 mt-1">You missed logging study hours 2 days ago. Try to maintain your <?= $streak ?> day streak for optimal retention.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <br><br><br>
    </main>

    <!-- AI Panel -->
    <?php include 'components/ai_assistant.php'; ?>
    
    <!-- Theme Toggle -->
    <button id="theme-toggle" class="fixed bottom-5 left-64 ml-5 w-10 h-10 rounded-full glass flex items-center justify-center text-gray-600 hover:text-blue-600 transition-colors z-50 shadow-sm border border-gray-200 dark:border-gray-700">
        <i class="fa-solid fa-moon"></i>
    </button>
    <script src="assets/js/main.js"></script>
    
    <script>
        const isDarkMode = document.documentElement.classList.contains('dark');
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        const textColor = isDarkMode ? '#9ca3af' : '#4b5563';

        // 1. Mastery Radar Chart (Dynamic from DB)
        const ctxRadar = document.getElementById('masteryChart').getContext('2d');
        const subjects = <?= json_encode($subject_labels) ?>;
        const scores = <?= json_encode($mastery_scores) ?>;

        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: subjects.length ? subjects : ['OS','HCI','MEFA','P&S','ADSA','ES'],
                datasets: [{
                    label: 'Mastery %',
                    data: scores.length ? scores : [60, 40, 80, 50, 70, 30],
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: gridColor },
                        grid: { color: gridColor },
                        pointLabels: {
                            color: textColor,
                            font: { family: "'Inter', sans-serif", size: 12, weight: 'bold' }
                        },
                        ticks: {
                            display: false, max: 100, min: 0
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // 2. Weekly Activity Bar Chart (Mock up for recent 7 days interpolation)
        const ctxBar = document.getElementById('activityChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Study Hours',
                        data: [2, 1.5, 0, 3, <?= $study_hours ?>, 0, 0],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Programs Solved',
                        data: [1, 0, 0, 2, <?= $programs_practiced ?>, 0, 0],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: textColor, font: { family: "'Inter', sans-serif" } }
                    }
                }
            }
        });
    </script>
</body>
</html>
