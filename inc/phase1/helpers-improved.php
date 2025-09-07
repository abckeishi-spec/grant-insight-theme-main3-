<?php
/**
 * Grant Insight - 改善版ヘルパー関数（重複対策済み）
 * 
 * 既存関数との重複を防ぐため、すべての関数定義を条件付きで行います
 */

// Direct access prevention
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
if (!function_exists('gi_safe_excerpt')) {
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
}

/**
 * 汎用エスケープ関数
 * 
 * @param string $text
 * @return string
 */
if (!function_exists('gi_safe_escape')) {
    function gi_safe_escape($text) {
        return esc_html($text);
    }
}

/**
 * URL用エスケープ
 * 
 * @param string $url
 * @return string
 */
if (!function_exists('gi_safe_url')) {
    function gi_safe_url($url) {
        return esc_url($url);
    }
}

/**
 * 属性用エスケープ
 * 
 * @param string $text
 * @return string
 */
if (!function_exists('gi_safe_attr')) {
    function gi_safe_attr($text) {
        return esc_attr($text);
    }
}

/**
 * 数値フォーマット
 * 
 * @param mixed $number
 * @param int $decimals
 * @return string
 */
if (!function_exists('gi_safe_number_format')) {
    function gi_safe_number_format($number, $decimals = 0) {
        $safe_number = is_numeric($number) ? floatval($number) : 0;
        return number_format($safe_number, $decimals);
    }
}

/**
 * 締切日時の取得と整形
 * 
 * @param int $post_id
 * @return array
 */
if (!function_exists('gi_get_formatted_deadline')) {
    function gi_get_formatted_deadline($post_id) {
        try {
            $deadline = gi_safe_get_meta($post_id, 'deadline', '');
            
            if (empty($deadline)) {
                return [
                    'raw' => '',
                    'formatted' => '締切未定',
                    'days_left' => -1,
                    'status' => 'unknown'
                ];
            }
            
            $deadline_date = strtotime($deadline);
            if ($deadline_date === false) {
                return [
                    'raw' => $deadline,
                    'formatted' => esc_html($deadline),
                    'days_left' => -1,
                    'status' => 'invalid'
                ];
            }
            
            $current_date = current_time('timestamp');
            $days_left = floor(($deadline_date - $current_date) / (60 * 60 * 24));
            
            $status = 'active';
            if ($days_left < 0) {
                $status = 'expired';
            } elseif ($days_left <= 7) {
                $status = 'urgent';
            } elseif ($days_left <= 30) {
                $status = 'soon';
            }
            
            return [
                'raw' => $deadline,
                'formatted' => date_i18n('Y年n月j日', $deadline_date),
                'days_left' => $days_left,
                'status' => $status
            ];
            
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('gi_get_formatted_deadline error: ' . $e->getMessage());
            }
            return [
                'raw' => '',
                'formatted' => 'エラー',
                'days_left' => -1,
                'status' => 'error'
            ];
        }
    }
}

/**
 * 助成金メタデータの同期
 * ACFフィールドと通常のメタデータを同期
 */
if (!function_exists('gi_sync_grant_meta_on_save')) {
    function gi_sync_grant_meta_on_save($post_id, $post, $update) {
        // 自動保存の場合は何もしない
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // 投稿タイプチェック
        if ($post->post_type !== 'grant') {
            return;
        }
        
        try {
            // ACFフィールドが存在する場合の同期処理
            if (function_exists('get_field')) {
                $fields_to_sync = [
                    'amount_min' => 'sanitize_text_field',
                    'amount_max' => 'sanitize_text_field',
                    'deadline' => 'sanitize_text_field',
                    'target_industry' => 'sanitize_text_field',
                    'target_number' => 'absint',
                    'municipality' => 'sanitize_text_field',
                    'is_featured' => 'absint'
                ];
                
                foreach ($fields_to_sync as $field => $sanitize_callback) {
                    $value = get_field($field, $post_id);
                    if ($value !== null && $value !== false) {
                        $sanitized_value = call_user_func($sanitize_callback, $value);
                        update_post_meta($post_id, $field, $sanitized_value);
                    }
                }
            }
            
            // POSTデータからの直接更新（ACFがない場合）
            if (isset($_POST) && !empty($_POST)) {
                $direct_fields = [
                    'grant_amount_min' => 'amount_min',
                    'grant_amount_max' => 'amount_max',
                    'grant_deadline' => 'deadline',
                    'grant_target_industry' => 'target_industry',
                    'grant_target_number' => 'target_number',
                    'grant_municipality' => 'municipality'
                ];
                
                foreach ($direct_fields as $post_field => $meta_key) {
                    if (isset($_POST[$post_field])) {
                        $value = sanitize_text_field($_POST[$post_field]);
                        update_post_meta($post_id, $meta_key, $value);
                    }
                }
            }
            
            // キャッシュクリア
            if (function_exists('gi_clear_grant_cache')) {
                gi_clear_grant_cache($post_id);
            }
            
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('Grant meta sync error: ' . $e->getMessage());
            }
        }
    }
}

// フックの登録（関数が定義されている場合のみ）
if (function_exists('gi_sync_grant_meta_on_save')) {
    add_action('save_post', 'gi_sync_grant_meta_on_save', 10, 3);
}

/**
 * アセットURLの取得
 * 
 * @param string $path
 * @return string
 */
if (!function_exists('gi_get_asset_url')) {
    function gi_get_asset_url($path) {
        return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
    }
}

/**
 * アップロードディレクトリのURL取得
 */
if (!function_exists('gi_get_upload_url')) {
    function gi_get_upload_url($filename) {
        $upload_dir = wp_upload_dir();
        return $upload_dir['url'] . '/' . $filename;
    }
}

/**
 * メディアURLの取得（フォールバック付き）
 * 
 * @param string $filename
 * @param bool $fallback
 * @return string
 */
if (!function_exists('gi_get_media_url')) {
    function gi_get_media_url($filename, $fallback = true) {
        // まずアップロードディレクトリを確認
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (file_exists($file_path)) {
            return $upload_dir['url'] . '/' . $filename;
        }
        
        // テーマのassetsディレクトリを確認
        $theme_path = get_template_directory() . '/assets/images/' . $filename;
        if (file_exists($theme_path)) {
            return get_template_directory_uri() . '/assets/images/' . $filename;
        }
        
        // フォールバック画像
        if ($fallback) {
            return get_template_directory_uri() . '/assets/images/placeholder.jpg';
        }
        
        return '';
    }
}

/**
 * 投稿タイプの判定ヘルパー
 * 
 * @param string $post_type
 * @return bool
 */
if (!function_exists('gi_is_grant_post_type')) {
    function gi_is_grant_post_type($post_type = null) {
        if (is_null($post_type)) {
            $post_type = get_post_type();
        }
        
        $grant_post_types = ['grant', 'grant_tip', 'tool', 'case_study'];
        return in_array($post_type, $grant_post_types, true);
    }
}

/**
 * カテゴリー取得ヘルパー
 * 
 * @param int $post_id
 * @return array
 */
if (!function_exists('gi_get_grant_categories')) {
    function gi_get_grant_categories($post_id = null) {
        if (is_null($post_id)) {
            $post_id = get_the_ID();
        }
        
        $categories = wp_get_post_terms($post_id, 'grant_category');
        
        if (is_wp_error($categories)) {
            return [];
        }
        
        return $categories;
    }
}

/**
 * ユーザー権限チェック
 * 
 * @param string $capability
 * @param int $post_id
 * @return bool
 */
if (!function_exists('gi_user_can_edit')) {
    function gi_user_can_edit($capability = 'edit_posts', $post_id = null) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        if ($post_id) {
            return current_user_can($capability, $post_id);
        }
        
        return current_user_can($capability);
    }
}

/**
 * エラーログ出力（改善版）
 * 
 * @param string $type
 * @param string $message
 * @param array $context
 */
if (!function_exists('gi_log_error')) {
    function gi_log_error($type, $message, $context = []) {
        if (!WP_DEBUG_LOG) {
            return;
        }
        
        $log_entry = sprintf(
            '[%s] [%s] %s',
            current_time('mysql'),
            strtoupper($type),
            $message
        );
        
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . json_encode($context);
        }
        
        error_log($log_entry);
        
        // データベースログ（オプション）
        if (defined('GI_DB_LOGGING') && GI_DB_LOGGING) {
            global $wpdb;
            $table = $wpdb->prefix . 'gi_error_logs';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $wpdb->insert(
                    $table,
                    [
                        'error_type' => $type,
                        'message' => $message,
                        'context' => json_encode($context),
                        'user_id' => get_current_user_id(),
                        'url' => $_SERVER['REQUEST_URI'] ?? '',
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%s', '%s', '%d', '%s', '%s']
                );
            }
        }
    }
}

/**
 * パフォーマンス計測ヘルパー
 * 
 * @param string $operation
 * @param callable $callback
 * @return mixed
 */
if (!function_exists('gi_measure_performance')) {
    function gi_measure_performance($operation, $callback) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            $result = call_user_func($callback);
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage();
            
            $execution_time = ($end_time - $start_time) * 1000; // ミリ秒
            $memory_used = ($end_memory - $start_memory) / 1024 / 1024; // MB
            
            if (WP_DEBUG && defined('GI_PERFORMANCE_LOG') && GI_PERFORMANCE_LOG) {
                gi_log_error('performance', sprintf(
                    '%s completed in %.2fms using %.2fMB',
                    $operation,
                    $execution_time,
                    $memory_used
                ));
            }
            
            return $result;
            
        } catch (Exception $e) {
            gi_log_error('performance_error', $e->getMessage(), [
                'operation' => $operation
            ]);
            throw $e;
        }
    }
}

/**
 * キャッシュヘルパー
 * 
 * @param string $key
 * @param callable $callback
 * @param int $expiration
 * @return mixed
 */
if (!function_exists('gi_cached_result')) {
    function gi_cached_result($key, $callback, $expiration = 3600) {
        $cached = get_transient($key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $result = call_user_func($callback);
        set_transient($key, $result, $expiration);
        
        return $result;
    }
}