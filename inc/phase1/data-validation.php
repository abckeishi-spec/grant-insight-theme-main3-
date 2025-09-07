<?php
/**
 * データ型統一とバリデーション機能
 * 
 * 金額・日付フィールドの統一とACFフィールドのバリデーションを提供します。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 金額フィールドの統一処理
 * max_amount (テキスト) と max_amount_numeric (数値) の整合性確保
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_unify_amount_fields($post_id) {
    // 自動保存時はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 投稿タイプチェック
    $post_type = get_post_type($post_id);
    if ($post_type !== 'grant') {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // max_amount フィールドの取得
    $amount_text = get_field('max_amount', $post_id);
    if (!$amount_text) {
        $amount_text = get_post_meta($post_id, 'max_amount', true);
    }
    
    if ($amount_text) {
        // 数値のみを抽出（カンマ、円、万円などを除去）
        $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
        
        // 「万円」が含まれている場合は10000倍する
        if (strpos($amount_text, '万') !== false) {
            $amount_numeric = intval($amount_numeric) * 10000;
        }
        
        // バリデーション: 0以上の数値
        $amount_numeric = max(0, intval($amount_numeric));
        
        // max_amount_numeric フィールドを更新
        update_post_meta($post_id, 'max_amount_numeric', $amount_numeric);
        
        // max_amount フィールドを正規化（例: "1000万円"）
        if ($amount_numeric >= 10000) {
            $formatted_amount = number_format($amount_numeric / 10000) . '万円';
            update_post_meta($post_id, 'max_amount', $formatted_amount);
            if (function_exists('update_field')) {
                update_field('max_amount', $formatted_amount, $post_id);
            }
        }
        
        // ログ記録
        if (WP_DEBUG_LOG) {
            error_log(sprintf(
                'Amount unified for post %d: text=%s, numeric=%d',
                $post_id,
                $amount_text,
                $amount_numeric
            ));
        }
    } else {
        // 金額が未設定の場合は0をセット
        update_post_meta($post_id, 'max_amount_numeric', 0);
    }
}

/**
 * 日付フィールドの統一処理
 * deadline_date の Ymd 形式統一
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_unify_date_fields($post_id) {
    // 自動保存時はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 投稿タイプチェック
    $post_type = get_post_type($post_id);
    if ($post_type !== 'grant') {
        return;
    }
    
    // deadline フィールドの取得
    $deadline = get_field('deadline', $post_id);
    if (!$deadline) {
        $deadline = get_post_meta($post_id, 'deadline', true);
    }
    
    if ($deadline) {
        $deadline_ymd = 0;
        
        // 様々な形式の日付を処理
        if (is_numeric($deadline)) {
            // UNIXタイムスタンプの場合
            if ($deadline > 19000101 && $deadline < 30000101) {
                // すでにYmd形式の場合
                $deadline_ymd = intval($deadline);
            } else {
                // タイムスタンプをYmd形式に変換
                $deadline_ymd = intval(date('Ymd', intval($deadline)));
            }
        } else {
            // 文字列の場合
            $timestamp = strtotime($deadline);
            if ($timestamp !== false) {
                $deadline_ymd = intval(date('Ymd', $timestamp));
            }
        }
        
        // deadline_date フィールドを更新
        if ($deadline_ymd > 0) {
            update_post_meta($post_id, 'deadline_date', $deadline_ymd);
            
            // 期限切れ判定
            $today_ymd = intval(date('Ymd'));
            if ($deadline_ymd < $today_ymd) {
                // 期限切れの場合、ステータスを自動更新
                update_post_meta($post_id, 'application_status', 'closed');
                if (function_exists('update_field')) {
                    update_field('application_status', 'closed', $post_id);
                }
                
                // 期限切れフラグを設定
                update_post_meta($post_id, 'is_expired', true);
            } else {
                // 期限内の場合
                delete_post_meta($post_id, 'is_expired');
            }
        }
    }
}

/**
 * ACFフィールド保存時のバリデーション
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_validate_acf_fields($post_id) {
    // 自動保存時はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 投稿タイプチェック
    $post_type = get_post_type($post_id);
    if (!in_array($post_type, array('grant', 'tool', 'case_study'), true)) {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 金額フィールドの統一
    if ($post_type === 'grant') {
        gi_unify_amount_fields($post_id);
        gi_unify_date_fields($post_id);
        
        // 必須フィールドのチェック
        $required_fields = array(
            'organization' => '実施機関',
            'max_amount' => '最大助成金額',
            'application_status' => '申請状況'
        );
        
        $errors = array();
        
        foreach ($required_fields as $field_key => $field_label) {
            $value = get_field($field_key, $post_id);
            if (empty($value)) {
                $errors[] = sprintf('%s が入力されていません。', $field_label);
            }
        }
        
        // エラーがある場合は管理者に通知
        if (!empty($errors) && is_admin()) {
            set_transient('gi_acf_validation_errors_' . $post_id, $errors, 60);
        }
    }
    
    // URLフィールドの検証
    $url_fields = array('official_url', 'application_url', 'website_url');
    foreach ($url_fields as $field_key) {
        $url = get_field($field_key, $post_id);
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            // 無効なURLの場合、http://を追加して再検証
            $url_with_protocol = 'https://' . $url;
            if (filter_var($url_with_protocol, FILTER_VALIDATE_URL)) {
                update_field($field_key, $url_with_protocol, $post_id);
            } else {
                // それでも無効な場合はクリア
                update_field($field_key, '', $post_id);
            }
        }
    }
    
    // メールアドレスフィールドの検証
    $email_fields = array('contact_email', 'support_email');
    foreach ($email_fields as $field_key) {
        $email = get_field($field_key, $post_id);
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // 無効なメールアドレスの場合はクリア
            update_field($field_key, '', $post_id);
        }
    }
    
    // 電話番号フィールドの正規化
    $phone_fields = array('contact_phone', 'support_phone');
    foreach ($phone_fields as $field_key) {
        $phone = get_field($field_key, $post_id);
        if ($phone) {
            // ハイフンを統一
            $phone = preg_replace('/[^\d-]/', '', $phone);
            $phone = preg_replace('/--+/', '-', $phone);
            update_field($field_key, $phone, $post_id);
        }
    }
}
add_action('acf/save_post', 'gi_validate_acf_fields', 20);
add_action('save_post', 'gi_validate_acf_fields', 20);

/**
 * 期限切れ助成金の自動ステータス更新（Cronジョブ）
 */
function gi_check_expired_grants() {
    $today_ymd = intval(date('Ymd'));
    
    // 期限切れの助成金を取得
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'deadline_date',
                'value' => $today_ymd,
                'compare' => '<',
                'type' => 'NUMERIC'
            ),
            array(
                'key' => 'application_status',
                'value' => 'closed',
                'compare' => '!='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // ステータスを「closed」に更新
            update_post_meta($post_id, 'application_status', 'closed');
            if (function_exists('update_field')) {
                update_field('application_status', 'closed', $post_id);
            }
            
            // 期限切れフラグを設定
            update_post_meta($post_id, 'is_expired', true);
            
            // ログ記録
            if (WP_DEBUG_LOG) {
                error_log(sprintf('Grant %d expired and closed automatically', $post_id));
            }
        }
        wp_reset_postdata();
    }
}

// 毎日実行するCronジョブを設定
if (!wp_next_scheduled('gi_check_expired_grants_event')) {
    wp_schedule_event(time(), 'daily', 'gi_check_expired_grants_event');
}
add_action('gi_check_expired_grants_event', 'gi_check_expired_grants');

/**
 * バリデーションエラーの表示（管理画面）
 */
function gi_display_validation_errors() {
    if (!is_admin()) {
        return;
    }
    
    global $post;
    if (!$post) {
        return;
    }
    
    $errors = get_transient('gi_acf_validation_errors_' . $post->ID);
    
    if ($errors && !empty($errors)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>入力エラー:</strong></p>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        // エラーを削除
        delete_transient('gi_acf_validation_errors_' . $post->ID);
    }
}
add_action('admin_notices', 'gi_display_validation_errors');

/**
 * 金額の表示フォーマット関数
 * 
 * @param int $amount_numeric 数値金額
 * @param string $format フォーマット（'man'=万円, 'yen'=円）
 * @return string フォーマット済み金額
 */
function gi_format_amount_display($amount_numeric, $format = 'man') {
    $amount_numeric = intval($amount_numeric);
    
    if ($amount_numeric <= 0) {
        return '要問合せ';
    }
    
    switch ($format) {
        case 'man':
            // 万円表示
            if ($amount_numeric >= 10000) {
                return number_format($amount_numeric / 10000) . '万円';
            } else {
                return number_format($amount_numeric) . '円';
            }
            break;
            
        case 'yen':
            // 円表示
            return number_format($amount_numeric) . '円';
            break;
            
        case 'short':
            // 短縮表示（1000万円以上は「1,000万円」、それ未満は「100万円」）
            if ($amount_numeric >= 100000000) {
                return number_format($amount_numeric / 100000000, 1) . '億円';
            } elseif ($amount_numeric >= 10000) {
                return number_format($amount_numeric / 10000) . '万円';
            } else {
                return number_format($amount_numeric) . '円';
            }
            break;
            
        default:
            return number_format($amount_numeric);
    }
}

/**
 * 期限の表示フォーマット関数
 * 
 * @param int $deadline_ymd Ymd形式の日付
 * @param string $format フォーマット
 * @return string フォーマット済み日付
 */
function gi_format_deadline_display($deadline_ymd, $format = 'relative') {
    $deadline_ymd = intval($deadline_ymd);
    
    if ($deadline_ymd <= 0) {
        return '随時募集';
    }
    
    $today_ymd = intval(date('Ymd'));
    $deadline_timestamp = strtotime($deadline_ymd);
    
    if ($deadline_timestamp === false) {
        return '随時募集';
    }
    
    switch ($format) {
        case 'relative':
            // 相対表示（あと○日）
            $days_left = floor(($deadline_timestamp - time()) / 86400);
            
            if ($days_left < 0) {
                return '<span class="text-gray-500">募集終了</span>';
            } elseif ($days_left == 0) {
                return '<span class="text-red-600 font-bold">本日締切</span>';
            } elseif ($days_left <= 7) {
                return '<span class="text-red-600 font-bold">あと' . $days_left . '日</span>';
            } elseif ($days_left <= 30) {
                return '<span class="text-orange-600">あと' . $days_left . '日</span>';
            } else {
                return date('Y年n月j日', $deadline_timestamp) . ' まで';
            }
            break;
            
        case 'full':
            // 完全表示
            return date('Y年n月j日', $deadline_timestamp);
            break;
            
        case 'short':
            // 短縮表示
            return date('n/j', $deadline_timestamp);
            break;
            
        default:
            return date($format, $deadline_timestamp);
    }
}