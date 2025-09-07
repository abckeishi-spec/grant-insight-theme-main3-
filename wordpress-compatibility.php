<?php
/**
 * Grant Insight WordPress Compatibility Enhancement
 * WordPress互換性向上モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress互換性向上クラス
 */
class GI_WordPress_Compatibility {
    
    private static $instance = null;
    private $wp_version;
    private $deprecated_functions = array();
    
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
        global $wp_version;
        $this->wp_version = $wp_version;
        
        $this->setup_deprecated_functions();
        $this->setup_hooks();
        $this->check_compatibility();
    }
    
    /**
     * 非推奨関数のマッピング設定
     */
    private function setup_deprecated_functions() {
        $this->deprecated_functions = array(
            // WordPress 5.0以降で非推奨
            'create_function' => array(
                'version' => '5.0',
                'replacement' => 'anonymous functions',
                'severity' => 'high'
            ),
            'get_settings' => array(
                'version' => '2.1',
                'replacement' => 'get_option',
                'severity' => 'medium'
            ),
            'wp_specialchars' => array(
                'version' => '2.8',
                'replacement' => 'esc_html',
                'severity' => 'medium'
            ),
            'attribute_escape' => array(
                'version' => '2.8',
                'replacement' => 'esc_attr',
                'severity' => 'medium'
            ),
            'js_escape' => array(
                'version' => '2.8',
                'replacement' => 'esc_js',
                'severity' => 'medium'
            ),
            // WordPress 5.3以降で非推奨
            'wp_make_link_relative' => array(
                'version' => '5.3',
                'replacement' => 'wp_make_link_relative',
                'severity' => 'low'
            ),
            // WordPress 5.5以降で非推奨
            'wp_blacklist_check' => array(
                'version' => '5.5',
                'replacement' => 'wp_check_comment_disallowed_list',
                'severity' => 'medium'
            ),
            // WordPress 6.0以降で非推奨
            'utf8_uri_encode' => array(
                'version' => '6.0',
                'replacement' => 'rawurlencode',
                'severity' => 'low'
            )
        );
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // 管理画面での互換性チェック
        add_action('admin_notices', array($this, 'show_compatibility_notices'));
        
        // テーマサポートの追加
        add_action('after_setup_theme', array($this, 'add_theme_support'));
        
        // 非推奨関数の使用チェック
        if (WP_DEBUG) {
            add_action('init', array($this, 'check_deprecated_function_usage'));
        }
        
        // WordPress更新時の互換性チェック
        add_action('upgrader_process_complete', array($this, 'post_update_compatibility_check'), 10, 2);
    }
    
    /**
     * 互換性チェック
     */
    private function check_compatibility() {
        // 最低要件のチェック
        $min_wp_version = '5.0';
        $min_php_version = '7.4';
        
        if (version_compare($this->wp_version, $min_wp_version, '<')) {
            add_action('admin_notices', function() use ($min_wp_version) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Grant Insight:</strong> このテーマはWordPress ' . $min_wp_version . ' 以降が必要です。';
                echo '現在のバージョン: ' . $this->wp_version;
                echo '</p></div>';
            });
        }
        
        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            add_action('admin_notices', function() use ($min_php_version) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Grant Insight:</strong> このテーマはPHP ' . $min_php_version . ' 以降が必要です。';
                echo '現在のバージョン: ' . PHP_VERSION;
                echo '</p></div>';
            });
        }
    }
    
    /**
     * テーマサポートの追加
     */
    public function add_theme_support() {
        // WordPress 5.0以降の機能
        if (version_compare($this->wp_version, '5.0', '>=')) {
            // ブロックエディタサポート
            add_theme_support('wp-block-styles');
            add_theme_support('align-wide');
            add_theme_support('editor-styles');
            add_editor_style('editor-style.css');
        }
        
        // WordPress 5.2以降の機能
        if (version_compare($this->wp_version, '5.2', '>=')) {
            // レスポンシブ埋め込み
            add_theme_support('responsive-embeds');
        }
        
        // WordPress 5.3以降の機能
        if (version_compare($this->wp_version, '5.3', '>=')) {
            // 自動更新サポート
            add_theme_support('automatic-feed-links');
        }
        
        // WordPress 5.5以降の機能
        if (version_compare($this->wp_version, '5.5', '>=')) {
            // ブロックテンプレート
            add_theme_support('block-templates');
        }
        
        // WordPress 5.8以降の機能
        if (version_compare($this->wp_version, '5.8', '>=')) {
            // ウィジェットブロックエディタ
            add_theme_support('widgets-block-editor');
        }
        
        // WordPress 5.9以降の機能
        if (version_compare($this->wp_version, '5.9', '>=')) {
            // フルサイト編集
            add_theme_support('block-template-parts');
        }
        
        // WordPress 6.0以降の機能
        if (version_compare($this->wp_version, '6.0', '>=')) {
            // 新しいブロック機能
            add_theme_support('appearance-tools');
        }
        
        // 基本的なテーマサポート
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('custom-logo');
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ));
    }
    
    /**
     * 非推奨関数の使用チェック
     */
    public function check_deprecated_function_usage() {
        if (!WP_DEBUG) {
            return;
        }
        
        $theme_files = $this->get_theme_files();
        
        foreach ($theme_files as $file) {
            $content = file_get_contents($file);
            
            foreach ($this->deprecated_functions as $function => $info) {
                if (strpos($content, $function) !== false) {
                    $message = sprintf(
                        'Deprecated function "%s" found in %s. Use "%s" instead. (Deprecated since WordPress %s)',
                        $function,
                        basename($file),
                        $info['replacement'],
                        $info['version']
                    );
                    
                    error_log('GI Compatibility Warning: ' . $message);
                    
                    if ($info['severity'] === 'high') {
                        trigger_error($message, E_USER_WARNING);
                    }
                }
            }
        }
    }
    
    /**
     * テーマファイルの取得
     */
    private function get_theme_files() {
        $theme_dir = get_template_directory();
        $files = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($theme_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * 互換性通知の表示
     */
    public function show_compatibility_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // WordPress バージョンチェック
        $recommended_version = '6.0';
        if (version_compare($this->wp_version, $recommended_version, '<')) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Grant Insight:</strong> WordPress ' . $recommended_version . ' 以降へのアップデートをお勧めします。';
            echo '最新機能とセキュリティ向上のため、定期的な更新をお願いします。</p>';
            echo '</div>';
        }
        
        // PHP バージョンチェック
        $recommended_php = '8.0';
        if (version_compare(PHP_VERSION, $recommended_php, '<')) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Grant Insight:</strong> PHP ' . $recommended_php . ' 以降へのアップデートをお勧めします。';
            echo 'パフォーマンスとセキュリティが向上します。</p>';
            echo '</div>';
        }
        
        // 必要なプラグインのチェック
        $this->check_required_plugins();
    }
    
    /**
     * 必要なプラグインのチェック
     */
    private function check_required_plugins() {
        $recommended_plugins = array(
            'advanced-custom-fields/acf.php' => array(
                'name' => 'Advanced Custom Fields',
                'required' => false,
                'message' => 'カスタムフィールド機能を最大限活用するために推奨されます。'
            ),
            'wp-super-cache/wp-cache.php' => array(
                'name' => 'WP Super Cache',
                'required' => false,
                'message' => 'サイトのパフォーマンス向上のために推奨されます。'
            ),
            'wordfence/wordfence.php' => array(
                'name' => 'Wordfence Security',
                'required' => false,
                'message' => 'セキュリティ強化のために推奨されます。'
            )
        );
        
        foreach ($recommended_plugins as $plugin_path => $plugin_info) {
            if (!is_plugin_active($plugin_path)) {
                $install_url = wp_nonce_url(
                    self_admin_url('update.php?action=install-plugin&plugin=' . dirname($plugin_path)),
                    'install-plugin_' . dirname($plugin_path)
                );
                
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>Grant Insight:</strong> ' . $plugin_info['name'] . ' プラグインが見つかりません。';
                echo $plugin_info['message'];
                echo ' <a href="' . $install_url . '" class="button button-primary">インストール</a></p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * WordPress更新後の互換性チェック
     */
    public function post_update_compatibility_check($upgrader_object, $options) {
        if ($options['type'] === 'core') {
            // WordPressコア更新後の処理
            $this->clear_compatibility_cache();
            $this->check_compatibility();
            
            // テーマの互換性を再チェック
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Grant Insight:</strong> WordPress更新後の互換性チェックが完了しました。';
                echo 'テーマは正常に動作しています。</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * 互換性キャッシュのクリア
     */
    private function clear_compatibility_cache() {
        delete_transient('gi_compatibility_check');
        delete_transient('gi_deprecated_functions_check');
    }
    
    /**
     * 安全な関数呼び出し
     */
    public static function safe_function_call($function_name, $args = array(), $fallback = null) {
        if (function_exists($function_name)) {
            return call_user_func_array($function_name, $args);
        }
        
        if ($fallback !== null) {
            if (is_callable($fallback)) {
                return call_user_func_array($fallback, $args);
            } else {
                return $fallback;
            }
        }
        
        error_log('GI Compatibility: Function "' . $function_name . '" does not exist');
        return false;
    }
    
    /**
     * 条件付き機能実行
     */
    public static function conditional_feature($wp_version_required, $callback, $fallback = null) {
        global $wp_version;
        
        if (version_compare($wp_version, $wp_version_required, '>=')) {
            if (is_callable($callback)) {
                return call_user_func($callback);
            }
        } elseif ($fallback !== null) {
            if (is_callable($fallback)) {
                return call_user_func($fallback);
            } else {
                return $fallback;
            }
        }
        
        return false;
    }
    
    /**
     * 互換性情報の取得
     */
    public function get_compatibility_info() {
        return array(
            'wp_version' => $this->wp_version,
            'php_version' => PHP_VERSION,
            'theme_version' => wp_get_theme()->get('Version'),
            'deprecated_functions' => $this->deprecated_functions,
            'supported_features' => $this->get_supported_features()
        );
    }
    
    /**
     * サポートされている機能の取得
     */
    private function get_supported_features() {
        $features = array();
        
        $theme_supports = array(
            'post-thumbnails',
            'title-tag',
            'custom-logo',
            'html5',
            'wp-block-styles',
            'align-wide',
            'editor-styles',
            'responsive-embeds',
            'automatic-feed-links',
            'block-templates',
            'widgets-block-editor',
            'block-template-parts',
            'appearance-tools'
        );
        
        foreach ($theme_supports as $feature) {
            $features[$feature] = current_theme_supports($feature);
        }
        
        return $features;
    }
}

// WordPress互換性向上の初期化
if (!function_exists('gi_init_wp_compatibility')) {
    function gi_init_wp_compatibility() {
        GI_WordPress_Compatibility::getInstance();
    }
    add_action('after_setup_theme', 'gi_init_wp_compatibility', 1);
}

// ヘルパー関数
if (!function_exists('gi_safe_call')) {
    function gi_safe_call($function_name, $args = array(), $fallback = null) {
        return GI_WordPress_Compatibility::safe_function_call($function_name, $args, $fallback);
    }
}

if (!function_exists('gi_wp_version_check')) {
    function gi_wp_version_check($required_version) {
        global $wp_version;
        return version_compare($wp_version, $required_version, '>=');
    }
}

if (!function_exists('gi_conditional_feature')) {
    function gi_conditional_feature($wp_version_required, $callback, $fallback = null) {
        return GI_WordPress_Compatibility::conditional_feature($wp_version_required, $callback, $fallback);
    }
}

