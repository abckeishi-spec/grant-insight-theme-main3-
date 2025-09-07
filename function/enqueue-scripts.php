<?php
/**
 * CSS・JS読み込み
 * 
 * このファイルでは、テーマで使用するCSSスタイルシートとJavaScriptファイルを管理します。
 * フロントエンドと管理画面の両方で必要なスクリプトとスタイルを適切に読み込みます。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * スクリプト・スタイルの読み込み（完全一元管理）
 */
function gi_enqueue_scripts() {
    // ビルドされたTailwind CSS（本番用）
    $css_file_path = get_template_directory() . '/dist/main.css';
    $css_file_uri = get_template_directory_uri() . '/dist/main.css';
    
    if (file_exists($css_file_path)) {
        // ファイルの最終更新時刻をバージョン番号として利用（キャッシュ対策）
        $css_version = filemtime($css_file_path);
        wp_enqueue_style('gi-tailwind-build', $css_file_uri, array(), $css_version);
    } else {
        // ビルドファイルが見つからない場合の警告
        if (WP_DEBUG && current_user_can('administrator')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Grant Insight Theme:</strong> Tailwind CSSのビルドファイルが見つかりません。テーマディレクトリで <code>npm run build</code> を実行してください。</p>';
                echo '</div>';
            });
        }
        // フォールバック: 既存のstyle.cssを読み込み
        wp_enqueue_style('gi-style-fallback', get_stylesheet_uri(), array(), GI_THEME_VERSION);
    }
    
    // Font Awesome CDN（一元管理）
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    
    // Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700;800;900&display=swap', array(), null);
    
    // テーマスタイル
    wp_enqueue_style('gi-style', get_stylesheet_uri(), array(), GI_THEME_VERSION);
    
    // メインJavaScript
    wp_enqueue_script('gi-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), GI_THEME_VERSION, true);
    
    // AJAX設定（強化版）
    wp_localize_script('gi-main-js', 'gi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'homeUrl' => home_url('/'),
        'themeUrl' => get_template_directory_uri(),
        'uploadsUrl' => wp_upload_dir()['baseurl'],
        'isAdmin' => current_user_can('administrator'),
        'userId' => get_current_user_id(),
        'version' => GI_THEME_VERSION,
        'debug' => WP_DEBUG,
        'strings' => array(
            'loading' => '読み込み中...',
            'error' => 'エラーが発生しました',
            'noResults' => '結果が見つかりませんでした',
            'confirm' => '実行してもよろしいですか？'
        )
    ));
    // Back-compat shim for legacy inline scripts expecting giAjax
    wp_add_inline_script('gi-main-js', 'window.giAjax = window.giAjax || { ajaxurl: gi_ajax.ajax_url, nonce: gi_ajax.nonce };');
    
    // 条件付きスクリプト読み込み
    if (is_singular()) {
        wp_enqueue_script('comment-reply');
    }
    
    if (is_front_page()) {
        wp_enqueue_script('gi-frontend-js', get_template_directory_uri() . '/assets/js/front-page.js', array('gi-main-js'), GI_THEME_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'gi_enqueue_scripts');

/**
 * 管理画面用スクリプト
 */
function gi_admin_enqueue_scripts($hook) {
    wp_enqueue_style('gi-admin-style', get_template_directory_uri() . '/css/admin.css', array(), GI_THEME_VERSION);
    wp_enqueue_script('gi-admin-js', get_template_directory_uri() . '/js/admin.js', array('jquery'), GI_THEME_VERSION, true);
    
    wp_localize_script('gi-admin-js', 'giAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'gi_admin_enqueue_scripts');


