<?php
/**
 * Grant Insight Perfect - Complete Functions File v6.2 - 修正版前半
 * Tailwind CSS Play CDN完全対応版 - 都道府県・AJAX・カード統合完璧版
 * 
 * @package Grant_Insight_Perfect
 * @version 6.2-perfect-fixed
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// テーマバージョン定数
define('GI_THEME_VERSION', '6.2.1');
define('GI_THEME_PREFIX', 'gi_');

// ACF helpers and local JSON import
if (file_exists(get_template_directory() . '/acf-fields-setup.php')) {
    require_once get_template_directory() . '/acf-fields-setup.php';
}
if (file_exists(get_template_directory() . '/inc/acf-import.php')) {
    require_once get_template_directory() . '/inc/acf-import.php';
}

/**
 * テーマセットアップ
 */
function gi_setup() {
    // テーマサポート追加
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('custom-background');
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    add_theme_support('menus');
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    
    // RSS フィード
    add_theme_support('automatic-feed-links');
    
    // 画像サイズ追加
    add_image_size('gi-card-thumb', 400, 300, true);
    add_image_size('gi-hero-thumb', 800, 600, true);
    add_image_size('gi-tool-logo', 120, 120, true);
    add_image_size('gi-banner', 1200, 400, true);
    
    // 言語ファイル読み込み
    load_theme_textdomain('grant-insight', get_template_directory() . '/languages');
    
    // メニュー登録
    register_nav_menus(array(
        'primary' => 'メインメニュー',
        'footer' => 'フッターメニュー',
        'mobile' => 'モバイルメニュー'
    ));
}
add_action('after_setup_theme', 'gi_setup');

/**
 * コンテンツ幅設定
 */
function gi_content_width() {
    $GLOBALS['content_width'] = apply_filters('gi_content_width', 1200);
}
add_action('after_setup_theme', 'gi_content_width', 0);

/**
 * スクリプト・スタイルの読み込み（完全一元管理）
 */
function gi_enqueue_scripts() {
    // Tailwind CSS Play CDN（一元管理）
    wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com', array(), GI_THEME_VERSION, false);
    
    // Tailwind設定（完全版）
    $tailwind_config = "
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'emerald-custom': {
                        50: '#ecfdf5',
                        100: '#d1fae5',
                        200: '#bbf7d0',
                        300: '#86efac',
                        400: '#4ade80',
                        500: '#10b981',
                        600: '#059669',
                        700: '#047857',
                        800: '#065f46',
                        900: '#064e3b',
                    },
                    'teal-custom': {
                        50: '#f0fdfa',
                        100: '#ccfbf1',
                        200: '#99f6e4',
                        300: '#5eead4',
                        400: '#2dd4bf',
                        500: '#14b8a6',
                        600: '#0d9488',
                        700: '#0f766e',
                        800: '#115e59',
                        900: '#134e4a',
                    },
                    'primary': {
                        50: '#f0f9ff',
                        100: '#e0f2fe',
                        200: '#bae6fd',
                        300: '#7dd3fc',
                        400: '#38bdf8',
                        500: '#0ea5e9',
                        600: '#0284c7',
                        700: '#0369a1',
                        800: '#075985',
                        900: '#0c4a6e',
                    }
                },
                animation: {
                    'fade-in': 'fadeIn 0.6s ease-out',
                    'fade-in-up': 'fadeInUp 0.6s ease-out',
                    'fade-in-down': 'fadeInDown 0.6s ease-out',
                    'fade-in-left': 'fadeInLeft 0.6s ease-out',
                    'fade-in-right': 'fadeInRight 0.6s ease-out',
                    'slide-up': 'slideUp 0.4s ease-out',
                    'slide-down': 'slideDown 0.4s ease-out',
                    'slide-in': 'slideIn 0.3s ease-out',
                    'bounce-gentle': 'bounceGentle 1s ease-out',
                    'pulse-gentle': 'pulseGentle 2s ease-in-out infinite',
                    'float': 'float 3s ease-in-out infinite',
                    'spin-slow': 'spin 20s linear infinite',
                    'wiggle': 'wiggle 0.5s ease-in-out',
                    'scale-in': 'scaleIn 0.5s ease-out',
                    'glow': 'glow 2s ease-in-out infinite alternate'
                },
                keyframes: {
                    fadeIn: {
                        '0%': { opacity: '0' },
                        '100%': { opacity: '1' }
                    },
                    fadeInUp: {
                        '0%': { opacity: '0', transform: 'translateY(20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    fadeInDown: {
                        '0%': { opacity: '0', transform: 'translateY(-20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    fadeInLeft: {
                        '0%': { opacity: '0', transform: 'translateX(-20px)' },
                        '100%': { opacity: '1', transform: 'translateX(0)' }
                    },
                    fadeInRight: {
                        '0%': { opacity: '0', transform: 'translateX(20px)' },
                        '100%': { opacity: '1', transform: 'translateX(0)' }
                    },
                    slideUp: {
                        '0%': { opacity: '0', transform: 'translateY(40px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    slideDown: {
                        '0%': { opacity: '0', transform: 'translateY(-40px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    slideIn: {
                        '0%': { opacity: '0', transform: 'translateX(-10px)' },
                        '100%': { opacity: '1', transform: 'translateX(0)' }
                    },
                    bounceGentle: {
                        '0%': { transform: 'scale(0.95)' },
                        '50%': { transform: 'scale(1.02)' },
                        '100%': { transform: 'scale(1)' }
                    },
                    pulseGentle: {
                        '0%, 100%': { opacity: '1' },
                        '50%': { opacity: '0.8' }
                    },
                    float: {
                        '0%, 100%': { transform: 'translateY(0px)' },
                        '50%': { transform: 'translateY(-10px)' }
                    },
                    wiggle: {
                        '0%, 100%': { transform: 'rotate(-2deg)' },
                        '50%': { transform: 'rotate(2deg)' }
                    },
                    scaleIn: {
                        '0%': { opacity: '0', transform: 'scale(0.9)' },
                        '100%': { opacity: '1', transform: 'scale(1)' }
                    },
                    glow: {
                        '0%': { boxShadow: '0 0 5px rgba(16, 185, 129, 0.2)' },
                        '100%': { boxShadow: '0 0 20px rgba(16, 185, 129, 0.4)' }
                    }
                },
                fontFamily: {
                    'noto': ['Noto Sans JP', 'sans-serif'],
                    'heading': ['Noto Sans JP', 'system-ui', 'sans-serif']
                },
                fontSize: {
                    'xs': ['0.75rem', { lineHeight: '1rem' }],
                    'sm': ['0.875rem', { lineHeight: '1.25rem' }],
                    'base': ['1rem', { lineHeight: '1.5rem' }],
                    'lg': ['1.125rem', { lineHeight: '1.75rem' }],
                    'xl': ['1.25rem', { lineHeight: '1.75rem' }],
                    '2xl': ['1.5rem', { lineHeight: '2rem' }],
                    '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
                    '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
                    '5xl': ['3rem', { lineHeight: '1' }],
                    '6xl': ['3.75rem', { lineHeight: '1' }],
                    '7xl': ['4.5rem', { lineHeight: '1' }],
                    '8xl': ['6rem', { lineHeight: '1' }],
                    '9xl': ['8rem', { lineHeight: '1' }]
                },
                spacing: {
                    '18': '4.5rem',
                    '88': '22rem',
                    '128': '32rem',
                    '144': '36rem'
                },
                boxShadow: {
                    'glow': '0 0 20px rgba(16, 185, 129, 0.3)',
                    'glow-lg': '0 0 30px rgba(16, 185, 129, 0.4)',
                    'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1)',
                    'card-hover': '0 25px 50px -12px rgba(0, 0, 0, 0.25)'
                },
                backdropBlur: {
                    'xs': '2px'
                },
                screens: {
                    'xs': '475px'
                }
            }
        },
        plugins: []
    }";
    wp_add_inline_script('tailwind-cdn', $tailwind_config);
    
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

/**
 * カスタム投稿タイプ登録（完全版）
 */
function gi_register_post_types() {
    // 助成金投稿タイプ
    register_post_type('grant', array(
        'labels' => array(
            'name' => '助成金・補助金',
            'singular_name' => '助成金・補助金',
            'add_new' => '新規追加',
            'add_new_item' => '新しい助成金・補助金を追加',
            'edit_item' => '助成金・補助金を編集',
            'new_item' => '新しい助成金・補助金',
            'view_item' => '助成金・補助金を表示',
            'search_items' => '助成金・補助金を検索',
            'not_found' => '助成金・補助金が見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱に助成金・補助金はありません',
            'all_items' => 'すべての助成金・補助金',
            'menu_name' => '助成金・補助金'
        ),
        'description' => '助成金・補助金情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grants',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-money-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // ツール投稿タイプ
    register_post_type('tool', array(
        'labels' => array(
            'name' => 'ビジネスツール',
            'singular_name' => 'ビジネスツール',
            'add_new' => '新規追加',
            'add_new_item' => '新しいツールを追加',
            'edit_item' => 'ツールを編集',
            'new_item' => '新しいツール',
            'view_item' => 'ツールを表示',
            'search_items' => 'ツールを検索',
            'not_found' => 'ツールが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にツールはありません',
            'all_items' => 'すべてのツール',
            'menu_name' => 'ビジネスツール'
        ),
        'description' => 'ビジネスツール情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'tools',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-admin-tools',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // 成功事例投稿タイプ
    register_post_type('case_study', array(
        'labels' => array(
            'name' => '成功事例',
            'singular_name' => '成功事例',
            'add_new' => '新規追加',
            'add_new_item' => '新しい成功事例を追加',
            'edit_item' => '成功事例を編集',
            'new_item' => '新しい成功事例',
            'view_item' => '成功事例を表示',
            'search_items' => '成功事例を検索',
            'not_found' => '成功事例が見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱に成功事例はありません',
            'all_items' => 'すべての成功事例',
            'menu_name' => '成功事例'
        ),
        'description' => '成功事例情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'case-studies',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 7,
        'menu_icon' => 'dashicons-chart-line',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // ガイド投稿タイプ
    register_post_type('guide', array(
        'labels' => array(
            'name' => 'ガイド・解説',
            'singular_name' => 'ガイド・解説',
            'add_new' => '新規追加',
            'add_new_item' => '新しいガイドを追加',
            'edit_item' => 'ガイドを編集',
            'new_item' => '新しいガイド',
            'view_item' => 'ガイドを表示',
            'search_items' => 'ガイドを検索',
            'not_found' => 'ガイドが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にガイドはありません',
            'all_items' => 'すべてのガイド',
            'menu_name' => 'ガイド・解説'
        ),
        'description' => 'ガイド・解説情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'guides',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 8,
        'menu_icon' => 'dashicons-book-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
    
    // 申請のコツ投稿タイプ
    register_post_type('grant_tip', array(
        'labels' => array(
            'name' => '申請のコツ',
            'singular_name' => '申請のコツ',
            'add_new' => '新規追加',
            'add_new_item' => '新しいコツを追加',
            'edit_item' => 'コツを編集',
            'new_item' => '新しいコツ',
            'view_item' => 'コツを表示',
            'search_items' => 'コツを検索',
            'not_found' => 'コツが見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱にコツはありません',
            'all_items' => 'すべてのコツ',
            'menu_name' => '申請のコツ'
        ),
        'description' => '申請のコツ情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tips',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 9,
        'menu_icon' => 'dashicons-lightbulb',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true
    ));
}
add_action('init', 'gi_register_post_types');
/**
 * カスタムタクソノミー登録（完全版・都道府県対応・修正版）
 */
function gi_register_taxonomies() {
    // 助成金カテゴリー
    register_taxonomy('grant_category', 'grant', array(
        'labels' => array(
            'name' => '助成金カテゴリー',
            'singular_name' => '助成金カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '助成金・補助金をカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
    
    // 都道府県タクソノミー
    register_taxonomy('grant_prefecture', 'grant', array(
        'labels' => array(
            'name' => '対象都道府県',
            'singular_name' => '都道府県',
            'search_items' => '都道府県を検索',
            'all_items' => 'すべての都道府県',
            'edit_item' => '都道府県を編集',
            'update_item' => '都道府県を更新',
            'add_new_item' => '新しい都道府県を追加',
            'new_item_name' => '新しい都道府県名'
        ),
        'description' => '助成金・補助金の対象都道府県を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'prefecture',
            'with_front' => false
        )
    ));
    
    // 助成金タグ
    register_taxonomy('grant_tag', 'grant', array(
        'labels' => array(
            'name' => '助成金タグ',
            'singular_name' => '助成金タグ',
            'search_items' => 'タグを検索',
            'all_items' => 'すべてのタグ',
            'edit_item' => 'タグを編集',
            'update_item' => 'タグを更新',
            'add_new_item' => '新しいタグを追加',
            'new_item_name' => '新しいタグ名'
        ),
        'description' => '助成金・補助金をタグで分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tag',
            'with_front' => false
        )
    ));
    
    // ツールカテゴリー
    register_taxonomy('tool_category', 'tool', array(
        'labels' => array(
            'name' => 'ツールカテゴリー',
            'singular_name' => 'ツールカテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => 'ビジネスツールをカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'tool-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
    
    // 成功事例カテゴリー
    register_taxonomy('case_study_category', 'case_study', array(
        'labels' => array(
            'name' => '成功事例カテゴリー',
            'singular_name' => '成功事例カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '成功事例をカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'case-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));

    // 【修正】申請のコツカテゴリー（不足していたタクソノミー）
    register_taxonomy('grant_tip_category', 'grant_tip', array(
        'labels' => array(
            'name' => '申請のコツカテゴリー',
            'singular_name' => '申請のコツカテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '申請のコツをカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tip-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
}
add_action('init', 'gi_register_taxonomies');

/**
 * デフォルト都道府県データの挿入
 */
function gi_insert_default_prefectures() {
    $prefectures = array(
        '全国対応', '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );

    foreach ($prefectures as $prefecture) {
        if (!term_exists($prefecture, 'grant_prefecture')) {
            wp_insert_term($prefecture, 'grant_prefecture');
        }
    }
}
add_action('init', 'gi_insert_default_prefectures');

/**
 * デフォルトカテゴリーデータの挿入
 */
function gi_insert_default_categories() {
    $categories = array(
        'IT・デジタル化支援',
        '設備投資・機械導入',
        '人材育成・教育訓練',
        '研究開発・技術革新',
        '省エネ・環境対策',
        '事業承継・M&A',
        '海外展開・輸出促進',
        '創業・起業支援',
        '販路開拓・マーケティング',
        '働き方改革・労働環境',
        '観光・地域振興',
        '農業・林業・水産業',
        '製造業・ものづくり',
        'サービス業・小売業',
        'コロナ対策・事業継続',
        '女性・若者・シニア支援',
        '障がい者雇用支援',
        '知的財産・特許',
        'BCP・リスク管理',
        'その他・汎用'
    );

    foreach ($categories as $category) {
        if (!term_exists($category, 'grant_category')) {
            wp_insert_term($category, 'grant_category');
        }
    }

    // 申請のコツ用デフォルトカテゴリー
    $tip_categories = array(
        '申請書作成のコツ',
        '事業計画書の書き方',
        '審査対策',
        '必要書類の準備',
        '申請スケジュール管理',
        'よくある失敗例',
        '成功のポイント'
    );

    foreach ($tip_categories as $category) {
        if (!term_exists($category, 'grant_tip_category')) {
            wp_insert_term($category, 'grant_tip_category');
        }
    }
}
add_action('init', 'gi_insert_default_categories');

/**
 * 【修正】未定義関数の追加
 */

// 締切日のフォーマット関数
function gi_get_formatted_deadline($post_id) {
    $deadline = gi_safe_get_meta($post_id, 'deadline_date');
    if (!$deadline) {
        // 旧フィールドも確認
        $deadline = gi_safe_get_meta($post_id, 'deadline');
    }
    
    if (!$deadline) {
        return '';
    }
    
    // 数値の場合（UNIXタイムスタンプ）
    if (is_numeric($deadline)) {
        return date('Y年m月d日', intval($deadline));
    }
    
    // 文字列の場合
    $timestamp = strtotime($deadline);
    if ($timestamp !== false) {
        return date('Y年m月d日', $timestamp);
    }
    
    return $deadline;
}

/**
 * 【修正】メタフィールドの同期処理（ACF対応）
 */
function gi_sync_grant_meta_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;
    if (!current_user_can('edit_post', $post_id)) return;

    // 金額の数値版を作成
    $amount_text = get_post_meta($post_id, 'max_amount', true);
    if (!$amount_text) {
        // ACFフィールドも確認
        $amount_text = get_field('max_amount', $post_id);
    }
    
    if ($amount_text) {
        // 数値のみを抽出
        $amount_numeric = preg_replace('/[^0-9]/', '', $amount_text);
        if ($amount_numeric) {
            update_post_meta($post_id, 'max_amount_numeric', intval($amount_numeric));
        }
    }

    // 日付の数値版を作成
    $deadline = get_post_meta($post_id, 'deadline', true);
    if (!$deadline) {
        // ACFフィールドも確認
        $deadline = get_field('deadline', $post_id);
    }
    
    if ($deadline) {
        if (is_numeric($deadline)) {
            update_post_meta($post_id, 'deadline_date', intval($deadline));
        } else {
            $deadline_numeric = strtotime($deadline);
            if ($deadline_numeric !== false) {
                update_post_meta($post_id, 'deadline_date', $deadline_numeric);
            }
        }
    }

    // ステータスの同期
    $status = get_post_meta($post_id, 'status', true);
    if (!$status) {
        $status = get_field('application_status', $post_id);
    }
    
    if ($status) {
        update_post_meta($post_id, 'application_status', $status);
    } else {
        // デフォルトステータス
        update_post_meta($post_id, 'application_status', 'open');
    }

    // 組織名の同期
    $organization = get_field('organization', $post_id);
    if ($organization) {
        update_post_meta($post_id, 'organization', $organization);
    }
}
add_action('save_post', 'gi_sync_grant_meta_on_save', 20, 3);

/**
 * セキュリティ・ヘルパー関数群（強化版）
 */

// 安全なメタ取得
function gi_safe_get_meta($post_id, $key, $default = '') {
    if (!$post_id || !is_numeric($post_id)) {
        return $default;
    }
    
    $value = get_post_meta($post_id, $key, true);
    
    // ACFフィールドも確認
    if (is_null($value) || $value === false || $value === '') {
        if (function_exists('get_field')) {
            $value = get_field($key, $post_id);
        }
    }
    
    if (is_null($value) || $value === false || $value === '') {
        return $default;
    }
    
    return $value;
}

// 安全な属性出力
function gi_safe_attr($value) {
    if (is_array($value)) {
        $value = implode(' ', $value);
    }
    return esc_attr($value);
}

// 安全なHTML出力
function gi_safe_escape($value) {
    if (is_array($value)) {
        return array_map('esc_html', $value);
    }
    return esc_html($value);
}

// 安全な数値フォーマット
function gi_safe_number_format($value, $decimals = 0) {
    if (!is_numeric($value)) {
        return '0';
    }
    $num = floatval($value);
    return number_format($num, $decimals);
}

// 安全な日付フォーマット
function gi_safe_date_format($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    
    if (is_numeric($date)) {
        return date($format, $date);
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

// 安全なパーセント表示
function gi_safe_percent_format($value, $decimals = 1) {
    if (!is_numeric($value)) {
        return '0%';
    }
    $num = floatval($value);
    return number_format($num, $decimals) . '%';
}

// 安全なURL出力
function gi_safe_url($url) {
    if (empty($url)) {
        return '';
    }
    return esc_url($url);
}

// 安全なJSON出力
function gi_safe_json($data) {
    return wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// 安全なテキスト切り取り
function gi_safe_excerpt($text, $length = 100, $more = '...') {
    if (mb_strlen($text) <= $length) {
        return esc_html($text);
    }
    
    $excerpt = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($excerpt, ' ');
    
    if ($last_space !== false) {
        $excerpt = mb_substr($excerpt, 0, $last_space);
    }
    
    return esc_html($excerpt . $more);
}

/**
 * 動的パス取得関数（完全版）
 */

// アセットURL取得
function gi_get_asset_url($path) {
    $path = ltrim($path, '/');
    return get_template_directory_uri() . '/' . $path;
}

// アップロードURL取得
function gi_get_upload_url($filename) {
    $upload_dir = wp_upload_dir();
    $filename = ltrim($filename, '/');
    return $upload_dir['baseurl'] . '/' . $filename;
}

// メディアURL取得（自動検出機能付き）
function gi_get_media_url($filename, $fallback = true) {
    if (empty($filename)) {
        return $fallback ? gi_get_asset_url('assets/images/placeholder.jpg') : '';
    }
    
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    $filename = str_replace([
        'http://keishi0804.xsrv.jp/wp-content/uploads/',
        'https://keishi0804.xsrv.jp/wp-content/uploads/',
        '/wp-content/uploads/'
    ], '', $filename);
    
    $filename = ltrim($filename, '/');
    
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $filename;
    
    if (file_exists($file_path)) {
        return $upload_dir['baseurl'] . '/' . $filename;
    }
    
    $current_year = date('Y');
    $current_month = date('m');
    
    $possible_paths = [
        $current_year . '/' . $current_month . '/' . $filename,
        $current_year . '/' . $filename,
        'uploads/' . $filename,
        'media/' . $filename
    ];
    
    foreach ($possible_paths as $path) {
        $full_path = $upload_dir['basedir'] . '/' . $path;
        if (file_exists($full_path)) {
            return $upload_dir['baseurl'] . '/' . $path;
        }
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/placeholder.jpg');
    }
    
    return '';
}

// 動画URL取得
function gi_get_video_url($filename, $fallback = true) {
    $url = gi_get_media_url($filename, false);
    
    if (!empty($url)) {
        return $url;
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/videos/placeholder.mp4');
    }
    
    return '';
}

// ロゴURL取得
function gi_get_logo_url($fallback = true) {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    $hero_logo = get_theme_mod('gi_hero_logo');
    if ($hero_logo) {
        return gi_get_media_url($hero_logo, false);
    }
    
    if ($fallback) {
        return gi_get_asset_url('assets/images/logo.png');
    }
    
    return '';
}

/**
 * 補助ヘルパー: 金額（円）を万円表示用に整形
 */
function gi_format_amount_man($amount_yen, $amount_text = '') {
    $yen = is_numeric($amount_yen) ? intval($amount_yen) : 0;
    if ($yen > 0) {
        return gi_safe_number_format(intval($yen / 10000));
    }
    if (!empty($amount_text)) {
        if (preg_match('/([0-9,]+)\s*万円/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
        if (preg_match('/([0-9,]+)/u', $amount_text, $m)) {
            return gi_safe_number_format(intval(str_replace(',', '', $m[1])));
        }
    }
    return '0';
}

/**
 * 補助ヘルパー: ACFのapplication_statusをUI用にマッピング
 */
function gi_map_application_status_ui($app_status) {
    switch ($app_status) {
        case 'open':
            return 'active';
        case 'upcoming':
            return 'upcoming';
        case 'closed':
            return 'closed';
        default:
            return 'active';
    }
}

/**
 * 【修正】AJAX - 助成金読み込み処理（都道府県・完全対応版）
 */
function gi_ajax_load_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce') && !wp_verify_nonce($_POST['nonce'] ?? '', 'grant_insight_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }

    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true);
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true);
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true);
    
    // Map UI statuses to ACF values
    if (is_array($status)) {
        $status = array_map(function($s){ return $s === 'active' ? 'open' : $s; }, $status);
    }
    
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = intval($_POST['page'] ?? 1);
    $posts_per_page = 12;

    // クエリ引数
    $args = array(
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish'
    );

    // 検索キーワード
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // タクソノミークエリ
    $tax_query = array('relation' => 'AND');

    // カテゴリーフィルター
    if (!empty($categories)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        );
    }

    // 都道府県フィルター
    if (!empty($prefectures)) {
        $tax_query[] = array(
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => $prefectures,
            'operator' => 'IN'
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // メタクエリ
    $meta_query = array('relation' => 'AND');

    // ステータスフィルター
    if (!empty($status)) {
        $meta_query[] = array(
            'key' => 'application_status',
            'value' => $status,
            'compare' => 'IN'
        );
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // 並び順
    switch ($sort) {
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'amount_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'DESC';
            break;
        case 'amount_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'ASC';
            break;
        case 'deadline_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'deadline_date';
            $args['order'] = 'ASC';
            break;
        case 'title_asc':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }

    // クエリ実行
    $query = new WP_Query($args);
    $grants = array();
    $user_favorites = gi_get_user_favorites();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // 都道府県取得
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            $prefecture = '';
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                $prefecture = $prefecture_terms[0]->name;
            }

            // カテゴリー取得
            $category_terms = get_the_terms($post_id, 'grant_category');
            $main_category = '';
            $related_categories = array();
            
            if ($category_terms && !is_wp_error($category_terms)) {
                $main_category = $category_terms[0]->name;
                if (count($category_terms) > 1) {
                    for ($i = 1; $i < count($category_terms); $i++) {
                        $related_categories[] = $category_terms[$i]->name;
                    }
                }
            }

            $grants[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'excerpt' => gi_safe_excerpt(get_the_excerpt(), 150),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
                'date' => get_the_date('Y-m-d'),
                'prefecture' => $prefecture,
                'main_category' => $main_category,
                'related_categories' => $related_categories,
                'amount' => gi_format_amount_man(gi_safe_get_meta($post_id, 'max_amount_numeric', 0), gi_safe_get_meta($post_id, 'max_amount', '')),
                'organization' => gi_safe_escape(gi_safe_get_meta($post_id, 'organization')),
                'deadline' => gi_get_formatted_deadline($post_id),
                'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')),
                'is_favorite' => in_array($post_id, $user_favorites)
            );
        }
        wp_reset_postdata();
    }

    // ページネーション情報
    $pagination = array(
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
        'total_posts' => $query->found_posts,
        'posts_per_page' => $posts_per_page
    );

    // クエリ情報
    $query_info = array(
        'search' => $search,
        'categories' => $categories,
        'prefectures' => $prefectures,
        'amount' => $amount,
        'status' => $status,
        'sort' => $sort
    );

    wp_send_json_success(array(
        'grants' => $grants,
        'found_posts' => $query->found_posts,
        'pagination' => $pagination,
        'query_info' => $query_info,
        'view' => $view
    ));
}
add_action('wp_ajax_gi_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_ajax_load_grants');

// 続く...（残りのAJAX関数とユーティリティ関数）
// 
// // === Missing AJAX endpoints implemented ===
// 1) Search suggestions
function gi_ajax_get_search_suggestions() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $query = sanitize_text_field($_POST['query'] ?? '');
    $suggestions = array();
    if ($query !== '') {
        $args = array(
            's' => $query,
            'post_type' => array('grant','tool','case_study','guide','grant_tip'),
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'fields' => 'ids'
        );
        $posts = get_posts($args);
        foreach ($posts as $pid) {
            $suggestions[] = array(
                'label' => get_the_title($pid),
                'value' => get_the_title($pid)
            );
        }
    }
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_get_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_get_search_suggestions', 'gi_ajax_get_search_suggestions');

// 2) Advanced search (simple wrapper around gi_search with HTML list)
function gi_ajax_advanced_search() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $keyword = sanitize_text_field($_POST['search_query'] ?? ($_POST['s'] ?? ''));
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? '');

    $tax_query = array('relation' => 'AND');
    if ($prefecture) {
        $tax_query[] = array('taxonomy'=>'grant_prefecture','field'=>'slug','terms'=>array($prefecture),'operator'=>'IN');
    }
    if ($category) {
        $tax_query[] = array('taxonomy'=>'grant_category','field'=>'slug','terms'=>array($category),'operator'=>'IN');
    }

    $meta_query = array('relation' => 'AND');
    if ($amount) {
        switch ($amount) {
            case '0-100':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>1000000,'compare'=>'<=','type'=>'NUMERIC');
                break;
            case '100-500':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(1000000,5000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '500-1000':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>array(5000000,10000000),'compare'=>'BETWEEN','type'=>'NUMERIC');
                break;
            case '1000+':
                $meta_query[] = array('key'=>'max_amount_numeric','value'=>10000000,'compare'=>'>=','type'=>'NUMERIC');
                break;
        }
    }
    if ($status) {
        $status = $status === 'active' ? 'open' : $status;
        $meta_query[] = array('key'=>'application_status','value'=>array($status),'compare'=>'IN');
    }

    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        's' => $keyword,
    );
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;
    if (count($meta_query) > 1) $args['meta_query'] = $meta_query;

    $q = new WP_Query($args);
    $html = '';
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $html .= gi_render_grant_card($pid, 'grid');
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array(
        'html' => $html ?: '<p class="text-gray-500">該当する助成金が見つかりませんでした。</p>',
        'count' => $q->found_posts
    ));
}
add_action('wp_ajax_advanced_search', 'gi_ajax_advanced_search');
add_action('wp_ajax_nopriv_advanced_search', 'gi_ajax_advanced_search');

// 2.5) Grant Insight top page search (section-search.php)
function gi_ajax_grant_insight_search() {
    // Verify nonce specific to front-page search section
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    $keyword   = sanitize_text_field($_POST['keyword'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? '');
    $orderby   = sanitize_text_field($_POST['orderby'] ?? 'relevance');
    $category  = sanitize_text_field($_POST['category'] ?? ''); // term_id expected for grant_category
    $amount_min = isset($_POST['amount_min']) ? intval($_POST['amount_min']) : 0;
    $amount_max = isset($_POST['amount_max']) ? intval($_POST['amount_max']) : 0;
    $deadline   = sanitize_text_field($_POST['deadline'] ?? '');
    $page       = max(1, intval($_POST['page'] ?? 1));

    $per_page = 12;

    // Determine post types
    $post_types = array('grant','tool','case_study','guide','grant_tip');
    if (!empty($post_type)) {
        $post_types = array($post_type);
    }

    $args = array(
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        's'              => $keyword,
        'paged'          => $page,
        'posts_per_page' => $per_page,
    );

    // Orderby mapping
    switch ($orderby) {
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'title':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'modified':
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
            break;
        case 'relevance':
        default:
            $args['orderby'] = 'relevance';
            $args['order']   = 'DESC';
            break;
    }

    // Tax query (grant category only when applicable)
    $tax_query = array('relation' => 'AND');
    if (!empty($category)) {
        // Only apply to grants; ignore for others
        $tax_query[] = array(
            'taxonomy' => 'grant_category',
            'field'    => 'term_id',
            'terms'    => array(intval($category)),
        );
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // Meta query for grants (amount and deadline)
    $meta_query = array('relation' => 'AND');
    if (in_array('grant', $post_types, true) || $post_type === 'grant') {
        if ($amount_min > 0 || $amount_max > 0) {
            $range = array();
            if ($amount_min > 0) $range[] = $amount_min;
            if ($amount_max > 0) $range[] = $amount_max;
            $meta_query[] = array(
                'key'     => 'max_amount_numeric',
                'value'   => $amount_max > 0 && $amount_min > 0 ? array($amount_min, $amount_max) : ($amount_max > 0 ? $amount_max : $amount_min),
                'compare' => ($amount_min > 0 && $amount_max > 0) ? 'BETWEEN' : ($amount_max > 0 ? '<=' : '>='),
                'type'    => 'NUMERIC',
            );
        }

        if (!empty($deadline)) {
            $todayYmd = intval(current_time('Ymd'));
            $targetYmd = $todayYmd;
            switch ($deadline) {
                case '1month':
                    $targetYmd = intval(date('Ymd', strtotime('+1 month', current_time('timestamp'))));
                    break;
                case '3months':
                    $targetYmd = intval(date('Ymd', strtotime('+3 months', current_time('timestamp'))));
                    break;
                case '6months':
                    $targetYmd = intval(date('Ymd', strtotime('+6 months', current_time('timestamp'))));
                    break;
                case '1year':
                    $targetYmd = intval(date('Ymd', strtotime('+1 year', current_time('timestamp'))));
                    break;
            }
            $meta_query[] = array(
                'key'     => 'deadline_date',
                'value'   => array($todayYmd, $targetYmd),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        }
    }
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    $q = new WP_Query($args);

    $favorites = gi_get_user_favorites();
    $posts = array();
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $ptype = get_post_type($pid);
            $amount_yen = ($ptype === 'grant') ? intval(gi_safe_get_meta($pid, 'max_amount_numeric', 0)) : 0;
            $deadline_date = ($ptype === 'grant') ? gi_safe_get_meta($pid, 'deadline_date', '') : '';

            $posts[] = array(
                'id'         => $pid,
                'title'      => get_the_title($pid),
                'excerpt'    => wp_strip_all_tags(get_the_excerpt($pid)),
                'permalink'  => get_permalink($pid),
                'thumbnail'  => get_the_post_thumbnail_url($pid, 'medium'),
                'date'       => get_the_date('Y-m-d', $pid),
                'post_type'  => $ptype,
                'amount'     => $amount_yen,
                'deadline'   => $deadline_date,
                'is_featured'=> false,
                'is_favorite'=> in_array($pid, $favorites, true),
            );
        }
        wp_reset_postdata();
    }

    $response = array(
        'posts' => $posts,
        'pagination' => array(
            'current_page' => $page,
            'total_pages'  => max(1, intval($q->max_num_pages)),
        ),
        'total' => intval($q->found_posts),
    );

    wp_send_json_success($response);
}
add_action('wp_ajax_grant_insight_search', 'gi_ajax_grant_insight_search');
add_action('wp_ajax_nopriv_grant_insight_search', 'gi_ajax_grant_insight_search');

// 2.6) Export search results as CSV
function gi_ajax_grant_insight_export_results() {
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    // Nonce is optional here since export may be triggered immediately after search; try both
    if (!wp_verify_nonce($nonce, 'grant_insight_search_nonce') && !wp_verify_nonce($nonce, 'gi_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    // Reuse the search builder with permissive defaults
    $_POST['page'] = 1;
    $_POST['orderby'] = sanitize_text_field($_POST['orderby'] ?? 'date');

    // Build query similar to gi_ajax_grant_insight_search
    $keyword   = sanitize_text_field($_POST['keyword'] ?? '');
    $post_type = sanitize_text_field($_POST['post_type'] ?? 'grant');
    $category  = sanitize_text_field($_POST['category'] ?? '');

    $args = array(
        'post_type'      => $post_type ? array($post_type) : array('grant'),
        'post_status'    => 'publish',
        's'              => $keyword,
        'posts_per_page' => 200, // cap export size
        'paged'          => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'grant_category',
                'field'    => 'term_id',
                'terms'    => array(intval($category)),
            )
        );
    }

    $q = new WP_Query($args);

    // Output CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="grant_search_results_' . date('Y-m-d') . '.csv"');
    $fp = fopen('php://output', 'w');
    // BOM for Excel (optional)
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($fp, array('ID','Title','Permalink','Post Type','Date','Amount(yen)','Deadline'));
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $ptype = get_post_type($pid);
            $amount_yen = ($ptype === 'grant') ? intval(gi_safe_get_meta($pid, 'max_amount_numeric', 0)) : 0;
            $deadline_date = ($ptype === 'grant') ? gi_safe_get_meta($pid, 'deadline_date', '') : '';
            fputcsv($fp, array(
                $pid,
                get_the_title($pid),
                get_permalink($pid),
                $ptype,
                get_the_date('Y-m-d', $pid),
                $amount_yen,
                $deadline_date,
            ));
        }
        wp_reset_postdata();
    }
    fclose($fp);
    exit;
}
add_action('wp_ajax_grant_insight_export_results', 'gi_ajax_grant_insight_export_results');
add_action('wp_ajax_nopriv_grant_insight_export_results', 'gi_ajax_grant_insight_export_results');

// 3) Newsletter signup (stores emails in option transient-like array)
function gi_ajax_newsletter_signup() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $email = sanitize_email($_POST['email'] ?? '');
    if (!$email || !is_email($email)) {
        wp_send_json_error('メールアドレスが正しくありません');
    }
    $list = get_option('gi_newsletter_list', array());
    if (!is_array($list)) $list = array();
    if (!in_array($email, $list)) {
        $list[] = $email;
        update_option('gi_newsletter_list', $list);
    }
    wp_send_json_success(array('message' => '登録しました'));
}
add_action('wp_ajax_newsletter_signup', 'gi_ajax_newsletter_signup');
add_action('wp_ajax_nopriv_newsletter_signup', 'gi_ajax_newsletter_signup');

// 4) Affiliate click tracking
function gi_ajax_track_affiliate_click() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $url = esc_url_raw($_POST['url'] ?? '');
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$url) wp_send_json_error('URLが無効です');
    $log = get_option('gi_affiliate_clicks', array());
    if (!is_array($log)) $log = array();
    $log[] = array('time' => current_time('timestamp'), 'url' => $url, 'post_id' => $post_id, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '');
    update_option('gi_affiliate_clicks', $log);
    wp_send_json_success(array('message' => 'ok'));
}
add_action('wp_ajax_track_affiliate_click', 'gi_ajax_track_affiliate_click');
add_action('wp_ajax_nopriv_track_affiliate_click', 'gi_ajax_track_affiliate_click');

// 5) Related grants
function gi_ajax_get_related_grants() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'get_related_grants_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $post_id = intval($_POST['post_id'] ?? 0);
    $category_name = sanitize_text_field($_POST['category'] ?? '');
    $prefecture_name = sanitize_text_field($_POST['prefecture'] ?? '');

    $tax_query = array('relation' => 'AND');
    if ($category_name) {
        $term = get_term_by('name', $category_name, 'grant_category');
        if ($term) {
            $tax_query[] = array('taxonomy'=>'grant_category','field'=>'term_id','terms'=>array($term->term_id));
        }
    }
    if ($prefecture_name) {
        $term = get_term_by('name', $prefecture_name, 'grant_prefecture');
        if ($term) {
            $tax_query[] = array('taxonomy'=>'grant_prefecture','field'=>'term_id','terms'=>array($term->term_id));
        }
    }

    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'post__not_in' => array($post_id),
    );
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;

    $q = new WP_Query($args);
    $html = '';
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post();
            $pid = get_the_ID();
            $html .= gi_render_grant_card($pid, 'list');
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_get_related_grants', 'gi_ajax_get_related_grants');
add_action('wp_ajax_nopriv_get_related_grants', 'gi_ajax_get_related_grants');

/**
 * お気に入り機能（強化版）
 */
function gi_ajax_toggle_favorite() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce') && !wp_verify_nonce($_POST['nonce'] ?? '', 'grant_insight_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();
    
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error('無効な投稿IDです');
    }
    
    if (!$user_id) {
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
        } else {
            $favorites[] = $post_id;
            $action = 'added';
        }
        
        setcookie($cookie_name, implode(',', $favorites), time() + (86400 * 30), '/');
    } else {
        $meta_key = 'gi_favorites';
        $favorites = get_user_meta($user_id, $meta_key, true);
        if (!is_array($favorites)) $favorites = array();
        
        if (in_array($post_id, $favorites)) {
            $favorites = array_diff($favorites, array($post_id));
            $action = 'removed';
        } else {
            $favorites[] = $post_id;
            $action = 'added';
        }
        
        update_user_meta($user_id, $meta_key, $favorites);
    }
    
    wp_send_json_success(array(
        'action' => $action,
        'post_id' => $post_id,
        'count' => count($favorites),
        'is_favorite' => $action === 'added',
        'message' => $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました'
    ));
}
add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
// Alias for front-page.js 'toggle_favorite'
add_action('wp_ajax_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'gi_ajax_toggle_favorite');
// Alias for section-search.php favorite action
add_action('wp_ajax_grant_insight_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_grant_insight_toggle_favorite', 'gi_ajax_toggle_favorite');

/**
 * お気に入り一覧取得
 */
function gi_get_user_favorites($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        $cookie_name = 'gi_favorites';
        $favorites = isset($_COOKIE[$cookie_name]) ? array_filter(explode(',', $_COOKIE[$cookie_name])) : array();
    } else {
        $favorites = get_user_meta($user_id, 'gi_favorites', true);
        if (!is_array($favorites)) $favorites = array();
    }
    
    return array_map('intval', $favorites);
}

/**
 * Sync ACF prefecture meta to grant_prefecture taxonomy on save
 */
function gi_sync_grant_prefectures_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'grant') return;

    // Try common meta keys
    $meta_values = array();
    $candidates = array('prefecture', 'prefectures', 'grant_prefecture');
    foreach ($candidates as $key) {
        $val = gi_safe_get_meta($post_id, $key, '');
        if (!empty($val)) {
            if (is_array($val)) {
                $meta_values = $val;
            } else {
                // Split by comma or pipe if stored as text
                $meta_values = preg_split('/[,|]/u', $val);
            }
            break;
        }
    }
    if (empty($meta_values)) return;

    $term_ids = array();
    foreach ($meta_values as $raw) {
        $name = trim(wp_strip_all_tags($raw));
        if ($name === '') continue;
        $term = get_term_by('name', $name, 'grant_prefecture');
        if (!$term) {
            // Try slug match
            $term = get_term_by('slug', sanitize_title($name), 'grant_prefecture');
        }
        if ($term && !is_wp_error($term)) {
            $term_ids[] = intval($term->term_id);
        }
    }
    if (!empty($term_ids)) {
        wp_set_post_terms($post_id, $term_ids, 'grant_prefecture', false);
    }
}
add_action('save_post', 'gi_sync_grant_prefectures_on_save', 20, 3);

/**
 * 投稿カテゴリー取得
 */
function gi_get_post_categories($post_id) {
    $post_type = get_post_type($post_id);
    $taxonomy = $post_type . '_category';
    
    if (!taxonomy_exists($taxonomy)) {
        return array();
    }
    
    $terms = get_the_terms($post_id, $taxonomy);
    if (!$terms || is_wp_error($terms)) {
        return array();
    }
    
    return array_map(function($term) {
        return array(
            'name' => $term->name,
            'slug' => $term->slug,
            'link' => get_term_link($term)
        );
    }, $terms);
}

/**
 * 【修正】カード表示関数（完全版）
 */
function gi_render_grant_card($post_id, $view = 'grid') {
    if (!$post_id || !get_post($post_id)) {
        return '';
    }

    $post = get_post($post_id);
    $user_favorites = gi_get_user_favorites();

    // 都道府県取得
    $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
    $prefecture = '';
    if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
        $prefecture = $prefecture_terms[0]->name;
    }

    // カテゴリー取得
    $category_terms = get_the_terms($post_id, 'grant_category');
    $main_category = '';
    $related_categories = array();
    
    if ($category_terms && !is_wp_error($category_terms)) {
        $main_category = $category_terms[0]->name;
        if (count($category_terms) > 1) {
            for ($i = 1; $i < count($category_terms); $i++) {
                $related_categories[] = $category_terms[$i]->name;
            }
        }
    }

    // データ取得
    $data = array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'excerpt' => gi_safe_excerpt(get_post_field('post_excerpt', $post_id), 150),
        'permalink' => get_permalink($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'gi-card-thumb'),
        'date' => get_the_date('Y-m-d', $post_id),
        'prefecture' => $prefecture,
        'main_category' => $main_category,
        'related_categories' => $related_categories,
        'amount' => gi_format_amount_man(gi_safe_get_meta($post_id, 'max_amount_numeric', 0), gi_safe_get_meta($post_id, 'max_amount', '')),
        'organization' => gi_safe_escape(gi_safe_get_meta($post_id, 'organization')),
        'deadline' => gi_get_formatted_deadline($post_id),
        'status' => gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open')),
        'is_favorite' => in_array($post_id, $user_favorites)
    );

    if ($view === 'list') {
        return gi_render_grant_card_list($data);
    } else {
        return gi_render_grant_card_grid($data);
    }
}

/**
 * グリッドカード表示
 */
function gi_render_grant_card_grid($grant) {
    ob_start();
    ?>
    <div class="grant-card bg-white rounded-xl shadow-sm border hover:shadow-lg transition-all duration-300 overflow-hidden animate-fade-in-up">
        <div class="relative">
            <?php if ($grant['thumbnail']) : ?>
                <img src="<?php echo gi_safe_url($grant['thumbnail']); ?>" alt="<?php echo gi_safe_attr($grant['title']); ?>" class="w-full h-48 object-cover">
            <?php else : ?>
                <div class="w-full h-48 bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <i class="fas fa-coins text-4xl text-white"></i>
                </div>
            <?php endif; ?>
            
            <!-- ステータスバッジ -->
            <div class="absolute top-3 left-3">
                <?php echo gi_get_status_badge($grant['status']); ?>
            </div>
            
            <!-- お気に入りボタン -->
            <button class="favorite-btn absolute top-3 right-3 w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center transition-all duration-200 <?php echo $grant['is_favorite'] ? 'text-red-500' : 'text-gray-400'; ?>"
                    data-post-id="<?php echo $grant['id']; ?>">
                <i class="fas fa-heart text-sm"></i>
            </button>
        </div>
        
        <div class="p-6">
            <!-- 都道府県・カテゴリ -->
            <div class="mb-3">
                <?php if ($grant['prefecture']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full mr-2 mb-1">
                        📍 <?php echo gi_safe_escape($grant['prefecture']); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($grant['main_category']) : ?>
                    <span class="inline-block px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full mb-1">
                        <?php echo gi_safe_escape($grant['main_category']); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($grant['related_categories'])) : ?>
                    <div class="mt-2 hidden related-categories">
                        <?php foreach ($grant['related_categories'] as $cat) : ?>
                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full mr-1 mb-1">
                                <?php echo gi_safe_escape($cat); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <button class="text-xs text-emerald-600 hover:text-emerald-800 mt-1 show-more-categories">
                        関連カテゴリを表示
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- タイトル -->
            <h3 class="text-lg font-semibold text-gray-900 mb-3 line-clamp-2 hover:text-emerald-600 transition-colors">
                <a href="<?php echo gi_safe_url($grant['permalink']); ?>"><?php echo gi_safe_escape($grant['title']); ?></a>
            </h3>
            
            <!-- 金額 -->
            <div class="flex items-center gap-2 mb-3">
                <div class="text-2xl font-bold text-emerald-600">
                    <?php echo $grant['amount']; ?>
                </div>
                <span class="text-sm text-gray-500">万円</span>
            </div>
            
            <!-- 概要 -->
            <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                <?php echo $grant['excerpt']; ?>
            </p>
            
            <!-- 詳細情報 -->
            <div class="space-y-2 mb-4 text-sm">
                <?php if ($grant['organization']) : ?>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-building w-4"></i>
                        <span><?php echo $grant['organization']; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($grant['deadline']) : ?>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-calendar w-4"></i>
                        <span>締切: <?php echo $grant['deadline']; ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- アクションボタン -->
            <div class="flex gap-2">
                <a href="<?php echo gi_safe_url($grant['permalink']); ?>" 
                   class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200">
                    詳細を見る
                </a>
                <button class="px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-600 hover:text-gray-700 rounded-lg transition-colors duration-200"
                        title="共有">
                    <i class="fas fa-share-alt text-sm"></i>
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * リストカード表示
 */
function gi_render_grant_card_list($grant) {
    ob_start();
    ?>
    <div class="grant-list-item bg-white rounded-xl shadow-sm border hover:shadow-md transition-all duration-300 p-6 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- サムネイル -->
            <div class="lg:w-48 lg:shrink-0">
                <?php if ($grant['thumbnail']) : ?>
                    <img src="<?php echo gi_safe_url($grant['thumbnail']); ?>" alt="<?php echo gi_safe_attr($grant['title']); ?>" class="w-full h-32 lg:h-24 object-cover rounded-lg">
                <?php else : ?>
                    <div class="w-full h-32 lg:h-24 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-coins text-2xl text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- コンテンツ -->
            <div class="flex-1">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="flex-1">
                        <!-- ヘッダー -->
                        <div class="flex items-center gap-3 mb-3 flex-wrap">
                            <?php echo gi_get_status_badge($grant['status']); ?>
                            
                            <?php if ($grant['prefecture']) : ?>
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                    📍 <?php echo gi_safe_escape($grant['prefecture']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($grant['main_category']) : ?>
                                <span class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">
                                    <?php echo gi_safe_escape($grant['main_category']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <button class="favorite-btn text-gray-400 hover:text-red-500 transition-colors <?php echo $grant['is_favorite'] ? 'text-red-500' : ''; ?>"
                                    data-post-id="<?php echo $grant['id']; ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        
                        <!-- タイトル -->
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 hover:text-emerald-600 transition-colors">
                            <a href="<?php echo gi_safe_url($grant['permalink']); ?>"><?php echo gi_safe_escape($grant['title']); ?></a>
                        </h3>
                        
                        <!-- 概要 -->
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            <?php echo $grant['excerpt']; ?>
                        </p>
                        
                        <!-- 詳細情報 -->
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <?php if ($grant['organization']) : ?>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo $grant['organization']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($grant['deadline']) : ?>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>
                                    <span>締切: <?php echo $grant['deadline']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 右側（金額とボタン） -->
                    <div class="lg:w-48 lg:text-right">
                        <!-- 金額 -->
                        <div class="mb-4">
                            <div class="text-3xl font-bold text-emerald-600">
                                <?php echo $grant['amount']; ?>
                                <span class="text-lg text-gray-500">万円</span>
                            </div>
                        </div>
                        
                        <!-- ボタン -->
                        <div class="flex lg:flex-col gap-2">
                            <a href="<?php echo gi_safe_url($grant['permalink']); ?>" 
                               class="flex-1 lg:flex-none bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors duration-200">
                                詳細を見る
                            </a>
                            <button class="px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-600 hover:text-gray-700 rounded-lg transition-colors duration-200"
                                    title="共有">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ステータスバッジ取得
 */
function gi_get_status_badge($status) {
    $badges = array(
        'active' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">募集中</span>',
        'upcoming' => '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">募集予定</span>',
        'closed' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">募集終了</span>'
    );
    return $badges[$status] ?? $badges['active'];
}

/**
 * 複数カード表示関数
 */
function gi_render_multiple_grants($post_ids, $view = 'grid', $columns = 3) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return '';
    }

    $grid_classes = array(
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4'
    );

    ob_start();
    
    if ($view === 'grid') {
        $grid_class = $grid_classes[$columns] ?? $grid_classes[3];
        echo '<div class="grid ' . $grid_class . ' gap-6">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'grid');
        }
        
        echo '</div>';
    } else {
        echo '<div class="space-y-4">';
        
        foreach ($post_ids as $post_id) {
            echo gi_render_grant_card($post_id, 'list');
        }
        
        echo '</div>';
    }
    
    return ob_get_clean();
}


/**
 * AJAX - ビジネスツール読み込み処理（修正版）
 */
function gi_ajax_load_tools() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました。');
    }

    // フォームからのデータをサニタイズして取得
    $search = sanitize_text_field($_POST['keyword'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $price_range = sanitize_text_field($_POST['price_range'] ?? '');
    $rating = sanitize_text_field($_POST['rating'] ?? '');
    $features = sanitize_text_field($_POST['features'] ?? '');
    $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'date');
    $sort_order = sanitize_text_field($_POST['sort_order'] ?? 'DESC');
    $posts_per_page = intval($_POST['posts_per_page'] ?? 12);
    $page = intval($_POST['page'] ?? 1);

    // WP_Queryの引数を構築
    $args = array(
        'post_type' => 'tool',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
    );

    // 検索キーワード
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // タクソノミークエリ（カテゴリ）
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'tool_category',
                'field' => 'slug',
                'terms' => $category,
            ),
        );
    }

    // メタクエリ（料金、評価、機能など）
    $meta_query = array('relation' => 'AND');
    
    if (!empty($price_range)) {
        switch ($price_range) {
            case 'free':
                $meta_query[] = array(
                    'key' => 'price_free',
                    'value' => '1',
                    'compare' => '='
                );
                break;
            case '0-5000':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => 5000,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                );
                break;
            case '5001-20000':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => array(5001, 20000),
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                );
                break;
            case '20001':
                $meta_query[] = array(
                    'key' => 'price_monthly',
                    'value' => 20001,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                );
                break;
        }
    }

    if (!empty($rating)) {
        $meta_query[] = array(
            'key' => 'rating',
            'value' => floatval($rating),
            'compare' => '>=',
            'type' => 'DECIMAL'
        );
    }

    if (!empty($features)) {
        $meta_query[] = array(
            'key' => 'features',
            'value' => $features,
            'compare' => 'LIKE'
        );
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    // 並び順
    switch ($sort_by) {
        case 'title':
            $args['orderby'] = 'title';
            break;
        case 'rating':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'rating';
            break;
        case 'price':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'price_monthly';
            break;
        case 'views':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'view_count';
            break;
        default: // date
            $args['orderby'] = 'date';
            break;
    }
    $args['order'] = $sort_order;

    $query = new WP_Query($args);
    $tools = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $tools[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => get_the_excerpt(),
                'rating' => gi_safe_get_meta($post_id, 'rating', '4.5'),
                'price' => gi_safe_get_meta($post_id, 'price_monthly', '無料'),
                'price_free' => gi_safe_get_meta($post_id, 'price_free', '0'),
            );
        }
    }
    wp_reset_postdata();

    // 結果をHTMLにレンダリング
    ob_start();
    if (!empty($tools)) {
        echo '<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">';
        foreach ($tools as $tool) {
            $price_display = $tool['price_free'] === '1' ? '無料プランあり' : '¥' . number_format(intval($tool['price'])) . '/月';
            if (!is_numeric($tool['price'])) {
                $price_display = $tool['price'];
            }
            ?>
            <div class="tool-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <?php if ($tool['thumbnail']) : ?>
                                <img src="<?php echo esc_url($tool['thumbnail']); ?>" alt="<?php echo esc_attr($tool['title']); ?>" class="w-full h-full object-cover rounded-xl">
                            <?php else : ?>
                                <i class="fas fa-tools text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-1 text-yellow-500">
                            <?php 
                            $rating = floatval($tool['rating']);
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5;
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '⭐';
                            }
                            if ($half_star) {
                                echo '⭐';
                            }
                            ?>
                            <span class="text-sm text-gray-600 ml-1">(<?php echo esc_html($tool['rating']); ?>)</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        <a href="<?php echo esc_url($tool['permalink']); ?>"><?php echo esc_html($tool['title']); ?></a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                        <?php echo esc_html(wp_trim_words($tool['excerpt'], 20)); ?>
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full font-medium">
                            <?php echo esc_html($price_display); ?>
                        </span>
                        <a href="<?php echo esc_url($tool['permalink']); ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                            詳細を見る →
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-20">
                <div class="w-32 h-32 bg-gradient-to-r from-indigo-400 via-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-tools text-white text-4xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-6">該当するツールが見つかりませんでした</h3>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
                    検索条件を変更して再度お試しください。
                </p>
              </div>';
    }
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'stats' => array(
            'total_found' => $query->found_posts,
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
        ),
    ));
}
add_action('wp_ajax_gi_load_tools', 'gi_ajax_load_tools');
add_action('wp_ajax_nopriv_gi_load_tools', 'gi_ajax_load_tools');

/**
 * AJAX - 申請のコツ読み込み処理（修正版）
 */
function gi_ajax_load_grant_tips() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました。');
    }

    $args = array(
        'post_type'      => 'grant_tip',
        'posts_per_page' => 9,
        'paged'          => intval($_POST['page'] ?? 1),
        'post_status'    => 'publish',
    );

    // 検索キーワード
    if (!empty($_POST['s'])) {
        $args['s'] = sanitize_text_field($_POST['s']);
    }

    // タクソノミークエリ
    $tax_query = array();
    if (!empty($_POST['grant_tip_category'])) {
        $tax_query[] = array(
            'taxonomy' => 'grant_tip_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_POST['grant_tip_category']),
        );
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    // メタクエリ
    $meta_query = array();
    if (!empty($_POST['difficulty'])) {
        $meta_query[] = array(
            'key'   => 'difficulty',
            'value' => sanitize_text_field($_POST['difficulty']),
            'compare' => '='
        );
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    // 並び順
    $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'date_desc');
    if ($sort_by === 'popular') {
        $args['orderby'] = 'comment_count';
        $args['order']   = 'DESC';
    } else {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    $query = new WP_Query($args);

    // 結果をHTMLにレンダリング
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="search-results-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            ?>
            <div class="tip-card bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover rounded-xl')); ?>
                            <?php else : ?>
                                <i class="fas fa-lightbulb text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 line-clamp-2">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                        <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                    </p>
                    
                    <div class="flex items-center justify-between text-sm">
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-medium">
                            <?php echo gi_safe_get_meta($post_id, 'difficulty', '初級'); ?>
                        </span>
                        <a href="<?php the_permalink(); ?>" class="text-yellow-600 hover:text-yellow-800 font-semibold">
                            詳細を見る →
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<div class="text-center py-20">
                <div class="w-32 h-32 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-lightbulb text-white text-5xl"></i>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-6">該当するコツが見つかりませんでした</h3>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">
                    検索条件を変更して再度お試しください。
                </p>
              </div>';
    }
    $html = ob_get_clean();
   
    // ページネーション
    ob_start();
    if ($query->max_num_pages > 1) {
        echo paginate_links([
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => max(1, $args['paged']),
            'total' => $query->max_num_pages,
            'prev_text' => '<i class="fas fa-chevron-left"></i>',
            'next_text' => '<i class="fas fa-chevron-right"></i>',
            'type' => 'list',
        ]);
    }
    $pagination = ob_get_clean();

    wp_reset_postdata();

    wp_send_json_success(array(
        'html' => $html,
        'pagination' => $pagination,
        'found_posts' => $query->found_posts
    ));
}
add_action('wp_ajax_gi_load_grant_tips', 'gi_ajax_load_grant_tips');
add_action('wp_ajax_nopriv_gi_load_grant_tips', 'gi_ajax_load_grant_tips');

/**
 * ウィジェットエリア登録
 */
function gi_widgets_init() {
    register_sidebar(array(
        'name'          => 'メインサイドバー',
        'id'            => 'sidebar-main',
        'description'   => 'メインサイドバーエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-8">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 pb-2 border-b-2 border-emerald-500">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア1',
        'id'            => 'footer-1',
        'description'   => 'フッター左側エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-base font-semibold mb-3 text-white">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア2',
        'id'            => 'footer-2',
        'description'   => 'フッター中央エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-base font-semibold mb-3 text-white">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => 'フッターエリア3',
        'id'            => 'footer-3',
        'description'   => 'フッター右側エリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-base font-semibold mb-3 text-white">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'gi_widgets_init');

/**
 * カスタマイザー設定（強化版）
 */
function gi_customize_register($wp_customize) {
    // ヒーローセクション設定
    $wp_customize->add_section('gi_hero_section', array(
        'title' => 'ヒーローセクション',
        'priority' => 30,
        'description' => 'フロントページのヒーローセクションを設定します'
    ));
    
    // ヒーロータイトル
    $wp_customize->add_setting('gi_hero_title', array(
        'default' => 'AI が提案する助成金・補助金情報サイト',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage'
    ));
    
    $wp_customize->add_control('gi_hero_title', array(
        'label' => 'メインタイトル',
        'section' => 'gi_hero_section',
        'type' => 'text'
    ));
    
    // ヒーローサブタイトル
    $wp_customize->add_setting('gi_hero_subtitle', array(
        'default' => '最先端のAI技術で、あなたのビジネスに最適な助成金・補助金を瞬時に発見。',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport' => 'postMessage'
    ));
    
    $wp_customize->add_control('gi_hero_subtitle', array(
        'label' => 'サブタイトル',
        'section' => 'gi_hero_section',
        'type' => 'textarea'
    ));
    
    // ヒーロー動画
    $wp_customize->add_setting('gi_hero_video', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'gi_hero_video', array(
        'label' => 'ヒーロー動画',
        'section' => 'gi_hero_section',
        'mime_type' => 'video'
    )));
    
    // ヒーローロゴ
    $wp_customize->add_setting('gi_hero_logo', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw'
    ));
    
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'gi_hero_logo', array(
        'label' => 'ヒーロー用ロゴ画像',
        'section' => 'gi_hero_section'
    )));
    
    // CTAボタン設定
    $wp_customize->add_setting('gi_hero_cta_primary_text', array(
        'default' => '今すぐ検索開始',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    $wp_customize->add_control('gi_hero_cta_primary_text', array(
        'label' => 'プライマリCTAテキスト',
        'section' => 'gi_hero_section',
        'type' => 'text'
    ));
    
    $wp_customize->add_setting('gi_hero_cta_primary_url', array(
        'default' => '#search-section',
        'sanitize_callback' => 'esc_url_raw'
    ));
    
    $wp_customize->add_control('gi_hero_cta_primary_url', array(
        'label' => 'プライマリCTA URL',
        'section' => 'gi_hero_section',
        'type' => 'url'
    ));
    
    // サイト基本設定
    $wp_customize->add_section('gi_site_settings', array(
        'title' => 'サイト基本設定',
        'priority' => 25
    ));
    
    // プライマリカラー
    $wp_customize->add_setting('gi_primary_color', array(
        'default' => '#10b981',
        'sanitize_callback' => 'sanitize_hex_color'
    ));
    
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'gi_primary_color', array(
        'label' => 'プライマリカラー',
        'section' => 'gi_site_settings'
    )));
}
add_action('customize_register', 'gi_customize_register');

/**
 * 管理画面カスタマイズ（強化版）
 */
function gi_admin_init() {
    // 管理画面スタイル
    add_action('admin_head', function() {
        echo '<style>
        .gi-admin-notice {
            border-left: 4px solid #10b981;
            background: #ecfdf5;
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .gi-admin-notice h3 {
            color: #047857;
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        .gi-admin-notice p {
            color: #065f46;
            margin: 0;
        }
        </style>';
    });
    
    // 投稿一覧カラム追加
    add_filter('manage_grant_posts_columns', 'gi_add_grant_columns');
    add_action('manage_grant_posts_custom_column', 'gi_grant_column_content', 10, 2);
}
add_action('admin_init', 'gi_admin_init');

// 助成金カラム
function gi_add_grant_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['gi_prefecture'] = '都道府県';
            $new_columns['gi_amount'] = '金額';
            $new_columns['gi_organization'] = '実施組織';
            $new_columns['gi_status'] = 'ステータス';
        }
    }
    return $new_columns;
}

function gi_grant_column_content($column, $post_id) {
    switch ($column) {
        case 'gi_prefecture':
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                echo gi_safe_escape($prefecture_terms[0]->name);
            } else {
                echo '－';
            }
            break;
        case 'gi_amount':
            $amount = gi_safe_get_meta($post_id, 'max_amount');
            echo $amount ? gi_safe_number_format($amount) . '万円' : '－';
            break;
        case 'gi_organization':
            echo gi_safe_escape(gi_safe_get_meta($post_id, 'organization', '－'));
            break;
        case 'gi_status':
            $status = gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open'));
            $status_labels = array(
                'active' => '<span style="color: #059669;">募集中</span>',
                'upcoming' => '<span style="color: #d97706;">募集予定</span>',
                'closed' => '<span style="color: #dc2626;">募集終了</span>'
            );
            echo $status_labels[$status] ?? $status;
            break;
    }
}

/**
 * パフォーマンス最適化
 */
function gi_performance_optimizations() {
    // 画像の遅延読み込み
    add_filter('wp_lazy_loading_enabled', '__return_true');
    
    // 不要なスクリプトの削除
    add_action('wp_enqueue_scripts', 'gi_dequeue_unnecessary_scripts', 100);
}
add_action('init', 'gi_performance_optimizations');

function gi_dequeue_unnecessary_scripts() {
    if (!is_admin()) {
        // 絵文字スクリプトの削除
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // 未使用のスクリプトの削除
        if (!is_singular() || !comments_open()) {
            wp_dequeue_script('comment-reply');
        }
    }
}

/**
 * セキュリティ強化（テーマエディター有効版）
 */
function gi_security_enhancements() {
    // WordPressバージョンの隠蔽
    remove_action('wp_head', 'wp_generator');
    
    // 不要なヘッダー情報の削除
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // XMLRPCの無効化
    add_filter('xmlrpc_enabled', '__return_false');
    
    // ログイン試行回数の制限
    add_action('wp_login_failed', 'gi_login_failed');
    add_filter('authenticate', 'gi_check_login_attempts', 30, 3);
}
add_action('init', 'gi_security_enhancements');

/**
 * ログイン失敗の記録
 */
function gi_login_failed($username) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $attempts = get_option('gi_login_attempts', []);
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = [];
    }
    
    $attempts[$ip][] = time();
    
    // 1時間以上前の試行を削除
    $attempts[$ip] = array_filter($attempts[$ip], function($time) {
        return $time > (time() - 3600);
    });
    
    update_option('gi_login_attempts', $attempts);
}

/**
 * ログイン試行チェック
 */
function gi_check_login_attempts($user, $username, $password) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $attempts = get_option('gi_login_attempts', []);
    
    if (isset($attempts[$ip]) && count($attempts[$ip]) >= 5) {
        return new WP_Error('too_many_attempts', 
            __('Too many login attempts. Please try again later.', 'grant-insight'));
    }
    
    return $user;
}

/**
 * 重要ニュース設定用カスタムフィールド追加
 */
function gi_add_news_importance_field() {
    add_meta_box(
        'gi_news_importance',
        '重要度設定',
        'gi_news_importance_callback',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'gi_add_news_importance_field');

function gi_news_importance_callback($post) {
    wp_nonce_field('gi_news_importance_nonce', 'gi_news_importance_nonce');
    $value = get_post_meta($post->ID, 'is_important_news', true);
    ?>
    <label for="is_important_news">
        <input type="checkbox" name="is_important_news" id="is_important_news" value="1" <?php checked($value, '1'); ?> />
        重要なお知らせとして表示
    </label>
    <p class="description">チェックすると、ニュース一覧の上部に優先表示されます。</p>
    <?php
}

function gi_save_news_importance($post_id) {
    if (!isset($_POST['gi_news_importance_nonce']) || 
        !wp_verify_nonce($_POST['gi_news_importance_nonce'], 'gi_news_importance_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['is_important_news'])) {
        update_post_meta($post_id, 'is_important_news', '1');
    } else {
        delete_post_meta($post_id, 'is_important_news');
    }
}
add_action('save_post', 'gi_save_news_importance');

/**
 * 最新ニュース取得関数
 */
function gi_get_latest_news($args = array()) {
    $defaults = array(
        'posts_per_page' => 6,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_type' => 'post',
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'is_important_news',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => 'is_important_news',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // 重要なニュースを優先
    $important_news = get_posts(array(
        'post_type' => 'post',
        'posts_per_page' => 2,
        'meta_key' => 'is_important_news',
        'meta_value' => '1',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    // 通常のニュース
    $regular_count = max(0, $args['posts_per_page'] - count($important_news));
    $regular_news = array();
    
    if ($regular_count > 0) {
        $regular_news = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => $regular_count,
            'meta_query' => array(
                array(
                    'key' => 'is_important_news',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }
    
    return array_merge($important_news, $regular_news);
}

/**
 * 都道府県データの自動セットアップ
 */
function gi_setup_prefecture_taxonomy_data() {
    $prefectures = array(
        '全国対応',
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県',
        '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
        '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県',
        '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );
    
    foreach ($prefectures as $prefecture) {
        $term = term_exists($prefecture, 'grant_prefecture');
        if (!$term) {
            wp_insert_term($prefecture, 'grant_prefecture', array(
                'description' => $prefecture . 'の助成金・補助金情報'
            ));
        }
    }
    
    // サンプル助成金データの投入（テスト用）
    gi_insert_sample_grants_with_prefectures();
}

/**
 * サンプル助成金データの投入（都道府県付き）
 */
function gi_insert_sample_grants_with_prefectures() {
    $sample_grants = array(
        array(
            'title' => 'IT導入補助金2024',
            'prefecture' => '全国対応',
            'amount' => 4500000,
            'category' => 'IT・デジタル化支援'
        ),
        array(
            'title' => '東京都中小企業DX推進補助金',
            'prefecture' => '東京都',
            'amount' => 3000000,
            'category' => 'IT・デジタル化支援'
        ),
        array(
            'title' => '大阪府ものづくり補助金',
            'prefecture' => '大阪府',
            'amount' => 10000000,
            'category' => '製造業・ものづくり'
        ),
        array(
            'title' => '愛知県創業支援補助金',
            'prefecture' => '愛知県',
            'amount' => 2000000,
            'category' => '創業・起業支援'
        ),
        array(
            'title' => '福岡県雇用促進助成金',
            'prefecture' => '福岡県',
            'amount' => 1500000,
            'category' => '人材育成・教育訓練'
        )
    );
    
    foreach ($sample_grants as $grant_data) {
        // 既存の投稿をチェック
        $existing = get_page_by_title($grant_data['title'], OBJECT, 'grant');
        if (!$existing) {
            $post_id = wp_insert_post(array(
                'post_title' => $grant_data['title'],
                'post_content' => $grant_data['title'] . 'の詳細情報です。この助成金は' . $grant_data['category'] . '分野の企業様を対象としています。',
                'post_type' => 'grant',
                'post_status' => 'publish',
                'meta_input' => array(
                    'max_amount' => number_format($grant_data['amount'] / 10000) . '万円',
                    'max_amount_numeric' => $grant_data['amount'],
                    'deadline_date' => strtotime('+3 months'),
                    'organization' => '経済産業省',
                    'application_status' => 'open'
                )
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // 都道府県を設定
                $prefecture_term = get_term_by('name', $grant_data['prefecture'], 'grant_prefecture');
                if ($prefecture_term) {
                    wp_set_post_terms($post_id, array($prefecture_term->term_id), 'grant_prefecture');
                }
                
                // カテゴリーを設定
                $category_term = get_term_by('name', $grant_data['category'], 'grant_category');
                if (!$category_term) {
                    $new_cat = wp_insert_term($grant_data['category'], 'grant_category');
                    if (!is_wp_error($new_cat)) {
                        wp_set_post_terms($post_id, array($new_cat['term_id']), 'grant_category');
                    }
                } else {
                    wp_set_post_terms($post_id, array($category_term->term_id), 'grant_category');
                }
            }
        }
    }
}

// テーマ有効化時に実行
add_action('after_switch_theme', 'gi_setup_prefecture_taxonomy_data');

// 管理画面で都道府県データを初期化するボタン
function gi_add_prefecture_init_button() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['init_prefecture_data']) && wp_verify_nonce($_POST['prefecture_nonce'], 'init_prefecture')) {
        gi_setup_prefecture_taxonomy_data();
        echo '<div class="notice notice-success"><p>都道府県データを初期化しました。</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h2>都道府県データ初期化</h2>
        <form method="post">
            <?php wp_nonce_field('init_prefecture', 'prefecture_nonce'); ?>
            <p>助成金の都道府県データとサンプルデータを初期化します。</p>
            <input type="submit" name="init_prefecture_data" class="button button-primary" value="都道府県データを初期化" />
        </form>
    </div>
    <?php
}

// 管理メニューに追加
function gi_add_admin_menu() {
    add_management_page(
        '都道府県データ初期化',
        '都道府県データ初期化',
        'manage_options',
        'gi-prefecture-init',
        'gi_add_prefecture_init_button'
    );
}
add_action('admin_menu', 'gi_add_admin_menu');

/**
 * テーマの最終初期化
 */
function gi_final_init() {
    // 初期化完了ログ
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Grant Insight Theme v6.2.1: Initialization completed successfully');
    }
}
add_action('wp_loaded', 'gi_final_init', 999);

/**
 * クリーンアップ処理
 */
function gi_theme_cleanup() {
    // 一時的なオプションの削除
    delete_option('gi_login_attempts');
    
    // キャッシュのクリア
    wp_cache_flush();
}
add_action('switch_theme', 'gi_theme_cleanup');

/**
 * ========================================
 * 修正完了・動作確認用のテスト関数
 * ========================================
 */

/**
 * テーマの動作確認用デバッグ関数
 */
function gi_debug_theme_status() {
    if (!current_user_can('administrator') || !WP_DEBUG) {
        return;
    }
    
    $debug_info = array(
        'version' => GI_THEME_VERSION,
        'post_types' => array(
            'grant' => post_type_exists('grant'),
            'tool' => post_type_exists('tool'),
            'case_study' => post_type_exists('case_study'),
            'guide' => post_type_exists('guide'),
            'grant_tip' => post_type_exists('grant_tip')
        ),
        'taxonomies' => array(
            'grant_category' => taxonomy_exists('grant_category'),
            'grant_prefecture' => taxonomy_exists('grant_prefecture'),
            'grant_tag' => taxonomy_exists('grant_tag'),
            'tool_category' => taxonomy_exists('tool_category'),
            'case_study_category' => taxonomy_exists('case_study_category'),
            'grant_tip_category' => taxonomy_exists('grant_tip_category')
        ),
        'functions' => array(
            'gi_get_formatted_deadline' => function_exists('gi_get_formatted_deadline'),
            'gi_safe_get_meta' => function_exists('gi_safe_get_meta'),
            'gi_render_grant_card' => function_exists('gi_render_grant_card'),
            'gi_get_user_favorites' => function_exists('gi_get_user_favorites')
        ),
        'ajax_actions' => array(
            'gi_load_grants' => has_action('wp_ajax_gi_load_grants'),
            'grant_insight_search' => has_action('wp_ajax_grant_insight_search'),
            'gi_toggle_favorite' => has_action('wp_ajax_gi_toggle_favorite')
        )
    );
    
    error_log('Grant Insight Debug Status: ' . print_r($debug_info, true));
}
add_action('init', 'gi_debug_theme_status', 999);
/**
 * ACF JSON 自動読み込み設定
 */
function gi_acf_json_load_point($paths) {
    // テーマディレクトリのacf-jsonフォルダを読み込みパスに追加
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
}
add_filter('acf/settings/load_json', 'gi_acf_json_load_point');

function gi_acf_json_save_point($path) {
    // 管理画面で編集した際の保存先をテーマディレクトリに設定
    return get_template_directory() . '/acf-json';
}
add_filter('acf/settings/save_json', 'gi_acf_json_save_point');

/**
 * Phase 1 改修機能の読み込み
 * カスタマイザー設定、アイコン管理、セキュリティ強化機能を含む
 */
if (file_exists(get_template_directory() . '/functions-integration.php')) {
    require_once get_template_directory() . '/functions-integration.php';
}

?>
