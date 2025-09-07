<?php
/**
 * Grant Card v4 - 助成金カード（Tailwind CSS Play CDN対応版）
 * 
 * Tailwind CSS Play CDNを使用してスタンドアロンで動作する完全版
 * 安全な数値変換とエラーハンドリングを実装
 */

// 必要なヘルパー関数を定義
if (!function_exists('gi_safe_get_meta')) {
    function gi_safe_get_meta($post_id, $key, $default = '') {
        $value = get_post_meta($post_id, $key, true);
        return !empty($value) ? $value : $default;
    }
}

if (!function_exists('gi_safe_attr')) {
    function gi_safe_attr($value) {
        return esc_attr($value);
    }
}

if (!function_exists('gi_safe_escape')) {
    function gi_safe_escape($value) {
        return esc_html($value);
    }
}

if (!function_exists('gi_safe_number_format')) {
    function gi_safe_number_format($value) {
        $num = floatval($value);
        return number_format($num);
    }
}

if (!function_exists('gi_safe_date_format')) {
    function gi_safe_date_format($date, $format = 'Y-m-d') {
        if (empty($date)) return '';
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }
}

if (!function_exists('gi_safe_percent_format')) {
    function gi_safe_percent_format($value) {
        $num = floatval($value);
        return number_format($num, 1) . '%';
    }
}

// 必要なデータを安全に取得
$grant_id = get_the_ID();
if (!$grant_id) {
    return; // IDが取得できない場合は表示しない
}

// カスタムフィールドを安全に取得
$max_amount = gi_safe_get_meta($grant_id, 'max_amount', '');
$organization = gi_safe_get_meta($grant_id, 'organization', '');
$region = gi_safe_get_meta($grant_id, 'region', '');
$status = gi_safe_get_meta($grant_id, 'status', 'active');
$deadline = gi_safe_get_meta($grant_id, 'deadline', '');
$difficulty = gi_safe_get_meta($grant_id, 'difficulty', '');
$success_rate = gi_safe_get_meta($grant_id, 'success_rate', '');
$target_industry = gi_safe_get_meta($grant_id, 'target_industry', '');
$purpose = gi_safe_get_meta($grant_id, 'purpose', '');

// 難易度に応じた色クラスを設定
$difficulty_color_map = [
    '低' => 'text-green-600',
    '中' => 'text-yellow-600', 
    '高' => 'text-red-600'
];
$difficulty_color = isset($difficulty_color_map[$difficulty]) ? $difficulty_color_map[$difficulty] : 'text-slate-900';

// カテゴリとタグを安全に取得
$categories = get_the_terms($grant_id, 'grant_category');
$tags = get_the_terms($grant_id, 'grant_tag');

// エラーチェック
if (is_wp_error($categories)) $categories = false;
if (is_wp_error($tags)) $tags = false;

// 投稿タイトルを安全に取得
$post_title = get_the_title($grant_id);
if (empty($post_title)) {
    $post_title = '助成金情報';
}
?>

<!-- Tailwind CSS Play CDNの読み込み（ページのhead部分に配置） -->
<?php if (!wp_script_is('tailwind-cdn', 'enqueued')): ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                animation: {
                    'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                }
            }
        }
    }
</script>
<?php endif; ?>

<article class="grant-card max-w-md mx-auto bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
         data-grant-id="<?php echo gi_safe_attr($grant_id); ?>"
         data-status="<?php echo gi_safe_attr($status); ?>"
         data-amount="<?php echo gi_safe_attr($max_amount); ?>"
         data-difficulty="<?php echo gi_safe_attr($difficulty); ?>"
         itemscope
         itemtype="https://schema.org/GovernmentService">

    <!-- ヘッダー（グラデーション背景 + ステータス/地域 + お気に入り + 金額） -->
    <header class="relative bg-gradient-to-br from-indigo-600 to-blue-600 text-white p-5">
        <div class="flex items-start justify-between">
            <!-- ステータス/地域バッジ -->
            <div class="flex flex-wrap items-center gap-2">
                <?php if ($status === 'active') : ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-white/10 ring-1 ring-white/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    募集中
                </span>
                <?php elseif ($status === 'upcoming') : ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-white/10 ring-1 ring-white/20">
                    <span class="w-2 h-2 rounded-full bg-amber-300"></span>
                    募集予定
                </span>
                <?php else : ?>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-white/10 ring-1 ring-white/20">
                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                    募集終了
                </span>
                <?php endif; ?>

                <?php if (!empty($region)) : ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-white/10 ring-1 ring-white/20">
                    <span>📍</span>
                    <?php echo gi_safe_escape($region); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- お気に入り -->
            <button class="favorite-btn inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 ring-1 ring-white/30 transition-all duration-200"
                    onclick="toggleFavorite(<?php echo gi_safe_attr($grant_id); ?>)"
                    aria-label="お気に入りに追加">
                <span class="favorite-icon text-lg transition-transform duration-200 hover:scale-110">♡</span>
            </button>
        </div>

        <!-- 金額表示 -->
        <div class="mt-4 text-center">
            <span class="block text-white/90 text-xs font-medium">上限金額</span>
            <span class="mt-1 inline-flex items-end gap-1 text-3xl font-bold" itemprop="amount">
                <?php if (!empty($max_amount)) : ?>
                    <?php echo gi_safe_number_format($max_amount); ?>
                    <span class="text-lg font-semibold">万円</span>
                <?php else : ?>
                    <span class="text-base font-medium text-white/80">要確認</span>
                <?php endif; ?>
            </span>
        </div>
    </header>

    <!-- ボディ -->
    <div class="p-5">
        <!-- タイトル/組織 -->
        <div>
            <h3 class="text-lg font-semibold text-slate-900 leading-snug">
                <a href="<?php echo esc_url(get_permalink($grant_id)); ?>" 
                   class="hover:text-blue-600 hover:underline decoration-2 underline-offset-2 transition-colors duration-200 grant-link">
                    <?php echo gi_safe_escape($post_title); ?>
                </a>
            </h3>

            <?php if (!empty($organization)) : ?>
            <div class="mt-2 flex items-center gap-2 text-sm text-slate-600" itemprop="provider">
                <span class="org-icon">🏢</span>
                <span class="org-name"><?php echo gi_safe_escape($organization); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- メタ情報 -->
        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <?php if (!empty($deadline)) : ?>
            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors duration-200">
                <span class="text-base">⏰</span>
                <div>
                    <div class="text-xs text-slate-500 font-medium">締切</div>
                    <div class="font-semibold text-slate-900"><?php echo gi_safe_date_format($deadline, 'm/d'); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($difficulty)) : ?>
            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors duration-200">
                <span class="text-base">📊</span>
                <div>
                    <div class="text-xs text-slate-500 font-medium">難易度</div>
                    <div class="font-semibold <?php echo gi_safe_attr($difficulty_color); ?>">
                        <?php echo gi_safe_escape($difficulty); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_rate)) : ?>
            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors duration-200">
                <span class="text-base">📈</span>
                <div>
                    <div class="text-xs text-slate-500 font-medium">成功率</div>
                    <div class="font-semibold text-emerald-600"><?php echo gi_safe_percent_format($success_rate); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($target_industry)) : ?>
            <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors duration-200">
                <span class="text-base">🏭</span>
                <div>
                    <div class="text-xs text-slate-500 font-medium">対象</div>
                    <div class="font-semibold text-slate-900"><?php echo gi_safe_escape($target_industry); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 用途・目的 -->
        <?php if (!empty($purpose)) : ?>
        <div class="mt-5">
            <div class="text-xs font-semibold text-slate-500 mb-2">用途・目的</div>
            <div class="text-sm text-slate-700 leading-relaxed bg-blue-50 p-3 rounded-lg">
                <?php echo gi_safe_escape($purpose); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- カテゴリ/タグ -->
        <?php if (!empty($categories) || !empty($tags)) : ?>
        <div class="mt-5 flex flex-wrap items-center gap-2">
            <?php if (!empty($categories)) : ?>
                <?php foreach ($categories as $category) : ?>
                    <?php if (!is_wp_error($category) && isset($category->name)) : ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-100 transition-colors duration-200">
                        <?php echo gi_safe_escape($category->name); ?>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($tags)) : ?>
                <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                    <?php if (!is_wp_error($tag) && isset($tag->name)) : ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors duration-200">
                        #<?php echo gi_safe_escape($tag->name); ?>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- フッター -->
    <footer class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?php echo esc_url(get_permalink($grant_id)); ?>"
               class="btn btn-primary btn-details inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md">
                詳細を見る
            </a>

            <button class="btn btn-secondary btn-bookmark inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-all duration-200 shadow-sm"
                    onclick="bookmarkGrant(<?php echo gi_safe_attr($grant_id); ?>)"
                    aria-label="ブックマークに追加">
                <span class="bookmark-icon mr-1">📋</span>
                保存
            </button>
        </div>

        <!-- 更新日 -->
        <div class="update-info text-xs text-slate-500 font-medium">
            <span class="update-label mr-1">更新:</span>
            <time datetime="<?php echo get_the_modified_date('c', $grant_id); ?>" 
                  class="text-slate-600">
                <?php echo get_the_modified_date('n/j', $grant_id); ?>
            </time>
        </div>
    </footer>
</article>

<!-- JavaScript関数 -->
<script>
// お気に入り機能
function toggleFavorite(grantId) {
    const btn = event.target.closest('.favorite-btn');
    const icon = btn.querySelector('.favorite-icon');
    
    // アニメーション効果
    btn.style.transform = 'scale(0.95)';
    setTimeout(() => {
        btn.style.transform = 'scale(1)';
    }, 100);
    
    // アイコンの切り替え
    if (icon.textContent === '♡') {
        icon.textContent = '♥';
        icon.style.color = '#ef4444';
        console.log(`助成金 ${grantId} をお気に入りに追加しました`);
    } else {
        icon.textContent = '♡';
        icon.style.color = '';
        console.log(`助成金 ${grantId} をお気に入りから削除しました`);
    }
}

// ブックマーク機能
function bookmarkGrant(grantId) {
    const btn = event.target.closest('.btn-bookmark');
    const icon = btn.querySelector('.bookmark-icon');
    
    // アニメーション効果
    btn.style.transform = 'scale(0.95)';
    setTimeout(() => {
        btn.style.transform = 'scale(1)';
    }, 100);
    
    // アイコンと状態の切り替え
    if (icon.textContent === '📋') {
        icon.textContent = '✅';
        btn.querySelector('span:last-child').textContent = '保存済み';
        btn.classList.add('bg-green-50', 'text-green-700', 'border-green-300');
        btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-300');
        console.log(`助成金 ${grantId} をブックマークに追加しました`);
    } else {
        icon.textContent = '📋';
        btn.querySelector('span:last-child').textContent = '保存';
        btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-300');
        btn.classList.add('bg-white', 'text-slate-700', 'border-slate-300');
        console.log(`助成金 ${grantId} をブックマークから削除しました`);
    }
}

// カードホバー効果の強化
document.addEventListener('DOMContentLoaded', function() {
    const grantCards = document.querySelectorAll('.grant-card');
    
    grantCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<!-- JSON-LD構造化データ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "GovernmentService",
    "name": "<?php echo gi_safe_escape($post_title); ?>",
    "provider": {
        "@type": "Organization",
        "name": "<?php echo gi_safe_escape($organization); ?>"
    },
    "serviceType": "助成金",
    "areaServed": "<?php echo gi_safe_escape($region); ?>",
    "url": "<?php echo esc_url(get_permalink($grant_id)); ?>",
    "offers": {
        "@type": "Offer",
        "price": "<?php echo gi_safe_escape($max_amount); ?>",
        "priceCurrency": "JPY"
    }
}
</script>