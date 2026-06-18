<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db/connection.php';

$subject_code = $_GET['id'] ?? 'OS';

$stmt = $pdo->prepare("SELECT * FROM subjects WHERE code = :code LIMIT 1");
$stmt->execute(['code' => $subject_code]);
$subject = $stmt->fetch();

if(!$subject) {
    die("Subject not found!");
}
$subject_id = $subject['id'];
$subject_name = $subject['name'];

// Fetch Notes
$stmt = $pdo->prepare("SELECT * FROM notes WHERE subject_id = :sid ORDER BY unit_number ASC");
$stmt->execute(['sid' => $subject_id]);
$notes = $stmt->fetchAll();

// Fetch Assignments
$stmt = $pdo->prepare("SELECT *, DATEDIFF(deadline, NOW()) as days_left FROM assignments WHERE subject_id = :sid ORDER BY deadline ASC");
$stmt->execute(['sid' => $subject_id]);
$assignments = $stmt->fetchAll();

// Fetch Doubts & their answers
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name as author_name, 
           (SELECT answer FROM answers a WHERE a.doubt_id = d.id ORDER BY is_best_answer DESC, created_at ASC LIMIT 1) as top_answer
    FROM doubts d
    LEFT JOIN users u ON d.student_id = u.id
    WHERE d.subject_id = :sid ORDER BY d.created_at DESC
");
$stmt->execute(['sid' => $subject_id]);
$doubts = $stmt->fetchAll();

$is_authorized = in_array($_SESSION['role'], ['admin', 'faculty', 'cr']);

// Fetch Reference Links
$stmt = $pdo->prepare("SELECT * FROM reference_links WHERE subject_id = :sid ORDER BY created_at DESC");
$stmt->execute(['sid' => $subject_id]);
$reference_links = $stmt->fetchAll();

// Handle Post Requests for Authorized Users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_authorized) {
    if (isset($_POST['add_note'])) {
        $title = $_POST['title'];
        $unit = (int)$_POST['unit'];
        
        // Handle file upload
        $file_path = '#';
        if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/notes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['note_file']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['note_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO notes (subject_id, title, unit_number, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $title, $unit, $file_path]);
        header("Location: subject.php?id=$subject_code&tab=notes");
        exit();
    } elseif (isset($_POST['delete_note'])) {
        $note_id = (int)$_POST['note_id'];
        // Fetch file path to delete file
        $stmt = $pdo->prepare("SELECT file_path FROM notes WHERE id = ? AND subject_id = ?");
        $stmt->execute([$note_id, $subject_id]);
        $note = $stmt->fetch();
        if ($note && $note['file_path'] !== '#' && file_exists($note['file_path'])) {
            unlink($note['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND subject_id = ?");
        $stmt->execute([$note_id, $subject_id]);
        header("Location: subject.php?id=$subject_code&tab=notes");
        exit();
    } elseif (isset($_POST['add_link'])) {
        $title = $_POST['title'];
        $url = $_POST['url'];
        $stmt = $pdo->prepare("INSERT INTO reference_links (subject_id, title, url) VALUES (?, ?, ?)");
        $stmt->execute([$subject_id, $title, $url]);
        header("Location: subject.php?id=$subject_code&tab=notes");
        exit();
    } elseif (isset($_POST['delete_link'])) {
        $link_id = (int)$_POST['link_id'];
        $stmt = $pdo->prepare("DELETE FROM reference_links WHERE id = ? AND subject_id = ?");
        $stmt->execute([$link_id, $subject_id]);
        header("Location: subject.php?id=$subject_code&tab=notes");
        exit();
    } elseif (isset($_POST['add_assignment'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $deadline = $_POST['deadline'];
        
        $file_path = '#';
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . basename($_FILES['assignment_file']['name']);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO assignments (subject_id, title, description, deadline, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$subject_id, $title, $desc, $deadline, $file_path]);
        header("Location: subject.php?id=$subject_code&tab=assignments");
        exit();
    } elseif (isset($_POST['update_assignment'])) {
        $aid = (int)$_POST['assignment_id'];
        $deadline = $_POST['deadline'];
        $stmt = $pdo->prepare("UPDATE assignments SET deadline = ? WHERE id = ? AND subject_id = ?");
        $stmt->execute([$deadline, $aid, $subject_id]);
        header("Location: subject.php?id=$subject_code&tab=assignments");
        exit();
    } elseif (isset($_POST['delete_assignment'])) {
        $aid = (int)$_POST['assignment_id'];
        // Delete file
        $stmt = $pdo->prepare("SELECT file_path FROM assignments WHERE id = ? AND subject_id = ?");
        $stmt->execute([$aid, $subject_id]);
        $assignment = $stmt->fetch();
        if ($assignment && isset($assignment['file_path']) && $assignment['file_path'] !== '#' && file_exists($assignment['file_path'])) {
            unlink($assignment['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND subject_id = ?");
        $stmt->execute([$aid, $subject_id]);
        header("Location: subject.php?id=$subject_code&tab=assignments");
        exit();
    }
}

// Handle Post Doubt (All users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_doubt'])) {
    $question = $_POST['question'] ?? '';
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    if(!empty(trim($question))) {
         $stmt = $pdo->prepare("INSERT INTO doubts (subject_id, student_id, question, is_anonymous) VALUES (?, ?, ?, ?)");
         $stmt->execute([$subject_id, $_SESSION['user_id'], $question, $is_anonymous]);
         
         $stmt = $pdo->prepare("INSERT INTO student_activity (user_id, subject_id, activity_type, activity_value) VALUES (?, ?, 'ask_doubt', 1)");
         $stmt->execute([$_SESSION['user_id'], $subject_id]);
         
         header("Location: subject.php?id=$subject_code&tab=forum");
         exit();
    }
}
$active_tab = $_GET['tab'] ?? 'notes';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject_name) ?> - ClassConnecto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex transition-colors duration-300">
    <?php include 'components/background.php'; ?>

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto w-full relative z-10">
        <!-- Notion-style cover image & header -->
        <div class="h-48 w-full bg-gradient-to-r from-blue-600 to-indigo-700 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <div class="px-10 -mt-12 relative z-10">
            <div class="w-24 h-24 bg-white dark:bg-gray-800 rounded-2xl shadow-xl flex items-center justify-center text-4xl mb-4 border-4 border-gray-50 dark:border-slate-900">
                💻
            </div>
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white mb-2"><?= htmlspecialchars($subject_name) ?></h1>
            <p class="text-gray-500 mb-8 max-w-2xl">Subject Code: <?= htmlspecialchars($subject_code) ?>. Welcome to your main workspace for learning and mastering the concepts of this subject.</p>

            <!-- Custom Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-8 overflow-x-auto no-scrollbar" id="tabs">
                <button onclick="switchTab('notes')" id="tab-notes" class="px-6 py-3 font-medium text-sm border-b-2 <?= $active_tab=='notes' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500' ?> whitespace-nowrap transition-colors">Notes & Resources</button>
                <button onclick="switchTab('assignments')" id="tab-assignments" class="px-6 py-3 font-medium text-sm border-b-2 <?= $active_tab=='assignments' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500' ?> whitespace-nowrap transition-colors hover:text-gray-700">Assignments</button>
                <button onclick="switchTab('forum')" id="tab-forum" class="px-6 py-3 font-medium text-sm border-b-2 <?= $active_tab=='forum' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-gray-500' ?> whitespace-nowrap transition-colors hover:text-gray-700">Anonymous Doubt Forum</button>
            </div>

            <!-- Tab Contents -->
            <div id="content-notes" class="tab-pane <?= $active_tab=='notes' ? 'active' : 'hidden' ?> pb-20">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Unit Materials</h2>
                    <?php if($is_authorized): ?>
                         <button onclick="document.getElementById('modal-add-note').classList.remove('hidden')" class="btn-primary text-sm py-2 px-4 shadow-sm"><i class="fa-solid fa-plus mr-1"></i> Add Note</button>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php if(empty($notes)): ?>
                        <p class="text-gray-500 italic col-span-full">No notes available for this subject yet.</p>
                    <?php endif; ?>
                    <?php foreach($notes as $note): ?>
                    <div class="glass rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm relative group overflow-hidden hover-lift gradient-border-blue transition-all duration-300">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-400 to-indigo-500 opacity-80 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded font-bold">Unit <?= htmlspecialchars($note['unit_number']) ?></span>
                                <h3 class="font-bold text-lg text-gray-800 dark:text-white mt-1 leading-tight"><?= htmlspecialchars($note['title']) ?></h3>
                            </div>
                            <div class="w-10 h-10 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-lg flex items-center justify-center text-xl shrink-0">
                                <i class="fa-regular fa-file-pdf"></i>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-6">Uploaded: <?= date('M d, Y', strtotime($note['uploaded_at'])) ?></p>
                        
                        <div class="grid grid-cols-2 gap-2 mt-auto relative">
                            <?php if($is_authorized): ?>
                                <form method="POST" class="absolute -top-12 right-0">
                                    <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                    <button type="submit" name="delete_note" class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-full flex items-center justify-center transition-colors shadow-sm" title="Delete Note" onclick="return confirm('Are you sure you want to delete this note?');">
                                        <i class="fa-solid fa-trash text-sm"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($note['file_path']) ?>" <?= $note['file_path'] !== '#' ? 'download' : '' ?> class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium py-2 rounded-lg transition text-gray-700 dark:text-gray-300 shadow-sm flex items-center justify-center">
                                <i class="fa-solid fa-download mr-1"></i> Download
                            </a>
                            <button onclick="generateSummary('notes<?= $note['id'] ?>')" class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-sm font-medium py-2 rounded-lg transition shadow-sm flex items-center justify-center gap-1 group-btn">
                                <i class="fa-solid fa-wand-magic-sparkles group-hover:animate-pulse"></i> AI Summary
                            </button>
                        </div>
                        
                        <div id="summary-notes<?= $note['id'] ?>" class="hidden mt-4 p-4 bg-indigo-50/50 dark:bg-gray-800/80 rounded-lg border border-indigo-100 dark:border-gray-700 text-sm">
                        <!-- AI content will load here -->
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reference Links -->
                <div class="flex justify-between items-center mb-6 mt-10 border-t border-gray-200 dark:border-gray-700 pt-8">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2"><i class="fa-solid fa-link text-emerald-500"></i> Reference Links</h2>
                    <?php if($is_authorized): ?>
                         <button onclick="document.getElementById('modal-add-link').classList.remove('hidden')" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 hover:bg-emerald-200 font-bold text-sm py-2 px-4 rounded-lg transition-colors shadow-sm"><i class="fa-solid fa-plus mr-1"></i> Add Link</button>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if(empty($reference_links)): ?>
                         <p class="text-gray-500 italic col-span-full">No reference links added yet.</p>
                    <?php endif; ?>
                    <?php foreach($reference_links as $link): ?>
                         <div class="relative group">
                             <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="glass p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600 transition-colors flex items-center justify-between group">
                                  <div class="flex items-center gap-3">
                                       <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900/50">
                                            <i class="fa-solid fa-globe"></i>
                                       </div>
                                       <h4 class="font-bold text-gray-800 dark:text-white group-hover:text-emerald-600 transition-colors truncate max-w-[200px]" title="<?= htmlspecialchars($link['title']) ?>"><?= htmlspecialchars($link['title']) ?></h4>
                                  </div>
                                  <i class="fa-solid fa-external-link-alt text-gray-400 text-xs"></i>
                             </a>
                             <?php if($is_authorized): ?>
                                 <form method="POST" class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                     <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                     <button type="submit" name="delete_link" class="w-6 h-6 bg-red-100 hover:bg-red-200 text-red-600 rounded-full flex items-center justify-center shadow-sm" title="Delete Link" onclick="return confirm('Are you sure you want to delete this link?');">
                                         <i class="fa-solid fa-xmark text-xs"></i>
                                     </button>
                                 </form>
                             <?php endif; ?>
                         </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Assignments Tab -->
            <div id="content-assignments" class="tab-pane <?= $active_tab=='assignments'? 'active' : 'hidden' ?> pb-20">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Upcoming Deadlines</h2>
                    <?php if($is_authorized): ?>
                         <button onclick="document.getElementById('modal-add-assignment').classList.remove('hidden')" class="btn-primary text-sm py-2 px-4 shadow-sm"><i class="fa-solid fa-file-signature text-orange-200 mr-2"></i> Create Assignment</button>
                    <?php endif; ?>
                </div>
                <div class="space-y-4 max-w-4xl">
                    <?php if(empty($assignments)): ?>
                        <p class="text-gray-500 italic">No assignments for this subject yet.</p>
                    <?php endif; ?>
                    <?php foreach($assignments as $assignment): 
                        $days_left = $assignment['days_left'];
                        if($days_left < 0) {
                            $color = 'green';
                            $statusText = '<i class="fa-solid fa-check"></i> Submitted';
                            $opacity = 'opacity-70';
                            $titleClass = 'line-through';
                        } else if($days_left <= 3) {
                            $color = 'red';
                            $statusText = '<i class="fa-solid fa-clock"></i> '.$days_left.' Days Left';
                            $opacity = '';
                            $titleClass = '';
                            echo '<div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg mb-6"><div class="flex items-center gap-3"><i class="fa-solid fa-circle-exclamation text-red-500 text-xl"></i><div><h4 class="text-red-800 dark:text-red-400 font-bold">Action Required: Assignment Due Soon</h4><p class="text-red-600 dark:text-red-300 text-sm mt-0.5">"'.htmlspecialchars($assignment['title']).'". Deadline approaching.</p></div></div></div>';
                        } else {
                            $color = 'orange';
                            $statusText = '<i class="fa-solid fa-clock"></i> '.$days_left.' Days Left';
                            $opacity = '';
                            $titleClass = '';
                        }
                    ?>
                    <div class="glass p-5 rounded-xl border border-<?= $color ?>-200 dark:border-<?= $color ?>-900/50 flex flex-col md:flex-row gap-6 relative <?= $opacity ?> hover-lift transition-all duration-300 group">
                         <div class="absolute left-0 top-0 bottom-0 w-1 bg-<?= $color ?>-400 rounded-l-xl opacity-80 group-hover:opacity-100 transition-opacity"></div>
                         <div class="md:w-1/4 flex flex-col justify-center">
                              <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">DEADLINE</p>
                              <p class="text-xl font-bold text-gray-800 dark:text-white <?= $titleClass ?>"><?= date('M d, Y', strtotime($assignment['deadline'])) ?></p>
                              <p class="text-sm text-<?= $color ?>-600 font-medium flex items-center gap-1 mt-1 <?= $days_left > 0 && $days_left <= 3 ? 'animate-soft-pulse rounded px-2 py-0.5' : '' ?>"><?= $statusText ?></p>
                         </div>
                         <div class="md:w-2/4">
                             <h3 class="text-lg font-bold text-gray-800 dark:text-white group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($assignment['title']) ?></h3>
                             <p class="text-sm text-gray-600 dark:text-gray-400 mt-2"><?= htmlspecialchars($assignment['description']) ?></p>
                             <?php if(isset($assignment['file_path']) && $assignment['file_path'] !== '#'): ?>
                                 <div class="mt-3">
                                     <a href="<?= htmlspecialchars($assignment['file_path']) ?>" download class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30 px-3 py-1.5 rounded border border-blue-200 dark:border-blue-800 transition-colors">
                                         <i class="fa-solid fa-paperclip"></i> Download Attachment
                                     </a>
                                 </div>
                             <?php endif; ?>
                         </div>
                         <div class="md:w-1/4 flex items-center justify-end gap-2">
                              <?php if($is_authorized): ?>
                                   <button onclick="openEditDeadline(<?= $assignment['id'] ?>, '<?= date('Y-m-d\TH:i', strtotime($assignment['deadline'])) ?>')" class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 px-3 py-2 rounded-lg text-sm font-bold shadow-sm transition-colors border border-gray-200 dark:border-gray-700" title="Edit Deadline"><i class="fa-solid fa-pen"></i></button>
                                   <form method="POST" class="inline">
                                       <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                       <button type="submit" name="delete_assignment" class="bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/40 px-3 py-2 rounded-lg text-sm font-bold shadow-sm transition-colors border border-red-200 dark:border-red-900/50" title="Delete Assignment" onclick="return confirm('Delete this assignment?');"><i class="fa-solid fa-trash"></i></button>
                                   </form>
                              <?php endif; ?>
                              <button class="<?= $days_left < 0 ? 'bg-gray-100 text-gray-500 px-4 py-2 rounded-lg font-bold text-sm border border-gray-200 dark:border-gray-700' : 'btn-primary' ?>"><?= $days_left < 0 ? 'View Feedback' : 'Submit Work' ?></button>
                         </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Anonymous Doubt Forum -->
            <div id="content-forum" class="tab-pane <?= $active_tab=='forum'? 'active' : 'hidden' ?> pb-20">
                <form method="POST" class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/50 rounded-xl p-5 mb-8 flex items-start gap-4 shadow-sm">
                     <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0 shadow-sm border border-indigo-200">
                          <i class="fa-solid fa-user-secret"></i>
                     </div>
                     <div class="flex-1 w-full">
                         <textarea name="question" required class="w-full input-glass resize-none min-h-[100px] bg-white dark:bg-gray-800" placeholder="Ask a doubt anonymously. Our AI or your peers will answer..."></textarea>
                         <div class="flex justify-between items-center mt-3">
                              <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer font-medium">
                                  <input type="checkbox" name="is_anonymous" class="w-4 h-4 text-indigo-600 rounded bg-gray-100 focus:ring-indigo-500 border-gray-300" checked>
                                  Keep my identity hidden
                              </label>
                              <button type="submit" name="submit_doubt" class="btn-primary text-sm py-2"><i class="fa-solid fa-paper-plane mr-2"></i> Post Doubt</button>
                         </div>
                     </div>
                </form>

                <div class="space-y-4 max-w-4xl">
                    <?php if(empty($doubts)): ?>
                         <p class="text-gray-500 italic">No doubts posted yet. Be the first to ask!</p>
                    <?php endif; ?>
                    <?php foreach($doubts as $doubt): 
                        $author = $doubt['is_anonymous'] ? 'Anonymous Student' : $doubt['author_name'];
                        $isResolved = !empty($doubt['top_answer']);
                    ?>
                    <div class="glass p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                         <div class="flex justify-between items-start mb-3">
                              <div class="flex items-center gap-2">
                                  <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 text-xs"><i class="fa-solid <?= $doubt['is_anonymous'] ? 'fa-mask' : 'fa-user' ?>"></i></div>
                                  <div>
                                      <p class="font-bold text-sm text-gray-800 dark:text-white"><?= htmlspecialchars($author) ?></p>
                                      <p class="text-xs text-gray-400">Asked <?= date('M d, g:i A', strtotime($doubt['created_at'])) ?></p>
                                  </div>
                              </div>
                              <span class="text-[10px] px-2 py-1 rounded-full font-bold <?= $isResolved ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-blue-100 text-blue-700 border border-blue-200' ?>">
                                  <?= $isResolved ? 'Resolved' : 'Unresolved' ?>
                              </span>
                         </div>
                         <h3 class="font-bold text-gray-800 dark:text-white mb-2 text-lg"><?= htmlspecialchars($doubt['question']) ?></h3>
                         
                         <?php if($isResolved): ?>
                         <div class="border-t border-gray-100 dark:border-gray-800 mt-4 pt-4">
                              <div class="bg-gradient-to-r from-indigo-50/50 to-blue-50/50 dark:from-indigo-900/20 dark:to-blue-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-lg p-4 flex gap-3 shadow-inner">
                                  <div class="w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                      <i class="fa-solid fa-graduation-cap text-xs"></i>
                                  </div>
                                  <div>
                                      <div class="flex items-center gap-2 mb-1">
                                          <p class="font-bold text-sm text-indigo-700 dark:text-indigo-400">Top Answer</p>
                                          <i class="fa-solid fa-check-circle text-green-500 text-xs" title="Verified"></i>
                                      </div>
                                      <div class="text-sm text-gray-700 dark:text-gray-300">
                                          <?= htmlspecialchars($doubt['top_answer']) ?>
                                      </div>
                                  </div>
                              </div>
                         </div>
                         <?php else: ?>
                            <!-- Reply box placeholder -->
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <button class="text-sm font-bold text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-reply mr-1"></i> Add an Answer</button>
                            </div>
                         <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- AI Panel -->
    <?php include 'components/ai_assistant.php'; ?>

    <?php if($is_authorized): ?>
    <!-- Add Note Modal -->
    <div id="modal-add-note" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="glass p-6 rounded-2xl w-full max-w-md relative">
             <button onclick="document.getElementById('modal-add-note').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-file-pdf text-blue-500 mr-2"></i> Add Study Material</h3>
             <form method="POST" enctype="multipart/form-data">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Title</label>
                         <input type="text" name="title" required class="w-full input-glass bg-white dark:bg-gray-800">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Unit Number</label>
                         <input type="number" name="unit" required class="w-full input-glass bg-white dark:bg-gray-800" min="1" max="10">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Upload File (PDF)</label>
                         <input type="file" name="note_file" accept=".pdf,.doc,.docx" required class="w-full input-glass bg-white dark:bg-gray-800 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                     </div>
                     <button type="submit" name="add_note" class="w-full btn-primary py-2 mt-4"><i class="fa-solid fa-upload"></i> Upload Note</button>
                 </div>
             </form>
        </div>
    </div>

    <!-- Add Link Modal -->
    <div id="modal-add-link" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="glass p-6 rounded-2xl w-full max-w-md relative border border-emerald-200 dark:border-emerald-900/50">
             <button onclick="document.getElementById('modal-add-link').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-globe text-emerald-500 mr-2"></i> Add Reference Link</h3>
             <form method="POST">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Link Title</label>
                         <input type="text" name="title" required class="w-full input-glass bg-white dark:bg-gray-800">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">URL / External Link</label>
                         <input type="url" name="url" required class="w-full input-glass bg-white dark:bg-gray-800" placeholder="https://...">
                     </div>
                     <button type="submit" name="add_link" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 rounded-lg mt-4 shadow-md transition-colors"><i class="fa-solid fa-check"></i> Save Link</button>
                 </div>
             </form>
        </div>
    </div>

    <!-- Add Assignment Modal -->
    <div id="modal-add-assignment" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="glass p-6 rounded-2xl w-full max-w-md relative border border-orange-200 dark:border-orange-900/50">
             <button onclick="document.getElementById('modal-add-assignment').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-file-signature text-orange-500 mr-2"></i> Create Assignment</h3>
             <form method="POST" enctype="multipart/form-data">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Title</label>
                         <input type="text" name="title" required class="w-full input-glass bg-white dark:bg-gray-800">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Description</label>
                         <textarea name="description" required class="w-full input-glass bg-white dark:bg-gray-800 resize-none h-20"></textarea>
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                         <input type="datetime-local" name="deadline" required class="w-full input-glass bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                     </div>
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Upload Attachment (Optional)</label>
                         <input type="file" name="assignment_file" accept=".pdf,.doc,.docx,.zip,.rar" class="w-full input-glass bg-white dark:bg-gray-800 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                     </div>
                     <button type="submit" name="add_assignment" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 rounded-lg mt-4 shadow-md transition-colors"><i class="fa-solid fa-flag-checkered"></i> Issue Assignment</button>
                 </div>
             </form>
        </div>
    </div>

    <!-- Edit Deadline Modal -->
    <div id="modal-edit-deadline" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
        <div class="glass p-6 rounded-2xl w-full max-w-sm relative">
             <button onclick="document.getElementById('modal-edit-deadline').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
             <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4"><i class="fa-solid fa-clock-rotate-left text-blue-500 mr-2"></i> Update Deadline</h3>
             <form method="POST">
                 <input type="hidden" name="assignment_id" id="edit_assignment_id">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">New Deadline Selection</label>
                         <input type="datetime-local" name="deadline" id="edit_deadline_input" required class="w-full input-glass bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                     </div>
                     <button type="submit" name="update_assignment" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg mt-4 shadow-md transition-colors">Confirm Update</button>
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
    <script>
        function openEditDeadline(id, currentDeadline) {
            document.getElementById('modal-edit-deadline').classList.remove('hidden');
            document.getElementById('edit_assignment_id').value = id;
            document.getElementById('edit_deadline_input').value = currentDeadline;
        }

        function switchTab(tabId) {
            // Update URL to persist tab state
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            // Hide all contents
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('active');
            });
            // Show target content
            document.getElementById('content-' + tabId).classList.remove('hidden');
            document.getElementById('content-' + tabId).classList.add('active');
            
            // Reset tab styles
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600', 'dark:border-blue-400', 'dark:text-blue-400');
                el.classList.add('border-transparent', 'text-gray-500');
            });
            // Activate target tab style
            const activeTab = document.getElementById('tab-' + tabId);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-blue-600', 'text-blue-600', 'dark:border-blue-400', 'dark:text-blue-400');
        }

        function generateSummary(noteId) {
             const summaryBox = document.getElementById('summary-' + noteId);
             const btn = event.currentTarget;
             
             if(summaryBox.classList.contains('hidden')) {
                  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
                  const title = btn.closest('.glass').querySelector('h3').innerText;

                  fetch('api/ai.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({ query: title, type: 'summary' })
                  })
                  .then(res => res.json())
                  .then(data => {
                       summaryBox.classList.remove('hidden');
                       if(data.success) {
                           summaryBox.innerHTML = `
                             <h4 class="font-bold text-indigo-700 dark:text-indigo-400 mb-2"><i class="fa-solid fa-bolt text-yellow-500 mr-1"></i> Quick Revision (AI Generated)</h4>
                             <div class="text-gray-700 dark:text-gray-300 text-sm space-y-1">${data.response}</div>
                           `;
                           btn.innerHTML = '<i class="fa-solid fa-check text-green-500"></i> Generated';
                       } else {
                           summaryBox.innerHTML = `<span class="text-red-500 font-medium text-xs">Error: ${data.error}</span>`;
                           btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> AI Summary';
                       }
                  })
                  .catch(err => {
                       summaryBox.classList.remove('hidden');
                       summaryBox.innerHTML = `<span class="text-red-500 text-xs text-center block p-2 font-medium">Please add your Gemini API Key in \`config.php\` for the local server to connect.</span>`;
                       btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> AI Summary';
                  });
             } else {
                  summaryBox.classList.add('hidden');
             }
        }
    </script>
</body>
</html>
