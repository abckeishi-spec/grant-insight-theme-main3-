<?php

namespace GrantInsight\Ajax;

use GrantInsight\Core\Logger;

/**
 * AI Chat Handler Class
 * 
 * Gemini APIとの連携を行うAIチャット機能
 */
class AiChat
{
    private static string $api_key;
    private static string $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    /**
     * 初期化
     */
    public static function init(): void
    {
        // APIキーを設定から取得
        self::$api_key = get_option('gemini_api_key', '');
        
        // AJAX処理を登録
        add_action('wp_ajax_gi_ai_chat', [self::class, 'handleChatRequest']);
        add_action('wp_ajax_nopriv_gi_ai_chat', [self::class, 'handleChatRequest']);
        
        add_action('wp_ajax_gi_get_chat_history', [self::class, 'getChatHistory']);
        add_action('wp_ajax_nopriv_gi_get_chat_history', [self::class, 'getChatHistory']);
        
        add_action('wp_ajax_gi_clear_chat_history', [self::class, 'clearChatHistory']);
        add_action('wp_ajax_nopriv_gi_clear_chat_history', [self::class, 'clearChatHistory']);

        // 管理画面にAPIキー設定を追加
        if (is_admin()) {
            add_action('admin_menu', [self::class, 'addAdminMenu']);
            add_action('admin_init', [self::class, 'registerSettings']);
        }
    }

    /**
     * チャットリクエストを処理
     */
    public static function handleChatRequest(): void
    {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_chat_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(['message' => 'メッセージが空です']);
        }

        // APIキーの確認
        if (empty(self::$api_key)) {
            wp_send_json_error(['message' => 'APIキーが設定されていません']);
        }

        try {
            // Gemini APIを呼び出し
            $response = self::callGeminiApi($message);
            
            // チャット履歴を保存
            self::saveChatMessage($message, $response);
            
            // パフォーマンスログ
            Logger::info('AI chat request processed', [
                'message_length' => strlen($message),
                'response_length' => strlen($response)
            ]);

            wp_send_json_success([
                'response' => $response,
                'timestamp' => current_time('c')
            ]);

        } catch (Exception $e) {
            Logger::error('AI chat error: ' . $e->getMessage(), [
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => 'AIからの応答を取得できませんでした。しばらく後でお試しください。'
            ]);
        }
    }

    /**
     * Gemini APIを呼び出し
     */
    private static function callGeminiApi(string $message): string
    {
        $url = self::$api_endpoint . '?key=' . self::$api_key;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => self::buildPrompt($message)
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (!isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid API response format');
        }

        return $decoded['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * プロンプトを構築
     */
    private static function buildPrompt(string $message): string
    {
        $system_prompt = "あなたは助成金・補助金の専門家です。以下の役割で回答してください：

1. 助成金・補助金に関する質問には詳しく、正確に回答する
2. 申請のコツや注意点を具体的にアドバイスする
3. 事業計画や資金調達についても相談に乗る
4. 丁寧で親しみやすい口調で回答する
5. 不明な点は「詳しくは公式サイトをご確認ください」と案内する

ユーザーからの質問: {$message}

上記の質問に対して、助成金の専門家として親切で実用的な回答をしてください。";

        return $system_prompt;
    }

    /**
     * チャット履歴を保存
     */
    private static function saveChatMessage(string $message, string $response): void
    {
        $session_id = self::getSessionId();
        $chat_history = get_transient("gi_chat_history_{$session_id}") ?: [];
        
        $chat_entry = [
            'timestamp' => current_time('c'),
            'user_message' => $message,
            'ai_response' => $response
        ];
        
        $chat_history[] = $chat_entry;
        
        // 最新50件のみ保持
        if (count($chat_history) > 50) {
            $chat_history = array_slice($chat_history, -50);
        }
        
        // 24時間保持
        set_transient("gi_chat_history_{$session_id}", $chat_history, 24 * HOUR_IN_SECONDS);
    }

    /**
     * チャット履歴を取得
     */
    public static function getChatHistory(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_chat_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $session_id = self::getSessionId();
        $chat_history = get_transient("gi_chat_history_{$session_id}") ?: [];
        
        wp_send_json_success(['history' => $chat_history]);
    }

    /**
     * チャット履歴をクリア
     */
    public static function clearChatHistory(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_chat_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $session_id = self::getSessionId();
        delete_transient("gi_chat_history_{$session_id}");
        
        wp_send_json_success(['message' => 'チャット履歴をクリアしました']);
    }

    /**
     * セッションIDを取得
     */
    private static function getSessionId(): string
    {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }

    /**
     * 管理画面メニューを追加
     */
    public static function addAdminMenu(): void
    {
        add_options_page(
            'AI Chat Settings',
            'AI Chat',
            'manage_options',
            'gi-ai-chat-settings',
            [self::class, 'renderSettingsPage']
        );
    }

    /**
     * 設定を登録
     */
    public static function registerSettings(): void
    {
        register_setting('gi_ai_chat_settings', 'gemini_api_key');
        
        add_settings_section(
            'gi_ai_chat_main',
            'Gemini API Settings',
            null,
            'gi-ai-chat-settings'
        );
        
        add_settings_field(
            'gemini_api_key',
            'Gemini API Key',
            [self::class, 'renderApiKeyField'],
            'gi-ai-chat-settings',
            'gi_ai_chat_main'
        );
    }

    /**
     * 設定ページを表示
     */
    public static function renderSettingsPage(): void
    {
        ?>
        <div class="wrap">
            <h1>AI Chat Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gi_ai_chat_settings');
                do_settings_sections('gi-ai-chat-settings');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="margin-top: 20px;">
                <h2>使用方法</h2>
                <ol>
                    <li>Google AI StudioでGemini APIキーを取得してください</li>
                    <li>上記のフィールドにAPIキーを入力して保存してください</li>
                    <li>AIチャットページでチャット機能が利用可能になります</li>
                </ol>
                <p><strong>注意:</strong> APIキーは安全に管理してください。第三者に漏洩しないよう注意が必要です。</p>
            </div>
        </div>
        <?php
    }

    /**
     * APIキーフィールドを表示
     */
    public static function renderApiKeyField(): void
    {
        $api_key = get_option('gemini_api_key', '');
        ?>
        <input type="password" 
               id="gemini_api_key" 
               name="gemini_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text" 
               placeholder="AIzaSy..." />
        <p class="description">
            Google AI Studioで取得したGemini APIキーを入力してください。
            <a href="https://makersuite.google.com/app/apikey" target="_blank">APIキーを取得</a>
        </p>
        <?php
    }
}

