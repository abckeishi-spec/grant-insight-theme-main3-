<?php
/**
 * AJAX処理
 * 
 * このファイルでは、テーマのAJAXリクエストを処理するハンドラを定義します。
 * 助成金の読み込み、検索候補の取得、お気に入り機能などが含まれます。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 【修正】AJAX - 助成金読み込み処理（都道府県・完全対応版）
 */
function gi_ajax_load_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce') && !wp_verify_nonce($_POST['nonce'] ?? '', 'grant_insight_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }

    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true);
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true);
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true);
    
    // Map UI statuses to ACF values
    if (is_array($status)) {
        $status = array_map(function($s){ return $s === 'active' ? 'open' : $s; }, $status);
    }
    
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = intval($_POST['page'] ?? 1);
    $posts_per_page = 12;

    // クエリ引数
    $args = array(
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish'
    );

    // 検索キーワード
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // タクソノミークエリ
    $tax_query = array('relation' => 'AND');

    // カテゴリーフィルター
    if (!empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        );
    }

    // 都道府県フィルター
    if (!empty($prefectures)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => $prefectures,
            'operator' => 'IN'
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // メタクエリ
    $meta_query = array('relation' => 'AND');

    // ステータスフィルター
    if (!empty($status)) {
        $meta_query[] = array(
            'key' => 'application_status',
            'value' => $status,
            'compare' => 'IN'
        );
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // 並び順
    switch ($sort) {
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'amount_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'DESC';
            break;
        case 'amount_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'ASC';
            break;
        case 'deadline_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'deadline_date';
            $args['order'] = 'ASC';
            break;
        case 'title_asc':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }

    // クエリ実行
    $query = new WP_Query($args);
    $grants = array();
    $user_favorites = gi_get_user_favorites();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // 都道府県取得
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            $prefecture = '';
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                $prefecture = $prefecture_terms[0]->name;
            }

            // カテゴリー取得
            $category_terms = get_the_terms($post_id, 'grant_category');
            $main_category = '';
            $related_categories = array();
            
            if ($category_terms && !is_wp_error($category_terms)) {
                $main_category = $category_terms[0]->name;
                if (count($category_terms) > 1) {
                    for ($i = 1; $i < count($category_terms); $i++) {
                        $related_categories[] = $category_terms[$i]->name;
                    }
                }
            }

            $grants[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'excerpt' => gi_safe_excerpt(get_the_excerpt(), 150),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
                'date' => get_the_date('Y-m-d'),
                'prefecture' => $prefecture,
                'main_category' => $main_category,
                'related_categories' => $related_categories,
                'amount' => gi_format_amount_man(gi_safe_get_meta($post_id, 'max_amount_numeric', 0), gi_safe_get_meta($post_id, 'max_amount', '')),
                'organization' => gi_safe_escape(gi_safe_get_meta($post_id, 'organization')),
                'deadline' => gi_get_formatted_deadline($post_id),
                'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')),
                'is_favorite' => in_array($post_id, $user_favorites)
            );
        }
        wp_reset_postdata();
    }

    // ページネーション情報
    $pagination = array(
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
        'total_posts' => $query->found_posts,
        'posts_per_page' => $posts_per_page
    );

    // クエリ情報
    $query_info = array(
        'search' => $search,
        'categories' => $categories,
        'prefectures' => $prefectures,
        'amount' => $amount,
        'status' => $status,
        'sort' => $sort
    );

    wp_send_json_success(array(
        'grants' => $grants,
        'found_posts' => $query->found_posts,
        'pagination' => $pagination,
        'query_info' => $query_info,
        'view' => $view
    ));
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

// 1) Search suggestions
function gi_ajax_get_search_suggestions() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $query = sanitize_text_field($_POST['query'] ?? '');
    $suggestions = array();
    if ($query !== '') {
        $args = array(
            's' => $query,
            'post_type' => array('grant','tool','case_study','guide','grant_tip'),
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'fields' => 'ids'
        );
        $posts = get_posts($args);
        foreach ($posts as $pid) {
            $suggestions[] = array(
                'label' => get_the_title($pid),
                'value' => get_the_title($pid)
            );
        }
    }
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');

// 2) Advanced search (simple wrapper around gi_search with HTML list)
function gi_ajax_advanced_search() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $keyword = sanitize_text_field($_POST['search_query'] ?? ($_POST['s'] ?? ''));
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? '');

    $tax_query = array('relation' => 'AND');
    if ($prefecture) {
        $tax_query[] = array('taxonomy'=>'grant_prefecture','field'=>'slug','terms'=>array($prefecture),'operator'=>'IN');
    }
    if ($category) {
        $tax_query[] = array('taxonomy'=>'grant_category','field'=>'slug','terms'=>array($category),'operator'=>'IN');
    }

    $meta_query = array('relation' => 'AND');
    if ($amount) {
        switch ($amount) {
            case '0-100':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>1000000,'compare'=>'<=','type'=>'NUMERIC');
                break;
            case '100-500':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(1000000,5000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '500-1000':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(5000000,10000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '1000+':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>10000000,'compare'=>'>=','type'=>'NUMERIC');
                break;
        }
    }
    if ($status) {
        $status = $status === 'active' ? 'open' : $status;
        $meta_query[] = array('key'=>'application_status','value'=>array($status),'compare'=>'IN');
    }

    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        's' => $keyword,
    );
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;
    if (count($meta_query) > 1) $args['meta_query'] = $meta_query;

    $q = new WP_Query($args);
    $html = '';
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $html .= gi_render_grant_card($pid, 'grid');
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array(
        'html' => $html ?: '<p class="text-gray-500">該当する助成金が見つかりませんでした。</p>',
        'count' => $q->found_posts
    ));
}
add_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
add_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');

// 2.5) Grant Insight top page search (section-search.php)
function gi_ajax_grant_insight_search() {
    // Verify nonce specific to front-page search section
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    $keyword   = sanitize_text_field($_POST['keyword'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? '');
    $orderby   = sanitize_text_field($_POST['orderby'] ?? 'relevance');
    $category  = sanitize_text_field($_POST['category'] ?? ''); // term_id expected for grant_category
    $amount_min = isset($_POST['amount_min']) ? intval($_POST['amount_min']) : 0;
    $amount_max = isset($_POST['amount_max']) ? intval($_POST['amount_max']) : 0;
    $deadline   = sanitize_text_field($_POST['deadline'] ?? '');
    $page       = max(1, intval($_POST['page'] ?? 1));

    $per_page = 12;

    // Determine post types
    $post_types = array('grant','tool','case_study','guide','grant_tip');
    if (!empty($post_type)) {
        $post_types = array($post_type);
    }

    $args = array(
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        's'              => $keyword,
        'paged'          => $page,
        'posts_per_page' => $per_page,
    );

    // Orderby mapping
    switch ($orderby) {
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'modified':
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
            break;
        case 'relevance':
        default:
            $args['orderby'] = 'relevance';
            $args['order']   = 'DESC';
            break;
    }

    // Tax query (grant category only when applicable)
    $tax_query = array('relation' => 'AND');
    if (!empty($category)) {
        // Only apply to grants; ignore for others
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field'    => 'term_id',
            'terms'    => array(intval($category)),
        );
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // Meta query for grants (amount and deadline)
    $meta_query = array('relation' => 'AND');
    if (in_array('grant', $post_types, true) || $post_type === 'grant') {
        if ($amount_min > 0 || $amount_max > 0) {
            $range = array();
            if ($amount_min > 0) $range[] = $amount_min;
            if ($amount_max > 0) $range[] = $amount_max;
            $meta_query[] = array(
                'key'     => 'max_amount_numeric',
                'value'   => $amount_max > 0 && $amount_min > 0 ? array($amount_min, $amount_max) : ($amount_max > 0 ? $amount_max : $amount_min),
                'compare' => ($amount_min > 0 && $amount_max > 0) ? 'BETWEEN' : ($amount_max > 0 ? '<=' : '>='),
                'type'    => 'NUMERIC',
            );
        }

        if (!empty($deadline)) {
            $todayYmd = intval(current_time('Ymd'));
            $targetYmd = $todayYmd;
            switch ($deadline) {
                case '1month':
                    $targetYmd = intval(date('Ymd', strtotime('+1 month', current_time('timestamp'))));
                    break;
                case '3months':
                    $targetYmd = intval(date('Ymd', strtotime('+3 months', current_time('timestamp'))));
                    break;
                case '6months':
                    $targetYmd = intval(date('Ymd', strtotime('+6 months', current_time('timestamp'))));
                    break;
                case '1year':
                    $targetYmd = intval(date('Ymd', strtotime('+1 year', current_time('timestamp'))));
                    break;
            }
            $meta_query[] = array(
                'key'     => 'deadline_date',
                'value'   => array($todayYmd, $targetYmd),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            );
        }
    }
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);
    $results = array();
    $user_favorites = gi_get_user_favorites();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_type_obj = get_post_type_object(get_post_type());
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => gi_safe_excerpt(get_the_excerpt(), 120),
                'post_type_label' => $post_type_obj->labels->singular_name,
                'is_favorite' => in_array($post_id, $user_favorites)
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'results' => $results,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current_page' => $page
    ));
}
add_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
add_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');

// 3) Toggle favorite
function gi_ajax_toggle_favorite() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    if (!is_user_logged_in()) {
        wp_send_json_error('ログインが必要です。');
    }
    $post_id = intval($_POST['post_id'] ?? 0);
    if ($post_id <= 0) {
        wp_send_json_error('無効な投稿IDです。');
    }
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'gi_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }
    $is_favorite = false;
    if (in_array($post_id, $favorites)) {
        $favorites = array_diff($favorites, array($post_id));
    } else {
        $favorites[] = $post_id;
        $is_favorite = true;
    }
    update_user_meta($user_id, 'gi_favorites', $favorites);
    wp_send_json_success(array('is_favorite' => $is_favorite, 'count' => count($favorites)));
}
add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');

// 4) Get user favorites
function gi_ajax_get_user_favorites() {
    if (!wp_verify_nonce($_GET['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    if (!is_user_logged_in()) {
        wp_send_json_error('ログインが必要です。');
    }
    $favorites = gi_get_user_favorites();
    wp_send_json_success($favorites);
}
add_action('wp_ajax_get_user_favorites', 'gi_ajax_get_user_favorites');

// 5) Get related posts
function gi_ajax_get_related_posts() {
    if (!wp_verify_nonce($_GET['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $post_id = intval($_GET['post_id'] ?? 0);
    if ($post_id <= 0) {
        wp_send_json_error('無効な投稿IDです。');
    }
    $related_posts = gi_get_related_posts($post_id, 4);
    $html = '';
    foreach ($related_posts as $p) {
        $html .= gi_render_grant_card($p->ID, 'grid');
    }
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_get_related_posts', 'gi_ajax_get_related_posts');
add_action('wp_ajax_nopriv_get_related_posts', 'gi_ajax_nopriv_get_related_posts');

// 6) Track post view
function gi_ajax_track_post_view() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $post_id = intval($_POST['post_id'] ?? 0);
    if ($post_id > 0) {
        gi_track_post_view($post_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('無効な投稿IDです。');
    }
}
add_action('wp_ajax_track_post_view', 'gi_ajax_track_post_view');
add_action('wp_ajax_nopriv_track_post_view', 'gi_ajax_nopriv_track_post_view');


