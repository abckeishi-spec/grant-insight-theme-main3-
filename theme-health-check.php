<?php
/**
 * WordPress Theme Health Check
 * Run this file to check theme integrity
 */

// Theme health check results
$results = array(
    'critical' => array(),
    'warnings' => array(),
    'success' => array()
);

// Check 1: Template files exist
$required_templates = array(
    'index.php',
    'header.php', 
    'footer.php',
    'functions.php'
);

foreach ($required_templates as $template) {
    if (file_exists(__DIR__ . '/' . $template)) {
        $results['success'][] = "✅ Required template exists: $template";
    } else {
        $results['critical'][] = "❌ Missing required template: $template";
    }
}

// Check 2: Template parts directory
if (is_dir(__DIR__ . '/template-parts')) {
    $results['success'][] = "✅ template-parts directory exists";
    
    // Check subdirectories
    $subdirs = array('front-page', 'cards');
    foreach ($subdirs as $subdir) {
        if (is_dir(__DIR__ . '/template-parts/' . $subdir)) {
            $count = count(glob(__DIR__ . '/template-parts/' . $subdir . '/*.php'));
            $results['success'][] = "✅ template-parts/$subdir exists with $count files";
        } else {
            $results['warnings'][] = "⚠️ Missing subdirectory: template-parts/$subdir";
        }
    }
} else {
    $results['critical'][] = "❌ template-parts directory missing";
}

// Check 3: Front page sections
$front_sections = array(
    'section-hero.php',
    'section-search.php',
    'section-problems.php',
    'section-categories.php',
    'section-news.php',
    'section-recommended-tools.php'
);

foreach ($front_sections as $section) {
    if (file_exists(__DIR__ . '/template-parts/front-page/' . $section)) {
        $results['success'][] = "✅ Front page section exists: $section";
    } else {
        $results['warnings'][] = "⚠️ Missing front page section: $section";
    }
}

// Check 4: Phase 1 files
if (is_dir(__DIR__ . '/inc/phase1')) {
    $phase1_count = count(glob(__DIR__ . '/inc/phase1/*.php'));
    $results['success'][] = "✅ Phase 1 directory exists with $phase1_count files";
} else {
    $results['warnings'][] = "⚠️ Phase 1 directory missing";
}

// Check 5: Archive template
if (file_exists(__DIR__ . '/archive.php')) {
    $results['success'][] = "✅ archive.php template exists";
} else {
    $results['warnings'][] = "⚠️ archive.php template missing";
}

// Check 6: Functions.php syntax
$functions_content = file_get_contents(__DIR__ . '/functions.php');
if (strpos($functions_content, 'acf-fields-setup.php') !== false && 
    strpos($functions_content, '// if (file_exists') === false) {
    $results['warnings'][] = "⚠️ functions.php may have broken includes";
} else {
    $results['success'][] = "✅ functions.php includes are properly handled";
}

// Check 7: Custom post type templates
$cpt_templates = array(
    'templates/singles/single-grant.php',
    'templates/archives/archive-grant.php'
);

foreach ($cpt_templates as $template) {
    if (file_exists(__DIR__ . '/' . $template)) {
        $results['success'][] = "✅ Custom template exists: $template";
    } else {
        $results['warnings'][] = "⚠️ Missing custom template: $template";
    }
}

// Output results
echo "====================================\n";
echo "  WORDPRESS THEME HEALTH CHECK\n";
echo "  Theme: Grant Insight V4\n";
echo "  Date: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n\n";

// Critical issues
if (!empty($results['critical'])) {
    echo "🔴 CRITICAL ISSUES (" . count($results['critical']) . ")\n";
    echo "====================================\n";
    foreach ($results['critical'] as $issue) {
        echo $issue . "\n";
    }
    echo "\n";
}

// Warnings
if (!empty($results['warnings'])) {
    echo "⚠️ WARNINGS (" . count($results['warnings']) . ")\n";
    echo "====================================\n";
    foreach ($results['warnings'] as $warning) {
        echo $warning . "\n";
    }
    echo "\n";
}

// Success
echo "✅ CHECKS PASSED (" . count($results['success']) . ")\n";
echo "====================================\n";
foreach ($results['success'] as $success) {
    echo $success . "\n";
}

// Summary
echo "\n====================================\n";
echo "SUMMARY:\n";
echo "✅ Passed: " . count($results['success']) . "\n";
echo "⚠️ Warnings: " . count($results['warnings']) . "\n";
echo "🔴 Critical: " . count($results['critical']) . "\n";

if (empty($results['critical'])) {
    echo "\n🎉 Theme is healthy and ready for use!\n";
} else {
    echo "\n❌ Critical issues need to be fixed before deployment.\n";
}

echo "====================================\n";
?>