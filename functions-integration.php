<?php
/**
 * Grant Insight テーマ - Phase 1 改修統合ファイル
 * 
 * このファイルを既存のfunctions.phpに含めることで、
 * Phase 1の改修機能を有効化します。
 * 
 * 使用方法:
 * 1. このファイルをテーマディレクトリに配置
 * 2. functions.phpの最後に以下を追加:
 *    require_once get_template_directory() . '/functions-integration.php';
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Phase 1 改修ファイルの読み込み
 */
function gi_load_phase1_improvements() {
    $theme_dir = get_template_directory();
    
    // 安全な出力関数の読み込み（最優先）
    if (file_exists($theme_dir . '/safe-output-functions.php')) {
        require_once $theme_dir . '/safe-output-functions.php';
    }
    
    // 改善版ヘルパー関数の読み込み
    if (file_exists($theme_dir . '/helpers-improved.php')) {
        require_once $theme_dir . '/helpers-improved.php';
    }
    
    // 改善版AJAXハンドラーの読み込み
    if (file_exists($theme_dir . '/ajax-handlers-improved.php')) {
        require_once $theme_dir . '/ajax-handlers-improved.php';
    }
    
    // 動的件数取得機能の読み込み
    if (file_exists($theme_dir . '/grant-counts.php')) {
        require_once $theme_dir . '/grant-counts.php';
    }
    
    // AI診断機能の読み込み
    if (file_exists($theme_dir . '/ai-diagnosis.php')) {
        require_once $theme_dir . '/ai-diagnosis.php';
    }
    
    // カスタマイザー設定の読み込み（タスク4&5）
    if (file_exists($theme_dir . '/customizer-settings.php')) {
        require_once $theme_dir . '/customizer-settings.php';
    }
    
    // アイコン管理機能の読み込み（タスク5）
    if (file_exists($theme_dir . '/icon-management.php')) {
        require_once $theme_dir . '/icon-management.php';
    }
}
add_action('after_setup_theme', 'gi_load_phase1_improvements', 5);

/**
 * 既存のAJAXハンドラーを無効化（改善版と置き換え）
 */
function gi_disable_old_ajax_handlers() {
    // 既存のAJAXアクションを削除
    remove_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
    remove_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');
    remove_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
    remove_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');
    remove_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
    remove_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');
    remove_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
    remove_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');
    remove_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');
    remove_action('wp_ajax_get_user_favorites', 'gi_ajax_get_user_favorites');
    remove_action('wp_ajax_get_related_posts', 'gi_ajax_get_related_posts');
    remove_action('wp_ajax_nopriv_get_related_posts', 'gi_ajax_get_related_posts');
    remove_action('wp_ajax_track_post_view', 'gi_ajax_track_post_view');
    remove_action('wp_ajax_nopriv_track_post_view', 'gi_ajax_track_post_view');
}
add_action('init', 'gi_disable_old_ajax_handlers', 20);

/**
 * JavaScript用のローカライズデータ拡張
 */
function gi_extend_localize_data() {
    if (is_admin()) {
        return;
    }
    
    // AI診断用のnonce追加
    wp_localize_script('gi-main', 'gi_ai_diagnosis', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ai_diagnosis_nonce'),
        'questions' => gi_get_diagnosis_questions(),
        'messages' => array(
            'loading' => '診断中...',
            'error' => 'エラーが発生しました。',
            'required' => '必須項目を入力してください。',
            'no_results' => '該当する助成金が見つかりませんでした。'
        )
    ));
    
    // 件数取得用のnonce追加
    wp_localize_script('gi-main', 'gi_counts', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'gi_extend_localize_data', 20);

/**
 * 管理画面にPhase 1改修の状態を表示
 */
function gi_add_admin_notices() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $improvements = array(
        'セキュリティ・エラーハンドリング統一化' => file_exists(get_template_directory() . '/ajax-handlers-improved.php'),
        '件数表示の動的化' => file_exists(get_template_directory() . '/grant-counts.php'),
        'AI診断機能' => file_exists(get_template_directory() . '/ai-diagnosis.php'),
        '改善版ヘルパー関数' => file_exists(get_template_directory() . '/helpers-improved.php')
    );
    
    $all_loaded = !in_array(false, $improvements, true);
    
    if ($all_loaded) {
        ?>
        <div class="notice notice-success">
            <p><strong>Grant Insight Phase 1改修:</strong> すべての機能が正常に読み込まれています。</p>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-warning">
            <p><strong>Grant Insight Phase 1改修:</strong> 一部の機能が読み込まれていません。</p>
            <ul>
            <?php foreach ($improvements as $name => $loaded) : ?>
                <li><?php echo esc_html($name); ?>: <?php echo $loaded ? '✅ 有効' : '❌ 無効'; ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}
add_action('admin_notices', 'gi_add_admin_notices');

/**
 * データベーステーブルの作成確認と初期化
 */
function gi_check_and_create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'gi_diagnosis_history';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // テーブルが存在しない場合は作成
        if (function_exists('gi_create_diagnosis_tables')) {
            gi_create_diagnosis_tables();
        }
    }
}
add_action('admin_init', 'gi_check_and_create_tables');

/**
 * Phase 1改修用のJavaScriptファイル
 */
function gi_enqueue_phase1_scripts() {
    if (is_admin()) {
        return;
    }
    
    // AI診断用JavaScript
    wp_enqueue_script(
        'gi-ai-diagnosis',
        get_template_directory_uri() . '/assets/js/ai-diagnosis.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // 動的件数更新用JavaScript
    wp_enqueue_script(
        'gi-dynamic-counts',
        get_template_directory_uri() . '/assets/js/dynamic-counts.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'gi_enqueue_phase1_scripts');

/**
 * 既存関数のオーバーライド設定
 * 既存のhelpers.phpより後に読み込まれるように優先度を設定
 */
if (!function_exists('gi_safe_get_meta')) {
    // helpers-improved.phpの関数が優先される
}

/**
 * WP-Cronジョブの設定（キャッシュクリア用）
 */
function gi_schedule_cache_clear() {
    if (!wp_next_scheduled('gi_clear_counts_cache_event')) {
        wp_schedule_event(time(), 'hourly', 'gi_clear_counts_cache_event');
    }
}
add_action('wp', 'gi_schedule_cache_clear');

// キャッシュクリアイベントのフック
add_action('gi_clear_counts_cache_event', 'gi_clear_grant_counts_cache');

/**
 * テーマ無効化時のクリーンアップ
 */
function gi_phase1_cleanup() {
    // スケジュールされたイベントをクリア
    wp_clear_scheduled_hook('gi_clear_counts_cache_event');
    
    // キャッシュをクリア
    if (function_exists('gi_clear_grant_counts_cache')) {
        gi_clear_grant_counts_cache();
    }
}
register_deactivation_hook(__FILE__, 'gi_phase1_cleanup');

/**
 * デバッグモード設定（開発環境用）
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    // デバッグ情報の出力
    add_action('wp_footer', function() {
        if (current_user_can('manage_options')) {
            echo "<!-- Grant Insight Phase 1 Improvements Loaded -->\n";
            echo "<!-- Security: Enhanced | Counts: Dynamic | AI: Enabled -->\n";
        }
    });
}

/**
 * REST API エンドポイントの追加（オプション）
 */
function gi_register_rest_endpoints() {
    // AI診断用RESTエンドポイント
    register_rest_route('grant-insight/v1', '/ai-diagnosis', array(
        'methods' => 'POST',
        'callback' => 'gi_rest_ai_diagnosis',
        'permission_callback' => '__return_true',
        'args' => array(
            'answers' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_array($param);
                }
            ),
        ),
    ));
    
    // 件数取得用RESTエンドポイント
    register_rest_route('grant-insight/v1', '/grant-counts', array(
        'methods' => 'GET',
        'callback' => 'gi_rest_get_counts',
        'permission_callback' => '__return_true',
        'args' => array(
            'type' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return in_array($param, ['category', 'prefecture', 'total'], true);
                }
            ),
            'slug' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_title'
            ),
        ),
    ));
}
add_action('rest_api_init', 'gi_register_rest_endpoints');

/**
 * REST APIコールバック: AI診断
 */
function gi_rest_ai_diagnosis($request) {
    $answers = $request->get_param('answers');
    
    // 内部的にAJAX関数を呼び出し
    $_POST['answers'] = json_encode($answers);
    $_POST['nonce'] = wp_create_nonce('gi_ai_diagnosis_nonce');
    
    // バッファリング開始
    ob_start();
    gi_ai_diagnosis_api();
    $response = ob_get_clean();
    
    return json_decode($response, true);
}

/**
 * REST APIコールバック: 件数取得
 */
function gi_rest_get_counts($request) {
    $type = $request->get_param('type');
    $slug = $request->get_param('slug');
    
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
        default:
            $count = 0;
    }
    
    return array(
        'type' => $type,
        'slug' => $slug,
        'count' => $count
    );
}