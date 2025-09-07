<?php
/**
 * ヘルパー関数 - セキュリティ強化版
 * 
 * このファイルでは、テーマ全体で再利用可能なヘルパー関数を定義します。
 * セキュリティ強化、エラーハンドリング、サニタイズ処理を含みます。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 安全なメタデータ取得
 * 
 * @param int $post_id 投稿ID
 * @param string $key メタキー
 * @param mixed $default デフォルト値
 * @return mixed サニタイズされた値
 */
if (!function_exists('gi_safe_get_meta')) {
    function gi_safe_get_meta($post_id, $key, $default = '') {
    try {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return $default;
        }
        
        $key = sanitize_key($key);
        if (empty($key)) {
            return $default;
        }
        
        $value = get_post_meta($post_id, $key, true);
        
        // ACFフィールドのフォールバック
        if (empty($value) && function_exists('get_field')) {
            $value = get_field($key, $post_id);
        }
        
        if (empty($value)) {
            return $default;
        }
        
        // 型に応じたサニタイズ
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            return sanitize_text_field($value);
        }
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_safe_get_meta error: ' . $e->getMessage());
        }
        return $default;
    }
    }
}

/**
 * 安全な抜粋取得
 * 
 * @param string $text テキスト
 * @param int $length 長さ
 * @param string $more 続き文字
 * @return string サニタイズされた抜粋
 */
function gi_safe_excerpt($text, $length = 150, $more = '...') {
    try {
        $text = wp_strip_all_tags($text);
        $text = sanitize_text_field($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $excerpt = mb_substr($text, 0, $length);
        return $excerpt . $more;
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_safe_excerpt error: ' . $e->getMessage());
        }
        return '';
    }
}

/**
 * 安全なHTML出力エスケープ
 * 
 * @param string $text テキスト
 * @return string エスケープされたテキスト
 */
function gi_safe_escape($text) {
    return esc_html($text);
}

/**
 * 安全なURL出力
 * 
 * @param string $url URL
 * @return string エスケープされたURL
 */
function gi_safe_url($url) {
    return esc_url($url);
}

/**
 * 安全な属性出力
 * 
 * @param string $text 属性値
 * @return string エスケープされた属性値
 */
function gi_safe_attr($text) {
    return esc_attr($text);
}

/**
 * 安全な数値フォーマット
 * 
 * @param mixed $number 数値
 * @param int $decimals 小数点以下の桁数
 * @return string フォーマットされた数値
 */
function gi_safe_number_format($number, $decimals = 0) {
    try {
        $number = is_numeric($number) ? floatval($number) : 0;
        return number_format($number, $decimals);
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_safe_number_format error: ' . $e->getMessage());
        }
        return '0';
    }
}

/**
 * 締切日のフォーマット関数（改善版）
 */
function gi_get_formatted_deadline($post_id) {
    try {
        $deadline = gi_safe_get_meta($post_id, 'deadline_date');
        if (!$deadline) {
            // 旧フィールドも確認
            $deadline = gi_safe_get_meta($post_id, 'deadline');
        }
        
        if (!$deadline) {
            return '';
        }
        
        // 数値の場合（UNIXタイムスタンプ）
        if (is_numeric($deadline)) {
            $timestamp = intval($deadline);
            if ($timestamp > 0) {
                return date_i18n('Y年m月d日', $timestamp);
            }
        }
        
        // 文字列の場合
        $timestamp = strtotime($deadline);
        if ($timestamp !== false) {
            return date_i18n('Y年m月d日', $timestamp);
        }
        
        return sanitize_text_field($deadline);
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_formatted_deadline error: ' . $e->getMessage());
        }
        return '';
    }
}

/**
 * メタフィールドの同期処理（ACF対応・改善版）
 */
function gi_sync_grant_meta_on_save($post_id, $post, $update) {
    try {
        // 自動保存時はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 投稿タイプチェック
        if ($post->post_type !== 'grant') {
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // 金額の数値版を作成
        $amount_text = get_post_meta($post_id, 'max_amount', true);
        if (!$amount_text && function_exists('get_field')) {
            $amount_text = get_field('max_amount', $post_id);
        }
        
        if ($amount_text) {
            // 数値のみを抽出
            $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
            if ($amount_numeric) {
                update_post_meta($post_id, 'max_amount_numeric', intval($amount_numeric));
            }
        }
        
        // 日付の数値版を作成
        $deadline = get_post_meta($post_id, 'deadline', true);
        if (!$deadline && function_exists('get_field')) {
            $deadline = get_field('deadline', $post_id);
        }
        
        if ($deadline) {
            if (is_numeric($deadline)) {
                update_post_meta($post_id, 'deadline_date', intval($deadline));
            } else {
                $deadline_numeric = strtotime($deadline);
                if ($deadline_numeric !== false) {
                    update_post_meta($post_id, 'deadline_date', $deadline_numeric);
                }
            }
        }
        
        // ステータスの同期
        $status = get_post_meta($post_id, 'status', true);
        if (!$status && function_exists('get_field')) {
            $status = get_field('application_status', $post_id);
        }
        
        if ($status) {
            update_post_meta($post_id, 'application_status', sanitize_text_field($status));
        } else {
            // デフォルトステータス
            update_post_meta($post_id, 'application_status', 'open');
        }
        
        // 組織名の同期
        if (function_exists('get_field')) {
            $organization = get_field('organization', $post_id);
            if ($organization) {
                update_post_meta($post_id, 'organization', sanitize_text_field($organization));
            }
        }
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_sync_grant_meta_on_save error: ' . $e->getMessage());
        }
    }
}
add_action('save_post', 'gi_sync_grant_meta_on_save', 20, 3);

/**
 * 動的パス取得関数（セキュリティ強化版）
 */

// アセットURL取得
function gi_get_asset_url($path) {
    $path = ltrim($path, '/');
    $path = str_replace('..', '', $path); // ディレクトリトラバーサル対策
    return esc_url(get_template_directory_uri() . '/' . $path);
}

// アップロードURL取得
function gi_get_upload_url($filename) {
    $upload_dir = wp_upload_dir();
    $filename = ltrim($filename, '/');
    $filename = str_replace('..', '', $filename); // ディレクトリトラバーサル対策
    return esc_url($upload_dir['baseurl'] . '/' . $filename);
}

// メディアURL取得（自動検出機能付き・セキュリティ強化版）
function gi_get_media_url($filename, $fallback = true) {
    try {
        if (empty($filename)) {
            return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
        }
        
        // URLの場合はそのまま返す
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            return esc_url($filename);
        }
        
        // ディレクトリトラバーサル対策
        $filename = str_replace('..', '', $filename);
        
        // 既知のパスを削除
        $filename = str_replace([
            'http://keishi0804.xsrv.jp/wp-content/uploads/',
            'https://keishi0804.xsrv.jp/wp-content/uploads/',
            '/wp-content/uploads/'
        ], '', $filename);
        
        $filename = ltrim($filename, '/');
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $filename;
        
        if (file_exists($file_path)) {
            return esc_url($upload_dir['baseurl'] . '/' . $filename);
        }
        
        // 年月ディレクトリをチェック
        $current_year = date('Y');
        $current_month = date('m');
        
        $possible_paths = [
            $current_year . '/' . $current_month . '/' . $filename,
            $current_year . '/' . $filename,
            'uploads/' . $filename,
            'media/' . $filename
        ];
        
        foreach ($possible_paths as $path) {
            $full_path = $upload_dir['basedir'] . '/' . $path;
            if (file_exists($full_path)) {
                return esc_url($upload_dir['baseurl'] . '/' . $path);
            }
        }
        
        if ($fallback) {
            return gi_get_asset_url('assets/images/placeholder.jpg');
        }
        
        return '';
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_media_url error: ' . $e->getMessage());
        }
        return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
    }
}

/**
 * 金額（円）を万円表示用に整形（改善版）
 */
function gi_format_amount_man($amount_yen, $amount_text = '') {
    try {
        $yen = is_numeric($amount_yen) ? intval($amount_yen) : 0;
        if ($yen > 0) {
            return gi_safe_number_format(intval($yen / 10000));
        }
        
        if (!empty($amount_text)) {
            // テキストから数値を抽出
            if (preg_match('/([0-9,]+)\s*万円/u', $amount_text, $m)) {
                return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
            }
            if (preg_match('/([0-9,]+)/u', $amount_text, $m)) {
                return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
            }
        }
        
        return '0';
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_format_amount_man error: ' . $e->getMessage());
        }
        return '0';
    }
}

/**
 * ACFのapplication_statusをUI用にマッピング
 */
function gi_map_application_status_ui($app_status) {
    $status_map = array(
        'open' => 'active',
        'upcoming' => 'upcoming',
        'closed' => 'closed'
    );
    
    $app_status = sanitize_text_field($app_status);
    return isset($status_map[$app_status]) ? $status_map[$app_status] : 'active';
}

/**
 * お気に入り一覧取得（改善版）
 */
function gi_get_user_favorites($user_id = null) {
    try {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            // Cookie利用（非ログインユーザー用）
            $cookie_name = 'gi_favorites';
            $favorites = isset($_COOKIE[$cookie_name]) ? 
                array_filter(explode(',', sanitize_text_field($_COOKIE[$cookie_name]))) : 
                array();
        } else {
            // ユーザーメタから取得
            $favorites = get_user_meta($user_id, 'gi_favorites', true);
            if (!is_array($favorites)) {
                $favorites = array();
            }
        }
        
        // 整数配列として返す
        return array_map('intval', array_filter($favorites));
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_user_favorites error: ' . $e->getMessage());
        }
        return array();
    }
}

/**
 * 投稿ビュー追跡（改善版）
 */
function gi_track_post_view($post_id) {
    try {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return false;
        }
        
        // 投稿の存在確認
        if (!get_post($post_id)) {
            return false;
        }
        
        // 現在のビュー数を取得
        $current_views = intval(get_post_meta($post_id, 'views_count', true));
        
        // ビュー数を増やす
        update_post_meta($post_id, 'views_count', $current_views + 1);
        
        return true;
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_track_post_view error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * 関連投稿取得（改善版）
 */
function gi_get_related_posts($post_id, $limit = 4) {
    try {
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return array();
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        
        // カテゴリーベースで関連投稿を取得
        $categories = wp_get_post_terms($post_id, $post->post_type . '_category', array('fields' => 'ids'));
        
        $args = array(
            'post_type' => $post->post_type,
            'posts_per_page' => absint($limit),
            'post__not_in' => array($post_id),
            'post_status' => 'publish'
        );
        
        if (!empty($categories) && !is_wp_error($categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $post->post_type . '_category',
                    'field' => 'term_id',
                    'terms' => $categories
                )
            );
        }
        
        $related_posts = get_posts($args);
        
        return $related_posts;
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_get_related_posts error: ' . $e->getMessage());
        }
        return array();
    }
}

/**
 * 都道府県同期処理（改善版）
 */
function gi_sync_grant_prefectures_on_save($post_id, $post, $update) {
    try {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if ($post->post_type !== 'grant') {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // メタ値から都道府県情報を取得
        $meta_values = array();
        $candidates = array('prefecture', 'prefectures', 'grant_prefecture');
        
        foreach ($candidates as $key) {
            $val = gi_safe_get_meta($post_id, $key, '');
            if (!empty($val)) {
                if (is_array($val)) {
                    $meta_values = $val;
                } else {
                    // カンマまたはパイプで分割
                    $meta_values = preg_split('/[,|]/u', $val);
                }
                break;
            }
        }
        
        if (empty($meta_values)) {
            return;
        }
        
        $term_ids = array();
        foreach ($meta_values as $raw) {
            $name = trim(wp_strip_all_tags($raw));
            if ($name === '') {
                continue;
            }
            
            $term = get_term_by('name', $name, 'grant_prefecture');
            if (!$term) {
                // スラッグでも試す
                $term = get_term_by('slug', sanitize_title($name), 'grant_prefecture');
            }
            
            if ($term && !is_wp_error($term)) {
                $term_ids[] = intval($term->term_id);
            }
        }
        
        if (!empty($term_ids)) {
            wp_set_post_terms($post_id, $term_ids, 'grant_prefecture', false);
        }
        
    } catch (Exception $e) {
        if (WP_DEBUG_LOG) {
            error_log('gi_sync_grant_prefectures_on_save error: ' . $e->getMessage());
        }
    }
}
add_action('save_post', 'gi_sync_grant_prefectures_on_save', 20, 3);