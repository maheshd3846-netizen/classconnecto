<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';

$lab_code = $_GET['id'] ?? 'ADSAL';

$stmt = $pdo->prepare("SELECT * FROM subjects WHERE code = :code LIMIT 1");
$stmt->execute(['code' => $lab_code]);
$lab = $stmt->fetch();

if(!$lab) {
    die("Lab not found!");
}
$lab_id = $lab['id'];
$lab_name = $lab['name'];

$is_authorized = in_array($_SESSION['role'], ['admin', 'faculty', 'cr']);

// Handle Lab Program Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_authorized && isset($_POST['create_program'])) {
    $title = $_POST['title'];
    $problem_statement = $_POST['problem_statement'];
    $source_code = $_POST['source_code'];
    $explanation = $_POST['explanation'];
    
    $stmt = $pdo->prepare("INSERT INTO programs (subject_id, title, problem_statement, source_code, explanation) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$lab_id, $title, $problem_statement, $source_code, $explanation]);
    
    $new_id = $pdo->lastInsertId();
    header("Location: lab.php?id={$lab_code}&pid={$new_id}");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM programs WHERE subject_id = :sid ORDER BY id ASC");
$stmt->execute(['sid' => $lab_id]);
$programs = $stmt->fetchAll();

$active_pid = $_GET['pid'] ?? (!empty($programs) ? $programs[0]['id'] : null);
$active_program = null;

if($active_pid) {
    foreach($programs as $p) {
        if($p['id'] == $active_pid) {
            $active_program = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lab_name) ?> - Program Library</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Prism.js for Syntax Highlighting (GitHub style) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
</head>
<body class="flex transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto w-full p-8 relative z-10">
        <header class="mb-8 flex justify-between items-center">
             <div class="flex items-center gap-4">
                 <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white shadow-lg">
                    <i class="fa-solid fa-code text-xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($lab_name) ?> Repository</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Explore, understand, and practice lab programs.</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button class="btn-primary flex items-center gap-2">
                    <i class="fa-brands fa-github"></i> Sync Setup
                </button>
                <?php if($is_authorized): ?>
                <button onclick="document.getElementById('modal-add-program').classList.remove('hidden')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400 hover:bg-emerald-200 text-sm font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Add Program
                </button>
                <?php endif; ?>
            </div>
        </header>

        <div class="flex flex-col xl:flex-row gap-6">
            <!-- Program List -->
            <div class="xl:w-1/3 space-y-3">
                <div class="relative mb-4">
                    <i class="fa-solid fa-search absolute left-3 top-3.5 text-gray-400"></i>
                    <input type="text" class="input-glass pl-9 w-full bg-white/50 dark:bg-gray-800" placeholder="Search programs...">
                </div>

                <div class="glass rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="p-3 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        Program Index
                    </div>
                    
                    <?php if(empty($programs)): ?>
                        <div class="p-4 text-sm text-gray-500 italic text-center">No programs uploaded yet.</div>
                    <?php endif; ?>

                    <?php foreach($programs as $index => $p): 
                        $isActive = ($p['id'] == $active_pid);
                        $bgClass = $isActive ? 'bg-blue-50/50 dark:bg-blue-900/10 border-l-4 border-l-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-l-4 border-l-transparent';
                    ?>
                    <a href="lab.php?id=<?= $lab_code ?>&pid=<?= $p['id'] ?>" class="block p-4 border-b border-gray-200 dark:border-gray-700 <?= $bgClass ?>">
                        <h4 class="font-bold text-gray-800 dark:text-white text-sm"><?= ($index+1) . ". " . htmlspecialchars($p['title']) ?></h4>
                        <div class="flex gap-2 mt-2">
                             <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">C++</span>
                             <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-bold">Lab <?= ($index+1) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- GitHub Style Code Viewer -->
            <div class="xl:w-2/3 glass rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col overflow-hidden h-max">
                <?php if($active_program): ?>
                <!-- Header -->
                <div class="bg-gray-100 dark:bg-gray-800 p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                     <div class="flex items-center gap-3">
                         <i class="fa-solid fa-file-code text-blue-500 text-lg"></i>
                         <h3 class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($active_program['title']) ?>.cpp</h3>
                     </div>
                     <div class="flex items-center gap-2">
                         <button onclick="copyCode()" class="bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 px-3 py-1.5 rounded-md text-xs font-medium transition shadow-sm flex items-center gap-1">
                             <i class="fa-regular fa-copy"></i> Copy
                         </button>
                     </div>
                </div>
                
                <!-- Description Box -->
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50">
                     <h4 class="font-bold text-sm text-gray-800 dark:text-white mb-2">Problem Statement:</h4>
                     <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed"><?= nl2br(htmlspecialchars($active_program['problem_statement'])) ?></p>
                </div>

                <!-- Code Container -->
                <div class="flex-1 bg-[#1d1f21] overflow-auto relative max-h-[500px]">
                    <pre><code class="language-cpp"><?= htmlspecialchars($active_program['source_code']) ?></code></pre>
                </div>
                
                <!-- Output / Explanation Footer -->
                <div class="bg-gray-50 dark:bg-gray-800 p-5 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="font-bold text-sm text-gray-800 dark:text-white mb-2 flex items-center gap-2">
                         <i class="fa-solid fa-terminal"></i> Concept Note
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4"><?= nl2br(htmlspecialchars($active_program['explanation'])) ?></p>
                </div>

                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center p-20 text-center opacity-50">
                          <i class="fa-solid fa-code text-6xl text-gray-400 mb-4"></i>
                          <h2 class="text-2xl font-bold text-gray-500">Repository Empty</h2>
                          <p class="text-sm text-gray-400 mt-2">No code has been pushed to this lab repository yet.</p>
                     </div>
                <?php endif; ?>
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
    
    <?php if($is_authorized): ?>
    <!-- Add Lab Program Modal -->
    <div id="modal-add-program" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="glass p-6 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto relative border border-emerald-200 dark:border-emerald-900/50">
             <button onclick="document.getElementById('modal-add-program').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-code text-emerald-500 mr-2"></i> Add Lab Program</h3>
             <form method="POST">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Program Title</label>
                         <input type="text" name="title" required class="w-full input-glass bg-white dark:bg-gray-800" placeholder="e.g. Program to Reverse an Array">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Problem Statement</label>
                         <textarea name="problem_statement" required rows="3" class="w-full input-glass bg-white dark:bg-gray-800" placeholder="Describe what the program should accomplish..."></textarea>
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Source Code</label>
                         <textarea name="source_code" required rows="8" class="w-full input-glass bg-white dark:bg-gray-800 font-mono text-sm" placeholder="#include <iostream>..."></textarea>
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Explanation / Concept Note</label>
                         <textarea name="explanation" required rows="3" class="w-full input-glass bg-white dark:bg-gray-800" placeholder="Briefly explain the underlying logic or output expectations."></textarea>
                     </div>
                     
                     <div class="pt-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 mt-6">
                         <button type="button" onclick="document.getElementById('modal-add-program').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition">Cancel</button>
                         <button type="submit" name="create_program" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-6 rounded-lg shadow-md hover:shadow-emerald-500/30 transition-all">Save Program</button>
                     </div>
                 </div>
             </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-c.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-cpp.min.js"></script>
    
    <script>
        function copyCode() {
            const code = document.querySelector('code').innerText;
            navigator.clipboard.writeText(code).then(() => {
                const btn = event.currentTarget;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check text-green-500"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                }, 2000);
            });
        }
    </script>
</body>
</html>
