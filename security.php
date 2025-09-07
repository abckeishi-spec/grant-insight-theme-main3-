<?php
/**
 * セキュリティ機能
 * 
 * このファイルでは、WordPressサイトのセキュリティを強化するための機能を提供します。
 * 不要な情報の削除、ログイン試行回数制限、XML-RPCの無効化などが含まれます。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPressのバージョン情報を削除
 */
remove_action('wp_head', 'wp_generator');

/**
 * RSDリンクを削除
 */
remove_action('wp_head', 'rsd_link');

/**
 * Windows Live Writerマニフェストファイルを削除
 */
remove_action('wp_head', 'wlwmanifest_link');

/**
 * ショートリンクを削除
 */
remove_action('wp_head', 'wp_shortlink_wp_head');

/**
 * REST APIリンクを削除
 */
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');

/**
 * 絵文字スクリプトとスタイルを削除
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');

/**
 * コメントフィードを削除
 */
remove_action('wp_head', 'feed_links_extra', 3);

/**
 * WordPressのログインエラーメッセージを汎用化
 */
function gi_login_errors() {
    return 'ログイン情報が正しくありません。';
}
add_filter('login_errors', 'gi_login_errors');

/**
 * XML-RPCを無効化
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * ユーザー名の列挙を防止
 */
if (!function_exists('gi_disable_author_archive')) {
    function gi_disable_author_archive() {
        if (is_author()) {
            wp_redirect(home_url());
            exit;
        }
    }
    add_action('template_redirect', 'gi_disable_author_archive');
}

/**
 * ログイン試行回数制限
 */
if (!function_exists('gi_limit_login_attempts')) {
    function gi_limit_login_attempts($user, $password) {
        if (get_transient('gi_login_lockout')) {
            $time_left = human_time_diff(time(), get_transient('gi_login_lockout_time'));
            wp_die(sprintf('ログイン試行回数が多すぎます。%s後に再度お試しください。', $time_left));
        }

        $attempts = get_transient('gi_login_attempts') ? get_transient('gi_login_attempts') : 0;
        $attempts++;
        set_transient('gi_login_attempts', $attempts, 5 * MINUTE_IN_SECONDS); // 5分間

        if ($attempts >= 5) { // 5回失敗でロックアウト
            set_transient('gi_login_lockout', true, 60 * MINUTE_IN_SECONDS); // 60分間ロックアウト
            set_transient('gi_login_lockout_time', time(), 60 * MINUTE_IN_SECONDS);
            wp_die('ログイン試行回数が多すぎます。60分後に再度お試しください。');
        }

        return $user;
    }
    add_filter('authenticate', 'gi_limit_login_attempts', 30, 2);
}

/**
 * ログイン成功時に試行回数をリセット
 */
function gi_login_success_reset_attempts() {
    delete_transient('gi_login_attempts');
    delete_transient('gi_login_lockout');
    delete_transient('gi_login_lockout_time');
}
add_action('wp_login', 'gi_login_success_reset_attempts');

/**
 * ユーザー登録時のスパム対策
 */
function gi_registration_honeypot() {
    echo '<p class="hidden"><label for="gi_email_confirm">Email Confirm</label><input type="text" name="gi_email_confirm" id="gi_email_confirm" class="gi_email_confirm" value="" /></p>';
}
add_action('register_form', 'gi_registration_honeypot');

function gi_check_registration_honeypot($errors, $sanitized_user_login, $user_email) {
    if (!empty($_POST['gi_email_confirm'])) {
        $errors->add('honeypot_error', '<strong>エラー</strong>: スパムと判断されました。');
    }
    return $errors;
}
add_filter('registration_errors', 'gi_check_registration_honeypot', 10, 3);

/**
 * ログインページでのCSS読み込み
 */
function gi_login_stylesheet() {
    wp_enqueue_style('gi-login-style', get_template_directory_uri() . '/css/login.css', array(), GI_THEME_VERSION);
}
add_action('login_enqueue_scripts', 'gi_login_stylesheet');

/**
 * ログインロゴのURL変更
 */
function gi_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'gi_login_logo_url');

/**
 * ログインロゴのタイトル変更
 */
function gi_login_logo_url_title() {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'gi_login_logo_url_title');

/**
 * ログイン画面のカスタムスタイル
 */
function gi_custom_login_css() {
    echo '<style type="text/css">
        #login h1 a { background-image: url(' . gi_get_logo_url() . ') !important; background-size: contain !important; width: auto !important; height: 80px !important; }
    </style>';
}
add_action('login_enqueue_scripts', 'gi_custom_login_css');


