<?php
$dir = __DIR__;
$files = scandir($dir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'index.php' && $file !== 'refactor_ui.php') {
        $content = file_get_contents($dir . '/' . $file);
        
        // Add Tailwind config
        if (strpos($content, 'tailwind.config') === false) {
            $content = str_replace('<script src="https://cdn.tailwindcss.com"></script>', "<script src=\"https://cdn.tailwindcss.com\"></script>\n    <script>tailwind.config = { darkMode: 'class' }</script>", $content);
        }
        
        // Add background
        if (strpos($content, 'components/background.php') === false) {
            $content = preg_replace('/(<body[^>]*>)/', "$1\n    <?php include 'components/background.php'; ?>", $content);
        }
        
        // Remove old backgrounds
        $content = str_replace('bg-gray-50 dark:bg-slate-900', '', $content);
        
        file_put_contents($dir . '/' . $file, $content);
    }
}

// Index.php separately
$index = file_get_contents($dir . '/index.php');
if (strpos($index, 'tailwind.config') === false) {
    $index = str_replace('<script src="https://cdn.tailwindcss.com"></script>', "<script src=\"https://cdn.tailwindcss.com\"></script>\n    <script>tailwind.config = { darkMode: 'class' }</script>", $index);
}
// For index.php, add background in body
if (strpos($index, 'components/background.php') === false) {
    $index = preg_replace('/(<body[^>]*>)/', "$1\n    <?php include 'components/background.php'; ?>", $index);
}
$index = str_replace('from-blue-50 to-indigo-100', '', $index);

file_put_contents($dir . '/index.php', $index);
echo "UI Updated successfully!";
?>
