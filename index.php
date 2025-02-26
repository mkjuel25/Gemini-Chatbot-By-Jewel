<?php
require 'config.php';
require 'auth.php';

// Redirect to guest.php if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: guest.php");
    exit;
}

$messages = [];
// Fetch messages for current user on server side
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT message, response, user_id, created_at FROM messages WHERE user_id = ? ORDER BY id ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        $messages = []; // Initialize messages to prevent errors
    }
} else {
    $messages = []; // Initialize messages to prevent errors if the user is not logged in
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owntweet Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/6.0.0/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">

    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
          navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
          }, function(err) {
            console.log('ServiceWorker registration failed: ', err);
          });
        });
      }
    </script>
      <style>
    @keyframes typing {
        0% { opacity: 0.4; }
        50% { opacity: 1; }
        100% { opacity: 0.4; }
    }
    .typing-dot { animation: typing 1.5s infinite; }

    /* Custom Animations */
    @keyframes slide-in-right {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slide-in-left {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* General message styles */
    .message-animation {
        animation-duration: 0.5s; /* Increased duration to 0.5s for better visibility on page load */
        animation-fill-mode: both;
    }

    /* Specific message animations */
    .user-message {
        animation-name: slide-in-right;
    }

    .bot-message {
        animation-name: slide-in-left;
        /* left: 0;  Remove if you tested before and it didn't work as expected */
    }

    .bot-message .max-w-[90%] { /* Targeting the container holding the message content */
        /* margin-left: -10px; Adjust this value if needed - Removed negative margin */
    }


    /* Typing Indicator Styles */
    .typing-indicator {
        display: flex;
        align-items: center;
    }
     .typing-indicator .typing-dot {
        width: 8px;
        height: 8px;
        background-color: #aaa;
        border-radius: 50%;
        margin: 0 2px;
        animation: typing 1.2s infinite;
        animation-delay: 0s;
    }
      .typing-indicator .typing-dot:nth-child(2) {
          animation-delay: 0.2s;
      }
     .typing-indicator .typing-dot:nth-child(3) {
          animation-delay: 0.4s;
      }

    /* Sidebar Styles */
    aside {
        height: 100vh; /* Full height */
        position: fixed; /* Fixed sidebar for desktop */
        top: 0;
        left: 0;
        z-index: 30; /* Higher z-index for sidebar */
        width: 250px; /* Adjust sidebar width as needed */
        border-right: 1px solid #4B5563; /* Border color from tailwind gray-700 */
        transform: translateX(-100%); /* Hide sidebar off-screen initially on mobile */
        transition: transform 0.3s ease-in-out; /* Smooth transition for mobile sidebar */
    }

    aside.open {
        transform: translateX(0); /* Slide in sidebar when open class is added */
    }

    /* Sidebar Backdrop for Mobile */
    #sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black backdrop */
        z-index: 25; /* Below sidebar, above main content */
        display: none; /* Hidden by default */
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    #sidebar-backdrop.open {
        display: block; /* Show backdrop when sidebar is open */
        opacity: 1;
    }


    /* Flex container for body to hold sidebar and content side by side */
    body.flex {
        display: flex;
    }

    .flex-1 { /* If not already defined */
        flex: 1;
    }

    /* Responsive adjustments for smaller screens */
    @media (min-width: 769px) { /* Desktop styles */
        aside {
            position: sticky; /* Make sidebar sticky on desktop */
            transform: translateX(0); /* Always show sidebar on desktop */
        }
        #sidebar-backdrop {
            display: none !important; /* Never show backdrop on desktop */
        }
        .input-area-fixed {
            margin-left: 250px; /* Shift input area to the right of the sidebar */
            left: 0;
            right: 0;
        }
        #chat-container {
            padding-left: ; /* Make space for fixed sidebar */
            align-items: flex-start; /* বাম দিকে সারিবদ্ধ করার জন্য এই লাইন যোগ করুন */
            width: 100%; /* Ensure full width in desktop view */
        }

        /* Force bot messages to the left edge on desktop */
        .bot-message {
            justify-content: flex-start; /* Ensure they are justified to the start */
        }
        .bot-message .max-w-[90%] {
            margin-left: 0; /* Reset any potential left margin */
        }
         .bot-message > div { /* Target the direct child div of .bot-message which is max-w-[90%] */
            margin-left: 0 !important; /* Forcefully reset margin if any */
            padding-left: 0 !important; /* Forcefully reset padding if any */
        }
    }

    @media (max-width: 768px) { /* Mobile styles */
        #chat-container {
            margin-left: 0; /* Reset margin for small screens */
            padding-top: 80px; /* Adjusted padding for fixed header */
            padding-bottom: 120px; /* Adjusted padding for fixed input area */
             align-items: flex-start; /* বাম দিকে সারিবদ্ধ করার জন্য এই লাইন যোগ করুন */
             width: 100%; /* Ensure full width in mobile view */
        }
         body.sidebar-open #chat-container {
            margin-left: 0; /* No margin on mobile when sidebar is open, it overlays */
        }

        /* Mobile specific bot message alignment */
        .bot-message .max-w-[90%] {
            margin-left: 0; /* Reset left margin */
            padding-left: 0; /* Reset left padding */
        }
    }

    /* Short Pre-loader Animation Styles */
    #preloader-animation {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #111827; /* bg-gray-900 from tailwind */
        z-index: 9999; /* Make sure it's on top */
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column; /* To center text below spinner */
        animation: fadeOutPreloader 0.5s forwards 0.5s; /* Fade out after 0.5s delay, total 1s */
        opacity: 1; /* Start as fully opaque */
    }

    .loader {
        border: 8px solid #f3f3f3; /* Light grey */
        border-top: 8px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 2s linear infinite;
        margin-bottom: 20px; /* Space between spinner and text */
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    #preloader-text {
        color: #fff; /* White text color */
        font-size: 1rem;
        font-weight: bold;
    }

    @keyframes fadeOutPreloader {
        to {
            opacity: 0;
            visibility: hidden; /* To fully remove from layout after animation */
        }
    }

    /* Fixed Header and Input Styles */
    .header-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 20;
    }

    .input-area-fixed {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 20;
        background-color: rgba(31, 41, 55, 0.5); /* bg-gray-800/50 fallback if backdrop-blur is not supported */
        backdrop-filter: blur(10px); /* backdrop-blur-sm equivalent */
    }

    #chat-container {
        padding-top: 80px; /* Adjust based on header height */
        padding-bottom: 120px; /* Adjust based on input area height */
         align-items: flex-start; /* বাম দিকে সারিবদ্ধ করার জন্য এই লাইন যোগ করুন */
         width: 100%; /* Ensure full width in base style */
    }


</style>

</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 h-screen flex">

    <!-- Pre-loader Animation Container (will be added by JS) -->
    <div id="preloader-animation">
        <div class="loader"></div>
        <div id="preloader-text">Loading...</div>
    </div>

     <!-- Sidebar Backdrop (for mobile) -->
    <div id="sidebar-backdrop"></div>

    <!-- Left Sidebar -->
    <aside class="bg-gray-800 border-r border-gray-700 flex-col">
        <div class="p-4 flex items-center justify-center border-b border-gray-700">
            <h1 class="text-xl font-semibold text-white">
                <i class='bx bxl-xing text-blue-500 align-middle'></i>
                Owntweet Chat
            </h1>
        </div>
        <nav class="flex-1 p-4">
            <ul class="space-y-2">
                <li>
                    <a href="#" class="block p-2 rounded hover:bg-gray-700 flex items-center text-gray-400 hover:text-gray-300">
                        <i class='bx bx-home align-middle mr-2'></i> Home
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="block p-2 rounded hover:bg-gray-700 flex items-center text-gray-400 hover:text-gray-300">
                        <i class='bx bx-user-circle align-middle mr-2'></i> Profile
                    </a>
                </li>
                 <li>
                    <button onclick="deleteChatHistory()" class="block p-2 rounded hover:bg-gray-700 flex items-center text-red-500 hover:text-red-300 w-full text-left">
                        <i class='bx bx-trash align-middle mr-2'></i> Delete Chat
                    </button>
                </li>
                <li>
                <a href="user_list.php" class="block p-2 rounded hover:bg-gray-700 flex items-center text-gray-400 hover:text-gray-300"> <!-- Active class for current page -->
                    <i class='bx bx-list-ul align-middle mr-2'></i> User List
                </a>
                </li>
                <li>
                    <a href="index.php?logout=1" class="block p-2 rounded hover:bg-gray-700 flex items-center text-gray-400 hover:text-gray-300">
                        <i class='bx bx-log-out align-middle mr-2'></i> Logout
                    </a>
                </li>

            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col h-screen flex-1">
        <!-- Header -->
        <div class="bg-gray-800 border-b border-gray-700 p-4 flex justify-between items-center header-fixed">
            <div class="flex items-center">
                <button id="sidebar-toggle" class="text-gray-400 hover:text-gray-300 mr-4 md:hidden">  <!-- Hidden on medium and up -->
                    <i class='bx bx-menu text-2xl'></i>
                </button>
                <h2 class="text-xl font-semibold text-white">Conversation</h2>
            </div>
            <div class="space-x-3 flex items-center">
                <a href="profile.php" title="Profile" class="text-gray-400 hover:text-gray-300 flex items-center">
                    <i class='bx bx-user-circle text-2xl mr-1'></i>
                    <span></span>
                </a>
                <button onclick="deleteChatHistory()" title="Delete All" class="text-red-500 hover:text-red-300">
                    <i class='bx bx-trash text-2xl'></i>
                </button>
                <a href="index.php?logout=1" title="Logout" class="text-gray-400 hover:text-gray-300">
                    <i class='bx bx-log-out text-2xl'></i>
                </a>
            </div>
        </div>

        <!-- Chat Container -->
        <div id="chat-container" class="flex-1 overflow-y-auto p-4 space-y-4 scroll-smooth">
            <!-- Messages will be dynamically added here -->
            <?php foreach($messages as $msg): ?>
                <div class="message-animation flex <?= ($msg['user_id'] == $_SESSION['user_id']) ? 'justify-end user-message' : 'justify-start bot-message' ?> mb-4">
                    <div class="max-w-[90%] md:max-w-[70%]">
                        <div class="<?= ($msg['user_id'] == $_SESSION['user_id']) ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-100' ?> px-4 py-3 rounded-2xl <?= ($msg['user_id'] == $_SESSION['user_id']) ? 'rounded-br-none' : 'rounded-bl-none' ?> message-container">
                             <?php if($msg['user_id'] == $_SESSION['user_id']): ?>
                                  <?= htmlspecialchars($msg['message']) ?>
                               <?php else: ?>
                                    <div class="message-content"><?= htmlspecialchars($msg['response']) ?></div>
                              <?php endif; ?>
                       </div>
                        <div class="text-xs text-gray-400 mt-1 <?= ($msg['user_id'] == $_SESSION['user_id']) ? 'text-right' : '' ?>">
                               <?=  date('h:i A', strtotime($msg['created_at'])) ?>
                               <?php if($msg['user_id'] != $_SESSION['user_id']): ?>
                                  <button onclick="copyMessage(this)" class="inline-block ml-2 text-gray-500 hover:text-gray-400">
                                     <i class='bx bx-copy'></i>
                                  </button>
                               <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Don't Remove this Duplicate response, it's showing need-->
            <div class="flex justify-start mb-4 message-animation bot-message">  <!-- Added message-animation and bot-message classes here -->
                <div class="max-w-[90%] md:max-w-[70%]">
                    <div class="bg-gray-700 text-gray-100 px-4 py-3 rounded-2xl rounded-bl-none">
                       <div class="message-content">
                       <?php if(isset($msg['response'])): ?>
                           <?= htmlspecialchars($msg['response']) ?>
                       <?php endif; ?>
                       </div>
                    </div>
                     <div class="text-xs text-gray-400 mt-1 ">
                          <?=  date('h:i A', strtotime($msg['created_at'])) ?>
                            <button onclick="copyMessage(this)" class="inline-block ml-2 text-gray-500 hover:text-gray-400">
                                  <i class='bx bx-copy'></i>
                           </button>
                     </div>
                </div>
            </div>

            <?php endforeach; ?>
              <div id="typing-indicator-container"></div> <!-- Container for typing indicator -->

        </div>

        <!-- Input Area -->
        <div class="input-area-fixed bg-gray-800/50 backdrop-blur-sm border-t border-gray-700 p-4">
            <form id="chat-form" class=" flex gap-3 items-center">
                <div class="flex-1 relative">
                    <textarea id="message-input"
                        class="w-full bg-gray-700/50 border border-gray-600 rounded-2xl py-3 px-5 pr-12
                                text-white placeholder-gray-400 focus:outline-none focus:border-blue-500
                                transition-colors resize-none overflow-hidden"
                        placeholder="Message something..."
                        autocomplete="off"
                        rows="2"
                        style="max-height: 150px;"
                        required></textarea>
                    <div class="absolute right-3 bottom-3 flex items-center gap-2">
                         <button type="submit" class="text-blue-400 hover:text-blue-300">
                            <i class='bx bx-send text-xl'></i>
                         </button>
                    </div>
                </div>
            </form>
            <!-- Footer -->
        <footer class="bg-gray-800 border-t border-gray-700 text-center text-gray-400 text-xs">
            Owntweet Chatbot can make mistakes.
        </footer>
        </div>
    </div>
<script src="js/conversations.js"></script>
</body>
</html>
