<?php
/**
 * Grant Insight Integration Master
 * 統合マスターファイル - 全ての改良機能を統合
 * 
 * @package Grant_Insight_Perfect
 * @version 6.2.1-master
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 統合フラグ
if (!defined('GI_INTEGRATION_MASTER_LOADED')) {
    define('GI_INTEGRATION_MASTER_LOADED', true);
}

/**
 * 統合マスタークラス
 */
class GI_Integration_Master {
    
    private static $instance = null;
    private $loaded_modules = array();
    
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
        $this->load_core_modules();
        $this->setup_hooks();
        $this->initialize_components();
    }
    
    /**
     * コアモジュールの読み込み
     */
    private function load_core_modules() {
        $modules = array(
            'functions-fixed.php' => 'Core Functions',
            'performance-enhanced.php' => 'Performance Enhancement',
            'security-enhanced.php' => 'Security Enhancement'
        );
        
        foreach ($modules as $file => $description) {
            $file_path = get_template_directory() . '/' . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                $this->loaded_modules[$file] = $description;
                
                if (WP_DEBUG) {
                    error_log("GI Integration: Loaded module - {$description} ({$file})");
                }
            } else {
                error_log("GI Integration: Missing module - {$description} ({$file})");
            }
        }
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // 初期化フック
        add_action('init', array($this, 'on_init'), 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_integrated_scripts'), 1);
        add_action('wp_head', array($this, 'output_integration_info'), 1);
        
        // AJAX統合
        add_action('wp_ajax_gi_integrated_search', array($this, 'handle_integrated_search'));
        add_action('wp_ajax_nopriv_gi_integrated_search', array($this, 'handle_integrated_search'));
        
        // 管理画面
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }
        
        // クリーンアップ
        register_deactivation_hook(__FILE__, array($this, 'on_deactivation'));
    }
    
    /**
     * コンポーネントの初期化
     */
    private function initialize_components() {
        // パフォーマンス監視開始
        if (class_exists('GI_Performance_Monitor')) {
            GI_Performance_Monitor::start_monitoring();
        }
        
        // セキュリティ強化初期化
        if (class_exists('GI_Security_Enhancement')) {
            GI_Security_Enhancement::init();
        }
        
        // キャッシュシステム初期化
        if (class_exists('GI_Cache_System')) {
            $cache = GI_Cache_System::getInstance();
        }
    }
    
    /**
     * 初期化時の処理
     */
    public function on_init() {
        // リライトルールの確認
        $this->check_rewrite_rules();
        
        // デフォルトデータの確認
        $this->ensure_default_data();
        
        // パフォーマンス最適化の適用
        $this->apply_performance_optimizations();
    }
    
    /**
     * 統合スクリプトの読み込み
     */
    public function enqueue_integrated_scripts() {
        // 統合CSS
        wp_enqueue_style(
            'gi-integrated-style',
            get_template_directory_uri() . '/css/integrated.css',
            array(),
            GI_THEME_VERSION
        );
        
        // 統合JavaScript
        wp_enqueue_script(
            'gi-integrated-script',
            get_template_directory_uri() . '/js/search-enhanced.js',
            array('jquery'),
            GI_THEME_VERSION,
            true
        );
        
        // AJAX設定（強化版）
        wp_localize_script('gi-integrated-script', 'gi_ajax_integrated', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_integrated_nonce'),
            'search_nonce' => wp_create_nonce('gi_integrated_search_nonce'),
            'version' => GI_THEME_VERSION,
            'debug' => WP_DEBUG,
            'performance_mode' => get_option('gi_performance_mode', 'optimized'),
            'cache_enabled' => get_option('gi_cache_enabled', true)
        ));
    }
    
    /**
     * 統合情報の出力
     */
    public function output_integration_info() {
        if (WP_DEBUG) {
            echo "<!-- Grant Insight Integration Master v" . GI_THEME_VERSION . " -->\n";
            echo "<!-- Loaded Modules: " . implode(', ', array_keys($this->loaded_modules)) . " -->\n";
        }
    }
    
    /**
     * 統合検索処理
     */
    public function handle_integrated_search() {
        // セキュリティ検証
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_integrated_nonce') &&
            !wp_verify_nonce($_POST['nonce'] ?? '', 'gi_integrated_search_nonce')) {
            wp_die('Security check failed');
        }
        
        // パフォーマンス監視開始
        if (class_exists('GI_Performance_Monitor')) {
            GI_Performance_Monitor::start_monitoring();
        }
        
        // 最適化された検索関数を使用
        if (function_exists('gi_ajax_load_grants_secure')) {
            gi_ajax_load_grants_secure();
        } elseif (function_exists('gi_ajax_load_grants_optimized')) {
            gi_ajax_load_grants_optimized();
        } else {
            gi_ajax_load_grants();
        }
        
        // パフォーマンス監視終了
        if (class_exists('GI_Performance_Monitor')) {
            GI_Performance_Monitor::end_monitoring('Integrated Search');
        }
    }
    
    /**
     * リライトルールの確認
     */
    private function check_rewrite_rules() {
        $rules_option = get_option('gi_rewrite_rules_version');
        
        if ($rules_option !== GI_THEME_VERSION) {
            flush_rewrite_rules();
            update_option('gi_rewrite_rules_version', GI_THEME_VERSION);
            
            if (WP_DEBUG) {
                error_log('GI Integration: Rewrite rules flushed for version ' . GI_THEME_VERSION);
            }
        }
    }
    
    /**
     * デフォルトデータの確認
     */
    private function ensure_default_data() {
        // 都道府県データ
        if (function_exists('gi_insert_default_prefectures')) {
            gi_insert_default_prefectures();
        }
        
        // カテゴリデータ
        if (function_exists('gi_insert_default_categories')) {
            gi_insert_default_categories();
        }
    }
    
    /**
     * パフォーマンス最適化の適用
     */
    private function apply_performance_optimizations() {
        // 不要なスクリプトの削除
        if (class_exists('GI_Resource_Optimizer')) {
            GI_Resource_Optimizer::remove_unnecessary_scripts();
        }
        
        // データベース最適化（週1回）
        if (!wp_next_scheduled('gi_weekly_optimization')) {
            wp_schedule_event(time(), 'weekly', 'gi_weekly_optimization');
        }
    }
    
    /**
     * 管理メニューの追加
     */
    public function add_admin_menu() {
        add_theme_page(
            'Grant Insight 設定',
            'Grant Insight',
            'manage_options',
            'grant-insight-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 設定の登録
     */
    public function register_settings() {
        register_setting('gi_settings', 'gi_performance_mode');
        register_setting('gi_settings', 'gi_cache_enabled');
        register_setting('gi_settings', 'gi_security_level');
        register_setting('gi_settings', 'gi_debug_mode');
    }
    
    /**
     * 管理画面
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight 設定</h1>
            
            <div class="notice notice-success">
                <p><strong>統合マスター v<?php echo GI_THEME_VERSION; ?> が正常に動作しています。</strong></p>
            </div>
            
            <h2>読み込み済みモジュール</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ファイル名</th>
                        <th>説明</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->loaded_modules as $file => $description): ?>
                    <tr>
                        <td><code><?php echo esc_html($file); ?></code></td>
                        <td><?php echo esc_html($description); ?></td>
                        <td><span class="dashicons dashicons-yes-alt" style="color: green;"></span> 読み込み済み</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="post" action="options.php">
                <?php settings_fields('gi_settings'); ?>
                <?php do_settings_sections('gi_settings'); ?>
                
                <h2>パフォーマンス設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">パフォーマンスモード</th>
                        <td>
                            <select name="gi_performance_mode">
                                <option value="optimized" <?php selected(get_option('gi_performance_mode', 'optimized'), 'optimized'); ?>>最適化</option>
                                <option value="balanced" <?php selected(get_option('gi_performance_mode'), 'balanced'); ?>>バランス</option>
                                <option value="compatibility" <?php selected(get_option('gi_performance_mode'), 'compatibility'); ?>>互換性重視</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">キャッシュ機能</th>
                        <td>
                            <input type="checkbox" name="gi_cache_enabled" value="1" <?php checked(get_option('gi_cache_enabled', true)); ?> />
                            <label>キャッシュ機能を有効にする</label>
                        </td>
                    </tr>
                </table>
                
                <h2>セキュリティ設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">セキュリティレベル</th>
                        <td>
                            <select name="gi_security_level">
                                <option value="high" <?php selected(get_option('gi_security_level', 'high'), 'high'); ?>>高</option>
                                <option value="medium" <?php selected(get_option('gi_security_level'), 'medium'); ?>>中</option>
                                <option value="low" <?php selected(get_option('gi_security_level'), 'low'); ?>>低</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2>デバッグ設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">デバッグモード</th>
                        <td>
                            <input type="checkbox" name="gi_debug_mode" value="1" <?php checked(get_option('gi_debug_mode', WP_DEBUG)); ?> />
                            <label>デバッグ情報を出力する</label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>システム情報</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>テーマバージョン</strong></td>
                        <td><?php echo GI_THEME_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress バージョン</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PHP バージョン</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>メモリ使用量</strong></td>
                        <td><?php echo size_format(memory_get_peak_usage(true)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>実行時間</strong></td>
                        <td><?php echo number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); ?>ms</td>
                    </tr>
                </tbody>
            </table>
            
            <h2>キャッシュ管理</h2>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('themes.php?page=grant-insight-settings&action=clear_cache'), 'clear_cache'); ?>" 
                   class="button button-secondary">キャッシュをクリア</a>
            </p>
            
            <?php if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && wp_verify_nonce($_GET['_wpnonce'], 'clear_cache')): ?>
                <?php
                if (class_exists('GI_Cache_System')) {
                    $cache = GI_Cache_System::getInstance();
                    $cache->flush_group();
                    echo '<div class="notice notice-success"><p>キャッシュをクリアしました。</p></div>';
                }
                ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 非アクティブ化時の処理
     */
    public function on_deactivation() {
        // スケジュールされたイベントの削除
        wp_clear_scheduled_hook('gi_daily_cleanup');
        wp_clear_scheduled_hook('gi_weekly_optimization');
        
        // キャッシュのクリア
        if (class_exists('GI_Cache_System')) {
            $cache = GI_Cache_System::getInstance();
            $cache->flush_group();
        }
        
        if (WP_DEBUG) {
            error_log('GI Integration: Deactivation cleanup completed');
        }
    }
    
    /**
     * 統合ステータスの取得
     */
    public function get_integration_status() {
        return array(
            'version' => GI_THEME_VERSION,
            'loaded_modules' => $this->loaded_modules,
            'performance_mode' => get_option('gi_performance_mode', 'optimized'),
            'cache_enabled' => get_option('gi_cache_enabled', true),
            'security_level' => get_option('gi_security_level', 'high'),
            'memory_usage' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        );
    }
}

// 統合マスターの初期化
$gi_integration_master = GI_Integration_Master::getInstance();

/**
 * 統合マスターのグローバル関数
 */
if (!function_exists('gi_get_integration_status')) {
    function gi_get_integration_status() {
        global $gi_integration_master;
        return $gi_integration_master ? $gi_integration_master->get_integration_status() : array();
    }
}

if (!function_exists('gi_is_integration_active')) {
    function gi_is_integration_active() {
        return defined('GI_INTEGRATION_MASTER_LOADED') && GI_INTEGRATION_MASTER_LOADED;
    }
}

// 統合完了ログ
if (WP_DEBUG) {
    error_log('GI Integration Master: Successfully loaded and initialized v' . GI_THEME_VERSION);
}

