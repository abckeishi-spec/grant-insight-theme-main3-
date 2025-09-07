<?php
/**
 * Grant Insight Performance Enhancement
 * パフォーマンス最適化機能
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 高度なキャッシュシステム
 */
class GI_Cache_System {
    
    private static $instance = null;
    private $cache_group = 'gi_cache';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * キャッシュ取得
     */
    public function get($key, $default = false) {
        $cache_key = $this->generate_cache_key($key);
        return get_transient($cache_key) ?: $default;
    }
    
    /**
     * キャッシュ設定
     */
    public function set($key, $data, $expiration = 300) {
        $cache_key = $this->generate_cache_key($key);
        return set_transient($cache_key, $data, $expiration);
    }
    
    /**
     * キャッシュ削除
     */
    public function delete($key) {
        $cache_key = $this->generate_cache_key($key);
        return delete_transient($cache_key);
    }
    
    /**
     * キャッシュキー生成
     */
    private function generate_cache_key($key) {
        return $this->cache_group . '_' . md5($key);
    }
    
    /**
     * 関連キャッシュの一括削除
     */
    public function flush_group($group = '') {
        global $wpdb;
        
        $group = $group ?: $this->cache_group;
        $pattern = '_transient_' . $group . '_%';
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s
        ", $pattern));
    }
}

/**
 * データベースクエリ最適化
 */
class GI_Query_Optimizer {
    
    /**
     * 助成金データの一括取得（N+1クエリ解消）
     */
    public static function bulk_get_grants_data($post_ids) {
        if (empty($post_ids)) {
            return array();
        }
        
        $cache = GI_Cache_System::getInstance();
        $cache_key = 'bulk_grants_' . md5(serialize($post_ids));
        
        $cached_data = $cache->get($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // メタデータの一括取得
        $post_ids_str = implode(',', array_map('intval', $post_ids));
        
        $meta_query = "
            SELECT post_id, meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE post_id IN ({$post_ids_str})
            AND meta_key IN (
                'amount_min', 'amount_max', 'deadline', 'application_period',
                'target_business', 'requirements', 'contact_info', 'website_url'
            )
        ";
        
        $meta_results = $wpdb->get_results($meta_query);
        
        // タクソノミーの一括取得
        $taxonomy_query = "
            SELECT tr.object_id, tt.taxonomy, t.term_id, t.name, t.slug
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE tr.object_id IN ({$post_ids_str})
            AND tt.taxonomy IN ('grant_category', 'prefecture')
        ";
        
        $taxonomy_results = $wpdb->get_results($taxonomy_query);
        
        // データの整理
        $organized_data = array();
        
        foreach ($post_ids as $post_id) {
            $organized_data[$post_id] = array(
                'meta' => array(),
                'taxonomies' => array()
            );
        }
        
        // メタデータの整理
        foreach ($meta_results as $meta) {
            $organized_data[$meta->post_id]['meta'][$meta->meta_key] = $meta->meta_value;
        }
        
        // タクソノミーデータの整理
        foreach ($taxonomy_results as $tax) {
            $organized_data[$tax->object_id]['taxonomies'][$tax->taxonomy][] = array(
                'term_id' => $tax->term_id,
                'name' => $tax->name,
                'slug' => $tax->slug
            );
        }
        
        // キャッシュに保存（5分）
        $cache->set($cache_key, $organized_data, 300);
        
        return $organized_data;
    }
    
    /**
     * 検索クエリの最適化
     */
    public static function optimize_search_query($args) {
        // 不要なメタクエリの削除
        if (isset($args['meta_query'])) {
            $args['meta_query'] = array_filter($args['meta_query'], function($query) {
                return !empty($query['value']);
            });
        }
        
        // 不要なタックスクエリの削除
        if (isset($args['tax_query'])) {
            $args['tax_query'] = array_filter($args['tax_query'], function($query) {
                return !empty($query['terms']);
            });
        }
        
        // キャッシュ可能なクエリの場合はキャッシュを使用
        $cache_key = 'search_query_' . md5(serialize($args));
        $cache = GI_Cache_System::getInstance();
        
        $cached_result = $cache->get($cache_key);
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $query = new WP_Query($args);
        
        // 結果をキャッシュ（3分）
        $cache->set($cache_key, $query, 180);
        
        return $query;
    }
}

/**
 * リソース最適化
 */
class GI_Resource_Optimizer {
    
    /**
     * 条件付きスクリプト読み込み
     */
    public static function conditional_enqueue() {
        // フロントページ専用スクリプト
        if (is_front_page()) {
            wp_enqueue_script('gi-front-page', get_template_directory_uri() . '/front-page.js', array('jquery'), GI_THEME_VERSION, true);
            wp_enqueue_script('gi-dynamic-counts', get_template_directory_uri() . '/js/dynamic-counts.js', array('jquery'), GI_THEME_VERSION, true);
        }
        
        // 検索ページ専用スクリプト
        if (is_search() || is_page_template('page-search.php') || is_post_type_archive('grant')) {
            wp_enqueue_script('gi-search-enhanced', get_template_directory_uri() . '/js/search-enhanced.js', array('jquery'), GI_THEME_VERSION, true);
        }
        
        // 助成金詳細ページ専用スクリプト
        if (is_singular('grant')) {
            wp_enqueue_script('gi-grant-detail', get_template_directory_uri() . '/js/grant-detail.js', array('jquery'), GI_THEME_VERSION, true);
        }
        
        // ツールページ専用スクリプト
        if (is_singular('tool') || is_post_type_archive('tool')) {
            wp_enqueue_script('gi-tool-functions', get_template_directory_uri() . '/js/tool-functions.js', array('jquery'), GI_THEME_VERSION, true);
        }
    }
    
    /**
     * 不要なスクリプトの削除
     */
    public static function remove_unnecessary_scripts() {
        if (!is_admin()) {
            // WordPress標準の不要スクリプト削除
            wp_dequeue_script('wp-embed');
            wp_dequeue_script('comment-reply');
            
            // jQuery Migrateの削除
            wp_deregister_script('jquery-migrate');
            
            // 絵文字関連の削除
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            
            // 不要なヘッダー情報の削除
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wp_shortlink_wp_head');
        }
    }
    
    /**
     * CSS最適化
     */
    public static function optimize_css() {
        // 不要なWordPressスタイルの削除
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('classic-theme-styles');
        
        // 条件付きスタイル読み込み
        if (is_front_page()) {
            wp_enqueue_style('gi-front-page', get_template_directory_uri() . '/css/front-page.css', array(), GI_THEME_VERSION);
        }
        
        if (is_search() || is_post_type_archive()) {
            wp_enqueue_style('gi-archive', get_template_directory_uri() . '/css/archive.css', array(), GI_THEME_VERSION);
        }
    }
}

/**
 * データベース最適化
 */
class GI_Database_Optimizer {
    
    /**
     * 期限切れキャッシュの自動削除
     */
    public static function cleanup_expired_cache() {
        global $wpdb;
        
        // 期限切れのtransientを削除
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // 対応するtransientデータも削除
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            AND option_name NOT LIKE '_transient_timeout_%'
            AND option_name NOT IN (
                SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_%'
            )
        ");
    }
    
    /**
     * 未使用メタデータの削除
     */
    public static function cleanup_unused_meta() {
        global $wpdb;
        
        // 存在しない投稿のメタデータを削除
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // 存在しないタームのメタデータを削除
        $wpdb->query("
            DELETE tm FROM {$wpdb->termmeta} tm
            LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
            WHERE t.term_id IS NULL
        ");
    }
    
    /**
     * データベースインデックスの最適化
     */
    public static function optimize_database_indexes() {
        global $wpdb;
        
        // よく使用されるクエリ用のインデックスを追加
        $indexes = array(
            "CREATE INDEX IF NOT EXISTS idx_postmeta_key_value ON {$wpdb->postmeta} (meta_key, meta_value(20))",
            "CREATE INDEX IF NOT EXISTS idx_posts_type_status_date ON {$wpdb->posts} (post_type, post_status, post_date)",
            "CREATE INDEX IF NOT EXISTS idx_term_relationships_object_id ON {$wpdb->term_relationships} (object_id)"
        );
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
    }
}

/**
 * パフォーマンス監視
 */
class GI_Performance_Monitor {
    
    private static $start_time;
    private static $queries_count;
    
    /**
     * 監視開始
     */
    public static function start_monitoring() {
        self::$start_time = microtime(true);
        self::$queries_count = get_num_queries();
    }
    
    /**
     * 監視終了とログ出力
     */
    public static function end_monitoring($context = '') {
        $end_time = microtime(true);
        $execution_time = $end_time - self::$start_time;
        $queries_executed = get_num_queries() - self::$queries_count;
        
        // 開発環境でのみログ出力
        if (WP_DEBUG) {
            error_log(sprintf(
                'GI Performance [%s]: Time: %.4fs, Queries: %d, Memory: %s',
                $context,
                $execution_time,
                $queries_executed,
                size_format(memory_get_peak_usage(true))
            ));
        }
        
        // 閾値を超えた場合の警告
        if ($execution_time > 2.0 || $queries_executed > 50) {
            error_log(sprintf(
                'GI Performance WARNING [%s]: Slow execution detected - Time: %.4fs, Queries: %d',
                $context,
                $execution_time,
                $queries_executed
            ));
        }
    }
}

// フック登録
add_action('wp_enqueue_scripts', array('GI_Resource_Optimizer', 'conditional_enqueue'), 5);
add_action('wp_enqueue_scripts', array('GI_Resource_Optimizer', 'remove_unnecessary_scripts'), 100);
add_action('wp_enqueue_scripts', array('GI_Resource_Optimizer', 'optimize_css'), 5);

// 定期的なクリーンアップ（1日1回）
if (!wp_next_scheduled('gi_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'gi_daily_cleanup');
}

add_action('gi_daily_cleanup', function() {
    GI_Database_Optimizer::cleanup_expired_cache();
    GI_Database_Optimizer::cleanup_unused_meta();
});

// 週1回のデータベース最適化
if (!wp_next_scheduled('gi_weekly_optimization')) {
    wp_schedule_event(time(), 'weekly', 'gi_weekly_optimization');
}

add_action('gi_weekly_optimization', function() {
    GI_Database_Optimizer::optimize_database_indexes();
});

/**
 * 最適化されたAJAX関数（既存関数の置き換え）
 */
if (!function_exists('gi_ajax_load_grants_optimized')) {
    function gi_ajax_load_grants_optimized() {
        GI_Performance_Monitor::start_monitoring();
        
        // nonce検証
        if (!gi_verify_nonce($_POST['nonce'] ?? '', array('gi_ajax_nonce', 'grant_insight_search_nonce'))) {
            wp_die('Security check failed');
        }

        // パラメータの取得とサニタイズ
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(20, max(1, intval($_POST['per_page'] ?? 12)));
        $search_query = sanitize_text_field($_POST['search'] ?? '');
        $categories = gi_sanitize_array(json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: []);
        $prefectures = gi_sanitize_array(json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: []);
        $amount_min = intval($_POST['amount_min'] ?? 0);
        $amount_max = intval($_POST['amount_max'] ?? 0);

        // キャッシュキーの生成
        $cache_key = 'gi_grants_optimized_' . md5(serialize([
            'page' => $page,
            'per_page' => $per_page,
            'search' => $search_query,
            'categories' => $categories,
            'prefectures' => $prefectures,
            'amount_min' => $amount_min,
            'amount_max' => $amount_max
        ]));

        $cache = GI_Cache_System::getInstance();
        $cached_result = $cache->get($cache_key);
        
        if ($cached_result !== false) {
            wp_send_json_success($cached_result);
            return;
        }

        // クエリ引数の構築
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array('relation' => 'AND'),
            'tax_query' => array('relation' => 'AND')
        );

        // 検索クエリ
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }

        // カテゴリーフィルター
        if (!empty($categories)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $categories
            );
        }

        // 都道府県フィルター
        if (!empty($prefectures)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'prefecture',
                'field' => 'slug',
                'terms' => $prefectures
            );
        }

        // 金額フィルター
        if ($amount_min > 0) {
            $args['meta_query'][] = array(
                'key' => 'amount_max',
                'value' => $amount_min,
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }

        if ($amount_max > 0) {
            $args['meta_query'][] = array(
                'key' => 'amount_min',
                'value' => $amount_max,
                'compare' => '<=',
                'type' => 'NUMERIC'
            );
        }

        // 最適化されたクエリ実行
        $query = GI_Query_Optimizer::optimize_search_query($args);
        
        // 結果の処理（一括取得でN+1クエリ解消）
        $grants = array();
        if ($query->have_posts()) {
            $post_ids = wp_list_pluck($query->posts, 'ID');
            
            // 一括データ取得
            $bulk_data = GI_Query_Optimizer::bulk_get_grants_data($post_ids);
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $grants[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'grant-thumbnail'),
                    'meta' => $bulk_data[$post_id]['meta'] ?? array(),
                    'categories' => $bulk_data[$post_id]['taxonomies']['grant_category'] ?? array(),
                    'prefectures' => $bulk_data[$post_id]['taxonomies']['prefecture'] ?? array()
                );
            }
            wp_reset_postdata();
        }

        $result = array(
            'grants' => $grants,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        );

        // キャッシュ保存（5分）
        $cache->set($cache_key, $result, 300);

        GI_Performance_Monitor::end_monitoring('AJAX Load Grants');
        
        wp_send_json_success($result);
    }
}

// 最適化されたAJAX関数を登録
add_action('wp_ajax_gi_load_grants_optimized', 'gi_ajax_load_grants_optimized');
add_action('wp_ajax_nopriv_gi_load_grants_optimized', 'gi_ajax_load_grants_optimized');

// 既存のAJAX関数も最適化版を使用
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants_optimized');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants_optimized');

