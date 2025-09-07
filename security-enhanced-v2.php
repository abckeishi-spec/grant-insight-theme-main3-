<?php
/**
 * Grant Insight Security Enhancement v2
 * セキュリティ強化機能 第2版 - 新しいレポートに基づく追加対策
 * 
 * @package Grant_Insight_Perfect
 * @version 2.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * セキュリティ強化クラス v2
 */
class GI_Security_Enhancement_V2 {
    
    private static $instance = null;
    private static $failed_login_attempts = array();
    private static $rate_limit_data = array();
    
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
        $this->setup_hooks();
        $this->init_security_features();
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // セキュリティヘッダー
        add_action('init', array($this, 'setup_security_headers'));
        add_action('wp_head', array($this, 'output_csp_header'));
        
        // AJAX リクエスト検証
        add_action('wp_ajax_gi_load_grants', array($this, 'validate_ajax_request'), 1);
        add_action('wp_ajax_nopriv_gi_load_grants', array($this, 'validate_ajax_request'), 1);
        add_action('wp_ajax_gi_advanced_search', array($this, 'validate_ajax_request'), 1);
        add_action('wp_ajax_nopriv_gi_advanced_search', array($this, 'validate_ajax_request'), 1);
        
        // ログイン試行制限
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_filter('authenticate', array($this, 'check_login_attempts'), 30, 3);
        
        // ファイルアップロード制限
        add_filter('upload_mimes', array($this, 'restrict_upload_mimes'));
        add_filter('wp_handle_upload_prefilter', array($this, 'validate_file_upload'));
        
        // 権限チェック強化
        add_action('admin_init', array($this, 'enforce_admin_permissions'));
        
        // HTTPS強制
        add_action('template_redirect', array($this, 'force_https'));
        
        // データサニタイズ強化
        add_filter('pre_get_posts', array($this, 'sanitize_query_vars'));
    }
    
    /**
     * セキュリティ機能の初期化
     */
    private function init_security_features() {
        // レート制限データの初期化
        self::$rate_limit_data = get_transient('gi_rate_limit_data') ?: array();
        
        // 失敗ログイン試行データの初期化
        self::$failed_login_attempts = get_transient('gi_failed_login_attempts') ?: array();
        
        // セキュリティログの初期化
        $this->init_security_log();
    }
    
    /**
     * セキュリティヘッダーの設定（強化版）
     */
    public function setup_security_headers() {
        if (!is_admin()) {
            // XSS保護
            header('X-XSS-Protection: 1; mode=block');
            
            // コンテンツタイプスニッフィング防止
            header('X-Content-Type-Options: nosniff');
            
            // クリックジャッキング防止
            header('X-Frame-Options: SAMEORIGIN');
            
            // HSTS（HTTPS強制）
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }
            
            // リファラーポリシー
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // 権限ポリシー
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        }
    }
    
    /**
     * CSP（Content Security Policy）ヘッダーの出力
     */
    public function output_csp_header() {
        $csp_policy = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        );
        
        $csp_header = implode('; ', $csp_policy);
        echo "<meta http-equiv=\"Content-Security-Policy\" content=\"{$csp_header}\">\n";
    }
    
    /**
     * AJAX リクエストの検証（強化版）
     */
    public function validate_ajax_request() {
        // レート制限チェック
        if (!$this->check_rate_limit()) {
            wp_die('Rate limit exceeded', 'Too Many Requests', array('response' => 429));
        }
        
        // リクエストサイズ制限
        if (!$this->check_request_size()) {
            wp_die('Request too large', 'Request Entity Too Large', array('response' => 413));
        }
        
        // nonce検証（強化版）
        if (!$this->verify_nonce_enhanced()) {
            wp_die('Security check failed', 'Forbidden', array('response' => 403));
        }
        
        // 不正パラメータ検出
        if (!$this->validate_request_parameters()) {
            wp_die('Invalid parameters', 'Bad Request', array('response' => 400));
        }
        
        // セキュリティログ記録
        $this->log_security_event('ajax_request_validated', array(
            'action' => $_POST['action'] ?? 'unknown',
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ));
    }
    
    /**
     * レート制限チェック
     */
    private function check_rate_limit() {
        $client_ip = $this->get_client_ip();
        $current_time = time();
        $rate_limit_window = 60; // 1分間
        $max_requests = 60; // 1分間に60リクエスト
        
        // 古いデータをクリーンアップ
        if (isset(self::$rate_limit_data[$client_ip])) {
            self::$rate_limit_data[$client_ip] = array_filter(
                self::$rate_limit_data[$client_ip],
                function($timestamp) use ($current_time, $rate_limit_window) {
                    return ($current_time - $timestamp) < $rate_limit_window;
                }
            );
        } else {
            self::$rate_limit_data[$client_ip] = array();
        }
        
        // リクエスト数チェック
        if (count(self::$rate_limit_data[$client_ip]) >= $max_requests) {
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $client_ip,
                'requests' => count(self::$rate_limit_data[$client_ip])
            ));
            return false;
        }
        
        // 現在のリクエストを記録
        self::$rate_limit_data[$client_ip][] = $current_time;
        set_transient('gi_rate_limit_data', self::$rate_limit_data, 3600);
        
        return true;
    }
    
    /**
     * リクエストサイズ制限
     */
    private function check_request_size() {
        $max_size = 1024 * 1024; // 1MB
        $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
        
        if ($content_length > $max_size) {
            $this->log_security_event('request_size_exceeded', array(
                'size' => $content_length,
                'max_size' => $max_size,
                'ip' => $this->get_client_ip()
            ));
            return false;
        }
        
        return true;
    }
    
    /**
     * nonce検証（強化版）
     */
    private function verify_nonce_enhanced() {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        
        if (empty($nonce)) {
            return false;
        }
        
        // 複数のnonce actionをチェック
        $valid_actions = array(
            'gi_ajax_nonce',
            'grant_insight_search_nonce',
            'gi_load_grants_nonce',
            'gi_advanced_search_nonce'
        );
        
        foreach ($valid_actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }
        
        $this->log_security_event('nonce_verification_failed', array(
            'nonce' => substr($nonce, 0, 10) . '...',
            'ip' => $this->get_client_ip(),
            'action' => $_POST['action'] ?? 'unknown'
        ));
        
        return false;
    }
    
    /**
     * リクエストパラメータの検証
     */
    private function validate_request_parameters() {
        // 危険なパラメータパターンをチェック
        $dangerous_patterns = array(
            '/\<script\>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/\<iframe/i',
            '/\<object/i',
            '/\<embed/i',
            '/eval\(/i',
            '/expression\(/i'
        );
        
        $all_input = array_merge($_POST, $_GET);
        
        foreach ($all_input as $key => $value) {
            if (is_string($value)) {
                foreach ($dangerous_patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $this->log_security_event('dangerous_parameter_detected', array(
                            'parameter' => $key,
                            'value' => substr($value, 0, 100),
                            'pattern' => $pattern,
                            'ip' => $this->get_client_ip()
                        ));
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * データサニタイズ（強化版）
     */
    public static function sanitize_data_enhanced($data, $type = 'text') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitize_data_enhanced($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            
            case 'url':
                return esc_url_raw($data);
            
            case 'int':
            case 'integer':
                return intval($data);
            
            case 'float':
                return floatval($data);
            
            case 'html':
                return wp_kses_post($data);
            
            case 'textarea':
                return sanitize_textarea_field($data);
            
            case 'key':
                return sanitize_key($data);
            
            case 'slug':
                return sanitize_title($data);
            
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * 個人情報の暗号化（強化版）
     */
    public static function encrypt_personal_data($data) {
        if (empty($data)) {
            return $data;
        }
        
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * 個人情報の復号化
     */
    public static function decrypt_personal_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return $encrypted_data;
        }
        
        $key = self::get_encryption_key();
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * 暗号化キーの取得
     */
    private static function get_encryption_key() {
        $key = get_option('gi_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('gi_encryption_key', $key);
        }
        
        return hash('sha256', $key . SECURE_AUTH_KEY);
    }
    
    /**
     * ログイン試行制限
     */
    public function log_failed_login($username) {
        $client_ip = $this->get_client_ip();
        $current_time = time();
        
        if (!isset(self::$failed_login_attempts[$client_ip])) {
            self::$failed_login_attempts[$client_ip] = array();
        }
        
        self::$failed_login_attempts[$client_ip][] = $current_time;
        
        // 古い試行を削除（24時間以内のみ保持）
        self::$failed_login_attempts[$client_ip] = array_filter(
            self::$failed_login_attempts[$client_ip],
            function($timestamp) use ($current_time) {
                return ($current_time - $timestamp) < 86400; // 24時間
            }
        );
        
        set_transient('gi_failed_login_attempts', self::$failed_login_attempts, 86400);
        
        $this->log_security_event('login_failed', array(
            'username' => $username,
            'ip' => $client_ip,
            'attempts' => count(self::$failed_login_attempts[$client_ip])
        ));
    }
    
    /**
     * ログイン試行チェック
     */
    public function check_login_attempts($user, $username, $password) {
        $client_ip = $this->get_client_ip();
        $max_attempts = 5;
        $lockout_duration = 1800; // 30分
        
        if (isset(self::$failed_login_attempts[$client_ip])) {
            $recent_attempts = array_filter(
                self::$failed_login_attempts[$client_ip],
                function($timestamp) use ($lockout_duration) {
                    return (time() - $timestamp) < $lockout_duration;
                }
            );
            
            if (count($recent_attempts) >= $max_attempts) {
                $this->log_security_event('login_blocked', array(
                    'username' => $username,
                    'ip' => $client_ip,
                    'attempts' => count($recent_attempts)
                ));
                
                return new WP_Error('too_many_attempts', 
                    'ログイン試行回数が上限に達しました。30分後に再試行してください。');
            }
        }
        
        return $user;
    }
    
    /**
     * ファイルアップロード制限（強化版）
     */
    public function restrict_upload_mimes($mimes) {
        // 危険なファイル形式を除外
        $dangerous_types = array(
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'rb',
            'sh', 'cgi', 'htaccess', 'htpasswd'
        );
        
        foreach ($dangerous_types as $type) {
            unset($mimes[$type]);
        }
        
        return $mimes;
    }
    
    /**
     * ファイルアップロード検証
     */
    public function validate_file_upload($file) {
        // ファイルサイズ制限（10MB）
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $file['error'] = 'ファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。';
            return $file;
        }
        
        // MIMEタイプ検証
        $allowed_types = array(
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
        
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file_type['type'], $allowed_types)) {
            $file['error'] = '許可されていないファイル形式です。';
            
            $this->log_security_event('file_upload_blocked', array(
                'filename' => $file['name'],
                'type' => $file_type['type'],
                'ip' => $this->get_client_ip()
            ));
            
            return $file;
        }
        
        return $file;
    }
    
    /**
     * 管理者権限チェック強化
     */
    public function enforce_admin_permissions() {
        // 重要な管理ページへのアクセス制限
        $restricted_pages = array(
            'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php'
        );
        
        $current_screen = get_current_screen();
        if ($current_screen && in_array($current_screen->parent_file, $restricted_pages)) {
            if (!current_user_can('manage_options')) {
                wp_die('このページにアクセスする権限がありません。');
            }
        }
    }
    
    /**
     * HTTPS強制
     */
    public function force_https() {
        if (!is_ssl() && !is_admin()) {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            wp_redirect($redirect_url, 301);
            exit;
        }
    }
    
    /**
     * クエリ変数のサニタイズ
     */
    public function sanitize_query_vars($query) {
        if (!is_admin() && $query->is_main_query()) {
            // 検索クエリのサニタイズ
            if ($query->is_search()) {
                $search_term = get_search_query();
                $sanitized_term = self::sanitize_data_enhanced($search_term, 'text');
                
                if ($search_term !== $sanitized_term) {
                    $query->set('s', $sanitized_term);
                }
            }
        }
    }
    
    /**
     * クライアントIPアドレスの取得
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * セキュリティログの初期化
     */
    private function init_security_log() {
        // ログテーブルが存在しない場合は作成
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_security_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data text,
            ip_address varchar(45),
            user_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * セキュリティイベントのログ記録
     */
    private function log_security_event($event_type, $event_data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_security_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => $event_type,
                'event_data' => json_encode($event_data),
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        // 古いログの削除（30日以上前）
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }
    
    /**
     * セキュリティログの取得
     */
    public function get_security_log($limit = 100, $event_type = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_security_log';
        
        $where_clause = '';
        $params = array();
        
        if ($event_type) {
            $where_clause = 'WHERE event_type = %s';
            $params[] = $event_type;
        }
        
        $params[] = intval($limit);
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * gi_safe_escape関数の実装
     */
    public static function gi_safe_escape($data, $context = 'html') {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return self::gi_safe_escape($item, $context);
            }, $data);
        }
        
        switch ($context) {
            case 'attr':
                return esc_attr($data);
            
            case 'url':
                return esc_url($data);
            
            case 'js':
                return esc_js($data);
            
            case 'textarea':
                return esc_textarea($data);
            
            case 'html':
            default:
                return esc_html($data);
        }
    }
}

// セキュリティ強化の初期化
if (!function_exists('gi_init_security_v2')) {
    function gi_init_security_v2() {
        GI_Security_Enhancement_V2::getInstance();
    }
    add_action('init', 'gi_init_security_v2', 1);
}

// gi_safe_escape関数のグローバル定義
if (!function_exists('gi_safe_escape')) {
    function gi_safe_escape($data, $context = 'html') {
        return GI_Security_Enhancement_V2::gi_safe_escape($data, $context);
    }
}

