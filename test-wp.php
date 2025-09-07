<?php
/**
 * WordPress Test File - Debug why content is not showing
 */

// Load WordPress
require_once('wp-load.php');

// Test if WordPress is loaded
echo "WordPress Loaded: " . (defined('ABSPATH') ? 'YES' : 'NO') . "\n\n";

// Check if we have posts
$args = array(
    'post_type' => 'post',
    'posts_per_page' => 5,
    'post_status' => 'publish'
);

$query = new WP_Query($args);

echo "Found Posts: " . $query->found_posts . "\n\n";

if ($query->have_posts()) {
    echo "Posts exist and loop works!\n\n";
    while ($query->have_posts()) {
        $query->the_post();
        echo "- " . get_the_title() . " (ID: " . get_the_ID() . ")\n";
    }
    wp_reset_postdata();
} else {
    echo "No posts found or loop not working.\n";
}

// Check custom post types
$grant_args = array(
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'post_status' => 'publish'
);

$grant_query = new WP_Query($grant_args);
echo "\n\nFound Grants: " . $grant_query->found_posts . "\n";

// Check if post types are registered
echo "\n\nRegistered Post Types:\n";
$post_types = get_post_types(array('public' => true), 'names');
foreach ($post_types as $post_type) {
    echo "- " . $post_type . "\n";
}

// Check for PHP errors
echo "\n\nPHP Error Reporting Level: " . error_reporting() . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";

// Check theme functions
echo "\n\nTheme Functions Check:\n";
echo "Theme Directory: " . get_template_directory() . "\n";
echo "Theme Version: " . (defined('GI_THEME_VERSION') ? GI_THEME_VERSION : 'NOT DEFINED') . "\n";

// Check if functions are defined
$functions_to_check = array(
    'gi_ajax_load_grants',
    'gi_customize_register',
    'gi_safe_get_meta',
    'gi_calculate_match_score'
);

echo "\n\nFunction Existence Check:\n";
foreach ($functions_to_check as $func) {
    echo "- $func: " . (function_exists($func) ? 'EXISTS' : 'NOT FOUND') . "\n";
}
?>