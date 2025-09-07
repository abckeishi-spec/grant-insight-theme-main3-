<?php
/**
 * AI診断機能バックエンド
 * 
 * 助成金のAI診断機能のバックエンド処理を提供します。
 * 診断回答に基づいた助成金マッチング、履歴保存、エラーハンドリングを含みます。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI診断テーブル作成
 * プラグイン有効化時に実行
 */
function gi_create_diagnosis_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'gi_diagnosis_history';
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        session_id varchar(255) DEFAULT NULL,
        answers longtext NOT NULL,
        results longtext NOT NULL,
        confidence_score float DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY session_id (session_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // バージョン保存
    update_option('gi_diagnosis_db_version', '1.0');
}

/**
 * 診断質問定義
 */
function gi_get_diagnosis_questions() {
    return array(
        'business_type' => array(
            'question' => '事業形態を選択してください',
            'type' => 'single',
            'required' => true,
            'options' => array(
                'corporation' => '法人（株式会社・有限会社等）',
                'sole_proprietor' => '個人事業主',
                'npo' => 'NPO法人',
                'association' => '組合・団体',
                'startup' => 'スタートアップ・創業予定'
            ),
            'weight' => 1.5
        ),
        'industry' => array(
            'question' => '業種を選択してください',
            'type' => 'single',
            'required' => true,
            'options' => array(
                'it' => 'IT・デジタル',
                'manufacturing' => 'ものづくり・製造業',
                'retail' => '小売・サービス業',
                'agriculture' => '農林水産業',
                'medical' => '医療・福祉',
                'education' => '教育・研究',
                'construction' => '建設・不動産',
                'other' => 'その他'
            ),
            'weight' => 1.3
        ),
        'purpose' => array(
            'question' => '助成金の使用目的は？（複数選択可）',
            'type' => 'multiple',
            'required' => true,
            'options' => array(
                'equipment' => '設備投資・機械購入',
                'hr' => '人材採用・育成',
                'rd' => '研究開発・技術開発',
                'marketing' => '販路拡大・マーケティング',
                'digitalization' => 'デジタル化・IT導入',
                'eco' => '環境対策・省エネ',
                'startup_fund' => '創業・起業資金',
                'working_capital' => '運転資金'
            ),
            'weight' => 1.2
        ),
        'employees' => array(
            'question' => '従業員数は？',
            'type' => 'single',
            'required' => true,
            'options' => array(
                '0' => '0人（本人のみ）',
                '1-5' => '1～5人',
                '6-20' => '6～20人',
                '21-50' => '21～50人',
                '51-100' => '51～100人',
                '101-300' => '101～300人',
                '301+' => '301人以上'
            ),
            'weight' => 1.0
        ),
        'location' => array(
            'question' => '事業所の所在地は？',
            'type' => 'prefecture',
            'required' => true,
            'weight' => 1.1
        ),
        'budget' => array(
            'question' => '希望する助成金額は？',
            'type' => 'single',
            'required' => false,
            'options' => array(
                '0-100' => '～100万円',
                '100-500' => '100～500万円',
                '500-1000' => '500～1000万円',
                '1000-3000' => '1000～3000万円',
                '3000+' => '3000万円以上'
            ),
            'weight' => 0.8
        ),
        'urgency' => array(
            'question' => '申請時期の希望は？',
            'type' => 'single',
            'required' => false,
            'options' => array(
                'immediate' => 'すぐに申請したい',
                '1-3months' => '1～3ヶ月以内',
                '3-6months' => '3～6ヶ月以内',
                '6months+' => '6ヶ月以上先でも可'
            ),
            'weight' => 0.6
        )
    );
}

/**
 * AI診断API実装
 */
function gi_ai_diagnosis_api() {
    try {
        // セキュリティチェック
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gi_ai_diagnosis_nonce')) {
            wp_send_json_error(
                array('message' => 'セキュリティチェックに失敗しました。'),
                403
            );
        }
        
        // 回答データの取得とサニタイズ
        $answers = isset($_POST['answers']) ? gi_sanitize_recursive(json_decode(stripslashes($_POST['answers']), true)) : array();
        
        if (empty($answers)) {
            throw new Exception('診断回答が入力されていません。');
        }
        
        // 必須項目のチェック
        $questions = gi_get_diagnosis_questions();
        foreach ($questions as $key => $question) {
            if ($question['required'] && empty($answers[$key])) {
                throw new Exception($question['question'] . 'は必須項目です。');
            }
        }
        
        // マッチング処理
        $matched_grants = gi_match_grants_by_answers($answers);
        
        // 結果の整形
        $results = array(
            'matched_grants' => $matched_grants['grants'],
            'match_reasons' => $matched_grants['reasons'],
            'confidence_score' => $matched_grants['confidence'],
            'recommendations' => gi_get_recommendations($answers),
            'diagnosis_id' => null
        );
        
        // 履歴保存
        $diagnosis_id = gi_save_diagnosis_history($answers, $results);
        $results['diagnosis_id'] = $diagnosis_id;
        
        wp_send_json_success($results);
        
    } catch (Exception $e) {
        gi_log_diagnosis_error(__FUNCTION__, $e->getMessage(), $_POST);
        
        // エラー時のフォールバック
        $fallback_grants = gi_get_fallback_grants();
        
        wp_send_json_error(
            array(
                'message' => $e->getMessage(),
                'fallback_grants' => $fallback_grants,
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ),
            500
        );
    }
}
add_action('wp_ajax_gi_ai_diagnosis', 'gi_ai_diagnosis_api');
add_action('wp_ajax_nopriv_gi_ai_diagnosis', 'gi_ai_diagnosis_api');

/**
 * 回答に基づく助成金マッチング
 */
function gi_match_grants_by_answers($answers) {
    $matched_grants = array();
    $match_scores = array();
    $questions = gi_get_diagnosis_questions();
    
    // クエリ構築
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'meta_query' => array('relation' => 'AND'),
        'tax_query' => array('relation' => 'AND')
    );
    
    // 業種でフィルタリング
    if (!empty($answers['industry'])) {
        $industry_mapping = array(
            'it' => 'it-digital',
            'manufacturing' => 'manufacturing',
            'retail' => 'retail-service',
            'agriculture' => 'agriculture',
            'medical' => 'medical-welfare',
            'education' => 'education',
            'construction' => 'construction',
        );
        
        if (isset($industry_mapping[$answers['industry']])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $industry_mapping[$answers['industry']]
            );
        }
    }
    
    // 地域でフィルタリング
    if (!empty($answers['location'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => sanitize_title($answers['location'])
        );
    }
    
    // 予算でフィルタリング
    if (!empty($answers['budget'])) {
        $budget_ranges = array(
            '0-100' => array('max' => 1000000),
            '100-500' => array('min' => 1000000, 'max' => 5000000),
            '500-1000' => array('min' => 5000000, 'max' => 10000000),
            '1000-3000' => array('min' => 10000000, 'max' => 30000000),
            '3000+' => array('min' => 30000000)
        );
        
        if (isset($budget_ranges[$answers['budget']])) {
            $range = $budget_ranges[$answers['budget']];
            if (isset($range['min']) && isset($range['max'])) {
                $args['meta_query'][] = array(
                    'key' => 'max_amount_numeric',
                    'value' => array($range['min'], $range['max']),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
            } elseif (isset($range['min'])) {
                $args['meta_query'][] = array(
                    'key' => 'max_amount_numeric',
                    'value' => $range['min'],
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                );
            } elseif (isset($range['max'])) {
                $args['meta_query'][] = array(
                    'key' => 'max_amount_numeric',
                    'value' => $range['max'],
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                );
            }
        }
    }
    
    // クエリ実行
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // スコア計算
            $score = gi_calculate_match_score($post_id, $answers, $questions);
            $match_scores[$post_id] = $score;
            
            // 助成金データ取得
            $grant_data = gi_get_grant_data_for_diagnosis($post_id);
            $grant_data['match_score'] = $score;
            $matched_grants[] = $grant_data;
        }
        wp_reset_postdata();
    }
    
    // スコアでソート
    usort($matched_grants, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
    
    // 上位10件を返す
    $matched_grants = array_slice($matched_grants, 0, 10);
    
    // マッチング理由生成
    $reasons = gi_generate_match_reasons($matched_grants, $answers);
    
    // 信頼度スコア計算
    $confidence = gi_calculate_confidence_score($matched_grants, $answers);
    
    return array(
        'grants' => $matched_grants,
        'reasons' => $reasons,
        'confidence' => $confidence
    );
}

/**
 * マッチスコア計算
 */
function gi_calculate_match_score($post_id, $answers, $questions) {
    $score = 0;
    $max_score = 0;
    
    foreach ($answers as $key => $answer) {
        if (!isset($questions[$key])) continue;
        
        $weight = $questions[$key]['weight'] ?? 1.0;
        $max_score += $weight;
        
        // 各回答に基づいてスコアを加算
        // ここでは簡単な実装例
        $score += $weight * 0.5; // 基本スコア
        
        // 特定の条件でボーナススコア
        if ($key === 'purpose') {
            $grant_categories = wp_get_post_terms($post_id, 'grant_category', array('fields' => 'slugs'));
            if (is_array($answer)) {
                foreach ($answer as $purpose) {
                    if (in_array($purpose, $grant_categories)) {
                        $score += $weight * 0.3;
                    }
                }
            }
        }
    }
    
    return $max_score > 0 ? ($score / $max_score) * 100 : 0;
}

/**
 * 診断用助成金データ取得
 */
function gi_get_grant_data_for_diagnosis($post_id) {
    $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
    $category_terms = get_the_terms($post_id, 'grant_category');
    
    return array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'excerpt' => wp_trim_words(get_the_excerpt($post_id), 30),
        'permalink' => get_permalink($post_id),
        'amount' => gi_format_amount_man(
            gi_safe_get_meta($post_id, 'max_amount_numeric', 0),
            gi_safe_get_meta($post_id, 'max_amount', '')
        ),
        'organization' => gi_safe_get_meta($post_id, 'organization', ''),
        'deadline' => gi_get_formatted_deadline($post_id),
        'prefecture' => $prefecture_terms ? $prefecture_terms[0]->name : '',
        'category' => $category_terms ? $category_terms[0]->name : '',
        'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open'))
    );
}

/**
 * マッチング理由生成
 */
function gi_generate_match_reasons($grants, $answers) {
    $reasons = array();
    
    foreach ($grants as $grant) {
        $grant_reasons = array();
        
        // 業種マッチ
        if (!empty($answers['industry']) && !empty($grant['category'])) {
            $grant_reasons[] = '業種が一致しています';
        }
        
        // 地域マッチ
        if (!empty($answers['location']) && !empty($grant['prefecture'])) {
            $grant_reasons[] = '地域の助成金です';
        }
        
        // 予算マッチ
        if (!empty($answers['budget'])) {
            $grant_reasons[] = '予算範囲に適合しています';
        }
        
        // スコアベースの理由
        if ($grant['match_score'] > 80) {
            $grant_reasons[] = '非常に高い適合度です';
        } elseif ($grant['match_score'] > 60) {
            $grant_reasons[] = '高い適合度です';
        }
        
        $reasons[$grant['id']] = $grant_reasons;
    }
    
    return $reasons;
}

/**
 * 信頼度スコア計算
 */
function gi_calculate_confidence_score($grants, $answers) {
    if (empty($grants)) {
        return 0;
    }
    
    // 平均マッチスコア
    $total_score = array_sum(array_column($grants, 'match_score'));
    $avg_score = $total_score / count($grants);
    
    // 回答の完全性
    $questions = gi_get_diagnosis_questions();
    $answered = count(array_filter($answers));
    $total_questions = count($questions);
    $completeness = ($answered / $total_questions) * 100;
    
    // 総合信頼度
    $confidence = ($avg_score * 0.7) + ($completeness * 0.3);
    
    return min(100, max(0, $confidence));
}

/**
 * 推奨事項生成
 */
function gi_get_recommendations($answers) {
    $recommendations = array();
    
    // 事業形態に基づく推奨
    if (!empty($answers['business_type'])) {
        switch ($answers['business_type']) {
            case 'startup':
                $recommendations[] = '創業支援の助成金を優先的に検討することをお勧めします。';
                break;
            case 'sole_proprietor':
                $recommendations[] = '個人事業主向けの小規模事業者支援制度もご確認ください。';
                break;
        }
    }
    
    // 目的に基づく推奨
    if (!empty($answers['purpose']) && is_array($answers['purpose'])) {
        if (in_array('digitalization', $answers['purpose'])) {
            $recommendations[] = 'IT導入補助金やDX推進関連の助成金が特に適している可能性があります。';
        }
        if (in_array('hr', $answers['purpose'])) {
            $recommendations[] = '雇用関連の助成金（キャリアアップ助成金等）も併せてご検討ください。';
        }
    }
    
    // 緊急度に基づく推奨
    if (!empty($answers['urgency'])) {
        if ($answers['urgency'] === 'immediate') {
            $recommendations[] = '締切が近い助成金から優先的に検討し、早めの準備をお勧めします。';
        }
    }
    
    return $recommendations;
}

/**
 * 診断履歴保存
 */
function gi_save_diagnosis_history($answers, $results) {
    global $wpdb;
    
    try {
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $session_id = !$user_id ? gi_get_or_create_session_id() : null;
        
        $table_name = $wpdb->prefix . 'gi_diagnosis_history';
        
        $data = array(
            'user_id' => $user_id,
            'session_id' => $session_id,
            'answers' => json_encode($answers, JSON_UNESCAPED_UNICODE),
            'results' => json_encode($results, JSON_UNESCAPED_UNICODE),
            'confidence_score' => $results['confidence_score'] ?? 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_save_diagnosis_history error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * セッションID取得・作成
 */
function gi_get_or_create_session_id() {
    if (!session_id()) {
        session_start();
    }
    
    if (!isset($_SESSION['gi_diagnosis_session_id'])) {
        $_SESSION['gi_diagnosis_session_id'] = wp_generate_uuid4();
    }
    
    return $_SESSION['gi_diagnosis_session_id'];
}

/**
 * フォールバック助成金取得
 */
function gi_get_fallback_grants() {
    // 人気の助成金を取得
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'meta_key' => 'views_count',
        'orderby' => 'meta_value_num',
        'order' => 'DESC'
    );
    
    $query = new WP_Query($args);
    $grants = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $grants[] = gi_get_grant_data_for_diagnosis(get_the_ID());
        }
        wp_reset_postdata();
    }
    
    // ビューカウントがない場合は最新の助成金
    if (empty($grants)) {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        unset($args['meta_key']);
        
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = gi_get_grant_data_for_diagnosis(get_the_ID());
            }
            wp_reset_postdata();
        }
    }
    
    return $grants;
}

/**
 * エラーログ記録
 */
function gi_log_diagnosis_error($function_name, $error_message, $context = array()) {
    if (WP_DEBUG_LOG) {
        error_log(sprintf(
            '[Grant Insight AI Diagnosis Error] %s in %s: %s | Context: %s',
            current_time('mysql'),
            $function_name,
            $error_message,
            json_encode($context)
        ));
    }
}

/**
 * 診断履歴取得API
 */
function gi_ajax_get_diagnosis_history() {
    try {
        // セキュリティチェック
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gi_ai_diagnosis_nonce')) {
            wp_send_json_error('セキュリティチェックに失敗しました。', 403);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gi_diagnosis_history';
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $session_id = !$user_id ? gi_get_or_create_session_id() : null;
        
        $where = $user_id ? "user_id = %d" : "session_id = %s";
        $where_value = $user_id ?: $session_id;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT 10",
                $where_value
            )
        );
        
        $history = array();
        foreach ($results as $row) {
            $history[] = array(
                'id' => $row->id,
                'answers' => json_decode($row->answers, true),
                'results' => json_decode($row->results, true),
                'confidence_score' => $row->confidence_score,
                'created_at' => $row->created_at
            );
        }
        
        wp_send_json_success($history);
        
    } catch (Exception $e) {
        gi_log_diagnosis_error(__FUNCTION__, $e->getMessage());
        wp_send_json_error('履歴の取得中にエラーが発生しました。', 500);
    }
}
add_action('wp_ajax_get_diagnosis_history', 'gi_ajax_get_diagnosis_history');
add_action('wp_ajax_nopriv_get_diagnosis_history', 'gi_ajax_get_diagnosis_history');

// テーマ有効化時にテーブル作成
add_action('after_switch_theme', 'gi_create_diagnosis_tables');