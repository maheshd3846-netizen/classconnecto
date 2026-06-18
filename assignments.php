<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';

// Fetch all assignments with subject names and days left
$stmt = $pdo->prepare("
    SELECT a.*, s.name as subject_name, s.code, DATEDIFF(a.deadline, NOW()) as days_left
    FROM assignments a
    JOIN subjects s ON a.subject_id = s.id
    ORDER BY a.deadline ASC
");
$stmt->execute();
$assignments = $stmt->fetchAll();

// Find if any assignment is urgent (due in <= 3 days and not past)
$urgent_assignment = null;
foreach($assignments as $a) {
    if($a['days_left'] >= 0 && $a['days_left'] <= 3) {
        $urgent_assignment = $a;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Assignments - ClassConnecto</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex  transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto w-full p-8 relative">
        <header class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-4">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Global Assignments</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Track all your deadlines across all subjects in one place.</p>
        </header>

         <div class="space-y-4 max-w-4xl">
            <?php if($urgent_assignment): ?>
            <!-- Deadline Warning Alert -->
             <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg mb-6">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-xl"></i>
                    <div>
                        <h4 class="text-red-800 dark:text-red-400 font-bold">Action Required: Assignment Due Soon</h4>
                        <p class="text-red-600 dark:text-red-300 text-sm mt-0.5">"<?= htmlspecialchars($urgent_assignment['title']) ?>" - Deadline in <?= $urgent_assignment['days_left'] ?> days.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(empty($assignments)): ?>
                <div class="text-center text-gray-500 italic p-8 glass rounded-xl border border-gray-200 dark:border-gray-700">No assignments found globally.</div>
            <?php endif; ?>

            <?php foreach($assignments as $assignment): 
                $days_left = $assignment['days_left'];
                if($days_left < 0) {
                    $color = 'green';
                    $statusText = '<i class="fa-solid fa-check"></i> Submitted';
                    $opacity = 'opacity-70';
                    $titleClass = 'line-through';
                    $btnText = 'View Feedback';
                } else if($days_left <= 3) {
                    $color = 'red';
                    $statusText = '<i class="fa-solid fa-clock"></i> '.$days_left.' Days Left';
                    $opacity = '';
                    $titleClass = '';
                    $btnText = 'Submit Now';
                } else {
                    $color = 'blue';
                    $statusText = '<i class="fa-solid fa-calendar"></i> '.$days_left.' Days Left';
                    $opacity = '';
                    $titleClass = '';
                    $btnText = 'View in Workspace';
                }
            ?>
            <div class="glass p-5 rounded-xl border border-<?= $color ?>-200 dark:border-<?= $color ?>-900/50 flex flex-col md:flex-row gap-6 relative <?= $opacity ?> group transition-all hover:-translate-y-1 hover:shadow-md">
                 <div class="absolute left-0 top-0 bottom-0 w-1 bg-<?= $color ?>-400 rounded-l-xl"></div>
                 <div class="md:w-1/4 flex flex-col justify-center">
                      <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">DEADLINE</p>
                      <p class="text-xl font-bold text-gray-800 dark:text-white <?= $titleClass ?>"><?= date('M d, Y', strtotime($assignment['deadline'])) ?></p>
                      <p class="text-sm text-<?= $color ?>-600 font-medium flex items-center gap-1 mt-1"><?= $statusText ?></p>
                 </div>
                 <div class="md:w-2/4">
                     <span class="bg-<?= $color ?>-100 text-<?= $color ?>-700 text-xs px-2 py-1 rounded font-bold mb-2 inline-block"><?= htmlspecialchars($assignment['subject_name']) ?></span>
                     <h3 class="text-lg font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($assignment['title']) ?></h3>
                     <p class="text-sm text-gray-600 dark:text-gray-400 mt-2"><?= htmlspecialchars($assignment['description']) ?></p>
                 </div>
                 <div class="md:w-1/4 flex items-center justify-end">
                      <a href="subject.php?id=<?= $assignment['code'] ?>&tab=assignments" class="<?= $days_left < 0 ? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 font-bold px-4 py-2 rounded-lg text-sm transition-colors' : 'btn-primary text-sm shadow-sm' ?>">
                          <?= $btnText ?>
                      </a>
                 </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- AI Panel -->
    <?php include 'components/ai_assistant.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
