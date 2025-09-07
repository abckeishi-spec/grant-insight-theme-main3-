<?php
/**
 * Grant Insight - AI診断機能（重複対策済み）
 * 
 * タスク3: AI診断機能の実装
 * すべての関数を条件付き定義に変更
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

/**
 * データベーステーブルの作成
 */
if (!function_exists('gi_create_diagnosis_tables')) {
    function gi_create_diagnosis_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'gi_diagnosis_history';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            answers longtext NOT NULL,
            results longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// 初期化時にテーブル作成
add_action('after_switch_theme', 'gi_create_diagnosis_tables');

/**
 * 診断質問の定義
 */
if (!function_exists('gi_get_diagnosis_questions')) {
    function gi_get_diagnosis_questions() {
        return array(
            'business_type' => array(
                'question' => '事業形態を選択してください',
                'type' => 'radio',
                'options' => array(
                    'corporation' => '法人',
                    'individual' => '個人事業主',
                    'startup' => '起業予定',
                    'npo' => 'NPO・団体'
                ),
                'weight' => 2.0,
                'required' => true
            ),
            'industry' => array(
                'question' => '業種を選択してください',
                'type' => 'select',
                'options' => array(
                    'manufacturing' => '製造業',
                    'retail' => '小売業',
                    'service' => 'サービス業',
                    'it' => 'IT・情報通信業',
                    'construction' => '建設業',
                    'agriculture' => '農林水産業',
                    'medical' => '医療・福祉',
                    'education' => '教育',
                    'other' => 'その他'
                ),
                'weight' => 1.5,
                'required' => true
            ),
            'employees' => array(
                'question' => '従業員数を選択してください',
                'type' => 'radio',
                'options' => array(
                    'under_5' => '5人以下',
                    '6_20' => '6〜20人',
                    '21_50' => '21〜50人',
                    '51_100' => '51〜100人',
                    'over_100' => '100人以上'
                ),
                'weight' => 1.0,
                'required' => true
            ),
            'purpose' => array(
                'question' => '助成金の利用目的を選択してください（複数選択可）',
                'type' => 'checkbox',
                'options' => array(
                    'equipment' => '設備投資',
                    'hiring' => '人材採用',
                    'training' => '人材育成',
                    'rd' => '研究開発',
                    'marketing' => '販路開拓',
                    'digitalization' => 'デジタル化',
                    'environment' => '環境対策',
                    'workstyle' => '働き方改革'
                ),
                'weight' => 1.8,
                'required' => true
            ),
            'amount' => array(
                'question' => '希望する助成金額を選択してください',
                'type' => 'radio',
                'options' => array(
                    'under_100' => '100万円以下',
                    '100_500' => '100〜500万円',
                    '500_1000' => '500〜1000万円',
                    'over_1000' => '1000万円以上'
                ),
                'weight' => 1.2,
                'required' => false
            ),
            'prefecture' => array(
                'question' => '事業所の都道府県を選択してください',
                'type' => 'select',
                'options' => gi_get_prefecture_list(),
                'weight' => 1.3,
                'required' => true
            ),
            'urgency' => array(
                'question' => '申請時期の緊急度',
                'type' => 'radio',
                'options' => array(
                    'immediate' => '今すぐ申請したい',
                    'within_3months' => '3ヶ月以内',
                    'within_6months' => '6ヶ月以内',
                    'anytime' => 'いつでも良い'
                ),
                'weight' => 0.8,
                'required' => false
            ),
            'experience' => array(
                'question' => '助成金申請の経験',
                'type' => 'radio',
                'options' => array(
                    'none' => '初めて',
                    'once' => '1回ある',
                    'multiple' => '複数回ある',
                    'expert' => '頻繁に申請している'
                ),
                'weight' => 0.5,
                'required' => false
            ),
            'budget_year' => array(
                'question' => '年間売上高',
                'type' => 'radio',
                'options' => array(
                    'under_10m' => '1000万円以下',
                    '10m_50m' => '1000万〜5000万円',
                    '50m_100m' => '5000万〜1億円',
                    'over_100m' => '1億円以上'
                ),
                'weight' => 0.7,
                'required' => false
            ),
            'challenges' => array(
                'question' => '現在の経営課題（複数選択可）',
                'type' => 'checkbox',
                'options' => array(
                    'sales' => '売上拡大',
                    'cost' => 'コスト削減',
                    'hr' => '人材不足',
                    'succession' => '事業承継',
                    'innovation' => 'イノベーション',
                    'global' => '海外展開'
                ),
                'weight' => 1.0,
                'required' => false
            )
        );
    }
}

/**
 * AJAX API エンドポイント
 */
if (!function_exists('gi_ai_diagnosis_api')) {
    function gi_ai_diagnosis_api() {
        // セキュリティチェック
        if (!check_ajax_referer('gi_ai_diagnosis_nonce', 'nonce', false)) {
            wp_send_json_error('セキュリティチェックに失敗しました');
            return;
        }
        
        try {
            // 回答データの取得
            $answers = json_decode(stripslashes($_POST['answers'] ?? '{}'), true);
            
            if (empty($answers)) {
                throw new Exception('回答データが不足しています');
            }
            
            // 診断実行
            $matched_grants = gi_match_grants_by_answers($answers);
            
            // 結果の整形
            $results = array(
                'grants' => array_slice($matched_grants, 0, 10), // 上位10件
                'total_matches' => count($matched_grants),
                'recommendations' => gi_get_recommendations($answers),
                'confidence_score' => gi_calculate_confidence_score($matched_grants, $answers),
                'session_id' => gi_get_or_create_session_id()
            );
            
            // 履歴保存
            gi_save_diagnosis_history($answers, $results);
            
            // 成功レスポンス
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            gi_log_diagnosis_error(__FUNCTION__, $e->getMessage(), array(
                'answers' => $answers ?? null
            ));
            
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'fallback_grants' => gi_get_fallback_grants()
            ));
        }
    }
}

// AJAXハンドラーの登録
add_action('wp_ajax_gi_ai_diagnosis', 'gi_ai_diagnosis_api');
add_action('wp_ajax_nopriv_gi_ai_diagnosis', 'gi_ai_diagnosis_api');

/**
 * 回答に基づく助成金マッチング
 */
if (!function_exists('gi_match_grants_by_answers')) {
    function gi_match_grants_by_answers($answers) {
        global $wpdb;
        
        // 基本クエリ
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'meta_query' => array('relation' => 'AND'),
            'tax_query' => array('relation' => 'OR')
        );
        
        // 都道府県フィルター
        if (!empty($answers['prefecture'])) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => 'municipality',
                    'value' => $answers['prefecture'],
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'municipality',
                    'value' => '全国',
                    'compare' => '='
                )
            );
        }
        
        // 金額フィルター
        if (!empty($answers['amount'])) {
            $amount_ranges = array(
                'under_100' => array(0, 100),
                '100_500' => array(100, 500),
                '500_1000' => array(500, 1000),
                'over_1000' => array(1000, PHP_INT_MAX)
            );
            
            if (isset($amount_ranges[$answers['amount']])) {
                $range = $amount_ranges[$answers['amount']];
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
        
        // 締切フィルター（緊急度に応じて）
        if (!empty($answers['urgency'])) {
            $deadline_days = array(
                'immediate' => 30,
                'within_3months' => 90,
                'within_6months' => 180,
                'anytime' => 365
            );
            
            if (isset($deadline_days[$answers['urgency']])) {
                $deadline_date = date('Y-m-d', strtotime('+' . $deadline_days[$answers['urgency']] . ' days'));
                $args['meta_query'][] = array(
                    'key' => 'deadline',
                    'value' => $deadline_date,
                    'compare' => '<=',
                    'type' => 'DATE'
                );
            }
        }
        
        // クエリ実行
        $query = new WP_Query($args);
        $grants = array();
        
        if ($query->have_posts()) {
            $questions = gi_get_diagnosis_questions();
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // スコア計算
                $score = gi_calculate_match_score($post_id, $answers, $questions);
                
                $grants[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'score' => $score,
                    'data' => gi_get_grant_data_for_diagnosis($post_id),
                    'match_reasons' => gi_generate_match_reasons(array($post_id), $answers)
                );
            }
            wp_reset_postdata();
        }
        
        // スコアでソート
        usort($grants, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $grants;
    }
}

/**
 * 診断結果の理由生成
 */
if (!function_exists('gi_generate_match_reasons')) {
    function gi_generate_match_reasons($grant_ids, $answers) {
        $reasons = array();
        
        // 業種マッチ
        if (!empty($answers['industry'])) {
            $reasons[] = sprintf('業種「%s」に適合', $answers['industry']);
        }
        
        // 規模マッチ
        if (!empty($answers['employees'])) {
            $reasons[] = sprintf('従業員数「%s」の条件に合致', $answers['employees']);
        }
        
        // 目的マッチ
        if (!empty($answers['purpose']) && is_array($answers['purpose'])) {
            $purposes = implode('・', $answers['purpose']);
            $reasons[] = sprintf('目的「%s」に対応', $purposes);
        }
        
        // 地域マッチ
        if (!empty($answers['prefecture'])) {
            $reasons[] = sprintf('%sでの申請が可能', $answers['prefecture']);
        }
        
        return $reasons;
    }
}

/**
 * マッチスコア計算
 */
if (!function_exists('gi_calculate_match_score')) {
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
}

/**
 * 助成金データ取得
 */
if (!function_exists('gi_get_grant_data_for_diagnosis')) {
    function gi_get_grant_data_for_diagnosis($post_id) {
        return array(
            'amount_min' => get_post_meta($post_id, 'amount_min', true),
            'amount_max' => get_post_meta($post_id, 'amount_max', true),
            'deadline' => get_post_meta($post_id, 'deadline', true),
            'municipality' => get_post_meta($post_id, 'municipality', true),
            'target_industry' => get_post_meta($post_id, 'target_industry', true),
            'target_number' => get_post_meta($post_id, 'target_number', true),
            'categories' => wp_get_post_terms($post_id, 'grant_category', array('fields' => 'names'))
        );
    }
}

/**
 * 信頼度スコア計算
 */
if (!function_exists('gi_calculate_confidence_score')) {
    function gi_calculate_confidence_score($grants, $answers) {
        if (empty($grants)) {
            return 0;
        }
        
        // 上位の助成金のスコア平均
        $top_grants = array_slice($grants, 0, 5);
        $total_score = 0;
        
        foreach ($top_grants as $grant) {
            $total_score += $grant['score'] ?? 0;
        }
        
        $avg_score = $total_score / count($top_grants);
        
        // 回答の完全性
        $required_answered = 0;
        $required_total = 0;
        $questions = gi_get_diagnosis_questions();
        
        foreach ($questions as $key => $question) {
            if ($question['required']) {
                $required_total++;
                if (!empty($answers[$key])) {
                    $required_answered++;
                }
            }
        }
        
        $completeness = $required_total > 0 ? ($required_answered / $required_total) * 100 : 0;
        
        // 総合信頼度
        return min(100, ($avg_score + $completeness) / 2);
    }
}

/**
 * 推奨事項の生成
 */
if (!function_exists('gi_get_recommendations')) {
    function gi_get_recommendations($answers) {
        $recommendations = array();
        
        // 初心者向けアドバイス
        if (isset($answers['experience']) && $answers['experience'] === 'none') {
            $recommendations[] = array(
                'type' => 'beginner',
                'title' => '初めての申請の方へ',
                'content' => '専門家への相談をお勧めします。申請書類の準備や要件確認など、サポートを受けることで成功率が上がります。'
            );
        }
        
        // 緊急度に応じたアドバイス
        if (isset($answers['urgency']) && $answers['urgency'] === 'immediate') {
            $recommendations[] = array(
                'type' => 'urgent',
                'title' => '早めの準備が必要です',
                'content' => '締切が近い助成金があります。必要書類の準備を今すぐ始めましょう。'
            );
        }
        
        // 業種別アドバイス
        if (isset($answers['industry'])) {
            $industry_tips = array(
                'manufacturing' => '設備投資系の助成金が充実しています',
                'it' => 'DX推進やIT導入支援の助成金をチェックしましょう',
                'retail' => '販路開拓や店舗改装の助成金があります',
                'service' => 'サービス品質向上や人材育成の助成金を検討しましょう'
            );
            
            if (isset($industry_tips[$answers['industry']])) {
                $recommendations[] = array(
                    'type' => 'industry',
                    'title' => '業種別のアドバイス',
                    'content' => $industry_tips[$answers['industry']]
                );
            }
        }
        
        return $recommendations;
    }
}

/**
 * 診断履歴の保存
 */
if (!function_exists('gi_save_diagnosis_history')) {
    function gi_save_diagnosis_history($answers, $results) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_diagnosis_history';
        
        $data = array(
            'session_id' => gi_get_or_create_session_id(),
            'user_id' => get_current_user_id() ?: null,
            'answers' => json_encode($answers),
            'results' => json_encode($results),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
}

/**
 * セッションID取得・作成
 */
if (!function_exists('gi_get_or_create_session_id')) {
    function gi_get_or_create_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['gi_diagnosis_session'])) {
            $_SESSION['gi_diagnosis_session'] = uniqid('gi_', true);
        }
        
        return $_SESSION['gi_diagnosis_session'];
    }
}

/**
 * フォールバック用助成金取得
 */
if (!function_exists('gi_get_fallback_grants')) {
    function gi_get_fallback_grants() {
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'meta_key' => 'is_featured',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $query = new WP_Query($args);
        $grants = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'permalink' => get_permalink()
                );
            }
            wp_reset_postdata();
        }
        
        return $grants;
    }
}

/**
 * エラーログ記録
 */
if (!function_exists('gi_log_diagnosis_error')) {
    function gi_log_diagnosis_error($function_name, $error_message, $context = array()) {
        if (WP_DEBUG_LOG) {
            error_log(sprintf(
                '[AI Diagnosis Error] %s in %s: %s | Context: %s',
                current_time('mysql'),
                $function_name,
                $error_message,
                json_encode($context)
            ));
        }
    }
}

/**
 * 診断履歴取得API
 */
if (!function_exists('gi_ajax_get_diagnosis_history')) {
    function gi_ajax_get_diagnosis_history() {
        // セキュリティチェック
        if (!check_ajax_referer('gi_ai_diagnosis_nonce', 'nonce', false)) {
            wp_send_json_error('セキュリティチェックに失敗しました');
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gi_diagnosis_history';
        $user_id = get_current_user_id();
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
            $user_id
        ));
        
        $history = array();
        foreach ($results as $row) {
            $history[] = array(
                'id' => $row->id,
                'answers' => json_decode($row->answers, true),
                'results' => json_decode($row->results, true),
                'created_at' => $row->created_at
            );
        }
        
        wp_send_json_success($history);
    }
}

// 診断履歴取得のAJAXハンドラー登録
add_action('wp_ajax_gi_get_diagnosis_history', 'gi_ajax_get_diagnosis_history');

/**
 * 都道府県リストの取得
 */
if (!function_exists('gi_get_prefecture_list')) {
    function gi_get_prefecture_list() {
        return array(
            '北海道' => '北海道',
            '青森県' => '青森県',
            '岩手県' => '岩手県',
            '宮城県' => '宮城県',
            '秋田県' => '秋田県',
            '山形県' => '山形県',
            '福島県' => '福島県',
            '茨城県' => '茨城県',
            '栃木県' => '栃木県',
            '群馬県' => '群馬県',
            '埼玉県' => '埼玉県',
            '千葉県' => '千葉県',
            '東京都' => '東京都',
            '神奈川県' => '神奈川県',
            '新潟県' => '新潟県',
            '富山県' => '富山県',
            '石川県' => '石川県',
            '福井県' => '福井県',
            '山梨県' => '山梨県',
            '長野県' => '長野県',
            '岐阜県' => '岐阜県',
            '静岡県' => '静岡県',
            '愛知県' => '愛知県',
            '三重県' => '三重県',
            '滋賀県' => '滋賀県',
            '京都府' => '京都府',
            '大阪府' => '大阪府',
            '兵庫県' => '兵庫県',
            '奈良県' => '奈良県',
            '和歌山県' => '和歌山県',
            '鳥取県' => '鳥取県',
            '島根県' => '島根県',
            '岡山県' => '岡山県',
            '広島県' => '広島県',
            '山口県' => '山口県',
            '徳島県' => '徳島県',
            '香川県' => '香川県',
            '愛媛県' => '愛媛県',
            '高知県' => '高知県',
            '福岡県' => '福岡県',
            '佐賀県' => '佐賀県',
            '長崎県' => '長崎県',
            '熊本県' => '熊本県',
            '大分県' => '大分県',
            '宮崎県' => '宮崎県',
            '鹿児島県' => '鹿児島県',
            '沖縄県' => '沖縄県'
        );
    }
}