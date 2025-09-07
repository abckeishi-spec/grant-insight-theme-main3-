<?php
/**
 * パフォーマンス最適化
 * 
 * このファイルでは、WordPressサイトのパフォーマンスを向上させるための機能を提供します。
 * 画像の遅延読み込み、不要なスクリプトの削除などが含まれます。
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * パフォーマンス最適化
 */
function gi_performance_optimizations() {
    // 画像の遅延読み込み
    add_filter("wp_lazy_loading_enabled", "__return_true");
    
    // 不要なスクリプトの削除
    add_action("wp_enqueue_scripts", "gi_dequeue_unnecessary_scripts", 100);
}
add_action("init", "gi_performance_optimizations");

function gi_dequeue_unnecessary_scripts() {
    if (!is_admin()) {
        // 絵文字スクリプトの削除
        remove_action("wp_head", "print_emoji_detection_script", 7);
        remove_action("wp_print_styles", "print_emoji_styles");
        
        // 未使用のスクリプトの削除
        if (!is_singular() || !comments_open()) {
            wp_dequeue_script("comment-reply");
        }
    }
}


