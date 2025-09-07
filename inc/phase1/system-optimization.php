<?php
/**
 * System Optimization
 * 
 * Task 16: システム最適化
 * Implements database optimization, caching strategies, and image optimization
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 16.1 データベース最適化
 * Database optimization
 */
class GI_Database_Optimization {
    
    /**
     * Initialize database optimizations
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'check_and_create_indexes'));
        add_filter('posts_request', array(__CLASS__, 'optimize_queries'), 10, 2);
        add_action('pre_get_posts', array(__CLASS__, 'prevent_n_plus_one'));
        add_action('admin_init', array(__CLASS__, 'add_optimization_admin_page'));
        
        // Schedule regular optimization
        if (!wp_next_scheduled('gi_database_optimization')) {
            wp_schedule_event(time(), 'weekly', 'gi_database_optimization');
        }
        add_action('gi_database_optimization', array(__CLASS__, 'run_optimization'));
    }
    
    /**
     * Check and create database indexes
     */
    public static function check_and_create_indexes() {
        global $wpdb;
        
        // Check if indexes need to be created
        $option_name = 'gi_db_indexes_created';
        if (get_option($option_name) === '1.0') {
            return;
        }
        
        // Create indexes for better performance
        $indexes = array(
            // Index for grant meta queries
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX gi_grant_deadline (post_id, meta_key(20), meta_value(20))",
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX gi_grant_amount (meta_key(20), meta_value_num)",
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX gi_grant_individual (meta_key(20), meta_value(1))",
            
            // Index for term relationships
            "ALTER TABLE {$wpdb->term_relationships} ADD INDEX gi_grant_terms (object_id, term_taxonomy_id)",
            
            // Index for view counting
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX gi_view_count (meta_key(20), meta_value_num)",
            
            // Index for error log table if exists
            "ALTER TABLE {$wpdb->prefix}gi_error_log ADD INDEX gi_error_date (created_at, error_code)",
            
            // Index for diagnosis history if exists
            "ALTER TABLE {$wpdb->prefix}gi_diagnosis_history ADD INDEX gi_diagnosis_user (user_id, created_at)"
        );
        
        foreach ($indexes as $index_query) {
            // Check if table exists before adding index
            $table_name = self::extract_table_name($index_query);
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                // Suppress errors as index might already exist
                $wpdb->query($index_query);
            }
        }
        
        // Mark indexes as created
        update_option($option_name, '1.0');
    }
    
    /**
     * Extract table name from ALTER query
     */
    private static function extract_table_name($query) {
        preg_match('/ALTER TABLE ([^\s]+)/', $query, $matches);
        return isset($matches[1]) ? $matches[1] : '';
    }
    
    /**
     * Optimize database queries
     */
    public static function optimize_queries($request, $query) {
        // Skip if not main query or in admin
        if (is_admin() || !$query->is_main_query()) {
            return $request;
        }
        
        // Optimize grant archive queries
        if ($query->is_post_type_archive('grant')) {
            // Add SQL_CALC_FOUND_ROWS only when necessary
            if (!$query->get('no_found_rows')) {
                $request = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $request);
            }
            
            // Force index usage for better performance
            $request = str_replace('FROM', 'USE INDEX (type_status_date) FROM', $request);
        }
        
        // Remove duplicate JOINs
        $request = self::remove_duplicate_joins($request);
        
        // Optimize ORDER BY clauses
        $request = self::optimize_order_by($request);
        
        return $request;
    }
    
    /**
     * Remove duplicate JOINs from query
     */
    private static function remove_duplicate_joins($query) {
        // Pattern to find JOIN statements
        $pattern = '/(LEFT JOIN|INNER JOIN|RIGHT JOIN)([^(LEFT JOIN|INNER JOIN|RIGHT JOIN)]+)/i';
        preg_match_all($pattern, $query, $matches);
        
        if (empty($matches[0])) {
            return $query;
        }
        
        $joins = array();
        $unique_joins = array();
        
        foreach ($matches[0] as $join) {
            $join_hash = md5(trim($join));
            if (!isset($unique_joins[$join_hash])) {
                $unique_joins[$join_hash] = $join;
                $joins[] = $join;
            }
        }
        
        // Rebuild query with unique joins
        if (count($joins) < count($matches[0])) {
            $parts = preg_split($pattern, $query);
            $rebuilt = $parts[0];
            
            foreach ($joins as $i => $join) {
                $rebuilt .= $join;
                if (isset($parts[$i + 1])) {
                    $rebuilt .= $parts[$i + 1];
                }
            }
            
            return $rebuilt;
        }
        
        return $query;
    }
    
    /**
     * Optimize ORDER BY clauses
     */
    private static function optimize_order_by($query) {
        // Remove unnecessary ORDER BY in subqueries
        $query = preg_replace('/\(SELECT(.+?)ORDER BY[^)]+\)/i', '(SELECT$1)', $query);
        
        // Optimize multiple ORDER BY columns
        if (strpos($query, 'ORDER BY') !== false) {
            // Limit ORDER BY columns to 2 for better performance
            $query = preg_replace('/ORDER BY ([^,]+,[^,]+),.+/', 'ORDER BY $1', $query);
        }
        
        return $query;
    }
    
    /**
     * Prevent N+1 query problems
     */
    public static function prevent_n_plus_one($query) {
        // Skip if not main query or in admin
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Prefetch meta data for grant queries
        if ($query->get('post_type') === 'grant' || $query->is_post_type_archive('grant')) {
            // Enable meta cache priming
            $query->set('update_post_meta_cache', true);
            $query->set('update_post_term_cache', true);
            
            // Limit fields if only IDs are needed
            if ($query->get('fields') === 'ids') {
                $query->set('no_found_rows', true);
            }
        }
        
        // Batch load user meta for author archives
        if ($query->is_author()) {
            $query->set('cache_results', true);
        }
    }
    
    /**
     * Run database optimization
     */
    public static function run_optimization() {
        global $wpdb;
        
        // Optimize tables
        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->options
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
        
        // Clean up orphaned meta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        // Clean up expired transients
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_%'
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // Delete associated transient data
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_%'
            AND option_name NOT IN (
                SELECT CONCAT('_transient_', SUBSTRING(option_name, 19))
                FROM (SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%') AS t
            )
        ");
        
        // Log optimization
        update_option('gi_last_db_optimization', current_time('mysql'));
    }
    
    /**
     * Add optimization admin page
     */
    public static function add_optimization_admin_page() {
        add_submenu_page(
            'tools.php',
            'データベース最適化',
            'DB最適化',
            'manage_options',
            'gi-db-optimization',
            array(__CLASS__, 'render_optimization_page')
        );
    }
    
    /**
     * Render optimization page
     */
    public static function render_optimization_page() {
        global $wpdb;
        
        // Handle optimization request
        if (isset($_POST['optimize']) && check_admin_referer('gi_db_optimize')) {
            self::run_optimization();
            echo '<div class="notice notice-success"><p>データベースを最適化しました。</p></div>';
        }
        
        // Get database statistics
        $table_status = $wpdb->get_results("
            SELECT 
                TABLE_NAME as name,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
                TABLE_ROWS as rows,
                ROUND(DATA_FREE / 1024 / 1024, 2) as overhead_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '" . DB_NAME . "'
            AND TABLE_NAME LIKE '{$wpdb->prefix}%'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");
        
        $last_optimization = get_option('gi_last_db_optimization', 'Never');
        
        ?>
        <div class="wrap">
            <h1>データベース最適化</h1>
            
            <div class="card">
                <h2>最適化状態</h2>
                <p>最終最適化: <strong><?php echo esc_html($last_optimization); ?></strong></p>
                
                <form method="post">
                    <?php wp_nonce_field('gi_db_optimize'); ?>
                    <p class="submit">
                        <input type="submit" name="optimize" class="button button-primary" value="今すぐ最適化">
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>テーブル情報</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>テーブル名</th>
                            <th>サイズ (MB)</th>
                            <th>行数</th>
                            <th>オーバーヘッド (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($table_status as $table): ?>
                        <tr>
                            <td><?php echo esc_html($table->name); ?></td>
                            <td><?php echo esc_html($table->size_mb); ?></td>
                            <td><?php echo number_format($table->rows); ?></td>
                            <td><?php echo esc_html($table->overhead_mb); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>推奨事項</h2>
                <ul>
                    <li>定期的な最適化のために、週次でのcronジョブが設定されています。</li>
                    <li>大量のデータ操作後は手動で最適化を実行してください。</li>
                    <li>オーバーヘッドが大きい場合は最適化を実行してください。</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// Initialize database optimization
GI_Database_Optimization::init();

/**
 * 16.2 キャッシュ戦略
 * Caching strategies
 */
class GI_Cache_Strategy {
    
    /**
     * Cache groups
     */
    const CACHE_GROUP = 'grant_insight';
    const CACHE_VERSION = '1.0';
    
    /**
     * Initialize caching strategies
     */
    public static function init() {
        // Object cache
        add_action('init', array(__CLASS__, 'setup_object_cache'));
        
        // Page cache
        add_action('template_redirect', array(__CLASS__, 'setup_page_cache'));
        
        // API response cache
        add_filter('rest_pre_serve_request', array(__CLASS__, 'cache_api_responses'), 10, 4);
        
        // Cache invalidation
        add_action('save_post_grant', array(__CLASS__, 'invalidate_grant_cache'), 10, 3);
        add_action('delete_post', array(__CLASS__, 'invalidate_grant_cache'));
        add_action('wp_ajax_gi_flush_cache', array(__CLASS__, 'ajax_flush_cache'));
        
        // Cache warming
        add_action('gi_cache_warming', array(__CLASS__, 'warm_cache'));
        if (!wp_next_scheduled('gi_cache_warming')) {
            wp_schedule_event(time(), 'daily', 'gi_cache_warming');
        }
    }
    
    /**
     * Setup object cache
     */
    public static function setup_object_cache() {
        // Check if object cache is available
        if (!wp_using_ext_object_cache()) {
            // Use transients as fallback
            self::setup_transient_cache();
        }
        
        // Add cache groups
        if (function_exists('wp_cache_add_global_groups')) {
            wp_cache_add_global_groups(array(self::CACHE_GROUP));
        }
        
        // Add non-persistent groups
        if (function_exists('wp_cache_add_non_persistent_groups')) {
            wp_cache_add_non_persistent_groups(array('gi_temp'));
        }
    }
    
    /**
     * Setup transient cache as fallback
     */
    private static function setup_transient_cache() {
        // Wrapper functions for transient-based caching
        if (!function_exists('gi_cache_get')) {
            function gi_cache_get($key, $group = '') {
                $transient_key = 'gi_cache_' . md5($group . '_' . $key);
                return get_transient($transient_key);
            }
        }
        
        if (!function_exists('gi_cache_set')) {
            function gi_cache_set($key, $value, $group = '', $expire = 3600) {
                $transient_key = 'gi_cache_' . md5($group . '_' . $key);
                return set_transient($transient_key, $value, $expire);
            }
        }
        
        if (!function_exists('gi_cache_delete')) {
            function gi_cache_delete($key, $group = '') {
                $transient_key = 'gi_cache_' . md5($group . '_' . $key);
                return delete_transient($transient_key);
            }
        }
    }
    
    /**
     * Setup page cache
     */
    public static function setup_page_cache() {
        // Skip if user is logged in or in admin
        if (is_user_logged_in() || is_admin()) {
            return;
        }
        
        // Skip if POST request or has query parameters
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !empty($_GET)) {
            return;
        }
        
        // Generate cache key
        $cache_key = 'page_' . md5($_SERVER['REQUEST_URI']);
        
        // Try to get cached page
        $cached_page = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if ($cached_page !== false) {
            // Serve cached page
            echo $cached_page;
            echo '<!-- Served from cache -->';
            exit;
        }
        
        // Start output buffering for caching
        ob_start(function($buffer) use ($cache_key) {
            // Cache the page for 1 hour
            wp_cache_set($cache_key, $buffer, self::CACHE_GROUP, HOUR_IN_SECONDS);
            return $buffer;
        });
    }
    
    /**
     * Cache API responses
     */
    public static function cache_api_responses($served, $result, $request, $server) {
        // Only cache GET requests
        if ($request->get_method() !== 'GET') {
            return $served;
        }
        
        // Check if route should be cached
        $route = $request->get_route();
        $cacheable_routes = array(
            '/grant-insight/v1/grants',
            '/grant-insight/v1/categories',
            '/grant-insight/v1/statistics'
        );
        
        $should_cache = false;
        foreach ($cacheable_routes as $cacheable_route) {
            if (strpos($route, $cacheable_route) === 0) {
                $should_cache = true;
                break;
            }
        }
        
        if (!$should_cache) {
            return $served;
        }
        
        // Generate cache key
        $cache_key = 'api_' . md5($route . serialize($request->get_params()));
        
        // Try to get cached response
        $cached_response = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if ($cached_response !== false) {
            // Serve cached response
            $server->send_header('X-Cache', 'HIT');
            echo wp_json_encode($cached_response);
            return true;
        }
        
        // Cache the response
        if ($result) {
            wp_cache_set($cache_key, $result, self::CACHE_GROUP, 15 * MINUTE_IN_SECONDS);
            $server->send_header('X-Cache', 'MISS');
        }
        
        return $served;
    }
    
    /**
     * Invalidate grant cache
     */
    public static function invalidate_grant_cache($post_id, $post = null, $update = false) {
        if (get_post_type($post_id) !== 'grant') {
            return;
        }
        
        // Clear specific caches
        wp_cache_delete('grant_' . $post_id, self::CACHE_GROUP);
        wp_cache_delete('grant_meta_' . $post_id, self::CACHE_GROUP);
        
        // Clear archive caches
        wp_cache_delete('grant_archive', self::CACHE_GROUP);
        wp_cache_delete('grant_categories', self::CACHE_GROUP);
        
        // Clear related transients
        delete_transient('gi_grant_count_total');
        delete_transient('gi_grant_count_active');
        
        // Clear page cache for grant pages
        $urls = array(
            get_permalink($post_id),
            get_post_type_archive_link('grant')
        );
        
        foreach ($urls as $url) {
            $cache_key = 'page_' . md5(parse_url($url, PHP_URL_PATH));
            wp_cache_delete($cache_key, self::CACHE_GROUP);
        }
    }
    
    /**
     * AJAX handler to flush cache
     */
    public static function ajax_flush_cache() {
        check_ajax_referer('gi_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません。');
        }
        
        // Flush all caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        
        wp_send_json_success('キャッシュをフラッシュしました。');
    }
    
    /**
     * Warm cache
     */
    public static function warm_cache() {
        // Warm popular grant pages
        $popular_grants = get_posts(array(
            'post_type' => 'grant',
            'posts_per_page' => 10,
            'meta_key' => 'view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ));
        
        foreach ($popular_grants as $grant) {
            // Cache grant data
            $cache_key = 'grant_' . $grant->ID;
            wp_cache_set($cache_key, $grant, self::CACHE_GROUP, DAY_IN_SECONDS);
            
            // Cache grant meta
            $meta_cache_key = 'grant_meta_' . $grant->ID;
            $meta = get_post_meta($grant->ID);
            wp_cache_set($meta_cache_key, $meta, self::CACHE_GROUP, DAY_IN_SECONDS);
        }
        
        // Warm category cache
        $categories = get_terms(array(
            'taxonomy' => 'grant_category',
            'hide_empty' => false
        ));
        
        wp_cache_set('grant_categories', $categories, self::CACHE_GROUP, DAY_IN_SECONDS);
        
        // Warm statistics cache
        $stats = array(
            'total_grants' => wp_count_posts('grant')->publish,
            'active_grants' => self::get_active_grants_count(),
            'categories_count' => count($categories)
        );
        
        wp_cache_set('grant_statistics', $stats, self::CACHE_GROUP, HOUR_IN_SECONDS);
    }
    
    /**
     * Get active grants count
     */
    private static function get_active_grants_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'grant'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'application_deadline'
            AND pm.meta_value >= CURDATE()
        ");
    }
    
    /**
     * Get cached data with fallback
     */
    public static function get_cached($key, $callback, $expire = 3600) {
        $cache_key = self::CACHE_VERSION . '_' . $key;
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if ($cached === false) {
            $cached = call_user_func($callback);
            wp_cache_set($cache_key, $cached, self::CACHE_GROUP, $expire);
        }
        
        return $cached;
    }
}

// Initialize cache strategy
GI_Cache_Strategy::init();

/**
 * 16.3 画像最適化
 * Image optimization
 */
class GI_Image_Optimization {
    
    /**
     * Initialize image optimization
     */
    public static function init() {
        // WebP support
        add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'generate_webp_on_upload'), 10, 2);
        add_filter('wp_get_attachment_image_attributes', array(__CLASS__, 'add_webp_sources'), 10, 3);
        
        // Lazy loading
        add_filter('wp_get_attachment_image_attributes', array(__CLASS__, 'add_lazy_loading'), 10, 3);
        add_filter('the_content', array(__CLASS__, 'add_lazy_loading_to_content'));
        
        // Thumbnail optimization
        add_filter('intermediate_image_sizes_advanced', array(__CLASS__, 'optimize_thumbnail_sizes'));
        add_action('after_setup_theme', array(__CLASS__, 'add_optimized_image_sizes'));
        
        // Responsive images
        add_filter('wp_calculate_image_srcset', array(__CLASS__, 'optimize_srcset'), 10, 5);
        
        // Image compression
        add_filter('wp_editor_set_quality', array(__CLASS__, 'set_image_quality'));
        add_filter('jpeg_quality', array(__CLASS__, 'set_jpeg_quality'));
        
        // Admin interface
        add_action('admin_menu', array(__CLASS__, 'add_optimization_page'));
    }
    
    /**
     * Generate WebP versions on upload
     */
    public static function generate_webp_on_upload($metadata, $attachment_id) {
        // Check if GD or Imagick supports WebP
        if (!function_exists('imagewebp') && !class_exists('Imagick')) {
            return $metadata;
        }
        
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'] . '/' . $metadata['file'];
        
        // Generate WebP for main image
        self::create_webp_image($path);
        
        // Generate WebP for all sizes
        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_data) {
                $size_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_data['file'];
                self::create_webp_image($size_path);
            }
        }
        
        return $metadata;
    }
    
    /**
     * Create WebP image
     */
    private static function create_webp_image($source_path) {
        $info = pathinfo($source_path);
        $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';
        
        // Skip if WebP already exists
        if (file_exists($webp_path)) {
            return;
        }
        
        // Use Imagick if available
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick($source_path);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(85);
                $image->writeImage($webp_path);
                $image->destroy();
            } catch (Exception $e) {
                error_log('WebP generation failed: ' . $e->getMessage());
            }
        } 
        // Fallback to GD
        elseif (function_exists('imagewebp')) {
            $image = null;
            
            switch ($info['extension']) {
                case 'jpeg':
                case 'jpg':
                    $image = imagecreatefromjpeg($source_path);
                    break;
                case 'png':
                    $image = imagecreatefrompng($source_path);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($source_path);
                    break;
            }
            
            if ($image) {
                imagewebp($image, $webp_path, 85);
                imagedestroy($image);
            }
        }
    }
    
    /**
     * Add WebP sources to images
     */
    public static function add_webp_sources($attributes, $attachment, $size) {
        // Check if WebP version exists
        $upload_dir = wp_upload_dir();
        $image_meta = wp_get_attachment_metadata($attachment->ID);
        
        if (!$image_meta) {
            return $attributes;
        }
        
        $image_path = $upload_dir['basedir'] . '/' . $image_meta['file'];
        $webp_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $image_path);
        
        if (file_exists($webp_path)) {
            $webp_url = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $attributes['src']);
            $attributes['data-webp'] = $webp_url;
            
            // Add picture element support
            $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] . ' ' : '') . 'has-webp';
        }
        
        return $attributes;
    }
    
    /**
     * Add lazy loading attributes
     */
    public static function add_lazy_loading($attributes, $attachment, $size) {
        // Skip if already has loading attribute
        if (isset($attributes['loading'])) {
            return $attributes;
        }
        
        // Add native lazy loading
        $attributes['loading'] = 'lazy';
        
        // Add data attributes for JavaScript fallback
        if (isset($attributes['src'])) {
            $attributes['data-src'] = $attributes['src'];
            $attributes['src'] = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . 
                                  ($attributes['width'] ?? 1) . ' ' . ($attributes['height'] ?? 1) . '"%3E%3C/svg%3E';
            $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] . ' ' : '') . 'lazyload';
        }
        
        return $attributes;
    }
    
    /**
     * Add lazy loading to content images
     */
    public static function add_lazy_loading_to_content($content) {
        // Skip if in admin or feed
        if (is_admin() || is_feed()) {
            return $content;
        }
        
        // Add loading="lazy" to all images
        $content = preg_replace('/<img([^>]+)>/i', '<img$1 loading="lazy">', $content);
        
        // Remove duplicate loading attributes
        $content = preg_replace('/loading="[^"]*"\s+loading="lazy"/', 'loading="lazy"', $content);
        
        return $content;
    }
    
    /**
     * Optimize thumbnail sizes
     */
    public static function optimize_thumbnail_sizes($sizes) {
        // Remove unnecessary default sizes
        unset($sizes['medium_large']);
        
        // Optimize existing sizes
        if (isset($sizes['thumbnail'])) {
            $sizes['thumbnail']['crop'] = true;
        }
        
        return $sizes;
    }
    
    /**
     * Add optimized image sizes
     */
    public static function add_optimized_image_sizes() {
        // Add custom optimized sizes
        add_image_size('gi_small', 400, 300, true);      // Small thumbnail
        add_image_size('gi_medium', 800, 600, true);     // Medium size
        add_image_size('gi_large', 1200, 900, true);     // Large size
        add_image_size('gi_hero', 1920, 1080, true);     // Hero image
        add_image_size('gi_card', 600, 400, true);       // Card image
        
        // Mobile-specific sizes
        add_image_size('gi_mobile', 768, 0, false);      // Mobile width
        add_image_size('gi_tablet', 1024, 0, false);     // Tablet width
    }
    
    /**
     * Optimize srcset
     */
    public static function optimize_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        // Add WebP sources if available
        foreach ($sources as $width => &$source) {
            $webp_url = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $source['url']);
            $upload_dir = wp_upload_dir();
            $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);
            
            if (file_exists($webp_path)) {
                $source['url_webp'] = $webp_url;
            }
        }
        
        return $sources;
    }
    
    /**
     * Set image quality
     */
    public static function set_image_quality($quality) {
        // Set quality based on image size
        $size = isset($_REQUEST['size']) ? $_REQUEST['size'] : '';
        
        switch ($size) {
            case 'thumbnail':
            case 'gi_small':
                return 85;
            case 'medium':
            case 'gi_medium':
                return 90;
            case 'large':
            case 'gi_large':
                return 92;
            default:
                return 90;
        }
    }
    
    /**
     * Set JPEG quality
     */
    public static function set_jpeg_quality($quality) {
        return 90; // Default JPEG quality
    }
    
    /**
     * Add optimization admin page
     */
    public static function add_optimization_page() {
        add_submenu_page(
            'upload.php',
            '画像最適化',
            '画像最適化',
            'manage_options',
            'gi-image-optimization',
            array(__CLASS__, 'render_optimization_page')
        );
    }
    
    /**
     * Render optimization page
     */
    public static function render_optimization_page() {
        // Handle bulk optimization
        if (isset($_POST['optimize_all']) && check_admin_referer('gi_image_optimize')) {
            self::bulk_optimize_images();
            echo '<div class="notice notice-success"><p>画像を最適化しました。</p></div>';
        }
        
        // Get image statistics
        global $wpdb;
        
        $total_images = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
        ");
        
        $total_size = $wpdb->get_var("
            SELECT SUM(meta_value) FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attachment_metadata'
        ");
        
        $webp_count = self::count_webp_images();
        
        ?>
        <div class="wrap">
            <h1>画像最適化</h1>
            
            <div class="card">
                <h2>最適化統計</h2>
                <table class="form-table">
                    <tr>
                        <th>総画像数:</th>
                        <td><?php echo number_format($total_images); ?></td>
                    </tr>
                    <tr>
                        <th>WebP画像数:</th>
                        <td><?php echo number_format($webp_count); ?></td>
                    </tr>
                    <tr>
                        <th>WebP変換率:</th>
                        <td><?php echo $total_images > 0 ? round($webp_count / $total_images * 100, 1) : 0; ?>%</td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2>一括最適化</h2>
                <p>すべての画像をWebP形式に変換し、最適化します。</p>
                <form method="post">
                    <?php wp_nonce_field('gi_image_optimize'); ?>
                    <p class="submit">
                        <input type="submit" name="optimize_all" class="button button-primary" value="すべての画像を最適化">
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>最適化設定</h2>
                <table class="form-table">
                    <tr>
                        <th>WebP自動生成:</th>
                        <td>
                            <label>
                                <input type="checkbox" checked disabled> 有効
                            </label>
                            <p class="description">アップロード時に自動的にWebP版を生成します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th>遅延読み込み:</th>
                        <td>
                            <label>
                                <input type="checkbox" checked disabled> 有効
                            </label>
                            <p class="description">画像の遅延読み込みを有効にします。</p>
                        </td>
                    </tr>
                    <tr>
                        <th>画像品質:</th>
                        <td>
                            <input type="number" value="90" min="50" max="100" disabled> %
                            <p class="description">JPEG画像の圧縮品質を設定します。</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Count WebP images
     */
    private static function count_webp_images() {
        $upload_dir = wp_upload_dir();
        $webp_files = glob($upload_dir['basedir'] . '/**/*.webp');
        return count($webp_files);
    }
    
    /**
     * Bulk optimize images
     */
    private static function bulk_optimize_images() {
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1
        ));
        
        foreach ($images as $image) {
            $metadata = wp_get_attachment_metadata($image->ID);
            if ($metadata) {
                self::generate_webp_on_upload($metadata, $image->ID);
            }
        }
    }
    
    /**
     * Add inline script for lazy loading
     */
    public static function add_lazyload_script() {
        ?>
        <script>
        (function() {
            // Native lazy loading fallback
            if ('loading' in HTMLImageElement.prototype) {
                const images = document.querySelectorAll('img[loading="lazy"]');
                images.forEach(img => {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                });
            } else {
                // Fallback for browsers without native lazy loading
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/lazysizes@5/lazysizes.min.js';
                document.body.appendChild(script);
            }
            
            // WebP support detection and fallback
            function checkWebPSupport(callback) {
                const webP = new Image();
                webP.onload = webP.onerror = function() {
                    callback(webP.height === 2);
                };
                webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
            }
            
            checkWebPSupport(function(supported) {
                if (supported) {
                    document.body.classList.add('webp-supported');
                    
                    // Replace img src with WebP if available
                    document.querySelectorAll('img[data-webp]').forEach(img => {
                        img.src = img.dataset.webp;
                    });
                } else {
                    document.body.classList.add('webp-not-supported');
                }
            });
        })();
        </script>
        <?php
    }
}

// Initialize image optimization
GI_Image_Optimization::init();
add_action('wp_footer', array('GI_Image_Optimization', 'add_lazyload_script'));

/**
 * Performance monitoring
 */
class GI_Performance_Monitor {
    
    /**
     * Initialize performance monitoring
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'start_monitoring'));
        add_action('shutdown', array(__CLASS__, 'log_performance'));
        add_action('admin_bar_menu', array(__CLASS__, 'add_performance_menu'), 999);
    }
    
    /**
     * Start performance monitoring
     */
    public static function start_monitoring() {
        if (!defined('GI_START_TIME')) {
            define('GI_START_TIME', microtime(true));
        }
        
        if (!defined('GI_START_MEMORY')) {
            define('GI_START_MEMORY', memory_get_usage());
        }
    }
    
    /**
     * Log performance metrics
     */
    public static function log_performance() {
        if (!defined('GI_START_TIME') || !current_user_can('manage_options')) {
            return;
        }
        
        $execution_time = microtime(true) - GI_START_TIME;
        $memory_usage = memory_get_usage() - GI_START_MEMORY;
        $peak_memory = memory_get_peak_usage();
        
        // Log to database if execution time is high
        if ($execution_time > 2.0) {
            global $wpdb;
            
            $wpdb->insert(
                $wpdb->prefix . 'gi_performance_log',
                array(
                    'page_url' => $_SERVER['REQUEST_URI'],
                    'execution_time' => $execution_time,
                    'memory_usage' => $memory_usage,
                    'peak_memory' => $peak_memory,
                    'query_count' => get_num_queries(),
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Add performance info to admin bar
     */
    public static function add_performance_menu($wp_admin_bar) {
        if (!current_user_can('manage_options') || !defined('GI_START_TIME')) {
            return;
        }
        
        $execution_time = round(microtime(true) - GI_START_TIME, 3);
        $memory_usage = size_format(memory_get_usage());
        $query_count = get_num_queries();
        
        $wp_admin_bar->add_node(array(
            'id' => 'gi-performance',
            'title' => sprintf(
                '⚡ %ss | %s | %s queries',
                $execution_time,
                $memory_usage,
                $query_count
            ),
            'href' => admin_url('tools.php?page=gi-performance')
        ));
    }
}

// Initialize performance monitoring
if (defined('WP_DEBUG') && WP_DEBUG) {
    GI_Performance_Monitor::init();
}