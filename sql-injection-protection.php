<?php
/**
 * Grant Insight SQL Injection Protection
 * SQLインジェクション対策専用モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SQLインジェクション対策クラス
 */
class GI_SQL_Injection_Protection {
    
    /**
     * 初期化
     */
    public static function init() {
        // 既存のクエリ関数をフック
        add_filter('posts_where', array(__CLASS__, 'sanitize_posts_where'), 10, 2);
        add_filter('posts_join', array(__CLASS__, 'sanitize_posts_join'), 10, 2);
        add_filter('posts_orderby', array(__CLASS__, 'sanitize_posts_orderby'), 10, 2);
        
        // カスタムクエリの監視
        add_action('pre_get_posts', array(__CLASS__, 'validate_query_vars'));
    }
    
    /**
     * WHERE句のサニタイズ
     */
    public static function sanitize_posts_where($where, $query) {
        global $wpdb;
        
        // 危険なSQLパターンを検出
        $dangerous_patterns = array(
            '/UNION\s+SELECT/i',
            '/DROP\s+TABLE/i',
            '/DELETE\s+FROM/i',
            '/INSERT\s+INTO/i',
            '/UPDATE\s+SET/i',
            '/EXEC\s*\(/i',
            '/EXECUTE\s*\(/i',
            '/xp_cmdshell/i',
            '/sp_executesql/i',
            '/--\s*$/m',
            '/\/\*.*?\*\//s'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $where)) {
                // 危険なクエリを検出した場合はログに記録
                error_log('GI Security: Dangerous SQL pattern detected in WHERE clause: ' . $pattern);
                
                // セキュリティログに記録
                if (class_exists('GI_Security_Enhancement_V2')) {
                    $security = GI_Security_Enhancement_V2::getInstance();
                    // セキュリティイベントをログに記録（privateメソッドなので直接呼び出せないため、代替手段を使用）
                    do_action('gi_security_event', 'sql_injection_attempt', array(
                        'where_clause' => substr($where, 0, 200),
                        'pattern' => $pattern,
                        'query_vars' => $query->query_vars
                    ));
                }
                
                // 安全なデフォルトクエリに置き換え
                return " AND 1=0 "; // 結果を返さない安全なクエリ
            }
        }
        
        return $where;
    }
    
    /**
     * JOIN句のサニタイズ
     */
    public static function sanitize_posts_join($join, $query) {
        // JOIN句でも同様の危険パターンをチェック
        $dangerous_patterns = array(
            '/UNION\s+SELECT/i',
            '/DROP\s+TABLE/i',
            '/DELETE\s+FROM/i',
            '/INSERT\s+INTO/i',
            '/UPDATE\s+SET/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $join)) {
                error_log('GI Security: Dangerous SQL pattern detected in JOIN clause: ' . $pattern);
                return ''; // 危険なJOINを無効化
            }
        }
        
        return $join;
    }
    
    /**
     * ORDER BY句のサニタイズ
     */
    public static function sanitize_posts_orderby($orderby, $query) {
        // ORDER BY句での危険パターンをチェック
        $dangerous_patterns = array(
            '/UNION\s+SELECT/i',
            '/\(\s*SELECT/i',
            '/CASE\s+WHEN/i',
            '/IF\s*\(/i',
            '/SLEEP\s*\(/i',
            '/BENCHMARK\s*\(/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $orderby)) {
                error_log('GI Security: Dangerous SQL pattern detected in ORDER BY clause: ' . $pattern);
                return 'post_date DESC'; // 安全なデフォルトソート
            }
        }
        
        return $orderby;
    }
    
    /**
     * クエリ変数の検証
     */
    public static function validate_query_vars($query) {
        if (is_admin()) {
            return; // 管理画面では制限を緩める
        }
        
        $query_vars = $query->query_vars;
        
        // 危険なクエリ変数をチェック
        $dangerous_vars = array(
            'meta_query', 'tax_query', 'date_query'
        );
        
        foreach ($dangerous_vars as $var) {
            if (isset($query_vars[$var]) && is_array($query_vars[$var])) {
                $query_vars[$var] = self::sanitize_query_array($query_vars[$var]);
                $query->set($var, $query_vars[$var]);
            }
        }
    }
    
    /**
     * クエリ配列のサニタイズ
     */
    private static function sanitize_query_array($array) {
        if (!is_array($array)) {
            return self::sanitize_query_value($array);
        }
        
        $sanitized = array();
        
        foreach ($array as $key => $value) {
            $sanitized_key = self::sanitize_query_key($key);
            
            if (is_array($value)) {
                $sanitized[$sanitized_key] = self::sanitize_query_array($value);
            } else {
                $sanitized[$sanitized_key] = self::sanitize_query_value($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * クエリキーのサニタイズ
     */
    private static function sanitize_query_key($key) {
        // 許可されたキーのホワイトリスト
        $allowed_keys = array(
            'key', 'value', 'compare', 'type', 'relation',
            'taxonomy', 'field', 'terms', 'operator',
            'year', 'month', 'day', 'hour', 'minute', 'second',
            'after', 'before', 'inclusive', 'column'
        );
        
        if (in_array($key, $allowed_keys)) {
            return sanitize_key($key);
        }
        
        // 許可されていないキーは削除
        error_log('GI Security: Unauthorized query key detected: ' . $key);
        return null;
    }
    
    /**
     * クエリ値のサニタイズ
     */
    private static function sanitize_query_value($value) {
        if (is_numeric($value)) {
            return is_float($value) ? floatval($value) : intval($value);
        }
        
        if (is_string($value)) {
            // SQLインジェクションパターンをチェック
            $dangerous_patterns = array(
                '/UNION\s+SELECT/i',
                '/DROP\s+TABLE/i',
                '/DELETE\s+FROM/i',
                '/INSERT\s+INTO/i',
                '/UPDATE\s+SET/i',
                '/EXEC\s*\(/i',
                '/xp_cmdshell/i',
                '/--\s*$/m',
                '/\/\*.*?\*\//s',
                '/\'\s*OR\s*\'/i',
                '/\'\s*AND\s*\'/i'
            );
            
            foreach ($dangerous_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    error_log('GI Security: Dangerous SQL pattern in query value: ' . substr($value, 0, 100));
                    return ''; // 危険な値は空文字に置き換え
                }
            }
            
            return sanitize_text_field($value);
        }
        
        return $value;
    }
    
    /**
     * プリペアードステートメントの強制使用
     */
    public static function force_prepared_statements() {
        global $wpdb;
        
        // wpdbクエリメソッドをフック
        add_filter('query', array(__CLASS__, 'validate_raw_query'));
    }
    
    /**
     * 生のクエリの検証
     */
    public static function validate_raw_query($query) {
        // プリペアードステートメントを使用していない危険なクエリパターンを検出
        if (preg_match('/\$\w+|\%[sd]/', $query)) {
            // 変数が直接埋め込まれている可能性
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            
            error_log('GI Security: Potentially unsafe query detected: ' . substr($query, 0, 200));
            error_log('GI Security: Query called from: ' . json_encode($backtrace));
        }
        
        return $query;
    }
    
    /**
     * 安全なクエリビルダー
     */
    public static function build_safe_meta_query($meta_queries) {
        if (!is_array($meta_queries)) {
            return array();
        }
        
        $safe_queries = array();
        
        foreach ($meta_queries as $meta_query) {
            if (!is_array($meta_query)) {
                continue;
            }
            
            $safe_query = array();
            
            // キーのサニタイズ
            if (isset($meta_query['key'])) {
                $safe_query['key'] = sanitize_key($meta_query['key']);
            }
            
            // 値のサニタイズ
            if (isset($meta_query['value'])) {
                if (is_array($meta_query['value'])) {
                    $safe_query['value'] = array_map('sanitize_text_field', $meta_query['value']);
                } else {
                    $safe_query['value'] = sanitize_text_field($meta_query['value']);
                }
            }
            
            // 比較演算子の検証
            if (isset($meta_query['compare'])) {
                $allowed_compares = array('=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS');
                
                if (in_array(strtoupper($meta_query['compare']), $allowed_compares)) {
                    $safe_query['compare'] = strtoupper($meta_query['compare']);
                } else {
                    $safe_query['compare'] = '='; // デフォルト
                }
            }
            
            // タイプの検証
            if (isset($meta_query['type'])) {
                $allowed_types = array('NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED');
                
                if (in_array(strtoupper($meta_query['type']), $allowed_types)) {
                    $safe_query['type'] = strtoupper($meta_query['type']);
                }
            }
            
            if (!empty($safe_query)) {
                $safe_queries[] = $safe_query;
            }
        }
        
        return $safe_queries;
    }
    
    /**
     * 安全なタクソノミークエリビルダー
     */
    public static function build_safe_tax_query($tax_queries) {
        if (!is_array($tax_queries)) {
            return array();
        }
        
        $safe_queries = array();
        
        foreach ($tax_queries as $tax_query) {
            if (!is_array($tax_query)) {
                continue;
            }
            
            $safe_query = array();
            
            // タクソノミーの検証
            if (isset($tax_query['taxonomy'])) {
                $taxonomy = sanitize_key($tax_query['taxonomy']);
                if (taxonomy_exists($taxonomy)) {
                    $safe_query['taxonomy'] = $taxonomy;
                } else {
                    continue; // 存在しないタクソノミーはスキップ
                }
            }
            
            // フィールドの検証
            if (isset($tax_query['field'])) {
                $allowed_fields = array('term_id', 'name', 'slug', 'term_taxonomy_id');
                
                if (in_array($tax_query['field'], $allowed_fields)) {
                    $safe_query['field'] = $tax_query['field'];
                } else {
                    $safe_query['field'] = 'term_id'; // デフォルト
                }
            }
            
            // タームの検証
            if (isset($tax_query['terms'])) {
                if (is_array($tax_query['terms'])) {
                    $safe_query['terms'] = array_map('sanitize_text_field', $tax_query['terms']);
                } else {
                    $safe_query['terms'] = sanitize_text_field($tax_query['terms']);
                }
            }
            
            // 演算子の検証
            if (isset($tax_query['operator'])) {
                $allowed_operators = array('IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS');
                
                if (in_array(strtoupper($tax_query['operator']), $allowed_operators)) {
                    $safe_query['operator'] = strtoupper($tax_query['operator']);
                } else {
                    $safe_query['operator'] = 'IN'; // デフォルト
                }
            }
            
            if (!empty($safe_query)) {
                $safe_queries[] = $safe_query;
            }
        }
        
        return $safe_queries;
    }
}

// SQLインジェクション対策の初期化
if (!function_exists('gi_init_sql_protection')) {
    function gi_init_sql_protection() {
        GI_SQL_Injection_Protection::init();
        GI_SQL_Injection_Protection::force_prepared_statements();
    }
    add_action('init', 'gi_init_sql_protection', 1);
}

// 安全なクエリ関数のグローバル定義
if (!function_exists('gi_safe_meta_query')) {
    function gi_safe_meta_query($meta_queries) {
        return GI_SQL_Injection_Protection::build_safe_meta_query($meta_queries);
    }
}

if (!function_exists('gi_safe_tax_query')) {
    function gi_safe_tax_query($tax_queries) {
        return GI_SQL_Injection_Protection::build_safe_tax_query($tax_queries);
    }
}

