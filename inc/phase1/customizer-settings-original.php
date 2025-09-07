<?php
/**
 * カスタマイザー設定
 * 
 * ヘッダー・フッター色設定、ロゴ、アイコン管理機能を提供します。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * カスタマイザーに設定を追加
 */
function gi_customize_register($wp_customize) {
    
    // ===========================================
    // タスク4: ヘッダー・フッター色設定
    // ===========================================
    
    // ヘッダー設定セクション
    $wp_customize->add_section('gi_header_colors', array(
        'title' => 'ヘッダー色設定',
        'priority' => 30,
        'description' => 'ヘッダーの背景色、テキスト色、リンク色を設定します。',
    ));
    
    // ヘッダー背景色
    $wp_customize->add_setting('gi_header_bg_color', array(
        'default' => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_header_bg_color', array(
        'label' => 'ヘッダー背景色',
        'section' => 'gi_header_colors',
    )));
    
    // ヘッダーテキスト色
    $wp_customize->add_setting('gi_header_text_color', array(
        'default' => '#333333',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_header_text_color', array(
        'label' => 'ヘッダーテキスト色',
        'section' => 'gi_header_colors',
    )));
    
    // ヘッダーリンク色
    $wp_customize->add_setting('gi_header_link_color', array(
        'default' => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_header_link_color', array(
        'label' => 'ヘッダーリンク色',
        'section' => 'gi_header_colors',
    )));
    
    // ヘッダーリンクホバー色
    $wp_customize->add_setting('gi_header_link_hover_color', array(
        'default' => '#005177',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_header_link_hover_color', array(
        'label' => 'ヘッダーリンクホバー色',
        'section' => 'gi_header_colors',
    )));
    
    // フッター設定セクション
    $wp_customize->add_section('gi_footer_colors', array(
        'title' => 'フッター色設定',
        'priority' => 31,
        'description' => 'フッターの背景色とテキスト色を設定します。',
    ));
    
    // フッター背景色
    $wp_customize->add_setting('gi_footer_bg_color', array(
        'default' => '#f8f9fa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_footer_bg_color', array(
        'label' => 'フッター背景色',
        'section' => 'gi_footer_colors',
    )));
    
    // フッターテキスト色
    $wp_customize->add_setting('gi_footer_text_color', array(
        'default' => '#6c757d',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_footer_text_color', array(
        'label' => 'フッターテキスト色',
        'section' => 'gi_footer_colors',
    )));
    
    // フッターリンク色
    $wp_customize->add_setting('gi_footer_link_color', array(
        'default' => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_footer_link_color', array(
        'label' => 'フッターリンク色',
        'section' => 'gi_footer_colors',
    )));
    
    // フッターリンクホバー色
    $wp_customize->add_setting('gi_footer_link_hover_color', array(
        'default' => '#005177',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_footer_link_hover_color', array(
        'label' => 'フッターリンクホバー色',
        'section' => 'gi_footer_colors',
    )));
    
    // アクセント色
    $wp_customize->add_setting('gi_accent_color', array(
        'default' => '#10b981',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_accent_color', array(
        'label' => 'アクセント色',
        'section' => 'gi_header_colors',
        'description' => 'ボタンや強調表示に使用される色です。',
    )));
    
    // ===========================================
    // タスク5: ロゴ・サイトアイデンティティ設定
    // ===========================================
    
    $wp_customize->add_section('gi_logo_settings', array(
        'title' => 'ロゴ・サイトアイデンティティ',
        'priority' => 25,
        'description' => 'サイトのロゴとファビコンを設定します。',
    ));
    
    // メインロゴ
    $wp_customize->add_setting('gi_main_logo', array(
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'gi_main_logo', array(
        'label' => 'メインロゴ（ヘッダー用）',
        'section' => 'gi_logo_settings',
        'mime_type' => 'image',
    )));
    
    // フッターロゴ
    $wp_customize->add_setting('gi_footer_logo', array(
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'gi_footer_logo', array(
        'label' => 'フッターロゴ',
        'section' => 'gi_logo_settings',
        'mime_type' => 'image',
    )));
    
    // モバイル用ロゴ
    $wp_customize->add_setting('gi_mobile_logo', array(
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'gi_mobile_logo', array(
        'label' => 'モバイル用ロゴ',
        'section' => 'gi_logo_settings',
        'mime_type' => 'image',
    )));
    
    // ファビコン
    $wp_customize->add_setting('gi_favicon', array(
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'gi_favicon', array(
        'label' => 'ファビコン',
        'section' => 'gi_logo_settings',
        'mime_type' => 'image',
        'description' => '推奨サイズ: 32x32px または 16x16px',
    )));
    
    // ロゴサイズ設定
    $wp_customize->add_setting('gi_logo_width', array(
        'default' => '200',
        'sanitize_callback' => 'absint',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control('gi_logo_width', array(
        'label' => 'ロゴ幅（px）',
        'section' => 'gi_logo_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 50,
            'max' => 500,
            'step' => 10,
        ),
    ));
    
    // ロゴ位置
    $wp_customize->add_setting('gi_logo_position', array(
        'default' => 'left',
        'sanitize_callback' => 'gi_sanitize_logo_position',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control('gi_logo_position', array(
        'label' => 'ロゴ位置',
        'section' => 'gi_logo_settings',
        'type' => 'select',
        'choices' => array(
            'left' => '左寄せ',
            'center' => '中央',
            'right' => '右寄せ',
        ),
    ));
    
    // ===========================================
    // 絵文字・アイコン置換セクション
    // ===========================================
    
    $wp_customize->add_section('gi_icon_settings', array(
        'title' => '絵文字・アイコン設定',
        'priority' => 26,
        'description' => 'サイト内で使用される絵文字をカスタム画像に置き換えます。',
    ));
    
    // 診断関連アイコン
    $diagnosis_icons = array(
        'diagnosis_main' => '診断メインアイコン（🔍の置換）',
        'step_icon' => 'ステップアイコン（📝の置換）',
        'result_icon' => '結果アイコン（✨の置換）',
    );
    
    foreach ($diagnosis_icons as $key => $label) {
        $wp_customize->add_setting("gi_icon_{$key}", array(
            'sanitize_callback' => 'esc_url_raw',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_icon_{$key}", array(
            'label' => $label,
            'section' => 'gi_icon_settings',
            'mime_type' => 'image',
        )));
    }
    
    // ステータスアイコン
    $status_icons = array(
        'featured' => '注目アイコン（🔥の置換）',
        'new' => '新着アイコン（⚡の置換）',
        'hot' => '人気アイコン（💥の置換）',
        'urgent' => '締切間近アイコン（⏰の置換）',
        'success' => '成功アイコン（✅の置換）',
        'warning' => '警告アイコン（⚠️の置換）',
    );
    
    foreach ($status_icons as $key => $label) {
        $wp_customize->add_setting("gi_icon_{$key}", array(
            'sanitize_callback' => 'esc_url_raw',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_icon_{$key}", array(
            'label' => $label,
            'section' => 'gi_icon_settings',
            'mime_type' => 'image',
        )));
    }
    
    // 機能アイコン
    $function_icons = array(
        'search' => '検索アイコン（🔍の置換）',
        'filter' => 'フィルターアイコン（📊の置換）',
        'favorite' => 'お気に入りアイコン（❤️の置換）',
        'share' => '共有アイコン（📤の置換）',
        'download' => 'ダウンロードアイコン（📥の置換）',
        'info' => '情報アイコン（💡の置換）',
        'help' => 'ヘルプアイコン（❓の置換）',
    );
    
    foreach ($function_icons as $key => $label) {
        $wp_customize->add_setting("gi_icon_{$key}", array(
            'sanitize_callback' => 'esc_url_raw',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_icon_{$key}", array(
            'label' => $label,
            'section' => 'gi_icon_settings',
            'mime_type' => 'image',
        )));
    }
    
    // カテゴリーアイコン
    $category_icons = array(
        'it_digital' => 'IT・デジタルアイコン（💻の置換）',
        'manufacturing' => 'ものづくりアイコン（🏭の置換）',
        'startup' => 'スタートアップアイコン（🚀の置換）',
        'regional' => '地域活性化アイコン（🏘️の置換）',
        'environment' => '環境アイコン（🌱の置換）',
        'welfare' => '福祉・医療アイコン（🏥の置換）',
        'education' => '教育アイコン（📚の置換）',
        'tourism' => '観光・文化アイコン（🗾の置換）',
    );
    
    foreach ($category_icons as $key => $label) {
        $wp_customize->add_setting("gi_icon_{$key}", array(
            'sanitize_callback' => 'esc_url_raw',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_icon_{$key}", array(
            'label' => $label,
            'section' => 'gi_icon_settings',
            'mime_type' => 'image',
        )));
    }
    
    // UI要素アイコン
    $ui_icons = array(
        'menu_hamburger' => 'ハンバーガーメニュー（☰の置換）',
        'close' => '閉じるアイコン（✕の置換）',
        'expand' => '展開アイコン（📈の置換）',
        'collapse' => '折りたたみアイコン（📉の置換）',
        'external_link' => '外部リンクアイコン（🔗の置換）',
    );
    
    foreach ($ui_icons as $key => $label) {
        $wp_customize->add_setting("gi_icon_{$key}", array(
            'sanitize_callback' => 'esc_url_raw',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, "gi_icon_{$key}", array(
            'label' => $label,
            'section' => 'gi_icon_settings',
            'mime_type' => 'image',
        )));
    }
}
add_action('customize_register', 'gi_customize_register');

/**
 * ロゴ位置のサニタイズ
 */
function gi_sanitize_logo_position($input) {
    $valid = array('left', 'center', 'right');
    return in_array($input, $valid, true) ? $input : 'left';
}

/**
 * カスタマイザーCSS出力
 */
function gi_customize_css() {
    // キャッシュ確認
    $cached_css = get_transient('gi_customizer_css');
    if ($cached_css !== false && !is_customize_preview()) {
        echo $cached_css;
        return;
    }
    
    // 色設定取得
    $header_bg = get_theme_mod('gi_header_bg_color', '#ffffff');
    $header_text = get_theme_mod('gi_header_text_color', '#333333');
    $header_link = get_theme_mod('gi_header_link_color', '#0073aa');
    $header_link_hover = get_theme_mod('gi_header_link_hover_color', '#005177');
    
    $footer_bg = get_theme_mod('gi_footer_bg_color', '#f8f9fa');
    $footer_text = get_theme_mod('gi_footer_text_color', '#6c757d');
    $footer_link = get_theme_mod('gi_footer_link_color', '#0073aa');
    $footer_link_hover = get_theme_mod('gi_footer_link_hover_color', '#005177');
    
    $accent_color = get_theme_mod('gi_accent_color', '#10b981');
    $logo_width = get_theme_mod('gi_logo_width', '200');
    $logo_position = get_theme_mod('gi_logo_position', 'left');
    
    // ロゴ位置のCSSマッピング
    $logo_alignment = array(
        'left' => 'flex-start',
        'center' => 'center',
        'right' => 'flex-end',
    );
    $logo_align = isset($logo_alignment[$logo_position]) ? $logo_alignment[$logo_position] : 'flex-start';
    
    ob_start();
    ?>
    <style type="text/css" id="gi-customizer-css">
        /* ヘッダー色設定 */
        .site-header,
        header.header,
        #masthead {
            background-color: <?php echo esc_attr($header_bg); ?>;
            color: <?php echo esc_attr($header_text); ?>;
        }
        
        .site-header a,
        header.header a,
        #masthead a {
            color: <?php echo esc_attr($header_link); ?>;
        }
        
        .site-header a:hover,
        header.header a:hover,
        #masthead a:hover {
            color: <?php echo esc_attr($header_link_hover); ?>;
        }
        
        /* フッター色設定 */
        .site-footer,
        footer.footer,
        #colophon {
            background-color: <?php echo esc_attr($footer_bg); ?>;
            color: <?php echo esc_attr($footer_text); ?>;
        }
        
        .site-footer a,
        footer.footer a,
        #colophon a {
            color: <?php echo esc_attr($footer_link); ?>;
        }
        
        .site-footer a:hover,
        footer.footer a:hover,
        #colophon a:hover {
            color: <?php echo esc_attr($footer_link_hover); ?>;
        }
        
        /* アクセント色 */
        .btn-primary,
        .button-primary,
        .wp-block-button__link,
        .ai-diagnosis-start {
            background-color: <?php echo esc_attr($accent_color); ?>;
            border-color: <?php echo esc_attr($accent_color); ?>;
        }
        
        .btn-primary:hover,
        .button-primary:hover,
        .wp-block-button__link:hover,
        .ai-diagnosis-start:hover {
            background-color: <?php echo esc_attr(gi_darken_color($accent_color, 10)); ?>;
            border-color: <?php echo esc_attr(gi_darken_color($accent_color, 10)); ?>;
        }
        
        .text-accent,
        .highlight {
            color: <?php echo esc_attr($accent_color); ?>;
        }
        
        /* ロゴ設定 */
        .gi-custom-logo {
            max-width: <?php echo absint($logo_width); ?>px;
            height: auto;
        }
        
        .logo-container,
        .site-branding {
            display: flex;
            justify-content: <?php echo esc_attr($logo_align); ?>;
            align-items: center;
        }
        
        /* カスタムアイコン */
        .gi-custom-icon {
            display: inline-block;
            vertical-align: middle;
            max-width: 100%;
            height: auto;
        }
        
        .gi-custom-icon.inline-icon {
            width: 1.2em;
            height: 1.2em;
            margin: 0 0.2em;
        }
        
        .gi-emoji-fallback {
            font-size: 1.2em;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .gi-custom-logo {
                max-width: <?php echo absint($logo_width * 0.75); ?>px;
            }
            
            .gi-custom-logo.mobile-logo {
                display: block;
            }
            
            .gi-custom-logo.header-logo {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .gi-custom-logo.mobile-logo {
                display: none;
            }
            
            .gi-custom-logo.header-logo {
                display: block;
            }
        }
    </style>
    <?php
    
    $css = ob_get_clean();
    
    // キャッシュ保存（1時間）
    if (!is_customize_preview()) {
        set_transient('gi_customizer_css', $css, HOUR_IN_SECONDS);
    }
    
    echo $css;
}
add_action('wp_head', 'gi_customize_css', 100);

/**
 * 色を暗くする関数
 */
function gi_darken_color($hex, $percent) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r - ($r * $percent / 100)));
    $g = max(0, min(255, $g - ($g * $percent / 100)));
    $b = max(0, min(255, $b - ($b * $percent / 100)));
    
    return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
}

/**
 * カスタマイザーのライブプレビュー用JavaScript
 */
function gi_customize_preview_js() {
    wp_enqueue_script(
        'gi-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array('customize-preview', 'jquery'),
        '1.0.0',
        true
    );
}
add_action('customize_preview_init', 'gi_customize_preview_js');

/**
 * カスタマイザーCSScache削除
 */
function gi_clear_customizer_cache() {
    delete_transient('gi_customizer_css');
}
add_action('customize_save_after', 'gi_clear_customizer_cache');