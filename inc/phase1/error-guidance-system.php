<?php
/**
 * Error Messages and Guidance System
 * 
 * Task 13: エラーメッセージ・ガイダンス強化
 * Implements unified error messaging, help features, and user tours
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 13.1 エラーメッセージ統一
 * Unified error message system
 */
class GI_Error_System {
    
    /**
     * Error codes and messages
     */
    private static $error_codes = array(
        // Authentication errors (1xxx)
        '1001' => array(
            'user' => 'ログインが必要です。',
            'admin' => 'Authentication required',
            'en' => 'Login required'
        ),
        '1002' => array(
            'user' => '権限がありません。',
            'admin' => 'Permission denied',
            'en' => 'No permission'
        ),
        '1003' => array(
            'user' => 'セッションが期限切れです。',
            'admin' => 'Session expired',
            'en' => 'Session expired'
        ),
        
        // Validation errors (2xxx)
        '2001' => array(
            'user' => '必須項目を入力してください。',
            'admin' => 'Required field missing',
            'en' => 'Required field'
        ),
        '2002' => array(
            'user' => '入力形式が正しくありません。',
            'admin' => 'Invalid format',
            'en' => 'Invalid format'
        ),
        '2003' => array(
            'user' => 'ファイルサイズが大きすぎます。',
            'admin' => 'File size exceeds limit',
            'en' => 'File too large'
        ),
        
        // Database errors (3xxx)
        '3001' => array(
            'user' => 'データの保存に失敗しました。',
            'admin' => 'Database save failed',
            'en' => 'Save failed'
        ),
        '3002' => array(
            'user' => 'データが見つかりません。',
            'admin' => 'Data not found',
            'en' => 'Not found'
        ),
        '3003' => array(
            'user' => 'データベース接続エラー。',
            'admin' => 'Database connection error',
            'en' => 'Connection error'
        ),
        
        // Search errors (4xxx)
        '4001' => array(
            'user' => '検索結果が見つかりませんでした。',
            'admin' => 'No search results',
            'en' => 'No results found'
        ),
        '4002' => array(
            'user' => '検索条件が無効です。',
            'admin' => 'Invalid search criteria',
            'en' => 'Invalid search'
        ),
        '4003' => array(
            'user' => '検索キーワードが短すぎます。',
            'admin' => 'Search term too short',
            'en' => 'Term too short'
        ),
        
        // System errors (5xxx)
        '5001' => array(
            'user' => 'システムエラーが発生しました。',
            'admin' => 'System error occurred',
            'en' => 'System error'
        ),
        '5002' => array(
            'user' => 'サービスが一時的に利用できません。',
            'admin' => 'Service temporarily unavailable',
            'en' => 'Service unavailable'
        ),
        '5003' => array(
            'user' => 'メンテナンス中です。',
            'admin' => 'Under maintenance',
            'en' => 'Under maintenance'
        )
    );
    
    /**
     * Get error message by code
     */
    public static function get_message($code, $type = 'user', $lang = 'ja') {
        if (!isset(self::$error_codes[$code])) {
            return $type === 'admin' ? 
                   "Unknown error (Code: $code)" : 
                   'エラーが発生しました。';
        }
        
        $messages = self::$error_codes[$code];
        
        if ($lang === 'en' && isset($messages['en'])) {
            return $messages['en'];
        }
        
        return isset($messages[$type]) ? $messages[$type] : $messages['user'];
    }
    
    /**
     * Display error message
     */
    public static function display($code, $additional_info = '', $type = 'user') {
        $message = self::get_message($code, $type);
        $is_admin = current_user_can('manage_options');
        
        ?>
        <div class="gi-error-message" data-error-code="<?php echo esc_attr($code); ?>">
            <div class="error-icon">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="error-content">
                <p class="error-main"><?php echo esc_html($message); ?></p>
                <?php if ($additional_info): ?>
                    <p class="error-additional"><?php echo esc_html($additional_info); ?></p>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                    <p class="error-code">エラーコード: <?php echo esc_html($code); ?></p>
                <?php endif; ?>
            </div>
            <button class="error-dismiss" aria-label="閉じる">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <?php
    }
    
    /**
     * Log error for admin
     */
    public static function log($code, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'GI Error [%s]: %s | Context: %s',
                $code,
                self::get_message($code, 'admin'),
                json_encode($context)
            ));
        }
        
        // Store in database for admin review
        global $wpdb;
        $table_name = $wpdb->prefix . 'gi_error_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'error_code' => $code,
                'error_message' => self::get_message($code, 'admin'),
                'context' => json_encode($context),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            )
        );
    }
}

/**
 * 13.2 ヘルプ機能実装
 * Help system implementation
 */
class GI_Help_System {
    
    /**
     * Initialize help system
     */
    public static function init() {
        add_action('wp_footer', array(__CLASS__, 'render_help_modal'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Render help modal
     */
    public static function render_help_modal() {
        ?>
        <div id="gi-help-modal" class="gi-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">ヘルプ</h3>
                    <button class="modal-close" aria-label="閉じる">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="help-search">
                        <input type="text" placeholder="質問を入力してください..." class="help-search-input">
                    </div>
                    <div class="help-categories">
                        <button class="help-category active" data-category="all">すべて</button>
                        <button class="help-category" data-category="search">検索について</button>
                        <button class="help-category" data-category="grant">補助金について</button>
                        <button class="help-category" data-category="account">アカウント</button>
                    </div>
                    <div class="help-content">
                        <!-- Dynamic content loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo esc_url(get_permalink(get_page_by_path('faq'))); ?>" 
                       class="btn-link">詳しいFAQを見る</a>
                </div>
            </div>
        </div>
        
        <button id="gi-help-trigger" class="help-trigger" aria-label="ヘルプ">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        
        <style>
            .gi-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                background: white;
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                display: flex;
                flex-direction: column;
            }
            
            .modal-header {
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .modal-title {
                font-size: 1.25rem;
                font-weight: 600;
                margin: 0;
            }
            
            .modal-close {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.5rem;
            }
            
            .modal-body {
                padding: 1.5rem;
                overflow-y: auto;
                flex: 1;
            }
            
            .help-search {
                margin-bottom: 1rem;
            }
            
            .help-search-input {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                font-size: 1rem;
            }
            
            .help-categories {
                display: flex;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                flex-wrap: wrap;
            }
            
            .help-category {
                padding: 0.5rem 1rem;
                background: #f3f4f6;
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .help-category.active {
                background: #6366f1;
                color: white;
            }
            
            .modal-footer {
                padding: 1.5rem;
                border-top: 1px solid #e5e7eb;
                text-align: center;
            }
            
            .help-trigger {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 56px;
                height: 56px;
                background: #6366f1;
                color: white;
                border: none;
                border-radius: 50%;
                box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.25);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s;
                z-index: 9998;
            }
            
            .help-trigger:hover {
                transform: scale(1.1);
                box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.35);
            }
            
            .gi-error-message {
                display: flex;
                align-items: start;
                gap: 1rem;
                padding: 1rem;
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 0.5rem;
                margin: 1rem 0;
                position: relative;
            }
            
            .error-icon {
                color: #ef4444;
                flex-shrink: 0;
            }
            
            .error-content {
                flex: 1;
            }
            
            .error-main {
                color: #991b1b;
                font-weight: 500;
                margin: 0 0 0.25rem 0;
            }
            
            .error-additional {
                color: #7f1d1d;
                font-size: 0.875rem;
                margin: 0;
            }
            
            .error-code {
                color: #991b1b;
                font-size: 0.75rem;
                margin-top: 0.5rem;
                font-family: monospace;
            }
            
            .error-dismiss {
                position: absolute;
                top: 0.5rem;
                right: 0.5rem;
                background: none;
                border: none;
                color: #991b1b;
                cursor: pointer;
                padding: 0.25rem;
            }
        </style>
        <?php
    }
    
    /**
     * Enqueue help system scripts
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'gi-help-system',
            get_template_directory_uri() . '/assets/js/help-system.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('gi-help-system', 'gi_help', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_help_nonce'),
            'faq_items' => self::get_faq_items()
        ));
    }
    
    /**
     * Get FAQ items
     */
    private static function get_faq_items() {
        return array(
            array(
                'category' => 'search',
                'question' => '補助金を検索するには？',
                'answer' => 'キーワード、カテゴリ、都道府県などで絞り込み検索ができます。検索バーに入力するか、フィルターを使用してください。'
            ),
            array(
                'category' => 'search',
                'question' => '検索結果が表示されません',
                'answer' => '検索条件を緩めてみてください。キーワードを短くする、フィルターを減らすなどお試しください。'
            ),
            array(
                'category' => 'grant',
                'question' => '申請方法を教えてください',
                'answer' => '各補助金の詳細ページに申請方法が記載されています。「申請する」ボタンから公式サイトへアクセスできます。'
            ),
            array(
                'category' => 'grant',
                'question' => '申請期限はいつですか？',
                'answer' => '補助金によって異なります。詳細ページで期限をご確認ください。期限が近い補助金は赤色で表示されます。'
            ),
            array(
                'category' => 'account',
                'question' => 'お気に入りの使い方は？',
                'answer' => 'ログイン後、補助金詳細ページのハートアイコンをクリックしてお気に入りに追加できます。'
            )
        );
    }
    
    /**
     * Add tooltip helper
     */
    public static function tooltip($text, $content) {
        return sprintf(
            '<span class="gi-tooltip" data-tooltip="%s">%s
                <svg class="tooltip-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </span>',
            esc_attr($content),
            esc_html($text)
        );
    }
}

// Initialize help system
add_action('init', array('GI_Help_System', 'init'));

/**
 * 13.3 初回ユーザー向けツアー
 * First-time user tour implementation
 */
class GI_User_Tour {
    
    /**
     * Initialize tour system
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_tour_scripts'));
        add_action('wp_footer', array(__CLASS__, 'render_tour_markup'));
    }
    
    /**
     * Enqueue tour scripts
     */
    public static function enqueue_tour_scripts() {
        // Check if user has completed tour
        $user_id = get_current_user_id();
        $tour_completed = $user_id ? get_user_meta($user_id, 'gi_tour_completed', true) : 
                                     isset($_COOKIE['gi_tour_completed']);
        
        if ($tour_completed) {
            return;
        }
        
        // Enqueue Shepherd.js for tour functionality
        wp_enqueue_script(
            'shepherd-js',
            'https://cdn.jsdelivr.net/npm/shepherd.js@10/dist/js/shepherd.min.js',
            array(),
            '10.0.0',
            true
        );
        
        wp_enqueue_style(
            'shepherd-css',
            'https://cdn.jsdelivr.net/npm/shepherd.js@10/dist/css/shepherd.css',
            array(),
            '10.0.0'
        );
        
        // Custom tour script
        wp_add_inline_script('shepherd-js', self::get_tour_script(), 'after');
    }
    
    /**
     * Get tour configuration script
     */
    private static function get_tour_script() {
        return "
        document.addEventListener('DOMContentLoaded', function() {
            // Check if tour should start
            if (localStorage.getItem('gi_tour_completed') === 'true') {
                return;
            }
            
            const tour = new Shepherd.Tour({
                useModalOverlay: true,
                defaultStepOptions: {
                    classes: 'gi-tour-step',
                    scrollTo: true,
                    cancelIcon: {
                        enabled: true
                    }
                }
            });
            
            // Step 1: Welcome
            tour.addStep({
                title: 'ようこそ！補助金検索サイトへ',
                text: 'このサイトでは、あなたに最適な補助金・助成金を簡単に見つけることができます。主要な機能をご紹介します。',
                buttons: [
                    {
                        text: 'スキップ',
                        action: tour.cancel,
                        secondary: true
                    },
                    {
                        text: '次へ',
                        action: tour.next
                    }
                ]
            });
            
            // Step 2: Search
            tour.addStep({
                title: '補助金の検索',
                text: 'キーワード、カテゴリ、地域などで補助金を検索できます。オートコンプリート機能で入力をサポートします。',
                attachTo: {
                    element: '.grant-search-form',
                    on: 'bottom'
                },
                buttons: [
                    {
                        text: '戻る',
                        action: tour.back
                    },
                    {
                        text: '次へ',
                        action: tour.next
                    }
                ]
            });
            
            // Step 3: AI Diagnosis
            if (document.querySelector('.ai-diagnosis-container')) {
                tour.addStep({
                    title: 'AI診断',
                    text: 'いくつかの質問に答えるだけで、あなたに最適な補助金をAIが提案します。',
                    attachTo: {
                        element: '.ai-diagnosis-container',
                        on: 'top'
                    },
                    buttons: [
                        {
                            text: '戻る',
                            action: tour.back
                        },
                        {
                            text: '次へ',
                            action: tour.next
                        }
                    ]
                });
            }
            
            // Step 4: Categories
            if (document.querySelector('.categories-grid')) {
                tour.addStep({
                    title: 'カテゴリ別検索',
                    text: 'カテゴリから補助金を探すこともできます。個人向け、法人向けなど、様々なカテゴリがあります。',
                    attachTo: {
                        element: '.categories-grid',
                        on: 'top'
                    },
                    buttons: [
                        {
                            text: '戻る',
                            action: tour.back
                        },
                        {
                            text: '次へ',
                            action: tour.next
                        }
                    ]
                });
            }
            
            // Step 5: Help
            tour.addStep({
                title: 'ヘルプ機能',
                text: '困ったときは、右下のヘルプボタンをクリックしてください。よくある質問や使い方ガイドを確認できます。',
                attachTo: {
                    element: '#gi-help-trigger',
                    on: 'left'
                },
                buttons: [
                    {
                        text: '戻る',
                        action: tour.back
                    },
                    {
                        text: '完了',
                        action: function() {
                            tour.complete();
                            // Mark tour as completed
                            localStorage.setItem('gi_tour_completed', 'true');
                            
                            // Save to server if logged in
                            if (typeof gi_ajax !== 'undefined') {
                                jQuery.post(gi_ajax.ajax_url, {
                                    action: 'gi_complete_tour',
                                    nonce: gi_ajax.nonce
                                });
                            }
                            
                            // Set cookie for non-logged users
                            document.cookie = 'gi_tour_completed=1; max-age=31536000; path=/';
                        }
                    }
                ]
            });
            
            // Show welcome modal first
            const showTourPrompt = function() {
                const prompt = document.createElement('div');
                prompt.className = 'tour-prompt';
                prompt.innerHTML = `
                    <div class='tour-prompt-overlay'></div>
                    <div class='tour-prompt-content'>
                        <h3>初めての方へ</h3>
                        <p>サイトの使い方を簡単にご案内します。</p>
                        <div class='tour-prompt-buttons'>
                            <button class='btn-secondary' onclick='this.closest(\".tour-prompt\").remove(); localStorage.setItem(\"gi_tour_completed\", \"true\");'>今はスキップ</button>
                            <button class='btn-primary' onclick='this.closest(\".tour-prompt\").remove(); tour.start();'>ツアーを開始</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(prompt);
            };
            
            // Show prompt after page load
            setTimeout(showTourPrompt, 1000);
        });
        ";
    }
    
    /**
     * Render tour markup and styles
     */
    public static function render_tour_markup() {
        ?>
        <style>
            /* Tour prompt styles */
            .tour-prompt {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
            }
            
            .tour-prompt-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .tour-prompt-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
            }
            
            .tour-prompt-content h3 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
                color: #111827;
            }
            
            .tour-prompt-content p {
                color: #6b7280;
                margin-bottom: 1.5rem;
            }
            
            .tour-prompt-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            
            .tour-prompt-buttons button {
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .btn-primary {
                background: #6366f1;
                color: white;
                border: none;
            }
            
            .btn-primary:hover {
                background: #4f46e5;
            }
            
            .btn-secondary {
                background: white;
                color: #6b7280;
                border: 1px solid #e5e7eb;
            }
            
            .btn-secondary:hover {
                background: #f9fafb;
            }
            
            /* Shepherd.js custom styles */
            .gi-tour-step {
                max-width: 400px;
            }
            
            .shepherd-content {
                padding: 1.5rem;
            }
            
            .shepherd-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 1rem 1.5rem;
            }
            
            .shepherd-title {
                color: white;
                font-size: 1.125rem;
                font-weight: 600;
            }
            
            .shepherd-cancel-icon {
                color: white;
            }
            
            .shepherd-text {
                font-size: 1rem;
                line-height: 1.5;
                color: #4b5563;
            }
            
            .shepherd-footer {
                padding: 1rem 1.5rem;
                border-top: 1px solid #e5e7eb;
            }
            
            .shepherd-button {
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                font-weight: 500;
                transition: all 0.2s;
            }
            
            .shepherd-button-primary {
                background: #6366f1;
                color: white;
                border: none;
            }
            
            .shepherd-button-primary:hover {
                background: #4f46e5;
            }
            
            .shepherd-button-secondary {
                background: white;
                color: #6b7280;
                border: 1px solid #e5e7eb;
            }
            
            .shepherd-button-secondary:hover {
                background: #f9fafb;
            }
            
            /* Tooltip styles */
            .gi-tooltip {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                cursor: help;
            }
            
            .tooltip-icon {
                color: #9ca3af;
                transition: color 0.2s;
            }
            
            .gi-tooltip:hover .tooltip-icon {
                color: #6366f1;
            }
            
            .gi-tooltip::after {
                content: attr(data-tooltip);
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                background: #1f2937;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s;
                margin-bottom: 0.5rem;
                z-index: 10;
            }
            
            .gi-tooltip:hover::after {
                opacity: 1;
            }
        </style>
        <?php
    }
}

// Initialize tour system
add_action('init', array('GI_User_Tour', 'init'));

/**
 * AJAX handler to mark tour as completed
 */
function gi_ajax_complete_tour() {
    check_ajax_referer('gi_ajax_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if ($user_id) {
        update_user_meta($user_id, 'gi_tour_completed', true);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_gi_complete_tour', 'gi_ajax_complete_tour');
add_action('wp_ajax_nopriv_gi_complete_tour', 'gi_ajax_complete_tour');

/**
 * Create error log table
 */
function gi_create_error_log_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'gi_error_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        error_code varchar(10) NOT NULL,
        error_message text NOT NULL,
        context longtext,
        user_id bigint(20) DEFAULT 0,
        ip_address varchar(45),
        user_agent text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY error_code (error_code),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'gi_create_error_log_table');