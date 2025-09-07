<?php
/**
 * Grant Insight Modular Functions
 * モジュール化されたfunctions.php - 機能別ファイル分割版
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * モジュール管理クラス
 */
class GI_Modular_Functions {
    
    private static $instance = null;
    private $loaded_modules = array();
    private $module_dependencies = array();
    
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
        $this->setup_module_dependencies();
        $this->load_core_modules();
        $this->setup_hooks();
    }
    
    /**
     * モジュール依存関係の設定
     */
    private function setup_module_dependencies() {
        $this->module_dependencies = array(
            // コアモジュール（依存関係なし）
            'core' => array(),
            'security' => array(),
            'compatibility' => array(),
            
            // 基本機能モジュール（コアに依存）
            'post-types' => array('core', 'security'),
            'taxonomies' => array('core', 'post-types'),
            'meta-fields' => array('core', 'post-types', 'security'),
            
            // 機能拡張モジュール
            'ajax' => array('core', 'security', 'post-types'),
            'search' => array('core', 'post-types', 'ajax'),
            'cache' => array('core'),
            'performance' => array('core', 'cache'),
            
            // フロントエンド関連
            'enqueue' => array('core', 'performance'),
            'frontend' => array('core', 'enqueue'),
            'shortcodes' => array('core', 'post-types'),
            
            // 管理画面関連
            'admin' => array('core', 'security'),
            'settings' => array('core', 'admin'),
            'dashboard' => array('core', 'admin'),
            
            // 統合・API関連
            'api' => array('core', 'security'),
            'integrations' => array('core', 'api'),
            'analytics' => array('core', 'integrations')
        );
    }
    
    /**
     * コアモジュールの読み込み
     */
    private function load_core_modules() {
        $core_modules = array(
            'core' => 'Core functionality and utilities',
            'security' => 'Security enhancements',
            'compatibility' => 'WordPress compatibility',
            'post-types' => 'Custom post types',
            'taxonomies' => 'Custom taxonomies',
            'meta-fields' => 'Custom fields and meta data',
            'ajax' => 'AJAX handlers',
            'search' => 'Search functionality',
            'cache' => 'Caching system',
            'performance' => 'Performance optimizations',
            'enqueue' => 'Scripts and styles',
            'frontend' => 'Frontend functionality',
            'admin' => 'Admin interface',
            'settings' => 'Theme settings'
        );
        
        foreach ($core_modules as $module => $description) {
            $this->load_module($module, $description);
        }
    }
    
    /**
     * モジュールの読み込み
     */
    public function load_module($module_name, $description = '') {
        // 既に読み込み済みの場合はスキップ
        if (in_array($module_name, $this->loaded_modules)) {
            return true;
        }
        
        // 依存関係のチェック
        if (isset($this->module_dependencies[$module_name])) {
            foreach ($this->module_dependencies[$module_name] as $dependency) {
                if (!in_array($dependency, $this->loaded_modules)) {
                    $this->load_module($dependency);
                }
            }
        }
        
        // モジュールファイルの読み込み
        $module_file = get_template_directory() . '/inc/' . $module_name . '.php';
        
        if (file_exists($module_file)) {
            require_once $module_file;
            $this->loaded_modules[] = $module_name;
            
            if (WP_DEBUG) {
                error_log("GI Module: Loaded '{$module_name}' - {$description}");
            }
            
            return true;
        } else {
            // モジュールファイルが存在しない場合は作成
            $this->create_module_file($module_name, $description);
            
            if (file_exists($module_file)) {
                require_once $module_file;
                $this->loaded_modules[] = $module_name;
                return true;
            }
        }
        
        error_log("GI Module: Failed to load '{$module_name}'");
        return false;
    }
    
    /**
     * モジュールファイルの作成
     */
    private function create_module_file($module_name, $description) {
        $module_dir = get_template_directory() . '/inc';
        $module_file = $module_dir . '/' . $module_name . '.php';
        
        // incディレクトリが存在しない場合は作成
        if (!is_dir($module_dir)) {
            wp_mkdir_p($module_dir);
        }
        
        $template = $this->get_module_template($module_name, $description);
        
        if (file_put_contents($module_file, $template) !== false) {
            if (WP_DEBUG) {
                error_log("GI Module: Created module file '{$module_name}.php'");
            }
        }
    }
    
    /**
     * モジュールテンプレートの取得
     */
    private function get_module_template($module_name, $description) {
        $class_name = 'GI_' . str_replace('-', '_', ucwords($module_name, '-'));
        $module_title = ucwords(str_replace('-', ' ', $module_name));
        
        $template = "<?php\n";
        $template .= "/**\n";
        $template .= " * Grant Insight {$module_title} Module\n";
        $template .= " * {$description}\n";
        $template .= " * \n";
        $template .= " * @package Grant_Insight_Perfect\n";
        $template .= " * @version 1.0\n";
        $template .= " */\n\n";
        $template .= "// セキュリティチェック\n";
        $template .= "if (!defined('ABSPATH')) {\n";
        $template .= "    exit;\n";
        $template .= "}\n\n";
        
        switch ($module_name) {
            case 'core':
                $template .= $this->get_core_module_content($class_name);
                break;
            case 'post-types':
                $template .= $this->get_post_types_module_content($class_name);
                break;
            case 'taxonomies':
                $template .= $this->get_taxonomies_module_content($class_name);
                break;
            case 'ajax':
                $template .= $this->get_ajax_module_content($class_name);
                break;
            case 'enqueue':
                $template .= $this->get_enqueue_module_content($class_name);
                break;
            case 'admin':
                $template .= $this->get_admin_module_content($class_name);
                break;
            default:
                $template .= $this->get_default_module_content($class_name, $module_name);
                break;
        }
        
        return $template;
    }
    
    /**
     * コアモジュールの内容
     */
    private function get_core_module_content($class_name) {
        return "/**\n * コア機能クラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        // コア機能の初期化\n        add_action('after_setup_theme', array(__CLASS__, 'setup_theme_support'));\n        add_action('init', array(__CLASS__, 'setup_core_features'));\n    }\n    \n    public static function setup_theme_support() {\n        // テーマサポートの追加\n        add_theme_support('post-thumbnails');\n        add_theme_support('title-tag');\n        add_theme_support('custom-logo');\n    }\n    \n    public static function setup_core_features() {\n        // コア機能の設定\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * カスタム投稿タイプモジュールの内容
     */
    private function get_post_types_module_content($class_name) {
        return "/**\n * カスタム投稿タイプクラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        add_action('init', array(__CLASS__, 'register_post_types'));\n    }\n    \n    public static function register_post_types() {\n        // 助成金投稿タイプ\n        register_post_type('grant', array(\n            'labels' => array(\n                'name' => '助成金',\n                'singular_name' => '助成金'\n            ),\n            'public' => true,\n            'has_archive' => true,\n            'supports' => array('title', 'editor', 'thumbnail', 'excerpt')\n        ));\n        \n        // ツール投稿タイプ\n        register_post_type('tool', array(\n            'labels' => array(\n                'name' => 'ツール',\n                'singular_name' => 'ツール'\n            ),\n            'public' => true,\n            'has_archive' => true,\n            'supports' => array('title', 'editor', 'thumbnail', 'excerpt')\n        ));\n        \n        // 事例投稿タイプ\n        register_post_type('case_study', array(\n            'labels' => array(\n                'name' => '成功事例',\n                'singular_name' => '成功事例'\n            ),\n            'public' => true,\n            'has_archive' => true,\n            'supports' => array('title', 'editor', 'thumbnail', 'excerpt')\n        ));\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * タクソノミーモジュールの内容
     */
    private function get_taxonomies_module_content($class_name) {
        return "/**\n * カスタムタクソノミークラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        add_action('init', array(__CLASS__, 'register_taxonomies'));\n    }\n    \n    public static function register_taxonomies() {\n        // 助成金カテゴリ\n        register_taxonomy('grant_category', 'grant', array(\n            'labels' => array(\n                'name' => '助成金カテゴリ',\n                'singular_name' => '助成金カテゴリ'\n            ),\n            'hierarchical' => true,\n            'public' => true\n        ));\n        \n        // 都道府県\n        register_taxonomy('prefecture', array('grant', 'case_study'), array(\n            'labels' => array(\n                'name' => '都道府県',\n                'singular_name' => '都道府県'\n            ),\n            'hierarchical' => true,\n            'public' => true\n        ));\n        \n        // ツールカテゴリ\n        register_taxonomy('tool_category', 'tool', array(\n            'labels' => array(\n                'name' => 'ツールカテゴリ',\n                'singular_name' => 'ツールカテゴリ'\n            ),\n            'hierarchical' => true,\n            'public' => true\n        ));\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * AJAXモジュールの内容
     */
    private function get_ajax_module_content($class_name) {
        return "/**\n * AJAX処理クラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        // AJAX アクションの登録\n        add_action('wp_ajax_gi_load_grants', array(__CLASS__, 'load_grants'));\n        add_action('wp_ajax_nopriv_gi_load_grants', array(__CLASS__, 'load_grants'));\n        \n        add_action('wp_ajax_gi_search_grants', array(__CLASS__, 'search_grants'));\n        add_action('wp_ajax_nopriv_gi_search_grants', array(__CLASS__, 'search_grants'));\n    }\n    \n    public static function load_grants() {\n        // nonce検証\n        if (!wp_verify_nonce(\$_POST['nonce'], 'gi_ajax_nonce')) {\n            wp_die('Security check failed');\n        }\n        \n        // 助成金の読み込み処理\n        \$page = intval(\$_POST['page'] ?? 1);\n        \$per_page = 10;\n        \n        \$args = array(\n            'post_type' => 'grant',\n            'posts_per_page' => \$per_page,\n            'paged' => \$page,\n            'post_status' => 'publish'\n        );\n        \n        \$query = new WP_Query(\$args);\n        \n        if (\$query->have_posts()) {\n            while (\$query->have_posts()) {\n                \$query->the_post();\n                // 助成金アイテムの出力\n            }\n        }\n        \n        wp_die();\n    }\n    \n    public static function search_grants() {\n        // nonce検証\n        if (!wp_verify_nonce(\$_POST['nonce'], 'gi_ajax_nonce')) {\n            wp_die('Security check failed');\n        }\n        \n        // 検索処理\n        \$search_term = sanitize_text_field(\$_POST['search'] ?? '');\n        \n        \$args = array(\n            'post_type' => 'grant',\n            's' => \$search_term,\n            'posts_per_page' => 10,\n            'post_status' => 'publish'\n        );\n        \n        \$query = new WP_Query(\$args);\n        \n        if (\$query->have_posts()) {\n            while (\$query->have_posts()) {\n                \$query->the_post();\n                // 検索結果の出力\n            }\n        }\n        \n        wp_die();\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * スクリプト・スタイル読み込みモジュールの内容
     */
    private function get_enqueue_module_content($class_name) {
        return "/**\n * スクリプト・スタイル読み込みクラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));\n        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));\n        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));\n    }\n    \n    public static function enqueue_styles() {\n        // Tailwind CSS\n        wp_enqueue_style('gi-tailwind', 'https://cdn.tailwindcss.com/3.3.0/tailwind.min.css', array(), '3.3.0');\n        \n        // テーマスタイル\n        wp_enqueue_style('gi-theme-style', get_stylesheet_uri(), array('gi-tailwind'), wp_get_theme()->get('Version'));\n    }\n    \n    public static function enqueue_scripts() {\n        // jQuery\n        wp_enqueue_script('jquery');\n        \n        // テーマスクリプト\n        wp_enqueue_script('gi-theme-script', get_template_directory_uri() . '/js/theme.js', array('jquery'), wp_get_theme()->get('Version'), true);\n        \n        // AJAX設定\n        wp_localize_script('gi-theme-script', 'gi_ajax', array(\n            'ajax_url' => admin_url('admin-ajax.php'),\n            'nonce' => wp_create_nonce('gi_ajax_nonce')\n        ));\n    }\n    \n    public static function admin_enqueue_scripts() {\n        // 管理画面用スクリプト\n        wp_enqueue_script('gi-admin-script', get_template_directory_uri() . '/js/admin.js', array('jquery'), wp_get_theme()->get('Version'), true);\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * 管理画面モジュールの内容
     */
    private function get_admin_module_content($class_name) {
        return "/**\n * 管理画面機能クラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));\n        add_action('admin_init', array(__CLASS__, 'admin_init'));\n    }\n    \n    public static function add_admin_menu() {\n        add_theme_page(\n            'Grant Insight 設定',\n            'Grant Insight',\n            'manage_options',\n            'gi-settings',\n            array(__CLASS__, 'settings_page')\n        );\n    }\n    \n    public static function admin_init() {\n        // 管理画面の初期化処理\n    }\n    \n    public static function settings_page() {\n        echo '<div class=\"wrap\">';\n        echo '<h1>Grant Insight 設定</h1>';\n        echo '<p>テーマの設定ページです。</p>';\n        echo '</div>';\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * デフォルトモジュールの内容
     */
    private function get_default_module_content($class_name, $module_name) {
        return "/**\n * {$class_name} クラス\n */\nclass {$class_name} {\n    \n    public static function init() {\n        // {$module_name} モジュールの初期化\n    }\n}\n\n// 初期化\n{$class_name}::init();\n";
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // 管理画面でのモジュール管理
        add_action('admin_menu', array($this, 'add_module_management_page'));
        
        // モジュールの動的読み込み
        add_action('init', array($this, 'load_optional_modules'));
    }
    
    /**
     * オプションモジュールの読み込み
     */
    public function load_optional_modules() {
        $optional_modules = array(
            'shortcodes' => 'Shortcode functionality',
            'widgets' => 'Custom widgets',
            'customizer' => 'Theme customizer options',
            'api' => 'REST API endpoints',
            'integrations' => 'Third-party integrations'
        );
        
        foreach ($optional_modules as $module => $description) {
            if (get_option('gi_enable_' . str_replace('-', '_', $module), false)) {
                $this->load_module($module, $description);
            }
        }
    }
    
    /**
     * モジュール管理ページの追加
     */
    public function add_module_management_page() {
        add_submenu_page(
            'themes.php',
            'モジュール管理',
            'モジュール管理',
            'manage_options',
            'gi-modules',
            array($this, 'render_module_management_page')
        );
    }
    
    /**
     * モジュール管理ページのレンダリング
     */
    public function render_module_management_page() {
        ?>
        <div class="wrap">
            <h1>Grant Insight モジュール管理</h1>
            
            <h2>読み込み済みモジュール</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>モジュール名</th>
                        <th>状態</th>
                        <th>依存関係</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->module_dependencies as $module => $dependencies): ?>
                    <tr>
                        <td><?php echo esc_html($module); ?></td>
                        <td>
                            <?php if (in_array($module, $this->loaded_modules)): ?>
                                <span style="color: green;">✅ 読み込み済み</span>
                            <?php else: ?>
                                <span style="color: red;">❌ 未読み込み</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(implode(', ', $dependencies)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>モジュール統計</h2>
            <p>
                <strong>総モジュール数:</strong> <?php echo count($this->module_dependencies); ?><br>
                <strong>読み込み済み:</strong> <?php echo count($this->loaded_modules); ?><br>
                <strong>読み込み率:</strong> <?php echo round((count($this->loaded_modules) / count($this->module_dependencies)) * 100, 1); ?>%
            </p>
        </div>
        <?php
    }
    
    /**
     * 読み込み済みモジュールの取得
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }
    
    /**
     * モジュール依存関係の取得
     */
    public function get_module_dependencies() {
        return $this->module_dependencies;
    }
}

// モジュラー関数システムの初期化
if (!function_exists('gi_init_modular_functions')) {
    function gi_init_modular_functions() {
        GI_Modular_Functions::getInstance();
    }
    add_action('after_setup_theme', 'gi_init_modular_functions', 1);
}

// ヘルパー関数
if (!function_exists('gi_load_module')) {
    function gi_load_module($module_name, $description = '') {
        $modular = GI_Modular_Functions::getInstance();
        return $modular->load_module($module_name, $description);
    }
}

if (!function_exists('gi_is_module_loaded')) {
    function gi_is_module_loaded($module_name) {
        $modular = GI_Modular_Functions::getInstance();
        $loaded_modules = $modular->get_loaded_modules();
        return in_array($module_name, $loaded_modules);
    }
}

