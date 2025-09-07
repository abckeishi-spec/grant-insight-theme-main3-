<?php
/**
 * 助成金件数動的取得機能
 * 
 * カテゴリー別、都道府県別の助成金件数を動的に取得し、
 * キャッシュを使用してパフォーマンスを最適化します。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * カテゴリー別助成金件数を取得
 * 
 * @param string $category_slug カテゴリースラッグ
 * @param bool $use_cache キャッシュを使用するか
 * @return int 助成金件数
 */
function gi_get_category_count($category_slug, $use_cache = true) {
    try {
        if (empty($category_slug)) {
            return 0;
        }
        
        // サニタイズ
        $category_slug = sanitize_title($category_slug);
        
        // キャッシュキー生成
        $cache_key = 'gi_category_count_' . $category_slug;
        $cache_group = 'grant_counts';
        
        // キャッシュ確認
        if ($use_cache) {
            $cached_count = wp_cache_get($cache_key, $cache_group);
            if ($cached_count !== false) {
                return intval($cached_count);
            }
        }
        
        // カテゴリーが存在するか確認
        $term = get_term_by('slug', $category_slug, 'grant_category');
        if (!$term || is_wp_error($term)) {
            // キャッシュに0を保存
            wp_cache_set($cache_key, 0, $cache_group, HOUR_IN_SECONDS);
            return 0;
        }
        
        // WP_Queryで件数取得
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => $category_slug,
                )
            ),
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        // キャッシュに保存（1時間）
        wp_cache_set($cache_key, $count, $cache_group, HOUR_IN_SECONDS);
        
        return intval($count);
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_category_count error: ' . $e->getMessage());
        }
        return 0;
    }
}

/**
 * 都道府県別助成金件数を取得
 * 
 * @param string $prefecture_slug 都道府県スラッグ
 * @param bool $use_cache キャッシュを使用するか
 * @return int 助成金件数
 */
function gi_get_prefecture_count($prefecture_slug, $use_cache = true) {
    try {
        if (empty($prefecture_slug)) {
            return 0;
        }
        
        // サニタイズ
        $prefecture_slug = sanitize_title($prefecture_slug);
        
        // キャッシュキー生成
        $cache_key = 'gi_prefecture_count_' . $prefecture_slug;
        $cache_group = 'grant_counts';
        
        // キャッシュ確認
        if ($use_cache) {
            $cached_count = wp_cache_get($cache_key, $cache_group);
            if ($cached_count !== false) {
                return intval($cached_count);
            }
        }
        
        // 都道府県が存在するか確認
        $term = get_term_by('slug', $prefecture_slug, 'grant_prefecture');
        if (!$term || is_wp_error($term)) {
            // キャッシュに0を保存
            wp_cache_set($cache_key, 0, $cache_group, HOUR_IN_SECONDS);
            return 0;
        }
        
        // WP_Queryで件数取得
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'grant_prefecture',
                    'field' => 'slug',
                    'terms' => $prefecture_slug,
                )
            ),
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        // キャッシュに保存（1時間）
        wp_cache_set($cache_key, $count, $cache_group, HOUR_IN_SECONDS);
        
        return intval($count);
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_prefecture_count error: ' . $e->getMessage());
        }
        return 0;
    }
}

/**
 * 全体の助成金件数を取得
 * 
 * @param bool $use_cache キャッシュを使用するか
 * @return int 助成金件数
 */
function gi_get_total_grant_count($use_cache = true) {
    try {
        // キャッシュキー
        $cache_key = 'gi_total_grant_count';
        $cache_group = 'grant_counts';
        
        // キャッシュ確認
        if ($use_cache) {
            $cached_count = wp_cache_get($cache_key, $cache_group);
            if ($cached_count !== false) {
                return intval($cached_count);
            }
        }
        
        // WP_Queryで件数取得
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        // キャッシュに保存（1時間）
        wp_cache_set($cache_key, $count, $cache_group, HOUR_IN_SECONDS);
        
        return intval($count);
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_total_grant_count error: ' . $e->getMessage());
        }
        return 0;
    }
}

/**
 * 複数カテゴリーの件数を一括取得
 * 
 * @param array $category_slugs カテゴリースラッグの配列
 * @return array カテゴリースラッグ => 件数の連想配列
 */
function gi_get_category_counts_bulk($category_slugs) {
    $counts = array();
    
    if (!is_array($category_slugs) || empty($category_slugs)) {
        return $counts;
    }
    
    foreach ($category_slugs as $slug) {
        $counts[$slug] = gi_get_category_count($slug);
    }
    
    return $counts;
}

/**
 * 複数都道府県の件数を一括取得
 * 
 * @param array $prefecture_slugs 都道府県スラッグの配列
 * @return array 都道府県スラッグ => 件数の連想配列
 */
function gi_get_prefecture_counts_bulk($prefecture_slugs) {
    $counts = array();
    
    if (!is_array($prefecture_slugs) || empty($prefecture_slugs)) {
        return $counts;
    }
    
    foreach ($prefecture_slugs as $slug) {
        $counts[$slug] = gi_get_prefecture_count($slug);
    }
    
    return $counts;
}

/**
 * キャッシュをクリア
 * 助成金が追加・更新・削除された時に呼び出される
 */
function gi_clear_grant_counts_cache() {
    // 全てのカテゴリーを取得
    $categories = get_terms(array(
        'taxonomy' => 'grant_category',
        'hide_empty' => false,
        'fields' => 'slugs'
    ));
    
    if (!is_wp_error($categories)) {
        foreach ($categories as $slug) {
            wp_cache_delete('gi_category_count_' . $slug, 'grant_counts');
        }
    }
    
    // 全ての都道府県を取得
    $prefectures = get_terms(array(
        'taxonomy' => 'grant_prefecture',
        'hide_empty' => false,
        'fields' => 'slugs'
    ));
    
    if (!is_wp_error($prefectures)) {
        foreach ($prefectures as $slug) {
            wp_cache_delete('gi_prefecture_count_' . $slug, 'grant_counts');
        }
    }
    
    // 全体件数のキャッシュもクリア
    wp_cache_delete('gi_total_grant_count', 'grant_counts');
}

// 助成金の保存・削除時にキャッシュをクリア
add_action('save_post_grant', 'gi_clear_grant_counts_cache');
add_action('delete_post', function($post_id) {
    if (get_post_type($post_id) === 'grant') {
        gi_clear_grant_counts_cache();
    }
});
add_action('transition_post_status', function($new_status, $old_status, $post) {
    if ($post->post_type === 'grant') {
        gi_clear_grant_counts_cache();
    }
}, 10, 3);

/**
 * 表示用ヘルパー関数
 * HTMLに直接埋め込む際に使用
 * 
 * @param string $type 'category' または 'prefecture'
 * @param string $slug スラッグ
 * @param string $format 表示フォーマット（%d件）
 * @return string フォーマット済み文字列
 */
function gi_display_grant_count($type, $slug, $format = '%d件') {
    $count = 0;
    
    switch ($type) {
        case 'category':
            $count = gi_get_category_count($slug);
            break;
        case 'prefecture':
            $count = gi_get_prefecture_count($slug);
            break;
        case 'total':
            $count = gi_get_total_grant_count();
            break;
    }
    
    // エラー時のフォールバック
    if ($count === 0) {
        return '<span class="grant-count">-件</span>';
    }
    
    return sprintf('<span class="grant-count">' . $format . '</span>', number_format($count));
}

/**
 * ショートコード登録
 * [grant_count type="category" slug="it-digital"]
 * [grant_count type="prefecture" slug="tokyo"]
 * [grant_count type="total"]
 */
function gi_grant_count_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'total',
        'slug' => '',
        'format' => '%d件'
    ), $atts);
    
    return gi_display_grant_count($atts['type'], $atts['slug'], $atts['format']);
}
add_shortcode('grant_count', 'gi_grant_count_shortcode');

/**
 * AJAX エンドポイント - 件数取得
 */
function gi_ajax_get_grant_counts() {
    try {
        // Nonceチェック
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gi_ajax_nonce')) {
            wp_send_json_error('セキュリティチェックに失敗しました。', 403);
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $slugs = isset($_POST['slugs']) ? array_map('sanitize_text_field', (array)$_POST['slugs']) : array();
        
        $counts = array();
        
        switch ($type) {
            case 'category':
                $counts = gi_get_category_counts_bulk($slugs);
                break;
            case 'prefecture':
                $counts = gi_get_prefecture_counts_bulk($slugs);
                break;
            case 'total':
                $counts['total'] = gi_get_total_grant_count();
                break;
            default:
                wp_send_json_error('無効なタイプが指定されました。');
        }
        
        wp_send_json_success($counts);
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_ajax_get_grant_counts error: ' . $e->getMessage());
        }
        wp_send_json_error('件数の取得中にエラーが発生しました。', 500);
    }
}
add_action('wp_ajax_get_grant_counts', 'gi_ajax_get_grant_counts');
add_action('wp_ajax_nopriv_get_grant_counts', 'gi_ajax_get_grant_counts');