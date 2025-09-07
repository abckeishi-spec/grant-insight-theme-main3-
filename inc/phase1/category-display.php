<?php
/**
 * Category Display Functions
 * 
 * Task 11: 全カテゴリ表示機能
 * Implements comprehensive category display with show more and infinite scroll
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 11.1 全カテゴリ表示関数
 * Display all categories with optional limit
 */
function gi_display_all_categories($args = array()) {
    // Default arguments
    $defaults = array(
        'initial_count' => 10,          // Initial number of categories to show
        'show_more_count' => 10,         // Number of categories to load on "show more"
        'show_count' => true,            // Show post count
        'show_description' => true,      // Show category description
        'show_icon' => true,             // Show category icon
        'enable_show_more' => true,      // Enable "show more" button
        'enable_infinite_scroll' => false, // Enable infinite scroll
        'container_class' => 'categories-grid', // Container CSS class
        'item_class' => 'category-item', // Item CSS class
        'hide_empty' => false,           // Hide empty categories
        'orderby' => 'count',            // Order by post count
        'order' => 'DESC',               // Order direction
        'exclude' => array(),            // Categories to exclude
        'include_individual' => true,    // Include individual categories
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Build query arguments
    $query_args = array(
        'taxonomy' => 'grant_category',
        'hide_empty' => $args['hide_empty'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
    );
    
    // Handle exclude
    if (!empty($args['exclude'])) {
        $query_args['exclude'] = $args['exclude'];
    }
    
    // Get all categories
    $categories = get_terms($query_args);
    
    if (is_wp_error($categories) || empty($categories)) {
        echo '<div class="no-categories-found">カテゴリが見つかりませんでした。</div>';
        return;
    }
    
    // Separate individual and regular categories if needed
    $individual_categories = array();
    $regular_categories = array();
    
    foreach ($categories as $category) {
        $is_individual = get_term_meta($category->term_id, 'is_individual_category', true);
        
        if ($is_individual && $args['include_individual']) {
            $individual_categories[] = $category;
        } else {
            $regular_categories[] = $category;
        }
    }
    
    // Output container
    $container_id = 'categories-container-' . uniqid();
    echo '<div id="' . esc_attr($container_id) . '" class="' . esc_attr($args['container_class']) . '" data-args="' . esc_attr(json_encode($args)) . '">';
    
    // Display individual categories section if applicable
    if (!empty($individual_categories) && $args['include_individual']) {
        echo '<div class="individual-categories-section mb-8">';
        echo '<h3 class="section-title text-xl font-bold mb-4">';
        echo '<svg class="w-6 h-6 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>';
        echo '</svg>';
        echo '個人向けカテゴリ';
        echo '</h3>';
        echo '<div class="individual-categories-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
        
        foreach ($individual_categories as $category) {
            gi_display_single_category($category, $args);
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Display regular categories
    if (!empty($regular_categories)) {
        echo '<div class="regular-categories-section">';
        echo '<h3 class="section-title text-xl font-bold mb-4">補助金カテゴリ一覧</h3>';
        echo '<div class="regular-categories-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" data-initial-count="' . esc_attr($args['initial_count']) . '">';
        
        $display_count = 0;
        foreach ($regular_categories as $index => $category) {
            $is_hidden = $index >= $args['initial_count'];
            gi_display_single_category($category, $args, $is_hidden);
            $display_count++;
        }
        
        echo '</div>';
        
        // Show more button
        if ($args['enable_show_more'] && count($regular_categories) > $args['initial_count']) {
            $remaining = count($regular_categories) - $args['initial_count'];
            echo '<div class="show-more-container text-center mt-6">';
            echo '<button class="show-more-categories-btn" data-container="' . esc_attr($container_id) . '" data-remaining="' . esc_attr($remaining) . '">';
            echo '<span class="btn-text">さらに表示</span>';
            echo '<span class="remaining-count">（残り' . $remaining . '件）</span>';
            echo '<svg class="w-5 h-5 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>';
            echo '</svg>';
            echo '</button>';
            echo '</div>';
        }
        
        // Infinite scroll trigger
        if ($args['enable_infinite_scroll']) {
            echo '<div class="infinite-scroll-trigger" data-container="' . esc_attr($container_id) . '"></div>';
            echo '<div class="infinite-scroll-loader hidden">';
            echo '<div class="spinner"></div>';
            echo '<span>読み込み中...</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>'; // Close main container
    
    // Add inline styles
    gi_category_display_styles();
    
    // Add JavaScript
    gi_category_display_scripts($container_id, $args);
}

/**
 * 11.2 単一カテゴリ表示
 * Display single category item
 */
function gi_display_single_category($category, $args, $is_hidden = false) {
    $post_count = $category->count;
    $category_link = get_term_link($category);
    $category_icon = get_term_meta($category->term_id, 'category_icon', true);
    $is_individual = get_term_meta($category->term_id, 'is_individual_category', true);
    
    $item_classes = array($args['item_class']);
    if ($is_hidden) {
        $item_classes[] = 'hidden-category';
    }
    if ($is_individual) {
        $item_classes[] = 'individual-category';
    }
    
    ?>
    <div class="<?php echo esc_attr(implode(' ', $item_classes)); ?>" data-category-id="<?php echo esc_attr($category->term_id); ?>">
        <a href="<?php echo esc_url($category_link); ?>" class="category-card-link">
            <div class="category-card">
                <?php if ($args['show_icon'] && $category_icon): ?>
                    <div class="category-icon">
                        <?php if (strpos($category_icon, 'http') === 0): ?>
                            <img src="<?php echo esc_url($category_icon); ?>" alt="<?php echo esc_attr($category->name); ?>" class="icon-image">
                        <?php else: ?>
                            <span class="icon-emoji"><?php echo esc_html($category_icon); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="category-content">
                    <h4 class="category-name"><?php echo esc_html($category->name); ?></h4>
                    
                    <?php if ($args['show_description'] && !empty($category->description)): ?>
                        <p class="category-description"><?php echo esc_html(wp_trim_words($category->description, 20)); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($args['show_count']): ?>
                        <div class="category-meta">
                            <span class="post-count">
                                <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.5a1 1 0 000-2H8a2 2 0 012-2h4a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                                </svg>
                                <?php echo number_format($post_count); ?>件
                            </span>
                            
                            <?php if ($is_individual): ?>
                                <span class="individual-tag">個人向け</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="category-arrow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>
    <?php
}

/**
 * 11.3 「もっと見る」機能実装
 * Implement "Show More" functionality via AJAX
 */
function gi_ajax_load_more_categories() {
    // Verify nonce
    if (!check_ajax_referer('gi_categories_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました。'));
        return;
    }
    
    try {
        // Get parameters
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $count = isset($_POST['count']) ? intval($_POST['count']) : 10;
        $args = isset($_POST['args']) ? json_decode(stripslashes($_POST['args']), true) : array();
        
        // Build query
        $query_args = array(
            'taxonomy' => 'grant_category',
            'hide_empty' => isset($args['hide_empty']) ? $args['hide_empty'] : false,
            'orderby' => isset($args['orderby']) ? $args['orderby'] : 'count',
            'order' => isset($args['order']) ? $args['order'] : 'DESC',
            'offset' => $offset,
            'number' => $count,
        );
        
        // Get categories
        $categories = get_terms($query_args);
        
        if (is_wp_error($categories)) {
            throw new Exception('カテゴリの取得に失敗しました。');
        }
        
        // Generate HTML
        ob_start();
        foreach ($categories as $category) {
            gi_display_single_category($category, $args);
        }
        $html = ob_get_clean();
        
        // Get total count for remaining calculation
        $total_args = $query_args;
        unset($total_args['offset'], $total_args['number']);
        $all_categories = get_terms($total_args);
        $total = is_array($all_categories) ? count($all_categories) : 0;
        $remaining = max(0, $total - ($offset + $count));
        
        wp_send_json_success(array(
            'html' => $html,
            'loaded' => count($categories),
            'remaining' => $remaining,
            'has_more' => $remaining > 0
        ));
        
    } catch (Exception $e) {
        error_log('Load more categories error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'カテゴリの読み込み中にエラーが発生しました。'));
    }
}
add_action('wp_ajax_gi_load_more_categories', 'gi_ajax_load_more_categories');
add_action('wp_ajax_nopriv_gi_load_more_categories', 'gi_ajax_load_more_categories');

/**
 * 11.4 無限スクロール対応
 * Implement infinite scroll functionality
 */
function gi_category_display_scripts($container_id, $args) {
    ?>
    <script>
    (function() {
        // Show More functionality
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('<?php echo esc_js($container_id); ?>');
            if (!container) return;
            
            // Show more button handler
            const showMoreBtn = container.querySelector('.show-more-categories-btn');
            if (showMoreBtn) {
                showMoreBtn.addEventListener('click', function() {
                    const hiddenCategories = container.querySelectorAll('.hidden-category');
                    const showCount = <?php echo intval($args['show_more_count']); ?>;
                    let shown = 0;
                    
                    hiddenCategories.forEach(function(category, index) {
                        if (index < showCount) {
                            category.classList.remove('hidden-category');
                            category.classList.add('fade-in');
                            shown++;
                        }
                    });
                    
                    // Update remaining count
                    const remaining = hiddenCategories.length - shown;
                    if (remaining <= 0) {
                        showMoreBtn.style.display = 'none';
                    } else {
                        const remainingSpan = showMoreBtn.querySelector('.remaining-count');
                        if (remainingSpan) {
                            remainingSpan.textContent = '（残り' + remaining + '件）';
                        }
                    }
                });
            }
            
            <?php if ($args['enable_infinite_scroll']): ?>
            // Infinite scroll functionality
            let isLoading = false;
            let offset = <?php echo intval($args['initial_count']); ?>;
            const trigger = container.querySelector('.infinite-scroll-trigger');
            const loader = container.querySelector('.infinite-scroll-loader');
            
            if (trigger) {
                const observerOptions = {
                    root: null,
                    rootMargin: '100px',
                    threshold: 0.1
                };
                
                const observerCallback = function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting && !isLoading) {
                            loadMoreCategories();
                        }
                    });
                };
                
                const observer = new IntersectionObserver(observerCallback, observerOptions);
                observer.observe(trigger);
                
                function loadMoreCategories() {
                    if (isLoading) return;
                    
                    isLoading = true;
                    if (loader) {
                        loader.classList.remove('hidden');
                    }
                    
                    // Prepare data
                    const formData = new FormData();
                    formData.append('action', 'gi_load_more_categories');
                    formData.append('nonce', '<?php echo wp_create_nonce('gi_categories_nonce'); ?>');
                    formData.append('offset', offset);
                    formData.append('count', <?php echo intval($args['show_more_count']); ?>);
                    formData.append('args', JSON.stringify(<?php echo json_encode($args); ?>));
                    
                    // Make AJAX request
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add new categories
                            const grid = container.querySelector('.regular-categories-grid');
                            if (grid && data.data.html) {
                                const temp = document.createElement('div');
                                temp.innerHTML = data.data.html;
                                
                                while (temp.firstChild) {
                                    grid.appendChild(temp.firstChild);
                                }
                            }
                            
                            // Update offset
                            offset += data.data.loaded;
                            
                            // Check if more to load
                            if (!data.data.has_more) {
                                observer.disconnect();
                                if (trigger) {
                                    trigger.remove();
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading categories:', error);
                    })
                    .finally(() => {
                        isLoading = false;
                        if (loader) {
                            loader.classList.add('hidden');
                        }
                    });
                }
            }
            <?php endif; ?>
        });
    })();
    </script>
    <?php
}

/**
 * Add category display styles
 */
function gi_category_display_styles() {
    ?>
    <style>
        /* Category Grid Styles */
        .categories-grid {
            padding: 2rem 0;
        }
        
        .category-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .category-card:hover {
            border-color: #6366f1;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.1), 0 10px 10px -5px rgba(99, 102, 241, 0.04);
            transform: translateY(-2px);
        }
        
        .category-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .category-icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.5rem;
            color: white;
        }
        
        .category-icon .icon-image {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        
        .category-icon .icon-emoji {
            font-size: 1.5rem;
        }
        
        .category-content {
            flex: 1;
            min-width: 0;
        }
        
        .category-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .category-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        
        .category-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }
        
        .post-count {
            display: inline-flex;
            align-items: center;
            color: #6b7280;
        }
        
        .individual-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        
        .category-arrow {
            position: absolute;
            right: 1rem;
            color: #9ca3af;
            transition: all 0.3s ease;
        }
        
        .category-card:hover .category-arrow {
            right: 0.75rem;
            color: #6366f1;
        }
        
        /* Hidden categories */
        .hidden-category {
            display: none;
        }
        
        /* Fade in animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Show more button */
        .show-more-categories-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 2rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 9999px;
            font-weight: 600;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .show-more-categories-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.25);
        }
        
        .remaining-count {
            margin-left: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.75;
        }
        
        /* Infinite scroll loader */
        .infinite-scroll-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .infinite-scroll-loader .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #e5e7eb;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 0.75rem;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Section titles */
        .section-title {
            display: flex;
            align-items: center;
            color: #111827;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        /* Individual categories section */
        .individual-categories-section {
            background: linear-gradient(135deg, #f3f4f6 0%, #fef3c7 100%);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .individual-category .category-card {
            border-color: #fbbf24;
            background: #fffbeb;
        }
        
        .individual-category .category-card:hover {
            border-color: #f59e0b;
            background: white;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .category-card {
                padding: 1rem;
            }
            
            .category-icon {
                width: 40px;
                height: 40px;
            }
            
            .category-name {
                font-size: 1rem;
            }
            
            .show-more-categories-btn {
                padding: 0.625rem 1.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
    <?php
}

/**
 * Shortcode for displaying all categories
 */
function gi_all_categories_shortcode($atts) {
    $atts = shortcode_atts(array(
        'initial_count' => 10,
        'show_more_count' => 10,
        'show_count' => 'true',
        'show_description' => 'true',
        'show_icon' => 'true',
        'enable_show_more' => 'true',
        'enable_infinite_scroll' => 'false',
        'hide_empty' => 'false',
        'orderby' => 'count',
        'order' => 'DESC',
        'include_individual' => 'true',
    ), $atts);
    
    // Convert string booleans to actual booleans
    foreach (array('show_count', 'show_description', 'show_icon', 'enable_show_more', 'enable_infinite_scroll', 'hide_empty', 'include_individual') as $key) {
        $atts[$key] = filter_var($atts[$key], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Convert numeric strings to integers
    $atts['initial_count'] = intval($atts['initial_count']);
    $atts['show_more_count'] = intval($atts['show_more_count']);
    
    ob_start();
    gi_display_all_categories($atts);
    return ob_get_clean();
}
add_shortcode('gi_all_categories', 'gi_all_categories_shortcode');

/**
 * Widget for displaying categories
 */
class GI_Categories_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'gi_categories_widget',
            '補助金カテゴリ一覧',
            array('description' => '補助金のカテゴリを表示します')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Display categories
        gi_display_all_categories(array(
            'initial_count' => isset($instance['initial_count']) ? $instance['initial_count'] : 5,
            'show_more_count' => isset($instance['show_more_count']) ? $instance['show_more_count'] : 5,
            'show_count' => isset($instance['show_count']) ? $instance['show_count'] : true,
            'show_description' => isset($instance['show_description']) ? $instance['show_description'] : false,
            'show_icon' => isset($instance['show_icon']) ? $instance['show_icon'] : true,
            'enable_show_more' => isset($instance['enable_show_more']) ? $instance['enable_show_more'] : true,
            'enable_infinite_scroll' => false,
            'container_class' => 'widget-categories-grid',
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '補助金カテゴリ';
        $initial_count = !empty($instance['initial_count']) ? $instance['initial_count'] : 5;
        $show_more_count = !empty($instance['show_more_count']) ? $instance['show_more_count'] : 5;
        $show_count = isset($instance['show_count']) ? $instance['show_count'] : true;
        $show_description = isset($instance['show_description']) ? $instance['show_description'] : false;
        $show_icon = isset($instance['show_icon']) ? $instance['show_icon'] : true;
        $enable_show_more = isset($instance['enable_show_more']) ? $instance['enable_show_more'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">タイトル:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('initial_count')); ?>">初期表示数:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('initial_count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('initial_count')); ?>" 
                   type="number" min="1" value="<?php echo esc_attr($initial_count); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('show_more_count')); ?>">追加表示数:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('show_more_count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_more_count')); ?>" 
                   type="number" min="1" value="<?php echo esc_attr($show_more_count); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_count); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_count')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>">投稿数を表示</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_description); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_description')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_description')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_description')); ?>">説明を表示</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_icon); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_icon')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_icon')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_icon')); ?>">アイコンを表示</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($enable_show_more); ?> 
                   id="<?php echo esc_attr($this->get_field_id('enable_show_more')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('enable_show_more')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('enable_show_more')); ?>">「もっと見る」ボタンを表示</label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['initial_count'] = (!empty($new_instance['initial_count'])) ? intval($new_instance['initial_count']) : 5;
        $instance['show_more_count'] = (!empty($new_instance['show_more_count'])) ? intval($new_instance['show_more_count']) : 5;
        $instance['show_count'] = isset($new_instance['show_count']);
        $instance['show_description'] = isset($new_instance['show_description']);
        $instance['show_icon'] = isset($new_instance['show_icon']);
        $instance['enable_show_more'] = isset($new_instance['enable_show_more']);
        return $instance;
    }
}

/**
 * Register widget
 */
function gi_register_categories_widget() {
    register_widget('GI_Categories_Widget');
}
add_action('widgets_init', 'gi_register_categories_widget');