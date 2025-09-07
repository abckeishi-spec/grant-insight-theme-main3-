<?php
/**
 * Grant Insight Security Enhancement
 * セキュリティ強化機能
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * セキュリティ強化クラス
 */
class GI_Security_Enhancement {
    
    /**
     * 初期化
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'setup_security_headers'));
        add_action('wp_ajax_gi_load_grants', array(__CLASS__, 'validate_ajax_request'), 1);
        add_action('wp_ajax_nopriv_gi_load_grants', array(__CLASS__, 'validate_ajax_request'), 1);
        add_action('wp_ajax_gi_advanced_search', array(__CLASS__, 'validate_ajax_request'), 1);
        add_action('wp_ajax_nopriv_gi_advanced_search', array(__CLASS__, 'validate_ajax_request'), 1);
        
        // ログイン試行制限
        add_action('wp_login_failed', array(__CLASS__, 'log_failed_login'));
        add_filter('authenticate', array(__CLASS__, 'check_login_attempts'), 30, 3);
        
        // ファイルアップロード制限
        add_filter('upload_mimes', array(__CLASS__, 'restrict_upload_mimes'));
        add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'validate_file_upload'));
    }
    
    /**
     * セキュリティヘッダーの設定
     */
    public static function setup_security_headers() {
        if (!is_admin()) {
            // XSS保護
            header('X-XSS-Protection: 1; mode=block');
            
            // コンテンツタイプスニッフィング防止
            header('X-Content-Type-Options: nosniff');
            
            // クリックジャッキング防止
            header('X-Frame-Options: SAMEORIGIN');
            
            // リファラーポリシー
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // コンテンツセキュリティポリシー（基本設定）
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
                   "font-src 'self' https://fonts.gstatic.com; " .
                   "img-src 'self' data: https:; " .
                   "connect-src 'self';";
            
            header("Content-Security-Policy: {$csp}");
        }
    }
    
    /**
     * AJAX リクエストの検証
     */
    public static function validate_ajax_request() {
        // リクエスト頻度制限
        $ip = self::get_client_ip();
        $rate_limit_key = 'ajax_rate_limit_' . md5($ip);
        $current_requests = get_transient($rate_limit_key) ?: 0;
        
        if ($current_requests >= 60) { // 1分間に60リクエストまで
            wp_die('Rate limit exceeded. Please try again later.', 'Too Many Requests', array('response' => 429));
        }
        
        set_transient($rate_limit_key, $current_requests + 1, 60);
        
        // リクエストサイズ制限
        $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
        if ($content_length > 1048576) { // 1MB制限
            wp_die('Request too large', 'Request Entity Too Large', array('response' => 413));
        }
        
        // 不正なパラメータチェック
        self::validate_request_parameters();
    }
    
    /**
     * リクエストパラメータの検証
     */
    private static function validate_request_parameters() {
        // SQLインジェクション対策
        $dangerous_patterns = array(
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";]/',
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi'
        );
        
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                foreach ($dangerous_patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        error_log("GI Security: Suspicious request detected from IP: " . self::get_client_ip() . " - Parameter: {$key}");
                        wp_die('Invalid request detected', 'Bad Request', array('response' => 400));
                    }
                }
            }
        }
    }
    
    /**
     * 強化されたnonce検証
     */
    public static function verify_nonce_enhanced($nonce, $actions = array()) {
        if (empty($nonce)) {
            return false;
        }
        
        // デフォルトアクション
        if (empty($actions)) {
            $actions = array('gi_ajax_nonce', 'grant_insight_search_nonce');
        }
        
        // 各アクションでnonce検証
        foreach ($actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                // nonce使用ログ（開発環境のみ）
                if (WP_DEBUG) {
                    error_log("GI Security: Valid nonce used - Action: {$action}, IP: " . self::get_client_ip());
                }
                return true;
            }
        }
        
        // 失敗ログ
        error_log("GI Security: Invalid nonce attempt - IP: " . self::get_client_ip() . " - Nonce: " . substr($nonce, 0, 10) . "...");
        return false;
    }
    
    /**
     * データサニタイズ強化
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
                return intval($data);
            
            case 'float':
                return floatval($data);
            
            case 'html':
                return wp_kses($data, array(
                    'p' => array(),
                    'br' => array(),
                    'strong' => array(),
                    'em' => array(),
                    'a' => array('href' => array(), 'title' => array())
                ));
            
            case 'json':
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::sanitize_data_enhanced($decoded);
                }
                return array();
            
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * ログイン失敗の記録
     */
    public static function log_failed_login($username) {
        $ip = self::get_client_ip();
        $attempts_key = 'login_attempts_' . md5($ip);
        $current_attempts = get_transient($attempts_key) ?: 0;
        
        set_transient($attempts_key, $current_attempts + 1, 900); // 15分間記録
        
        // 失敗ログ
        error_log("GI Security: Failed login attempt - Username: {$username}, IP: {$ip}, Attempts: " . ($current_attempts + 1));
        
        // 5回失敗でIP一時ブロック
        if ($current_attempts >= 4) {
            set_transient('blocked_ip_' . md5($ip), true, 3600); // 1時間ブロック
            error_log("GI Security: IP blocked due to multiple failed login attempts - IP: {$ip}");
        }
    }
    
    /**
     * ログイン試行チェック
     */
    public static function check_login_attempts($user, $username, $password) {
        $ip = self::get_client_ip();
        $blocked_key = 'blocked_ip_' . md5($ip);
        
        if (get_transient($blocked_key)) {
            return new WP_Error('login_blocked', 'ログイン試行回数が上限に達しました。しばらく時間をおいてから再度お試しください。');
        }
        
        return $user;
    }
    
    /**
     * ファイルアップロード制限
     */
    public static function restrict_upload_mimes($mimes) {
        // 危険なファイル形式を除外
        unset($mimes['exe']);
        unset($mimes['bat']);
        unset($mimes['cmd']);
        unset($mimes['com']);
        unset($mimes['pif']);
        unset($mimes['scr']);
        unset($mimes['vbs']);
        unset($mimes['js']);
        
        return $mimes;
    }
    
    /**
     * ファイルアップロード検証
     */
    public static function validate_file_upload($file) {
        // ファイルサイズ制限（5MB）
        if ($file['size'] > 5242880) {
            $file['error'] = 'ファイルサイズが大きすぎます（最大5MB）。';
            return $file;
        }
        
        // ファイル名の検証
        $filename = $file['name'];
        if (preg_match('/[<>:"/\\|?*]/', $filename)) {
            $file['error'] = '無効な文字がファイル名に含まれています。';
            return $file;
        }
        
        // 拡張子の二重チェック
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $file['error'] = '許可されていないファイル形式です。';
            return $file;
        }
        
        return $file;
    }
    
    /**
     * 個人情報の暗号化保存
     */
    public static function encrypt_personal_data($data) {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($data); // フォールバック
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
        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_data); // フォールバック
        }
        
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $key = self::get_encryption_key();
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
        
        return $key;
    }
    
    /**
     * クライアントIPアドレスの取得
     */
    private static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // 複数IPの場合は最初のものを使用
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // IPアドレスの妥当性チェック
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * セキュリティログの記録
     */
    public static function log_security_event($event_type, $details = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'ip' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'event_type' => $event_type,
            'details' => $details
        );
        
        // ログファイルに記録（開発環境）
        if (WP_DEBUG) {
            error_log('GI Security Event: ' . wp_json_encode($log_entry));
        }
        
        // データベースに記録（本番環境では適切なログ管理システムを使用）
        $logs = get_option('gi_security_logs', array());
        $logs[] = $log_entry;
        
        // 最新100件のみ保持
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('gi_security_logs', $logs);
    }
}

/**
 * 強化されたAJAX関数（セキュリティ対応版）
 */
if (!function_exists('gi_ajax_load_grants_secure')) {
    function gi_ajax_load_grants_secure() {
        // セキュリティ検証
        $nonce = $_POST['nonce'] ?? '';
        if (!GI_Security_Enhancement::verify_nonce_enhanced($nonce)) {
            GI_Security_Enhancement::log_security_event('invalid_nonce', array(
                'action' => 'gi_load_grants',
                'nonce' => substr($nonce, 0, 10) . '...'
            ));
            wp_die('Security check failed');
        }
        
        // パラメータのサニタイズ
        $page = GI_Security_Enhancement::sanitize_data_enhanced($_POST['page'] ?? 1, 'int');
        $per_page = min(20, max(1, GI_Security_Enhancement::sanitize_data_enhanced($_POST['per_page'] ?? 12, 'int')));
        $search_query = GI_Security_Enhancement::sanitize_data_enhanced($_POST['search'] ?? '');
        $categories = GI_Security_Enhancement::sanitize_data_enhanced($_POST['categories'] ?? '[]', 'json');
        $prefectures = GI_Security_Enhancement::sanitize_data_enhanced($_POST['prefectures'] ?? '[]', 'json');
        $amount_min = GI_Security_Enhancement::sanitize_data_enhanced($_POST['amount_min'] ?? 0, 'int');
        $amount_max = GI_Security_Enhancement::sanitize_data_enhanced($_POST['amount_max'] ?? 0, 'int');
        
        // 既存のgi_ajax_load_grants_optimized関数を呼び出し
        $_POST['page'] = $page;
        $_POST['per_page'] = $per_page;
        $_POST['search'] = $search_query;
        $_POST['categories'] = wp_json_encode($categories);
        $_POST['prefectures'] = wp_json_encode($prefectures);
        $_POST['amount_min'] = $amount_min;
        $_POST['amount_max'] = $amount_max;
        
        if (function_exists('gi_ajax_load_grants_optimized')) {
            gi_ajax_load_grants_optimized();
        } else {
            gi_ajax_load_grants();
        }
    }
}

// セキュリティ強化の初期化
GI_Security_Enhancement::init();

// セキュアなAJAX関数を登録
add_action('wp_ajax_gi_load_grants_secure', 'gi_ajax_load_grants_secure');
add_action('wp_ajax_nopriv_gi_load_grants_secure', 'gi_ajax_load_grants_secure');

