<?php
/**
 * AJAX処理 - セキュリティ強化版
 * 
 * このファイルでは、テーマのAJAXリクエストを処理するハンドラを定義します。
 * セキュリティ、エラーハンドリング、入力値サニタイズを統一的に実装しています。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * セキュリティチェック共通関数
 */
function gi_verify_ajax_security($nonce_name = 'gi_ajax_nonce', $action = null) {
    // Nonceチェック
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : '';
    
    // 複数のnonce名をチェック可能にする
    $valid_nonces = ['gi_ajax_nonce', 'grant_insight_search_nonce'];
    $is_valid = false;
    
    foreach ($valid_nonces as $valid_nonce_name) {
        if (wp_verify_nonce($nonce, $valid_nonce_name)) {
            $is_valid = true;
            break;
        }
    }
    
    if (!$is_valid) {
        wp_send_json_error(
            array(
                'message' => 'セキュリティチェックに失敗しました。ページを再読み込みしてください。',
                'code' => 'invalid_nonce'
            ),
            403
        );
    }
    
    // 権限チェック（必要に応じて）
    if ($action && !current_user_can($action)) {
        wp_send_json_error(
            array(
                'message' => 'この操作を実行する権限がありません。',
                'code' => 'insufficient_permissions'
            ),
            403
        );
    }
    
    return true;
}

/**
 * 再帰的サニタイズ関数
 */
function gi_sanitize_recursive($data) {
    if (is_array($data)) {
        return array_map('gi_sanitize_recursive', $data);
    }
    
    if (is_object($data)) {
        $sanitized = new stdClass();
        foreach ($data as $key => $value) {
            $sanitized->$key = gi_sanitize_recursive($value);
        }
        return $sanitized;
    }
    
    // スカラー値のサニタイズ
    if (is_string($data)) {
        return sanitize_text_field($data);
    }
    
    return $data;
}

/**
 * エラーログ記録関数
 */
function gi_log_ajax_error($function_name, $error_message, $context = array()) {
    if (WP_DEBUG_LOG) {
        error_log(sprintf(
            '[Grant Insight AJAX Error] %s in %s: %s | Context: %s',
            current_time('mysql'),
            $function_name,
            $error_message,
            json_encode($context)
        ));
    }
}

/**
 * 【改善版】AJAX - 助成金読み込み処理
 */
function gi_ajax_load_grants() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        // 入力値の取得とサニタイズ
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $categories = isset($_POST['categories']) ? gi_sanitize_recursive(json_decode(stripslashes($_POST['categories']), true)) : array();
        $prefectures = isset($_POST['prefectures']) ? gi_sanitize_recursive(json_decode(stripslashes($_POST['prefectures']), true)) : array();
        $amount = isset($_POST['amount']) ? sanitize_text_field($_POST['amount']) : '';
        $status = isset($_POST['status']) ? gi_sanitize_recursive(json_decode(stripslashes($_POST['status']), true)) : array();
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date_desc';
        $view = isset($_POST['view']) ? sanitize_text_field($_POST['view']) : 'grid';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $posts_per_page = 12;
        
        // ステータスマッピング
        if (is_array($status)) {
            $status = array_map(function($s) {
                return $s === 'active' ? 'open' : sanitize_text_field($s);
            }, $status);
        }
        
        // クエリ引数構築
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
        if (!empty($categories) && is_array($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => array_map('sanitize_text_field', $categories),
                'operator' => 'IN'
            );
        }
        
        // 都道府県フィルター
        if (!empty($prefectures) && is_array($prefectures)) {
            $tax_query[] = array(
                'taxonomy' => 'grant_prefecture',
                'field' => 'slug',
                'terms' => array_map('sanitize_text_field', $prefectures),
                'operator' => 'IN'
            );
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        // メタクエリ
        $meta_query = array('relation' => 'AND');
        
        // ステータスフィルター
        if (!empty($status) && is_array($status)) {
            $meta_query[] = array(
                'key' => 'application_status',
                'value' => array_map('sanitize_text_field', $status),
                'compare' => 'IN'
            );
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
        
        // 並び順の設定
        $allowed_sorts = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc', 'deadline_asc', 'title_asc'];
        if (!in_array($sort, $allowed_sorts, true)) {
            $sort = 'date_desc';
        }
        
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
        }
        
        // クエリ実行
        $query = new WP_Query($args);
        
        // エラーチェック
        if (is_wp_error($query)) {
            throw new Exception($query->get_error_message());
        }
        
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
                    $prefecture = esc_html($prefecture_terms[0]->name);
                }
                
                // カテゴリー取得
                $category_terms = get_the_terms($post_id, 'grant_category');
                $main_category = '';
                $related_categories = array();
                
                if ($category_terms && !is_wp_error($category_terms)) {
                    $main_category = esc_html($category_terms[0]->name);
                    if (count($category_terms) > 1) {
                        for ($i = 1; $i < count($category_terms); $i++) {
                            $related_categories[] = esc_html($category_terms[$i]->name);
                        }
                    }
                }
                
                $grants[] = array(
                    'id' => $post_id,
                    'title' => esc_html(get_the_title()),
                    'excerpt' => esc_html(gi_safe_excerpt(get_the_excerpt(), 150)),
                    'permalink' => esc_url(get_permalink()),
                    'thumbnail' => esc_url(get_the_post_thumbnail_url($post_id, 'gi-card-thumb') ?: ''),
                    'date' => esc_html(get_the_date('Y-m-d')),
                    'prefecture' => $prefecture,
                    'main_category' => $main_category,
                    'related_categories' => $related_categories,
                    'amount' => esc_html(gi_format_amount_man(
                        gi_safe_get_meta($post_id, 'max_amount_numeric', 0),
                        gi_safe_get_meta($post_id, 'max_amount', '')
                    )),
                    'organization' => esc_html(gi_safe_get_meta($post_id, 'organization', '')),
                    'deadline' => esc_html(gi_get_formatted_deadline($post_id)),
                    'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')),
                    'is_favorite' => in_array($post_id, $user_favorites, true)
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
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => '助成金の読み込み中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

/**
 * 【改善版】検索候補取得
 */
function gi_ajax_get_search_suggestions() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $suggestions = array();
        
        if (!empty($query)) {
            $args = array(
                's' => $query,
                'post_type' => array('grant', 'tool', 'case_study', 'guide', 'grant_tip'),
                'post_status' => 'publish',
                'posts_per_page' => 5,
                'fields' => 'ids'
            );
            
            $posts = get_posts($args);
            
            if (is_wp_error($posts)) {
                throw new Exception($posts->get_error_message());
            }
            
            foreach ($posts as $pid) {
                $suggestions[] = array(
                    'label' => esc_html(get_the_title($pid)),
                    'value' => esc_html(get_the_title($pid))
                );
            }
        }
        
        wp_send_json_success($suggestions);
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => '検索候補の取得中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');

/**
 * 【改善版】詳細検索
 */
function gi_ajax_advanced_search() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        // 入力値の取得とサニタイズ
        $keyword = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : 
                  (isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '');
        $prefecture = isset($_POST['prefecture']) ? sanitize_text_field($_POST['prefecture']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $amount = isset($_POST['amount']) ? sanitize_text_field($_POST['amount']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // タクソノミークエリ構築
        $tax_query = array('relation' => 'AND');
        
        if (!empty($prefecture)) {
            $tax_query[] = array(
                'taxonomy' => 'grant_prefecture',
                'field' => 'slug',
                'terms' => array($prefecture),
                'operator' => 'IN'
            );
        }
        
        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => array($category),
                'operator' => 'IN'
            );
        }
        
        // メタクエリ構築
        $meta_query = array('relation' => 'AND');
        
        if (!empty($amount)) {
            $amount_ranges = [
                '0-100' => ['max' => 1000000],
                '100-500' => ['min' => 1000000, 'max' => 5000000],
                '500-1000' => ['min' => 5000000, 'max' => 10000000],
                '1000+' => ['min' => 10000000]
            ];
            
            if (isset($amount_ranges[$amount])) {
                $range = $amount_ranges[$amount];
                if (isset($range['min']) && isset($range['max'])) {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => array($range['min'], $range['max']),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                } elseif (isset($range['min'])) {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => $range['min'],
                        'compare' => '>=',
                        'type' => 'NUMERIC'
                    );
                } elseif (isset($range['max'])) {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => $range['max'],
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    );
                }
            }
        }
        
        if (!empty($status)) {
            $status = ($status === 'active') ? 'open' : $status;
            $meta_query[] = array(
                'key' => 'application_status',
                'value' => array($status),
                'compare' => 'IN'
            );
        }
        
        // クエリ引数
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            's' => $keyword,
        );
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
        
        // クエリ実行
        $query = new WP_Query($args);
        
        if (is_wp_error($query)) {
            throw new Exception($query->get_error_message());
        }
        
        $html = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $html .= gi_render_grant_card($pid, 'grid');
            }
            wp_reset_postdata();
        }
        
        if (empty($html)) {
            $html = '<p class="text-gray-500">該当する助成金が見つかりませんでした。</p>';
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => $query->found_posts
        ));
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => '検索中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
add_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');

/**
 * 【改善版】Grant Insightトップページ検索
 */
function gi_ajax_grant_insight_search() {
    try {
        // セキュリティチェック（特定のnonce）
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce')) {
            wp_send_json_error(
                array('message' => 'セキュリティチェックに失敗しました。'),
                403
            );
        }
        
        // 入力値の取得とサニタイズ
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'relevance';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
        $amount_min = isset($_POST['amount_min']) ? absint($_POST['amount_min']) : 0;
        $amount_max = isset($_POST['amount_max']) ? absint($_POST['amount_max']) : 0;
        $deadline = isset($_POST['deadline']) ? sanitize_text_field($_POST['deadline']) : '';
        $page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;
        
        $per_page = 12;
        
        // 投稿タイプの決定
        $allowed_post_types = array('grant', 'tool', 'case_study', 'guide', 'grant_tip');
        $post_types = $allowed_post_types;
        
        if (!empty($post_type) && in_array($post_type, $allowed_post_types, true)) {
            $post_types = array($post_type);
        }
        
        // クエリ引数
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            's' => $keyword,
            'paged' => $page,
            'posts_per_page' => $per_page,
        );
        
        // 並び順の設定
        $allowed_orderby = ['date', 'title', 'modified', 'relevance'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'relevance';
        }
        
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
                $args['order'] = 'DESC';
                break;
        }
        
        // タクソノミークエリ
        $tax_query = array('relation' => 'AND');
        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'term_id',
                'terms' => array($category),
            );
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        // メタクエリ（助成金のみ）
        $meta_query = array('relation' => 'AND');
        
        if (in_array('grant', $post_types, true) || $post_type === 'grant') {
            if ($amount_min > 0 || $amount_max > 0) {
                if ($amount_min > 0 && $amount_max > 0) {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => array($amount_min, $amount_max),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC',
                    );
                } elseif ($amount_max > 0) {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => $amount_max,
                        'compare' => '<=',
                        'type' => 'NUMERIC',
                    );
                } else {
                    $meta_query[] = array(
                        'key' => 'max_amount_numeric',
                        'value' => $amount_min,
                        'compare' => '>=',
                        'type' => 'NUMERIC',
                    );
                }
            }
            
            if (!empty($deadline)) {
                $todayYmd = intval(current_time('Ymd'));
                $targetYmd = $todayYmd;
                
                $deadline_map = [
                    '1month' => '+1 month',
                    '3months' => '+3 months',
                    '6months' => '+6 months',
                    '1year' => '+1 year'
                ];
                
                if (isset($deadline_map[$deadline])) {
                    $targetYmd = intval(date('Ymd', strtotime($deadline_map[$deadline], current_time('timestamp'))));
                }
                
                $meta_query[] = array(
                    'key' => 'deadline_date',
                    'value' => array($todayYmd, $targetYmd),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
            }
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }
        
        // クエリ実行
        $query = new WP_Query($args);
        
        if (is_wp_error($query)) {
            throw new Exception($query->get_error_message());
        }
        
        $results = array();
        $user_favorites = gi_get_user_favorites();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_type_obj = get_post_type_object(get_post_type());
                
                $results[] = array(
                    'id' => $post_id,
                    'title' => esc_html(get_the_title()),
                    'permalink' => esc_url(get_permalink()),
                    'excerpt' => esc_html(gi_safe_excerpt(get_the_excerpt(), 120)),
                    'post_type_label' => esc_html($post_type_obj->labels->singular_name),
                    'is_favorite' => in_array($post_id, $user_favorites, true)
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
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => '検索中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
add_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');

/**
 * 【改善版】お気に入り切り替え
 */
function gi_ajax_toggle_favorite() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        // ログインチェック
        if (!is_user_logged_in()) {
            wp_send_json_error(
                array('message' => 'ログインが必要です。'),
                401
            );
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            throw new Exception('無効な投稿IDです。');
        }
        
        // 投稿の存在確認
        if (!get_post($post_id)) {
            throw new Exception('指定された投稿が見つかりません。');
        }
        
        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        
        if (!is_array($favorites)) {
            $favorites = array();
        }
        
        $is_favorite = false;
        
        if (in_array($post_id, $favorites, true)) {
            // お気に入りから削除
            $favorites = array_diff($favorites, array($post_id));
        } else {
            // お気に入りに追加
            $favorites[] = $post_id;
            $is_favorite = true;
        }
        
        // メタデータ更新
        $result = update_user_meta($user_id, 'gi_favorites', array_values($favorites));
        
        if ($result === false) {
            throw new Exception('お気に入りの更新に失敗しました。');
        }
        
        wp_send_json_success(array(
            'is_favorite' => $is_favorite,
            'count' => count($favorites)
        ));
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => $e->getMessage(),
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');

/**
 * 【改善版】ユーザーのお気に入り取得
 */
function gi_ajax_get_user_favorites() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        // ログインチェック
        if (!is_user_logged_in()) {
            wp_send_json_error(
                array('message' => 'ログインが必要です。'),
                401
            );
        }
        
        $favorites = gi_get_user_favorites();
        
        wp_send_json_success($favorites);
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_GET);
        wp_send_json_error(
            array(
                'message' => 'お気に入りの取得中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_get_user_favorites', 'gi_ajax_get_user_favorites');

/**
 * 【改善版】関連投稿取得
 */
function gi_ajax_get_related_posts() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        
        if ($post_id <= 0) {
            throw new Exception('無効な投稿IDです。');
        }
        
        // 投稿の存在確認
        if (!get_post($post_id)) {
            throw new Exception('指定された投稿が見つかりません。');
        }
        
        $related_posts = gi_get_related_posts($post_id, 4);
        
        if (is_wp_error($related_posts)) {
            throw new Exception($related_posts->get_error_message());
        }
        
        $html = '';
        foreach ($related_posts as $p) {
            $html .= gi_render_grant_card($p->ID, 'grid');
        }
        
        if (empty($html)) {
            $html = '<p class="text-gray-500">関連する投稿が見つかりませんでした。</p>';
        }
        
        wp_send_json_success(array('html' => $html));
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_GET);
        wp_send_json_error(
            array(
                'message' => '関連投稿の取得中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_get_related_posts', 'gi_ajax_get_related_posts');
add_action('wp_ajax_nopriv_get_related_posts', 'gi_ajax_get_related_posts');

/**
 * 【改善版】投稿ビュー追跡
 */
function gi_ajax_track_post_view() {
    try {
        // セキュリティチェック
        gi_verify_ajax_security();
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if ($post_id <= 0) {
            throw new Exception('無効な投稿IDです。');
        }
        
        // 投稿の存在確認
        if (!get_post($post_id)) {
            throw new Exception('指定された投稿が見つかりません。');
        }
        
        // ビュー追跡関数を呼び出し
        if (function_exists('gi_track_post_view')) {
            gi_track_post_view($post_id);
        }
        
        wp_send_json_success(array('tracked' => true));
        
    } catch (Exception $e) {
        gi_log_ajax_error(__FUNCTION__, $e->getMessage(), $_POST);
        wp_send_json_error(
            array(
                'message' => 'ビュー追跡中にエラーが発生しました。',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_track_post_view', 'gi_ajax_track_post_view');
add_action('wp_ajax_nopriv_track_post_view', 'gi_ajax_track_post_view');