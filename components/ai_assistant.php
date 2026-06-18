<!-- AI Assistant Panel -->
<div id="aiAssistantPanel" class="ai-panel glass shadow-2xl flex flex-col overflow-hidden border border-indigo-200 dark:border-indigo-900/50">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gradient-to-r from-indigo-500/10 to-blue-500/10">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-robot text-sm"></i>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-800 dark:text-white">AI Doubt Solver</h3>
                <p class="text-xs text-indigo-500 font-medium">Online</p>
            </div>
        </div>
        <button onclick="toggleAIPanel()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition flex items-center justify-center">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    
    <!-- Chat Area -->
    <div id="aiChatSpace" class="flex-1 p-4 overflow-y-auto space-y-4 text-sm relative">
        <!-- AI Welcome Message -->
        <div class="flex gap-2">
            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-500 flex-shrink-0 flex items-center justify-center mt-1">
                <i class="fa-solid fa-robot text-xs"></i>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800/50 p-3 rounded-2xl rounded-tl-none text-gray-700 dark:text-gray-300 max-w-[85%]">
                <p>Hi <?= htmlspecialchars($_SESSION['full_name'] ?? 'Student') ?>! Need help with any subject? Ask me a concept, code, or for exam summaries.</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <button onclick="askAI('Explain Deadlock in OS')" class="bg-white dark:bg-gray-800 px-2 py-1 rounded shadow-sm hover:shadow text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 transition">Explain Deadlock in OS</button>
                    <button onclick="askAI('Summary of HCI Unit 1')" class="bg-white dark:bg-gray-800 px-2 py-1 rounded shadow-sm hover:shadow text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 transition">Summary of HCI Unit 1</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <form id="aiChatForm" class="p-3 border-t border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-900/50" onsubmit="handleAIChat(event)">
        <div class="relative flex items-center">
            <input type="text" id="aiQuery" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-full py-2.5 pl-4 pr-12 text-sm focus:ring-2 ring-indigo-500 outline-none text-gray-800 dark:text-gray-200 placeholder-gray-400" placeholder="Type your doubt here..." required autocomplete="off">
            <button type="submit" class="absolute right-2 w-8 h-8 rounded-full bg-indigo-500 hover:bg-indigo-600 text-white flex items-center justify-center transition shadow shadow-indigo-500/30">
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </div>
        <div class="flex gap-2 mt-2 px-1 text-xs justify-center">
            <button type="button" class="text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Explain Simply</button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button type="button" class="text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium"><i class="fa-solid fa-list-check mr-1"></i> Exam Point Summary</button>
        </div>
    </form>
</div>

<script>
    function toggleAIPanel() {
        const panel = document.getElementById('aiAssistantPanel');
        panel.classList.toggle('active');
    }

    function askAI(query) {
        document.getElementById('aiQuery').value = query;
        document.getElementById('aiChatForm').dispatchEvent(new Event('submit'));
    }

    function handleAIChat(e) {
        e.preventDefault();
        const input = document.getElementById('aiQuery');
        const query = input.value.trim();
        if(!query) return;

        const chatSpace = document.getElementById('aiChatSpace');

        // Add user message
        chatSpace.innerHTML += `
            <div class="flex gap-2 justify-end">
                <div class="bg-blue-500 text-white p-3 rounded-2xl rounded-tr-none max-w-[85%] shadow-sm">
                    <p>${query}</p>
                </div>
            </div>
        `;

        input.value = '';
        chatSpace.scrollTop = chatSpace.scrollHeight;

        // Simulate AI thinking
        const loadingId = 'loading-' + Date.now();
        chatSpace.innerHTML += `
             <div id="${loadingId}" class="flex gap-2">
                <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-500 flex-shrink-0 flex items-center justify-center mt-1">
                    <i class="fa-solid fa-robot text-xs fa-flip"></i>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800/50 p-3 rounded-2xl rounded-tl-none text-gray-500 dark:text-gray-400 max-w-[85%] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                </div>
            </div>
        `;
        chatSpace.scrollTop = chatSpace.scrollHeight;

        // Real API Call
        fetch('api/ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: query, type: 'doubt' })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById(loadingId).remove();
            let aiResponse = "";
            
            if(data.success) {
                aiResponse = data.response;
            } else {
                aiResponse = `<span class="text-red-500 font-medium">Error: ${data.error || 'Failed to fetch reply.'}</span><br><br><span class="text-xs text-gray-400">If you see an API key error, please open \`config.php\` and add your Gemini API Key.</span>`;
            }

            chatSpace.innerHTML += `
                <div class="flex gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-500 flex-shrink-0 flex items-center justify-center mt-1">
                        <i class="fa-solid fa-robot text-xs"></i>
                    </div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-100 dark:border-indigo-800/50 p-3 rounded-2xl rounded-tl-none text-gray-700 dark:text-gray-300 max-w-[90%]">
                        <div class="ai-generated-content text-sm">${aiResponse}</div>
                    </div>
                </div>
            `;
            chatSpace.scrollTop = chatSpace.scrollHeight;
        })
        .catch(err => {
            document.getElementById(loadingId).remove();
            chatSpace.innerHTML += `
                <div class="flex gap-2">
                    <div class="w-6 h-6 rounded-full bg-red-100 text-red-500 flex-shrink-0 flex items-center justify-center mt-1">
                        <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                    </div>
                    <div class="bg-red-50 border border-red-100 p-3 rounded-2xl rounded-tl-none text-red-700 max-w-[90%] text-sm">
                        Connection error to AI Server.
                    </div>
                </div>
            `;
            chatSpace.scrollTop = chatSpace.scrollHeight;
        });
    }
</script>
