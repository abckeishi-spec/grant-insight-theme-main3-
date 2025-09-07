<?php
/**
 * Search Form Component
 * 
 * 助成金検索フォームコンポーネント
 * 
 * @param array $args {
 *     @type string $placeholder   プレースホルダーテキスト
 *     @type string $button_text   ボタンテキスト
 *     @type bool   $show_filters  フィルターを表示するか
 *     @type string $form_id       フォームID
 *     @type string $css_class     追加CSSクラス
 * }
 */

// デフォルト値の設定
$defaults = [
    'placeholder' => '助成金を検索...',
    'button_text' => '検索',
    'show_filters' => true,
    'form_id' => 'grant-search-form',
    'css_class' => ''
];

$args = wp_parse_args($args ?? [], $defaults);
extract($args);

// 都道府県一覧を取得
$prefectures = get_terms([
    'taxonomy' => 'prefecture',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

// カテゴリー一覧を取得
$categories = get_terms([
    'taxonomy' => 'grant_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);
?>

<form id="<?php echo esc_attr($form_id); ?>" 
      class="grant-search-form <?php echo esc_attr($css_class); ?>" 
      method="GET" 
      action="<?php echo esc_url(home_url('/grants/')); ?>">

    <!-- メイン検索バー -->
    <div class="search-main flex flex-col md:flex-row gap-4 mb-6">
        <div class="search-input flex-1">
            <div class="relative">
                <input type="text" 
                       name="search" 
                       id="search-input"
                       class="w-full px-4 py-3 pl-12 pr-4 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                       placeholder="<?php echo esc_attr($placeholder); ?>"
                       value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <div class="search-button">
            <?php gi_component('ui/button', [
                'text' => $button_text,
                'type' => 'primary',
                'size' => 'lg',
                'icon' => 'fas fa-search',
                'url' => '#',
                'css_class' => 'search-submit-btn'
            ]); ?>
        </div>
    </div>

    <?php if ($show_filters): ?>
    <!-- 詳細フィルター -->
    <div class="search-filters">
        <div class="filters-toggle mb-4">
            <button type="button" 
                    class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-medium"
                    onclick="toggleFilters()">
                <i class="fas fa-filter mr-2"></i>
                詳細検索
                <i class="fas fa-chevron-down ml-2 transform transition-transform" id="filter-chevron"></i>
            </button>
        </div>

        <div id="search-filters-panel" class="filters-panel hidden bg-gray-50 rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 都道府県 -->
                <div class="filter-group">
                    <label for="prefecture" class="block text-sm font-medium text-gray-700 mb-2">
                        都道府県
                    </label>
                    <select name="prefecture" 
                            id="prefecture"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">すべて</option>
                        <?php if (!is_wp_error($prefectures)): ?>
                            <?php foreach ($prefectures as $prefecture): ?>
                                <option value="<?php echo esc_attr($prefecture->slug); ?>"
                                        <?php selected($_GET['prefecture'] ?? '', $prefecture->slug); ?>>
                                    <?php echo esc_html($prefecture->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- カテゴリー -->
                <div class="filter-group">
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        カテゴリー
                    </label>
                    <select name="category" 
                            id="category"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">すべて</option>
                        <?php if (!is_wp_error($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>"
                                        <?php selected($_GET['category'] ?? '', $category->slug); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 助成額 -->
                <div class="filter-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        助成額（万円）
                    </label>
                    <div class="flex gap-2">
                        <input type="number" 
                               name="amount_min" 
                               placeholder="最小"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               value="<?php echo esc_attr($_GET['amount_min'] ?? ''); ?>">
                        <span class="flex items-center text-gray-500">〜</span>
                        <input type="number" 
                               name="amount_max" 
                               placeholder="最大"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               value="<?php echo esc_attr($_GET['amount_max'] ?? ''); ?>">
                    </div>
                </div>

                <!-- 締切日 -->
                <div class="filter-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        締切日
                    </label>
                    <div class="flex gap-2">
                        <input type="date" 
                               name="deadline_from"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               value="<?php echo esc_attr($_GET['deadline_from'] ?? ''); ?>">
                        <span class="flex items-center text-gray-500">〜</span>
                        <input type="date" 
                               name="deadline_to"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               value="<?php echo esc_attr($_GET['deadline_to'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- フィルターアクション -->
            <div class="filter-actions flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                <button type="button" 
                        class="text-gray-600 hover:text-gray-800"
                        onclick="clearFilters()">
                    <i class="fas fa-times mr-1"></i>
                    フィルターをクリア
                </button>
                
                <div class="flex gap-2">
                    <?php gi_component('ui/button', [
                        'text' => 'フィルターを適用',
                        'type' => 'primary',
                        'size' => 'md',
                        'icon' => 'fas fa-filter',
                        'url' => '#',
                        'css_class' => 'filter-apply-btn'
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</form>

<script>
function toggleFilters() {
    const panel = document.getElementById('search-filters-panel');
    const chevron = document.getElementById('filter-chevron');
    
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        panel.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}

function clearFilters() {
    const form = document.getElementById('<?php echo esc_js($form_id); ?>');
    const inputs = form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], select');
    
    inputs.forEach(input => {
        if (input.name !== 'search') {
            input.value = '';
        }
    });
}
</script>

