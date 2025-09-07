<?php
/**
 * カスタマイザー設定
 * 
 * このファイルでは、WordPressのカスタマイザーを通じてテーマオプションを設定します。
 * ヒーローセクション、フッター、SNSリンク、統計、特徴、CTAなどの設定が含まれます。
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * カスタマイザー設定
 */
function gi_customize_register($wp_customize) {
    // ヒーローセクション設定
    $wp_customize->add_section("gi_hero_section", array(
        "title" => "ヒーローセクション",
        "priority" => 30,
        "description" => "フロントページのヒーローセクションを設定します"
    ));
    
    // ヒーロータイトル
    $wp_customize->add_setting("gi_hero_title", array(
        "default" => "AI が提案する助成金・補助金情報サイト",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control("gi_hero_title", array(
        "label" => "メインタイトル",
        "section" => "gi_hero_section",
        "type" => "text"
    ));
    
    // ヒーローサブタイトル
    $wp_customize->add_setting("gi_hero_subtitle", array(
        "default" => "最先端のAI技術で、あなたのビジネスに最適な助成金・補助金を瞬時に発見。",
        "sanitize_callback" => "sanitize_textarea_field",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control("gi_hero_subtitle", array(
        "label" => "サブタイトル",
        "section" => "gi_hero_section",
        "type" => "textarea"
    ));
    
    // ヒーローロゴ
    $wp_customize->add_setting("gi_hero_logo", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_hero_logo", array(
        "label" => "ヒーローロゴ",
        "section" => "gi_hero_section",
        "mime_type" => "image"
    )));
    
    // ヒーロー背景画像
    $wp_customize->add_setting("gi_hero_background_image", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_hero_background_image", array(
        "label" => "背景画像",
        "section" => "gi_hero_section",
        "mime_type" => "image"
    )));
    
    // ヒーローボタンテキスト
    $wp_customize->add_setting("gi_hero_button_text", array(
        "default" => "助成金を探す",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control("gi_hero_button_text", array(
        "label" => "ボタンテキスト",
        "section" => "gi_hero_section",
        "type" => "text"
    ));
    
    // ヒーローボタンURL
    $wp_customize->add_setting("gi_hero_button_url", array(
        "default" => "#",
        "sanitize_callback" => "esc_url_raw",
        "transport" => "postMessage"
    ));
    
    $wp_customize->add_control("gi_hero_button_url", array(
        "label" => "ボタンURL",
        "section" => "gi_hero_section",
        "type" => "url"
    ));

    /**
     * ★★★ 統計セクション設定 ★★★
     */
    $wp_customize->add_section("grant_insight_stats_section", array(
        "title" => __("統計セクション", "grant-insight"),
        "priority" => 45,
        "description" => __("実績数値を表示するセクションの設定を行います。", "grant-insight"),
    ));

    // 統計セクション表示切り替え
    $wp_customize->add_setting("stats_section_enabled", array(
        "default" => true,
        "sanitize_callback" => "wp_validate_boolean",
    ));

    $wp_customize->add_control("stats_section_enabled", array(
        "label" => __("統計セクションを表示", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "checkbox",
    ));

    // 統計項目1
    $wp_customize->add_setting("stats_1_number", array(
        "default" => "1,200",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_1_number", array(
        "label" => __("統計1 - 数値", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("stats_1_label", array(
        "default" => __("掲載助成金数", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_1_label", array(
        "label" => __("統計1 - ラベル", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    // 統計項目2
    $wp_customize->add_setting("stats_2_number", array(
        "default" => "15,000",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_2_number", array(
        "label" => __("統計2 - 数値", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("stats_2_label", array(
        "default" => __("利用企業数", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_2_label", array(
        "label" => __("統計2 - ラベル", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    // 統計項目3
    $wp_customize->add_setting("stats_3_number", array(
        "default" => "98%",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_3_number", array(
        "label" => __("統計3 - 数値", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("stats_3_label", array(
        "default" => __("満足度", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_3_label", array(
        "label" => __("統計3 - ラベル", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    // 統計項目4
    $wp_customize->add_setting("stats_4_number", array(
        "default" => "24時間",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_4_number", array(
        "label" => __("統計4 - 数値", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("stats_4_label", array(
        "default" => __("サポート対応", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("stats_4_label", array(
        "label" => __("統計4 - ラベル", "grant-insight"),
        "section" => "grant_insight_stats_section",
        "type" => "text",
    ));

    /**
     * ★★★ 特徴セクション設定 ★★★
     */
    $wp_customize->add_section("grant_insight_features_section", array(
        "title" => __("特徴セクション", "grant-insight"),
        "priority" => 50,
        "description" => __("サービスの特徴を紹介するセクションの設定を行います。", "grant-insight"),
    ));

    // 特徴セクションタイトル
    $wp_customize->add_setting("features_section_title", array(
        "default" => __("Grant Insightが選ばれる理由", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("features_section_title", array(
        "label" => __("セクションタイトル", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    // 特徴1
    $wp_customize->add_setting("feature_1_icon", array(
        "default" => "fas fa-search",
        "sanitize_callback" => "sanitize_text_field",
    ));

    $wp_customize->add_control("feature_1_icon", array(
        "label" => __("特徴1 - アイコン (Font Awesomeクラス)", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
        "description" => __("例: fas fa-search", "grant-insight"),
    ));

    $wp_customize->add_setting("feature_1_title", array(
        "default" => __("簡単検索", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_1_title", array(
        "label" => __("特徴1 - タイトル", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("feature_1_description", array(
        "default" => __("業種や条件から最適な助成金を素早く見つけることができます。", "grant-insight"),
        "sanitize_callback" => "sanitize_textarea_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_1_description", array(
        "label" => __("特徴1 - 説明", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "textarea",
    ));

    // 特徴2
    $wp_customize->add_setting("feature_2_icon", array(
        "default" => "fas fa-robot",
        "sanitize_callback" => "sanitize_text_field",
    ));

    $wp_customize->add_control("feature_2_icon", array(
        "label" => __("特徴2 - アイコン", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("feature_2_title", array(
        "default" => __("AI相談", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_2_title", array(
        "label" => __("特徴2 - タイトル", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("feature_2_description", array(
        "default" => __("AIアシスタントが24時間いつでもあなたの質問にお答えします。", "grant-insight"),
        "sanitize_callback" => "sanitize_textarea_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_2_description", array(
        "label" => __("特徴2 - 説明", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "textarea",
    ));

    // 特徴3
    $wp_customize->add_setting("feature_3_icon", array(
        "default" => "fas fa-clock",
        "sanitize_callback" => "sanitize_text_field",
    ));

    $wp_customize->add_control("feature_3_icon", array(
        "label" => __("特徴3 - アイコン", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("feature_3_title", array(
        "default" => __("最新情報", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_3_title", array(
        "label" => __("特徴3 - タイトル", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("feature_3_description", array(
        "default" => __("常に最新の助成金情報を提供し、申請期限もしっかりお知らせします。", "grant-insight"),
        "sanitize_callback" => "sanitize_textarea_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("feature_3_description", array(
        "label" => __("特徴3 - 説明", "grant-insight"),
        "section" => "grant_insight_features_section",
        "type" => "textarea",
    ));

    /**
     * ★★★ CTA (Call to Action) セクション設定 ★★★
     */
    $wp_customize->add_section("grant_insight_cta_section", array(
        "title" => __("CTAセクション", "grant-insight"),
        "priority" => 55,
        "description" => __("行動を促すセクションの設定を行います。", "grant-insight"),
    ));

    // CTA タイトル
    $wp_customize->add_setting("cta_title", array(
        "default" => __("今すぐ助成金を探してみませんか？", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("cta_title", array(
        "label" => __("CTAタイトル", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "text",
    ));

    // CTA サブタイトル
    $wp_customize->add_setting("cta_subtitle", array(
        "default" => __("あなたの事業に最適な支援制度を見つけて、成長を加速させましょう。", "grant-insight"),
        "sanitize_callback" => "sanitize_textarea_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("cta_subtitle", array(
        "label" => __("CTAサブタイトル", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "textarea",
    ));

    // CTA プライマリボタン
    $wp_customize->add_setting("cta_primary_button_text", array(
        "default" => __("無料で始める", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("cta_primary_button_text", array(
        "label" => __("プライマリボタンテキスト", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("cta_primary_button_url", array(
        "default" => "/grants/",
        "sanitize_callback" => "esc_url_raw",
    ));

    $wp_customize->add_control("cta_primary_button_url", array(
        "label" => __("プライマリボタンURL", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "url",
    ));

    // CTA セカンダリボタン
    $wp_customize->add_setting("cta_secondary_button_text", array(
        "default" => __("詳しく見る", "grant-insight"),
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage",
    ));

    $wp_customize->add_control("cta_secondary_button_text", array(
        "label" => __("セカンダリボタンテキスト", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "text",
    ));

    $wp_customize->add_setting("cta_secondary_button_url", array(
        "default" => "/about/",
        "sanitize_callback" => "esc_url_raw",
    ));

    $wp_customize->add_control("cta_secondary_button_url", array(
        "label" => __("セカンダリボタンURL", "grant-insight"),
        "section" => "grant_insight_cta_section",
        "type" => "url",
    ));

    // CTA 背景色
    $wp_customize->add_setting("cta_background_color", array(
        "default" => "#2563eb",
        "sanitize_callback" => "sanitize_hex_color",
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, "cta_background_color", array(
        "label" => __("背景色", "grant-insight"),
        "section" => "grant_insight_cta_section",
    )));

    // フッター設定
    $wp_customize->add_section("gi_footer_section", array(
        "title" => "フッター設定",
        "priority" => 160,
        "description" => "フッターのコンテンツを設定します"
    ));

    // フッターコピーライト
    $wp_customize->add_setting("gi_footer_copyright", array(
        "default" => "© 2023 Grant Insight Perfect. All rights reserved.",
        "sanitize_callback" => "sanitize_text_field",
        "transport" => "postMessage"
    ));

    $wp_customize->add_control("gi_footer_copyright", array(
        "label" => "コピーライトテキスト",
        "section" => "gi_footer_section",
        "type" => "text"
    ));

    // SNS設定
    $wp_customize->add_section("gi_social_section", array(
        "title" => "SNS設定",
        "priority" => 170,
        "description" => "ソーシャルメディアのリンクを設定します"
    ));

    // Twitter URL
    $wp_customize->add_setting("gi_social_twitter", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw"
    ));
    $wp_customize->add_control("gi_social_twitter", array(
        "label" => "Twitter URL",
        "section" => "gi_social_section",
        "type" => "url"
    ));

    // Facebook URL
    $wp_customize->add_setting("gi_social_facebook", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw"
    ));
    $wp_customize->add_control("gi_social_facebook", array(
        "label" => "Facebook URL",
        "section" => "gi_social_section",
        "type" => "url"
    ));

    // Instagram URL
    $wp_customize->add_setting("gi_social_instagram", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw"
    ));
    $wp_customize->add_control("gi_social_instagram", array(
        "label" => "Instagram URL",
        "section" => "gi_social_section",
        "type" => "url"
    ));

    // LinkedIn URL
    $wp_customize->add_setting("gi_social_linkedin", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw"
    ));
    $wp_customize->add_control("gi_social_linkedin", array(
        "label" => "LinkedIn URL",
        "section" => "gi_social_section",
        "type" => "url"
    ));

    // YouTube URL
    $wp_customize->add_setting("gi_social_youtube", array(
        "default" => "",
        "sanitize_callback" => "esc_url_raw"
    ));
    $wp_customize->add_control("gi_social_youtube", array(
        "label" => "YouTube URL",
        "section" => "gi_social_section",
        "type" => "url"
    ));

    // カスタマイザーのライブプレビュー
    function gi_customize_preview_js() {
        wp_enqueue_script(
            "gi-customizer-preview",
            get_template_directory_uri() . "/assets/js/customizer-preview.js",
            array("jquery", "customize-preview"),
            GI_THEME_VERSION,
            true
        );
    }
    add_action("customize_preview_init", "gi_customize_preview_js");
}
add_action("customize_register", "gi_customize_register");

// カスタムスタイル出力
function gi_output_custom_styles() {
    ?>
    <style type="text/css">
        .hero-overlay {
            opacity: <?php echo esc_attr(get_theme_mod("hero_overlay_opacity", 0.7)); ?>;
        }
        .cta-section {
            background-color: <?php echo esc_attr(get_theme_mod("cta_background_color", "#2563eb")); ?>;
        }
    </style>
    <?php
}
add_action("wp_head", "gi_output_custom_styles");

// sanitize_float関数が未定義の場合に定義
if (!function_exists("sanitize_float")) {
    function sanitize_float($input) {
        return floatval($input);
    }
}


