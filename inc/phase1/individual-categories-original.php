<?php
/**
 * Individual/Personal Category Implementation
 * 
 * Task 10: 個人向けカテゴリ実装
 * Implements individual/personal category functionality for grants
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 10.1 個人向けフラグACFフィールド追加
 * Register ACF field for individual/personal targeting
 */
function gi_register_individual_target_field() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    // Add field group for individual targeting
    acf_add_local_field_group(array(
        'key' => 'group_individual_target',
        'title' => '個人向け設定',
        'fields' => array(
            array(
                'key' => 'field_target_individual',
                'label' => '個人向け補助金',
                'name' => 'target_individual',
                'type' => 'true_false',
                'instructions' => 'この補助金が個人事業主、フリーランス、個人向けの場合はチェックしてください',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => '個人向け',
                'ui_off_text' => '法人向け',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'grant',
                ),
            ),
        ),
        'menu_order' => 5,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '個人向け補助金の識別フラグ',
    ));
}
add_action('acf/init', 'gi_register_individual_target_field');

/**
 * 10.2 個人向けカテゴリー作成
 * Create individual/personal categories
 */
function gi_create_individual_categories() {
    // Define individual categories
    $individual_categories = array(
        '個人事業主' => array(
            'slug' => 'sole-proprietor',
            'description' => '個人事業主向けの補助金・助成金'
        ),
        'フリーランス' => array(
            'slug' => 'freelancer',
            'description' => 'フリーランス向けの補助金・助成金'
        ),
        '個人' => array(
            'slug' => 'individual',
            'description' => '個人向けの補助金・助成金'
        ),
    );
    
    // Check if taxonomy exists
    if (!taxonomy_exists('grant_category')) {
        return;
    }
    
    // Create categories if they don't exist
    foreach ($individual_categories as $name => $data) {
        $term = term_exists($name, 'grant_category');
        
        if (!$term) {
            $result = wp_insert_term(
                $name,
                'grant_category',
                array(
                    'slug' => $data['slug'],
                    'description' => $data['description']
                )
            );
            
            if (!is_wp_error($result)) {
                // Add meta to identify as individual category
                add_term_meta($result['term_id'], 'is_individual_category', true, true);
            }
        }
    }
}
add_action('init', 'gi_create_individual_categories', 20);

/**
 * 10.3 検索フィルター追加
 * Add individual filter to search functionality
 */
function gi_add_individual_search_filter($query_args, $filters) {
    // Check if individual filter is set
    if (isset($filters['individual_only']) && $filters['individual_only']) {
        // Add meta query for individual targeting
        if (!isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array();
        }
        
        $query_args['meta_query'][] = array(
            'key' => 'target_individual',
            'value' => '1',
            'compare' => '='
        );
    }
    
    return $query_args;
}
add_filter('gi_grant_search_query_args', 'gi_add_individual_search_filter', 10, 2);

/**
 * Add individual filter to AJAX search handler
 */
function gi_ajax_filter_individual_grants() {
    // Verify nonce
    if (!check_ajax_referer('gi_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました。'));
        return;
    }
    
    try {
        // Get filter parameters
        $individual_only = isset($_POST['individual_only']) ? 
                          filter_var($_POST['individual_only'], FILTER_VALIDATE_BOOLEAN) : false;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 12;
        
        // Build query
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Add individual filter
        if ($individual_only) {
            $args['meta_query'] = array(
                array(
                    'key' => 'target_individual',
                    'value' => '1',
                    'compare' => '='
                )
            );
        }
        
        // Execute query
        $query = new WP_Query($args);
        
        // Prepare results
        $grants = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = gi_format_grant_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // Send response
        wp_send_json_success(array(
            'grants' => $grants,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
            'message' => $individual_only ? '個人向け補助金を表示中' : '全ての補助金を表示中'
        ));
        
    } catch (Exception $e) {
        error_log('Individual filter error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'フィルタリング中にエラーが発生しました。'));
    }
}
add_action('wp_ajax_gi_filter_individual_grants', 'gi_ajax_filter_individual_grants');
add_action('wp_ajax_nopriv_gi_filter_individual_grants', 'gi_ajax_filter_individual_grants');

/**
 * 10.4 表示フラグ追加
 * Add individual badge to grant display
 */
function gi_display_individual_badge($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $is_individual = get_field('target_individual', $post_id);
    
    if ($is_individual) {
        echo '<span class="individual-badge" aria-label="個人向け">';
        echo '<svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>';
        echo '</svg>';
        echo '個人向け';
        echo '</span>';
    }
}

/**
 * Add CSS for individual badge
 */
function gi_individual_badge_styles() {
    ?>
    <style>
        .individual-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            margin-left: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        
        .individual-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }
        
        .individual-filter-toggle {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .individual-filter-toggle:hover {
            border-color: #667eea;
            background: #f3f4f6;
        }
        
        .individual-filter-toggle.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .individual-category-tag {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            margin-right: 0.25rem;
        }
        
        .individual-category-tag.highlight {
            background: #fef3c7;
            color: #92400e;
            font-weight: 600;
        }
    </style>
    <?php
}
add_action('wp_head', 'gi_individual_badge_styles');

/**
 * 10.5 検索フォームに個人向けフィルター追加
 * Add individual filter toggle to search form
 */
function gi_add_individual_filter_to_search_form() {
    ?>
    <div class="individual-filter-container mb-4">
        <label class="individual-filter-toggle" id="individual-filter-toggle">
            <input type="checkbox" name="individual_only" id="individual-only-checkbox" class="sr-only">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
            <span>個人向けのみ表示</span>
        </label>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle individual filter toggle
        $('#individual-filter-toggle').on('click', function(e) {
            e.preventDefault();
            const checkbox = $('#individual-only-checkbox');
            const isChecked = !checkbox.prop('checked');
            checkbox.prop('checked', isChecked);
            $(this).toggleClass('active', isChecked);
            
            // Trigger search update
            if (typeof window.updateGrantSearch === 'function') {
                window.updateGrantSearch();
            }
        });
    });
    </script>
    <?php
}
add_action('gi_search_form_filters', 'gi_add_individual_filter_to_search_form');

/**
 * Helper function to check if a grant is for individuals
 */
function gi_is_individual_grant($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return (bool) get_field('target_individual', $post_id);
}

/**
 * Get all individual categories
 */
function gi_get_individual_categories() {
    $args = array(
        'taxonomy' => 'grant_category',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => 'is_individual_category',
                'value' => true,
                'compare' => '='
            )
        )
    );
    
    return get_terms($args);
}

/**
 * Display individual category selector
 */
function gi_display_individual_category_selector($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $is_individual = gi_is_individual_grant($post_id);
    
    if (!$is_individual) {
        return;
    }
    
    $individual_categories = gi_get_individual_categories();
    $current_categories = wp_get_post_terms($post_id, 'grant_category', array('fields' => 'ids'));
    
    if (!empty($individual_categories)) {
        echo '<div class="individual-categories-selector">';
        echo '<label class="block text-sm font-medium text-gray-700 mb-2">個人向けカテゴリ:</label>';
        echo '<div class="flex flex-wrap gap-2">';
        
        foreach ($individual_categories as $category) {
            $is_selected = in_array($category->term_id, $current_categories);
            $class = $is_selected ? 'individual-category-tag highlight' : 'individual-category-tag';
            
            echo sprintf(
                '<span class="%s">%s</span>',
                esc_attr($class),
                esc_html($category->name)
            );
        }
        
        echo '</div>';
        echo '</div>';
    }
}

/**
 * Auto-assign individual categories based on content
 */
function gi_auto_assign_individual_categories($post_id) {
    // Check if it's a grant post type
    if (get_post_type($post_id) !== 'grant') {
        return;
    }
    
    // Check if it's marked as individual
    if (!gi_is_individual_grant($post_id)) {
        return;
    }
    
    // Get post content and title
    $post = get_post($post_id);
    $content = $post->post_content . ' ' . $post->post_title;
    $content_lower = mb_strtolower($content);
    
    $categories_to_assign = array();
    
    // Check for keywords
    if (strpos($content_lower, '個人事業') !== false || 
        strpos($content_lower, '個人事業主') !== false) {
        $term = get_term_by('slug', 'sole-proprietor', 'grant_category');
        if ($term) {
            $categories_to_assign[] = $term->term_id;
        }
    }
    
    if (strpos($content_lower, 'フリーランス') !== false) {
        $term = get_term_by('slug', 'freelancer', 'grant_category');
        if ($term) {
            $categories_to_assign[] = $term->term_id;
        }
    }
    
    // Default to general individual category if no specific match
    if (empty($categories_to_assign)) {
        $term = get_term_by('slug', 'individual', 'grant_category');
        if ($term) {
            $categories_to_assign[] = $term->term_id;
        }
    }
    
    // Assign categories
    if (!empty($categories_to_assign)) {
        wp_set_post_terms($post_id, $categories_to_assign, 'grant_category', true);
    }
}
add_action('save_post_grant', 'gi_auto_assign_individual_categories', 20);

/**
 * Format grant data for AJAX responses
 */
function gi_format_grant_data($post_id) {
    $grant_data = array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'excerpt' => get_the_excerpt($post_id),
        'link' => get_permalink($post_id),
        'date' => get_the_date('Y年m月d日', $post_id),
        'is_individual' => gi_is_individual_grant($post_id),
        'categories' => array(),
        'max_amount' => get_field('max_amount', $post_id),
        'application_deadline' => get_field('application_deadline', $post_id),
    );
    
    // Get categories
    $categories = wp_get_post_terms($post_id, 'grant_category');
    if (!is_wp_error($categories)) {
        foreach ($categories as $category) {
            $grant_data['categories'][] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_individual' => (bool) get_term_meta($category->term_id, 'is_individual_category', true)
            );
        }
    }
    
    return $grant_data;
}

/**
 * Add individual grants count to dashboard
 */
function gi_add_individual_grants_dashboard_widget() {
    wp_add_dashboard_widget(
        'individual_grants_stats',
        '個人向け補助金統計',
        'gi_display_individual_grants_stats'
    );
}
add_action('wp_dashboard_setup', 'gi_add_individual_grants_dashboard_widget');

/**
 * Display individual grants statistics
 */
function gi_display_individual_grants_stats() {
    // Count individual grants
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'target_individual',
                'value' => '1',
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    
    $query = new WP_Query($args);
    $individual_count = $query->found_posts;
    
    // Get total grants count
    $total_args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    
    $total_query = new WP_Query($total_args);
    $total_count = $total_query->found_posts;
    
    // Calculate percentage
    $percentage = $total_count > 0 ? round(($individual_count / $total_count) * 100, 1) : 0;
    
    // Get category breakdown
    $categories = gi_get_individual_categories();
    
    echo '<div class="individual-grants-stats">';
    echo '<p class="text-lg mb-4">';
    echo sprintf(
        '<strong>個人向け補助金:</strong> %d件 / 全体 %d件 (%s%%)',
        $individual_count,
        $total_count,
        $percentage
    );
    echo '</p>';
    
    if (!empty($categories)) {
        echo '<h4 class="font-semibold mb-2">カテゴリ別内訳:</h4>';
        echo '<ul class="category-breakdown">';
        
        foreach ($categories as $category) {
            $cat_args = array(
                'post_type' => 'grant',
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'grant_category',
                        'terms' => $category->term_id
                    )
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            
            $cat_query = new WP_Query($cat_args);
            
            echo sprintf(
                '<li>%s: %d件</li>',
                esc_html($category->name),
                $cat_query->found_posts
            );
        }
        
        echo '</ul>';
    }
    
    echo '<div class="mt-4">';
    echo '<a href="' . admin_url('edit.php?post_type=grant&individual_only=1') . '" class="button button-primary">個人向け補助金を表示</a>';
    echo '</div>';
    
    echo '</div>';
    
    ?>
    <style>
        .individual-grants-stats {
            padding: 10px 0;
        }
        .category-breakdown {
            margin-left: 20px;
            list-style-type: disc;
        }
        .category-breakdown li {
            margin: 5px 0;
        }
    </style>
    <?php
}

/**
 * Add admin filter for individual grants
 */
function gi_add_admin_individual_filter() {
    global $typenow;
    
    if ($typenow !== 'grant') {
        return;
    }
    
    $selected = isset($_GET['individual_only']) ? $_GET['individual_only'] : '';
    
    ?>
    <select name="individual_only" id="individual_only_filter">
        <option value="">全ての補助金</option>
        <option value="1" <?php selected($selected, '1'); ?>>個人向けのみ</option>
        <option value="0" <?php selected($selected, '0'); ?>>法人向けのみ</option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'gi_add_admin_individual_filter');

/**
 * Filter admin posts list by individual status
 */
function gi_filter_admin_posts_by_individual($query) {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'grant' || !is_admin()) {
        return;
    }
    
    if (isset($_GET['individual_only']) && $_GET['individual_only'] !== '') {
        $value = $_GET['individual_only'] === '1' ? '1' : '0';
        
        $query->set('meta_query', array(
            array(
                'key' => 'target_individual',
                'value' => $value,
                'compare' => '='
            )
        ));
    }
}
add_action('pre_get_posts', 'gi_filter_admin_posts_by_individual');

// Initialize individual categories on plugin activation
register_activation_hook(__FILE__, 'gi_create_individual_categories');