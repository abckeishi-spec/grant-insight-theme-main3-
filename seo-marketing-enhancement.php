<?php
/**
 * Grant Insight SEO & Marketing Enhancement
 * SEO・マーケティング問題解決モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined("ABSPATH")) {
    exit;
}

/**
 * SEO・マーケティング強化クラス
 */
class GI_SEO_Marketing_Enhancement {
    
    private static $instance = null;
    private $google_analytics_id = "";
    private $facebook_pixel_id = "";
    
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
        $this->google_analytics_id = get_option("gi_google_analytics_id", "");
        $this->facebook_pixel_id = get_option("gi_facebook_pixel_id", "");
        
        $this->setup_hooks();
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // メタタグ、OGP、構造化データ
        add_action("wp_head", array($this, "add_seo_meta_tags"), 1);
        add_action("wp_head", array($this, "add_ogp_tags"), 2);
        add_action("wp_head", array($this, "add_structured_data"), 3);
        
        // Google Analytics
        if (!empty($this->google_analytics_id)) {
            add_action("wp_head", array($this, "add_google_analytics_script"), 99);
        }
        
        // Facebook Pixel
        if (!empty($this->facebook_pixel_id)) {
            add_action("wp_head", array($this, "add_facebook_pixel_script"), 98);
        }
        
        // ソーシャルメディア共有ボタン
        add_filter("the_content", array($this, "add_social_share_buttons"));
        
        // 管理画面設定
        add_action("admin_menu", array($this, "add_seo_marketing_settings_page"));
        add_action("admin_init", array($this, "register_seo_marketing_settings"));
    }
    
    /**
     * SEOメタタグの追加
     */
    public function add_seo_meta_tags() {
        if (is_single() || is_page()) {
            global $post;
            $description = get_post_meta($post->ID, "_gi_seo_description", true);
            $keywords = get_post_meta($post->ID, "_gi_seo_keywords", true);
            
            if (empty($description)) {
                $description = wp_trim_words(strip_shortcodes($post->post_content), 30, "...");
            }
            
            echo "<meta name=\"description\" content=\"" . esc_attr($description) . "\">\n";
            if (!empty($keywords)) {
                echo "<meta name=\"keywords\" content=\"" . esc_attr($keywords) . "\">\n";
            }
        } else if (is_home() || is_front_page()) {
            $description = get_bloginfo("description");
            echo "<meta name=\"description\" content=\"" . esc_attr($description) . "\">\n";
        }
        
        echo "<meta name=\"robots\" content=\"index, follow\">\n";
    }
    
    /**
     * OGPタグの追加
     */
    public function add_ogp_tags() {
        if (is_single() || is_page()) {
            global $post;
            $title = get_the_title();
            $description = wp_trim_words(strip_shortcodes($post->post_content), 30, "...");
            $url = get_permalink();
            $image = get_the_post_thumbnail_url($post->ID, "full");
            
            echo "<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">\n";
            echo "<meta property=\"og:description\" content=\"" . esc_attr($description) . "\">\n";
            echo "<meta property=\"og:url\" content=\"" . esc_url($url) . "\">\n";
            echo "<meta property=\"og:type\" content=\"article\">\n";
            echo "<meta property=\"og:site_name\" content=\"" . esc_attr(get_bloginfo("name")) . "\">\n";
            if ($image) {
                echo "<meta property=\"og:image\" content=\"" . esc_url($image) . "\">\n";
            }
            
            // Twitter Card
            echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
            echo "<meta name=\"twitter:title\" content=\"" . esc_attr($title) . "\">\n";
            echo "<meta name=\"twitter:description\" content=\"" . esc_attr($description) . "\">\n";
            if ($image) {
                echo "<meta name=\"twitter:image\" content=\"" . esc_url($image) . "\">\n";
            }
        } else if (is_home() || is_front_page()) {
            $title = get_bloginfo("name");
            $description = get_bloginfo("description");
            $url = home_url();
            
            echo "<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">\n";
            echo "<meta property=\"og:description\" content=\"" . esc_attr($description) . "\">\n";
            echo "<meta property=\"og:url\" content=\"" . esc_url($url) . "\">\n";
            echo "<meta property=\"og:type\" content=\"website\">\n";
            echo "<meta property=\"og:site_name\" content=\"" . esc_attr(get_bloginfo("name")) . "\">\n";
            // ホームページのOGP画像はテーマオプションで設定可能にする
            $home_ogp_image = get_option("gi_home_ogp_image");
            if ($home_ogp_image) {
                echo "<meta property=\"og:image\" content=\"" . esc_url($home_ogp_image) . "\">\n";
            }
        }
    }
    
    /**
     * 構造化データの追加 (Schema.org)
     */
    public function add_structured_data() {
        if (is_single() || is_page()) {
            global $post;
            $author = get_the_author_meta("display_name", $post->post_author);
            $date_published = get_the_time("c", $post->ID);
            $date_modified = get_the_modified_time("c", $post->ID);
            $image = get_the_post_thumbnail_url($post->ID, "full");
            
            $schema = array(
                "@context" => "https://schema.org",
                "@type" => "Article",
                "headline" => get_the_title(),
                "description" => wp_trim_words(strip_shortcodes($post->post_content), 30, "..."),
                "image" => array(
                    "@type" => "ImageObject",
                    "url" => $image ? esc_url($image) : "",
                    "width" => "1200", // 仮の値
                    "height" => "630"  // 仮の値
                ),
                "datePublished" => $date_published,
                "dateModified" => $date_modified,
                "author" => array(
                    "@type" => "Person",
                    "name" => esc_attr($author)
                ),
                "publisher" => array(
                    "@type" => "Organization",
                    "name" => esc_attr(get_bloginfo("name")),
                    "logo" => array(
                        "@type" => "ImageObject",
                        "url" => esc_url(get_custom_logo_url()), // サイトロゴのURL
                        "width" => "600", // 仮の値
                        "height" => "60"  // 仮の値
                    )
                ),
                "mainEntityOfPage" => array(
                    "@type" => "WebPage",
                    "@id" => get_permalink()
                )
            );
            
            echo "<script type=\"application/ld+json\">" . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        }
    }
    
    /**
     * Google Analyticsスクリプトの追加
     */
    public function add_google_analytics_script() {
        ?>
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->google_analytics_id); ?>"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag("js", new Date());
          gtag("config", "<?php echo esc_attr($this->google_analytics_id); ?>");
        </script>
        <?php
    }
    
    /**
     * Facebook Pixelスクリプトの追加
     */
    public function add_facebook_pixel_script() {
        ?>
        <!-- Facebook Pixel Code -->
        <script>
          !function(f,b,e,v,n,t,s)
          {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
          n.callMethod.apply(n,arguments):n.queue.push(arguments)};
          if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
          n.queue=[];t=b.createElement(e);t.async=!0;
          t.src=v;s=b.getElementsByTagName(e)[0];
          s.parentNode.insertBefore(t,s)}(window, document,
          \'script\', \'https://connect.facebook.net/en_US/fbevents.js\');
          fbq(\'init\', \'<?php echo esc_attr($this->facebook_pixel_id); ?>\');
          fbq(\'track\', \'PageView\');
        </script>
        <noscript><img height=\


"1" width="1" style="display:none"
          src="https://www.facebook.com/tr?id=<?php echo esc_attr($this->facebook_pixel_id); ?>&ev=PageView&noscript=1"
        /></noscript>
        <!-- End Facebook Pixel Code -->
        <?php
    }
    
    /**
     * ソーシャルメディア共有ボタンの追加
     */
    public function add_social_share_buttons($content) {
        if (is_single() && get_post_type() === 'grant') {
            $post_title = urlencode(get_the_title());
            $post_url = urlencode(get_permalink());
            
            $share_buttons = '<div class="gi-share-buttons">';
            $share_buttons .= '<a href="https://twitter.com/intent/tweet?text=' . $post_title . '&url=' . $post_url . '" target="_blank" rel="noopener noreferrer" class="gi-share-button gi-share-twitter">Twitter</a>';
            $share_buttons .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $post_url . '" target="_blank" rel="noopener noreferrer" class="gi-share-button gi-share-facebook">Facebook</a>';
            $share_buttons .= '<a href="https://social-plugins.line.me/lineit/share?url=' . $post_url . '" target="_blank" rel="noopener noreferrer" class="gi-share-button gi-share-line">LINE</a>';
            $share_buttons .= '</div>';
            
            $content .= $share_buttons;
        }
        return $content;
    }
    
    /**
     * SEO・マーケティング設定ページの追加
     */
    public function add_seo_marketing_settings_page() {
        add_submenu_page(
            'themes.php',
            'SEO & マーケティング設定',
            'SEO & マーケティング',
            'manage_options',
            'gi-seo-marketing-settings',
            array($this, 'render_seo_marketing_settings_page')
        );
    }
    
    /**
     * SEO・マーケティング設定の登録
     */
    public function register_seo_marketing_settings() {
        register_setting('gi_seo_marketing_settings', 'gi_google_analytics_id');
        register_setting('gi_seo_marketing_settings', 'gi_facebook_pixel_id');
        register_setting('gi_seo_marketing_settings', 'gi_home_ogp_image');
        
        add_settings_section(
            'gi_seo_marketing_section',
            'トラッキングコード設定',
            null,
            'gi-seo-marketing-settings'
        );
        
        add_settings_field(
            'gi_google_analytics_id_field',
            'Google Analytics ID (GA4)',
            array($this, 'google_analytics_id_callback'),
            'gi-seo-marketing-settings',
            'gi_seo_marketing_section'
        );
        
        add_settings_field(
            'gi_facebook_pixel_id_field',
            'Facebook Pixel ID',
            array($this, 'facebook_pixel_id_callback'),
            'gi-seo-marketing-settings',
            'gi_seo_marketing_section'
        );
        
        add_settings_field(
            'gi_home_ogp_image_field',
            'ホームページOGP画像URL',
            array($this, 'home_ogp_image_callback'),
            'gi-seo-marketing-settings',
            'gi_seo_marketing_section'
        );
    }
    
    /**
     * Google Analytics ID入力フィールドのコールバック
     */
    public function google_analytics_id_callback() {
        $id = get_option('gi_google_analytics_id', '');
        echo '<input type="text" name="gi_google_analytics_id" value="' . esc_attr($id) . '" class="regular-text" />';
        echo '<p class="description">例: G-XXXXXXXXXX</p>';
    }
    
    /**
     * Facebook Pixel ID入力フィールドのコールバック
     */
    public function facebook_pixel_id_callback() {
        $id = get_option('gi_facebook_pixel_id', '');
        echo '<input type="text" name="gi_facebook_pixel_id" value="' . esc_attr($id) . '" class="regular-text" />';
        echo '<p class="description">例: 123456789012345</p>';
    }
    
    /**
     * ホームページOGP画像URL入力フィールドのコールバック
     */
    public function home_ogp_image_callback() {
        $url = get_option('gi_home_ogp_image', '');
        echo '<input type="text" name="gi_home_ogp_image" value="' . esc_attr($url) . '" class="regular-text" />';
        echo '<p class="description">ホームページのOGP画像URLを入力してください。</p>';
    }
    
    /**
     * SEO・マーケティング設定ページのレンダリング
     */
    public function render_seo_marketing_settings_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight SEO & マーケティング設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gi_seo_marketing_settings'); ?>
                <?php do_settings_sections('gi_seo_marketing_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// SEO・マーケティング強化の初期化
if (!function_exists('gi_init_seo_marketing_enhancement')) {
    function gi_init_seo_marketing_enhancement() {
        GI_SEO_Marketing_Enhancement::getInstance();
    }
    add_action('init', 'gi_init_seo_marketing_enhancement', 1);
}

// ヘルパー関数：サイトロゴURLの取得
if (!function_exists('get_custom_logo_url')) {
    function get_custom_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        $image = wp_get_attachment_image_src($custom_logo_id, 'full');
        return $image ? $image[0] : '';
    }
}

