<?php
/**
 * Debug Template - Check what's happening with content display
 */

// WordPressが正しく読み込まれているか確認
echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333;'>";
echo "<h2>Debug Information</h2>";

// 現在のテンプレート
echo "<p><strong>Current Template:</strong> ";
if (is_front_page()) {
    echo "Front Page";
} elseif (is_home()) {
    echo "Blog Home";
} elseif (is_page()) {
    echo "Page";
} elseif (is_single()) {
    echo "Single Post";
} elseif (is_archive()) {
    echo "Archive";
} elseif (is_search()) {
    echo "Search";
} elseif (is_404()) {
    echo "404";
} else {
    echo "Unknown";
}
echo "</p>";

// 投稿情報
if (have_posts()) {
    echo "<p><strong>Posts Found:</strong> Yes</p>";
    
    while (have_posts()) {
        the_post();
        echo "<div style='margin: 10px 0; padding: 10px; background: white;'>";
        echo "<p><strong>Post ID:</strong> " . get_the_ID() . "</p>";
        echo "<p><strong>Post Title:</strong> " . get_the_title() . "</p>";
        echo "<p><strong>Post Type:</strong> " . get_post_type() . "</p>";
        echo "<p><strong>Post Status:</strong> " . get_post_status() . "</p>";
        
        // コンテンツの長さ
        $content = get_the_content();
        echo "<p><strong>Content Length:</strong> " . strlen($content) . " characters</p>";
        
        // コンテンツの最初の100文字
        if (!empty($content)) {
            echo "<p><strong>Content Preview:</strong> " . substr(strip_tags($content), 0, 100) . "...</p>";
        } else {
            echo "<p><strong>Content:</strong> <span style='color: red;'>EMPTY</span></p>";
        }
        echo "</div>";
    }
    rewind_posts();
} else {
    echo "<p style='color: red;'><strong>No Posts Found!</strong></p>";
}

// グローバル変数の確認
global $post, $wp_query;
echo "<p><strong>WP_Query Found Posts:</strong> " . $wp_query->found_posts . "</p>";
echo "<p><strong>WP_Query Post Count:</strong> " . $wp_query->post_count . "</p>";

// テンプレートファイル
echo "<p><strong>Template File Being Used:</strong> ";
global $template;
echo basename($template);
echo "</p>";

// PHPエラー
$error = error_get_last();
if ($error) {
    echo "<p><strong>Last PHP Error:</strong> " . print_r($error, true) . "</p>";
}

echo "</div>";
?>