<?php
/**
 * Grant Insight Performance Enhancement v2
 * パフォーマンス強化機能 第2版 - 追加のパフォーマンス問題解決
 * 
 * @package Grant_Insight_Perfect
 * @version 2.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * パフォーマンス強化クラス v2
 */
class GI_Performance_Enhancement_V2 {
    
    private static $instance = null;
    private $cdn_loaded = array();
    private $image_optimization_enabled = false;
    
    /**
     * シングルトンインスタンス取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初期化
     */
    public function __construct() {
        $this->setup_hooks();
        $this->init_performance_features();
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // CDN重複読み込み対策
        add_action('wp_enqueue_scripts', array($this, 'prevent_cdn_duplication'), 1);
        add_action('wp_head', array($this, 'output_cdn_manager'), 1);
        
        // 画像最適化
        add_filter('wp_handle_upload', array($this, 'optimize_uploaded_image'));
        add_filter('wp_get_attachment_image_src', array($this, 'serve_webp_images'), 10, 4);
        add_action('wp_head', array($this, 'add_webp_support_detection'));
        
        // データベースクエリ最適化（追加分）
        add_action('pre_get_posts', array($this, 'optimize_main_query'));
        add_filter('posts_clauses', array($this, 'optimize_search_query'), 10, 2);
        
        // キャッシュ戦略の拡張
        add_action('init', array($this, 'setup_advanced_caching'));
        add_action('wp_footer', array($this, 'implement_lazy_loading'));
        
        // リソース最適化
        add_action('wp_enqueue_scripts', array($this, 'optimize_script_loading'), 999);
        add_action('wp_print_styles', array($this, 'optimize_css_loading'), 999);
        
        // 管理画面での設定
        add_action('admin_menu', array($this, 'add_performance_settings_page'));
        add_action('admin_init', array($this, 'register_performance_settings'));
    }
    
    /**
     * パフォーマンス機能の初期化
     */
    private function init_performance_features() {
        // 画像最適化の有効化チェック
        $this->image_optimization_enabled = get_option('gi_image_optimization_enabled', true);
        
        // WebP対応の確認
        $this->check_webp_support();
        
        // オブジェクトキャッシュの確認
        $this->check_object_cache();
    }
    
    /**
     * CDN重複読み込み対策
     */
    public function prevent_cdn_duplication() {
        // 既に読み込まれたCDNをトラッキング
        global $wp_scripts, $wp_styles;
        
        // Tailwind CDNの重複チェック
        $tailwind_sources = array(
            'https://cdn.tailwindcss.com',
            'https://unpkg.com/tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss'
        );
        
        $tailwind_loaded = false;
        
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                foreach ($tailwind_sources as $source) {
                    if (strpos($style->src, $source) !== false) {
                        if ($tailwind_loaded) {
                            // 重複を検出した場合は削除
                            wp_dequeue_style($handle);
                            wp_deregister_style($handle);
                            error_log('GI Performance: Duplicate Tailwind CSS removed - ' . $handle);
                        } else {
                            $tailwind_loaded = true;
                            $this->cdn_loaded['tailwind'] = $handle;
                        }
                        break;
                    }
                }
            }
        }
        
        // Font Awesome CDNの重複チェック
        $fontawesome_sources = array(
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome',
            'https://use.fontawesome.com',
            'https://maxcdn.bootstrapcdn.com/font-awesome'
        );
        
        $fontawesome_loaded = false;
        
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                foreach ($fontawesome_sources as $source) {
                    if (strpos($style->src, $source) !== false) {
                        if ($fontawesome_loaded) {
                            wp_dequeue_style($handle);
                            wp_deregister_style($handle);
                            error_log('GI Performance: Duplicate Font Awesome removed - ' . $handle);
                        } else {
                            $fontawesome_loaded = true;
                            $this->cdn_loaded['fontawesome'] = $handle;
                        }
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * CDN管理の出力
     */
    public function output_cdn_manager() {
        echo "<!-- GI CDN Manager -->\n";
        echo "<script>\n";
        echo "window.GI_CDN_Manager = {\n";
        echo "  loaded: " . json_encode($this->cdn_loaded) . ",\n";
        echo "  prevent_duplicate: function(src) {\n";
        echo "    for (var cdn in this.loaded) {\n";
        echo "      if (src.indexOf(cdn) !== -1) {\n";
        echo "        console.warn('GI Performance: Prevented duplicate CDN load - ' + src);\n";
        echo "        return false;\n";
        echo "      }\n";
        echo "    }\n";
        echo "    return true;\n";
        echo "  }\n";
        echo "};\n";
        echo "</script>\n";
    }
    
    /**
     * アップロード画像の最適化
     */
    public function optimize_uploaded_image($upload) {
        if (!$this->image_optimization_enabled) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        $file_type = $upload['type'];
        
        // 対応する画像形式のチェック
        $supported_types = array('image/jpeg', 'image/png', 'image/gif');
        
        if (!in_array($file_type, $supported_types)) {
            return $upload;
        }
        
        // WebP変換
        $webp_path = $this->convert_to_webp($file_path, $file_type);
        
        if ($webp_path) {
            // WebPファイルの情報を追加
            $upload['webp_file'] = $webp_path;
            $upload['webp_url'] = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $webp_path);
        }
        
        // 画像圧縮
        $this->compress_image($file_path, $file_type);
        
        return $upload;
    }
    
    /**
     * WebP変換
     */
    private function convert_to_webp($file_path, $file_type) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file_path);
        
        switch ($file_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                // PNG透明度の保持
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
            default:
                return false;
        }
        
        if ($image) {
            $result = imagewebp($image, $webp_path, 85); // 85%品質
            imagedestroy($image);
            
            if ($result) {
                return $webp_path;
            }
        }
        
        return false;
    }
    
    /**
     * 画像圧縮
     */
    private function compress_image($file_path, $file_type) {
        switch ($file_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                if ($image) {
                    imagejpeg($image, $file_path, 85); // 85%品質
                    imagedestroy($image);
                }
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                if ($image) {
                    imagepng($image, $file_path, 6); // 圧縮レベル6
                    imagedestroy($image);
                }
                break;
        }
    }
    
    /**
     * WebP画像の配信
     */
    public function serve_webp_images($image, $attachment_id, $size, $icon) {
        if (!$image || !$this->image_optimization_enabled) {
            return $image;
        }
        
        // WebPサポートの確認
        if (!$this->is_webp_supported()) {
            return $image;
        }
        
        $original_url = $image[0];
        $webp_url = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $original_url);
        
        // WebPファイルの存在確認
        $webp_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $webp_url);
        
        if (file_exists($webp_path)) {
            $image[0] = $webp_url;
        }
        
        return $image;
    }
    
    /**
     * WebPサポート検出の追加
     */
    public function add_webp_support_detection() {
        echo "<script>\n";
        echo "function checkWebPSupport() {\n";
        echo "  var webP = new Image();\n";
        echo "  webP.onload = webP.onerror = function () {\n";
        echo "    document.documentElement.classList.add(webP.height == 2 ? 'webp' : 'no-webp');\n";
        echo "  };\n";
        echo "  webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';\n";
        echo "}\n";
        echo "checkWebPSupport();\n";
        echo "</script>\n";
    }
    
    /**
     * WebPサポートの確認
     */
    private function is_webp_supported() {
        // ユーザーエージェントベースの簡易チェック
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Chrome, Firefox, Edge, Opera, Safari (iOS 14+) でWebPサポート
        if (strpos($user_agent, 'Chrome') !== false ||
            strpos($user_agent, 'Firefox') !== false ||
            strpos($user_agent, 'Edge') !== false ||
            strpos($user_agent, 'Opera') !== false) {
            return true;
        }
        
        // Safari の場合はバージョンチェック
        if (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Version/14') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * WebPサポートの確認
     */
    private function check_webp_support() {
        if (!function_exists('imagewebp')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>Grant Insight:</strong> WebP画像形式がサポートされていません。';
                echo 'GDライブラリのWebP拡張を有効にすることで、画像サイズを大幅に削減できます。';
                echo '</p></div>';
            });
        }
    }
    
    /**
     * メインクエリの最適化
     */
    public function optimize_main_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // アーカイブページでの最適化
        if ($query->is_post_type_archive('grant')) {
            // 必要なメタフィールドのみを事前読み込み
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => 'deadline',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'deadline',
                    'compare' => 'NOT EXISTS'
                )
            ));
            
            // ソート最適化
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'deadline');
            $query->set('order', 'ASC');
        }
        
        // 検索クエリの最適化
        if ($query->is_search()) {
            // 検索対象の投稿タイプを制限
            $query->set('post_type', array('grant', 'tool', 'case_study'));
            
            // 検索フィールドの最適化
            add_filter('posts_search', array($this, 'optimize_search_fields'), 10, 2);
        }
    }
    
    /**
     * 検索クエリの最適化
     */
    public function optimize_search_query($clauses, $query) {
        if (!$query->is_search() || is_admin()) {
            return $clauses;
        }
        
        global $wpdb;
        
        // 検索語の取得
        $search_term = $query->get('s');
        
        if (empty($search_term)) {
            return $clauses;
        }
        
        // 関連性スコアによるソート
        $clauses['fields'] .= ", MATCH({$wpdb->posts}.post_title, {$wpdb->posts}.post_content) AGAINST ('" . esc_sql($search_term) . "') as relevance_score";
        $clauses['orderby'] = 'relevance_score DESC, ' . $clauses['orderby'];
        
        // インデックスの活用
        $clauses['where'] .= " AND MATCH({$wpdb->posts}.post_title, {$wpdb->posts}.post_content) AGAINST ('" . esc_sql($search_term) . "' IN BOOLEAN MODE)";
        
        return $clauses;
    }
    
    /**
     * 検索フィールドの最適化
     */
    public function optimize_search_fields($search, $query) {
        if (empty($search) || !$query->is_search()) {
            return $search;
        }
        
        global $wpdb;
        
        $search_term = $query->get('s');
        
        // カスタムフィールドも検索対象に含める
        $search .= " OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
            AND {$wpdb->postmeta}.meta_key IN ('eligibility', 'required_documents', 'contact_info')
            AND {$wpdb->postmeta}.meta_value LIKE '%" . esc_sql($search_term) . "%'
        )";
        
        return $search;
    }
    
    /**
     * 高度なキャッシュの設定
     */
    public function setup_advanced_caching() {
        // オブジェクトキャッシュの活用
        if (function_exists('wp_cache_get')) {
            add_filter('posts_pre_query', array($this, 'get_cached_query_results'), 10, 2);
            add_action('save_post', array($this, 'clear_related_cache'));
        }
        
        // ページキャッシュの設定
        if (!is_admin() && !is_user_logged_in()) {
            add_action('template_redirect', array($this, 'setup_page_cache'));
        }
    }
    
    /**
     * キャッシュされたクエリ結果の取得
     */
    public function get_cached_query_results($posts, $query) {
        if ($query->is_main_query() && !is_admin()) {
            $cache_key = 'gi_query_' . md5(serialize($query->query_vars));
            $cached_posts = wp_cache_get($cache_key, 'gi_queries');
            
            if ($cached_posts !== false) {
                return $cached_posts;
            }
        }
        
        return $posts;
    }
    
    /**
     * 関連キャッシュのクリア
     */
    public function clear_related_cache($post_id) {
        $post_type = get_post_type($post_id);
        
        // 投稿タイプ別のキャッシュクリア
        wp_cache_delete('gi_' . $post_type . '_archive', 'gi_queries');
        wp_cache_delete('gi_search_' . $post_type, 'gi_queries');
        
        // 関連するタクソノミーキャッシュのクリア
        $taxonomies = get_object_taxonomies($post_type);
        foreach ($taxonomies as $taxonomy) {
            wp_cache_delete('gi_tax_' . $taxonomy, 'gi_taxonomies');
        }
    }
    
    /**
     * ページキャッシュの設定
     */
    public function setup_page_cache() {
        if (is_front_page() || is_post_type_archive() || is_single()) {
            $cache_key = 'gi_page_' . md5($_SERVER['REQUEST_URI']);
            $cached_content = wp_cache_get($cache_key, 'gi_pages');
            
            if ($cached_content !== false) {
                echo $cached_content;
                exit;
            }
            
            // 出力バッファリング開始
            ob_start(function($content) use ($cache_key) {
                wp_cache_set($cache_key, $content, 'gi_pages', 300); // 5分キャッシュ
                return $content;
            });
        }
    }
    
    /**
     * 遅延読み込みの実装
     */
    public function implement_lazy_loading() {
        if (is_admin()) {
            return;
        }
        
        ?>
        <script>
        // Intersection Observer による遅延読み込み
        document.addEventListener('DOMContentLoaded', function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
                
                // AJAX コンテンツの遅延読み込み
                const contentObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const element = entry.target;
                            if (element.dataset.ajaxUrl) {
                                fetch(element.dataset.ajaxUrl)
                                    .then(response => response.text())
                                    .then(html => {
                                        element.innerHTML = html;
                                        observer.unobserve(element);
                                    })
                                    .catch(error => {
                                        console.error('Lazy load error:', error);
                                        observer.unobserve(element);
                                    });
                            }
                        }
                    });
                });
                
                document.querySelectorAll('[data-ajax-url]').forEach(element => {
                    contentObserver.observe(element);
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * スクリプト読み込みの最適化
     */
    public function optimize_script_loading() {
        global $wp_scripts;
        
        if (is_admin()) {
            return;
        }
        
        // 非同期読み込み対象のスクリプト
        $async_scripts = array('gi-search-enhanced', 'gi-front-page', 'gi-analytics');
        
        foreach ($async_scripts as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $wp_scripts->registered[$handle]->extra['async'] = true;
            }
        }
        
        // 遅延読み込み対象のスクリプト
        $defer_scripts = array('gi-social-share', 'gi-comments');
        
        foreach ($defer_scripts as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $wp_scripts->registered[$handle]->extra['defer'] = true;
            }
        }
    }
    
    /**
     * CSS読み込みの最適化
     */
    public function optimize_css_loading() {
        global $wp_styles;
        
        if (is_admin()) {
            return;
        }
        
        // 重要でないCSSの遅延読み込み
        $non_critical_styles = array('gi-social-icons', 'gi-print-styles');
        
        foreach ($non_critical_styles as $handle) {
            if (isset($wp_styles->registered[$handle])) {
                $wp_styles->registered[$handle]->extra['media'] = 'print';
                $wp_styles->registered[$handle]->extra['onload'] = "this.media='all'";
            }
        }
    }
    
    /**
     * オブジェクトキャッシュの確認
     */
    private function check_object_cache() {
        if (!wp_using_ext_object_cache()) {
            add_action('admin_notices', function() {
                if (current_user_can('manage_options')) {
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo '<strong>Grant Insight:</strong> オブジェクトキャッシュが有効になっていません。';
                    echo 'Redis や Memcached を使用することで、さらなるパフォーマンス向上が期待できます。';
                    echo '</p></div>';
                }
            });
        }
    }
    
    /**
     * パフォーマンス設定ページの追加
     */
    public function add_performance_settings_page() {
        add_submenu_page(
            'themes.php',
            'パフォーマンス設定',
            'パフォーマンス',
            'manage_options',
            'gi-performance-settings',
            array($this, 'render_performance_settings_page')
        );
    }
    
    /**
     * パフォーマンス設定の登録
     */
    public function register_performance_settings() {
        register_setting('gi_performance_settings', 'gi_image_optimization_enabled');
        register_setting('gi_performance_settings', 'gi_lazy_loading_enabled');
        register_setting('gi_performance_settings', 'gi_cdn_optimization_enabled');
        register_setting('gi_performance_settings', 'gi_cache_duration');
    }
    
    /**
     * パフォーマンス設定ページのレンダリング
     */
    public function render_performance_settings_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight パフォーマンス設定</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('gi_performance_settings'); ?>
                <?php do_settings_sections('gi_performance_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">画像最適化</th>
                        <td>
                            <input type="checkbox" name="gi_image_optimization_enabled" value="1" 
                                   <?php checked(get_option('gi_image_optimization_enabled', true)); ?> />
                            <label>画像の自動最適化とWebP変換を有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">遅延読み込み</th>
                        <td>
                            <input type="checkbox" name="gi_lazy_loading_enabled" value="1" 
                                   <?php checked(get_option('gi_lazy_loading_enabled', true)); ?> />
                            <label>画像とコンテンツの遅延読み込みを有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">CDN最適化</th>
                        <td>
                            <input type="checkbox" name="gi_cdn_optimization_enabled" value="1" 
                                   <?php checked(get_option('gi_cdn_optimization_enabled', true)); ?> />
                            <label>CDN重複読み込み防止を有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">キャッシュ期間</th>
                        <td>
                            <select name="gi_cache_duration">
                                <option value="300" <?php selected(get_option('gi_cache_duration', 300), 300); ?>>5分</option>
                                <option value="900" <?php selected(get_option('gi_cache_duration'), 900); ?>>15分</option>
                                <option value="1800" <?php selected(get_option('gi_cache_duration'), 1800); ?>>30分</option>
                                <option value="3600" <?php selected(get_option('gi_cache_duration'), 3600); ?>>1時間</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>パフォーマンス情報</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>WebP サポート</strong></td>
                        <td><?php echo function_exists('imagewebp') ? '✅ 有効' : '❌ 無効'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>オブジェクトキャッシュ</strong></td>
                        <td><?php echo wp_using_ext_object_cache() ? '✅ 有効' : '❌ 無効'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>読み込み済みCDN</strong></td>
                        <td><?php echo !empty($this->cdn_loaded) ? implode(', ', array_keys($this->cdn_loaded)) : 'なし'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>メモリ使用量</strong></td>
                        <td><?php echo size_format(memory_get_peak_usage(true)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// パフォーマンス強化 v2 の初期化
if (!function_exists('gi_init_performance_v2')) {
    function gi_init_performance_v2() {
        GI_Performance_Enhancement_V2::getInstance();
    }
    add_action('init', 'gi_init_performance_v2', 1);
}

