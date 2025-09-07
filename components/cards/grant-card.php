<?php
/**
 * Grant Card Component
 * 
 * 助成金情報を表示する再利用可能なカードコンポーネント
 * 
 * @param array $args {
 *     @type int    $post_id     投稿ID
 *     @type string $size        カードサイズ ('small', 'medium', 'large')
 *     @type bool   $show_excerpt 概要を表示するか
 *     @type string $link_target  リンクターゲット ('_self', '_blank')
 *     @type string $css_class    追加CSSクラス
 * }
 */

// デフォルト値の設定
$defaults = [
    'post_id' => get_the_ID(),
    'size' => 'medium',
    'show_excerpt' => true,
    'link_target' => '_self',
    'css_class' => ''
];

$args = wp_parse_args($args ?? [], $defaults);
extract($args);

// 投稿データの取得
$post = get_post($post_id);
if (!$post) return;

$title = get_the_title($post_id);
$permalink = get_permalink($post_id);
$thumbnail = get_the_post_thumbnail($post_id, 'gi-card-thumb', ['class' => 'w-full h-48 object-cover']);

// ACFフィールドの取得
$deadline = get_field('deadline', $post_id);
$amount = get_field('amount', $post_id);
$prefecture = get_field('prefecture', $post_id);
$category = get_field('category', $post_id);

// サイズ別のCSSクラス
$size_classes = [
    'small' => 'max-w-sm',
    'medium' => 'max-w-md',
    'large' => 'max-w-lg'
];

$card_class = $size_classes[$size] ?? $size_classes['medium'];
?>

<article class="grant-card <?php echo esc_attr($card_class . ' ' . $css_class); ?> bg-white rounded-xl shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden group">
    <?php if ($thumbnail): ?>
        <div class="card-thumbnail relative overflow-hidden">
            <a href="<?php echo esc_url($permalink); ?>" target="<?php echo esc_attr($link_target); ?>">
                <?php echo $thumbnail; ?>
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </a>
        </div>
    <?php endif; ?>

    <div class="card-content p-6">
        <!-- カテゴリーと都道府県 -->
        <div class="card-meta flex flex-wrap gap-2 mb-3">
            <?php if ($category): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                    <i class="fas fa-tag mr-1"></i>
                    <?php echo esc_html($category); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($prefecture): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    <?php echo esc_html($prefecture); ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- タイトル -->
        <h3 class="card-title mb-3">
            <a href="<?php echo esc_url($permalink); ?>" 
               target="<?php echo esc_attr($link_target); ?>"
               class="text-lg font-bold text-gray-900 hover:text-emerald-600 transition-colors duration-200 line-clamp-2">
                <?php echo esc_html($title); ?>
            </a>
        </h3>

        <!-- 概要 -->
        <?php if ($show_excerpt): ?>
            <div class="card-excerpt text-gray-600 text-sm mb-4 line-clamp-3">
                <?php echo wp_trim_words(get_the_excerpt($post_id), 20, '...'); ?>
            </div>
        <?php endif; ?>

        <!-- 助成金情報 -->
        <div class="card-info grid grid-cols-2 gap-4 mb-4 text-sm">
            <?php if ($amount): ?>
                <div class="info-item">
                    <span class="block text-gray-500 text-xs mb-1">助成額</span>
                    <span class="font-semibold text-emerald-600">
                        <i class="fas fa-yen-sign mr-1"></i>
                        <?php echo esc_html(number_format($amount)); ?>円
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($deadline): ?>
                <div class="info-item">
                    <span class="block text-gray-500 text-xs mb-1">締切</span>
                    <span class="font-semibold text-red-600">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo esc_html(date('n/j', strtotime($deadline))); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- アクションボタン -->
        <div class="card-actions">
            <a href="<?php echo esc_url($permalink); ?>" 
               target="<?php echo esc_attr($link_target); ?>"
               class="btn btn-primary w-full text-center">
                詳細を見る
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</article>

