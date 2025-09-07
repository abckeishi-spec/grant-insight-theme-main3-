<?php
/**
 * すべてのPHPファイルの関数重複を修正するスクリプト
 */

$phase1_dir = __DIR__ . '/inc/phase1/';
$files_to_fix = [
    'ai-diagnosis.php',
    'category-display.php',
    'customizer-settings.php',
    'data-validation.php',
    'error-guidance-system.php',
    'grant-counts.php',
    'icon-management.php',
    'individual-categories.php',
    'responsive-accessibility.php',
    'search-filter-stability.php'
];

foreach ($files_to_fix as $filename) {
    $filepath = $phase1_dir . $filename;
    
    if (!file_exists($filepath)) {
        echo "File not found: $filename\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // バックアップを作成
    $backup_path = $phase1_dir . str_replace('.php', '-original.php', $filename);
    if (!file_exists($backup_path)) {
        file_put_contents($backup_path, $content);
    }
    
    // 関数定義のパターンを検索して置換
    // function functionName( を if (!function_exists('functionName')) { function functionName( に変換
    $pattern = '/^(function\s+)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/m';
    
    $modified_content = preg_replace_callback($pattern, function($matches) {
        $function_name = $matches[2];
        
        // すでに条件付きの場合はスキップ
        if (strpos($matches[0], 'function_exists') !== false) {
            return $matches[0];
        }
        
        return "if (!function_exists('{$function_name}')) {\n    function {$function_name}(";
    }, $content);
    
    // 関数の閉じ括弧の後に追加の閉じ括弧を追加
    // 各関数の終わりを見つけて、閉じ括弧を追加する必要がある
    $lines = explode("\n", $modified_content);
    $new_lines = [];
    $in_function = false;
    $brace_count = 0;
    $function_name = '';
    
    foreach ($lines as $line) {
        // 新しい関数定義を検出
        if (preg_match('/if \(!function_exists\([\'"]([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)[\'\"]\)\) \{/', $line, $matches)) {
            $in_function = true;
            $brace_count = 1; // if文の開き括弧
            $function_name = $matches[1];
        }
        
        $new_lines[] = $line;
        
        if ($in_function) {
            // 括弧をカウント
            $open_braces = substr_count($line, '{');
            $close_braces = substr_count($line, '}');
            $brace_count += $open_braces - $close_braces;
            
            // 関数定義が始まった場合
            if (strpos($line, "function {$function_name}(") !== false) {
                $brace_count = 1; // 関数の括弧カウントをリセット
            }
            
            // 関数が終了した場合
            if ($brace_count === 0 && $close_braces > 0) {
                // 追加の閉じ括弧を追加
                $new_lines[] = '}';
                $in_function = false;
                $function_name = '';
            }
        }
    }
    
    $modified_content = implode("\n", $new_lines);
    
    // ファイルを保存
    file_put_contents($filepath, $modified_content);
    echo "Fixed: $filename\n";
}

echo "All files have been fixed!\n";
?>