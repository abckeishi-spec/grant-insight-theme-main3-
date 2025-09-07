<?php
/**
 * Grant Insight - 改善版AJAXハンドラー（重複対策済み）
 * 
 * セキュリティ強化とエラーハンドリング統一
 * タスク1&7: セキュリティとエラーハンドリングの改善
 * 
 * すべての関数を条件付き定義に変更し、重複エラーを防止
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 共通セキュリティチェック関数
 * 
 * @throws Exception セキュリティチェック失敗時
 */
if (!function_exists('gi_verify_ajax_security')) {
    function gi_verify_ajax_security() {
        // 複数のnonceフィールドに対応
        $nonce_fields = array(
            'nonce' => 'gi_ajax_nonce',
            'security' => 'gi_ajax_nonce',
            'search_nonce' => 'grant_insight_search_nonce',
            '_ajax_nonce' => 'gi_ajax_nonce'
        );
        
        $valid = false;
        foreach ($nonce_fields as $field => $action) {
            if (isset($_POST[$field]) && wp_verify_nonce($_POST[$field], $action)) {
                $valid = true;
                break;
            }
        }
        
        if (!$valid) {
            throw new Exception('セキュリティチェックに失敗しました');
        }
    }
}

/**
 * 共通エラーレスポンス関数
 * 
 * @param string $message エラーメッセージ
 * @param array $data 追加データ
 */
if (!function_exists('gi_ajax_error_response')) {
    function gi_ajax_error_response($message, $data = array()) {
        wp_send_json_error(array(
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ));
    }
}

/**
 * 成功レスポンス関数
 * 
 * @param array $data レスポンスデータ
 * @param string $message 成功メッセージ
 */
if (!function_exists('gi_ajax_success_response')) {
    function gi_ajax_success_response($data, $message = '') {
        wp_send_json_success(array(
            'data' => $data,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ));
    }
}

/**
 * 再帰的サニタイズ関数
 * 
 * @param mixed $data サニタイズ対象
 * @return mixed サニタイズ済みデータ
 */
if (!function_exists('gi_sanitize_recursive')) {
    function gi_sanitize_recursive($data) {
        if (is_array($data)) {
            return array_map('gi_sanitize_recursive', $data);
        } elseif (is_object($data)) {
            $cleaned = new stdClass();
            foreach ($data as $key => $value) {
                $cleaned->$key = gi_sanitize_recursive($value);
            }
            return $cleaned;
        } else {
            return sanitize_text_field($data);
        }
    }
}

/**
 * パフォーマンスログ記録
 */
if (!function_exists('gi_log_ajax_performance')) {
    function gi_log_ajax_performance($action, $start_time) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('GI_PERFORMANCE_LOG') && GI_PERFORMANCE_LOG) {
            $execution_time = microtime(true) - $start_time;
            error_log(sprintf(
                'AJAX Performance: %s completed in %.2fms',
                $action,
                $execution_time * 1000
            ));
        }
    }
}

/**
 * 【改善版】AJAX - 助成金読み込み処理
 */
if (!function_exists('gi_ajax_load_grants')) {
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
            $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 12;
            
            // キャッシュキーの生成
            $cache_key = 'gi_grants_' . md5(serialize(array(
                'search' => $search,
                'categories' => $categories,
                'prefectures' => $prefectures,
                'amount' => $amount,
                'status' => $status,
                'sort' => $sort,
                'page' => $page,
                'per_page' => $per_page
            )));
            
            // キャッシュチェック
            $cached_result = get_transient($cache_key);
            if ($cached_result !== false && !defined('WP_DEBUG')) {
                gi_ajax_success_response($cached_result, 'キャッシュから取得');
                return;
            }
            
            // WP_Queryの構築
            $args = array(
                'post_type' => 'grant',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'post_status' => 'publish',
                'meta_query' => array('relation' => 'AND'),
                'tax_query' => array('relation' => 'AND')
            );
            
            // 検索条件
            if (!empty($search)) {
                $args['s'] = $search;
            }
            
            // カテゴリフィルター
            if (!empty($categories) && is_array($categories)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN'
                );
            }
            
            // 都道府県フィルター
            if (!empty($prefectures) && is_array($prefectures)) {
                $args['meta_query'][] = array(
                    'key' => 'municipality',
                    'value' => $prefectures,
                    'compare' => 'IN'
                );
            }
            
            // 金額フィルター
            if (!empty($amount)) {
                $amount_ranges = array(
                    'under_100' => array(0, 100),
                    '100_500' => array(100, 500),
                    '500_1000' => array(500, 1000),
                    'over_1000' => array(1000, PHP_INT_MAX)
                );
                
                if (isset($amount_ranges[$amount])) {
                    $range = $amount_ranges[$amount];
                    $args['meta_query'][] = array(
                        'relation' => 'OR',
                        array(
                            'key' => 'amount_min',
                            'value' => $range[0] * 10000,
                            'compare' => '>=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => 'amount_max',
                            'value' => $range[1] * 10000,
                            'compare' => '<=',
                            'type' => 'NUMERIC'
                        )
                    );
                }
            }
            
            // ステータスフィルター
            if (!empty($status) && is_array($status)) {
                $status_query = array('relation' => 'OR');
                foreach ($status as $s) {
                    if ($s === 'active' || $s === 'open') {
                        $status_query[] = array(
                            'key' => 'deadline',
                            'value' => current_time('Y-m-d'),
                            'compare' => '>=',
                            'type' => 'DATE'
                        );
                    } elseif ($s === 'closed') {
                        $status_query[] = array(
                            'key' => 'deadline',
                            'value' => current_time('Y-m-d'),
                            'compare' => '<',
                            'type' => 'DATE'
                        );
                    }
                }
                if (count($status_query) > 1) {
                    $args['meta_query'][] = $status_query;
                }
            }
            
            // ソート条件
            switch ($sort) {
                case 'amount_desc':
                    $args['meta_key'] = 'amount_max';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                    break;
                case 'amount_asc':
                    $args['meta_key'] = 'amount_min';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'ASC';
                    break;
                case 'deadline_asc':
                    $args['meta_key'] = 'deadline';
                    $args['orderby'] = 'meta_value';
                    $args['order'] = 'ASC';
                    break;
                case 'date_asc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
            
            // クエリ実行
            $query = new WP_Query($args);
            
            // 結果の整形
            $grants = array();
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    
                    // 締切日の処理
                    $deadline = get_post_meta($post_id, 'deadline', true);
                    $deadline_formatted = '';
                    $deadline_status = 'unknown';
                    $days_left = -1;
                    
                    if ($deadline) {
                        $deadline_date = strtotime($deadline);
                        $current_date = current_time('timestamp');
                        $days_left = floor(($deadline_date - $current_date) / (60 * 60 * 24));
                        
                        if ($days_left < 0) {
                            $deadline_status = 'expired';
                            $deadline_formatted = '募集終了';
                        } elseif ($days_left <= 7) {
                            $deadline_status = 'urgent';
                            $deadline_formatted = sprintf('あと%d日', $days_left);
                        } else {
                            $deadline_status = 'active';
                            $deadline_formatted = date_i18n('Y年n月j日', $deadline_date);
                        }
                    }
                    
                    // カテゴリー取得
                    $categories_terms = wp_get_post_terms($post_id, 'grant_category');
                    $category_names = array();
                    foreach ($categories_terms as $term) {
                        $category_names[] = array(
                            'name' => $term->name,
                            'slug' => $term->slug
                        );
                    }
                    
                    $grants[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'permalink' => get_permalink(),
                        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                        'amount_min' => get_post_meta($post_id, 'amount_min', true),
                        'amount_max' => get_post_meta($post_id, 'amount_max', true),
                        'deadline' => $deadline,
                        'deadline_formatted' => $deadline_formatted,
                        'deadline_status' => $deadline_status,
                        'days_left' => $days_left,
                        'municipality' => get_post_meta($post_id, 'municipality', true),
                        'categories' => $category_names,
                        'is_featured' => get_post_meta($post_id, 'is_featured', true) == '1'
                    );
                }
                wp_reset_postdata();
            }
            
            // ページネーション情報
            $total_pages = $query->max_num_pages;
            $total_posts = $query->found_posts;
            
            $result = array(
                'grants' => $grants,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'per_page' => $per_page,
                    'has_next' => $page < $total_pages,
                    'has_prev' => $page > 1
                ),
                'filters' => array(
                    'search' => $search,
                    'categories' => $categories,
                    'prefectures' => $prefectures,
                    'amount' => $amount,
                    'status' => $status,
                    'sort' => $sort,
                    'view' => $view
                )
            );
            
            // 結果をキャッシュ（1時間）
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            
            // 成功レスポンス
            gi_ajax_success_response($result);
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - 検索候補取得
 */
if (!function_exists('gi_ajax_get_search_suggestions')) {
    function gi_ajax_get_search_suggestions() {
        try {
            gi_verify_ajax_security();
            
            $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
            
            if (strlen($query) < 2) {
                gi_ajax_success_response(array());
                return;
            }
            
            // キャッシュチェック
            $cache_key = 'gi_suggestions_' . md5($query);
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                gi_ajax_success_response($cached);
                return;
            }
            
            $suggestions = array();
            
            // 助成金タイトルから検索
            $args = array(
                'post_type' => 'grant',
                'posts_per_page' => 5,
                's' => $query,
                'post_status' => 'publish'
            );
            
            $posts = get_posts($args);
            foreach ($posts as $post) {
                $suggestions[] = array(
                    'title' => $post->post_title,
                    'type' => 'grant',
                    'url' => get_permalink($post->ID)
                );
            }
            
            // カテゴリから検索
            $terms = get_terms(array(
                'taxonomy' => 'grant_category',
                'name__like' => $query,
                'number' => 3,
                'hide_empty' => true
            ));
            
            foreach ($terms as $term) {
                $suggestions[] = array(
                    'title' => $term->name,
                    'type' => 'category',
                    'url' => get_term_link($term)
                );
            }
            
            // キャッシュ保存（30分）
            set_transient($cache_key, $suggestions, 30 * MINUTE_IN_SECONDS);
            
            gi_ajax_success_response($suggestions);
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - 高度な検索
 */
if (!function_exists('gi_ajax_advanced_search')) {
    function gi_ajax_advanced_search() {
        try {
            gi_verify_ajax_security();
            
            $filters = isset($_POST['filters']) ? gi_sanitize_recursive(json_decode(stripslashes($_POST['filters']), true)) : array();
            $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 12;
            
            $args = array(
                'post_type' => 'grant',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'post_status' => 'publish',
                'meta_query' => array('relation' => 'AND'),
                'tax_query' => array('relation' => 'AND')
            );
            
            // 業種フィルター
            if (!empty($filters['industry'])) {
                $args['meta_query'][] = array(
                    'key' => 'target_industry',
                    'value' => $filters['industry'],
                    'compare' => 'LIKE'
                );
            }
            
            // 従業員数フィルター
            if (!empty($filters['employees'])) {
                $employee_ranges = array(
                    'under_10' => array(0, 10),
                    '10_50' => array(10, 50),
                    '50_100' => array(50, 100),
                    'over_100' => array(100, PHP_INT_MAX)
                );
                
                if (isset($employee_ranges[$filters['employees']])) {
                    $range = $employee_ranges[$filters['employees']];
                    $args['meta_query'][] = array(
                        'key' => 'target_number',
                        'value' => array($range[0], $range[1]),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                }
            }
            
            // 利用目的フィルター
            if (!empty($filters['purpose'])) {
                $args['meta_query'][] = array(
                    'key' => 'grant_purpose',
                    'value' => $filters['purpose'],
                    'compare' => 'LIKE'
                );
            }
            
            // 緊急度フィルター
            if (!empty($filters['urgency'])) {
                if ($filters['urgency'] === 'urgent') {
                    $args['meta_query'][] = array(
                        'key' => 'deadline',
                        'value' => array(
                            current_time('Y-m-d'),
                            date('Y-m-d', strtotime('+7 days'))
                        ),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    );
                }
            }
            
            // クエリ実行
            $query = new WP_Query($args);
            
            $results = array();
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    
                    $results[] = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'permalink' => get_permalink(),
                        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                        'amount_min' => get_post_meta($post_id, 'amount_min', true),
                        'amount_max' => get_post_meta($post_id, 'amount_max', true),
                        'deadline' => get_post_meta($post_id, 'deadline', true),
                        'municipality' => get_post_meta($post_id, 'municipality', true),
                        'match_score' => gi_calculate_match_score($post_id, $filters)
                    );
                }
                wp_reset_postdata();
            }
            
            // マッチスコアでソート
            usort($results, function($a, $b) {
                return $b['match_score'] - $a['match_score'];
            });
            
            gi_ajax_success_response(array(
                'results' => $results,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages
            ));
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * マッチスコア計算
 */
if (!function_exists('gi_calculate_match_score')) {
    function gi_calculate_match_score($post_id, $filters) {
        $score = 0;
        
        // 各条件に対するスコア計算
        if (!empty($filters['industry'])) {
            $target_industry = get_post_meta($post_id, 'target_industry', true);
            if (stripos($target_industry, $filters['industry']) !== false) {
                $score += 30;
            }
        }
        
        if (!empty($filters['employees'])) {
            $target_number = get_post_meta($post_id, 'target_number', true);
            // 従業員数がマッチする場合
            $score += 20;
        }
        
        if (!empty($filters['purpose'])) {
            $grant_purpose = get_post_meta($post_id, 'grant_purpose', true);
            if (stripos($grant_purpose, $filters['purpose']) !== false) {
                $score += 25;
            }
        }
        
        // 締切が近い場合はボーナススコア
        $deadline = get_post_meta($post_id, 'deadline', true);
        if ($deadline) {
            $days_left = floor((strtotime($deadline) - current_time('timestamp')) / (60 * 60 * 24));
            if ($days_left > 0 && $days_left <= 7) {
                $score += 15;
            }
        }
        
        // おすすめフラグ
        if (get_post_meta($post_id, 'is_featured', true) == '1') {
            $score += 10;
        }
        
        return $score;
    }
}

/**
 * 【改善版】AJAX - 統合検索
 */
if (!function_exists('gi_ajax_grant_insight_search')) {
    function gi_ajax_grant_insight_search() {
        try {
            gi_verify_ajax_security();
            
            $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
            $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
            
            $results = array();
            
            // 投稿タイプの定義
            $post_types = array();
            switch ($type) {
                case 'grant':
                    $post_types = array('grant');
                    break;
                case 'tip':
                    $post_types = array('grant_tip');
                    break;
                case 'tool':
                    $post_types = array('tool');
                    break;
                case 'case':
                    $post_types = array('case_study');
                    break;
                default:
                    $post_types = array('grant', 'grant_tip', 'tool', 'case_study', 'post', 'page');
            }
            
            // 検索クエリ
            $args = array(
                'post_type' => $post_types,
                'posts_per_page' => 20,
                'paged' => $page,
                's' => $keyword,
                'post_status' => 'publish'
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $post_type = get_post_type();
                    
                    // 投稿タイプ別のラベル
                    $type_labels = array(
                        'grant' => '助成金',
                        'grant_tip' => 'ヒント',
                        'tool' => 'ツール',
                        'case_study' => '事例',
                        'post' => 'ブログ',
                        'page' => 'ページ'
                    );
                    
                    $result_item = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'excerpt' => wp_trim_words(get_the_excerpt(), 30),
                        'permalink' => get_permalink(),
                        'type' => $post_type,
                        'type_label' => isset($type_labels[$post_type]) ? $type_labels[$post_type] : $post_type,
                        'date' => get_the_date('Y年n月j日'),
                        'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail')
                    );
                    
                    // 投稿タイプ別の追加情報
                    if ($post_type === 'grant') {
                        $result_item['amount_min'] = get_post_meta($post_id, 'amount_min', true);
                        $result_item['amount_max'] = get_post_meta($post_id, 'amount_max', true);
                        $result_item['deadline'] = get_post_meta($post_id, 'deadline', true);
                        $result_item['municipality'] = get_post_meta($post_id, 'municipality', true);
                    }
                    
                    $results[] = $result_item;
                }
                wp_reset_postdata();
            }
            
            // 検索履歴の保存（ログインユーザーのみ）
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $search_history = get_user_meta($user_id, 'gi_search_history', true);
                if (!is_array($search_history)) {
                    $search_history = array();
                }
                
                // 新しい検索を追加（重複を避ける）
                $new_search = array(
                    'keyword' => $keyword,
                    'type' => $type,
                    'timestamp' => current_time('timestamp'),
                    'results_count' => $query->found_posts
                );
                
                // 同じキーワードがあれば削除
                $search_history = array_filter($search_history, function($item) use ($keyword) {
                    return $item['keyword'] !== $keyword;
                });
                
                // 先頭に追加
                array_unshift($search_history, $new_search);
                
                // 最大10件まで保持
                $search_history = array_slice($search_history, 0, 10);
                
                update_user_meta($user_id, 'gi_search_history', $search_history);
            }
            
            gi_ajax_success_response(array(
                'results' => $results,
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'keyword' => $keyword,
                'type' => $type
            ));
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - お気に入り切り替え
 */
if (!function_exists('gi_ajax_toggle_favorite')) {
    function gi_ajax_toggle_favorite() {
        try {
            gi_verify_ajax_security();
            
            if (!is_user_logged_in()) {
                throw new Exception('ログインが必要です');
            }
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id || !get_post($post_id)) {
                throw new Exception('無効な投稿IDです');
            }
            
            $user_id = get_current_user_id();
            $favorites = get_user_meta($user_id, 'gi_favorites', true);
            
            if (!is_array($favorites)) {
                $favorites = array();
            }
            
            $is_favorited = in_array($post_id, $favorites);
            
            if ($is_favorited) {
                // お気に入りから削除
                $favorites = array_diff($favorites, array($post_id));
                $action = 'removed';
            } else {
                // お気に入りに追加
                $favorites[] = $post_id;
                $action = 'added';
            }
            
            // 重複を削除して保存
            $favorites = array_unique($favorites);
            update_user_meta($user_id, 'gi_favorites', array_values($favorites));
            
            // アクティビティログ
            gi_log_user_activity('favorite_toggle', array(
                'post_id' => $post_id,
                'action' => $action
            ));
            
            gi_ajax_success_response(array(
                'action' => $action,
                'post_id' => $post_id,
                'total_favorites' => count($favorites)
            ), $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました');
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - お気に入り一覧取得
 */
if (!function_exists('gi_ajax_get_user_favorites')) {
    function gi_ajax_get_user_favorites() {
        try {
            gi_verify_ajax_security();
            
            if (!is_user_logged_in()) {
                throw new Exception('ログインが必要です');
            }
            
            $user_id = get_current_user_id();
            $favorites = get_user_meta($user_id, 'gi_favorites', true);
            
            if (!is_array($favorites) || empty($favorites)) {
                gi_ajax_success_response(array('favorites' => array()));
                return;
            }
            
            $favorite_posts = array();
            foreach ($favorites as $post_id) {
                $post = get_post($post_id);
                if ($post && $post->post_status === 'publish') {
                    $favorite_posts[] = array(
                        'id' => $post_id,
                        'title' => $post->post_title,
                        'permalink' => get_permalink($post_id),
                        'type' => $post->post_type,
                        'date' => get_the_date('Y年n月j日', $post_id)
                    );
                }
            }
            
            gi_ajax_success_response(array('favorites' => $favorite_posts));
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - 関連投稿取得
 */
if (!function_exists('gi_ajax_get_related_posts')) {
    function gi_ajax_get_related_posts() {
        try {
            gi_verify_ajax_security();
            
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 5;
            
            if (!$post_id || !get_post($post_id)) {
                throw new Exception('無効な投稿IDです');
            }
            
            // キャッシュチェック
            $cache_key = 'gi_related_' . $post_id . '_' . $limit;
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                gi_ajax_success_response($cached);
                return;
            }
            
            $post = get_post($post_id);
            $categories = wp_get_post_terms($post_id, 'grant_category', array('fields' => 'ids'));
            
            $args = array(
                'post_type' => $post->post_type,
                'posts_per_page' => $limit,
                'post__not_in' => array($post_id),
                'post_status' => 'publish'
            );
            
            if (!empty($categories)) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'grant_category',
                        'field' => 'term_id',
                        'terms' => $categories
                    )
                );
            }
            
            $related_posts = get_posts($args);
            $results = array();
            
            foreach ($related_posts as $related) {
                $results[] = array(
                    'id' => $related->ID,
                    'title' => $related->post_title,
                    'permalink' => get_permalink($related->ID),
                    'thumbnail' => get_the_post_thumbnail_url($related->ID, 'thumbnail'),
                    'excerpt' => wp_trim_words($related->post_excerpt, 20)
                );
            }
            
            // キャッシュ保存（1時間）
            set_transient($cache_key, $results, HOUR_IN_SECONDS);
            
            gi_ajax_success_response($results);
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * 【改善版】AJAX - 閲覧履歴記録
 */
if (!function_exists('gi_ajax_track_post_view')) {
    function gi_ajax_track_post_view() {
        try {
            // nonceチェックは省略（公開情報のため）
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
            
            if (!$post_id || !get_post($post_id)) {
                throw new Exception('無効な投稿IDです');
            }
            
            // ビューカウントを更新
            $views = get_post_meta($post_id, 'gi_post_views', true);
            $views = $views ? intval($views) : 0;
            update_post_meta($post_id, 'gi_post_views', $views + 1);
            
            // ユーザーの閲覧履歴を記録
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $history = get_user_meta($user_id, 'gi_view_history', true);
                
                if (!is_array($history)) {
                    $history = array();
                }
                
                // 新しい閲覧を追加
                $new_view = array(
                    'post_id' => $post_id,
                    'timestamp' => current_time('timestamp')
                );
                
                // 同じ投稿の古い記録を削除
                $history = array_filter($history, function($item) use ($post_id) {
                    return $item['post_id'] !== $post_id;
                });
                
                // 先頭に追加
                array_unshift($history, $new_view);
                
                // 最大50件まで保持
                $history = array_slice($history, 0, 50);
                
                update_user_meta($user_id, 'gi_view_history', $history);
            }
            
            gi_ajax_success_response(array(
                'post_id' => $post_id,
                'views' => $views + 1
            ));
            
        } catch (Exception $e) {
            gi_ajax_error_response($e->getMessage());
        }
    }
}

/**
 * ユーザーアクティビティログ
 */
if (!function_exists('gi_log_user_activity')) {
    function gi_log_user_activity($action, $data = array()) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $activity_log = get_user_meta($user_id, 'gi_activity_log', true);
        
        if (!is_array($activity_log)) {
            $activity_log = array();
        }
        
        $new_activity = array(
            'action' => $action,
            'data' => $data,
            'timestamp' => current_time('timestamp'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        array_unshift($activity_log, $new_activity);
        $activity_log = array_slice($activity_log, 0, 100); // 最大100件
        
        update_user_meta($user_id, 'gi_activity_log', $activity_log);
    }
}

// AJAXアクションフックの登録（関数が存在する場合のみ）
if (function_exists('gi_ajax_load_grants')) {
    add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
    add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');
}

if (function_exists('gi_ajax_get_search_suggestions')) {
    add_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
    add_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');
}

if (function_exists('gi_ajax_advanced_search')) {
    add_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
    add_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');
}

if (function_exists('gi_ajax_grant_insight_search')) {
    add_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
    add_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');
}

if (function_exists('gi_ajax_toggle_favorite')) {
    add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');
}

if (function_exists('gi_ajax_get_user_favorites')) {
    add_action('wp_ajax_get_user_favorites', 'gi_ajax_get_user_favorites');
}

if (function_exists('gi_ajax_get_related_posts')) {
    add_action('wp_ajax_get_related_posts', 'gi_ajax_get_related_posts');
    add_action('wp_ajax_nopriv_get_related_posts', 'gi_ajax_get_related_posts');
}

if (function_exists('gi_ajax_track_post_view')) {
    add_action('wp_ajax_track_post_view', 'gi_ajax_track_post_view');
    add_action('wp_ajax_nopriv_track_post_view', 'gi_ajax_track_post_view');
}