import os
import re

dir_path = r'C:\Users\Laptop-PC\.gemini\antigravity\scratch\classconnecto'

for filename in os.listdir(dir_path):
    if filename.endswith('.php') and filename != 'index.php':
        filepath = os.path.join(dir_path, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Add Tailwind config
        if 'tailwind.config' not in content:
            content = content.replace(
                '<script src="https://cdn.tailwindcss.com"></script>',
                '<script src="https://cdn.tailwindcss.com"></script>\n    <script>tailwind.config = { darkMode: "class" }</script>'
            )
        
        # Add background include just after <body ...>
        if 'components/background.php' not in content:
            content = re.sub(
                r'(<body[^>]*>)',
                r'\1\n    <?php include "components/background.php"; ?>',
                content
            )
        
        # Remove the hardcoded bg so transparent background blobs show through
        if 'bg-gray-50 dark:bg-slate-900' in content:
            content = content.replace('bg-gray-50 dark:bg-slate-900', '')
            
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)

# Process index.php separately
index_path = os.path.join(dir_path, 'index.php')
with open(index_path, 'r', encoding='utf-8') as f:
    content = f.read()
    if 'tailwind.config' not in content:
        content = content.replace(
            '<script src="https://cdn.tailwindcss.com"></script>',
            '<script src="https://cdn.tailwindcss.com"></script>\n    <script>tailwind.config = { darkMode: "class" }</script>'
        )
with open(index_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("UI Boilerplate injection complete.")
