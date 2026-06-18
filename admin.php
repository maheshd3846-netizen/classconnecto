<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Demote to index if not admin.
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';

$subject_count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$notes_count = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$programs_count = $pdo->query("SELECT COUNT(*) FROM programs")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ClassConnecto</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class=" transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Top Navigation (Admin focused) -->
    <nav class="glass border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center text-white shadow-lg">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h2 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-red-600 to-orange-600">Admin Control</h2>
        </div>
        <div class="flex items-center gap-4">
             <button id="theme-toggle" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 hover:text-blue-600 transition-colors text-xl border border-gray-200 dark:border-gray-700">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="api/logout.php" class="text-sm font-bold text-red-500 hover:text-red-700 flex items-center gap-1 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg">
                 Logout <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </nav>

    <div class="p-8 max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Platform Management</h1>
        <p class="text-gray-500 mb-8">Manage subjects, users, notes, and lab programs globally.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4 hover-lift">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-book"></i></div>
                <div><p class="text-sm text-gray-500 font-bold uppercase tracking-wider">Subjects</p><p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $subject_count ?></p></div>
            </div>
            <div class="glass p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4 hover-lift">
                <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-users"></i></div>
                <div><p class="text-sm text-gray-500 font-bold uppercase tracking-wider">Students</p><p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $student_count ?></p></div>
            </div>
            <div class="glass p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4 hover-lift">
                <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xl shrink-0"><i class="fa-regular fa-file-pdf"></i></div>
                <div><p class="text-sm text-gray-500 font-bold uppercase tracking-wider">Notes</p><p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $notes_count ?></p></div>
            </div>
             <div class="glass p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4 hover-lift">
                <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-code"></i></div>
                <div><p class="text-sm text-gray-500 font-bold uppercase tracking-wider">Programs</p><p class="text-2xl font-bold text-gray-800 dark:text-white"><?= $programs_count ?></p></div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
             <div class="lg:w-1/3">
                 <div class="glass p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                     <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">Quick Actions</h3>
                     <ul class="space-y-4">
                         <li><a href="subjects.php" class="block w-full text-left font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors"><i class="fa-solid fa-plus text-blue-500 w-6 text-center"></i> Add New Subject</a></li>
                         <li><a href="subjects.php" class="block w-full text-left font-medium text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors"><i class="fa-solid fa-upload text-purple-500 w-6 text-center"></i> Upload Notes / Material</a></li>
                         <li><a href="subjects.php" class="block w-full text-left font-medium text-gray-700 dark:text-gray-300 hover:text-orange-600 dark:hover:text-orange-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors"><i class="fa-solid fa-file-invoice text-orange-500 w-6 text-center"></i> Create Assignment</a></li>
                         <li><a href="subjects.php" class="block w-full text-left font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors"><i class="fa-solid fa-file-code text-emerald-500 w-6 text-center"></i> Add Lab Program (GitHub Sync)</a></li>
                         <li><a href="subjects.php" class="block w-full text-left font-medium text-gray-700 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors"><i class="fa-solid fa-shield-halved text-red-500 w-6 text-center"></i> Moderate Doubts Forum</a></li>
                     </ul>
                 </div>
             </div>

             <div class="lg:w-2/3">
                  <div class="glass p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm h-full flex flex-col items-center justify-center text-center">
                       <div class="w-24 h-24 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center text-4xl mb-4 border-4 border-white dark:border-slate-800 shadow-xl">
                            <i class="fa-solid fa-rocket"></i>
                       </div>
                       <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Admin Module Skeleton Ready</h2>
                       <p class="text-gray-500 mt-2 max-w-md">The core structure is established. Functionalities like uploading notes or syncing programs via actual backend logic can be tied to the database endpoints here in future sprints.</p>
                       <a href="dashboard.php" class="mt-6 btn-primary">Switch to Student View</a>
                  </div>
             </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
