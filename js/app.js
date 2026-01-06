$(document).ready(function () {
    // --- Constants & Config ---
    const CONFIG = {
        apiChat: 'api/chat.php', // Proxy to AI
        apiHistory: 'api/history.php', // Local History
        voiceSpeed: parseFloat(localStorage.getItem('ai_voice_speed')) || 1.0,
        voiceEnabled: localStorage.getItem('ai_voice_enabled') === 'true' // Default false
    };

    // --- State ---
    let state = {
        userId: localStorage.getItem('ai_user_id') || generateUserId(),
        currentChatId: null,
        currentChatMessages: [],
        speechRec: null,
        isListening: false,
        selectedFile: null
    };

    // Save User ID immediately
    localStorage.setItem('ai_user_id', state.userId);
    $('#userIdDisplay').text(state.userId);

    // --- Initialization ---
    init();

    function init() {
        // Setup Markdown
        marked.setOptions({
            highlight: function (code, lang) {
                if (Prism.languages[lang]) {
                    return Prism.highlight(code, Prism.languages[lang], lang);
                } else {
                    return code;
                }
            },
            breaks: true
        });

        // Setup Voice
        setupVoice();

        // Load History
        loadHistoryList();

        // Sync Settings UI
        $('#settingVoiceToggle').prop('checked', CONFIG.voiceEnabled);
        $('#settingVoiceSpeed').val(CONFIG.voiceSpeed);
        $('#voiceSpeedValue').text(CONFIG.voiceSpeed + 'x');
        updateVoiceIcon();

        // Auto-resize textarea
        $('#messageInput').on('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    function generateUserId() {
        return 'user_' + Math.random().toString(36).substr(2, 9);
    }

    // --- API Calls ---

    function loadHistoryList() {
        $.get(CONFIG.apiHistory, { action: 'list_history', user_id: state.userId }, function (res) {
            const list = $('#historyList');
            list.empty();

            if (res.history && res.history.length > 0) {
                res.history.forEach(chat => {
                    const active = chat.id === state.currentChatId ? 'bg-base-200 border-l-4 border-primary' : 'hover:bg-base-200 border-l-4 border-transparent';
                    const item = `
                        <div class="p-3 rounded cursor-pointer transition-all ${active} group relative" onclick="loadChat('${chat.id}')">
                            <div class="text-sm font-medium truncate pr-6">${chat.title}</div>
                            <div class="text-[10px] opacity-40">${new Date(chat.updated_at * 1000).toLocaleDateString()}</div>
                            <button onclick="deleteChat(event, '${chat.id}')" class="absolute right-2 top-3 opacity-0 group-hover:opacity-100 btn btn-xs btn-ghost btn-circle text-error">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                    list.append(item);
                });
            } else {
                list.html('<div class="text-center opacity-40 text-xs mt-4">No history yet.</div>');
            }
        });
    }

    window.loadChat = function (chatId) {
        state.currentChatId = chatId;
        $('#messagesSpace').empty();
        $('#emptyState').hide();
        $('#loadingIndicator').addClass('hidden');

        loadHistoryList();

        $.get(CONFIG.apiHistory, { action: 'get_chat', user_id: state.userId, chat_id: chatId }, function (data) {
            try {
                const chat = typeof data === 'string' ? JSON.parse(data) : data;
                if (chat.error) return Swal.fire('Error', 'Chat not found', 'error');

                $('#currentChatTitle').text(chat.title);
                state.currentChatMessages = chat.messages || [];

                // Render Messages
                state.currentChatMessages.forEach(msg => {
                    if (msg.html) renderMessageUI(msg.isUser, msg.html, msg.file);
                    else renderMessageUI(msg.isUser, marked.parse(msg.text), msg.file);
                });

                scrollToBottom();

            } catch (e) { console.error(e); }
        });

        if (window.innerWidth < 768) {
            $('#sidebar').addClass('-translate-x-full');
            $('#mobileOverlay').addClass('hidden');
        }
    };

    window.deleteChat = function (e, chatId) {
        e.stopPropagation();
        if (!confirm('Delete this chat?')) return;

        $.post(CONFIG.apiHistory + '?action=delete_chat&user_id=' + state.userId, { chat_id: chatId }, function () {
            if (state.currentChatId === chatId) {
                state.currentChatId = null;
                state.currentChatMessages = [];
                $('#messagesSpace').empty();
                $('#emptyState').show();
            }
            loadHistoryList();
        });
    };

    // --- Message Logic ---

    $('#chatForm').on('submit', async function (e) {
        e.preventDefault();
        const text = $('#messageInput').val().trim();
        if (!text && !state.selectedFile) return;

        const fileToSend = state.selectedFile;
        const fileDisplay = state.selectedFile ? state.selectedFile.name : null;
        const msgObj = { text: text, isUser: true, file: fileDisplay, html: text.replace(/\n/g, '<br>') };

        state.currentChatMessages.push(msgObj);
        renderMessageUI(true, msgObj.html, fileDisplay);

        $('#messageInput').val('').css('height', 'auto');
        clearFile();
        $('#emptyState').hide();

        scrollToBottom();

        if (!state.currentChatId) {
            state.currentChatId = 'chat_' + Date.now();
            const title = text.substring(0, 30) || "New Conversation";
            saveChatHistory(title);
        }

        renderLoading(true);

        try {
            const formData = new FormData();
            formData.append('text', text);
            if (fileToSend) formData.append('file', fileToSend);

            const aiRes = await $.ajax({
                url: CONFIG.apiChat,
                method: 'POST',
                data: formData,
                processData: false, contentType: false,
                dataType: 'json'
            });

            renderLoading(false);

            const rawText = aiRes.result || aiRes.error || "No response.";
            const parsedHtml = marked.parse(rawText);

            state.currentChatMessages.push({ text: rawText, isUser: false, html: parsedHtml });
            renderMessageUI(false, parsedHtml);

            saveChatHistory();

            if (CONFIG.voiceEnabled) speak(rawText);

        } catch (err) {
            renderLoading(false);
            renderMessageUI(false, '<span class="text-error">Connection Error. Please try again.</span>');
            console.error(err);
        }
    });

    function saveChatHistory(newTitle = null) {
        let title = "New Chat";
        if (newTitle) title = newTitle;
        else {
            const domTitle = $('#currentChatTitle').text();
            if (domTitle !== 'New Conversation') title = domTitle;
        }

        $('#currentChatTitle').text(title);

        const payload = {
            chat_id: state.currentChatId,
            title: title,
            content: JSON.stringify(state.currentChatMessages)
        };

        $.post(CONFIG.apiHistory + '?action=save_chat&user_id=' + state.userId, payload, function () {
            loadHistoryList();
        });
    }

    // --- UI Rendering ---

    function renderMessageUI(isUser, html, fileName = null) {
        const align = isUser ? 'justify-end' : 'justify-start';
        const bg = isUser ? 'bg-primary text-primary-content' : 'bg-base-200 text-base-content';
        const radius = isUser ? 'rounded-br-none' : 'rounded-bl-none';

        let fileHtml = '';
        if (fileName) {
            fileHtml = `
            <div class="flex items-center gap-2 mb-2 bg-black/10 p-2 rounded text-xs">
                <i class="fa-solid fa-paperclip"></i> ${fileName}
            </div>`;
        }

        const dom = `
            <div class="flex ${align} animate-in fade-in slide-in-from-bottom-2 duration-300">
                <div class="max-w-[85%] md:max-w-[75%]">
                     ${!isUser ? '<div class="text-xs opacity-50 mb-1 ml-1 font-bold">BunthyAI</div>' : ''}
                    <div class="p-3 md:p-4 rounded-2xl ${radius} ${bg} shadow-sm overflow-hidden prose prose-sm prose-invert max-w-none break-words leading-relaxed">
                        ${fileHtml}
                        ${html}
                    </div>
                </div>
            </div>
        `;

        $('#messagesSpace').append(dom);

        // Code blocks
        $('#messagesSpace pre code').each(function () {
            const el = $(this);
            if (el.parent().has('.code-header').length > 0) return;

            const langClass = el.attr('class') || 'language-text';
            const lang = langClass.replace('language-', '').toUpperCase();

            const header = `
                <div class="code-header select-none">
                    <span>${lang}</span>
                    <button class="hover:text-white transition-colors" onclick="openCodeModal(this)">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                </div>
            `;

            el.parent().wrap('<div class="relative group"></div>').before(header);
            Prism.highlightElement(this);
        });

        scrollToBottom();
    }

    function renderLoading(show) {
        $('#loadingState').remove();
        if (!show) return;

        const loader = `
            <div id="loadingState" class="flex justify-start animate-in fade-in slide-in-from-bottom-2">
                 <div class="bg-base-200 p-4 rounded-2xl rounded-bl-none shadow-sm flex gap-1 items-center h-10">
                    <span class="loading loading-dots loading-sm opacity-50"></span>
                </div>
            </div>
        `;
        $('#messagesSpace').append(loader);
        scrollToBottom();
    }

    // --- Interactions ---

    window.openCodeModal = function (btn) {
        const codeBlock = $(btn).parent().next('pre').find('code');
        const lang = codeBlock.attr('class');
        $('#codeModalContent').attr('class', lang).text(codeBlock.text());
        $('#codeModalLang').text(lang.replace('language-', '').toUpperCase());
        Prism.highlightElement(document.getElementById('codeModalContent'));
        document.getElementById('codeModal').showModal();
    };

    $('#btnCopyCode').click(function () {
        navigator.clipboard.writeText($('#codeModalContent').text());
        const original = $(this).html();
        $(this).html('<i class="fa-solid fa-check"></i> Copied');
        setTimeout(() => $(this).html(original), 2000);
    });

    $('#btnNewChat').click(function () {
        state.currentChatId = null;
        state.currentChatMessages = [];
        $('#messagesSpace').empty();
        $('#emptyState').show();
        $('#currentChatTitle').text('New Conversation');
        if (window.innerWidth < 768) $('#closeSidebar').click();
    });

    // File
    $('#btnUpload').click(() => $('#fileInput').click());
    $('#fileInput').change(function (e) {
        if (e.target.files.length) {
            state.selectedFile = e.target.files[0];
            $('#previewFileName').text(state.selectedFile.name);
            $('#filePreview').removeClass('hidden').addClass('flex');
        }
    });

    function clearFile() {
        state.selectedFile = null;
        $('#fileInput').val('');
        $('#filePreview').addClass('hidden').removeClass('flex');
    }
    $('#btnClearFile').click(clearFile);

    // Settings
    $('#btnSettings').click(() => {
        document.getElementById('settingsModal').showModal();
    });

    $('#settingVoiceToggle').change(function () {
        CONFIG.voiceEnabled = $(this).is(':checked');
        localStorage.setItem('ai_voice_enabled', CONFIG.voiceEnabled);
        updateVoiceIcon();
    });

    $('#settingVoiceSpeed').on('input', function () {
        CONFIG.voiceSpeed = $(this).val();
        $('#voiceSpeedValue').text(CONFIG.voiceSpeed + 'x');
        localStorage.setItem('ai_voice_speed', CONFIG.voiceSpeed);
    });

    $('#btnClearAllData').click(function () {
        if (confirm("Are you sure? This will delete ALL history.")) {
            localStorage.clear();
            location.reload();
        }
    });

    // Voice
    function setupVoice() {
        if ('webkitSpeechRecognition' in window) {
            state.speechRec = new webkitSpeechRecognition();
            state.speechRec.continuous = false;
            state.speechRec.onresult = function (e) {
                $('#messageInput').val(e.results[0][0].transcript);
                $('#chatForm').submit();
            };
            state.speechRec.onend = () => {
                $('#btnMic').removeClass('text-error animate-pulse');
            };
        } else {
            $('#btnMic').hide();
        }

        $('#btnMic').click(function () {
            if (!state.speechRec) return;
            state.speechRec.start();
            $(this).addClass('text-error animate-pulse');
        });

        $('#btnVoiceToggle').click(function () {
            CONFIG.voiceEnabled = !CONFIG.voiceEnabled;
            // Mirror to settings modal
            $('#settingVoiceToggle').prop('checked', CONFIG.voiceEnabled);
            localStorage.setItem('ai_voice_enabled', CONFIG.voiceEnabled);
            updateVoiceIcon();
        });
    }

    function updateVoiceIcon() {
        if (CONFIG.voiceEnabled) {
            $('#btnVoiceToggle').removeClass('text-base-content/30').addClass('text-primary');
        } else {
            $('#btnVoiceToggle').removeClass('text-primary').addClass('text-base-content/30');
        }
    }

    function speak(text) {
        if (!window.speechSynthesis) return;
        window.speechSynthesis.cancel();
        const clean = text.replace(/[*`#\[\]]/g, '');
        const u = new SpeechSynthesisUtterance(clean);
        u.rate = CONFIG.voiceSpeed;
        window.speechSynthesis.speak(u);
    }

    function scrollToBottom() {
        const c = $('#chatContainer');
        c.scrollTop(c[0].scrollHeight);
    }

    $('#openSidebar').click(() => {
        $('#sidebar').removeClass('-translate-x-full');
        $('#mobileOverlay').removeClass('hidden');
    });

    $('#closeSidebar, #mobileOverlay').click(() => {
        $('#sidebar').addClass('-translate-x-full');
        $('#mobileOverlay').addClass('hidden');
    });

    $('#btnTheme').click(() => {
        const html = $('html');
        const cur = html.attr('data-theme');
        const next = cur === 'light' ? 'dark' : 'light';
        html.attr('data-theme', next);
    });

    $('#btnExport').click(() => {
        const blob = new Blob([JSON.stringify(state.currentChatMessages, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `chat_export_${state.currentChatId || 'new'}.json`;
        a.click();
    });

});
