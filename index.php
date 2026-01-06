<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BunthyCyberDev AI - Full Stack</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100..900&family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Core Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Syntax Highlighting (Prism) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
    <!-- Markdown Parser -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { font-family: 'Inter', 'Hanuman', sans-serif; }
        .font-hanuman { font-family: 'Hanuman', sans-serif; }
        .font-mono { font-family: 'Fira Code', monospace; }
        
        /* Chat Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.3); border-radius: 20px; }
        
        /* Code Block Styling */
        pre[class*="language-"] {
            border-radius: 0.75rem;
            margin: 0.5rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2d2d2d;
            padding: 0.25rem 1rem;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            color: #a0a0a0;
            font-size: 0.75rem;
            border-bottom: 1px solid #404040;
        }

        /* Mobile Responsive Tweaks */
        @media (max-width: 768px) {
            .mobile-full { height: 100dvh; }
            .sidebar-overlay { 
                position: fixed; inset: 0; background: rgba(0,0,0,0.5); 
                backdrop-filter: blur(2px); z-index: 40; 
            }
        }
    </style>
</head>
<body class="bg-base-200 h-dvh w-full overflow-hidden text-base-content antialiased flex">

    <!-- Sidebar (History) -->
    <div id="sidebar" class="fixed md:static inset-y-0 left-0 w-72 bg-base-100 border-r border-base-300 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50 flex flex-col h-full shadow-2xl md:shadow-none">
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-base-300 flex justify-between items-center">
             <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white font-bold">
                    AI
                </div>
                <span class="font-bold text-lg font-hanuman">BunthyAI</span>
            </div>
            <button id="closeSidebar" class="md:hidden btn btn-sm btn-ghost btn-circle">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <!-- New Chat Button -->
        <div class="p-3">
            <button id="btnNewChat" class="btn btn-primary btn-outline btn-block gap-2 justify-start">
                <i class="fa-solid fa-plus"></i> New Chat
            </button>
        </div>

        <!-- History List -->
        <div class="flex-1 overflow-y-auto custom-scroll p-2 space-y-1" id="historyList">
            <!-- Items loaded via JS -->
            <div class="text-center opacity-50 text-sm mt-10">Loading history...</div>
        </div>

        <!-- User/Settings Footer -->
        <div class="p-3 border-t border-base-300 bg-base-100/50">
             <div class="dropdown dropdown-top w-full">
                <div tabindex="0" role="button" class="btn btn-ghost w-full justify-start gap-3 px-2">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                            <span class="text-xs">U</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-start truncate overflow-hidden">
                        <span class="text-xs font-bold truncate">User Config</span>
                        <span class="text-[10px] opacity-60 truncate" id="userIdDisplay">...</span>
                    </div>
                    <i class="fa-solid fa-chevron-up ml-auto text-xs opacity-50"></i>
                </div>
                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow-xl bg-base-100 rounded-box w-52 mb-2 border border-base-200">
                    <li><a id="btnSettings"><i class="fa-solid fa-gear"></i> Settings</a></li>
                    <li><a id="btnExport"><i class="fa-solid fa-download"></i> Export Data</a></li>
                    <li><a id="btnTheme"><i class="fa-solid fa-moon"></i> Toggle Theme</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="sidebar-overlay hidden md:hidden"></div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-full relative min-w-0">
        
        <!-- Header -->
        <div class="h-16 border-b border-base-300 bg-base-100/80 backdrop-blur flex justify-between items-center px-4 flex-shrink-0 z-10">
            <div class="flex items-center gap-3">
                <button id="openSidebar" class="md:hidden btn btn-ghost btn-circle btn-sm">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div class="flex flex-col">
                    <h2 class="font-bold text-sm md:text-base truncate max-w-[200px] md:max-w-md" id="currentChatTitle">New Conversation</h2>
                    <span class="text-[10px] opacity-50 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Online
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-1">
                <button id="btnVoiceToggle" class="btn btn-ghost btn-circle btn-sm text-primary" title="Voice Response">
                     <i class="fa-solid fa-volume-high"></i>
                </button>
                <a href="Docs/index.html" target="_blank" class="btn btn-ghost btn-circle btn-sm" title="Docs">
                     <i class="fa-regular fa-file-code"></i>
                </a>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 overflow-y-auto custom-scroll p-4 md:p-8 scroll-smooth" id="chatContainer">
            <div id="messagesSpace" class="max-w-3xl mx-auto space-y-6 pb-4">
                <!-- Welcome -->
                <div class="hero min-h-[50vh] flex flex-col items-center justify-center opacity-70" id="emptyState">
                   <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center mb-6">
                        <i class="fa-solid fa-robot text-4xl text-primary"></i>
                   </div>
                   <h3 class="text-2xl font-bold font-hanuman text-center">How can I help you?</h3>
                   <p class="text-sm opacity-60 mt-2 text-center max-w-sm">
                       I'm a full-stack capable AI. Ask me to code, explain concepts, or draft content.
                   </p>
                </div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="p-4 bg-base-100 border-t border-base-300">
            <div class="max-w-3xl mx-auto relative">
                <!-- Attachment Preview -->
                <div id="filePreview" class="hidden absolute -top-14 left-0 bg-base-200 border border-base-300 rounded-lg p-2 flex items-center gap-3 shadow-lg animate-in fade-in slide-in-from-bottom-2">
                     <div class="w-8 h-8 rounded bg-primary/10 flex items-center justify-center text-primary">
                         <i class="fa-regular fa-file"></i>
                     </div>
                     <div class="flex flex-col">
                         <span class="text-xs font-bold truncate max-w-[120px]" id="previewFileName">filename.txt</span>
                         <span class="text-[10px] opacity-50">Ready to upload</span>
                     </div>
                     <button id="btnClearFile" class="btn btn-ghost btn-xs btn-circle text-error"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form id="chatForm" class="flex items-end gap-2 bg-base-200/50 p-2 rounded-3xl border border-base-300 focus-within:border-primary/50 focus-within:ring-2 focus-within:ring-primary/10 transition-all">
                    
                    <button type="button" id="btnUpload" class="btn btn-circle btn-ghost btn-sm text-base-content/60 hover:text-primary hover:bg-base-200">
                        <i class="fa-solid fa-paperclip"></i>
                    </button>
                    <input type="file" id="fileInput" hidden>
                    
                    <textarea id="messageInput" rows="1" class="textarea textarea-ghost flex-1 focus:outline-none p-2 min-h-[2.5rem] max-h-32 resize-none bg-transparent text-base leading-tight" placeholder="Message BunthyAI..."></textarea>
                    
                    <button type="button" id="btnMic" class="btn btn-circle btn-ghost btn-sm text-base-content/60 hover:text-primary transition-colors">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                    
                    <button type="submit" id="btnSend" class="btn btn-primary btn-circle btn-sm shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                </form>
                <div class="text-center text-[10px] opacity-40 mt-2">
                    AI can make mistakes. Please verify important information.
                </div>
            </div>
        </div>

    </div>

    <!-- Code Editor Modal (Monaco-like simple view) -->
    <dialog id="codeModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box w-11/12 max-w-5xl h-[80vh] p-0 flex flex-col bg-[#1e1e1e] text-white">
            <div class="flex justify-between items-center p-3 border-b border-[#333] bg-[#2d2d2d]">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#a0a0a0]" id="codeModalLang">JAVASCRIPT</span>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-xs btn-ghost text-white" id="btnCopyCode"><i class="fa-regular fa-copy"></i> Copy</button>
                    <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost text-white"><i class="fa-solid fa-xmark"></i></button></form>
                </div>
            </div>
            <div class="flex-1 overflow-auto p-4 font-mono custom-scroll relative">
                <pre><code id="codeModalContent" class="language-javascript"></code></pre>
            </div>
        </div>
    </dialog>

    <!-- Settings Modal -->
    <dialog id="settingsModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Settings</h3>
            <div class="py-4 space-y-4">
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">Enable Voice Response</span>
                        <input type="checkbox" class="toggle toggle-primary" id="settingVoiceToggle" />
                    </label>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Voice Speed</span>
                        <span class="label-text-alt" id="voiceSpeedValue">1.0x</span>
                    </label>
                    <input type="range" min="0.5" max="2" step="0.1" value="1.0" class="range range-xs range-primary" id="settingVoiceSpeed" />
                </div>
                <!-- Clear Data -->
                <div class="divider"></div>
                 <button id="btnClearAllData" class="btn btn-error btn-outline btn-sm w-full">
                    <i class="fa-solid fa-triangle-exclamation"></i> Clear All History
                </button>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn">Close</button>
                </form>
            </div>
        </div>
    </dialog>

    <script src="js/app.js"></script>
</body>
</html>