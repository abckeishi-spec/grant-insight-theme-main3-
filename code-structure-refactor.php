<?php
/**
 * Grant Insight Code Structure Refactor
 * コード構造・メンテナンス性改善モジュール
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * コード構造リファクタリングクラス
 */
class GI_Code_Structure_Refactor {
    
    private static $instance = null;
    private $function_registry = array();
    private $naming_violations = array();
    
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
        $this->register_functions();
        $this->check_naming_conventions();
    }
    
    /**
     * フックの設定
     */
    private function setup_hooks() {
        // 開発環境でのコード品質チェック
        if (WP_DEBUG) {
            add_action('init', array($this, 'check_code_quality'), 999);
        }
        
        // 管理画面でのコード品質レポート
        add_action('admin_menu', array($this, 'add_code_quality_page'));
        
        // 関数重複チェック
        add_action('after_setup_theme', array($this, 'check_function_duplicates'), 999);
    }
    
    /**
     * 関数の登録
     */
    private function register_functions() {
        // 既存の関数を登録
        $this->function_registry = array(
            // コア関数
            'gi_safe_escape' => array(
                'file' => 'security-enhanced-v2.php',
                'class' => 'GI_Security_Enhancement_V2',
                'type' => 'security'
            ),
            'gi_safe_excerpt' => array(
                'file' => 'missing-functions.php',
                'class' => 'GI_Missing_Functions',
                'type' => 'utility'
            ),
            'gi_get_formatted_deadline' => array(
                'file' => 'missing-functions.php',
                'class' => 'GI_Missing_Functions',
                'type' => 'utility'
            ),
            'gi_format_amount' => array(
                'file' => 'missing-functions.php',
                'class' => 'GI_Missing_Functions',
                'type' => 'utility'
            ),
            
            // AJAX関数
            'gi_ajax_load_grants' => array(
                'file' => 'functions-fixed.php',
                'class' => null,
                'type' => 'ajax'
            ),
            'gi_ajax_search_grants' => array(
                'file' => 'functions-fixed.php',
                'class' => null,
                'type' => 'ajax'
            ),
            
            // キャッシュ関数
            'gi_get_cached_grants' => array(
                'file' => 'performance-enhanced.php',
                'class' => 'GI_Cache_System',
                'type' => 'cache'
            ),
            'gi_clear_cache' => array(
                'file' => 'performance-enhanced.php',
                'class' => 'GI_Cache_System',
                'type' => 'cache'
            ),
            
            // セキュリティ関数
            'gi_verify_nonce' => array(
                'file' => 'security-enhanced-v2.php',
                'class' => 'GI_Security_Enhancement_V2',
                'type' => 'security'
            ),
            'gi_sanitize_input' => array(
                'file' => 'security-enhanced-v2.php',
                'class' => 'GI_Security_Enhancement_V2',
                'type' => 'security'
            )
        );
    }
    
    /**
     * 命名規則のチェック
     */
    private function check_naming_conventions() {
        $theme_files = $this->get_theme_php_files();
        
        foreach ($theme_files as $file) {
            $this->check_file_naming_conventions($file);
        }
    }
    
    /**
     * ファイルの命名規則チェック
     */
    private function check_file_naming_conventions($file_path) {
        $content = file_get_contents($file_path);
        $file_name = basename($file_path);
        
        // 関数名のチェック
        preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
        
        foreach ($matches[1] as $function_name) {
            // WordPressの標準関数は除外
            if (in_array($function_name, array('__construct', '__destruct', '__call', '__get', '__set'))) {
                continue;
            }
            
            // gi_ プレフィックスのチェック
            if (!preg_match('/^(gi_|GI_|wp_|get_|is_|has_|the_)/', $function_name)) {
                $this->naming_violations[] = array(
                    'type' => 'function',
                    'name' => $function_name,
                    'file' => $file_name,
                    'issue' => 'Missing gi_ prefix',
                    'suggestion' => 'gi_' . $function_name
                );
            }
        }
        
        // クラス名のチェック
        preg_match_all('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*{/', $content, $class_matches);
        
        foreach ($class_matches[1] as $class_name) {
            if (!preg_match('/^GI_/', $class_name)) {
                $this->naming_violations[] = array(
                    'type' => 'class',
                    'name' => $class_name,
                    'file' => $file_name,
                    'issue' => 'Missing GI_ prefix',
                    'suggestion' => 'GI_' . $class_name
                );
            }
        }
        
        // 定数名のチェック
        preg_match_all('/define\s*\(\s*[\'"]([A-Z_][A-Z0-9_]*)[\'"]/', $content, $const_matches);
        
        foreach ($const_matches[1] as $const_name) {
            if (!preg_match('/^GI_/', $const_name)) {
                $this->naming_violations[] = array(
                    'type' => 'constant',
                    'name' => $const_name,
                    'file' => $file_name,
                    'issue' => 'Missing GI_ prefix',
                    'suggestion' => 'GI_' . $const_name
                );
            }
        }
    }
    
    /**
     * 関数重複のチェック
     */
    public function check_function_duplicates() {
        $defined_functions = array();
        $duplicates = array();
        
        foreach ($this->function_registry as $function_name => $info) {
            if (function_exists($function_name)) {
                if (isset($defined_functions[$function_name])) {
                    $duplicates[] = array(
                        'function' => $function_name,
                        'files' => array($defined_functions[$function_name], $info['file'])
                    );
                } else {
                    $defined_functions[$function_name] = $info['file'];
                }
            }
        }
        
        if (!empty($duplicates) && WP_DEBUG) {
            foreach ($duplicates as $duplicate) {
                error_log('GI Code Quality: Duplicate function "' . $duplicate['function'] . '" found in: ' . implode(', ', $duplicate['files']));
            }
        }
    }
    
    /**
     * コード品質のチェック
     */
    public function check_code_quality() {
        $issues = array();
        
        // 関数の複雑度チェック
        $issues = array_merge($issues, $this->check_function_complexity());
        
        // コードの重複チェック
        $issues = array_merge($issues, $this->check_code_duplication());
        
        // 未使用関数のチェック
        $issues = array_merge($issues, $this->check_unused_functions());
        
        // 長すぎるファイルのチェック
        $issues = array_merge($issues, $this->check_file_length());
        
        if (!empty($issues)) {
            foreach ($issues as $issue) {
                error_log('GI Code Quality: ' . $issue['message'] . ' in ' . $issue['file']);
            }
        }
    }
    
    /**
     * 関数の複雑度チェック
     */
    private function check_function_complexity() {
        $issues = array();
        $theme_files = $this->get_theme_php_files();
        
        foreach ($theme_files as $file) {
            $content = file_get_contents($file);
            
            // 関数の抽出
            preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*{(.*?)^}/ms', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $function_name = $match[1];
                $function_body = $match[2];
                
                // 循環的複雑度の簡易計算
                $complexity = $this->calculate_cyclomatic_complexity($function_body);
                
                if ($complexity > 10) {
                    $issues[] = array(
                        'type' => 'complexity',
                        'message' => "Function '{$function_name}' has high complexity ({$complexity})",
                        'file' => basename($file),
                        'severity' => $complexity > 15 ? 'high' : 'medium'
                    );
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * 循環的複雑度の計算
     */
    private function calculate_cyclomatic_complexity($code) {
        $complexity = 1; // 基本パス
        
        // 条件分岐の数をカウント
        $patterns = array(
            '/\bif\s*\(/',
            '/\belseif\s*\(/',
            '/\belse\b/',
            '/\bwhile\s*\(/',
            '/\bfor\s*\(/',
            '/\bforeach\s*\(/',
            '/\bswitch\s*\(/',
            '/\bcase\s+/',
            '/\bcatch\s*\(/',
            '/\b\?\s*.*?\s*:/', // 三項演算子
            '/\b&&\b/',
            '/\b\|\|\b/'
        );
        
        foreach ($patterns as $pattern) {
            $complexity += preg_match_all($pattern, $code);
        }
        
        return $complexity;
    }
    
    /**
     * コードの重複チェック
     */
    private function check_code_duplication() {
        $issues = array();
        $theme_files = $this->get_theme_php_files();
        $code_blocks = array();
        
        foreach ($theme_files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            // 10行以上のブロックをチェック
            for ($i = 0; $i < count($lines) - 10; $i++) {
                $block = implode("\n", array_slice($lines, $i, 10));
                $block = trim(preg_replace('/\s+/', ' ', $block)); // 空白を正規化
                
                if (strlen($block) > 100) { // 短すぎるブロックは除外
                    $hash = md5($block);
                    
                    if (isset($code_blocks[$hash])) {
                        $issues[] = array(
                            'type' => 'duplication',
                            'message' => 'Duplicate code block found',
                            'file' => basename($file) . ':' . ($i + 1),
                            'duplicate_in' => $code_blocks[$hash],
                            'severity' => 'medium'
                        );
                    } else {
                        $code_blocks[$hash] = basename($file) . ':' . ($i + 1);
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * 未使用関数のチェック
     */
    private function check_unused_functions() {
        $issues = array();
        $all_functions = array();
        $used_functions = array();
        $theme_files = $this->get_theme_php_files();
        
        // 全ての関数を収集
        foreach ($theme_files as $file) {
            $content = file_get_contents($file);
            preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
            
            foreach ($matches[1] as $function_name) {
                $all_functions[$function_name] = basename($file);
            }
        }
        
        // 使用されている関数を収集
        foreach ($theme_files as $file) {
            $content = file_get_contents($file);
            
            foreach (array_keys($all_functions) as $function_name) {
                if (preg_match('/\b' . preg_quote($function_name) . '\s*\(/', $content)) {
                    $used_functions[] = $function_name;
                }
            }
        }
        
        // 未使用関数の検出
        foreach ($all_functions as $function_name => $file) {
            if (!in_array($function_name, $used_functions) && 
                !in_array($function_name, array('__construct', '__destruct'))) {
                $issues[] = array(
                    'type' => 'unused',
                    'message' => "Unused function '{$function_name}'",
                    'file' => $file,
                    'severity' => 'low'
                );
            }
        }
        
        return $issues;
    }
    
    /**
     * ファイル長のチェック
     */
    private function check_file_length() {
        $issues = array();
        $theme_files = $this->get_theme_php_files();
        
        foreach ($theme_files as $file) {
            $line_count = count(file($file));
            
            if ($line_count > 1000) {
                $issues[] = array(
                    'type' => 'file_length',
                    'message' => "File is too long ({$line_count} lines)",
                    'file' => basename($file),
                    'severity' => $line_count > 2000 ? 'high' : 'medium'
                );
            }
        }
        
        return $issues;
    }
    
    /**
     * テーマのPHPファイル取得
     */
    private function get_theme_php_files() {
        $theme_dir = get_template_directory();
        $files = array();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($theme_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * コード品質ページの追加
     */
    public function add_code_quality_page() {
        add_submenu_page(
            'themes.php',
            'コード品質',
            'コード品質',
            'manage_options',
            'gi-code-quality',
            array($this, 'render_code_quality_page')
        );
    }
    
    /**
     * コード品質ページのレンダリング
     */
    public function render_code_quality_page() {
        $issues = array();
        
        if (isset($_POST['run_analysis'])) {
            $issues = array_merge($issues, $this->check_function_complexity());
            $issues = array_merge($issues, $this->check_code_duplication());
            $issues = array_merge($issues, $this->check_unused_functions());
            $issues = array_merge($issues, $this->check_file_length());
        }
        
        ?>
        <div class="wrap">
            <h1>Grant Insight コード品質分析</h1>
            
            <form method="post">
                <p>
                    <input type="submit" name="run_analysis" class="button button-primary" value="コード品質分析を実行" />
                </p>
            </form>
            
            <?php if (!empty($issues)): ?>
            <h2>分析結果</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>種類</th>
                        <th>メッセージ</th>
                        <th>ファイル</th>
                        <th>重要度</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?php echo esc_html($issue['type']); ?></td>
                        <td><?php echo esc_html($issue['message']); ?></td>
                        <td><?php echo esc_html($issue['file']); ?></td>
                        <td>
                            <span class="severity-<?php echo esc_attr($issue['severity']); ?>">
                                <?php echo esc_html($issue['severity']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <h2>命名規則違反</h2>
            <?php if (!empty($this->naming_violations)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>種類</th>
                        <th>名前</th>
                        <th>ファイル</th>
                        <th>問題</th>
                        <th>推奨</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->naming_violations as $violation): ?>
                    <tr>
                        <td><?php echo esc_html($violation['type']); ?></td>
                        <td><?php echo esc_html($violation['name']); ?></td>
                        <td><?php echo esc_html($violation['file']); ?></td>
                        <td><?php echo esc_html($violation['issue']); ?></td>
                        <td><?php echo esc_html($violation['suggestion']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>命名規則違反は見つかりませんでした。</p>
            <?php endif; ?>
            
            <h2>関数レジストリ</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>関数名</th>
                        <th>ファイル</th>
                        <th>クラス</th>
                        <th>種類</th>
                        <th>状態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->function_registry as $function_name => $info): ?>
                    <tr>
                        <td><?php echo esc_html($function_name); ?></td>
                        <td><?php echo esc_html($info['file']); ?></td>
                        <td><?php echo esc_html($info['class'] ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($info['type']); ?></td>
                        <td>
                            <?php if (function_exists($function_name)): ?>
                                <span style="color: green;">✅ 定義済み</span>
                            <?php else: ?>
                                <span style="color: red;">❌ 未定義</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <style>
            .severity-high { color: #d63638; font-weight: bold; }
            .severity-medium { color: #dba617; }
            .severity-low { color: #72aee6; }
            </style>
        </div>
        <?php
    }
    
    /**
     * 自動リファクタリング提案の生成
     */
    public function generate_refactor_suggestions() {
        $suggestions = array();
        
        // functions.php の分割提案
        $functions_file = get_template_directory() . '/functions.php';
        if (file_exists($functions_file)) {
            $line_count = count(file($functions_file));
            
            if ($line_count > 1000) {
                $suggestions[] = array(
                    'type' => 'file_split',
                    'message' => 'functions.php を機能別に分割することを推奨します',
                    'files' => array(
                        'inc/core/post-types.php' => 'カスタム投稿タイプ関連',
                        'inc/core/taxonomies.php' => 'タクソノミー関連',
                        'inc/ajax/handlers.php' => 'AJAX処理関連',
                        'inc/admin/settings.php' => '管理画面設定関連',
                        'inc/frontend/enqueue.php' => 'スクリプト・スタイル読み込み関連'
                    )
                );
            }
        }
        
        // 重複関数の統合提案
        foreach ($this->function_registry as $function_name => $info) {
            if ($info['type'] === 'utility') {
                $suggestions[] = array(
                    'type' => 'function_consolidation',
                    'message' => "ユーティリティ関数 '{$function_name}' を共通クラスに統合",
                    'target_class' => 'GI_Utility_Functions'
                );
            }
        }
        
        return $suggestions;
    }
    
    /**
     * 関数レジストリの取得
     */
    public function get_function_registry() {
        return $this->function_registry;
    }
    
    /**
     * 命名規則違反の取得
     */
    public function get_naming_violations() {
        return $this->naming_violations;
    }
}

// コード構造リファクタリングの初期化
if (!function_exists('gi_init_code_refactor')) {
    function gi_init_code_refactor() {
        GI_Code_Structure_Refactor::getInstance();
    }
    add_action('after_setup_theme', 'gi_init_code_refactor', 999);
}

// ヘルパー関数
if (!function_exists('gi_register_function')) {
    function gi_register_function($function_name, $file, $class = null, $type = 'utility') {
        $refactor = GI_Code_Structure_Refactor::getInstance();
        $registry = $refactor->get_function_registry();
        $registry[$function_name] = array(
            'file' => $file,
            'class' => $class,
            'type' => $type
        );
    }
}

if (!function_exists('gi_check_function_exists')) {
    function gi_check_function_exists($function_name) {
        if (!function_exists($function_name)) {
            error_log("GI Code Quality: Required function '{$function_name}' is not defined");
            return false;
        }
        return true;
    }
}

