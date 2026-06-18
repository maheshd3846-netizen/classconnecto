<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 glass h-screen fixed left-0 top-0 z-40 border-r border-gray-200 dark:border-gray-800 flex flex-col transition-all">
    <div class="p-6 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h2 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">ClassConnecto</h2>
    </div>

    <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
        <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Workspace</p>
        <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page == 'dashboard.php' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
            <i class="fa-solid fa-house w-5"></i> Dashboard
        </a>
        <a href="subjects.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page == 'subjects.php' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
            <i class="fa-solid fa-book w-5"></i> Subjects
        </a>
        <a href="assignments.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page == 'assignments.php' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
            <i class="fa-solid fa-clipboard-list w-5"></i> Assignments
        </a>
        <a href="planner.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page == 'planner.php' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
            <i class="fa-solid fa-calendar-alt w-5"></i> Study Planner
        </a>
        
        <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">Analytics</p>
        <a href="twin.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $current_page == 'twin.php' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' ?>">
            <i class="fa-solid fa-user-astronaut w-5"></i> Digital Twin
        </a>
        
        <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">Assistance</p>
        <button onclick="toggleAIPanel()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 text-left">
            <i class="fa-solid fa-robot w-5 text-indigo-500"></i> AI Doubts
        </button>
    </nav>

    <div class="p-4 mt-auto border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex flex-shrink-0 items-center justify-center font-bold text-gray-600 dark:text-gray-300">
                <?= substr($_SESSION['full_name'] ?? 'U', 0, 1) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Student') ?></p>
                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($_SESSION['reg_number'] ?? '') ?></p>
            </div>
        </div>
        <div class="mt-2 flex gap-2 w-full">
            <button id="theme-toggle" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors border border-gray-200 dark:border-gray-700">
                <i class="fa-solid fa-moon theme-icon"></i> Theme
            </button>
            <a href="api/logout.php" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors border border-red-100 dark:border-red-900/20">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</aside>
