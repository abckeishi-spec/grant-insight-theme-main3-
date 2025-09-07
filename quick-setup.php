<?php
/**
 * Quick Setup for Grant Insight Theme
 * 
 * このファイルはテーマの初期セットアップを支援します
 * ACFが無い場合の代替機能とカスタム投稿タイプの登録を行います
 * 
 * @package Grant_Subsidy_Diagnosis
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * カスタム投稿タイプとタクソノミーの登録
 */
add_action('init', function() {
    // grant投稿タイプが存在しない場合のみ登録
    if (!post_type_exists('grant')) {
        register_post_type('grant', array(
            'labels' => array(
                'name' => '補助金',
                'singular_name' => '補助金',
                'add_new' => '新規追加',
                'add_new_item' => '新規補助金を追加',
                'edit_item' => '補助金を編集',
                'new_item' => '新規補助金',
                'view_item' => '補助金を表示',
                'search_items' => '補助金を検索',
                'not_found' => '補助金が見つかりません',
                'not_found_in_trash' => 'ゴミ箱に補助金はありません',
                'menu_name' => '補助金管理'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'menu_icon' => 'dashicons-money-alt',
            'menu_position' => 5,
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => 'grants',
                'with_front' => false
            )
        ));
    }
    
    // grant_categoryタクソノミーが存在しない場合のみ登録
    if (!taxonomy_exists('grant_category')) {
        register_taxonomy('grant_category', 'grant', array(
            'labels' => array(
                'name' => '補助金カテゴリー',
                'singular_name' => 'カテゴリー',
                'search_items' => 'カテゴリーを検索',
                'all_items' => 'すべてのカテゴリー',
                'parent_item' => '親カテゴリー',
                'parent_item_colon' => '親カテゴリー:',
                'edit_item' => 'カテゴリーを編集',
                'update_item' => 'カテゴリーを更新',
                'add_new_item' => '新規カテゴリーを追加',
                'new_item_name' => '新規カテゴリー名',
                'menu_name' => 'カテゴリー'
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => 'grant-category',
                'with_front' => false,
                'hierarchical' => true
            )
        ));
    }
    
    // grant_tip投稿タイプが存在しない場合のみ登録（ヒント・tips用）
    if (!post_type_exists('grant_tip')) {
        register_post_type('grant_tip', array(
            'labels' => array(
                'name' => '補助金ヒント',
                'singular_name' => 'ヒント',
                'menu_name' => '補助金ヒント'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-lightbulb',
            'show_in_rest' => true
        ));
    }
    
    // tool投稿タイプが存在しない場合のみ登録（ツール用）
    if (!post_type_exists('tool')) {
        register_post_type('tool', array(
            'labels' => array(
                'name' => 'ツール',
                'singular_name' => 'ツール',
                'menu_name' => 'ツール管理'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-admin-tools',
            'show_in_rest' => true
        ));
    }
    
    // case_study投稿タイプが存在しない場合のみ登録（事例用）
    if (!post_type_exists('case_study')) {
        register_post_type('case_study', array(
            'labels' => array(
                'name' => '活用事例',
                'singular_name' => '事例',
                'menu_name' => '活用事例'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-portfolio',
            'show_in_rest' => true
        ));
    }
});

/**
 * ACF不在時の代替関数
 */
if (!function_exists('get_field')) {
    /**
     * ACF get_field代替関数
     */
    function get_field($field, $post_id = null, $format_value = true) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Handle different post_id formats
        if (is_string($post_id)) {
            if (strpos($post_id, 'term_') === 0) {
                $term_id = str_replace('term_', '', $post_id);
                return get_term_meta($term_id, $field, true);
            } elseif (strpos($post_id, 'user_') === 0) {
                $user_id = str_replace('user_', '', $post_id);
                return get_user_meta($user_id, $field, true);
            } elseif (strpos($post_id, 'option') === 0) {
                return get_option($field);
            }
        }
        
        return get_post_meta($post_id, $field, true);
    }
}

if (!function_exists('update_field')) {
    /**
     * ACF update_field代替関数
     */
    function update_field($field, $value, $post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Handle different post_id formats
        if (is_string($post_id)) {
            if (strpos($post_id, 'term_') === 0) {
                $term_id = str_replace('term_', '', $post_id);
                return update_term_meta($term_id, $field, $value);
            } elseif (strpos($post_id, 'user_') === 0) {
                $user_id = str_replace('user_', '', $post_id);
                return update_user_meta($user_id, $field, $value);
            } elseif (strpos($post_id, 'option') === 0) {
                return update_option($field, $value);
            }
        }
        
        return update_post_meta($post_id, $field, $value);
    }
}

if (!function_exists('acf_add_local_field_group')) {
    /**
     * ACF フィールドグループ登録の代替（何もしない）
     */
    function acf_add_local_field_group($field_group) {
        // ACFが無い場合は何もしない
        // 将来的にカスタムフィールドUIを実装する場合はここに追加
        return false;
    }
}

if (!function_exists('the_field')) {
    /**
     * ACF the_field代替関数
     */
    function the_field($field, $post_id = null) {
        echo get_field($field, $post_id);
    }
}

/**
 * 必要なページの自動作成
 */
add_action('after_switch_theme', function() {
    // FAQページが存在しない場合は作成
    if (!get_page_by_path('faq')) {
        wp_insert_post(array(
            'post_title' => 'よくある質問',
            'post_name' => 'faq',
            'post_content' => '<!-- wp:heading --><h2>よくある質問</h2><!-- /wp:heading -->',
            'post_status' => 'publish',
            'post_type' => 'page',
            'page_template' => 'page-faq.php'
        ));
    }
    
    // AI診断ページが存在しない場合は作成
    if (!get_page_by_path('ai-diagnosis')) {
        wp_insert_post(array(
            'post_title' => 'AI診断',
            'post_name' => 'ai-diagnosis',
            'post_content' => '[gi_ai_diagnosis]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
    }
    
    // お問い合わせページが存在しない場合は作成
    if (!get_page_by_path('contact')) {
        wp_insert_post(array(
            'post_title' => 'お問い合わせ',
            'post_name' => 'contact',
            'post_content' => '<!-- wp:contact-form-7/contact-form-selector --><!-- /wp:contact-form-7/contact-form-selector -->',
            'post_status' => 'publish',
            'post_type' => 'page',
            'page_template' => 'page-contact.php'
        ));
    }
});

/**
 * 初期データベーステーブル作成
 */
register_activation_hook(__FILE__, function() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // AI診断履歴テーブル
    $table_name = $wpdb->prefix . 'gi_diagnosis_history';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0,
        diagnosis_data longtext,
        recommended_grants longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // エラーログテーブル
    $table_name = $wpdb->prefix . 'gi_error_log';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        error_code varchar(10) NOT NULL,
        error_message text NOT NULL,
        context longtext,
        user_id bigint(20) DEFAULT 0,
        ip_address varchar(45),
        user_agent text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY error_code (error_code),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // パフォーマンスログテーブル
    $table_name = $wpdb->prefix . 'gi_performance_log';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        page_url varchar(255),
        execution_time float,
        memory_usage bigint(20),
        peak_memory bigint(20),
        query_count int(11),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    dbDelta($sql);
});

/**
 * サンプルデータの作成（開発環境用）
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_init', function() {
        // サンプル補助金データがない場合のみ作成
        $existing = get_posts(array(
            'post_type' => 'grant',
            'posts_per_page' => 1
        ));
        
        if (empty($existing)) {
            // サンプルカテゴリ作成
            $categories = array(
                'スタートアップ支援',
                'IT・デジタル化',
                '研究開発',
                '人材育成',
                '地域振興'
            );
            
            foreach ($categories as $cat_name) {
                if (!term_exists($cat_name, 'grant_category')) {
                    wp_insert_term($cat_name, 'grant_category');
                }
            }
            
            // サンプル補助金作成
            $sample_grants = array(
                array(
                    'post_title' => 'IT導入補助金2024',
                    'post_content' => 'ITツール導入による業務効率化を支援する補助金です。',
                    'meta' => array(
                        'max_amount' => '最大450万円',
                        'max_amount_numeric' => 4500000,
                        'application_deadline' => date('Y-m-d', strtotime('+30 days')),
                        'target_individual' => false
                    )
                ),
                array(
                    'post_title' => '小規模事業者持続化補助金',
                    'post_content' => '小規模事業者の販路開拓等を支援する補助金です。',
                    'meta' => array(
                        'max_amount' => '最大200万円',
                        'max_amount_numeric' => 2000000,
                        'application_deadline' => date('Y-m-d', strtotime('+45 days')),
                        'target_individual' => true
                    )
                ),
                array(
                    'post_title' => 'ものづくり補助金',
                    'post_content' => '中小企業の設備投資を支援する補助金です。',
                    'meta' => array(
                        'max_amount' => '最大1250万円',
                        'max_amount_numeric' => 12500000,
                        'application_deadline' => date('Y-m-d', strtotime('+60 days')),
                        'target_individual' => false
                    )
                )
            );
            
            foreach ($sample_grants as $grant) {
                $post_id = wp_insert_post(array(
                    'post_title' => $grant['post_title'],
                    'post_content' => $grant['post_content'],
                    'post_type' => 'grant',
                    'post_status' => 'publish'
                ));
                
                if ($post_id && !is_wp_error($post_id)) {
                    foreach ($grant['meta'] as $key => $value) {
                        update_post_meta($post_id, $key, $value);
                    }
                    
                    // ランダムにカテゴリを割り当て
                    $term = get_terms(array(
                        'taxonomy' => 'grant_category',
                        'number' => 1,
                        'orderby' => 'rand'
                    ));
                    
                    if (!empty($term)) {
                        wp_set_object_terms($post_id, $term[0]->term_id, 'grant_category');
                    }
                }
            }
        }
    });
}

/**
 * 管理画面にセットアップ状態を表示
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $acf_active = class_exists('ACF');
    $grant_count = wp_count_posts('grant')->publish;
    
    ?>
    <div class="notice notice-info is-dismissible">
        <h3>Grant Insight テーマ - セットアップ状態</h3>
        <ul style="list-style: disc; margin-left: 20px;">
            <li>ACF (Advanced Custom Fields): <?php echo $acf_active ? '✅ 有効' : '⚠️ 未インストール（代替機能で動作中）'; ?></li>
            <li>補助金データ: <?php echo $grant_count > 0 ? "✅ {$grant_count}件" : '⚠️ データなし（サンプルデータを自動生成します）'; ?></li>
            <li>カスタム投稿タイプ: ✅ 登録済み</li>
            <li>データベーステーブル: ✅ 作成済み</li>
        </ul>
        <?php if (!$acf_active): ?>
        <p style="color: #d63638;">
            <strong>推奨:</strong> Advanced Custom Fields プラグインをインストールすることで、全機能が利用可能になります。
            <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>" class="button button-primary">ACFをインストール</a>
        </p>
        <?php endif; ?>
    </div>
    <?php
});

/**
 * テーマ有効化時の初期設定
 */
add_action('after_switch_theme', function() {
    // パーマリンク設定を更新
    flush_rewrite_rules();
    
    // 初期オプション設定
    if (!get_option('gi_theme_setup_complete')) {
        // デフォルトのカスタマイザー設定
        set_theme_mod('gi_header_bg_color', '#ffffff');
        set_theme_mod('gi_header_text_color', '#333333');
        set_theme_mod('gi_footer_bg_color', '#1a1a1a');
        set_theme_mod('gi_footer_text_color', '#ffffff');
        
        update_option('gi_theme_setup_complete', true);
    }
});