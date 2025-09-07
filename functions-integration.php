<?php
/**
 * 機能統合ファイル
 * 
 * Phase 1の改善機能をWordPressテーマに統合します。
 * このファイルをfunctions.phpから読み込んでください。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1の改善ファイルを読み込み
 */
function gi_load_phase1_improvements() {
    // 改善版AJAX処理（セキュリティ強化版）
    if (file_exists(get_template_directory() . '/ajax-handlers-improved.php')) {
        require_once get_template_directory() . '/ajax-handlers-improved.php';
    }
    
    // 助成金件数動的取得機能
    if (file_exists(get_template_directory() . '/grant-counts.php')) {
        require_once get_template_directory() . '/grant-counts.php';
    }
    
    // AI診断機能バックエンド
    if (file_exists(get_template_directory() . '/ai-diagnosis.php')) {
        require_once get_template_directory() . '/ai-diagnosis.php';
    }
}

// 初期化時に読み込み
add_action('after_setup_theme', 'gi_load_phase1_improvements', 5);

/**
 * 必要な追加ヘルパー関数
 */

// 安全な抜粋取得
if (!function_exists('gi_safe_excerpt')) {
    function gi_safe_excerpt($text, $length = 150) {
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text);
        $text = mb_substr($text, 0, $length);
        if (mb_strlen($text) === $length) {
            $text .= '...';
        }
        return $text;
    }
}

// 安全なメタデータ取得
if (!function_exists('gi_safe_get_meta')) {
    function gi_safe_get_meta($post_id, $key, $default = '') {
        $value = get_post_meta($post_id, $key, true);
        return !empty($value) ? $value : $default;
    }
}

// 安全なエスケープ
if (!function_exists('gi_safe_escape')) {
    function gi_safe_escape($text) {
        return esc_html($text);
    }
}

// 安全なURL処理
if (!function_exists('gi_safe_url')) {
    function gi_safe_url($url) {
        return esc_url($url);
    }
}

// 安全な属性処理
if (!function_exists('gi_safe_attr')) {
    function gi_safe_attr($text) {
        return esc_attr($text);
    }
}

// 安全な数値フォーマット
if (!function_exists('gi_safe_number_format')) {
    function gi_safe_number_format($number) {
        return number_format(intval($number));
    }
}

// 関連投稿取得（未定義の場合）
if (!function_exists('gi_get_related_posts')) {
    function gi_get_related_posts($post_id, $count = 4) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        
        // カテゴリーベースで関連投稿を取得
        $categories = wp_get_post_terms($post_id, $post->post_type . '_category', array('fields' => 'ids'));
        
        $args = array(
            'post_type' => $post->post_type,
            'post__not_in' => array($post_id),
            'posts_per_page' => $count,
            'post_status' => 'publish'
        );
        
        if (!empty($categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $post->post_type . '_category',
                    'field' => 'term_id',
                    'terms' => $categories
                )
            );
        }
        
        return get_posts($args);
    }
}

// 投稿ビュー追跡（未定義の場合）
if (!function_exists('gi_track_post_view')) {
    function gi_track_post_view($post_id) {
        if (!$post_id) return;
        
        $count_key = 'views_count';
        $count = get_post_meta($post_id, $count_key, true);
        
        if ($count == '') {
            $count = 0;
            delete_post_meta($post_id, $count_key);
            add_post_meta($post_id, $count_key, '1');
        } else {
            $count++;
            update_post_meta($post_id, $count_key, $count);
        }
    }
}

/**
 * AJAXノンス登録
 */
function gi_register_ajax_nonces() {
    // 管理画面とフロントエンドの両方で登録
    wp_localize_script('gi-main-script', 'gi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'search_nonce' => wp_create_nonce('grant_insight_search_nonce'),
        'diagnosis_nonce' => wp_create_nonce('gi_ai_diagnosis_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'gi_register_ajax_nonces', 20);
add_action('admin_enqueue_scripts', 'gi_register_ajax_nonces', 20);

/**
 * データベーステーブル作成（テーマ有効化時）
 */
function gi_phase1_activation() {
    // AI診断テーブル作成
    if (function_exists('gi_create_diagnosis_tables')) {
        gi_create_diagnosis_tables();
    }
}
add_action('after_switch_theme', 'gi_phase1_activation');

/**
 * 管理画面への通知追加
 */
function gi_phase1_admin_notices() {
    // Phase 1の機能が有効になったことを通知
    if (get_transient('gi_phase1_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>助成金診断サイト Phase 1 改修完了</strong></p>
            <ul>
                <li>✅ セキュリティ・エラーハンドリングの統一化</li>
                <li>✅ 件数表示の動的化</li>
                <li>✅ AI診断機能のバックエンド実装</li>
            </ul>
        </div>
        <?php
        delete_transient('gi_phase1_activated');
    }
}
add_action('admin_notices', 'gi_phase1_admin_notices');

// 初回実行時にトランジェント設定
if (!get_option('gi_phase1_installed')) {
    set_transient('gi_phase1_activated', true, 60);
    update_option('gi_phase1_installed', true);
}