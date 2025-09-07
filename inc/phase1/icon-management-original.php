<?php
/**
 * アイコン管理機能
 * 
 * カスタマイザーで設定されたアイコンの表示と絵文字の置換機能を提供します。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * カスタマイザーで設定されたアイコンを取得・表示する関数
 * 
 * @param string $icon_type アイコンタイプ
 * @param string $fallback_emoji フォールバック絵文字
 * @param string $class 追加クラス
 * @param int $size アイコンサイズ（px）
 * @return string HTML出力
 */
function gi_get_custom_icon($icon_type, $fallback_emoji = '', $class = '', $size = 24) {
    $custom_icon_url = get_theme_mod("gi_icon_{$icon_type}");
    
    if ($custom_icon_url) {
        // カスタム画像が設定されている場合
        $attachment_id = attachment_url_to_postid($custom_icon_url);
        $alt_text = '';
        
        if ($attachment_id) {
            $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        }
        
        if (empty($alt_text)) {
            $alt_text = str_replace('_', ' ', $icon_type);
        }
        
        return sprintf(
            '<img src="%s" alt="%s" class="gi-custom-icon gi-icon-%s %s" width="%s" height="%s" loading="lazy">',
            esc_url($custom_icon_url),
            esc_attr($alt_text),
            esc_attr($icon_type),
            esc_attr($class),
            absint($size),
            absint($size)
        );
    } elseif ($fallback_emoji) {
        // フォールバック絵文字を表示
        return sprintf(
            '<span class="gi-emoji-fallback gi-emoji-%s %s" role="img" aria-label="%s">%s</span>',
            esc_attr($icon_type),
            esc_attr($class),
            esc_attr(str_replace('_', ' ', $icon_type)),
            esc_html($fallback_emoji)
        );
    }
    
    return '';
}

/**
 * テキスト内の絵文字を自動でカスタムアイコンに置換
 * 
 * @param string $content コンテンツ
 * @return string 置換後のコンテンツ
 */
function gi_replace_emoji_with_custom_icons($content) {
    // 絵文字とアイコンタイプのマッピング
    $emoji_map = array(
        '🔍' => array('type' => 'search', 'size' => 20),
        '📝' => array('type' => 'step_icon', 'size' => 20),
        '✨' => array('type' => 'result_icon', 'size' => 20),
        '🔥' => array('type' => 'featured', 'size' => 20),
        '⚡' => array('type' => 'new', 'size' => 20),
        '💥' => array('type' => 'hot', 'size' => 20),
        '⏰' => array('type' => 'urgent', 'size' => 20),
        '✅' => array('type' => 'success', 'size' => 20),
        '⚠️' => array('type' => 'warning', 'size' => 20),
        '💡' => array('type' => 'info', 'size' => 20),
        '❓' => array('type' => 'help', 'size' => 20),
        '❤️' => array('type' => 'favorite', 'size' => 20),
        '📤' => array('type' => 'share', 'size' => 20),
        '📥' => array('type' => 'download', 'size' => 20),
        '📊' => array('type' => 'filter', 'size' => 20),
        '💻' => array('type' => 'it_digital', 'size' => 20),
        '🏭' => array('type' => 'manufacturing', 'size' => 20),
        '🚀' => array('type' => 'startup', 'size' => 20),
        '🏘️' => array('type' => 'regional', 'size' => 20),
        '🌱' => array('type' => 'environment', 'size' => 20),
        '🏥' => array('type' => 'welfare', 'size' => 20),
        '📚' => array('type' => 'education', 'size' => 20),
        '🗾' => array('type' => 'tourism', 'size' => 20),
        '☰' => array('type' => 'menu_hamburger', 'size' => 24),
        '✕' => array('type' => 'close', 'size' => 20),
        '📈' => array('type' => 'expand', 'size' => 20),
        '📉' => array('type' => 'collapse', 'size' => 20),
        '🔗' => array('type' => 'external_link', 'size' => 16),
        '📍' => array('type' => 'location', 'size' => 18),
    );
    
    // 置換を実行するかどうかのフラグ
    $replace_emojis = apply_filters('gi_replace_emojis', true);
    
    if (!$replace_emojis) {
        return $content;
    }
    
    foreach ($emoji_map as $emoji => $config) {
        $custom_icon = gi_get_custom_icon(
            $config['type'], 
            $emoji, 
            'inline-icon', 
            $config['size']
        );
        
        if (!empty($custom_icon)) {
            $content = str_replace($emoji, $custom_icon, $content);
        }
    }
    
    return $content;
}

// コンテンツ出力時に自動置換を適用（優先度を調整）
add_filter('the_content', 'gi_replace_emoji_with_custom_icons', 15);
add_filter('the_title', 'gi_replace_emoji_with_custom_icons', 15);
add_filter('the_excerpt', 'gi_replace_emoji_with_custom_icons', 15);
add_filter('widget_text', 'gi_replace_emoji_with_custom_icons', 15);

/**
 * カスタムロゴを表示する関数
 * 
 * @param string $location ロゴの位置（header/footer/mobile）
 * @param string $class 追加クラス
 * @param bool $link_home ホームへのリンクを付けるか
 * @return void
 */
function gi_display_custom_logo($location = 'header', $class = '', $link_home = true) {
    $logo_setting = '';
    $default_class = 'gi-custom-logo';
    
    switch ($location) {
        case 'header':
            $logo_setting = 'gi_main_logo';
            $default_class .= ' header-logo';
            break;
        case 'footer':
            $logo_setting = 'gi_footer_logo';
            $default_class .= ' footer-logo';
            break;
        case 'mobile':
            $logo_setting = 'gi_mobile_logo';
            $default_class .= ' mobile-logo';
            break;
    }
    
    // カスタマイザーからロゴURLを取得
    $logo_url = get_theme_mod($logo_setting);
    
    // メディアIDからURLを取得する場合
    if (is_numeric($logo_url)) {
        $logo_url = wp_get_attachment_url($logo_url);
    }
    
    $logo_width = get_theme_mod('gi_logo_width', '200');
    $site_name = get_bloginfo('name');
    $site_url = home_url('/');
    
    ob_start();
    
    if ($link_home) {
        echo '<a href="' . esc_url($site_url) . '" rel="home" class="logo-link">';
    }
    
    if ($logo_url) {
        $attachment_id = attachment_url_to_postid($logo_url);
        $logo_alt = '';
        
        if ($attachment_id) {
            $logo_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        }
        
        if (empty($logo_alt)) {
            $logo_alt = $site_name;
        }
        
        printf(
            '<img src="%s" alt="%s" class="%s %s" style="max-width: %spx; height: auto;" loading="lazy">',
            esc_url($logo_url),
            esc_attr($logo_alt),
            esc_attr($default_class),
            esc_attr($class),
            absint($logo_width)
        );
    } else {
        // フォールバック: サイト名をテキストで表示
        printf(
            '<span class="site-title-text %s">%s</span>',
            esc_attr($class),
            esc_html($site_name)
        );
    }
    
    if ($link_home) {
        echo '</a>';
    }
    
    echo ob_get_clean();
}

/**
 * ファビコンを動的に設定
 */
function gi_custom_favicon() {
    $favicon_url = get_theme_mod('gi_favicon');
    
    if ($favicon_url) {
        // メディアIDからURLを取得する場合
        if (is_numeric($favicon_url)) {
            $favicon_url = wp_get_attachment_url($favicon_url);
        }
        
        if ($favicon_url) {
            $mime_type = wp_check_filetype($favicon_url);
            $type = isset($mime_type['type']) ? $mime_type['type'] : 'image/x-icon';
            
            echo '<link rel="icon" type="' . esc_attr($type) . '" href="' . esc_url($favicon_url) . '">' . "\n";
            echo '<link rel="shortcut icon" type="' . esc_attr($type) . '" href="' . esc_url($favicon_url) . '">' . "\n";
            
            // Apple Touch Icon
            echo '<link rel="apple-touch-icon" href="' . esc_url($favicon_url) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'gi_custom_favicon', 5);
add_action('admin_head', 'gi_custom_favicon', 5);

/**
 * カテゴリー別アイコン取得関数
 * 
 * @param string $category_slug カテゴリースラッグ
 * @param int $size アイコンサイズ
 * @return string アイコンHTML
 */
function gi_get_category_icon($category_slug, $size = 24) {
    $icon_map = array(
        'it-digital' => array('icon' => 'it_digital', 'emoji' => '💻'),
        'manufacturing' => array('icon' => 'manufacturing', 'emoji' => '🏭'),
        'startup' => array('icon' => 'startup', 'emoji' => '🚀'),
        'regional' => array('icon' => 'regional', 'emoji' => '🏘️'),
        'environment' => array('icon' => 'environment', 'emoji' => '🌱'),
        'welfare' => array('icon' => 'welfare', 'emoji' => '🏥'),
        'medical-welfare' => array('icon' => 'welfare', 'emoji' => '🏥'),
        'education' => array('icon' => 'education', 'emoji' => '📚'),
        'tourism' => array('icon' => 'tourism', 'emoji' => '🗾'),
        'agriculture' => array('icon' => 'agriculture', 'emoji' => '🌾'),
        'retail-service' => array('icon' => 'retail', 'emoji' => '🏪'),
        'construction' => array('icon' => 'construction', 'emoji' => '🏗️'),
    );
    
    $category_data = isset($icon_map[$category_slug]) ? $icon_map[$category_slug] : null;
    
    if ($category_data) {
        return gi_get_custom_icon(
            $category_data['icon'],
            $category_data['emoji'],
            'category-icon',
            $size
        );
    }
    
    // デフォルトアイコン
    return gi_get_custom_icon('default', '📁', 'category-icon', $size);
}

/**
 * ステータス別アイコン取得関数
 * 
 * @param string $status ステータス
 * @param int $size アイコンサイズ
 * @return string アイコンHTML
 */
function gi_get_status_icon($status, $size = 20) {
    $icon_map = array(
        'featured' => array('icon' => 'featured', 'emoji' => '🔥'),
        'new' => array('icon' => 'new', 'emoji' => '⚡'),
        'hot' => array('icon' => 'hot', 'emoji' => '💥'),
        'urgent' => array('icon' => 'urgent', 'emoji' => '⏰'),
        'active' => array('icon' => 'success', 'emoji' => '✅'),
        'upcoming' => array('icon' => 'info', 'emoji' => '💡'),
        'closed' => array('icon' => 'warning', 'emoji' => '⚠️'),
    );
    
    $status_data = isset($icon_map[$status]) ? $icon_map[$status] : null;
    
    if ($status_data) {
        return gi_get_custom_icon(
            $status_data['icon'],
            $status_data['emoji'],
            'status-icon',
            $size
        );
    }
    
    return '';
}

/**
 * アイコンのインラインCSS生成
 */
function gi_icon_inline_styles() {
    ?>
    <style type="text/css">
        /* カスタムアイコンの基本スタイル */
        .gi-custom-icon {
            display: inline-block;
            vertical-align: middle;
            max-width: 100%;
            height: auto;
            object-fit: contain;
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
            line-height: 1;
        }
        
        /* カテゴリーアイコン */
        .gi-custom-icon.category-icon {
            margin-right: 0.5em;
        }
        
        /* ステータスアイコン */
        .gi-custom-icon.status-icon {
            margin-right: 0.3em;
        }
        
        /* ロゴスタイル */
        .gi-custom-logo {
            display: block;
            max-width: 100%;
            height: auto;
        }
        
        .gi-custom-logo.header-logo {
            max-height: 60px;
        }
        
        .gi-custom-logo.footer-logo {
            max-height: 40px;
            opacity: 0.8;
        }
        
        .gi-custom-logo.mobile-logo {
            max-height: 45px;
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .gi-custom-logo.header-logo {
                max-height: 45px;
            }
            
            .gi-custom-icon.inline-icon {
                width: 1em;
                height: 1em;
                margin: 0 0.1em;
            }
            
            .gi-custom-logo.mobile-logo {
                display: block;
            }
            
            .gi-custom-logo.header-logo:not(.mobile-logo) {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .gi-custom-logo.mobile-logo {
                display: none;
            }
        }
        
        /* アニメーション */
        .gi-custom-icon {
            transition: transform 0.2s ease;
        }
        
        .gi-custom-icon:hover {
            transform: scale(1.1);
        }
        
        /* ダークモード対応 */
        @media (prefers-color-scheme: dark) {
            .gi-custom-logo.footer-logo {
                filter: brightness(0.9);
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'gi_icon_inline_styles', 15);

/**
 * 管理画面でのアイコンプレビュー
 */
function gi_admin_icon_preview_script() {
    if (!is_customize_preview()) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function($) {
        wp.customize.bind('ready', function() {
            // アイコン設定が変更されたときのプレビュー
            <?php
            $icon_types = array(
                'diagnosis_main', 'step_icon', 'result_icon',
                'featured', 'new', 'hot', 'urgent', 'success', 'warning',
                'search', 'filter', 'favorite', 'share', 'download', 'info', 'help',
                'it_digital', 'manufacturing', 'startup', 'regional',
                'environment', 'welfare', 'education', 'tourism',
                'menu_hamburger', 'close', 'expand', 'collapse', 'external_link'
            );
            
            foreach ($icon_types as $icon_type) {
                ?>
                wp.customize('gi_icon_<?php echo $icon_type; ?>', function(value) {
                    value.bind(function(newval) {
                        if (newval) {
                            $('.gi-icon-<?php echo $icon_type; ?>').each(function() {
                                $(this).attr('src', newval);
                            });
                        }
                    });
                });
                <?php
            }
            ?>
        });
    })(jQuery);
    </script>
    <?php
}
add_action('customize_preview_init', 'gi_admin_icon_preview_script');