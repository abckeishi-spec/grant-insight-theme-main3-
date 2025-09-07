<?php
/**
 * Grant Insight Perfect - Complete Functions File v6.2 - 修正版
 * Tailwind CSS Play CDN完全対応版 - 都道府県・AJAX・カード統合完璧版
 * 
 * @package Grant_Insight_Perfect
 * @version 6.2.1-fixed
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// テーマバージョン定数
if (!defined('GI_THEME_VERSION')) {
    define('GI_THEME_VERSION', '6.2.1');
}
if (!defined('GI_THEME_PREFIX')) {
    define('GI_THEME_PREFIX', 'gi_');
}

/**
 * テーマセットアップ
 */
if (!function_exists('gi_setup')) {
    function gi_setup() {
        // テーマサポート追加
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ));
        add_theme_support('custom-background');
        add_theme_support('custom-logo', array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        ));

        // メニューサポート
        register_nav_menus(array(
            'primary' => __('Primary Menu', 'grant-insight'),
            'footer'  => __('Footer Menu', 'grant-insight'),
        ));

        // 画像サイズ
        add_image_size('grant-thumbnail', 300, 200, true);
        add_image_size('grant-large', 800, 600, true);
    }
}
add_action('after_setup_theme', 'gi_setup');

/**
 * スクリプトとスタイルの読み込み（最適化版）
 */
if (!function_exists('gi_enqueue_scripts')) {
    function gi_enqueue_scripts() {
        // 基本スタイル
        wp_enqueue_style('gi-style', get_stylesheet_uri(), array(), GI_THEME_VERSION);
        
        // Tailwind CSS（条件付き読み込み）
        if (is_front_page() || is_search() || is_page_template('page-search.php')) {
            wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), null, false);
        }
        
        // Font Awesome（必要なページのみ）
        if (is_front_page() || is_single() || is_archive()) {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
        }
        
        // Google Fonts（最適化）
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap', array(), null);
        
        // メインJavaScript（条件付き読み込み）
        if (is_front_page()) {
            wp_enqueue_script('gi-front-page', get_template_directory_uri() . '/front-page.js', array('jquery'), GI_THEME_VERSION, true);
        }
        
        if (is_search() || is_page_template('page-search.php')) {
            wp_enqueue_script('gi-search', get_template_directory_uri() . '/js/search.js', array('jquery'), GI_THEME_VERSION, true);
        }
        
        // AJAX設定
        wp_localize_script('gi-front-page', 'gi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_ajax_nonce'),
            'search_nonce' => wp_create_nonce('grant_insight_search_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'gi_enqueue_scripts');

/**
 * カスタム投稿タイプ登録（完全版）
 */
if (!function_exists('gi_register_post_types')) {
    function gi_register_post_types() {
        // 助成金投稿タイプ
        register_post_type('grant', array(
            'labels' => array(
                'name' => '助成金',
                'singular_name' => '助成金',
                'add_new' => '新規追加',
                'add_new_item' => '新しい助成金を追加',
                'edit_item' => '助成金を編集',
                'new_item' => '新しい助成金',
                'view_item' => '助成金を表示',
                'search_items' => '助成金を検索',
                'not_found' => '助成金が見つかりません',
                'not_found_in_trash' => 'ゴミ箱に助成金はありません'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'grants'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-money-alt',
            'show_in_rest' => true
        ));

        // ツール投稿タイプ
        register_post_type('tool', array(
            'labels' => array(
                'name' => '診断ツール',
                'singular_name' => '診断ツール',
                'add_new' => '新規追加',
                'add_new_item' => '新しいツールを追加',
                'edit_item' => 'ツールを編集',
                'new_item' => '新しいツール',
                'view_item' => 'ツールを表示',
                'search_items' => 'ツールを検索',
                'not_found' => 'ツールが見つかりません',
                'not_found_in_trash' => 'ゴミ箱にツールはありません'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'tools'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-admin-tools',
            'show_in_rest' => true
        ));

        // 成功事例投稿タイプ
        register_post_type('case_study', array(
            'labels' => array(
                'name' => '成功事例',
                'singular_name' => '成功事例',
                'add_new' => '新規追加',
                'add_new_item' => '新しい事例を追加',
                'edit_item' => '事例を編集',
                'new_item' => '新しい事例',
                'view_item' => '事例を表示',
                'search_items' => '事例を検索',
                'not_found' => '事例が見つかりません',
                'not_found_in_trash' => 'ゴミ箱に事例はありません'
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'case-studies'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-chart-line',
            'show_in_rest' => true
        ));
    }
}
add_action('init', 'gi_register_post_types');

/**
 * カスタムタクソノミー登録（完全版・都道府県対応・修正版）
 */
if (!function_exists('gi_register_taxonomies')) {
    function gi_register_taxonomies() {
        // 助成金カテゴリー
        register_taxonomy('grant_category', 'grant', array(
            'labels' => array(
                'name' => '助成金カテゴリー',
                'singular_name' => '助成金カテゴリー',
                'search_items' => 'カテゴリーを検索',
                'all_items' => 'すべてのカテゴリー',
                'parent_item' => '親カテゴリー',
                'parent_item_colon' => '親カテゴリー:',
                'edit_item' => 'カテゴリーを編集',
                'update_item' => 'カテゴリーを更新',
                'add_new_item' => '新しいカテゴリーを追加',
                'new_item_name' => '新しいカテゴリー名',
                'menu_name' => 'カテゴリー'
            ),
            'hierarchical' => true,
            'public' => true,
            'rewrite' => array('slug' => 'grant-category'),
            'show_in_rest' => true
        ));

        // 都道府県タクソノミー
        register_taxonomy('prefecture', array('grant', 'tool', 'case_study'), array(
            'labels' => array(
                'name' => '都道府県',
                'singular_name' => '都道府県',
                'search_items' => '都道府県を検索',
                'all_items' => 'すべての都道府県',
                'edit_item' => '都道府県を編集',
                'update_item' => '都道府県を更新',
                'add_new_item' => '新しい都道府県を追加',
                'new_item_name' => '新しい都道府県名',
                'menu_name' => '都道府県'
            ),
            'hierarchical' => true,
            'public' => true,
            'rewrite' => array('slug' => 'prefecture'),
            'show_in_rest' => true
        ));

        // ツールカテゴリー
        register_taxonomy('tool_category', 'tool', array(
            'labels' => array(
                'name' => 'ツールカテゴリー',
                'singular_name' => 'ツールカテゴリー',
                'search_items' => 'カテゴリーを検索',
                'all_items' => 'すべてのカテゴリー',
                'edit_item' => 'カテゴリーを編集',
                'update_item' => 'カテゴリーを更新',
                'add_new_item' => '新しいカテゴリーを追加',
                'new_item_name' => '新しいカテゴリー名',
                'menu_name' => 'カテゴリー'
            ),
            'hierarchical' => true,
            'public' => true,
            'rewrite' => array('slug' => 'tool-category'),
            'show_in_rest' => true
        ));
    }
}
add_action('init', 'gi_register_taxonomies');

/**
 * デフォルト都道府県データの挿入（最適化版）
 */
if (!function_exists('gi_insert_default_prefectures')) {
    function gi_insert_default_prefectures() {
        // キャッシュチェック
        $cache_key = 'gi_prefectures_inserted';
        if (get_transient($cache_key)) {
            return;
        }

        $prefectures = array(
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        );

        foreach ($prefectures as $prefecture) {
            if (!term_exists($prefecture, 'prefecture')) {
                wp_insert_term($prefecture, 'prefecture');
            }
        }

        // キャッシュ設定（24時間）
        set_transient($cache_key, true, DAY_IN_SECONDS);
    }
}
add_action('init', 'gi_insert_default_prefectures');

/**
 * デフォルトカテゴリーデータの挿入（最適化版）
 */
if (!function_exists('gi_insert_default_categories')) {
    function gi_insert_default_categories() {
        // キャッシュチェック
        $cache_key = 'gi_categories_inserted';
        if (get_transient($cache_key)) {
            return;
        }

        $categories = array(
            'スタートアップ支援',
            '研究開発',
            '地域振興',
            '環境・エネルギー',
            'IT・デジタル',
            '製造業',
            '農業・林業・水産業',
            '観光・サービス業',
            '医療・福祉',
            '教育・人材育成'
        );

        foreach ($categories as $category) {
            if (!term_exists($category, 'grant_category')) {
                wp_insert_term($category, 'grant_category');
            }
        }

        // キャッシュ設定（24時間）
        set_transient($cache_key, true, DAY_IN_SECONDS);
    }
}
add_action('init', 'gi_insert_default_categories');

/**
 * 安全な数値フォーマット関数
 */
if (!function_exists('gi_safe_number_format')) {
    function gi_safe_number_format($value, $decimals = 0) {
        if (is_numeric($value)) {
            return number_format((float)$value, $decimals);
        }
        return '0';
    }
}

/**
 * 安全な日付フォーマット関数
 */
if (!function_exists('gi_safe_date_format')) {
    function gi_safe_date_format($date, $format = 'Y-m-d') {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }
        
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }
        
        return date($format, $timestamp);
    }
}

/**
 * 安全なパーセント表示関数
 */
if (!function_exists('gi_safe_percent_format')) {
    function gi_safe_percent_format($value, $decimals = 1) {
        if (is_numeric($value)) {
            return number_format((float)$value, $decimals) . '%';
        }
        return '0%';
    }
}

/**
 * 安全なURL関数
 */
if (!function_exists('gi_safe_url')) {
    function gi_safe_url($url) {
        return esc_url($url);
    }
}

/**
 * 安全なJSON関数
 */
if (!function_exists('gi_safe_json')) {
    function gi_safe_json($data) {
        return wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * AJAX検索機能（最適化版・N+1クエリ解消）
 */
if (!function_exists('gi_ajax_load_grants')) {
    function gi_ajax_load_grants() {
        // nonce検証（修正版）
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'gi_ajax_nonce') || 
                          wp_verify_nonce($_POST['nonce'], 'grant_insight_search_nonce');
        }
        
        if (!$nonce_valid) {
            wp_die('Security check failed');
        }

        // パラメータの取得とサニタイズ
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(20, max(1, intval($_POST['per_page'] ?? 12)));
        $search_query = sanitize_text_field($_POST['search'] ?? '');
        $categories = array_map('sanitize_text_field', json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: []);
        $prefectures = array_map('sanitize_text_field', json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: []);
        $amount_min = intval($_POST['amount_min'] ?? 0);
        $amount_max = intval($_POST['amount_max'] ?? 0);

        // キャッシュキーの生成
        $cache_key = 'gi_grants_' . md5(serialize([
            'page' => $page,
            'per_page' => $per_page,
            'search' => $search_query,
            'categories' => $categories,
            'prefectures' => $prefectures,
            'amount_min' => $amount_min,
            'amount_max' => $amount_max
        ]));

        // キャッシュチェック
        $cached_result = get_transient($cache_key);
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

        // クエリ実行
        $query = new WP_Query($args);
        
        // 結果の処理（一括取得でN+1クエリ解消）
        $grants = array();
        if ($query->have_posts()) {
            $post_ids = wp_list_pluck($query->posts, 'ID');
            
            // メタデータを一括取得
            $meta_data = gi_bulk_get_post_meta($post_ids);
            
            // タクソノミーデータを一括取得
            $taxonomy_data = gi_bulk_get_post_terms($post_ids, ['grant_category', 'prefecture']);
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $grants[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'grant-thumbnail'),
                    'meta' => $meta_data[$post_id] ?? array(),
                    'categories' => $taxonomy_data[$post_id]['grant_category'] ?? array(),
                    'prefectures' => $taxonomy_data[$post_id]['prefecture'] ?? array()
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
        set_transient($cache_key, $result, 300);

        wp_send_json_success($result);
    }
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

/**
 * メタデータ一括取得関数（N+1クエリ解消）
 */
if (!function_exists('gi_bulk_get_post_meta')) {
    function gi_bulk_get_post_meta($post_ids) {
        global $wpdb;
        
        if (empty($post_ids)) {
            return array();
        }
        
        $post_ids_str = implode(',', array_map('intval', $post_ids));
        $meta_data = array();
        
        $results = $wpdb->get_results("
            SELECT post_id, meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE post_id IN ({$post_ids_str})
        ");
        
        foreach ($results as $row) {
            $meta_data[$row->post_id][$row->meta_key] = $row->meta_value;
        }
        
        return $meta_data;
    }
}

/**
 * タクソノミー一括取得関数（N+1クエリ解消）
 */
if (!function_exists('gi_bulk_get_post_terms')) {
    function gi_bulk_get_post_terms($post_ids, $taxonomies) {
        if (empty($post_ids) || empty($taxonomies)) {
            return array();
        }
        
        $terms_data = array();
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post_ids, $taxonomy);
            
            foreach ($terms as $term) {
                if (isset($term->object_id)) {
                    $terms_data[$term->object_id][$taxonomy][] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }
        }
        
        return $terms_data;
    }
}

/**
 * 高度検索AJAX（最適化版）
 */
if (!function_exists('gi_ajax_advanced_search')) {
    function gi_ajax_advanced_search() {
        // nonce検証
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'gi_ajax_nonce') || 
                          wp_verify_nonce($_POST['nonce'], 'grant_insight_search_nonce');
        }
        
        if (!$nonce_valid) {
            wp_die('Security check failed');
        }

        // 検索パラメータの処理
        $search_params = array(
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'categories' => array_map('sanitize_text_field', json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: []),
            'prefectures' => array_map('sanitize_text_field', json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: []),
            'amount_min' => intval($_POST['amount_min'] ?? 0),
            'amount_max' => intval($_POST['amount_max'] ?? 0),
            'deadline_from' => sanitize_text_field($_POST['deadline_from'] ?? ''),
            'deadline_to' => sanitize_text_field($_POST['deadline_to'] ?? ''),
            'page' => max(1, intval($_POST['page'] ?? 1)),
            'per_page' => min(20, max(1, intval($_POST['per_page'] ?? 12)))
        );

        // gi_ajax_load_grantsを再利用
        $_POST = array_merge($_POST, $search_params);
        gi_ajax_load_grants();
    }
}
add_action('wp_ajax_gi_advanced_search', 'gi_ajax_advanced_search');
add_action('wp_ajax_nopriv_gi_advanced_search', 'gi_ajax_advanced_search');

/**
 * パフォーマンス最適化
 */
if (!function_exists('gi_performance_optimizations')) {
    function gi_performance_optimizations() {
        // 不要なスクリプトの削除
        if (!is_admin()) {
            wp_dequeue_script('wp-embed');
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
        }
        
        // 絵文字スクリプトの削除
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
}
add_action('init', 'gi_performance_optimizations');

/**
 * 不要なスクリプトの削除
 */
if (!function_exists('gi_dequeue_unnecessary_scripts')) {
    function gi_dequeue_unnecessary_scripts() {
        if (!is_admin()) {
            // jQuery Migrateの削除
            wp_deregister_script('jquery-migrate');
            
            // 不要なWordPressスクリプトの削除
            wp_dequeue_script('comment-reply');
            wp_dequeue_style('wp-block-library');
        }
    }
}
add_action('wp_enqueue_scripts', 'gi_dequeue_unnecessary_scripts', 100);

/**
 * 検索フォールバック機能
 */
if (!function_exists('gi_search_fallback')) {
    function gi_search_fallback($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            // 助成金も検索対象に含める
            $query->set('post_type', array('post', 'grant', 'tool', 'case_study'));
        }
    }
}
add_action('pre_get_posts', 'gi_search_fallback');

/**
 * カスタムクエリ変数の追加
 */
if (!function_exists('gi_add_query_vars')) {
    function gi_add_query_vars($vars) {
        $vars[] = 'grant_category';
        $vars[] = 'prefecture';
        $vars[] = 'amount_min';
        $vars[] = 'amount_max';
        return $vars;
    }
}
add_filter('query_vars', 'gi_add_query_vars');

/**
 * リライトルールの追加
 */
if (!function_exists('gi_add_rewrite_rules')) {
    function gi_add_rewrite_rules() {
        add_rewrite_rule(
            '^grants/([^/]*)/([^/]*)/?',
            'index.php?post_type=grant&grant_category=$matches[1]&prefecture=$matches[2]',
            'top'
        );
        
        add_rewrite_rule(
            '^tools/([^/]*)/?',
            'index.php?post_type=tool&tool_category=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'gi_add_rewrite_rules');

// テーマアクティベーション時の処理
if (!function_exists('gi_theme_activation')) {
    function gi_theme_activation() {
        // リライトルールをフラッシュ
        flush_rewrite_rules();
        
        // デフォルトデータの挿入
        gi_insert_default_prefectures();
        gi_insert_default_categories();
    }
}
add_action('after_switch_theme', 'gi_theme_activation');

/**
 * エラーハンドリング強化
 */
if (!function_exists('gi_handle_ajax_error')) {
    function gi_handle_ajax_error($message = 'エラーが発生しました') {
        wp_send_json_error(array(
            'message' => $message,
            'timestamp' => current_time('mysql')
        ));
    }
}

/**
 * セキュリティ強化
 */
if (!function_exists('gi_security_headers')) {
    function gi_security_headers() {
        if (!is_admin()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
        }
    }
}
add_action('send_headers', 'gi_security_headers');

/**
 * データサニタイズ強化
 */
if (!function_exists('gi_sanitize_array')) {
    function gi_sanitize_array($data) {
        if (is_array($data)) {
            return array_map('gi_sanitize_array', $data);
        }
        return sanitize_text_field($data);
    }
}

/**
 * nonce検証関数
 */
if (!function_exists('gi_verify_nonce')) {
    function gi_verify_nonce($nonce, $actions = array()) {
        if (empty($actions)) {
            $actions = array('gi_ajax_nonce', 'grant_insight_search_nonce');
        }
        
        foreach ($actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }
        return false;
    }
}

// 初期化完了
if (!defined('GI_FUNCTIONS_LOADED')) {
    define('GI_FUNCTIONS_LOADED', true);
}

