<?php
/**
 * ACF Local JSON importer/registrar
 *
 * Loads field groups from /acf-fields.json and registers them via acf_add_local_field_group().
 * Also enables ACF Local JSON save/load to /acf-json within the theme so groups appear in UI and can be synced.
 */

if (!defined('ABSPATH')) { exit; }

// Ensure ACF is present before doing anything.
if (!function_exists('acf_add_local_field_group')) {
    // If ACF not active, bail out early.
    return;
}

/**
 * Configure ACF Local JSON paths (load from theme/acf-json, save to theme/acf-json)
 */
add_filter('acf/settings/load_json', function($paths) {
    // Keep default load paths, then add our theme path
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!in_array($theme_path, $paths, true)) {
        $paths[] = $theme_path;
    }
    return $paths;
});

add_filter('acf/settings/save_json', function($path) {
    $theme_path = get_stylesheet_directory() . '/acf-json';
    if (!file_exists($theme_path)) {
        // Create directory if it doesn't exist
        if (function_exists('wp_mkdir_p')) {
            wp_mkdir_p($theme_path);
        } else {
            @mkdir($theme_path, 0755, true);
        }
    }
    return $theme_path;
});

/**
 * Register ACF groups from our bundled JSON definition file.
 */
add_action('acf/init', function() {
    $json_file = get_stylesheet_directory() . '/acf-fields.json';
    if (!file_exists($json_file)) {
        return;
    }

    $raw = file_get_contents($json_file);
    if ($raw === false) {
        return;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return;
    }

    $groups = $data['groups'] ?? [];
    foreach ($groups as $group) {
        // Minimal required keys: key, title, fields, location
        if (empty($group['key']) || empty($group['title']) || empty($group['fields']) || empty($group['location'])) {
            continue;
        }

        // Normalize fields array: ensure each field has required keys
        $fields = [];
        foreach ((array)$group['fields'] as $f) {
            if (empty($f['key']) || empty($f['name']) || empty($f['type'])) {
                continue;
            }
            // Pass through common ACF args; provide safe defaults
            $field = [
                'key'               => $f['key'],
                'label'             => $f['label'] ?? $f['name'],
                'name'              => $f['name'],
                'type'              => $f['type'],
                'instructions'      => $f['instructions'] ?? '',
                'required'          => (int)($f['required'] ?? 0),
                'conditional_logic' => $f['conditional_logic'] ?? 0,
                'wrapper'           => $f['wrapper'] ?? ['width' => '', 'class' => '', 'id' => ''],
            ];

            // Merge type-specific options if provided
            $merge_keys = [
                'choices','default_value','placeholder','prepend','append','min','max','step',
                'rows','new_lines','tabs','toolbar','media_upload','ui','return_format','multiple',
                'display_format','save_format','date_format','time_format','post_type','taxonomy'
            ];
            foreach ($merge_keys as $mk) {
                if (array_key_exists($mk, $f)) {
                    $field[$mk] = $f[$mk];
                }
            }

            $fields[] = $field;
        }

        if (empty($fields)) {
            continue;
        }

        $args = [
            'key'                   => $group['key'],
            'title'                 => $group['title'],
            'fields'                => $fields,
            'location'              => $group['location'],
            'menu_order'            => (int)($group['menu_order'] ?? 0),
            'position'              => $group['position'] ?? 'normal',
            'style'                 => $group['style'] ?? 'default',
            'label_placement'       => $group['label_placement'] ?? 'top',
            'instruction_placement' => $group['instruction_placement'] ?? 'label',
            'hide_on_screen'        => $group['hide_on_screen'] ?? '',
            'active'                => isset($group['active']) ? (bool)$group['active'] : true,
            'description'           => $group['description'] ?? '',
            'show_in_rest'          => isset($group['show_in_rest']) ? (bool)$group['show_in_rest'] : true,
        ];

        // Finally register as local field group
        acf_add_local_field_group($args);
    }
});
