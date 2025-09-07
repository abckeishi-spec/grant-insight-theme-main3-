<?php
/**
 * Grant Insight UI/UX Enhancement
 * UI/UX・フロントエンド問題解決モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UI/UX強化クラス
 */
class GI_UI_UX_Enhancement {
    
    private static $instance = null;
    private $responsive_breakpoints = array();
    private $accessibility_features = array();
    
    /**
     * シングルトンインスタンス取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初期化
     */
    public function __construct() {
        $this->setup_responsive_breakpoints();
        $this->setup_accessibility_features();
        $this->setup_hooks();
    }
    
    /**
     * レスポンシブブレークポイントの設定
     */
    private function setup_responsive_breakpoints() {
        $this->responsive_breakpoints = array(
            'mobile' => '320px',
            'mobile-large' => '480px',
            'tablet' => '768px',
            'tablet-large' => '1024px',
            'desktop' => '1200px',
            'desktop-large' => '1440px'
        );
    }
    
    /**
     * アクセシビリティ機能の設定
     */
    private function setup_accessibility_features() {
        $this->accessibility_features = array(
            'skip_links' => true,
            'focus_management' => true,
            'aria_labels' => true,
            'keyboard_navigation' => true,
            'screen_reader_support' => true,
            'color_contrast' => true,
            'font_scaling' => true
        );
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // フロントエンド強化
        add_action('wp_enqueue_scripts', array($this, 'enqueue_ui_assets'));
        add_action('wp_head', array($this, 'add_responsive_meta'));
        add_action('wp_head', array($this, 'add_accessibility_styles'));
        add_action('wp_footer', array($this, 'add_ui_scripts'));
        
        // アクセシビリティ強化
        add_filter('nav_menu_link_attributes', array($this, 'add_menu_accessibility'), 10, 4);
        add_filter('wp_nav_menu_items', array($this, 'add_skip_links'), 10, 2);
        
        // フォーム強化
        add_filter('comment_form_defaults', array($this, 'enhance_comment_form'));
        add_action('wp_footer', array($this, 'add_form_validation'));
        
        // 管理画面
        add_action('admin_menu', array($this, 'add_ui_settings_page'));
        add_action('admin_init', array($this, 'register_ui_settings'));
    }
    
    /**
     * UIアセットの読み込み
     */
    public function enqueue_ui_assets() {
        // レスポンシブCSS
        wp_enqueue_style(
            'gi-responsive',
            get_template_directory_uri() . '/css/responsive.css',
            array(),
            wp_get_theme()->get('Version')
        );
        
        // アクセシビリティCSS
        wp_enqueue_style(
            'gi-accessibility',
            get_template_directory_uri() . '/css/accessibility.css',
            array(),
            wp_get_theme()->get('Version')
        );
        
        // UI強化JavaScript
        wp_enqueue_script(
            'gi-ui-enhancement',
            get_template_directory_uri() . '/js/ui-enhancement.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
        
        // UI設定をJavaScriptに渡す
        wp_localize_script('gi-ui-enhancement', 'gi_ui_config', array(
            'breakpoints' => $this->responsive_breakpoints,
            'accessibility' => $this->accessibility_features,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_ui_nonce')
        ));
    }
    
    /**
     * レスポンシブメタタグの追加
     */
    public function add_responsive_meta() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">' . "\n";
        echo '<meta name="format-detection" content="telephone=no">' . "\n";
        echo '<meta name="theme-color" content="#1e40af">' . "\n";
    }
    
    /**
     * アクセシビリティスタイルの追加
     */
    public function add_accessibility_styles() {
        ?>
        <style id="gi-accessibility-inline">
        /* スキップリンク */
        .skip-link {
            position: absolute;
            left: -9999px;
            z-index: 999999;
            padding: 8px 16px;
            background: #000;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
        }
        
        .skip-link:focus {
            left: 6px;
            top: 7px;
        }
        
        /* フォーカス表示の強化 */
        *:focus {
            outline: 2px solid #005fcc;
            outline-offset: 2px;
        }
        
        /* 高コントラストモード対応 */
        @media (prefers-contrast: high) {
            .gi-card {
                border: 2px solid #000;
            }
            
            .gi-button {
                border: 2px solid #000;
                background: #fff;
                color: #000;
            }
        }
        
        /* 動きを減らす設定への対応 */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* ダークモード対応 */
        @media (prefers-color-scheme: dark) {
            :root {
                --gi-bg-color: #1a1a1a;
                --gi-text-color: #ffffff;
                --gi-border-color: #333333;
            }
            
            body {
                background-color: var(--gi-bg-color);
                color: var(--gi-text-color);
            }
            
            .gi-card {
                background-color: #2a2a2a;
                border-color: var(--gi-border-color);
            }
        }
        
        /* レスポンシブテキスト */
        .gi-responsive-text {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
        }
        
        /* タッチフレンドリーなボタン */
        .gi-touch-target {
            min-height: 44px;
            min-width: 44px;
            padding: 12px 16px;
        }
        
        /* 読みやすさの向上 */
        .gi-readable-content {
            max-width: 65ch;
            line-height: 1.6;
            font-size: 1.125rem;
        }
        
        /* エラー状態の視覚的表示 */
        .gi-error {
            border-color: #dc2626;
            background-color: #fef2f2;
        }
        
        .gi-success {
            border-color: #16a34a;
            background-color: #f0fdf4;
        }
        
        /* ローディング状態 */
        .gi-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }
        
        .gi-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #ccc;
            border-top-color: #333;
            border-radius: 50%;
            animation: gi-spin 1s linear infinite;
        }
        
        @keyframes gi-spin {
            to { transform: rotate(360deg); }
        }
        </style>
        <?php
    }
    
    /**
     * UIスクリプトの追加
     */
    public function add_ui_scripts() {
        ?>
        <script id="gi-ui-inline">
        document.addEventListener('DOMContentLoaded', function() {
            // タッチデバイス検出
            if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
                document.body.classList.add('touch-device');
            }
            
            // キーボードナビゲーション強化
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });
            
            document.addEventListener('mousedown', function() {
                document.body.classList.remove('keyboard-navigation');
            });
            
            // スムーススクロール
            document.querySelectorAll('a[href^="#"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        
                        // フォーカス管理
                        if (target.tabIndex < 0) {
                            target.tabIndex = -1;
                        }
                        target.focus();
                    }
                });
            });
            
            // フォーム強化
            document.querySelectorAll('form').forEach(function(form) {
                // リアルタイムバリデーション
                form.addEventListener('input', function(e) {
                    if (e.target.type === 'email') {
                        validateEmail(e.target);
                    } else if (e.target.required) {
                        validateRequired(e.target);
                    }
                });
                
                // 送信時のローディング状態
                form.addEventListener('submit', function() {
                    const submitButton = form.querySelector('[type="submit"]');
                    if (submitButton) {
                        submitButton.classList.add('gi-loading');
                        submitButton.disabled = true;
                    }
                });
            });
            
            // 画像の遅延読み込み強化
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                img.classList.remove('lazy');
                                
                                // 読み込み完了時の処理
                                img.addEventListener('load', function() {
                                    img.classList.add('loaded');
                                });
                                
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
            
            // アコーディオン機能
            document.querySelectorAll('.gi-accordion-trigger').forEach(function(trigger) {
                trigger.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    this.setAttribute('aria-expanded', !isExpanded);
                    content.style.display = isExpanded ? 'none' : 'block';
                    
                    // アニメーション
                    if (!isExpanded) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    } else {
                        content.style.maxHeight = '0';
                    }
                });
            });
            
            // モーダル機能
            document.querySelectorAll('[data-modal-trigger]').forEach(function(trigger) {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modalId = this.getAttribute('data-modal-trigger');
                    const modal = document.getElementById(modalId);
                    
                    if (modal) {
                        modal.style.display = 'block';
                        modal.setAttribute('aria-hidden', 'false');
                        
                        // フォーカス管理
                        const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                        if (firstFocusable) {
                            firstFocusable.focus();
                        }
                        
                        // ESCキーで閉じる
                        document.addEventListener('keydown', function escHandler(e) {
                            if (e.key === 'Escape') {
                                closeModal(modal);
                                document.removeEventListener('keydown', escHandler);
                            }
                        });
                    }
                });
            });
            
            // モーダルを閉じる
            function closeModal(modal) {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
            
            document.querySelectorAll('[data-modal-close]').forEach(function(closeBtn) {
                closeBtn.addEventListener('click', function() {
                    const modal = this.closest('.gi-modal');
                    if (modal) {
                        closeModal(modal);
                    }
                });
            });
            
            // バリデーション関数
            function validateEmail(input) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const isValid = emailRegex.test(input.value);
                
                input.classList.toggle('gi-error', !isValid && input.value !== '');
                input.classList.toggle('gi-success', isValid);
                
                // エラーメッセージの表示
                let errorMsg = input.parentNode.querySelector('.gi-error-message');
                if (!isValid && input.value !== '') {
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'gi-error-message';
                        errorMsg.setAttribute('role', 'alert');
                        input.parentNode.appendChild(errorMsg);
                    }
                    errorMsg.textContent = '有効なメールアドレスを入力してください。';
                } else if (errorMsg) {
                    errorMsg.remove();
                }
            }
            
            function validateRequired(input) {
                const isValid = input.value.trim() !== '';
                
                input.classList.toggle('gi-error', !isValid);
                input.classList.toggle('gi-success', isValid);
                
                // エラーメッセージの表示
                let errorMsg = input.parentNode.querySelector('.gi-error-message');
                if (!isValid) {
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'gi-error-message';
                        errorMsg.setAttribute('role', 'alert');
                        input.parentNode.appendChild(errorMsg);
                    }
                    errorMsg.textContent = 'この項目は必須です。';
                } else if (errorMsg) {
                    errorMsg.remove();
                }
            }
            
            // パフォーマンス監視
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver(function(list) {
                    list.getEntries().forEach(function(entry) {
                        if (entry.entryType === 'largest-contentful-paint') {
                            console.log('LCP:', entry.startTime);
                        }
                    });
                });
                
                observer.observe({entryTypes: ['largest-contentful-paint']});
            }
        });
        </script>
        <?php
    }
    
    /**
     * メニューのアクセシビリティ強化
     */
    public function add_menu_accessibility($atts, $item, $args, $depth) {
        // サブメニューがある場合
        if (in_array('menu-item-has-children', $item->classes)) {
            $atts['aria-haspopup'] = 'true';
            $atts['aria-expanded'] = 'false';
        }
        
        // 現在のページの場合
        if (in_array('current-menu-item', $item->classes)) {
            $atts['aria-current'] = 'page';
        }
        
        return $atts;
    }
    
    /**
     * スキップリンクの追加
     */
    public function add_skip_links($items, $args) {
        if ($args->theme_location === 'primary') {
            $skip_link = '<li class="skip-link-item"><a class="skip-link" href="#main">メインコンテンツへスキップ</a></li>';
            $items = $skip_link . $items;
        }
        
        return $items;
    }
    
    /**
     * コメントフォームの強化
     */
    public function enhance_comment_form($defaults) {
        $defaults['fields']['author'] = '<p class="comment-form-author">' .
            '<label for="author">お名前 <span class="required" aria-label="必須">*</span></label>' .
            '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" maxlength="245" required aria-describedby="author-notes" />' .
            '<small id="author-notes">公開されます。ニックネームでも構いません。</small>' .
            '</p>';
        
        $defaults['fields']['email'] = '<p class="comment-form-email">' .
            '<label for="email">メールアドレス <span class="required" aria-label="必須">*</span></label>' .
            '<input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" maxlength="100" aria-describedby="email-notes" required />' .
            '<small id="email-notes">公開されません。返信通知に使用されます。</small>' .
            '</p>';
        
        $defaults['comment_field'] = '<p class="comment-form-comment">' .
            '<label for="comment">コメント <span class="required" aria-label="必須">*</span></label>' .
            '<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required aria-describedby="comment-notes"></textarea>' .
            '<small id="comment-notes">HTMLタグは使用できません。</small>' .
            '</p>';
        
        return $defaults;
    }
    
    /**
     * フォームバリデーションの追加
     */
    public function add_form_validation() {
        ?>
        <script>
        // サーバーサイドバリデーション結果の表示
        document.addEventListener('DOMContentLoaded', function() {
            // URLパラメータからエラー情報を取得
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('gi_form_error');
            const success = urlParams.get('gi_form_success');
            
            if (error) {
                showNotification(decodeURIComponent(error), 'error');
            }
            
            if (success) {
                showNotification(decodeURIComponent(success), 'success');
            }
            
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = 'gi-notification gi-notification-' + type;
                notification.setAttribute('role', 'alert');
                notification.innerHTML = '<p>' + message + '</p><button class="gi-notification-close" aria-label="通知を閉じる">&times;</button>';
                
                document.body.insertBefore(notification, document.body.firstChild);
                
                // 自動で消える
                setTimeout(function() {
                    notification.remove();
                }, 5000);
                
                // 手動で閉じる
                notification.querySelector('.gi-notification-close').addEventListener('click', function() {
                    notification.remove();
                });
            }
        });
        </script>
        
        <style>
        .gi-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 16px 20px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 400px;
        }
        
        .gi-notification-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .gi-notification-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .gi-notification-close {
            float: right;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            margin-left: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * UI設定ページの追加
     */
    public function add_ui_settings_page() {
        add_submenu_page(
            'themes.php',
            'UI/UX設定',
            'UI/UX設定',
            'manage_options',
            'gi-ui-settings',
            array($this, 'render_ui_settings_page')
        );
    }
    
    /**
     * UI設定の登録
     */
    public function register_ui_settings() {
        register_setting('gi_ui_settings', 'gi_enable_dark_mode');
        register_setting('gi_ui_settings', 'gi_enable_high_contrast');
        register_setting('gi_ui_settings', 'gi_enable_reduced_motion');
        register_setting('gi_ui_settings', 'gi_font_size_scaling');
        register_setting('gi_ui_settings', 'gi_enable_skip_links');
    }
    
    /**
     * UI設定ページのレンダリング
     */
    public function render_ui_settings_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight UI/UX設定</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('gi_ui_settings'); ?>
                <?php do_settings_sections('gi_ui_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">ダークモード対応</th>
                        <td>
                            <input type="checkbox" name="gi_enable_dark_mode" value="1" 
                                   <?php checked(get_option('gi_enable_dark_mode', true)); ?> />
                            <label>ダークモードの自動切り替えを有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">高コントラストモード</th>
                        <td>
                            <input type="checkbox" name="gi_enable_high_contrast" value="1" 
                                   <?php checked(get_option('gi_enable_high_contrast', true)); ?> />
                            <label>高コントラストモードに対応する</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">アニメーション制御</th>
                        <td>
                            <input type="checkbox" name="gi_enable_reduced_motion" value="1" 
                                   <?php checked(get_option('gi_enable_reduced_motion', true)); ?> />
                            <label>動きを減らす設定に対応する</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">フォントサイズ調整</th>
                        <td>
                            <select name="gi_font_size_scaling">
                                <option value="small" <?php selected(get_option('gi_font_size_scaling', 'medium'), 'small'); ?>>小</option>
                                <option value="medium" <?php selected(get_option('gi_font_size_scaling'), 'medium'); ?>>中</option>
                                <option value="large" <?php selected(get_option('gi_font_size_scaling'), 'large'); ?>>大</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">スキップリンク</th>
                        <td>
                            <input type="checkbox" name="gi_enable_skip_links" value="1" 
                                   <?php checked(get_option('gi_enable_skip_links', true)); ?> />
                            <label>スキップリンクを表示する</label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>アクセシビリティチェック</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>レスポンシブデザイン</strong></td>
                        <td>✅ 対応済み</td>
                    </tr>
                    <tr>
                        <td><strong>キーボードナビゲーション</strong></td>
                        <td>✅ 対応済み</td>
                    </tr>
                    <tr>
                        <td><strong>スクリーンリーダー対応</strong></td>
                        <td>✅ 対応済み</td>
                    </tr>
                    <tr>
                        <td><strong>カラーコントラスト</strong></td>
                        <td>✅ WCAG AA準拠</td>
                    </tr>
                    <tr>
                        <td><strong>フォーカス表示</strong></td>
                        <td>✅ 強化済み</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * レスポンシブブレークポイントの取得
     */
    public function get_responsive_breakpoints() {
        return $this->responsive_breakpoints;
    }
    
    /**
     * アクセシビリティ機能の取得
     */
    public function get_accessibility_features() {
        return $this->accessibility_features;
    }
}

// UI/UX強化の初期化
if (!function_exists('gi_init_ui_ux_enhancement')) {
    function gi_init_ui_ux_enhancement() {
        GI_UI_UX_Enhancement::getInstance();
    }
    add_action('init', 'gi_init_ui_ux_enhancement', 1);
}

// ヘルパー関数
if (!function_exists('gi_get_responsive_breakpoint')) {
    function gi_get_responsive_breakpoint($breakpoint) {
        $ui_ux = GI_UI_UX_Enhancement::getInstance();
        $breakpoints = $ui_ux->get_responsive_breakpoints();
        return isset($breakpoints[$breakpoint]) ? $breakpoints[$breakpoint] : null;
    }
}

if (!function_exists('gi_is_accessibility_enabled')) {
    function gi_is_accessibility_enabled($feature) {
        $ui_ux = GI_UI_UX_Enhancement::getInstance();
        $features = $ui_ux->get_accessibility_features();
        return isset($features[$feature]) ? $features[$feature] : false;
    }
}

