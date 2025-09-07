<?php
/**
 * Grant Insight Theme - AI Optimized Functions
 * 
 * This is the new, streamlined functions.php file that uses
 * class-based architecture for better AI development.
 * 
 * @package Grant_Insight_AI_Optimized
 * @version 7.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// オートローダーの読み込み
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Composerのオートローダー（推奨）
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/autoload.php')) {
    // 手動オートローダー（フォールバック）
    require_once __DIR__ . '/autoload.php';
} else {
    // オートローダーが見つからない場合の警告
    if (is_admin()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Grant Insight Theme:</strong> オートローダーが見つかりません。<code>composer install</code>を実行するか、<code>autoload.php</code>ファイルを確認してください。</p>';
            echo '</div>';
        });
    }
    return;
}

// テーマクラスの初期化
try {
    // コア機能の初期化
    if (class_exists('GrantInsight\Core\ThemeSetup')) {
        GrantInsight\Core\ThemeSetup::init();
    }
    
    if (class_exists('GrantInsight\Core\Enqueue')) {
        GrantInsight\Core\Enqueue::init();
    }
    
    // AJAX機能の初期化
    if (class_exists('GrantInsight\Ajax\GrantSearch')) {
        GrantInsight\Ajax\GrantSearch::init();
    }
    
    if (class_exists('GrantInsight\Ajax\AiChat')) {
        GrantInsight\Ajax\AiChat::init();
    }
    
    if (class_exists('GrantInsight\Ajax\GrantAnalyzer')) {
        GrantInsight\Ajax\GrantAnalyzer::init();
    }
    
    if (class_exists('GrantInsight\Ajax\DiagnosisEngine')) {
        GrantInsight\Ajax\DiagnosisEngine::init();
    }
    
    if (class_exists('GrantInsight\Ajax\NewsletterManager')) {
        GrantInsight\Ajax\NewsletterManager::init();
    }
    
    // ヘルパー機能の初期化
    if (class_exists('GrantInsight\Helpers\Formatting')) {
        GrantInsight\Helpers\Formatting::init();
    }
    
    // カスタム投稿タイプの初期化
    if (class_exists('GrantInsight\PostTypes\Grant')) {
        GrantInsight\PostTypes\Grant::init();
    }
    
    // 既存のincファイルとの互換性を保持
    $inc_files = [
        'inc/function/enqueue-scripts.php',
        'inc/function/performance.php',
        'inc/function/security.php',
        'inc/function/helpers.php',
        'inc/function/ajax-handlers.php',
        'inc/function/post-types.php',
        'inc/function/taxonomies.php',
        'inc/function/customizer.php',
        'inc/function/admin-enhancements.php',
        'inc/function/theme-setup.php'
    ];
    
    foreach ($inc_files as $file) {
        $file_path = get_template_directory() . '/' . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // ACF関連ファイル
    if (file_exists(get_template_directory() . '/acf-fields-setup.php')) {
        require_once get_template_directory() . '/acf-fields-setup.php';
    }
    if (file_exists(get_template_directory() . '/inc/acf-import.php')) {
        require_once get_template_directory() . '/inc/acf-import.php';
    }
    
} catch (Exception $e) {
    // エラーハンドリング
    if (WP_DEBUG) {
        error_log('Grant Insight Theme Error: ' . $e->getMessage());
    }
    
    if (is_admin()) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Grant Insight Theme Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '</div>';
        });
    }
}

/**
 * 開発者向けヘルパー関数
 * AIが利用可能なコンポーネントを確認するための関数
 */
if (WP_DEBUG && function_exists('gi_get_available_components')) {
    add_action('wp_footer', function() {
        if (current_user_can('administrator') && isset($_GET['debug_components'])) {
            $components = gi_get_available_components();
            echo '<!-- Available Components: ' . implode(', ', $components) . ' -->';
        }
    });
}

