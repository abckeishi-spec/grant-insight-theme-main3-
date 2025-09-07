<?php
/**
 * テーマ基本設定
 * 
 * このファイルでは、WordPressテーマの基本的な設定を行います。
 * テーマサポートの追加、画像サイズの定義、メニューの登録などが含まれます。
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * テーマセットアップ
 */
function gi_setup() {
    // テーマサポート追加
    add_theme_support("title-tag");
    add_theme_support("post-thumbnails");
    add_theme_support("html5", array(
        "search-form",
        "comment-form",
        "comment-list",
        "gallery",
        "caption",
        "style",
        "script"
    ));
    add_theme_support("custom-background");
    add_theme_support("custom-logo", array(
        "height"      => 250,
        "width"       => 250,
        "flex-width"  => true,
        "flex-height" => true,
    ));
    add_theme_support("menus");
    add_theme_support("customize-selective-refresh-widgets");
    add_theme_support("responsive-embeds");
    add_theme_support("align-wide");
    add_theme_support("wp-block-styles");
    
    // RSS フィード
    add_theme_support("automatic-feed-links");
    
    // 画像サイズ追加
    add_image_size("gi-card-thumb", 400, 300, true);
    add_image_size("gi-hero-thumb", 800, 600, true);
    add_image_size("gi-tool-logo", 120, 120, true);
    add_image_size("gi-banner", 1200, 400, true);
    
    // 言語ファイル読み込み
    load_theme_textdomain("grant-insight", get_template_directory() . "/languages");
    
    // メニュー登録
    register_nav_menus(array(
        "primary" => "メインメニュー",
        "footer" => "フッターメニュー",
        "mobile" => "モバイルメニュー"
    ));
}
add_action("after_setup_theme", "gi_setup");

/**
 * コンテンツ幅設定
 */
function gi_content_width() {
    $GLOBALS["content_width"] = apply_filters("gi_content_width", 1200);
}
add_action("after_setup_theme", "gi_content_width", 0);


