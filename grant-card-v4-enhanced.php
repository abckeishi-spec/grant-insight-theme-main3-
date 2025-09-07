<?php
/**
 * Grant Card v4 Enhanced - 助成金カード強化版（Tailwind CSS対応）
 * マイクロインタラクション、プログレッシブディスクロージャー、コンバージョン最適化機能付き
 * 
 * @package Grant_Insight_Perfect
 * @version 4.0-enhanced
 */

// 必要なヘルパー関数
if (!function_exists('gi_safe_get_meta')) {
    function gi_safe_get_meta($post_id, $key, $default = '') {
        $value = get_post_meta($post_id, $key, true);
        return !empty($value) ? $value : $default;
    }
}

// カード用データ取得
$grant_id = get_the_ID();
$grant_amount = gi_safe_get_meta($grant_id, 'grant_amount', 0);
$grant_deadline = gi_safe_get_meta($grant_id, 'grant_deadline');
$grant_target = gi_safe_get_meta($grant_id, 'grant_target', '中小企業');
$grant_rate = gi_safe_get_meta($grant_id, 'grant_rate', '2/3');
$grant_difficulty = gi_safe_get_meta($grant_id, 'grant_difficulty', 'normal');
$grant_success_rate = gi_safe_get_meta($grant_id, 'grant_success_rate', 65);
$is_featured = gi_safe_get_meta($grant_id, 'is_featured', false);
$views_count = gi_safe_get_meta($grant_id, 'views_count', mt_rand(100, 500));

// 都道府県情報
$prefectures = wp_get_post_terms($grant_id, 'grant_prefecture', array('fields' => 'names'));
$categories = wp_get_post_terms($grant_id, 'grant_category', array('fields' => 'names'));

// 締切までの日数計算
$days_remaining = 0;
if ($grant_deadline) {
    $deadline_timestamp = strtotime($grant_deadline);
    $days_remaining = ceil(($deadline_timestamp - time()) / (60 * 60 * 24));
}

// 難易度表示設定
$difficulty_config = array(
    'easy' => array('label' => '易しい', 'color' => 'green', 'stars' => 1),
    'normal' => array('label' => '普通', 'color' => 'blue', 'stars' => 2),
    'hard' => array('label' => '難しい', 'color' => 'orange', 'stars' => 3),
    'expert' => array('label' => '専門的', 'color' => 'red', 'stars' => 4)
);
$difficulty_info = $difficulty_config[$grant_difficulty] ?? $difficulty_config['normal'];
?>

<article class="grant-card-enhanced group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transform transition-all duration-500 hover:-translate-y-1 overflow-hidden" data-grant-id="<?php echo $grant_id; ?>">
    
    <!-- Featured Badge -->
    <?php if ($is_featured): ?>
    <div class="absolute top-0 right-0 z-10">
        <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold py-2 px-4 rounded-bl-2xl shadow-lg">
            <i class="fas fa-star mr-1"></i>注目
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Card Header with Progress Indicator -->
    <div class="relative">
        <!-- Background Gradient -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-10 group-hover:opacity-20 transition-opacity duration-500"></div>
        
        <!-- Header Content -->
        <div class="relative p-6 pb-4">
            <!-- Category & Prefecture Tags -->
            <div class="flex flex-wrap gap-2 mb-3">
                <?php if (!empty($categories)): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                    <i class="fas fa-folder mr-1"></i><?php echo esc_html($categories[0]); ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($prefectures)): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <i class="fas fa-map-marker-alt mr-1"></i><?php echo esc_html($prefectures[0]); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Title with hover effect -->
            <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors duration-300">
                <?php the_title(); ?>
            </h3>
            
            <!-- Success Rate & Views -->
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div class="flex items-center gap-3">
                    <span class="flex items-center">
                        <i class="fas fa-chart-line text-green-500 mr-1"></i>
                        採択率 <strong class="text-green-600 ml-1"><?php echo $grant_success_rate; ?>%</strong>
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-eye text-gray-400 mr-1"></i>
                        <?php echo number_format($views_count); ?>回閲覧
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Deadline Progress Bar -->
        <?php if ($grant_deadline && $days_remaining > 0): ?>
        <div class="px-6 pb-4">
            <div class="relative">
                <?php 
                $progress_percentage = max(0, min(100, (30 - $days_remaining) / 30 * 100));
                $progress_color = $days_remaining <= 7 ? 'red' : ($days_remaining <= 14 ? 'yellow' : 'green');
                ?>
                <div class="flex justify-between items-center mb-1 text-xs">
                    <span class="text-gray-600">申請期限</span>
                    <span class="font-bold text-<?php echo $progress_color; ?>-600">
                        残り<?php echo $days_remaining; ?>日
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-<?php echo $progress_color; ?>-400 to-<?php echo $progress_color; ?>-600 h-2 rounded-full transition-all duration-500" 
                         style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Card Body with Key Information -->
    <div class="px-6 pb-4">
        <!-- Amount Display with Animation -->
        <div class="mb-4 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-200">
            <div class="text-sm text-gray-600 mb-1">最大支援額</div>
            <div class="flex items-baseline">
                <span class="text-3xl font-bold text-emerald-600">
                    <?php if ($grant_amount > 0): ?>
                        <?php echo number_format($grant_amount / 10000); ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </span>
                <?php if ($grant_amount > 0): ?>
                <span class="text-lg text-emerald-600 ml-1">万円</span>
                <?php endif; ?>
                <?php if ($grant_rate): ?>
                <span class="ml-3 text-sm text-gray-600">
                    (補助率: <strong><?php echo esc_html($grant_rate); ?></strong>)
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Difficulty Level Display -->
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">申請難易度</span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <i class="fas fa-star text-<?php echo $i <= $difficulty_info['stars'] ? $difficulty_info['color'] : 'gray'; ?>-400"></i>
                    <?php endfor; ?>
                    <span class="ml-2 text-sm font-medium text-<?php echo $difficulty_info['color']; ?>-600">
                        <?php echo $difficulty_info['label']; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Target Info -->
        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-600 mb-1">対象事業者</div>
            <div class="text-sm font-medium text-gray-800"><?php echo esc_html($grant_target); ?></div>
        </div>
        
        <!-- Quick Benefits List (Progressive Disclosure) -->
        <div class="card-benefits mb-4" style="max-height: 0; overflow: hidden; transition: max-height 0.5s ease;">
            <div class="space-y-2 pt-3 border-t border-gray-200">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span class="text-sm text-gray-700">返済不要の資金支援</span>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span class="text-sm text-gray-700">専門家による申請サポート可</span>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span class="text-sm text-gray-700">オンライン申請対応</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Actions -->
    <div class="px-6 pb-6">
        <div class="flex gap-3">
            <!-- Primary CTA -->
            <a href="<?php the_permalink(); ?>" 
               class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-xl">
                <span>詳細を見る</span>
                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
            </a>
            
            <!-- Secondary Actions -->
            <button type="button" 
                    class="favorite-btn p-3 bg-gray-100 hover:bg-red-100 rounded-lg transition-all duration-300 group/fav"
                    data-grant-id="<?php echo $grant_id; ?>">
                <i class="far fa-heart text-gray-600 group-hover/fav:text-red-500 transition-colors duration-300"></i>
            </button>
            
            <button type="button" 
                    class="share-btn p-3 bg-gray-100 hover:bg-blue-100 rounded-lg transition-all duration-300 group/share"
                    data-grant-id="<?php echo $grant_id; ?>">
                <i class="fas fa-share-alt text-gray-600 group-hover/share:text-blue-500 transition-colors duration-300"></i>
            </button>
        </div>
        
        <!-- Quick Apply Button (Appears on Hover) -->
        <div class="mt-3 quick-apply-section opacity-0 max-h-0 overflow-hidden transition-all duration-500 group-hover:opacity-100 group-hover:max-h-20">
            <button class="w-full py-2 bg-emerald-500 text-white font-medium rounded-lg hover:bg-emerald-600 transition-colors duration-300">
                <i class="fas fa-rocket mr-2"></i>今すぐ申請を開始
            </button>
        </div>
    </div>
    
    <!-- Hover Effect Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
</article>

<!-- Card-specific JavaScript for Microinteractions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Progressive disclosure on hover
    const cards = document.querySelectorAll('.grant-card-enhanced');
    
    cards.forEach(card => {
        const benefits = card.querySelector('.card-benefits');
        const quickApply = card.querySelector('.quick-apply-section');
        
        card.addEventListener('mouseenter', function() {
            if (benefits) {
                benefits.style.maxHeight = benefits.scrollHeight + 'px';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (benefits) {
                benefits.style.maxHeight = '0';
            }
        });
        
        // Favorite button interaction
        const favoriteBtn = card.querySelector('.favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'text-red-500');
                    
                    // Animation effect
                    this.classList.add('scale-125');
                    setTimeout(() => this.classList.remove('scale-125'), 300);
                } else {
                    icon.classList.remove('fas', 'text-red-500');
                    icon.classList.add('far');
                }
            });
        }
        
        // Share button interaction
        const shareBtn = card.querySelector('.share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Share functionality
                if (navigator.share) {
                    navigator.share({
                        title: card.querySelector('h3').textContent,
                        url: card.querySelector('a').href
                    });
                }
            });
        }
    });
});
</script>