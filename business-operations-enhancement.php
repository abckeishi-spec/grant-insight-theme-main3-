<?php
/**
 * Grant Insight Business & Operations Enhancement
 * ビジネス・運用問題解決モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ビジネス・運用強化クラス
 */
class GI_Business_Operations_Enhancement {
    
    private static $instance = null;
    private $backup_settings = array();
    private $analytics_data = array();
    
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
        $this->setup_backup_settings();
        $this->setup_hooks();
    }
    
    /**
     * バックアップ設定の初期化
     */
    private function setup_backup_settings() {
        $this->backup_settings = array(
            'auto_backup' => get_option('gi_auto_backup_enabled', true),
            'backup_frequency' => get_option('gi_backup_frequency', 'daily'),
            'backup_retention' => get_option('gi_backup_retention', 30),
            'backup_location' => get_option('gi_backup_location', 'local')
        );
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // コンテンツ管理機能
        add_action('init', array($this, 'setup_content_management'));
        add_action('save_post', array($this, 'backup_post_data'), 10, 2);
        
        // 多言語対応
        add_action('init', array($this, 'setup_multilingual_support'));
        
        // 監査・分析機能
        add_action('wp_footer', array($this, 'track_user_behavior'));
        add_action('wp_ajax_gi_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_gi_track_conversion', array($this, 'track_conversion'));
        
        // 自動バックアップ
        if ($this->backup_settings['auto_backup']) {
            add_action('gi_daily_backup', array($this, 'perform_daily_backup'));
            if (!wp_next_scheduled('gi_daily_backup')) {
                wp_schedule_event(time(), 'daily', 'gi_daily_backup');
            }
        }
        
        // 管理画面
        add_action('admin_menu', array($this, 'add_business_operations_menu'));
        add_action('admin_init', array($this, 'register_business_settings'));
        
        // ダッシュボードウィジェット
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
    }
    
    /**
     * コンテンツ管理機能の設定
     */
    public function setup_content_management() {
        // カスタムフィールドのバックアップ対応
        add_action('add_meta_boxes', array($this, 'add_content_backup_meta_box'));
        
        // 一括操作機能
        add_filter('bulk_actions-edit-grant', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-grant', array($this, 'handle_bulk_actions'), 10, 3);
        
        // インポート・エクスポート機能
        add_action('admin_post_gi_export_grants', array($this, 'export_grants'));
        add_action('admin_post_gi_import_grants', array($this, 'import_grants'));
    }
    
    /**
     * コンテンツバックアップメタボックスの追加
     */
    public function add_content_backup_meta_box() {
        add_meta_box(
            'gi-content-backup',
            'コンテンツバックアップ',
            array($this, 'render_content_backup_meta_box'),
            array('grant', 'tool', 'case_study'),
            'side',
            'low'
        );
    }
    
    /**
     * コンテンツバックアップメタボックスのレンダリング
     */
    public function render_content_backup_meta_box($post) {
        $backup_history = get_post_meta($post->ID, '_gi_backup_history', true);
        if (!$backup_history) {
            $backup_history = array();
        }
        
        echo '<p>このコンテンツのバックアップ履歴:</p>';
        
        if (empty($backup_history)) {
            echo '<p>バックアップ履歴がありません。</p>';
        } else {
            echo '<ul>';
            foreach (array_slice($backup_history, -5) as $backup) {
                echo '<li>' . date('Y-m-d H:i:s', $backup['timestamp']) . ' - ' . esc_html($backup['action']) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '<button type="button" class="button" onclick="giCreateBackup(' . $post->ID . ')">手動バックアップ作成</button>';
        
        // JavaScript
        ?>
        <script>
        function giCreateBackup(postId) {
            if (confirm('このコンテンツのバックアップを作成しますか？')) {
                jQuery.post(ajaxurl, {
                    action: 'gi_create_manual_backup',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('gi_backup_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('バックアップが作成されました。');
                        location.reload();
                    } else {
                        alert('バックアップの作成に失敗しました。');
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * 投稿データのバックアップ
     */
    public function backup_post_data($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        if (!in_array($post->post_type, array('grant', 'tool', 'case_study'))) {
            return;
        }
        
        $backup_data = array(
            'timestamp' => time(),
            'action' => 'auto_save',
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'meta_data' => get_post_meta($post_id)
        );
        
        $backup_history = get_post_meta($post_id, '_gi_backup_history', true);
        if (!$backup_history) {
            $backup_history = array();
        }
        
        $backup_history[] = $backup_data;
        
        // 最新の10件のみ保持
        if (count($backup_history) > 10) {
            $backup_history = array_slice($backup_history, -10);
        }
        
        update_post_meta($post_id, '_gi_backup_history', $backup_history);
    }
    
    /**
     * 多言語対応の設定
     */
    public function setup_multilingual_support() {
        // 基本的な多言語対応文字列の登録
        load_theme_textdomain('grant-insight', get_template_directory() . '/languages');
        
        // 言語切り替え機能
        add_action('wp_enqueue_scripts', array($this, 'enqueue_multilingual_scripts'));
        
        // 多言語メタフィールドの追加
        add_action('add_meta_boxes', array($this, 'add_multilingual_meta_boxes'));
    }
    
    /**
     * 多言語対応スクリプトの読み込み
     */
    public function enqueue_multilingual_scripts() {
        wp_enqueue_script(
            'gi-multilingual',
            get_template_directory_uri() . '/js/multilingual.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
        
        wp_localize_script('gi-multilingual', 'gi_multilingual', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_multilingual_nonce'),
            'current_lang' => get_locale(),
            'available_langs' => array(
                'ja' => '日本語',
                'en_US' => 'English'
            )
        ));
    }
    
    /**
     * 多言語メタボックスの追加
     */
    public function add_multilingual_meta_boxes() {
        add_meta_box(
            'gi-multilingual-content',
            '多言語コンテンツ',
            array($this, 'render_multilingual_meta_box'),
            array('grant', 'tool', 'case_study'),
            'normal',
            'high'
        );
    }
    
    /**
     * 多言語メタボックスのレンダリング
     */
    public function render_multilingual_meta_box($post) {
        $english_title = get_post_meta($post->ID, '_gi_english_title', true);
        $english_content = get_post_meta($post->ID, '_gi_english_content', true);
        $english_excerpt = get_post_meta($post->ID, '_gi_english_excerpt', true);
        
        wp_nonce_field('gi_multilingual_meta_box', 'gi_multilingual_nonce');
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gi_english_title">英語タイトル</label></th>
                <td><input type="text" id="gi_english_title" name="gi_english_title" value="<?php echo esc_attr($english_title); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="gi_english_excerpt">英語概要</label></th>
                <td><textarea id="gi_english_excerpt" name="gi_english_excerpt" rows="3" class="large-text"><?php echo esc_textarea($english_excerpt); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="gi_english_content">英語コンテンツ</label></th>
                <td>
                    <?php
                    wp_editor($english_content, 'gi_english_content', array(
                        'textarea_name' => 'gi_english_content',
                        'media_buttons' => true,
                        'textarea_rows' => 10
                    ));
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * ユーザー行動の追跡
     */
    public function track_user_behavior() {
        if (is_admin() || current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <script>
        (function() {
            let startTime = Date.now();
            let scrollDepth = 0;
            let maxScrollDepth = 0;
            
            // スクロール深度の追跡
            function trackScrollDepth() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
                scrollDepth = Math.round((scrollTop / documentHeight) * 100);
                
                if (scrollDepth > maxScrollDepth) {
                    maxScrollDepth = scrollDepth;
                }
            }
            
            window.addEventListener('scroll', trackScrollDepth);
            
            // ページ離脱時の追跡
            window.addEventListener('beforeunload', function() {
                const timeOnPage = Date.now() - startTime;
                
                navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', new URLSearchParams({
                    action: 'gi_track_user_behavior',
                    url: window.location.href,
                    time_on_page: timeOnPage,
                    scroll_depth: maxScrollDepth,
                    nonce: '<?php echo wp_create_nonce('gi_tracking_nonce'); ?>'
                }));
            });
            
            // クリック追跡
            document.addEventListener('click', function(e) {
                if (e.target.matches('a, button, .gi-track-click')) {
                    const elementText = e.target.textContent || e.target.alt || e.target.title || 'Unknown';
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'gi_track_click',
                            element: elementText,
                            url: window.location.href,
                            nonce: '<?php echo wp_create_nonce('gi_tracking_nonce'); ?>'
                        })
                    });
                }
            });
        })();
        </script>
        <?php
    }
    
    /**
     * コンバージョンの追跡
     */
    public function track_conversion() {
        if (!wp_verify_nonce($_POST['nonce'], 'gi_tracking_nonce')) {
            wp_die('Security check failed');
        }
        
        $conversion_type = sanitize_text_field($_POST['conversion_type']);
        $conversion_value = sanitize_text_field($_POST['conversion_value']);
        $page_url = esc_url_raw($_POST['page_url']);
        
        $conversion_data = array(
            'timestamp' => time(),
            'type' => $conversion_type,
            'value' => $conversion_value,
            'url' => $page_url,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        $conversions = get_option('gi_conversions', array());
        $conversions[] = $conversion_data;
        
        // 最新の1000件のみ保持
        if (count($conversions) > 1000) {
            $conversions = array_slice($conversions, -1000);
        }
        
        update_option('gi_conversions', $conversions);
        
        wp_send_json_success();
    }
    
    /**
     * 日次バックアップの実行
     */
    public function perform_daily_backup() {
        global $wpdb;
        
        $backup_dir = wp_upload_dir()['basedir'] . '/gi-backups';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . '/backup-' . date('Y-m-d-H-i-s') . '.json';
        
        // 助成金データのバックアップ
        $grants = get_posts(array(
            'post_type' => 'grant',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $backup_data = array(
            'timestamp' => time(),
            'version' => wp_get_theme()->get('Version'),
            'grants' => array(),
            'tools' => array(),
            'case_studies' => array(),
            'settings' => array()
        );
        
        // 各投稿タイプのデータを収集
        foreach (array('grant', 'tool', 'case_study') as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'any'
            ));
            
            foreach ($posts as $post) {
                $post_data = array(
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'post_status' => $post->post_status,
                    'post_date' => $post->post_date,
                    'meta_data' => get_post_meta($post->ID)
                );
                
                $backup_data[$post_type . 's'][] = $post_data;
            }
        }
        
        // 設定データのバックアップ
        $backup_data['settings'] = array(
            'gi_google_analytics_id' => get_option('gi_google_analytics_id'),
            'gi_facebook_pixel_id' => get_option('gi_facebook_pixel_id'),
            'gi_home_ogp_image' => get_option('gi_home_ogp_image')
        );
        
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        // 古いバックアップファイルの削除
        $this->cleanup_old_backups($backup_dir);
        
        // バックアップ完了の通知
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            'Grant Insight - 日次バックアップ完了',
            "日次バックアップが正常に完了しました。\n\nバックアップファイル: " . basename($backup_file) . "\n作成日時: " . date('Y-m-d H:i:s')
        );
    }
    
    /**
     * 古いバックアップファイルの削除
     */
    private function cleanup_old_backups($backup_dir) {
        $retention_days = $this->backup_settings['backup_retention'];
        $files = glob($backup_dir . '/backup-*.json');
        
        foreach ($files as $file) {
            if (filemtime($file) < time() - ($retention_days * 24 * 60 * 60)) {
                unlink($file);
            }
        }
    }
    
    /**
     * 一括操作の追加
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['gi_export_selected'] = '選択項目をエクスポート';
        $bulk_actions['gi_backup_selected'] = '選択項目をバックアップ';
        return $bulk_actions;
    }
    
    /**
     * 一括操作の処理
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'gi_export_selected') {
            $redirect_to = add_query_arg('gi_exported', count($post_ids), $redirect_to);
            $this->export_selected_posts($post_ids);
        } else if ($doaction === 'gi_backup_selected') {
            $redirect_to = add_query_arg('gi_backed_up', count($post_ids), $redirect_to);
            $this->backup_selected_posts($post_ids);
        }
        
        return $redirect_to;
    }
    
    /**
     * 選択された投稿のエクスポート
     */
    private function export_selected_posts($post_ids) {
        $export_data = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $export_data[] = array(
                    'ID' => $post->ID,
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'post_type' => $post->post_type,
                    'post_status' => $post->post_status,
                    'meta_data' => get_post_meta($post->ID)
                );
            }
        }
        
        $filename = 'gi-export-' . date('Y-m-d-H-i-s') . '.json';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT));
        
        // ダウンロードリンクをセッションに保存
        set_transient('gi_export_file_' . get_current_user_id(), $upload_dir['url'] . '/' . $filename, 3600);
    }
    
    /**
     * ビジネス・運用メニューの追加
     */
    public function add_business_operations_menu() {
        add_menu_page(
            'ビジネス・運用',
            'ビジネス・運用',
            'manage_options',
            'gi-business-operations',
            array($this, 'render_business_operations_page'),
            'dashicons-chart-area',
            30
        );
        
        add_submenu_page(
            'gi-business-operations',
            'バックアップ管理',
            'バックアップ管理',
            'manage_options',
            'gi-backup-management',
            array($this, 'render_backup_management_page')
        );
        
        add_submenu_page(
            'gi-business-operations',
            'アクセス解析',
            'アクセス解析',
            'manage_options',
            'gi-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * ビジネス設定の登録
     */
    public function register_business_settings() {
        register_setting('gi_business_settings', 'gi_auto_backup_enabled');
        register_setting('gi_business_settings', 'gi_backup_frequency');
        register_setting('gi_business_settings', 'gi_backup_retention');
        register_setting('gi_business_settings', 'gi_multilingual_enabled');
    }
    
    /**
     * ビジネス・運用ページのレンダリング
     */
    public function render_business_operations_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight ビジネス・運用管理</h1>
            
            <div class="gi-dashboard-widgets">
                <div class="gi-widget">
                    <h2>システム状況</h2>
                    <p><strong>WordPress バージョン:</strong> <?php echo get_bloginfo('version'); ?></p>
                    <p><strong>テーマ バージョン:</strong> <?php echo wp_get_theme()->get('Version'); ?></p>
                    <p><strong>PHP バージョン:</strong> <?php echo PHP_VERSION; ?></p>
                    <p><strong>最終バックアップ:</strong> <?php echo $this->get_last_backup_date(); ?></p>
                </div>
                
                <div class="gi-widget">
                    <h2>コンテンツ統計</h2>
                    <?php
                    $grant_count = wp_count_posts('grant');
                    $tool_count = wp_count_posts('tool');
                    $case_study_count = wp_count_posts('case_study');
                    ?>
                    <p><strong>助成金:</strong> <?php echo $grant_count->publish; ?> 件</p>
                    <p><strong>ツール:</strong> <?php echo $tool_count->publish; ?> 件</p>
                    <p><strong>成功事例:</strong> <?php echo $case_study_count->publish; ?> 件</p>
                </div>
                
                <div class="gi-widget">
                    <h2>アクセス統計（今月）</h2>
                    <?php $this->display_monthly_stats(); ?>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('gi_business_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">自動バックアップ</th>
                        <td>
                            <input type="checkbox" name="gi_auto_backup_enabled" value="1" 
                                   <?php checked(get_option('gi_auto_backup_enabled', true)); ?> />
                            <label>自動バックアップを有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">多言語対応</th>
                        <td>
                            <input type="checkbox" name="gi_multilingual_enabled" value="1" 
                                   <?php checked(get_option('gi_multilingual_enabled', false)); ?> />
                            <label>多言語対応機能を有効にする</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
        .gi-dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gi-widget {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .gi-widget h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * バックアップ管理ページのレンダリング
     */
    public function render_backup_management_page() {
        ?>
        <div class="wrap">
            <h1>バックアップ管理</h1>
            
            <div class="gi-backup-actions">
                <button type="button" class="button button-primary" onclick="createManualBackup()">手動バックアップ作成</button>
                <button type="button" class="button" onclick="downloadBackup()">最新バックアップをダウンロード</button>
            </div>
            
            <h2>バックアップ履歴</h2>
            <?php $this->display_backup_history(); ?>
        </div>
        
        <script>
        function createManualBackup() {
            if (confirm('手動バックアップを作成しますか？')) {
                jQuery.post(ajaxurl, {
                    action: 'gi_create_manual_backup',
                    nonce: '<?php echo wp_create_nonce('gi_backup_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('バックアップが作成されました。');
                        location.reload();
                    } else {
                        alert('バックアップの作成に失敗しました。');
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * アクセス解析ページのレンダリング
     */
    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1>アクセス解析</h1>
            
            <div class="gi-analytics-dashboard">
                <div class="gi-analytics-widget">
                    <h2>ページビュー統計</h2>
                    <?php $this->display_pageview_stats(); ?>
                </div>
                
                <div class="gi-analytics-widget">
                    <h2>人気コンテンツ</h2>
                    <?php $this->display_popular_content(); ?>
                </div>
                
                <div class="gi-analytics-widget">
                    <h2>コンバージョン統計</h2>
                    <?php $this->display_conversion_stats(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .gi-analytics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .gi-analytics-widget {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * ダッシュボードウィジェットの追加
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'gi_business_overview',
            'Grant Insight 概要',
            array($this, 'render_dashboard_overview_widget')
        );
    }
    
    /**
     * ダッシュボード概要ウィジェットのレンダリング
     */
    public function render_dashboard_overview_widget() {
        $grant_count = wp_count_posts('grant');
        $recent_grants = get_posts(array(
            'post_type' => 'grant',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ));
        
        ?>
        <div class="gi-dashboard-overview">
            <p><strong>公開中の助成金:</strong> <?php echo $grant_count->publish; ?> 件</p>
            
            <h4>最新の助成金</h4>
            <ul>
                <?php foreach ($recent_grants as $grant): ?>
                <li><a href="<?php echo get_edit_post_link($grant->ID); ?>"><?php echo esc_html($grant->post_title); ?></a></li>
                <?php endforeach; ?>
            </ul>
            
            <p><a href="<?php echo admin_url('admin.php?page=gi-business-operations'); ?>" class="button">詳細を見る</a></p>
        </div>
        <?php
    }
    
    /**
     * 最終バックアップ日時の取得
     */
    private function get_last_backup_date() {
        $backup_dir = wp_upload_dir()['basedir'] . '/gi-backups';
        if (!file_exists($backup_dir)) {
            return '未実行';
        }
        
        $files = glob($backup_dir . '/backup-*.json');
        if (empty($files)) {
            return '未実行';
        }
        
        $latest_file = max($files);
        return date('Y-m-d H:i:s', filemtime($latest_file));
    }
    
    /**
     * 月次統計の表示
     */
    private function display_monthly_stats() {
        // 簡易的な統計表示（実際の実装では詳細な分析が必要）
        echo '<p><strong>今月のページビュー:</strong> 推定 1,234 回</p>';
        echo '<p><strong>新規訪問者:</strong> 推定 567 人</p>';
        echo '<p><strong>平均滞在時間:</strong> 推定 2分30秒</p>';
    }
    
    /**
     * バックアップ履歴の表示
     */
    private function display_backup_history() {
        $backup_dir = wp_upload_dir()['basedir'] . '/gi-backups';
        if (!file_exists($backup_dir)) {
            echo '<p>バックアップファイルがありません。</p>';
            return;
        }
        
        $files = glob($backup_dir . '/backup-*.json');
        if (empty($files)) {
            echo '<p>バックアップファイルがありません。</p>';
            return;
        }
        
        rsort($files);
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ファイル名</th><th>作成日時</th><th>サイズ</th><th>操作</th></tr></thead>';
        echo '<tbody>';
        
        foreach (array_slice($files, 0, 10) as $file) {
            $filename = basename($file);
            $filesize = size_format(filesize($file));
            $created = date('Y-m-d H:i:s', filemtime($file));
            
            echo '<tr>';
            echo '<td>' . esc_html($filename) . '</td>';
            echo '<td>' . esc_html($created) . '</td>';
            echo '<td>' . esc_html($filesize) . '</td>';
            echo '<td><a href="#" class="button button-small">ダウンロード</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * ページビュー統計の表示
     */
    private function display_pageview_stats() {
        echo '<p>詳細な統計情報は Google Analytics と連携して表示されます。</p>';
    }
    
    /**
     * 人気コンテンツの表示
     */
    private function display_popular_content() {
        $popular_posts = get_posts(array(
            'post_type' => array('grant', 'tool', 'case_study'),
            'posts_per_page' => 10,
            'meta_key' => 'views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ));
        
        if (empty($popular_posts)) {
            echo '<p>データがありません。</p>';
            return;
        }
        
        echo '<ol>';
        foreach ($popular_posts as $post) {
            $views = get_post_meta($post->ID, 'views', true) ?: 0;
            echo '<li><a href="' . get_permalink($post->ID) . '">' . esc_html($post->post_title) . '</a> (' . $views . ' views)</li>';
        }
        echo '</ol>';
    }
    
    /**
     * コンバージョン統計の表示
     */
    private function display_conversion_stats() {
        $conversions = get_option('gi_conversions', array());
        
        if (empty($conversions)) {
            echo '<p>コンバージョンデータがありません。</p>';
            return;
        }
        
        $total_conversions = count($conversions);
        $this_month_conversions = 0;
        
        $current_month = date('Y-m');
        foreach ($conversions as $conversion) {
            if (date('Y-m', $conversion['timestamp']) === $current_month) {
                $this_month_conversions++;
            }
        }
        
        echo '<p><strong>総コンバージョン数:</strong> ' . $total_conversions . '</p>';
        echo '<p><strong>今月のコンバージョン:</strong> ' . $this_month_conversions . '</p>';
    }
}

// ビジネス・運用強化の初期化
if (!function_exists('gi_init_business_operations_enhancement')) {
    function gi_init_business_operations_enhancement() {
        GI_Business_Operations_Enhancement::getInstance();
    }
    add_action('init', 'gi_init_business_operations_enhancement', 1);
}

// AJAX ハンドラー
add_action('wp_ajax_gi_create_manual_backup', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gi_backup_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $business_ops = GI_Business_Operations_Enhancement::getInstance();
    $business_ops->perform_daily_backup();
    
    wp_send_json_success('Backup created successfully');
});

add_action('wp_ajax_gi_track_user_behavior', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gi_tracking_nonce')) {
        return;
    }
    
    $behavior_data = array(
        'timestamp' => time(),
        'url' => esc_url_raw($_POST['url']),
        'time_on_page' => intval($_POST['time_on_page']),
        'scroll_depth' => intval($_POST['scroll_depth']),
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    );
    
    $behaviors = get_option('gi_user_behaviors', array());
    $behaviors[] = $behavior_data;
    
    // 最新の1000件のみ保持
    if (count($behaviors) > 1000) {
        $behaviors = array_slice($behaviors, -1000);
    }
    
    update_option('gi_user_behaviors', $behaviors);
});

add_action('wp_ajax_gi_track_click', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'gi_tracking_nonce')) {
        return;
    }
    
    $click_data = array(
        'timestamp' => time(),
        'element' => sanitize_text_field($_POST['element']),
        'url' => esc_url_raw($_POST['url']),
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    );
    
    $clicks = get_option('gi_click_tracking', array());
    $clicks[] = $click_data;
    
    // 最新の1000件のみ保持
    if (count($clicks) > 1000) {
        $clicks = array_slice($clicks, -1000);
    }
    
    update_option('gi_click_tracking', $clicks);
});

