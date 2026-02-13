<?php
include 'db.php';

$rows = [];
$message = "";
$passkeyId = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $key      = trim($_POST['key'] ?? '');
    $value    = trim($_POST['value'] ?? '');

    if ($username !== "" && $key !== "") {

        $hashedKey = md5($key);

        // Validate username AND password
        $stmt = $conn->prepare("SELECT id FROM passkey_table WHERE username = ? AND passkey = ?");
        $stmt->bind_param("ss", $username, $hashedKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $passkeyId = $row['id'];

            // DELETE ALL (only user's own texts)
            if ($value === "<del />") {
                $delStmt = $conn->prepare("DELETE FROM value_table WHERE passkey_id = ?");
                $delStmt->bind_param("i", $passkeyId);
                $delStmt->execute();
                //$message = "All your data deleted successfully.";
            }

            // FETCH DESC (only user's own texts)
            elseif ($value === "") {
                $fetch = $conn->prepare(
                    "SELECT key_value, createdAt FROM value_table WHERE passkey_id = ? ORDER BY id DESC"
                );
                $fetch->bind_param("i", $passkeyId);
                $fetch->execute();
                $fetchResult = $fetch->get_result();
                while ($r = $fetchResult->fetch_assoc()) {
                    $rows[] = $r;
                }
            }

            // INSERT (linked to user's passkey_id)
            else {
                $insert = $conn->prepare(
                    "INSERT INTO value_table (passkey_id, key_value) VALUES (?, ?)"
                );
                $insert->bind_param("is", $passkeyId, $value);
                $insert->execute();
               // $message = "Data saved successfully.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Admin - WordPress Style</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f0f0f1;
            color: #3c434a;
        }

        /* WordPress specific tweaks */
        .wp-sidebar { background-color: #1d2327; }
        .wp-sidebar-item:hover { background-color: #2c3338; color: #72aee6; }
        .wp-sidebar-item.active { background-color: #2271b1; color: #fff; }
        .wp-primary-button { background-color: #2271b1; }
        .wp-primary-button:hover { background-color: #135e96; }
        
        /* Hide scrollbar for cleaner look */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .sidebar-transition { transition: width 0.2s ease-in-out; }
        
        /* Row Actions hover effect */
        .row-actions { visibility: hidden; opacity: 0; transition: opacity 0.1s; }
        tr:hover .row-actions { visibility: visible; opacity: 1; }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Copy button styles */
        .copy-btn {
            opacity: 0;
            transition: opacity 0.2s;
        }
        tr:hover .copy-btn {
            opacity: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar-transition {
                width: 60px !important;
                position: relative;
            }
            .sidebar-text {
                display: none !important;
            }
            .mobile-overlay {
                display: none;
            }
            /* Make copy button always visible on mobile */
            .copy-btn {
                opacity: 1;
            }
            /* Better table handling */
            table {
                font-size: 12px;
            }
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #2271b1;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="min-h-screen flex">

    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="mobile-overlay" onclick="toggleMobileSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar-transition w-64 wp-sidebar flex flex-col z-50 shrink-0">
        <div class="h-12 flex items-center justify-center md:justify-start md:px-3 mb-2">
            <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white md:mr-3 shrink-0">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            </div>
            <span class="sidebar-text text-white font-medium whitespace-nowrap">Recycle Admin</span>
        </div>

        <nav class="flex-1">
            <button onclick="switchTab('text')" id="tab-text" class="wp-sidebar-item active w-full flex items-center justify-center md:justify-start px-3 py-2 transition-all relative" title="Text Management">
                <i data-lucide="file-text" class="w-[18px] h-[18px] shrink-0"></i>
                <span class="sidebar-text ml-3 text-sm font-medium whitespace-nowrap">Text Management</span>
            </button>
            <button onclick="switchTab('generate')" id="tab-generate" class="wp-sidebar-item w-full flex items-center justify-center md:justify-start px-3 py-2 transition-all text-gray-300 relative" title="Generate Account">
                <i data-lucide="user-plus" class="w-[18px] h-[18px] shrink-0"></i>
                <span class="sidebar-text ml-3 text-sm font-medium whitespace-nowrap">Generate Account</span>
            </button>
            <div class="border-t border-gray-700 my-2 opacity-30"></div>
        </nav>

        <button onclick="toggleSidebar()" class="hidden md:flex p-3 text-gray-400 hover:text-white transition-colors items-center justify-center border-t border-gray-700">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Header -->
        <header class="h-10 bg-white border-b border-[#dcdcde] flex items-center justify-between px-4 shrink-0">
            <div class="flex items-center gap-2 md:gap-4 text-sm">
                <span class="font-medium flex items-center gap-1 cursor-pointer hover:text-blue-600 text-xs md:text-sm">
                   Howdy, <span id="display-username">Admin</span>
                </span>
            </div>
            <div class="flex items-center gap-4">
                <i data-lucide="bell" class="w-4 h-4 text-gray-500 cursor-pointer hidden sm:block"></i>
                <i data-lucide="help-circle" class="w-4 h-4 text-gray-500 cursor-pointer hidden sm:block"></i>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 md:p-6 max-w-5xl overflow-y-auto w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                <h1 id="page-title" class="text-xl md:text-2xl font-normal text-[#1d2327]">Recycle Text Dashboard</h1>
                <button onclick="openHelpModal()" class="px-3 py-1 border border-[#2271b1] text-[#2271b1] rounded text-sm font-medium hover:bg-[#f0f6fb] transition-colors">
                    Help & Documentation
                </button>
            </div>

            <!-- TAB 1: TEXT MANAGEMENT -->
            <div id="view-text" class="space-y-6">
                <!-- Form Card -->
                <div class="bg-white border border-[#dcdcde] shadow-sm rounded">
                    <div class="p-4 border-b border-[#dcdcde] font-medium text-sm flex justify-between items-center">
                        <span>New Entry</span>
                        <span class="text-xs text-gray-400 hidden sm:inline">Security: High</span>
                    </div>
                    <div class="p-4 md:p-5">
                        <form method="POST" id="paste-form" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-sm font-medium text-[#1d2327]">Username</label>
                                    <input type="text" id="input-username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                        class="w-full px-3 py-1.5 border border-[#8c8f94] rounded text-sm focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] outline-none transition-all">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-sm font-medium text-[#1d2327]">Passkey</label>
                                    <div class="relative">
                                        <input type="password" id="input-passkey" name="key" value="<?= htmlspecialchars($_POST['key'] ?? '') ?>"
                                            class="w-full pl-3 pr-20 py-1.5 border border-[#8c8f94] rounded text-sm focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] outline-none transition-all">
                                        <button type="button" onclick="togglePasskey()" id="lock-btn"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1.5 px-2 py-0.5 bg-[#f6f7f7] border border-[#dcdcde] rounded text-[11px] font-medium hover:bg-gray-100 transition-colors">
                                            <i data-lucide="lock" class="w-3 h-3" id="lock-icon"></i>
                                            <span id="lock-text">Unlock</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-sm font-medium text-[#1d2327]">Content Value</label>
                                <textarea id="input-content" name="value" placeholder="Paste your text here..."
                                    class="w-full h-32 px-3 py-2 border border-[#8c8f94] rounded text-sm focus:border-[#2271b1] focus:ring-1 focus:ring-[#2271b1] outline-none transition-all resize-none"></textarea>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button type="submit" class="px-6 py-2 wp-primary-button text-white rounded text-sm font-semibold transition-colors shadow-sm">
                                    Publish Entry
                                </button>
                                <button type="button" onclick="openModal()" class="px-6 py-2 bg-[#f6f7f7] text-[#2271b1] border border-[#2271b1] rounded text-sm font-semibold hover:bg-[#f0f6fb] transition-colors">
                                    Get Credentials
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Section -->
                <?php if (!empty($rows)): ?>
                <div class="bg-white border border-[#dcdcde] shadow-sm rounded overflow-hidden">
                    <div class="p-3 bg-white border-b border-[#dcdcde] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 overflow-x-auto">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="font-semibold text-[#1d2327] border-r pr-3 border-gray-300">Total Paste (<span id="count-total"><?= count($rows) ?></span>)</span>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input type="text" id="searchInput" placeholder="Search data..." class="px-2 py-1 text-xs border border-gray-300 rounded outline-none flex-1 sm:flex-none">
                            <button onclick="searchTable()" class="px-2 py-1 bg-[#f6f7f7] border border-[#dcdcde] text-xs rounded hover:bg-gray-100">Search</button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[600px]">
                            <thead>
                                <tr class="bg-[#f6f7f7] text-[13px] font-bold text-[#1d2327] border-b border-[#dcdcde]">
                                    <th class="px-4 py-3 font-semibold">Data / Content Snippet</th>
                                    <th class="px-4 py-3 font-semibold w-48">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="pastes-table-body">
                                <?php foreach ($rows as $row): ?>
                                <tr class="text-[13px] border-b border-[#f0f0f1] hover:bg-[#f6f7f7] transition-colors group">
                                    <td class="px-4 py-3">
                                        <div class="flex items-start gap-2">
                                            <button onclick="copyToClipboard(this)" data-content="<?= htmlspecialchars($row['key_value']) ?>" 
                                                class="copy-btn shrink-0 mt-0.5 p-1.5 bg-[#f6f7f7] border border-[#dcdcde] rounded hover:bg-[#2271b1] hover:text-white hover:border-[#2271b1] transition-all" 
                                                title="Copy to clipboard">
                                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <div class="font-medium text-[#2271b1] break-words"><?= nl2br(htmlspecialchars($row['key_value'])) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[#646970]">
                                        <div class="text-xs">Published</div>
                                        <div><?= htmlspecialchars($row['createdAt']) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-white border-t border-[#dcdcde] flex justify-between items-center text-xs text-[#646970]">
                        <span><span id="count-footer"><?= count($rows) ?></span> items</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: GENERATE ACCOUNT -->
            <div id="view-generate" class="hidden">
                <div class="bg-white border border-[#dcdcde] shadow-sm rounded p-6 md:p-8 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-[#f0f6fb] rounded-full flex items-center justify-center text-[#2271b1] mb-4">
                        <i data-lucide="user-plus" class="w-8 h-8"></i>
                    </div>
                    <h2 class="text-xl font-medium mb-2">Account Management</h2>
                    <p class="text-[#646970] max-w-md mb-6 text-sm">
                        Create a new account to securely store and manage your text entries. Click the button below to register.
                    </p>
                    <button onclick="openModal()" class="px-8 py-2.5 wp-primary-button text-white rounded font-semibold transition-shadow shadow-md hover:shadow-lg">
                        Create New Account
                    </button>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-auto p-4 text-[13px] text-[#646970] flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>Thank you for using <span class="font-semibold">Recycle Text</span>.</div>
            <div>Version 2.4.0</div>
        </footer>
    </div>

    <!-- Registration Modal -->
    <div id="registrationModal" class="modal" onclick="handleModalBackdropClick(event, 'registrationModal')">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Account</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Username
                </label>
                <input
                    type="text"
                    id="regUsername"
                    placeholder="Choose a username"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1]"
                />
                <p id="usernameError" class="text-xs text-red-600 mt-1 hidden"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    id="regPassword"
                    placeholder="Choose a password"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2271b1] focus:border-[#2271b1]"
                />
            </div>

            <p id="regMessage" class="text-sm mb-4 hidden"></p>

            <div class="flex gap-2">
                <button
                    onclick="registerUser()"
                    class="flex-1 wp-primary-button text-white py-2 rounded-md text-sm font-medium transition-colors"
                >
                    Register
                </button>
                <button
                    onclick="closeModal()"
                    class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Help Documentation Modal -->
    <div id="helpModal" class="modal" onclick="handleModalBackdropClick(event, 'helpModal')">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-[#1d2327]">Help & Documentation</h2>
                <button onclick="closeHelpModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="border-l-4 border-[#2271b1] pl-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#2271b1] text-white flex items-center justify-center font-bold text-sm shrink-0">1</div>
                        <div>
                            <h3 class="font-semibold text-lg text-[#1d2327] mb-2">Create a New Account</h3>
                            <p class="text-sm text-[#646970] mb-2">To get started, you need to create an account:</p>
                            <ul class="list-disc list-inside text-sm text-[#646970] space-y-1 ml-2">
                                <li>Click on the <strong>"Generate Account"</strong> tab in the sidebar</li>
                                <li>Click the <strong>"Create New Account"</strong> button</li>
                                <li>Enter your desired username and password</li>
                                <li>Click <strong>"Register"</strong> to create your account</li>
                                <li>Remember your credentials - you'll need them to access your data</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="border-l-4 border-[#72aee6] pl-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#72aee6] text-white flex items-center justify-center font-bold text-sm shrink-0">2</div>
                        <div>
                            <h3 class="font-semibold text-lg text-[#1d2327] mb-2">Login and Save Text</h3>
                            <p class="text-sm text-[#646970] mb-2">Once you have an account, you can save text entries:</p>
                            <ul class="list-disc list-inside text-sm text-[#646970] space-y-1 ml-2">
                                <li>Go to the <strong>"Text Management"</strong> tab</li>
                                <li>Enter your <strong>username</strong> and <strong>passkey</strong></li>
                                <li>Click the lock button to lock your credentials (optional)</li>
                                <li>Type or paste your text in the <strong>"Content Value"</strong> field</li>
                                <li>Click <strong>"Publish Entry"</strong> to save your text</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="border-l-4 border-[#00a32a] pl-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#00a32a] text-white flex items-center justify-center font-bold text-sm shrink-0">3</div>
                        <div>
                            <h3 class="font-semibold text-lg text-[#1d2327] mb-2">View Your Saved Entries</h3>
                            <p class="text-sm text-[#646970] mb-2">To view your previously saved text entries:</p>
                            <ul class="list-disc list-inside text-sm text-[#646970] space-y-1 ml-2">
                                <li>Enter your <strong>username</strong> and <strong>passkey</strong></li>
                                <li>Leave the <strong>"Content Value"</strong> field empty</li>
                                <li>Click <strong>"Publish Entry"</strong> to load your data</li>
                                <li>Your entries will appear in a table below the form</li>
                                <li>Hover over any entry to see the <strong>copy button</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="border-l-4 border-[#f0b849] pl-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#f0b849] text-white flex items-center justify-center font-bold text-sm shrink-0">4</div>
                        <div>
                            <h3 class="font-semibold text-lg text-[#1d2327] mb-2">Copy Text to Clipboard</h3>
                            <p class="text-sm text-[#646970] mb-2">Easily copy any saved entry:</p>
                            <ul class="list-disc list-inside text-sm text-[#646970] space-y-1 ml-2">
                                <li>Hover over any row in the data table</li>
                                <li>Click the <strong>copy icon</strong> that appears on the left</li>
                                <li>The text will be copied to your clipboard</li>
                                <li>A notification will confirm the copy action</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Security Tips -->
                <div class="bg-[#f0f6fb] border border-[#2271b1] rounded p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="shield-check" class="w-5 h-5 text-[#2271b1] shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="font-semibold text-[#1d2327] mb-2">Security Tips</h3>
                            <ul class="list-disc list-inside text-sm text-[#646970] space-y-1 ml-2">
                                <li>Use a strong, unique password for your account</li>
                                <li>Never share your credentials with others</li>
                                <li>Use the lock feature to prevent accidental credential changes</li>
                                <li>Your password is encrypted and cannot be recovered if lost</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button onclick="closeHelpModal()" class="px-6 py-2 wp-primary-button text-white rounded text-sm font-semibold transition-colors">
                    Got it!
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        let isPasskeyLocked = true;
        let isSidebarOpen = true;
        let isMobileSidebarOpen = false;

        // Initialize Icons
        function refreshIcons() {
            lucide.createIcons();
        }

        // Mobile Sidebar Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            isMobileSidebarOpen = !isMobileSidebarOpen;
            
            if (isMobileSidebarOpen) {
                sidebar.classList.remove('mobile-hidden');
                overlay.classList.add('active');
            } else {
                sidebar.classList.add('mobile-hidden');
                overlay.classList.remove('active');
            }
        }

        // Desktop Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const texts = document.querySelectorAll('.sidebar-text');
            isSidebarOpen = !isSidebarOpen;
            
            if (isSidebarOpen) {
                sidebar.classList.remove('w-12');
                sidebar.classList.add('w-64');
                setTimeout(() => {
                    texts.forEach(t => t.classList.remove('hidden'));
                }, 100);
            } else {
                texts.forEach(t => t.classList.add('hidden'));
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-12');
            }
        }

        // Tab Switching
        function switchTab(tab) {
            const textTab = document.getElementById('tab-text');
            const genTab = document.getElementById('tab-generate');
            const textView = document.getElementById('view-text');
            const genView = document.getElementById('view-generate');
            const title = document.getElementById('page-title');

            if (tab === 'text') {
                textTab.classList.add('active', 'text-white');
                textTab.classList.remove('text-gray-300');
                genTab.classList.remove('active', 'text-white');
                genTab.classList.add('text-gray-300');
                textView.classList.remove('hidden');
                genView.classList.add('hidden');
                title.innerText = "Recycle Text Dashboard";
            } else {
                genTab.classList.add('active', 'text-white');
                genTab.classList.remove('text-gray-300');
                textTab.classList.remove('active', 'text-white');
                textTab.classList.add('text-gray-300');
                genView.classList.remove('hidden');
                textView.classList.add('hidden');
                title.innerText = "Account Generator";
            }

            // Close mobile sidebar after tab switch
            if (window.innerWidth < 768 && isMobileSidebarOpen) {
                toggleMobileSidebar();
            }
        }

        // Passkey Toggle - Enhanced to lock/unlock both username and passkey
        function togglePasskey() {
            const usernameField = document.getElementById('input-username');
            const passkeyField = document.getElementById('input-passkey');
            const icon = document.getElementById('lock-icon');
            const text = document.getElementById('lock-text');
            const lockBtn = document.getElementById('lock-btn');
            
            isPasskeyLocked = !isPasskeyLocked;
            
            if (isPasskeyLocked) {
                // LOCKED: Hide both fields and disable them
                usernameField.type = 'password';
                passkeyField.type = 'password';
                usernameField.disabled = true;
                passkeyField.disabled = true;
                text.innerText = 'Unlock';
                lockBtn.classList.add('bg-green-100', 'border-green-300');
                lockBtn.classList.remove('bg-[#f6f7f7]', 'border-[#dcdcde]');
            } else {
                // UNLOCKED: Show username, hide passkey, enable both
                usernameField.type = 'text';
                passkeyField.type = 'password';
                usernameField.disabled = false;
                passkeyField.disabled = false;
                text.innerText = 'Lock';
                lockBtn.classList.remove('bg-green-100', 'border-green-300');
                lockBtn.classList.add('bg-[#f6f7f7]', 'border-[#dcdcde]');
            }
            
            icon.setAttribute('data-lucide', isPasskeyLocked ? 'lock' : 'unlock');
            refreshIcons();
        }

        // Form submission handler - temporarily enable disabled fields
        document.getElementById('paste-form').addEventListener('submit', function(e) {
            if (isPasskeyLocked) {
                const usernameField = document.getElementById('input-username');
                const passkeyField = document.getElementById('input-passkey');
                usernameField.disabled = false;
                passkeyField.disabled = false;
            }
        });

        // Copy to Clipboard
        function copyToClipboard(button) {
            const content = button.getAttribute('data-content');
            
            navigator.clipboard.writeText(content).then(() => {
                showToast('Copied to clipboard!');
                
                // Visual feedback
                const icon = button.querySelector('i');
                icon.setAttribute('data-lucide', 'check');
                button.classList.add('bg-[#00a32a]', 'text-white', 'border-[#00a32a]');
                refreshIcons();
                
                setTimeout(() => {
                    icon.setAttribute('data-lucide', 'copy');
                    button.classList.remove('bg-[#00a32a]', 'text-white', 'border-[#00a32a]');
                    refreshIcons();
                }, 1500);
            }).catch(err => {
                showToast('Failed to copy', 'error');
            });
        }

        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            if (type === 'error') {
                toast.style.backgroundColor = '#dc3545';
            } else {
                toast.style.backgroundColor = '#2271b1';
            }
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Search Table
        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const tbody = document.getElementById('pastes-table-body');
            const rows = tbody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                if (text.includes(input)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Registration Modal
        function openModal() {
            document.getElementById('registrationModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('registrationModal').classList.remove('active');
            document.getElementById('regUsername').value = '';
            document.getElementById('regPassword').value = '';
            document.getElementById('usernameError').classList.add('hidden');
            document.getElementById('regMessage').classList.add('hidden');
        }

        async function registerUser() {
            const username = document.getElementById('regUsername').value.trim();
            const password = document.getElementById('regPassword').value.trim();
            const errorEl = document.getElementById('usernameError');
            const messageEl = document.getElementById('regMessage');

            errorEl.classList.add('hidden');
            messageEl.classList.add('hidden');

            if (!username || !password) {
                errorEl.textContent = 'Both fields are required';
                errorEl.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                });

                const result = await response.json();

                if (result.success) {
                    messageEl.textContent = result.message;
                    messageEl.classList.remove('hidden');
                    messageEl.classList.add('text-green-600');
                    messageEl.classList.remove('text-red-600');
                    
                    // Auto-fill credentials
                    document.getElementById('input-username').value = username;
                    document.getElementById('input-passkey').value = password;
                    document.getElementById('display-username').textContent = username;
                    
                    showToast('Account created successfully!');
                    
                    setTimeout(() => {
                        closeModal();
                        switchTab('text');
                    }, 2000);
                } else {
                    errorEl.textContent = result.message;
                    errorEl.classList.remove('hidden');
                }
            } catch (error) {
                errorEl.textContent = 'Registration failed. Please try again.';
                errorEl.classList.remove('hidden');
            }
        }

        // Help Modal
        function openHelpModal() {
            document.getElementById('helpModal').classList.add('active');
            refreshIcons();
        }

        function closeHelpModal() {
            document.getElementById('helpModal').classList.remove('active');
        }

        // Handle modal backdrop click to close
        function handleModalBackdropClick(event, modalId) {
            if (event.target.id === modalId) {
                if (modalId === 'registrationModal') {
                    closeModal();
                } else if (modalId === 'helpModal') {
                    closeHelpModal();
                }
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeHelpModal();
            }
        });

        // Initial Load
        window.onload = () => {
            refreshIcons();
            
            // Update display username if logged in
            <?php if (!empty($_POST['username'])): ?>
            document.getElementById('display-username').textContent = '<?= htmlspecialchars($_POST['username']) ?>';
            <?php endif; ?>
        };
    </script>

</body>
</html>
