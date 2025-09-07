<?php

namespace GrantInsight\Core;

/**
 * Enqueue Class
 * 
 * CSS・JavaScriptの読み込みを管理するクラス
 */
class Enqueue
{
    /**
     * 初期化
     */
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [self::class, 'adminEnqueueScripts']);
    }

    /**
     * フロントエンド用スクリプト・スタイルの読み込み
     */
    public static function enqueueScripts(): void
    {
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
            wp_enqueue_style('gi-style-fallback', get_stylesheet_uri(), array(), ThemeSetup::getVersion());
        }

        // Font Awesome CDN
        wp_enqueue_style(
            'font-awesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 
            array(), 
            '6.4.0'
        );

        // Google Fonts
        wp_enqueue_style(
            'google-fonts',
            'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap',
            array(),
            ThemeSetup::getVersion()
        );

        // メインJavaScript
        wp_enqueue_script(
            'gi-main-js',
            get_template_directory_uri() . '/assets/js/main.js',
            array('jquery'),
            ThemeSetup::getVersion(),
            true
        );

        // AJAX用の設定
        wp_localize_script('gi-main-js', 'gi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_ajax_nonce'),
            'loading_text' => '読み込み中...',
            'error_text' => 'エラーが発生しました。'
        ));

        // 条件付きスクリプト
        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }

    /**
     * 管理画面用スクリプト・スタイルの読み込み
     */
    public static function adminEnqueueScripts(string $hook): void
    {
        // 管理画面用CSS
        wp_enqueue_style(
            'gi-admin-style',
            get_template_directory_uri() . '/assets/css/admin.css',
            array(),
            ThemeSetup::getVersion()
        );

        // 投稿編集画面でのみ読み込み
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_script(
                'gi-admin-js',
                get_template_directory_uri() . '/assets/js/admin.js',
                array('jquery'),
                ThemeSetup::getVersion(),
                true
            );
        }
    }

    /**
     * 不要なスクリプトの削除
     */
    public static function dequeueUnnecessaryScripts(): void
    {
        // WordPress標準のjQueryを削除（CDNから読み込む場合）
        if (!is_admin()) {
            wp_deregister_script('jquery');
            wp_register_script(
                'jquery',
                'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
                false,
                '3.6.0',
                true
            );
            wp_enqueue_script('jquery');
        }

        // 絵文字関連のスクリプトを削除
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }

    /**
     * アセットURLを取得
     */
    public static function getAssetUrl(string $path): string
    {
        return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
    }

    /**
     * メディアURLを取得
     */
    public static function getMediaUrl(string $filename, bool $fallback = true): string
    {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $filename;
        
        if (file_exists($file_path)) {
            return $upload_dir['baseurl'] . '/' . $filename;
        }
        
        if ($fallback) {
            return self::getAssetUrl('images/placeholder.jpg');
        }
        
        return '';
    }
}

