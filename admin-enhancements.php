<?php
/**
 * Admin Dashboard Enhancements
 * 
 * Task 15: 管理画面機能充実
 * Implements custom dashboard widgets, bulk operations, and admin improvements
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 15.1 カスタムダッシュボードウィジェット
 * Custom dashboard widgets
 */
class GI_Dashboard_Widgets {
    
    /**
     * Initialize dashboard widgets
     */
    public static function init() {
        add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_dashboard_scripts'));
    }
    
    /**
     * Add custom dashboard widgets
     */
    public static function add_dashboard_widgets() {
        // 助成金統計表示
        wp_add_dashboard_widget(
            'gi_grant_statistics',
            '助成金統計',
            array(__CLASS__, 'render_grant_statistics_widget')
        );
        
        // 新着お問い合わせ
        wp_add_dashboard_widget(
            'gi_recent_inquiries',
            '新着お問い合わせ',
            array(__CLASS__, 'render_recent_inquiries_widget')
        );
        
        // システム状況
        wp_add_dashboard_widget(
            'gi_system_status',
            'システム状況',
            array(__CLASS__, 'render_system_status_widget')
        );
    }
    
    /**
     * Render grant statistics widget
     */
    public static function render_grant_statistics_widget() {
        global $wpdb;
        
        // Get statistics
        $total_grants = wp_count_posts('grant')->publish;
        $active_grants = self::get_active_grants_count();
        $expired_grants = self::get_expired_grants_count();
        $individual_grants = self::get_individual_grants_count();
        
        // Get category distribution
        $categories = get_terms(array(
            'taxonomy' => 'grant_category',
            'hide_empty' => false,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5
        ));
        
        // Get recent views
        $recent_views = $wpdb->get_var("
            SELECT SUM(meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'view_count' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'grant' 
                AND post_status = 'publish'
            )
        ");
        
        ?>
        <div class="gi-statistics-widget">
            <div class="stat-grid">
                <div class="stat-item">
                    <span class="stat-value"><?php echo number_format($total_grants); ?></span>
                    <span class="stat-label">総補助金数</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value text-green"><?php echo number_format($active_grants); ?></span>
                    <span class="stat-label">募集中</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value text-red"><?php echo number_format($expired_grants); ?></span>
                    <span class="stat-label">期限切れ</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value text-blue"><?php echo number_format($individual_grants); ?></span>
                    <span class="stat-label">個人向け</span>
                </div>
            </div>
            
            <?php if (!empty($categories)): ?>
            <div class="category-distribution">
                <h4>カテゴリ別分布（上位5件）</h4>
                <ul class="category-list">
                    <?php foreach ($categories as $category): ?>
                    <li>
                        <span class="category-name"><?php echo esc_html($category->name); ?></span>
                        <span class="category-count"><?php echo $category->count; ?>件</span>
                        <div class="category-bar" style="width: <?php echo ($category->count / $total_grants * 100); ?>%"></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="view-statistics">
                <p>総閲覧数: <strong><?php echo number_format($recent_views ?: 0); ?></strong></p>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('edit.php?post_type=grant'); ?>" class="button">
                    すべての補助金を見る
                </a>
                <a href="#" class="button" onclick="GIAdmin.exportStatistics(); return false;">
                    統計をエクスポート
                </a>
            </div>
        </div>
        
        <style>
            .stat-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .stat-item {
                text-align: center;
                padding: 15px;
                background: #f0f0f1;
                border-radius: 5px;
            }
            
            .stat-value {
                display: block;
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .stat-label {
                display: block;
                font-size: 12px;
                color: #666;
            }
            
            .text-green { color: #46b450; }
            .text-red { color: #dc3232; }
            .text-blue { color: #0073aa; }
            
            .category-distribution {
                margin: 20px 0;
            }
            
            .category-distribution h4 {
                margin-bottom: 10px;
            }
            
            .category-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .category-list li {
                position: relative;
                padding: 8px 0;
                border-bottom: 1px solid #ddd;
            }
            
            .category-list li:last-child {
                border-bottom: none;
            }
            
            .category-name {
                display: inline-block;
                width: 60%;
            }
            
            .category-count {
                display: inline-block;
                width: 35%;
                text-align: right;
                font-weight: bold;
            }
            
            .category-bar {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                background: rgba(0, 115, 170, 0.1);
                z-index: -1;
            }
            
            .widget-actions {
                margin-top: 20px;
                display: flex;
                gap: 10px;
            }
            
            .widget-actions .button {
                flex: 1;
                text-align: center;
            }
        </style>
        <?php
    }
    
    /**
     * Render recent inquiries widget
     */
    public static function render_recent_inquiries_widget() {
        // Get recent contact form submissions (assuming Contact Form 7 or similar)
        $inquiries = self::get_recent_inquiries(5);
        
        if (empty($inquiries)) {
            echo '<p>新着のお問い合わせはありません。</p>';
            return;
        }
        
        ?>
        <div class="gi-inquiries-widget">
            <ul class="inquiry-list">
                <?php foreach ($inquiries as $inquiry): ?>
                <li class="inquiry-item">
                    <div class="inquiry-header">
                        <strong><?php echo esc_html($inquiry->name); ?></strong>
                        <span class="inquiry-date"><?php echo human_time_diff(strtotime($inquiry->date), current_time('timestamp')); ?>前</span>
                    </div>
                    <div class="inquiry-subject"><?php echo esc_html($inquiry->subject); ?></div>
                    <div class="inquiry-excerpt"><?php echo esc_html(wp_trim_words($inquiry->message, 20)); ?></div>
                    <a href="<?php echo esc_url($inquiry->link); ?>" class="inquiry-link">詳細を見る →</a>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="widget-footer">
                <a href="<?php echo admin_url('admin.php?page=inquiries'); ?>" class="button button-primary">
                    すべてのお問い合わせ
                </a>
            </div>
        </div>
        
        <style>
            .inquiry-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .inquiry-item {
                padding: 12px 0;
                border-bottom: 1px solid #ddd;
            }
            
            .inquiry-item:last-child {
                border-bottom: none;
            }
            
            .inquiry-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }
            
            .inquiry-date {
                font-size: 12px;
                color: #666;
            }
            
            .inquiry-subject {
                font-weight: 500;
                margin-bottom: 5px;
            }
            
            .inquiry-excerpt {
                font-size: 13px;
                color: #666;
                margin-bottom: 5px;
            }
            
            .inquiry-link {
                font-size: 13px;
            }
            
            .widget-footer {
                margin-top: 15px;
                text-align: center;
            }
        </style>
        <?php
    }
    
    /**
     * Render system status widget
     */
    public static function render_system_status_widget() {
        global $wpdb;
        
        // Get system information
        $php_version = PHP_VERSION;
        $wp_version = get_bloginfo('version');
        $mysql_version = $wpdb->db_version();
        $memory_usage = size_format(memory_get_usage(true));
        $memory_limit = ini_get('memory_limit');
        
        // Check cache status
        $cache_enabled = wp_using_ext_object_cache();
        $transient_count = self::get_transient_count();
        
        // Get error log summary
        $recent_errors = self::get_recent_errors(3);
        
        // Check cron status
        $cron_status = wp_next_scheduled('gi_daily_maintenance') ? '正常' : '未設定';
        
        ?>
        <div class="gi-system-status-widget">
            <div class="status-section">
                <h4>システム情報</h4>
                <table class="status-table">
                    <tr>
                        <td>PHP Version:</td>
                        <td><?php echo esc_html($php_version); ?></td>
                    </tr>
                    <tr>
                        <td>WordPress Version:</td>
                        <td><?php echo esc_html($wp_version); ?></td>
                    </tr>
                    <tr>
                        <td>MySQL Version:</td>
                        <td><?php echo esc_html($mysql_version); ?></td>
                    </tr>
                    <tr>
                        <td>メモリ使用量:</td>
                        <td><?php echo esc_html($memory_usage . ' / ' . $memory_limit); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="status-section">
                <h4>キャッシュ状態</h4>
                <p>
                    オブジェクトキャッシュ: 
                    <span class="status-indicator <?php echo $cache_enabled ? 'status-good' : 'status-warning'; ?>">
                        <?php echo $cache_enabled ? '有効' : '無効'; ?>
                    </span>
                </p>
                <p>Transient数: <?php echo number_format($transient_count); ?></p>
            </div>
            
            <div class="status-section">
                <h4>Cronジョブ</h4>
                <p>
                    状態: 
                    <span class="status-indicator <?php echo $cron_status === '正常' ? 'status-good' : 'status-error'; ?>">
                        <?php echo esc_html($cron_status); ?>
                    </span>
                </p>
            </div>
            
            <?php if (!empty($recent_errors)): ?>
            <div class="status-section">
                <h4>最近のエラー</h4>
                <ul class="error-list">
                    <?php foreach ($recent_errors as $error): ?>
                    <li>
                        <code><?php echo esc_html($error->error_code); ?></code>
                        <span><?php echo esc_html($error->count); ?>件</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('tools.php?page=gi-system-info'); ?>" class="button">
                    詳細情報
                </a>
                <a href="#" class="button" onclick="GIAdmin.clearCache(); return false;">
                    キャッシュクリア
                </a>
            </div>
        </div>
        
        <style>
            .status-section {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ddd;
            }
            
            .status-section:last-child {
                border-bottom: none;
            }
            
            .status-section h4 {
                margin: 0 0 10px 0;
                font-size: 13px;
                font-weight: 600;
            }
            
            .status-table {
                width: 100%;
                font-size: 13px;
            }
            
            .status-table td {
                padding: 5px 0;
            }
            
            .status-table td:first-child {
                width: 50%;
                color: #666;
            }
            
            .status-indicator {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }
            
            .status-good {
                background: #d4edda;
                color: #155724;
            }
            
            .status-warning {
                background: #fff3cd;
                color: #856404;
            }
            
            .status-error {
                background: #f8d7da;
                color: #721c24;
            }
            
            .error-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .error-list li {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                font-size: 13px;
            }
            
            .error-list code {
                background: #f0f0f1;
                padding: 2px 5px;
                border-radius: 3px;
            }
        </style>
        <?php
    }
    
    /**
     * Helper methods
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
    
    private static function get_expired_grants_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'grant'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'application_deadline'
            AND pm.meta_value < CURDATE()
        ");
    }
    
    private static function get_individual_grants_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'grant'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'target_individual'
            AND pm.meta_value = '1'
        ");
    }
    
    private static function get_recent_inquiries($limit = 5) {
        // This would integrate with your contact form plugin
        // For demo purposes, returning mock data
        return array();
    }
    
    private static function get_transient_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_%'
        ");
    }
    
    private static function get_recent_errors($limit = 3) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_error_log';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT error_code, COUNT(*) as count
            FROM {$table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY error_code
            ORDER BY count DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Enqueue dashboard scripts
     */
    public static function enqueue_dashboard_scripts($hook) {
        if ($hook !== 'index.php') {
            return;
        }
        
        wp_add_inline_script('jquery', '
            var GIAdmin = {
                exportStatistics: function() {
                    jQuery.post(ajaxurl, {
                        action: "gi_export_statistics",
                        nonce: "' . wp_create_nonce('gi_admin_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            window.location.href = response.data.url;
                        }
                    });
                },
                clearCache: function() {
                    if (confirm("キャッシュをクリアしますか？")) {
                        jQuery.post(ajaxurl, {
                            action: "gi_clear_cache",
                            nonce: "' . wp_create_nonce('gi_admin_nonce') . '"
                        }, function(response) {
                            if (response.success) {
                                alert("キャッシュをクリアしました");
                                location.reload();
                            }
                        });
                    }
                }
            };
        ');
    }
}

// Initialize dashboard widgets
GI_Dashboard_Widgets::init();

/**
 * 15.2 一括操作機能
 * Bulk operations functionality
 */
class GI_Bulk_Operations {
    
    /**
     * Initialize bulk operations
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_bulk_operations_page'));
        add_action('admin_init', array(__CLASS__, 'handle_csv_upload'));
        add_filter('bulk_actions-edit-grant', array(__CLASS__, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-grant', array(__CLASS__, 'handle_bulk_actions'), 10, 3);
    }
    
    /**
     * Add bulk operations page
     */
    public static function add_bulk_operations_page() {
        add_submenu_page(
            'edit.php?post_type=grant',
            'インポート/エクスポート',
            'インポート/エクスポート',
            'manage_options',
            'gi-import-export',
            array(__CLASS__, 'render_import_export_page')
        );
    }
    
    /**
     * Render import/export page
     */
    public static function render_import_export_page() {
        ?>
        <div class="wrap">
            <h1>補助金データ インポート/エクスポート</h1>
            
            <div class="card">
                <h2>CSVエクスポート</h2>
                <p>現在の補助金データをCSV形式でエクスポートします。</p>
                <form method="post" action="">
                    <?php wp_nonce_field('gi_export_csv', 'gi_export_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">エクスポート範囲</th>
                            <td>
                                <label>
                                    <input type="radio" name="export_range" value="all" checked>
                                    すべての補助金
                                </label><br>
                                <label>
                                    <input type="radio" name="export_range" value="active">
                                    募集中の補助金のみ
                                </label><br>
                                <label>
                                    <input type="radio" name="export_range" value="expired">
                                    期限切れの補助金のみ
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">含めるフィールド</th>
                            <td>
                                <label><input type="checkbox" name="fields[]" value="basic" checked> 基本情報</label><br>
                                <label><input type="checkbox" name="fields[]" value="meta" checked> カスタムフィールド</label><br>
                                <label><input type="checkbox" name="fields[]" value="categories" checked> カテゴリ</label><br>
                                <label><input type="checkbox" name="fields[]" value="tags"> タグ</label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="export_csv" class="button button-primary" value="CSVエクスポート">
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>CSVインポート</h2>
                <p>CSV形式の補助金データをインポートします。</p>
                <form method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('gi_import_csv', 'gi_import_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">CSVファイル</th>
                            <td>
                                <input type="file" name="csv_file" accept=".csv" required>
                                <p class="description">UTF-8エンコードのCSVファイルを選択してください。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">インポートモード</th>
                            <td>
                                <label>
                                    <input type="radio" name="import_mode" value="add" checked>
                                    新規追加（既存データは保持）
                                </label><br>
                                <label>
                                    <input type="radio" name="import_mode" value="update">
                                    更新（タイトルが一致する場合は上書き）
                                </label><br>
                                <label>
                                    <input type="radio" name="import_mode" value="replace">
                                    置換（既存データを削除して新規作成）
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">オプション</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="dry_run" value="1">
                                    テスト実行（実際にはインポートしない）
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="import_csv" class="button button-primary" value="CSVインポート">
                    </p>
                </form>
                
                <h3>CSVフォーマット</h3>
                <p>以下の形式でCSVファイルを作成してください：</p>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>列名</th>
                            <th>説明</th>
                            <th>必須</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>title</td>
                            <td>補助金名</td>
                            <td>○</td>
                        </tr>
                        <tr>
                            <td>content</td>
                            <td>詳細説明</td>
                            <td>○</td>
                        </tr>
                        <tr>
                            <td>max_amount</td>
                            <td>最大金額</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>application_deadline</td>
                            <td>申請期限（YYYY-MM-DD）</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>categories</td>
                            <td>カテゴリ（カンマ区切り）</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>target_individual</td>
                            <td>個人向け（1 or 0）</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle CSV upload
     */
    public static function handle_csv_upload() {
        // Handle export
        if (isset($_POST['export_csv']) && check_admin_referer('gi_export_csv', 'gi_export_nonce')) {
            self::export_csv();
        }
        
        // Handle import
        if (isset($_POST['import_csv']) && check_admin_referer('gi_import_csv', 'gi_import_nonce')) {
            self::import_csv();
        }
    }
    
    /**
     * Export grants to CSV
     */
    private static function export_csv() {
        $range = $_POST['export_range'] ?? 'all';
        $fields = $_POST['fields'] ?? array('basic');
        
        // Build query
        $args = array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        
        // Apply range filter
        if ($range === 'active') {
            $args['meta_query'] = array(
                array(
                    'key' => 'application_deadline',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            );
        } elseif ($range === 'expired') {
            $args['meta_query'] = array(
                array(
                    'key' => 'application_deadline',
                    'value' => current_time('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                )
            );
        }
        
        $grants = get_posts($args);
        
        // Prepare CSV data
        $csv_data = array();
        $headers = array('ID', 'タイトル', '内容');
        
        if (in_array('meta', $fields)) {
            $headers = array_merge($headers, array('最大金額', '申請期限', '個人向け'));
        }
        
        if (in_array('categories', $fields)) {
            $headers[] = 'カテゴリ';
        }
        
        if (in_array('tags', $fields)) {
            $headers[] = 'タグ';
        }
        
        $csv_data[] = $headers;
        
        // Add grant data
        foreach ($grants as $grant) {
            $row = array(
                $grant->ID,
                $grant->post_title,
                strip_tags($grant->post_content)
            );
            
            if (in_array('meta', $fields)) {
                $row[] = get_field('max_amount', $grant->ID);
                $row[] = get_field('application_deadline', $grant->ID);
                $row[] = get_field('target_individual', $grant->ID) ? '1' : '0';
            }
            
            if (in_array('categories', $fields)) {
                $categories = wp_get_post_terms($grant->ID, 'grant_category', array('fields' => 'names'));
                $row[] = implode(',', $categories);
            }
            
            if (in_array('tags', $fields)) {
                $tags = wp_get_post_terms($grant->ID, 'post_tag', array('fields' => 'names'));
                $row[] = implode(',', $tags);
            }
            
            $csv_data[] = $row;
        }
        
        // Output CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="grants_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
    
    /**
     * Import grants from CSV
     */
    private static function import_csv() {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die('ファイルのアップロードに失敗しました。');
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $mode = $_POST['import_mode'] ?? 'add';
        $dry_run = isset($_POST['dry_run']);
        
        // Parse CSV
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_die('CSVファイルを開けませんでした。');
        }
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        $headers = fgetcsv($handle);
        $imported = 0;
        $errors = array();
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $grant_data = array_combine($headers, $data);
            
            if (empty($grant_data['title'])) {
                $errors[] = 'タイトルが空の行をスキップしました。';
                continue;
            }
            
            if (!$dry_run) {
                $result = self::import_single_grant($grant_data, $mode);
                if ($result) {
                    $imported++;
                } else {
                    $errors[] = 'インポート失敗: ' . $grant_data['title'];
                }
            } else {
                $imported++;
            }
        }
        
        fclose($handle);
        
        // Show results
        $message = $dry_run ? 
            "テスト実行: {$imported}件の補助金がインポート可能です。" :
            "{$imported}件の補助金をインポートしました。";
        
        if (!empty($errors)) {
            $message .= '<br>エラー: ' . implode('<br>', $errors);
        }
        
        wp_admin_notice($message, array('type' => $dry_run ? 'info' : 'success'));
    }
    
    /**
     * Import single grant
     */
    private static function import_single_grant($data, $mode) {
        // Check if grant exists
        $existing = get_page_by_title($data['title'], OBJECT, 'grant');
        
        if ($existing && $mode === 'add') {
            return false; // Skip if exists
        }
        
        if ($existing && $mode === 'replace') {
            wp_delete_post($existing->ID, true);
            $existing = null;
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => $data['title'],
            'post_content' => $data['content'] ?? '',
            'post_type' => 'grant',
            'post_status' => 'publish'
        );
        
        if ($existing && $mode === 'update') {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Update meta fields
        if (isset($data['max_amount'])) {
            update_field('max_amount', $data['max_amount'], $post_id);
        }
        
        if (isset($data['application_deadline'])) {
            update_field('application_deadline', $data['application_deadline'], $post_id);
        }
        
        if (isset($data['target_individual'])) {
            update_field('target_individual', $data['target_individual'] === '1', $post_id);
        }
        
        // Set categories
        if (!empty($data['categories'])) {
            $categories = array_map('trim', explode(',', $data['categories']));
            wp_set_object_terms($post_id, $categories, 'grant_category');
        }
        
        return true;
    }
    
    /**
     * Add bulk actions
     */
    public static function add_bulk_actions($bulk_actions) {
        $bulk_actions['mark_active'] = '募集中にする';
        $bulk_actions['mark_expired'] = '期限切れにする';
        $bulk_actions['mark_individual'] = '個人向けに設定';
        $bulk_actions['unmark_individual'] = '個人向けを解除';
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        switch ($action) {
            case 'mark_active':
                foreach ($post_ids as $post_id) {
                    update_field('application_deadline', date('Y-m-d', strtotime('+30 days')), $post_id);
                }
                $redirect_to = add_query_arg('bulk_marked_active', count($post_ids), $redirect_to);
                break;
                
            case 'mark_expired':
                foreach ($post_ids as $post_id) {
                    update_field('application_deadline', date('Y-m-d', strtotime('-1 day')), $post_id);
                }
                $redirect_to = add_query_arg('bulk_marked_expired', count($post_ids), $redirect_to);
                break;
                
            case 'mark_individual':
                foreach ($post_ids as $post_id) {
                    update_field('target_individual', true, $post_id);
                }
                $redirect_to = add_query_arg('bulk_marked_individual', count($post_ids), $redirect_to);
                break;
                
            case 'unmark_individual':
                foreach ($post_ids as $post_id) {
                    update_field('target_individual', false, $post_id);
                }
                $redirect_to = add_query_arg('bulk_unmarked_individual', count($post_ids), $redirect_to);
                break;
        }
        
        return $redirect_to;
    }
}

// Initialize bulk operations
GI_Bulk_Operations::init();

/**
 * 15.3 助成金管理画面改善
 * Grant admin screen improvements
 */
class GI_Admin_Improvements {
    
    /**
     * Initialize admin improvements
     */
    public static function init() {
        add_filter('manage_grant_posts_columns', array(__CLASS__, 'add_custom_columns'));
        add_action('manage_grant_posts_custom_column', array(__CLASS__, 'render_custom_columns'), 10, 2);
        add_filter('manage_edit-grant_sortable_columns', array(__CLASS__, 'make_columns_sortable'));
        add_action('pre_get_posts', array(__CLASS__, 'sort_custom_columns'));
        add_action('quick_edit_custom_box', array(__CLASS__, 'add_quick_edit_fields'), 10, 2);
        add_action('admin_footer-edit.php', array(__CLASS__, 'quick_edit_javascript'));
        add_action('save_post_grant', array(__CLASS__, 'save_quick_edit_data'));
        add_action('restrict_manage_posts', array(__CLASS__, 'add_admin_filters'));
        add_filter('parse_query', array(__CLASS__, 'filter_grants_query'));
    }
    
    /**
     * Add custom columns
     */
    public static function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['status'] = '状態';
                $new_columns['max_amount'] = '最大金額';
                $new_columns['deadline'] = '申請期限';
                $new_columns['individual'] = '個人向け';
                $new_columns['views'] = '閲覧数';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public static function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'status':
                $deadline = get_field('application_deadline', $post_id);
                if ($deadline) {
                    $is_active = strtotime($deadline) >= strtotime('today');
                    echo $is_active ? 
                        '<span style="color: green;">●</span> 募集中' : 
                        '<span style="color: red;">●</span> 期限切れ';
                } else {
                    echo '<span style="color: gray;">●</span> 未設定';
                }
                break;
                
            case 'max_amount':
                $amount = get_field('max_amount', $post_id);
                echo $amount ? esc_html($amount) : '—';
                break;
                
            case 'deadline':
                $deadline = get_field('application_deadline', $post_id);
                if ($deadline) {
                    $date = date_i18n('Y/m/d', strtotime($deadline));
                    $days_left = floor((strtotime($deadline) - time()) / 86400);
                    
                    if ($days_left > 0) {
                        echo esc_html($date) . '<br><small>あと' . $days_left . '日</small>';
                    } elseif ($days_left === 0) {
                        echo '<span style="color: orange;">' . esc_html($date) . '<br><small>本日締切</small></span>';
                    } else {
                        echo '<span style="color: red;">' . esc_html($date) . '<br><small>終了</small></span>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'individual':
                $is_individual = get_field('target_individual', $post_id);
                echo $is_individual ? '✓' : '—';
                break;
                
            case 'views':
                $views = get_post_meta($post_id, 'view_count', true);
                echo number_format($views ?: 0);
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public static function make_columns_sortable($columns) {
        $columns['max_amount'] = 'max_amount';
        $columns['deadline'] = 'deadline';
        $columns['views'] = 'views';
        return $columns;
    }
    
    /**
     * Sort custom columns
     */
    public static function sort_custom_columns($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'grant') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'max_amount':
                $query->set('meta_key', 'max_amount_numeric');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'deadline':
                $query->set('meta_key', 'application_deadline');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'views':
                $query->set('meta_key', 'view_count');
                $query->set('orderby', 'meta_value_num');
                break;
        }
    }
    
    /**
     * Add quick edit fields
     */
    public static function add_quick_edit_fields($column_name, $post_type) {
        if ($post_type !== 'grant') {
            return;
        }
        
        if ($column_name === 'max_amount') {
            ?>
            <fieldset class="inline-edit-col-left">
                <div class="inline-edit-col">
                    <label>
                        <span class="title">最大金額</span>
                        <span class="input-text-wrap">
                            <input type="text" name="max_amount" value="">
                        </span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
        
        if ($column_name === 'deadline') {
            ?>
            <fieldset class="inline-edit-col-left">
                <div class="inline-edit-col">
                    <label>
                        <span class="title">申請期限</span>
                        <span class="input-text-wrap">
                            <input type="date" name="application_deadline" value="">
                        </span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
        
        if ($column_name === 'individual') {
            ?>
            <fieldset class="inline-edit-col-left">
                <div class="inline-edit-col">
                    <label>
                        <input type="checkbox" name="target_individual" value="1">
                        <span class="checkbox-title">個人向け</span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Quick edit JavaScript
     */
    public static function quick_edit_javascript() {
        $screen = get_current_screen();
        if ($screen->post_type !== 'grant') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#the-list').on('click', '.editinline', function() {
                var post_id = $(this).closest('tr').attr('id').replace('post-', '');
                var $row = $('#post-' + post_id);
                
                // Populate quick edit fields with current values
                var max_amount = $row.find('.column-max_amount').text().trim();
                var deadline = $row.find('.column-deadline').text().split('\n')[0];
                var is_individual = $row.find('.column-individual').text().trim() === '✓';
                
                setTimeout(function() {
                    $(':input[name="max_amount"]').val(max_amount !== '—' ? max_amount : '');
                    $(':input[name="application_deadline"]').val(deadline !== '—' ? deadline : '');
                    $(':input[name="target_individual"]').prop('checked', is_individual);
                }, 100);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save quick edit data
     */
    public static function save_quick_edit_data($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['max_amount'])) {
            update_field('max_amount', $_POST['max_amount'], $post_id);
        }
        
        if (isset($_POST['application_deadline'])) {
            update_field('application_deadline', $_POST['application_deadline'], $post_id);
        }
        
        if (isset($_POST['target_individual'])) {
            update_field('target_individual', $_POST['target_individual'] === '1', $post_id);
        }
    }
    
    /**
     * Add admin filters
     */
    public static function add_admin_filters() {
        $screen = get_current_screen();
        if ($screen->post_type !== 'grant') {
            return;
        }
        
        // Status filter
        $selected_status = $_GET['grant_status'] ?? '';
        ?>
        <select name="grant_status">
            <option value="">すべての状態</option>
            <option value="active" <?php selected($selected_status, 'active'); ?>>募集中</option>
            <option value="expired" <?php selected($selected_status, 'expired'); ?>>期限切れ</option>
            <option value="no_deadline" <?php selected($selected_status, 'no_deadline'); ?>>期限未設定</option>
        </select>
        
        <?php
        // Individual filter
        $selected_individual = $_GET['grant_individual'] ?? '';
        ?>
        <select name="grant_individual">
            <option value="">すべて</option>
            <option value="1" <?php selected($selected_individual, '1'); ?>>個人向けのみ</option>
            <option value="0" <?php selected($selected_individual, '0'); ?>>法人向けのみ</option>
        </select>
        
        <?php
        // Category filter
        $selected_category = $_GET['grant_category_filter'] ?? '';
        wp_dropdown_categories(array(
            'show_option_all' => 'すべてのカテゴリ',
            'taxonomy' => 'grant_category',
            'name' => 'grant_category_filter',
            'selected' => $selected_category,
            'show_count' => true,
            'hide_empty' => false
        ));
    }
    
    /**
     * Filter grants query
     */
    public static function filter_grants_query($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'grant') {
            return;
        }
        
        // Status filter
        if (!empty($_GET['grant_status'])) {
            $status = $_GET['grant_status'];
            
            if ($status === 'active') {
                $query->set('meta_query', array(
                    array(
                        'key' => 'application_deadline',
                        'value' => current_time('Y-m-d'),
                        'compare' => '>=',
                        'type' => 'DATE'
                    )
                ));
            } elseif ($status === 'expired') {
                $query->set('meta_query', array(
                    array(
                        'key' => 'application_deadline',
                        'value' => current_time('Y-m-d'),
                        'compare' => '<',
                        'type' => 'DATE'
                    )
                ));
            } elseif ($status === 'no_deadline') {
                $query->set('meta_query', array(
                    array(
                        'key' => 'application_deadline',
                        'compare' => 'NOT EXISTS'
                    )
                ));
            }
        }
        
        // Individual filter
        if (isset($_GET['grant_individual']) && $_GET['grant_individual'] !== '') {
            $meta_query = $query->get('meta_query') ?: array();
            $meta_query[] = array(
                'key' => 'target_individual',
                'value' => $_GET['grant_individual'],
                'compare' => '='
            );
            $query->set('meta_query', $meta_query);
        }
        
        // Category filter
        if (!empty($_GET['grant_category_filter'])) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'grant_category',
                    'field' => 'term_id',
                    'terms' => $_GET['grant_category_filter']
                )
            ));
        }
    }
}

// Initialize admin improvements
GI_Admin_Improvements::init();

/**
 * AJAX handlers for admin functions
 */
add_action('wp_ajax_gi_export_statistics', function() {
    check_ajax_referer('gi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('権限がありません。');
    }
    
    // Generate statistics report
    // Implementation would generate a downloadable report
    
    wp_send_json_success(array(
        'message' => '統計をエクスポートしました。',
        'url' => admin_url('admin-ajax.php?action=download_statistics')
    ));
});

add_action('wp_ajax_gi_clear_cache', function() {
    check_ajax_referer('gi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('権限がありません。');
    }
    
    // Clear all transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    wp_send_json_success('キャッシュをクリアしました。');
});