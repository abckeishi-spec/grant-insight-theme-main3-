<?php
/**
 * Simple PSR-4 Autoloader for Grant Insight Theme
 * 
 * This is a simplified autoloader for when Composer is not available
 */

spl_autoload_register(function ($class) {
    // プレフィックスを確認
    $prefix = 'GrantInsight\\';
    $base_dir = __DIR__ . '/src/';

    // クラスがプレフィックスを使用しているかチェック
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // 相対クラス名を取得
    $relative_class = substr($class, $len);

    // ファイルパスを作成
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // ファイルが存在する場合は読み込み
    if (file_exists($file)) {
        require $file;
    }
});

// コンポーネントローダーを読み込み
if (file_exists(__DIR__ . '/inc/function/component-loader.php')) {
    require_once __DIR__ . '/inc/function/component-loader.php';
}

