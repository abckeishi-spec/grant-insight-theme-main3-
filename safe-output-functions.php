<?php
/**
 * 安全な出力関数
 * 
 * XSS対策のための出力エスケープ関数を提供します。
 * これらの関数が未定義の場合のフォールバックとして機能します。
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 安全なHTML出力エスケープ
 * 
 * @param string $text テキスト
 * @return string エスケープされたテキスト
 */
if (!function_exists('gi_safe_escape')) {
    function gi_safe_escape($text) {
        return esc_html($text);
    }
}

/**
 * 安全なURL出力
 * 
 * @param string $url URL
 * @return string エスケープされたURL
 */
if (!function_exists('gi_safe_url')) {
    function gi_safe_url($url) {
        return esc_url($url);
    }
}

/**
 * 安全な属性出力
 * 
 * @param string $text 属性値
 * @return string エスケープされた属性値
 */
if (!function_exists('gi_safe_attr')) {
    function gi_safe_attr($text) {
        return esc_attr($text);
    }
}

/**
 * 安全な抜粋取得
 * 
 * @param string $text テキスト
 * @param int $length 長さ
 * @param string $more 続き文字
 * @return string サニタイズされた抜粋
 */
if (!function_exists('gi_safe_excerpt')) {
    function gi_safe_excerpt($text, $length = 150, $more = '...') {
        try {
            $text = wp_strip_all_tags($text);
            $text = sanitize_text_field($text);
            
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            
            $excerpt = mb_substr($text, 0, $length);
            return $excerpt . $more;
            
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('gi_safe_excerpt error: ' . $e->getMessage());
            }
            return '';
        }
    }
}

/**
 * 安全なメタデータ取得
 * 
 * @param int $post_id 投稿ID
 * @param string $key メタキー
 * @param mixed $default デフォルト値
 * @return mixed サニタイズされた値
 */
if (!function_exists('gi_safe_get_meta')) {
    function gi_safe_get_meta($post_id, $key, $default = '') {
        try {
            $post_id = absint($post_id);
            if ($post_id <= 0) {
                return $default;
            }
            
            $key = sanitize_key($key);
            if (empty($key)) {
                return $default;
            }
            
            $value = get_post_meta($post_id, $key, true);
            
            // ACFフィールドのフォールバック
            if (empty($value) && function_exists('get_field')) {
                $value = get_field($key, $post_id);
            }
            
            if (empty($value)) {
                return $default;
            }
            
            // 型に応じたサニタイズ
            if (is_array($value)) {
                return array_map('sanitize_text_field', $value);
            } elseif (is_numeric($value)) {
                return $value;
            } else {
                return sanitize_text_field($value);
            }
            
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('gi_safe_get_meta error: ' . $e->getMessage());
            }
            return $default;
        }
    }
}

/**
 * 安全な数値フォーマット
 * 
 * @param mixed $number 数値
 * @param int $decimals 小数点以下の桁数
 * @return string フォーマットされた数値
 */
if (!function_exists('gi_safe_number_format')) {
    function gi_safe_number_format($number, $decimals = 0) {
        try {
            $number = is_numeric($number) ? floatval($number) : 0;
            return number_format($number, $decimals);
        } catch (Exception $e) {
            if (WP_DEBUG_LOG) {
                error_log('gi_safe_number_format error: ' . $e->getMessage());
            }
            return '0';
        }
    }
}

/**
 * 安全なJavaScript出力
 * 
 * @param mixed $data データ
 * @return string エスケープされたJSONデータ
 */
if (!function_exists('gi_safe_json')) {
    function gi_safe_json($data) {
        return wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}

/**
 * 安全なtextarea出力
 * 
 * @param string $text テキスト
 * @return string エスケープされたテキスト
 */
if (!function_exists('gi_safe_textarea')) {
    function gi_safe_textarea($text) {
        return esc_textarea($text);
    }
}

/**
 * 安全なHTMLクラス出力
 * 
 * @param string|array $classes クラス名
 * @return string エスケープされたクラス名
 */
if (!function_exists('gi_safe_class')) {
    function gi_safe_class($classes) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }
        return esc_attr($classes);
    }
}

/**
 * 安全なID属性出力
 * 
 * @param string $id ID
 * @return string エスケープされたID
 */
if (!function_exists('gi_safe_id')) {
    function gi_safe_id($id) {
        return esc_attr(sanitize_html_class($id));
    }
}

/**
 * 安全なファイル名取得
 * 
 * @param string $filename ファイル名
 * @return string サニタイズされたファイル名
 */
if (!function_exists('gi_safe_filename')) {
    function gi_safe_filename($filename) {
        return sanitize_file_name($filename);
    }
}

/**
 * 安全なHTMLタグ付き出力（特定のタグのみ許可）
 * 
 * @param string $html HTML
 * @param array $allowed_tags 許可するタグ
 * @return string フィルタリングされたHTML
 */
if (!function_exists('gi_safe_html')) {
    function gi_safe_html($html, $allowed_tags = null) {
        if ($allowed_tags === null) {
            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'target' => array(),
                    'rel' => array(),
                    'class' => array(),
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                'b' => array(),
                'i' => array(),
                'u' => array(),
                'span' => array(
                    'class' => array(),
                    'style' => array(),
                ),
                'p' => array(
                    'class' => array(),
                ),
                'div' => array(
                    'class' => array(),
                    'id' => array(),
                ),
                'ul' => array(
                    'class' => array(),
                ),
                'ol' => array(
                    'class' => array(),
                ),
                'li' => array(
                    'class' => array(),
                ),
            );
        }
        return wp_kses($html, $allowed_tags);
    }
}

/**
 * 安全なスタイル属性出力
 * 
 * @param array $styles スタイル配列
 * @return string エスケープされたスタイル文字列
 */
if (!function_exists('gi_safe_style')) {
    function gi_safe_style($styles) {
        if (!is_array($styles)) {
            return '';
        }
        
        $safe_styles = array();
        $allowed_properties = array(
            'color', 'background-color', 'background', 'border', 'border-color',
            'border-width', 'border-radius', 'margin', 'padding', 'width',
            'height', 'max-width', 'max-height', 'min-width', 'min-height',
            'font-size', 'font-weight', 'text-align', 'display', 'visibility',
            'opacity', 'z-index', 'position', 'top', 'right', 'bottom', 'left'
        );
        
        foreach ($styles as $property => $value) {
            if (in_array($property, $allowed_properties, true)) {
                $safe_styles[] = esc_attr($property) . ':' . esc_attr($value);
            }
        }
        
        return implode(';', $safe_styles);
    }
}

/**
 * 安全なタイトル出力（文字数制限付き）
 * 
 * @param string $title タイトル
 * @param int $max_length 最大文字数
 * @return string エスケープされたタイトル
 */
if (!function_exists('gi_safe_title')) {
    function gi_safe_title($title, $max_length = 0) {
        $title = esc_html($title);
        
        if ($max_length > 0 && mb_strlen($title) > $max_length) {
            $title = mb_substr($title, 0, $max_length) . '...';
        }
        
        return $title;
    }
}