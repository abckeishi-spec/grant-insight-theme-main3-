<?php
/**
 * Remove duplicate Tailwind CSS CDN includes
 */

$files = glob('./**/*.php', GLOB_BRACE);
$count = 0;

foreach ($files as $file) {
    // Skip backup files and this script
    if (strpos($file, 'backup-files') !== false || 
        strpos($file, 'remove-tailwind-duplicates.php') !== false ||
        strpos($file, 'header.php') !== false) {
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Remove script tags with Tailwind CDN
    $patterns = [
        '/<script\s+src=["\']https:\/\/cdn\.tailwindcss\.com[^"\']*["\']\s*><\/script>\s*/i',
        '/<script\s+src=["\']https:\/\/cdn\.tailwindcss\.com[^"\']*["\']\s*\/>\s*/i',
        '/<link\s+href=["\']https:\/\/cdn\.tailwindcss\.com[^"\']*["\']\s*[^>]*>\s*/i',
    ];
    
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Also comment out any inline Tailwind config that's outside header.php
    if (strpos($file, 'header.php') === false && strpos($file, 'functions.php') === false) {
        $content = preg_replace(
            '/<script>\s*tailwind\.config\s*=\s*{[^}]*}\s*<\/script>/s',
            '<!-- Tailwind config moved to header.php -->',
            $content
        );
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        $count++;
        echo "Cleaned: $file\n";
    }
}

echo "\nTotal files cleaned: $count\n";
?>