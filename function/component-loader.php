<?php
/**
 * Component Loader
 * 
 * コンポーネントを簡単に読み込むためのヘルパー関数群
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * コンポーネントを読み込む
 * 
 * @param string $component_path コンポーネントのパス（例: 'cards/grant-card'）
 * @param array  $args          コンポーネントに渡す引数
 * @param bool   $echo          出力するかどうか
 * @return string|void
 */
function gi_component($component_path, $args = [], $echo = true) {
    $component_file = get_template_directory() . '/components/' . $component_path . '.php';
    
    if (!file_exists($component_file)) {
        if (WP_DEBUG) {
            $error_msg = "Component not found: {$component_path}";
            error_log($error_msg);
            if ($echo) {
                echo "<!-- {$error_msg} -->";
            } else {
                return "<!-- {$error_msg} -->";
            }
        }
        return;
    }
    
    if ($echo) {
        ob_start();
        include $component_file;
        $output = ob_get_clean();
        echo $output;
    } else {
        ob_start();
        include $component_file;
        return ob_get_clean();
    }
}

/**
 * グラントカードコンポーネントのショートカット
 * 
 * @param array $args コンポーネント引数
 * @param bool  $echo 出力するかどうか
 */
function gi_grant_card($args = [], $echo = true) {
    return gi_component('cards/grant-card', $args, $echo);
}

/**
 * ツールカードコンポーネントのショートカット
 * 
 * @param array $args コンポーネント引数
 * @param bool  $echo 出力するかどうか
 */
function gi_tool_card($args = [], $echo = true) {
    return gi_component('cards/tool-card', $args, $echo);
}

/**
 * ボタンコンポーネントのショートカット
 * 
 * @param array $args コンポーネント引数
 * @param bool  $echo 出力するかどうか
 */
function gi_button($args = [], $echo = true) {
    return gi_component('ui/button', $args, $echo);
}

/**
 * コンポーネント一覧を取得（開発用）
 * 
 * @return array 利用可能なコンポーネントの配列
 */
function gi_get_available_components() {
    $components_dir = get_template_directory() . '/components';
    $components = [];
    
    if (is_dir($components_dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($components_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative_path = str_replace($components_dir . '/', '', $file->getPathname());
                $component_name = str_replace('.php', '', $relative_path);
                $components[] = $component_name;
            }
        }
    }
    
    return $components;
}

/**
 * 管理画面でコンポーネント一覧を表示（開発用）
 */
function gi_admin_show_components() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    add_action('admin_notices', function() {
        if (isset($_GET['show_components']) && $_GET['show_components'] === '1') {
            $components = gi_get_available_components();
            echo '<div class="notice notice-info">';
            echo '<h3>利用可能なコンポーネント:</h3>';
            echo '<ul>';
            foreach ($components as $component) {
                echo '<li><code>gi_component(\'' . esc_html($component) . '\')</code></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    });
}
add_action('admin_init', 'gi_admin_show_components');

