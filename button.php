<?php
/**
 * Button Component
 * 
 * 再利用可能なボタンコンポーネント
 * 
 * @param array $args {
 *     @type string $text        ボタンテキスト
 *     @type string $url         リンクURL
 *     @type string $type        ボタンタイプ ('primary', 'secondary', 'danger', 'outline')
 *     @type string $size        サイズ ('sm', 'md', 'lg')
 *     @type string $icon        アイコンクラス
 *     @type string $target      リンクターゲット
 *     @type string $css_class   追加CSSクラス
 *     @type bool   $disabled    無効化フラグ
 * }
 */

// デフォルト値の設定
$defaults = [
    'text' => 'ボタン',
    'url' => '#',
    'type' => 'primary',
    'size' => 'md',
    'icon' => '',
    'target' => '_self',
    'css_class' => '',
    'disabled' => false
];

$args = wp_parse_args($args ?? [], $defaults);
extract($args);

// タイプ別のCSSクラス
$type_classes = [
    'primary' => 'bg-emerald-500 hover:bg-emerald-600 text-white',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
    'danger' => 'bg-red-500 hover:bg-red-600 text-white',
    'outline' => 'border-2 border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white bg-transparent'
];

// サイズ別のCSSクラス
$size_classes = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-6 py-3 text-base',
    'lg' => 'px-8 py-4 text-lg'
];

$button_class = 'btn inline-flex items-center justify-center font-bold rounded-lg transition-all duration-300 ' . 
                ($type_classes[$type] ?? $type_classes['primary']) . ' ' .
                ($size_classes[$size] ?? $size_classes['md']) . ' ' .
                $css_class;

if ($disabled) {
    $button_class .= ' opacity-50 cursor-not-allowed';
}
?>

<a href="<?php echo esc_url($url); ?>" 
   target="<?php echo esc_attr($target); ?>"
   class="<?php echo esc_attr($button_class); ?>"
   <?php if ($disabled): ?>aria-disabled="true"<?php endif; ?>>
    
    <?php if (!empty($icon)): ?>
        <i class="<?php echo esc_attr($icon); ?> mr-2"></i>
    <?php endif; ?>
    
    <?php echo esc_html($text); ?>
</a>

