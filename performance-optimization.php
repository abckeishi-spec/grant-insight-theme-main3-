<?php
/**
 * Performance Optimization for Grant Insight Theme
 */

// 1. 不要なWordPressデフォルト機能を無効化
function gi_remove_unnecessary_features() {
    // 絵文字関連のスクリプトとスタイルを削除
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    
    // WordPressのバージョン情報を削除
    remove_action('wp_head', 'wp_generator');
    
    // RSD Link削除
    remove_action('wp_head', 'rsd_link');
    
    // Windows Live Writer削除
    remove_action('wp_head', 'wlwmanifest_link');
    
    // 短縮URLを削除
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // DNS Prefetchingを最適化
    remove_action('wp_head', 'wp_resource_hints', 2);
    add_action('wp_head', 'gi_optimized_resource_hints', 2);
}
add_action('init', 'gi_remove_unnecessary_features');

// 2. 最適化されたDNS Prefetching
function gi_optimized_resource_hints() {
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
    echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";
    // Tailwind CDNは既にheader.phpにある
}

// 3. Gutenbergブロックエディター用CSSを無効化（使わない場合）
function gi_remove_block_css() {
    if (!is_admin()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
    }
}
add_action('wp_enqueue_scripts', 'gi_remove_block_css', 100);

// 4. jQuery Migrate を削除
function gi_remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
add_action('wp_default_scripts', 'gi_remove_jquery_migrate');

// 5. 画像の遅延読み込み
function gi_add_lazy_loading() {
    if (!is_admin()) {
        add_filter('wp_get_attachment_image_attributes', function($attributes) {
            if (!isset($attributes['loading'])) {
                $attributes['loading'] = 'lazy';
            }
            return $attributes;
        });
    }
}
add_action('init', 'gi_add_lazy_loading');

// 6. スクリプトとスタイルシートの最適化
function gi_optimize_scripts() {
    if (!is_admin()) {
        // 不要なスクリプトを削除
        wp_deregister_script('wp-embed');
        
        // スクリプトにdefer属性を追加
        add_filter('script_loader_tag', function($tag, $handle) {
            // 除外するハンドル
            $exclude = array('jquery', 'jquery-core');
            
            if (!in_array($handle, $exclude)) {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }
}
add_action('wp_enqueue_scripts', 'gi_optimize_scripts', 99);

// 7. HTMLの圧縮（本番環境のみ推奨）
function gi_compress_html($html) {
    if (!WP_DEBUG && !is_admin()) {
        $search = array(
            '/\>[^\S ]+/s',  // 空白を削除
            '/[^\S ]+\</s',  // 空白を削除
            '/(\s)+/s',      // 複数の空白を1つに
            '/<!--(.|\s)*?-->/' // HTMLコメントを削除
        );
        $replace = array('>', '<', '\\1', '');
        $html = preg_replace($search, $replace, $html);
    }
    return $html;
}
// 注意: この機能は慎重に使用してください
// add_filter('wp_loaded', function() {
//     ob_start('gi_compress_html');
// });

// 8. 不要なクエリ文字列を削除
function gi_remove_query_strings($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'gi_remove_query_strings', 15, 1);
add_filter('style_loader_src', 'gi_remove_query_strings', 15, 1);

// 9. キャッシュヘッダーの設定
function gi_set_cache_headers() {
    if (!is_admin() && !is_user_logged_in()) {
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    }
}
add_action('send_headers', 'gi_set_cache_headers');

// 10. データベースクエリの最適化
function gi_optimize_queries() {
    // トランジェントを使用してクエリ結果をキャッシュ
    add_filter('posts_results', function($posts, $query) {
        if (!is_admin() && $query->is_main_query()) {
            // メインクエリの結果を30分キャッシュ
            $cache_key = 'gi_query_' . md5(serialize($query->query_vars));
            set_transient($cache_key, $posts, 30 * MINUTE_IN_SECONDS);
        }
        return $posts;
    }, 10, 2);
}
add_action('init', 'gi_optimize_queries');

?>