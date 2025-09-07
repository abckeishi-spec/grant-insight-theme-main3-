<?php
/**
 * Grant Insight Theme - Installation Verification Script
 * 
 * このスクリプトを実行して、すべてのコンポーネントが正しく設置されているか確認します。
 * 使用方法: WP-CLIまたはブラウザからアクセス
 */

// WordPress環境チェック
$is_wp_env = defined('ABSPATH');

echo "========================================\n";
echo "Grant Insight Theme 検証レポート\n";
echo "========================================\n\n";

// 1. 必須ファイルの存在確認
echo "【1. 必須ファイルチェック】\n";
echo "----------------------------\n";

$required_files = [
    // Core Files
    'functions.php' => '既存のテーマ関数',
    'functions-integration.php' => 'Phase 1機能統合ローダー',
    'quick-setup.php' => 'ACF代替＆初期セットアップ',
    'style.css' => 'テーマスタイルシート',
    
    // Phase 1 Features (16 Tasks)
    'ajax-handlers-improved.php' => 'Task 1&7: セキュリティ強化AJAX',
    'grant-counts.php' => 'Task 2: 動的件数取得',
    'ai-diagnosis.php' => 'Task 3: AI診断機能',
    'customizer-settings.php' => 'Task 4: カスタマイザー設定',
    'icon-management.php' => 'Task 5: アイコン管理',
    'helpers-improved.php' => 'Task 6: 改善版ヘルパー関数',
    'safe-output-functions.php' => 'Task 8: 安全な出力関数',
    'data-validation.php' => 'Task 9: データ検証・統一',
    'individual-categories.php' => 'Task 10: 個人向けカテゴリ',
    'category-display.php' => 'Task 11: カテゴリ表示機能',
    'search-filter-stability.php' => 'Task 12: 検索・フィルター安定化',
    'error-guidance-system.php' => 'Task 13: エラー/ガイダンス強化',
    'responsive-accessibility.php' => 'Task 14: レスポンシブ・アクセシビリティ',
    'admin-enhancements.php' => 'Task 15: 管理画面強化',
    'system-optimization.php' => 'Task 16: システム最適化',
];

$missing_files = [];
$found_files = [];

foreach ($required_files as $file => $description) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        $found_files[] = $file;
        echo "✅ {$file} - {$description}\n";
    } else {
        $missing_files[] = $file;
        echo "❌ {$file} - {$description} [見つかりません]\n";
    }
}

echo "\n";

// 2. JavaScriptファイルの確認
echo "【2. JavaScriptファイルチェック】\n";
echo "----------------------------------\n";

$js_files = [
    'assets/js/ai-diagnosis.js' => 'AI診断フロントエンド',
    'assets/js/dynamic-counts.js' => '動的件数更新',
    'assets/js/help-system.js' => 'ヘルプシステム',
    'assets/js/customizer-preview.js' => 'カスタマイザープレビュー',
];

$missing_js = [];
$found_js = [];

foreach ($js_files as $file => $description) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        $found_js[] = $file;
        echo "✅ {$file} - {$description}\n";
    } else {
        $missing_js[] = $file;
        echo "❌ {$file} - {$description} [見つかりません]\n";
    }
}

echo "\n";

// 3. テンプレートファイルの確認
echo "【3. テンプレートファイルチェック】\n";
echo "------------------------------------\n";

$template_files = [
    'archive-grant.php' => '補助金一覧ページ',
    'single-grant.php' => '補助金詳細ページ',
    'archive-grant_tip.php' => 'ヒント一覧ページ',
    'single-grant_tip.php' => 'ヒント詳細ページ',
    'archive-tool.php' => 'ツール一覧ページ',
    'single-tool.php' => 'ツール詳細ページ',
    'page-faq.php' => 'FAQページ',
    'page-contact.php' => 'お問い合わせページ',
];

foreach ($template_files as $file => $description) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        echo "✅ {$file} - {$description}\n";
    } else {
        echo "⚠️  {$file} - {$description} [オプション]\n";
    }
}

echo "\n";

// 4. ACF関連のチェック
echo "【4. ACF（Advanced Custom Fields）状態】\n";
echo "-----------------------------------------\n";

if (function_exists('get_field')) {
    echo "✅ ACFプラグインがインストールされています\n";
} else {
    echo "⚠️  ACFプラグインが見つかりません\n";
    echo "   → quick-setup.phpの代替機能が使用されます\n";
}

// quick-setup.phpのACF代替関数チェック
if (file_exists(__DIR__ . '/quick-setup.php')) {
    $quick_setup_content = file_get_contents(__DIR__ . '/quick-setup.php');
    if (strpos($quick_setup_content, 'function get_field') !== false) {
        echo "✅ ACF代替関数が定義されています\n";
    }
}

echo "\n";

// 5. functions.phpの統合確認
echo "【5. functions.php統合確認】\n";
echo "-----------------------------\n";

if (file_exists(__DIR__ . '/functions.php')) {
    $functions_content = file_get_contents(__DIR__ . '/functions.php');
    
    // functions-integration.phpの読み込み確認
    if (strpos($functions_content, 'functions-integration.php') !== false) {
        echo "✅ functions-integration.phpが読み込まれています\n";
    } else {
        echo "⚠️  functions-integration.phpが読み込まれていません\n";
        echo "   → functions.phpに以下を追加してください:\n";
        echo "     require_once get_template_directory() . '/functions-integration.php';\n";
    }
    
    // quick-setup.phpの読み込み確認
    if (strpos($functions_content, 'quick-setup.php') !== false) {
        echo "✅ quick-setup.phpが読み込まれています\n";
    } else {
        echo "⚠️  quick-setup.phpが読み込まれていません\n";
        echo "   → functions.phpに以下を追加してください:\n";
        echo "     require_once get_template_directory() . '/quick-setup.php';\n";
    }
}

echo "\n";

// 6. データベーステーブルチェック（WordPress環境の場合のみ）
if ($is_wp_env) {
    echo "【6. データベーステーブル】\n";
    echo "---------------------------\n";
    
    global $wpdb;
    $tables = [
        'gi_diagnosis_history' => 'AI診断履歴',
        'gi_error_logs' => 'エラーログ',
        'gi_performance_logs' => 'パフォーマンスログ',
    ];
    
    foreach ($tables as $table => $description) {
        $table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($exists) {
            echo "✅ {$table_name} - {$description}\n";
        } else {
            echo "⚠️  {$table_name} - {$description} [未作成]\n";
        }
    }
} else {
    echo "【6. データベーステーブル】\n";
    echo "---------------------------\n";
    echo "ℹ️  WordPress環境外のため確認できません\n";
}

echo "\n";

// 7. サマリー
echo "========================================\n";
echo "【検証結果サマリー】\n";
echo "========================================\n\n";

$total_required = count($required_files);
$total_found = count($found_files);
$completion_rate = round(($total_found / $total_required) * 100, 1);

echo "📊 必須ファイル: {$total_found}/{$total_required} ({$completion_rate}%)\n";

if (empty($missing_files)) {
    echo "✅ すべての必須ファイルが正常に配置されています！\n";
} else {
    echo "⚠️  以下のファイルが見つかりません:\n";
    foreach ($missing_files as $file) {
        echo "   - {$file}\n";
    }
}

echo "\n";

if (empty($missing_js)) {
    echo "✅ すべてのJavaScriptファイルが配置されています！\n";
} else {
    echo "⚠️  以下のJavaScriptファイルが見つかりません:\n";
    foreach ($missing_js as $file) {
        echo "   - {$file}\n";
    }
}

echo "\n";

// 8. 推奨事項
echo "【推奨事項】\n";
echo "------------\n";

$recommendations = [];

if (!empty($missing_files)) {
    $recommendations[] = "不足しているPHPファイルを追加してください";
}

if (!empty($missing_js)) {
    $recommendations[] = "不足しているJavaScriptファイルを追加してください";
}

if (!function_exists('get_field') && !file_exists(__DIR__ . '/quick-setup.php')) {
    $recommendations[] = "ACFプラグインをインストールするか、quick-setup.phpを設置してください";
}

if (file_exists(__DIR__ . '/functions.php')) {
    $functions_content = file_get_contents(__DIR__ . '/functions.php');
    if (strpos($functions_content, 'functions-integration.php') === false) {
        $recommendations[] = "functions.phpにfunctions-integration.phpの読み込みコードを追加してください";
    }
}

if (empty($recommendations)) {
    echo "✅ 現在、特に推奨事項はありません。\n";
    echo "   テーマは正常に動作する準備ができています！\n";
} else {
    foreach ($recommendations as $index => $rec) {
        echo ($index + 1) . ". {$rec}\n";
    }
}

echo "\n";
echo "========================================\n";
echo "検証完了: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n";

// HTMLフォーマット（ブラウザアクセス時）
if (php_sapi_name() !== 'cli' && !$is_wp_env) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Grant Insight Theme - 検証レポート</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 900px;
                margin: 0 auto;
                padding: 20px;
                background: #f5f5f5;
            }
            pre {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                overflow-x: auto;
                white-space: pre-wrap;
            }
            h1 {
                color: #2c3e50;
                border-bottom: 3px solid #3498db;
                padding-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <h1>Grant Insight Theme - インストール検証レポート</h1>
        <pre><?php ob_end_flush(); ?></pre>
    </body>
    </html>
    <?php
}
?>