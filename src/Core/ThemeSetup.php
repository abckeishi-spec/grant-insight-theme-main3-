<?php

namespace GrantInsight\Core;

/**
 * Theme Setup Class
 * 
 * WordPressテーマの基本設定を管理するクラス
 * AIが理解しやすいように、責務を明確に分離
 */
class ThemeSetup
{
    /**
     * テーマバージョン
     */
    public const VERSION = '7.0.0';

    /**
     * テーマプレフィックス
     */
    public const PREFIX = 'gi_';

    /**
     * 初期化
     */
    public static function init(): void
    {
        add_action('after_setup_theme', [self::class, 'setup']);
        add_action('after_setup_theme', [self::class, 'contentWidth'], 0);
    }

    /**
     * テーマセットアップ
     */
    public static function setup(): void
    {
        // 基本的なテーマサポート
        self::addThemeSupports();
        
        // 画像サイズの登録
        self::registerImageSizes();
        
        // メニューの登録
        self::registerMenus();
        
        // 言語ファイルの読み込み
        self::loadTextDomain();
    }

    /**
     * テーマサポートの追加
     */
    private static function addThemeSupports(): void
    {
        $supports = [
            'title-tag',
            'post-thumbnails',
            'custom-background',
            'custom-logo' => [
                'height' => 250,
                'width' => 250,
                'flex-width' => true,
                'flex-height' => true,
            ],
            'html5' => [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script'
            ],
            'menus',
            'customize-selective-refresh-widgets',
            'responsive-embeds',
            'align-wide',
            'wp-block-styles',
            'automatic-feed-links'
        ];

        foreach ($supports as $feature => $args) {
            if (is_numeric($feature)) {
                add_theme_support($args);
            } else {
                add_theme_support($feature, $args);
            }
        }
    }

    /**
     * カスタム画像サイズの登録
     */
    private static function registerImageSizes(): void
    {
        $image_sizes = [
            'gi-card-thumb' => [400, 300, true],
            'gi-hero-thumb' => [800, 600, true],
            'gi-tool-logo' => [120, 120, true],
            'gi-banner' => [1200, 400, true],
        ];

        foreach ($image_sizes as $name => $args) {
            add_image_size($name, ...$args);
        }
    }

    /**
     * ナビゲーションメニューの登録
     */
    private static function registerMenus(): void
    {
        register_nav_menus([
            'primary' => 'メインメニュー',
            'footer' => 'フッターメニュー',
            'mobile' => 'モバイルメニュー'
        ]);
    }

    /**
     * 言語ファイルの読み込み
     */
    private static function loadTextDomain(): void
    {
        load_theme_textdomain(
            'grant-insight',
            get_template_directory() . '/languages'
        );
    }

    /**
     * コンテンツ幅の設定
     */
    public static function contentWidth(): void
    {
        $GLOBALS['content_width'] = apply_filters('gi_content_width', 1200);
    }

    /**
     * テーマバージョンを取得
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * テーマプレフィックスを取得
     */
    public static function getPrefix(): string
    {
        return self::PREFIX;
    }
}

