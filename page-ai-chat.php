<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIチャットボット - WordPress PHP テンプレート</title>
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#3B82F6',
                        'secondary': '#8B5CF6',
                        'accent': '#06B6D4',
                        'success': '#10B981',
                        'warning': '#F59E0B',
                        'error': '#EF4444'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'slide-down': 'slideDown 0.3s ease-out',
                        'bounce-gentle': 'bounceGentle 0.6s ease-in-out',
                        'pulse-slow': 'pulse 2s infinite',
                        'typing': 'typing 1.5s infinite',
                        'message-in': 'messageIn 0.4s ease-out',
                        'float': 'float 3s ease-in-out infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 20%, 50%, 80%, 100%': { transform: 'translateY(0)' },
                            '40%': { transform: 'translateY(-5px)' },
                            '60%': { transform: 'translateY(-3px)' }
                        },
                        typing: {
                            '0%, 60%': { opacity: '1' },
                            '30%': { opacity: '0.5' }
                        },
                        messageIn: {
                            '0%': { opacity: '0', transform: 'translateX(-20px) scale(0.95)' },
                            '100%': { opacity: '1', transform: 'translateX(0) scale(1)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <style>
        /* カスタムスクロールバー */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* タイピングアニメーション用ドット */
        .typing-dots::after {
            content: '...';
            animation: typing 1.5s infinite;
        }
        
        /* グラデーションアニメーション */
        .gradient-animation {
            background: linear-gradient(-45deg, #3B82F6, #8B5CF6, #06B6D4, #10B981);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* メッセージバブルの影効果 */
        .message-shadow {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* PDF出力最適化 */
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .chat-container { height: auto !important; max-height: none !important; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen">

<!-- PHP WordPress Template Header -->
<?php
/*
Template Name: AIチャットボット
Description: Gemini API統合AIチャットボットページ
Version: 2.0
Author: Grant Insight Team
*/

get_header();

// セキュリティ設定
wp_nonce_field('ai_chat_action', 'ai_chat_nonce');

// Gemini API設定
$gemini_api_key = get_option('gemini_api_key', '');
$api_configured = !empty($gemini_api_key);
?>

<!-- メインコンテナ -->
<div class="container mx-auto px-4 py-8 max-w-6xl">
    
    <!-- ページヘッダー -->
    <header class="text-center mb-8 animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-primary to-secondary rounded-full mb-4 animate-float">
            <i class="fas fa-robot text-2xl text-white"></i>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary via-secondary to-accent mb-4">
            AI チャットボット
        </h1>
        <p class="text-lg text-slate-600 max-w-2xl mx-auto">
            Gemini AIを活用した高性能チャットボット。ビジネスの課題解決から日常の質問まで、なんでもお気軽にご相談ください。
        </p>
    </header>

    <!-- チャットコンテナ -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
        
        <!-- チャットヘッダー -->
        <div class="gradient-animation p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-brain text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Gemini AI アシスタント</h2>
                        <p class="text-sm opacity-90">オンライン • 24時間対応</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="text-sm">アクティブ</span>
                </div>
            </div>
        </div>

        <!-- チャット履歴エリア -->
        <div id="chatHistory" class="h-96 overflow-y-auto p-6 space-y-4 bg-gradient-to-b from-slate-50 to-white custom-scrollbar">
            
            <!-- ウェルカムメッセージ -->
            <div class="flex items-start space-x-3 animate-message-in">
                <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-sm"></i>
                </div>
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-4 rounded-2xl rounded-tl-lg max-w-md message-shadow">
                    <p class="text-sm mb-2">👋 こんにちは！</p>
                    <p>私はGemini AIアシスタントです。ビジネスに関するご質問、技術的なサポート、創作活動のお手伝いなど、何でもお気軽にお聞かせください。</p>
                </div>
            </div>

            <!-- PHPで動的に生成されるチャット履歴 -->
            <!-- <?php
            $chat_history = get_chat_history();
            if ($chat_history) {
                foreach ($chat_history as $chat) {
                    if ($chat['type'] === 'user') {
                        echo '<div class="flex items-start justify-end space-x-3">';
                        echo '<div class="bg-gradient-to-r from-accent to-primary text-white p-4 rounded-2xl rounded-tr-lg max-w-md message-shadow">';
                        echo '<p>' . esc_html($chat['message']) . '</p>';
                        echo '</div>';
                        echo '<div class="w-10 h-10 bg-gradient-to-br from-accent to-primary rounded-full flex items-center justify-center flex-shrink-0">';
                        echo '<i class="fas fa-user text-white text-sm"></i>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="flex items-start space-x-3">';
                        echo '<div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center flex-shrink-0">';
                        echo '<i class="fas fa-robot text-white text-sm"></i>';
                        echo '</div>';
                        echo '<div class="bg-gradient-to-r from-slate-100 to-slate-200 text-slate-800 p-4 rounded-2xl rounded-tl-lg max-w-md message-shadow">';
                        echo '<p>' . wp_kses_post($chat['message']) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }
            ?> -->

        </div>

        <!-- タイピングインジケーター -->
        <div id="typingIndicator" class="px-6 pb-4 hidden">
            <div class="flex items-start space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white text-sm animate-pulse"></i>
                </div>
                <div class="bg-gradient-to-r from-slate-100 to-slate-200 text-slate-800 p-4 rounded-2xl rounded-tl-lg message-shadow">
                    <div class="flex items-center space-x-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                        </div>
                        <span class="text-sm text-slate-600">AIが入力中...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- メッセージ入力エリア -->
        <div class="p-6 bg-gradient-to-r from-slate-50 to-blue-50 border-t border-slate-200">
            <form id="chatForm" class="flex space-x-4">
                <!-- <?php wp_nonce_field('ai_chat_action', 'chat_nonce'); ?> -->
                
                <div class="flex-1 relative">
                    <textarea 
                        id="messageInput" 
                        name="user_message"
                        rows="1"
                        placeholder="メッセージを入力してください..." 
                        class="w-full p-4 pr-12 border border-slate-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent resize-none transition-all duration-300 bg-white/80 backdrop-blur-sm"
                        maxlength="1000"
                    ></textarea>
                    
                    <!-- 文字数カウンター -->
                    <div class="absolute bottom-2 right-12 text-xs text-slate-400">
                        <span id="charCount">0</span>/1000
                    </div>
                    
                    <!-- 音声入力ボタン -->
                    <button type="button" id="voiceBtn" class="absolute right-3 top-1/2 transform -translate-y-1/2 p-2 text-slate-400 hover:text-primary transition-colors duration-200">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                
                <button 
                    type="submit" 
                    id="sendBtn"
                    class="px-8 py-4 bg-gradient-to-r from-primary to-secondary text-white rounded-2xl hover:from-primary/90 hover:to-secondary/90 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all duration-300 transform hover:scale-105 flex items-center space-x-2 shadow-lg"
                >
                    <i class="fas fa-paper-plane"></i>
                    <span class="hidden sm:inline">送信</span>
                </button>
            </form>
            
            <!-- クイックアクションボタン -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="quick-action px-4 py-2 bg-white/80 backdrop-blur-sm text-slate-600 rounded-full border border-slate-200 hover:bg-primary hover:text-white transition-all duration-200 text-sm">
                    <i class="fas fa-lightbulb mr-2"></i>アイデア提案
                </button>
                <button type="button" class="quick-action px-4 py-2 bg-white/80 backdrop-blur-sm text-slate-600 rounded-full border border-slate-200 hover:bg-secondary hover:text-white transition-all duration-200 text-sm">
                    <i class="fas fa-chart-line mr-2"></i>ビジネス分析
                </button>
                <button type="button" class="quick-action px-4 py-2 bg-white/80 backdrop-blur-sm text-slate-600 rounded-full border border-slate-200 hover:bg-accent hover:text-white transition-all duration-200 text-sm">
                    <i class="fas fa-code mr-2"></i>技術サポート
                </button>
                <button type="button" class="quick-action px-4 py-2 bg-white/80 backdrop-blur-sm text-slate-600 rounded-full border border-slate-200 hover:bg-success hover:text-white transition-all duration-200 text-sm">
                    <i class="fas fa-question-circle mr-2"></i>FAQ
                </button>
            </div>
        </div>
    </div>

    <!-- 機能説明セクション -->
    <div class="mt-12 grid md:grid-cols-3 gap-8">
        
        <div class="text-center animate-fade-in" style="animation-delay: 0.1s;">
            <div class="w-16 h-16 bg-gradient-to-br from-primary to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 transform hover:scale-110 transition-transform duration-300">
                <i class="fas fa-brain text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-slate-800 mb-3">高度なAI理解力</h3>
            <p class="text-slate-600">Gemini AIの最新技術により、複雑な質問も正確に理解し、的確な回答を提供します。</p>
        </div>
        
        <div class="text-center animate-fade-in" style="animation-delay: 0.2s;">
            <div class="w-16 h-16 bg-gradient-to-br from-secondary to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 transform hover:scale-110 transition-transform duration-300">
                <i class="fas fa-clock text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-slate-800 mb-3">24時間対応</h3>
            <p class="text-slate-600">いつでもお気軽にご相談いただけます。迅速で正確な回答をお約束します。</p>
        </div>
        
        <div class="text-center animate-fade-in" style="animation-delay: 0.3s;">
            <div class="w-16 h-16 bg-gradient-to-br from-accent to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4 transform hover:scale-110 transition-transform duration-300">
                <i class="fas fa-shield-alt text-2xl text-white"></i>
            </div>
            <h3 class="text-xl font-semibold text-slate-800 mb-3">セキュア</h3>
            <p class="text-slate-600">すべての会話は暗号化され、プライバシーを最優先に保護されています。</p>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatForm = document.getElementById('chatForm');
    const chatHistory = document.getElementById('chatHistory');
    const typingIndicator = document.getElementById('typingIndicator');
    const charCount = document.getElementById('charCount');
    const quickActions = document.querySelectorAll('.quick-action');
    const voiceBtn = document.getElementById('voiceBtn');
    
    // 文字数カウンター
    messageInput.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        
        // 自動高さ調整
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Enterキーで送信（Shift+Enterで改行）
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // フォーム送信処理
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // ユーザーメッセージを追加
        addMessage(message, 'user');
        messageInput.value = '';
        charCount.textContent = '0';
        messageInput.style.height = 'auto';
        
        // 送信ボタンを無効化
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner animate-spin"></i><span class="hidden sm:inline ml-2">送信中...</span>';
        
        // タイピングインジケーターを表示
        showTypingIndicator();
        
        try {
            // WordPress AJAX処理
            const formData = new FormData();
            formData.append('action', 'gi_ai_chat');
            formData.append('message', message);
            formData.append('nonce', '<?php echo wp_create_nonce('ai_chat_nonce'); ?>');
            
            const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            // タイピングインジケーターを非表示
            hideTypingIndicator();
            
            if (data.success) {
                // AI応答を追加
                addMessage(data.data.response, 'ai');
            } else {
                addMessage(data.data.message || 'エラーが発生しました。', 'ai', true);
            }
        } catch (error) {
            hideTypingIndicator();
            addMessage('接続エラーが発生しました。インターネット接続をご確認の上、再度お試しください。', 'ai', true);
        } finally {
            // 送信ボタンを有効化
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span class="hidden sm:inline ml-2">送信</span>';
        }
    });
    
    // メッセージ追加関数
    function addMessage(text, type, isError = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex items-start space-x-3 animate-message-in ${type === 'user' ? 'justify-end' : ''}`;
        
        const iconBg = type === 'user' 
            ? 'bg-gradient-to-br from-accent to-primary' 
            : (isError ? 'bg-gradient-to-br from-error to-red-600' : 'bg-gradient-to-br from-primary to-secondary');
        
        const messageBg = type === 'user' 
            ? 'bg-gradient-to-r from-accent to-primary text-white' 
            : (isError ? 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' : 'bg-gradient-to-r from-slate-100 to-slate-200 text-slate-800');
        
        const roundedClass = type === 'user' ? 'rounded-tr-lg' : 'rounded-tl-lg';
        const icon = type === 'user' ? 'fa-user' : (isError ? 'fa-exclamation-triangle' : 'fa-robot');
        
        if (type === 'user') {
            messageDiv.innerHTML = `
                <div class="${messageBg} p-4 rounded-2xl ${roundedClass} max-w-md message-shadow">
                    <p>${escapeHtml(text)}</p>
                </div>
                <div class="w-10 h-10 ${iconBg} rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas ${icon} text-white text-sm"></i>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="w-10 h-10 ${iconBg} rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas ${icon} text-white text-sm"></i>
                </div>
                <div class="${messageBg} p-4 rounded-2xl ${roundedClass} max-w-md message-shadow">
                    <p>${text}</p>
                </div>
            `;
        }
        
        chatHistory.appendChild(messageDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
    
    // タイピングインジケーター制御
    function showTypingIndicator() {
        typingIndicator.classList.remove('hidden');
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
    
    function hideTypingIndicator() {
        typingIndicator.classList.add('hidden');
    }
    
    // クイックアクション
    quickActions.forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.textContent.trim();
            messageInput.value = text;
            messageInput.focus();
            charCount.textContent = text.length;
        });
    });
    
    // 音声入力（ブラウザ対応の場合）
    if ('webkitSpeechRecognition' in window) {
        const recognition = new webkitSpeechRecognition();
        recognition.lang = 'ja-JP';
        recognition.continuous = false;
        
        voiceBtn.addEventListener('click', function() {
            recognition.start();
            this.innerHTML = '<i class="fas fa-microphone-slash animate-pulse"></i>';
        });
        
        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            messageInput.value = transcript;
            charCount.textContent = transcript.length;
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        };
        
        recognition.onerror = function() {
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        };
    } else {
        voiceBtn.style.display = 'none';
    }
    
    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<!-- PHP WordPress Template Footer -->
<!-- <?php
// AJAX処理用フック（functions.phpに追加）
/*
add_action('wp_ajax_send_chat_message', 'handle_chat_message');
add_action('wp_ajax_nopriv_send_chat_message', 'handle_chat_message');

function handle_chat_message() {
    // ノンス検証
    if (!wp_verify_nonce($_POST['nonce'], 'ai_chat_action')) {
        wp_die('Security check failed');
    }
    
    $message = sanitize_chat_input($_POST['message']);
    
    if (empty($message)) {
        wp_send_json_error('メッセージが空です。');
        return;
    }
    
    // Gemini API呼び出し
    $ai_response = call_gemini_api($message);
    
    // チャット履歴保存
    save_chat_message($message, $ai_response);
    
    wp_send_json_success(array(
        'response' => $ai_response,
        'timestamp' => current_time('mysql')
    ));
}

// Gemini API関数の実装例
function call_gemini_api($message) {
    $api_key = get_option('gemini_api_key');
    $model = get_option('gemini_model', 'gemini-pro');
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    
    $data = array(
        'contents' => array(
            array(
                'parts' => array(
                    array('text' => $message)
                )
            )
        )
    );
    
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return 'APIエラーが発生しました。';
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return 'AI応答を取得できませんでした。';
}
*/

get_footer();
?> -->

</body>
</html>