<?php
/**
 * Search Section Template - Particle Animation Enhanced Version
 * Grant Insight Perfect - パーティクルアニメーション強化版
 * 
 * ヒーローセクションと調和するパーティクル背景付きデザイン
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 都道府県データ
$prefectures = array(
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
);

// 必要なデータを取得
$search_stats = wp_cache_get('grant_search_stats', 'grant_insight');
if (false === $search_stats) {
    $search_stats = array(
        'total_grants' => wp_count_posts('grant')->publish ?? 0,
        'total_tools' => wp_count_posts('tool')->publish ?? 0,
        'total_cases' => wp_count_posts('case_study')->publish ?? 0,
        'total_guides' => wp_count_posts('guide')->publish ?? 0
    );
    wp_cache_set('grant_search_stats', $search_stats, 'grant_insight', 3600);
}

// カテゴリとタグの取得
$grant_categories = get_terms(array(
    'taxonomy' => 'grant_category',
    'hide_empty' => false,
    'number' => 20
));

$popular_tags = get_terms(array(
    'taxonomy' => 'post_tag',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 10
));

// エラーハンドリング
if (is_wp_error($grant_categories)) {
    $grant_categories = array();
}
if (is_wp_error($popular_tags)) {
    $popular_tags = array();
}

// nonce生成
$search_nonce = wp_create_nonce('grant_insight_search_nonce');
?>

<!-- Font Awesome & Particles.js -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- 検索セクション - パーティクルアニメーション強化版 -->
<section id="search-section" class="search-section py-24 bg-gradient-to-br from-white via-blue-50/30 to-emerald-50/30 relative overflow-hidden" role="search" aria-label="助成金検索セクション">
    
    <!-- パーティクル背景キャンバス (z-index: 0) -->
    <div id="particles-search" class="absolute inset-0 z-0"></div>
    
    <!-- 波状パターン装飾 (z-index: 5) -->
    <div class="absolute inset-0 z-5 opacity-20" aria-hidden="true">
        <svg class="absolute top-0 left-0 w-full h-full" viewBox="0 0 1200 800">
            <defs>
                <linearGradient id="wave-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#10b981;stop-opacity:0.3" />
                    <stop offset="50%" style="stop-color:#3b82f6;stop-opacity:0.2" />
                    <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.1" />
                </linearGradient>
            </defs>
            <path d="M0,200 Q300,100 600,200 T1200,200 L1200,0 L0,0 Z" fill="url(#wave-gradient)" class="animate-wave"/>
        </svg>
    </div>

    <!-- 六角形背景パターン (z-index: 5) -->
    <div class="absolute inset-0 z-5 hexagon-pattern opacity-30" aria-hidden="true"></div>

    <!-- ドットパターン (z-index: 5) -->
    <div class="absolute inset-0 z-5 dot-pattern opacity-10" aria-hidden="true"></div>

    <!-- フローティング装飾要素 (z-index: 10) -->
    <div class="absolute inset-0 pointer-events-none z-10" aria-hidden="true">
        <!-- 大きなグラデーション円 -->
        <div class="floating-circle absolute top-20 left-10 w-96 h-96 bg-gradient-to-br from-emerald-200/40 via-teal-200/30 to-blue-200/20 rounded-full blur-3xl animate-float-slow"></div>
        
        <!-- 小さなアクセント円 -->
        <div class="floating-accent absolute top-32 right-1/4 w-32 h-32 bg-gradient-to-r from-cyan-300/50 to-blue-300/50 rounded-full animate-pulse"></div>
        
        <!-- 右上のフローティング円 -->
        <div class="floating-element absolute bottom-20 right-10 w-40 h-40 bg-gradient-to-r from-blue-400/20 to-indigo-400/20 rounded-full blur-3xl animate-float-2"></div>
        
        <!-- 中央のフローティング円 -->
        <div class="floating-element absolute top-1/2 left-1/3 w-24 h-24 bg-gradient-to-r from-purple-400/20 to-pink-400/20 rounded-full blur-3xl animate-float-3"></div>
        
        <!-- 三角形装飾 -->
        <div class="triangle-decoration absolute top-40 right-40 w-16 h-16 opacity-30 animate-spin-very-slow"></div>
    </div>

    <!-- 回転リング装飾 (z-index: 10) -->
    <div class="absolute inset-0 flex items-center justify-center opacity-10 z-10" aria-hidden="true">
        <div class="absolute w-[600px] h-[600px] border-2 border-emerald-300 rounded-full animate-spin-slow ring-decoration"></div>
        <div class="absolute w-80 h-80 border border-teal-200 rounded-full animate-spin-reverse"></div>
        <div class="absolute w-64 h-64 border border-emerald-300 rounded-full animate-spin-slow-2"></div>
    </div>

    <div class="container mx-auto px-4 lg:px-8 relative z-20">
        <!-- セクションヘッダー -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-3 bg-white/90 backdrop-blur-sm text-emerald-700 px-6 py-3 rounded-full text-sm font-bold mb-8 shadow-xl border border-emerald-200 hover:shadow-2xl transition-all duration-300">
                <div class="relative">
                    <i class="fas fa-search animate-pulse text-emerald-500" aria-hidden="true"></i>
                    <div class="absolute -inset-2 bg-emerald-200 rounded-full opacity-30 animate-ping"></div>
                </div>
                <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent font-black">高精度検索</span>
                <span class="hidden sm:inline">システム</span>
            </div>

            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight mb-8">
                <span class="bg-gradient-to-r from-gray-800 via-emerald-700 to-teal-700 bg-clip-text text-transparent drop-shadow-sm">
                    助成金を見つけよう
                </span>
            </h2>
            <p class="text-lg md:text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto leading-relaxed font-medium">
                <i class="fas fa-database mr-2 text-emerald-500"></i>
                <?php echo number_format($search_stats['total_grants']); ?>件の助成金情報から、
                あなたのビジネスに最適な支援制度を見つけましょう
            </p>
        </div>

        <!-- 統計情報バー -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
            <?php
            $stats_items = array(
                array(
                    'count' => $search_stats['total_grants'],
                    'label' => '助成金',
                    'icon' => 'fas fa-coins',
                    'gradient' => 'from-emerald-500 to-emerald-600',
                    'progress' => min(100, ($search_stats['total_grants'] / 10))
                ),
                array(
                    'count' => $search_stats['total_tools'],
                    'label' => 'ツール',
                    'icon' => 'fas fa-tools',
                    'gradient' => 'from-blue-500 to-blue-600',
                    'progress' => min(100, ($search_stats['total_tools'] / 5) * 100)
                ),
                array(
                    'count' => $search_stats['total_cases'],
                    'label' => '成功事例',
                    'icon' => 'fas fa-chart-line',
                    'gradient' => 'from-purple-500 to-purple-600',
                    'progress' => min(100, ($search_stats['total_cases'] / 5) * 100)
                ),
                array(
                    'count' => $search_stats['total_guides'],
                    'label' => 'ガイド',
                    'icon' => 'fas fa-book-open',
                    'gradient' => 'from-orange-500 to-orange-600',
                    'progress' => min(100, ($search_stats['total_guides'] / 5) * 100)
                )
            );

            foreach ($stats_items as $index => $item): ?>
                <div class="stat-card bg-white/90 backdrop-blur-sm rounded-xl p-6 text-center shadow-lg border border-white/50 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r <?php echo esc_attr($item['gradient']); ?> rounded-lg flex items-center justify-center shadow-md">
                            <i class="<?php echo esc_attr($item['icon']); ?> text-white text-xl" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="text-3xl md:text-4xl font-black text-transparent bg-gradient-to-r <?php echo esc_attr($item['gradient']); ?> bg-clip-text mb-2 counter" 
                         data-target="<?php echo esc_attr(str_replace(',', '', $item['count'])); ?>"
                         data-suffix="">
                        0
                    </div>
                    <div class="text-sm md:text-base text-gray-600 font-semibold mb-3"><?php echo esc_html($item['label']); ?></div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r <?php echo esc_attr($item['gradient']); ?> h-2 rounded-full progress-bar transition-all duration-1500 ease-out" 
                             data-width="<?php echo esc_attr($item['progress']); ?>" 
                             style="width: 0%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- メイン検索フォーム -->
        <div class="max-w-6xl mx-auto mb-16">
            <form id="grant-search-form" class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl p-8 lg:p-12 border border-white/70" role="search" aria-label="助成金検索フォーム">
                <!-- 隠しフィールド -->
                <input type="hidden" id="search-nonce" value="<?php echo esc_attr($search_nonce); ?>">
                <input type="hidden" id="ajax-url" value="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">

                <!-- メインキーワード検索 -->
                <div class="mb-10">
                    <label for="search-keyword" class="block text-2xl font-black text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-search mr-3 text-emerald-500"></i>
                        キーワード検索
                    </label>
                    <div class="relative group">
                        <input 
                            type="text" 
                            id="search-keyword" 
                            name="keyword"
                            class="w-full p-6 pr-16 border-2 border-emerald-200 bg-white rounded-2xl focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 transition-all duration-300 text-xl text-gray-800 placeholder-gray-400 font-medium shadow-lg group-hover:shadow-xl"
                            placeholder="例：IT導入補助金、デジタル化支援、中小企業向け支援..."
                            autocomplete="off"
                            aria-describedby="search-keyword-help"
                        >
                        <div class="absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-400 text-2xl group-hover:text-emerald-500 transition-colors duration-300">
                            <i class="fas fa-search"></i>
                        </div>
                        <div id="search-keyword-help" class="sr-only">助成金や支援制度に関するキーワードを入力してください</div>
                    </div>
                </div>

                <!-- フィルターオプション -->
                <div class="search-filters border-t border-gray-200 pt-8 mt-8">
                    <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-filter mr-3 text-blue-500"></i>
                        詳細フィルター
                    </h3>
                    
                    <div id="filter-content" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 bg-slate-50/80 backdrop-blur-sm p-8 rounded-2xl border border-slate-200/50">
                        <!-- カテゴリ選択 -->
                        <div class="group">
                            <label for="search-category" class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-emerald-600 transition-colors duration-200">
                                <i class="fas fa-folder mr-2 text-emerald-500"></i>
                                カテゴリ
                            </label>
                            <select 
                                id="search-category" 
                                name="category"
                                class="w-full px-4 py-4 border-2 border-slate-200 bg-white rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-200 text-gray-800 font-medium shadow-sm hover:shadow-md"
                                aria-label="助成金カテゴリを選択"
                            >
                                <option value="">すべてのカテゴリ</option>
                                <?php if (!empty($grant_categories)): ?>
                                    <?php foreach ($grant_categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>">
                                            <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- 投稿タイプ選択 -->
                        <div class="group">
                            <label for="search-post-type" class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-blue-600 transition-colors duration-200">
                                <i class="fas fa-list mr-2 text-blue-500"></i>
                                種類
                            </label>
                            <select 
                                id="search-post-type" 
                                name="post_type"
                                class="w-full px-4 py-4 border-2 border-slate-200 bg-white rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-gray-800 font-medium shadow-sm hover:shadow-md"
                                aria-label="投稿種類を選択"
                            >
                                <option value="">すべての種類</option>
                                <option value="grant">助成金</option>
                                <option value="tool">ツール</option>
                                <option value="case_study">成功事例</option>
                                <option value="guide">ガイド</option>
                            </select>
                        </div>

                        <!-- 都道府県選択 -->
                        <div class="group">
                            <label for="search-prefecture" class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-purple-600 transition-colors duration-200">
                                <i class="fas fa-map-marker-alt mr-2 text-purple-500"></i>
                                都道府県
                            </label>
                            <select 
                                id="search-prefecture" 
                                name="prefecture"
                                class="w-full px-4 py-4 border-2 border-slate-200 bg-white rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all duration-200 text-gray-800 font-medium shadow-sm hover:shadow-md"
                                aria-label="都道府県を選択"
                            >
                                <option value="">全国対象</option>
                                <?php foreach ($prefectures as $prefecture): ?>
                                    <option value="<?php echo esc_attr($prefecture); ?>">
                                        <?php echo esc_html($prefecture); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 並び順選択 -->
                        <div class="group">
                            <label for="search-orderby" class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-indigo-600 transition-colors duration-200">
                                <i class="fas fa-sort mr-2 text-indigo-500"></i>
                                並び順
                            </label>
                            <select 
                                id="search-orderby" 
                                name="orderby"
                                class="w-full px-4 py-4 border-2 border-slate-200 bg-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-gray-800 font-medium shadow-sm hover:shadow-md"
                                aria-label="結果の並び順を選択"
                            >
                                <option value="relevance">関連度順</option>
                                <option value="date">新着順</option>
                                <option value="title">タイトル順</option>
                                <option value="modified">更新順</option>
                                <option value="amount_desc">金額の高い順</option>
                                <option value="amount_asc">金額の安い順</option>
                                <option value="deadline">締切の近い順</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 高度な検索オプション -->
                <div id="advanced-search" class="border-t border-gray-200 pt-8 mt-8" style="display: none;">
                    <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-cog mr-3 text-orange-500"></i>
                        高度な検索オプション
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-8 bg-slate-50/80 backdrop-blur-sm p-8 rounded-2xl border border-slate-200/50">
                        <!-- 金額範囲 -->
                        <div class="group">
                            <label class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-green-600 transition-colors duration-200">
                                <i class="fas fa-yen-sign mr-2 text-green-500"></i>
                                助成金額範囲
                            </label>
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="number" 
                                    id="amount-min" 
                                    name="amount_min"
                                    class="flex-1 px-4 py-3 border-2 border-slate-200 bg-white rounded-xl focus:border-green-500 focus:ring-1 focus:ring-green-200 text-gray-800 placeholder-gray-400 shadow-sm hover:shadow-md"
                                    placeholder="最小額"
                                    min="0"
                                    step="10000"
                                >
                                <span class="text-gray-500 font-bold">〜</span>
                                <input 
                                    type="number" 
                                    id="amount-max" 
                                    name="amount_max"
                                    class="flex-1 px-4 py-3 border-2 border-slate-200 bg-white rounded-xl focus:border-green-500 focus:ring-1 focus:ring-green-200 text-gray-800 placeholder-gray-400 shadow-sm hover:shadow-md"
                                    placeholder="最大額"
                                    min="0"
                                    step="10000"
                                >
                            </div>
                        </div>

                        <!-- 申請期限 -->
                        <div class="group">
                            <label for="deadline-filter" class="block text-sm font-bold text-gray-700 mb-3 flex items-center group-hover:text-red-600 transition-colors duration-200">
                                <i class="fas fa-clock mr-2 text-red-500"></i>
                                申請期限
                            </label>
                            <select 
                                id="deadline-filter" 
                                name="deadline"
                                class="w-full px-4 py-3 border-2 border-slate-200 bg-white rounded-xl focus:border-red-500 focus:ring-1 focus:ring-red-200 text-gray-800 shadow-sm hover:shadow-md"
                            >
                                <option value="">指定なし</option>
                                <option value="1month">1ヶ月以内</option>
                                <option value="3months">3ヶ月以内</option>
                                <option value="6months">6ヶ月以内</option>
                                <option value="1year">1年以内</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 人気タグ -->
                <?php if (!empty($popular_tags)): ?>
                <div class="mb-10">
                    <h3 class="text-lg font-black text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tags mr-3 text-pink-500"></i>
                        人気タグ
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($popular_tags as $tag): ?>
                            <button 
                                type="button" 
                                class="tag-button px-6 py-3 bg-white border-2 border-slate-200 text-gray-700 rounded-full text-sm font-semibold hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm hover:shadow-md"
                                data-tag="<?php echo esc_attr($tag->name); ?>"
                                aria-label="<?php echo esc_attr($tag->name); ?>タグで検索"
                            >
                                <?php echo esc_html($tag->name); ?>
                                <span class="ml-2 text-xs opacity-70 bg-slate-100 px-2 py-1 rounded-full">(<?php echo $tag->count; ?>)</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- アクションボタン -->
                <div class="flex flex-col lg:flex-row gap-6 items-center">
                    <button 
                        type="submit" 
                        id="search-submit"
                        class="group relative w-full lg:flex-1 inline-flex items-center justify-center gap-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white px-8 py-5 rounded-full font-bold text-lg transition-all duration-500 transform hover:scale-105 hover:shadow-2xl shadow-emerald-500/25 overflow-hidden focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        aria-label="検索を実行"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <span class="search-button-text relative z-10 flex items-center">
                            <i class="fas fa-rocket mr-3 group-hover:animate-bounce" aria-hidden="true"></i>
                            検索実行
                        </span>
                        <span class="search-button-loading hidden relative z-10 flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-6 w-6 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            検索中...
                        </span>
                    </button>

                    <button 
                        type="button" 
                        id="advanced-toggle"
                        class="px-8 py-4 border-2 border-emerald-200 bg-white hover:bg-emerald-50 text-emerald-700 hover:text-emerald-800 rounded-full font-bold hover:border-emerald-300 hover:scale-105 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-200 shadow-sm hover:shadow-md"
                        aria-label="高度な検索オプションを切り替え"
                        aria-expanded="false"
                        aria-controls="advanced-search"
                    >
                        <i class="fas fa-sliders-h mr-2"></i>
                        高度な検索
                    </button>

                    <button 
                        type="button" 
                        id="search-reset"
                        class="px-8 py-4 text-gray-600 hover:text-gray-800 font-bold transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gray-300 rounded-full hover:bg-slate-50"
                        aria-label="検索条件をリセット"
                    >
                        <i class="fas fa-redo mr-2"></i>
                        リセット
                    </button>
                </div>
            </form>
        </div>

        <!-- 検索結果表示エリア -->
        <div id="search-results" class="hidden">
            <!-- 結果ヘッダー -->
            <div class="flex flex-col sm:flex-row justify-between items-center mb-8 p-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-white/70">
                <div id="results-info" class="text-xl font-bold text-gray-800 mb-4 sm:mb-0">
                    <!-- 結果件数が表示される -->
                </div>
                <div class="flex items-center space-x-4">
                    <!-- ビュー切り替え -->
                    <div class="flex border-2 border-slate-200 rounded-xl overflow-hidden">
                        <button 
                            id="grid-view" 
                            class="px-6 py-3 bg-emerald-600 text-white hover:bg-emerald-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-semibold"
                            aria-label="グリッド表示に切り替え"
                        >
                            <i class="fas fa-th"></i>
                        </button>
                        <button 
                            id="list-view" 
                            class="px-6 py-3 bg-white text-gray-700 hover:bg-slate-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 font-semibold"
                            aria-label="リスト表示に切り替え"
                        >
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <!-- エクスポートボタン -->
                    <button 
                        id="export-results" 
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 font-semibold shadow-lg hover:shadow-xl hover:scale-105"
                        aria-label="検索結果をエクスポート"
                    >
                        <i class="fas fa-download mr-2"></i>
                        エクスポート
                    </button>
                </div>
            </div>

            <!-- 結果一覧 -->
            <div id="results-container" class="grid gap-8">
                <!-- 検索結果がここに表示される -->
            </div>

            <!-- ページネーション -->
            <div id="pagination-container" class="mt-16 flex justify-center">
                <!-- ページネーションがここに表示される -->
            </div>
        </div>

        <!-- ローディング表示 -->
        <div id="search-loading" class="hidden text-center py-16">
            <div class="inline-flex items-center px-8 py-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/70">
                <svg class="animate-spin -ml-1 mr-4 h-8 w-8 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xl font-bold text-gray-800">検索中...</span>
            </div>
        </div>

        <!-- エラー表示 -->
        <div id="search-error" class="hidden text-center py-16">
            <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-8 max-w-md mx-auto shadow-xl">
                <div class="text-red-500 text-6xl mb-6">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-2xl font-bold text-red-800 mb-4">検索エラー</h3>
                <p class="text-red-700 mb-6 text-lg" id="error-message">
                    検索中にエラーが発生しました。しばらく時間をおいて再度お試しください。
                </p>
                <button 
                    id="retry-search" 
                    class="px-8 py-4 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 font-semibold shadow-lg hover:shadow-xl hover:scale-105"
                >
                    <i class="fas fa-redo mr-2"></i>
                    再試行
                </button>
            </div>
        </div>

        <!-- 検索履歴 -->
        <div id="search-history" class="mt-12 hidden">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-history mr-3 text-yellow-500"></i>
                最近の検索履歴
            </h3>
            <div class="flex flex-wrap gap-3" id="history-container">
                <!-- 検索履歴がここに表示される -->
            </div>
        </div>
    </div>
</section>

<!-- JavaScript - パーティクルアニメーション強化版 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // パーティクル初期化
    initializeSearchParticles();
    
    // 統計カウンターアニメーション初期化
    initializeCounterAnimations();
    
    // DOM要素の取得
    const searchForm = document.getElementById('grant-search-form');
    const searchKeyword = document.getElementById('search-keyword');
    const searchCategory = document.getElementById('search-category');
    const searchPostType = document.getElementById('search-post-type');
    const searchPrefecture = document.getElementById('search-prefecture');
    const searchOrderby = document.getElementById('search-orderby');
    const amountMin = document.getElementById('amount-min');
    const amountMax = document.getElementById('amount-max');
    const deadlineFilter = document.getElementById('deadline-filter');
    const advancedToggle = document.getElementById('advanced-toggle');
    const advancedSearch = document.getElementById('advanced-search');
    const searchReset = document.getElementById('search-reset');
    const submitButton = document.getElementById('search-submit');
    const searchButtonText = document.querySelector('.search-button-text');
    const searchButtonLoading = document.querySelector('.search-button-loading');
    const resultsSection = document.getElementById('search-results');
    const resultsContainer = document.getElementById('results-container');
    const resultsInfo = document.getElementById('results-info');
    const paginationContainer = document.getElementById('pagination-container');
    const loadingDiv = document.getElementById('search-loading');
    const errorDiv = document.getElementById('search-error');
    const errorMessage = document.getElementById('error-message');
    const retryButton = document.getElementById('retry-search');
    const tagButtons = document.querySelectorAll('.tag-button');
    const gridViewButton = document.getElementById('grid-view');
    const listViewButton = document.getElementById('list-view');
    const exportButton = document.getElementById('export-results');
    const historySection = document.getElementById('search-history');
    const historyContainer = document.getElementById('history-container');

    // パーティクル初期化関数（強化版の設定を反映）
    function initializeSearchParticles() {
        if (typeof particlesJS !== 'undefined' && document.getElementById('particles-search')) {
            particlesJS('particles-search', {
                "particles": {
                    "number": { 
                        "value": 90, 
                        "density": { "enable": true, "value_area": 1000 } 
                    },
                    "color": { 
                        "value": ["#10b981", "#14b8a6", "#3b82f6", "#8b5cf6", "#f59e0b", "#ef4444", "#06b6d4"] 
                    },
                    "shape": { 
                        "type": ["circle", "triangle", "edge", "star"],
                        "stroke": { "width": 0, "color": "#000000" }
                    },
                    "opacity": { 
                        "value": 0.4, 
                        "random": true,
                        "anim": { "enable": true, "speed": 1, "opacity_min": 0.1 }
                    },
                    "size": { 
                        "value": 3, 
                        "random": true,
                        "anim": { "enable": true, "speed": 2, "size_min": 0.5 }
                    },
                    "line_linked": { 
                        "enable": true, 
                        "distance": 120, 
                        "color": "#10b981", 
                        "opacity": 0.3, 
                        "width": 1 
                    },
                    "move": { 
                        "enable": true, 
                        "speed": 1.5,
                        "direction": "none", 
                        "random": true,
                        "straight": false,
                        "out_mode": "out", 
                        "bounce": false,
                        "attract": { "enable": false, "rotateX": 600, "rotateY": 1200 }
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": { 
                        "onhover": { "enable": true, "mode": "grab" }, 
                        "onclick": { "enable": true, "mode": "push" },
                        "resize": true
                    },
                    "modes": { 
                        "grab": { 
                            "distance": 140, 
                            "line_linked": { "opacity": 0.8 } 
                        }, 
                        "push": { "particles_nb": 3 } 
                    }
                },
                "retina_detect": true
            });
            
            console.log('🎨 Search particles initialized successfully!');
        } else {
            console.warn('Particles.js not loaded or container not found');
        }
    }

    // 統計カウンターアニメーション
    function initializeCounterAnimations() {
        const counters = document.querySelectorAll('.counter');
        const progressBars = document.querySelectorAll('.progress-bar');
        
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (entry.target.classList.contains('counter')) {
                            animateCounter(entry.target);
                        } else if (entry.target.classList.contains('progress-bar')) {
                            animateProgressBar(entry.target);
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => observer.observe(counter));
            progressBars.forEach(bar => observer.observe(bar));
        }
    }

    function animateCounter(element) {
        const target = parseInt(element.dataset.target) || 0;
        const suffix = element.dataset.suffix || '';
        const duration = 2000;
        const stepTime = 16;
        const steps = duration / stepTime;
        const increment = target / steps;
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target.toLocaleString() + suffix;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString() + suffix;
            }
        }, stepTime);
    }

    function animateProgressBar(element) {
        const width = element.dataset.width || 0;
        setTimeout(() => {
            element.style.width = width + '%';
        }, 300);
    }

    // 設定値
    const CONFIG = {
        debounceDelay: 300,
        maxRetries: 3,
        retryDelay: 1000,
        resultsPerPage: 12,
        maxHistoryItems: 10,
        cacheExpiry: 300000, // 5分
        DEBUG: <?php echo WP_DEBUG ? 'true' : 'false'; ?>
    };

    // 状態管理
    let currentSearchParams = {};
    let searchCache = new Map();
    let searchHistory = JSON.parse(localStorage.getItem('grant_search_history') || '[]');
    let currentPage = 1;
    let currentView = 'grid';
    let currentResults = [];
    let debounceTimer = null;
    let abortController = null;

    // デバッグログ
    function log(message, type = 'info') {
        if (CONFIG.DEBUG) {
            console.log(`🔍 Search Section [${type.toUpperCase()}]: ${message}`);
        }
    }

    // 初期化
    init();

    function init() {
        try {
            setupEventListeners();
            loadSearchHistory();
            setupKeyboardShortcuts();
            setupAccessibility();
            log('パーティクル強化検索システム初期化完了', 'success');
        } catch (error) {
            console.error('初期化エラー:', error);
            showError('システムの初期化に失敗しました。ページを再読み込みしてください。');
        }
    }

    // イベントリスナーの設定
    function setupEventListeners() {
        // フォーム送信
        searchForm.addEventListener('submit', handleFormSubmit);

        // リアルタイム検索（デバウンス）
        searchKeyword.addEventListener('input', debounce(handleRealtimeSearch, CONFIG.debounceDelay));

        // フィルター変更
        [searchCategory, searchPostType, searchPrefecture, searchOrderby, amountMin, amountMax, deadlineFilter].forEach(element => {
            if (element) {
                element.addEventListener('change', handleFilterChange);
            }
        });

        // 高度な検索の切り替え
        if (advancedToggle) {
            advancedToggle.addEventListener('click', toggleAdvancedSearch);
        }

        // リセットボタン
        if (searchReset) {
            searchReset.addEventListener('click', resetSearch);
        }

        // 再試行ボタン
        if (retryButton) {
            retryButton.addEventListener('click', retrySearch);
        }

        // タグボタン
        tagButtons.forEach(button => {
            button.addEventListener('click', handleTagClick);
        });

        // ビュー切り替え
        if (gridViewButton) {
            gridViewButton.addEventListener('click', () => switchView('grid'));
        }
        if (listViewButton) {
            listViewButton.addEventListener('click', () => switchView('list'));
        }

        // エクスポートボタン
        if (exportButton) {
            exportButton.addEventListener('click', exportResults);
        }

        // ウィンドウリサイズ
        window.addEventListener('resize', debounce(handleWindowResize, 250));
    }

    // フォーム送信処理
    async function handleFormSubmit(event) {
        event.preventDefault();
        
        if (submitButton.disabled) {
            return;
        }

        const searchData = collectSearchData();
        
        if (!validateSearchData(searchData)) {
            return;
        }

        try {
            await performSearch(searchData, 1);
            addToSearchHistory(searchData);
        } catch (error) {
            console.error('検索送信エラー:', error);
            showError('検索の実行に失敗しました。');
        }
    }

    // 検索データの収集
    function collectSearchData() {
        return {
            keyword: searchKeyword.value.trim(),
            category: searchCategory.value,
            post_type: searchPostType.value,
            prefecture: searchPrefecture.value,
            orderby: searchOrderby.value,
            amount_min: amountMin.value,
            amount_max: amountMax.value,
            deadline: deadlineFilter.value,
            nonce: document.getElementById('search-nonce').value
        };
    }

    // 検索データの検証
    function validateSearchData(data) {
        if (!data.keyword && !data.category && !data.post_type && !data.prefecture) {
            showError('検索キーワードまたはフィルター条件を指定してください。');
            return false;
        }

        if (data.amount_min && data.amount_max && parseInt(data.amount_min) > parseInt(data.amount_max)) {
            showError('最小金額は最大金額以下にしてください。');
            return false;
        }

        return true;
    }

    // 検索実行
    async function performSearch(searchData, page = 1) {
        if (abortController) {
            abortController.abort();
        }

        abortController = new AbortController();
        currentPage = page;
        currentSearchParams = { ...searchData, page };

        // UIの更新
        setLoadingState(true);
        hideError();

        // パーティクル効果強化
        enhanceParticlesForSearch();

        // キャッシュチェック
        const cacheKey = JSON.stringify(currentSearchParams);
        const cached = searchCache.get(cacheKey);
        
        if (cached && Date.now() - cached.timestamp < CONFIG.cacheExpiry) {
            displayResults(cached.data);
            setLoadingState(false);
            return;
        }

        try {
            const response = await fetch(document.getElementById('ajax-url').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'grant_insight_search',
                    ...currentSearchParams
                }),
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data?.message || '検索に失敗しました');
            }

            // キャッシュに保存
            searchCache.set(cacheKey, {
                data: data.data,
                timestamp: Date.now()
            });

            displayResults(data.data);

        } catch (error) {
            if (error.name === 'AbortError') {
                return; // リクエストがキャンセルされた場合は何もしない
            }
            
            console.error('検索エラー:', error);
            showError(error.message || '検索中にエラーが発生しました。');
        } finally {
            setLoadingState(false);
            resetParticlesAfterSearch();
        }
    }

    // パーティクル検索効果強化
    function enhanceParticlesForSearch() {
        if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
            const pJS = window.pJSDom[0].pJS;
            // パーティクル速度を一時的に上げる
            pJS.particles.move.speed = 3;
            pJS.particles.line_linked.opacity = 0.6;
            pJS.fn.particlesRefresh();
        }
    }

    function resetParticlesAfterSearch() {
        setTimeout(() => {
            if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
                const pJS = window.pJSDom[0].pJS;
                // 元の速度に戻す
                pJS.particles.move.speed = 1.5;
                pJS.particles.line_linked.opacity = 0.3;
                pJS.fn.particlesRefresh();
            }
        }, 2000);
    }

    // 結果表示
    function displayResults(data) {
        if (!data || !data.posts) {
            showError('検索結果の取得に失敗しました。');
            return;
        }

        currentResults = data.posts || [];
        resultsSection.classList.remove('hidden');
        
        // 結果情報の更新
        updateResultsInfo(data);
        
        // 結果一覧の表示
        renderResults(data.posts);
        
        // ページネーションの表示
        renderPagination(data.pagination);

        // アクセシビリティ
        announceResults(data.total);
    }

    // 結果情報の更新
    function updateResultsInfo(data) {
        const total = data.total || 0;
        const start = ((currentPage - 1) * CONFIG.resultsPerPage) + 1;
        const end = Math.min(start + CONFIG.resultsPerPage - 1, total);
        
        resultsInfo.innerHTML = `
            <span class="text-emerald-600 font-black text-2xl">${total.toLocaleString()}</span>
            <span class="text-gray-800">件中</span> 
            <span class="text-gray-600 text-lg">${start.toLocaleString()}-${end.toLocaleString()}</span>
            <span class="text-gray-800">件を表示</span>
        `;
    }

    // 結果一覧のレンダリング
    function renderResults(posts) {
        if (!posts || posts.length === 0) {
            resultsContainer.innerHTML = `
                <div class="col-span-full text-center py-20">
                    <div class="text-8xl mb-8">🔍</div>
                    <h3 class="text-3xl font-bold text-gray-800 mb-4">検索結果が見つかりませんでした</h3>
                    <p class="text-gray-600 mb-8 text-lg">検索条件を変更して再度お試しください。</p>
                    <button onclick="document.getElementById('search-reset').click()" 
                            class="px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl hover:from-emerald-600 hover:to-teal-700 transition-all duration-300 font-semibold text-lg shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-redo mr-2"></i>
                        検索条件をリセット
                    </button>
                </div>
            `;
            return;
        }

        const gridClass = currentView === 'grid' ? 
            'grid md:grid-cols-2 lg:grid-cols-3 gap-8' : 
            'space-y-6';

        resultsContainer.className = gridClass;
        resultsContainer.innerHTML = posts.map(post => renderPostCard(post)).join('');

        // 遅延読み込みの設定
        setupLazyLoading();
        
        // カードのアニメーション
        animateCards();
    }

    // 投稿カードのレンダリング
    function renderPostCard(post) {
        const cardClass = currentView === 'grid' ? 
            'bg-white/95 backdrop-blur-md rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 overflow-hidden border border-slate-100 hover:border-emerald-200 group' :
            'bg-white/95 backdrop-blur-md rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 flex items-center space-x-8 border border-slate-100 hover:border-emerald-200 group';

        const imageSection = post.thumbnail ? `
            <div class="${currentView === 'grid' ? 'h-56 overflow-hidden' : 'flex-shrink-0'}">
                <img src="${escapeHtml(post.thumbnail)}" 
                     alt="${escapeHtml(post.title)}"
                     class="${currentView === 'grid' ? 'w-full h-full object-cover group-hover:scale-110 transition-transform duration-500' : 'w-32 h-32 rounded-xl object-cover group-hover:scale-105 transition-transform duration-300'}"
                     loading="lazy">
            </div>
        ` : '';

        const contentClass = currentView === 'grid' ? 'p-8' : 'flex-1';

        return `
            <article class="${cardClass}" role="article" aria-labelledby="post-${post.id}-title">
                ${imageSection}
                <div class="${contentClass}">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-gradient-to-r ${getPostTypeGradient(post.post_type)} text-white shadow-md">
                            <i class="${getPostTypeIcon(post.post_type)} mr-2"></i>
                            ${getPostTypeLabel(post.post_type)}
                        </span>
                        ${post.is_featured ? '<span class="text-yellow-500 text-2xl">⭐</span>' : ''}
                    </div>
                    
                    <h3 id="post-${post.id}-title" class="text-xl lg:text-2xl font-bold text-gray-800 mb-4 line-clamp-2 group-hover:text-emerald-600 transition-colors duration-300">
                        <a href="${escapeHtml(post.permalink)}" 
                           class="hover:text-emerald-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded"
                           aria-describedby="post-${post.id}-excerpt">
                            ${escapeHtml(post.title)}
                        </a>
                    </h3>
                    
                    <p id="post-${post.id}-excerpt" class="text-gray-600 text-base mb-6 line-clamp-3 leading-relaxed">
                        ${escapeHtml(post.excerpt)}
                    </p>
                    
                    <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                        <time datetime="${post.date}" class="flex items-center font-medium">
                            <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                            ${formatDate(post.date)}
                        </time>
                        ${post.amount ? `<span class="font-bold text-green-600 text-lg flex items-center">
                            <i class="fas fa-yen-sign mr-1"></i>
                            ${formatAmount(post.amount)}
                        </span>` : ''}
                    </div>
                    
                    ${post.deadline ? `
                        <div class="mb-4 text-sm text-red-600 flex items-center font-semibold bg-red-50 px-3 py-2 rounded-lg border border-red-200">
                            <i class="fas fa-clock mr-2"></i>
                            締切: ${formatDate(post.deadline)}
                        </div>
                    ` : ''}
                    
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <a href="${escapeHtml(post.permalink)}" 
                           class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-bold text-base focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg px-4 py-2 bg-emerald-50 hover:bg-emerald-100 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-arrow-right mr-2"></i>
                            詳細を見る
                        </a>
                        <button class="favorite-button p-3 rounded-full hover:bg-slate-100 transition-all duration-300 transform hover:scale-110 ${post.is_favorite ? 'text-red-500' : 'text-gray-400 hover:text-red-500'}"
                                data-post-id="${post.id}"
                                aria-label="${post.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'}"
                                title="${post.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'}">
                            <i class="fas fa-heart text-xl"></i>
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    // ページネーションのレンダリング
    function renderPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        const { current_page, total_pages } = pagination;
        let paginationHTML = '<nav class="flex items-center justify-center space-x-3" aria-label="ページネーション">';

        // 前のページ
        if (current_page > 1) {
            paginationHTML += `
                <button class="pagination-btn px-6 py-4 bg-white/95 backdrop-blur-md border-2 border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-gray-700 hover:text-emerald-700 font-semibold shadow-sm hover:shadow-md transform hover:scale-105"
                        data-page="${current_page - 1}"
                        aria-label="前のページ">
                    <i class="fas fa-chevron-left mr-2"></i>前
                </button>
            `;
        }

        // ページ番号
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);

        if (startPage > 1) {
            paginationHTML += `
                <button class="pagination-btn px-4 py-4 bg-white/95 backdrop-blur-md border-2 border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-gray-700 hover:text-emerald-700 font-semibold shadow-sm hover:shadow-md"
                        data-page="1">1</button>
            `;
            if (startPage > 2) {
                paginationHTML += '<span class="px-3 text-gray-400 text-xl">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHTML += `
                <button class="pagination-btn px-4 py-4 ${isActive ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-xl border-2 border-emerald-500' : 'bg-white/95 backdrop-blur-md text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 border-2 border-slate-200 hover:border-emerald-300'} rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold ${isActive ? 'transform scale-110' : 'hover:scale-105 shadow-sm hover:shadow-md'}"
                        data-page="${i}"
                        ${isActive ? 'aria-current="page"' : ''}
                        aria-label="ページ ${i}">
                    ${i}
                </button>
            `;
        }

        if (endPage < total_pages) {
            if (endPage < total_pages - 1) {
                paginationHTML += '<span class="px-3 text-gray-400 text-xl">...</span>';
            }
            paginationHTML += `
                <button class="pagination-btn px-4 py-4 bg-white/95 backdrop-blur-md border-2 border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-gray-700 hover:text-emerald-700 font-semibold shadow-sm hover:shadow-md"
                        data-page="${total_pages}">${total_pages}</button>
            `;
        }

        // 次のページ
        if (current_page < total_pages) {
            paginationHTML += `
                <button class="pagination-btn px-6 py-4 bg-white/95 backdrop-blur-md border-2 border-slate-200 rounded-xl hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-gray-700 hover:text-emerald-700 font-semibold shadow-sm hover:shadow-md transform hover:scale-105"
                        data-page="${current_page + 1}"
                        aria-label="次のページ">
                    次<i class="fas fa-chevron-right ml-2"></i>
                </button>
            `;
        }

        paginationHTML += '</nav>';
        paginationContainer.innerHTML = paginationHTML;

        // ページネーションのイベントリスナー
        paginationContainer.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const page = parseInt(e.target.dataset.page);
                if (page && page !== currentPage) {
                    try {
                        await performSearch(currentSearchParams, page);
                        scrollToResults();
                    } catch (error) {
                        console.error('ページネーションエラー:', error);
                        showError('ページの読み込みに失敗しました。');
                    }
                }
            });
        });
    }

    // ユーティリティ関数群

    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(debounceTimer);
                func(...args);
            };
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(later, wait);
        };
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric'
            });
        } catch (error) {
            return dateString;
        }
    }

    function formatAmount(amount) {
        if (!amount) return '';
        return parseInt(amount).toLocaleString() + '円';
    }

    function getPostTypeLabel(postType) {
        const labels = {
            'grant': '助成金',
            'tool': 'ツール',
            'case_study': '成功事例',
            'guide': 'ガイド'
        };
        return labels[postType] || postType;
    }

    function getPostTypeIcon(postType) {
        const icons = {
            'grant': 'fas fa-coins',
            'tool': 'fas fa-tools',
            'case_study': 'fas fa-chart-line',
            'guide': 'fas fa-book-open'
        };
        return icons[postType] || 'fas fa-file';
    }

    function getPostTypeGradient(postType) {
        const gradients = {
            'grant': 'from-emerald-500 to-emerald-600',
            'tool': 'from-blue-500 to-blue-600',
            'case_study': 'from-purple-500 to-purple-600',
            'guide': 'from-orange-500 to-orange-600'
        };
        return gradients[postType] || 'from-gray-500 to-gray-600';
    }

    function setLoadingState(isLoading) {
        if (isLoading) {
            submitButton.disabled = true;
            searchButtonText.classList.add('hidden');
            searchButtonLoading.classList.remove('hidden');
            loadingDiv.classList.remove('hidden');
            resultsSection.classList.add('hidden');
        } else {
            submitButton.disabled = false;
            searchButtonText.classList.remove('hidden');
            searchButtonLoading.classList.add('hidden');
            loadingDiv.classList.add('hidden');
        }
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorDiv.classList.remove('hidden');
        resultsSection.classList.add('hidden');
        
        // エラーアナウンス
        announceToScreenReader(`エラー: ${message}`);
    }

    function hideError() {
        errorDiv.classList.add('hidden');
    }

    function announceResults(total) {
        const message = total > 0 ? 
            `${total}件の検索結果が見つかりました` : 
            '検索結果が見つかりませんでした';
        announceToScreenReader(message);
    }

    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.classList.add('sr-only');
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    function scrollToResults() {
        resultsSection.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }

    // 追加機能

    function toggleAdvancedSearch() {
        const isVisible = !advancedSearch.classList.contains('hidden');
        
        if (isVisible) {
            advancedSearch.style.display = 'none';
            advancedSearch.classList.add('hidden');
            advancedToggle.setAttribute('aria-expanded', 'false');
            advancedToggle.innerHTML = '<i class="fas fa-sliders-h mr-2"></i>高度な検索';
        } else {
            advancedSearch.classList.remove('hidden');
            advancedSearch.style.display = 'block';
            advancedToggle.setAttribute('aria-expanded', 'true');
            advancedToggle.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>基本検索';
        }
    }

    function resetSearch() {
        searchForm.reset();
        currentSearchParams = {};
        currentPage = 1;
        resultsSection.classList.add('hidden');
        hideError();
        
        // 高度な検索を閉じる
        if (!advancedSearch.classList.contains('hidden')) {
            toggleAdvancedSearch();
        }
        
        // パーティクル効果リセット
        if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
            const pJS = window.pJSDom[0].pJS;
            pJS.fn.vendors.destroypJS();
            setTimeout(initializeSearchParticles, 100);
        }
        
        // フォーカスをキーワード入力に戻す
        searchKeyword.focus();
        
        announceToScreenReader('検索条件がリセットされました');
    }

    function handleTagClick(event) {
        const tag = event.target.dataset.tag;
        if (tag) {
            searchKeyword.value = tag;
            searchKeyword.focus();
            
            // タグボタンをアクティブ状態に
            tagButtons.forEach(btn => {
                btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-500', 'scale-105');
                btn.classList.add('bg-white', 'text-gray-700', 'border-slate-200');
            });
            event.target.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-500', 'scale-105');
            event.target.classList.remove('bg-white', 'text-gray-700', 'border-slate-200');
        }
    }

    async function retrySearch() {
        if (currentSearchParams && Object.keys(currentSearchParams).length > 0) {
            try {
                await performSearch(currentSearchParams, currentPage);
            } catch (error) {
                console.error('再試行エラー:', error);
                showError('再試行に失敗しました。再度お試しください。');
            }
        }
    }

    function handleRealtimeSearch() {
        const keyword = searchKeyword.value.trim();
        if (keyword.length >= 2) {
            const searchData = {
                keyword: keyword,
                category: '',
                post_type: '',
                prefecture: '',
                orderby: 'relevance',
                nonce: document.getElementById('search-nonce').value
            };
            performSearch(searchData, 1);
        }
    }

    function handleFilterChange() {
        if (currentSearchParams && Object.keys(currentSearchParams).length > 0) {
            const searchData = collectSearchData();
            if (validateSearchData(searchData)) {
                performSearch(searchData, 1);
            }
        }
    }

    function switchView(viewType) {
        if (currentView === viewType) return;

        currentView = viewType;
        
        // ボタンのスタイルを更新
        if (viewType === 'grid') {
            gridViewButton.classList.add('bg-emerald-600', 'text-white');
            gridViewButton.classList.remove('bg-white', 'text-gray-700');
            listViewButton.classList.add('bg-white', 'text-gray-700');
            listViewButton.classList.remove('bg-emerald-600', 'text-white');
        } else {
            listViewButton.classList.add('bg-emerald-600', 'text-white');
            listViewButton.classList.remove('bg-white', 'text-gray-700');
            gridViewButton.classList.add('bg-white', 'text-gray-700');
            gridViewButton.classList.remove('bg-emerald-600', 'text-white');
        }
        
        // 現在の結果データを使って再レンダリング
        if (currentResults && currentResults.length > 0) {
            renderResults(currentResults);
        }
    }

    async function exportResults() {
        if (!currentSearchParams || Object.keys(currentSearchParams).length === 0) {
            showError('エクスポートする検索結果がありません。');
            return;
        }

        try {
            const response = await fetch(document.getElementById('ajax-url').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'grant_insight_export_results',
                    ...currentSearchParams,
                    export_format: 'csv'
                })
            });

            if (!response.ok) {
                throw new Error('エクスポートに失敗しました');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `grant_search_results_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            announceToScreenReader('検索結果がエクスポートされました');
        } catch (error) {
            console.error('エクスポートエラー:', error);
            showError('エクスポートに失敗しました。');
        }
    }

    function addToSearchHistory(searchData) {
        const historyItem = {
            keyword: searchData.keyword,
            category: searchData.category,
            post_type: searchData.post_type,
            prefecture: searchData.prefecture,
            timestamp: Date.now()
        };

        // 重複を除去
        searchHistory = searchHistory.filter(item => 
            item.keyword !== historyItem.keyword || 
            item.category !== historyItem.category || 
            item.post_type !== historyItem.post_type ||
            item.prefecture !== historyItem.prefecture
        );

        searchHistory.unshift(historyItem);
        searchHistory = searchHistory.slice(0, CONFIG.maxHistoryItems);

        localStorage.setItem('grant_search_history', JSON.stringify(searchHistory));
        renderSearchHistory();
    }

    function loadSearchHistory() {
        if (searchHistory.length > 0) {
            renderSearchHistory();
        }
    }

    function renderSearchHistory() {
        if (searchHistory.length === 0) {
            historySection.classList.add('hidden');
            return;
        }

        historySection.classList.remove('hidden');
        historyContainer.innerHTML = searchHistory.map(item => `
            <button class="history-item px-6 py-3 bg-white/95 backdrop-blur-md border-2 border-slate-200 hover:bg-emerald-50 hover:border-emerald-300 rounded-xl text-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-gray-700 hover:text-emerald-700 font-medium shadow-sm hover:shadow-md transform hover:scale-105"
                    data-keyword="${escapeHtml(item.keyword)}"
                    data-category="${escapeHtml(item.category)}"
                    data-post-type="${escapeHtml(item.post_type)}"
                    data-prefecture="${escapeHtml(item.prefecture)}">
                <i class="fas fa-history mr-2 text-yellow-500"></i>
                ${escapeHtml(item.keyword || '（フィルターのみ）')}
                ${item.category ? `<span class="text-emerald-600">・${escapeHtml(item.category)}</span>` : ''}
                ${item.post_type ? `<span class="text-blue-600">・${getPostTypeLabel(item.post_type)}</span>` : ''}
                ${item.prefecture ? `<span class="text-purple-600">・${escapeHtml(item.prefecture)}</span>` : ''}
            </button>
        `).join('');

        historyContainer.querySelectorAll('.history-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const { keyword, category, postType, prefecture } = e.target.dataset;
                searchKeyword.value = keyword || '';
                searchCategory.value = category || '';
                searchPostType.value = postType || '';
                searchPrefecture.value = prefecture || '';
                
                const searchData = collectSearchData();
                if (validateSearchData(searchData)) {
                    performSearch(searchData, 1);
                }
            });
        });
    }

    function setupLazyLoading() {
        const images = resultsContainer.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    }

    function animateCards() {
        const cards = resultsContainer.querySelectorAll('article');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Enter で検索実行
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (!submitButton.disabled) {
                    searchForm.dispatchEvent(new Event('submit'));
                }
            }
            
            // Escape で検索結果を閉じる
            if (e.key === 'Escape') {
                if (!resultsSection.classList.contains('hidden')) {
                    resetSearch();
                }
            }
        });
    }

    function setupAccessibility() {
        // スクリーンリーダー用のライブリージョン
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.classList.add('sr-only');
        liveRegion.id = 'search-announcements';
        document.body.appendChild(liveRegion);

        // フォーカス管理
        searchForm.addEventListener('submit', () => {
            setTimeout(() => {
                if (!resultsSection.classList.contains('hidden')) {
                    resultsSection.focus();
                }
            }, 100);
        });
    }

    function handleWindowResize() {
        // レスポンシブ対応の調整
        if (window.innerWidth < 768) {
            currentView = 'list';
        }
        
        // パーティクルの再調整
        if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
            window.pJSDom[0].pJS.fn.particlesRefresh();
        }
    }

    // お気に入り機能（デリゲートイベント）
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.favorite-button')) {
            e.preventDefault();
            
            const button = e.target.closest('.favorite-button');
            const postId = button.dataset.postId;
            
            if (!postId) return;

            try {
                const response = await fetch(document.getElementById('ajax-url').value, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'grant_insight_toggle_favorite',
                        post_id: postId,
                        nonce: document.getElementById('search-nonce').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    const isFavorite = data.data.is_favorite;
                    
                    if (isFavorite) {
                        button.classList.add('text-red-500');
                        button.classList.remove('text-gray-400');
                    } else {
                        button.classList.remove('text-red-500');
                        button.classList.add('text-gray-400');
                    }
                    
                    button.setAttribute('aria-label', isFavorite ? 'お気に入りから削除' : 'お気に入りに追加');
                    button.setAttribute('title', isFavorite ? 'お気に入りから削除' : 'お気に入りに追加');
                    
                    // アニメーション効果
                    button.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        button.style.transform = 'scale(1)';
                    }, 200);
                    
                    announceToScreenReader(isFavorite ? 'お気に入りに追加しました' : 'お気に入りから削除しました');
                } else {
                    throw new Error(data.data?.message || 'お気に入りの更新に失敗しました');
                }
            } catch (error) {
                console.error('お気に入り切り替えエラー:', error);
                showError('お気に入りの更新に失敗しました。');
            }
        }
    });

    log('🎉 パーティクル強化版検索システム初期化完了! ✨🌟', 'success');
});
</script>

<!-- CSS - パーティクルアニメーション強化版 -->
<style>
/* 検索セクション専用スタイル - パーティクル強化版 */
.search-section {
    font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    position: relative;
    background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 25%, #ecfdf5 50%, #f0f9ff 75%, #ffffff 100%);
    min-height: 100vh;
}

/* パーティクルキャンバスのスタイル */
#particles-search {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 0;
}

#particles-search canvas {
    display: block;
    vertical-align: bottom;
    -webkit-transform: scale(1);
    transform: scale(1);
    opacity: 0.7;
}

/* 六角形パターン */
.hexagon-pattern {
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2310b981' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    width: 100%;
    height: 100%;
    opacity: 0.3;
}

/* ドットパターン */
.dot-pattern {
    background-image: radial-gradient(circle, #10b981 1px, transparent 1px);
    background-size: 20px 20px;
    width: 100%;
    height: 100%;
    opacity: 0.1;
}

/* 三角形装飾 */
.triangle-decoration {
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 16px solid rgba(16, 185, 129, 0.3);
}

/* 回転リング装飾 */
.ring-decoration {
    box-shadow: 
        0 0 20px rgba(16, 185, 129, 0.2),
        inset 0 0 20px rgba(16, 185, 129, 0.1);
}

/* フローティングアニメーション */
@keyframes float-slow {
    0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
    33% { transform: translateY(-30px) rotate(2deg) scale(1.05); }
    66% { transform: translateY(-15px) rotate(-1deg) scale(0.98); }
}

@keyframes float-1 { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
@keyframes float-2 { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
@keyframes float-3 { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-18px); } }

/* 波のアニメーション */
@keyframes wave {
    0%, 100% { transform: translateX(0px) translateY(0px) scaleY(1); }
    50% { transform: translateX(-25px) translateY(-10px) scaleY(1.1); }
}

/* 回転アニメーション */
@keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes spin-reverse { from { transform: rotate(360deg); } to { transform: rotate(0deg); } }
@keyframes spin-slow-2 { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes spin-very-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.animate-float-slow { animation: float-slow 8s ease-in-out infinite; }
.animate-float-1 { animation: float-1 4s ease-in-out infinite; }
.animate-float-2 { animation: float-2 5s ease-in-out infinite 1s; }
.animate-float-3 { animation: float-3 6s ease-in-out infinite 2s; }

.animate-wave { animation: wave 20s ease-in-out infinite; }
.animate-spin-slow { animation: spin-slow 40s linear infinite; }
.animate-spin-reverse { animation: spin-reverse 30s linear infinite; }
.animate-spin-slow-2 { animation: spin-slow-2 50s linear infinite; }
.animate-spin-very-slow { animation: spin-very-slow 60s linear infinite; }

/* 統計カードのスタイル強化 */
.stat-card {
    backdrop-filter: blur(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.stat-card:hover::before {
    left: 100%;
}

.stat-card:hover {
    background: rgba(255, 255, 255, 0.98);
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* プログレスバーの強化 */
.progress-bar {
    position: relative;
    overflow: hidden;
}

.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* ガラスモーフィズム効果の強化 */
.backdrop-blur-md {
    backdrop-filter: blur(12px) saturate(180%);
    -webkit-backdrop-filter: blur(12px) saturate(180%);
}

.backdrop-blur-sm {
    backdrop-filter: blur(6px) saturate(160%);
    -webkit-backdrop-filter: blur(6px) saturate(160%);
}

/* line-clamp ユーティリティ */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* スクリーンリーダー専用 */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* フォーカス表示の改善 */
*:focus {
    outline: 2px solid #10b981;
    outline-offset: 2px;
}

/* セレクトボックスのカスタマイズ */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px 12px;
    padding-right: 40px;
}

/* ボタンアニメーション強化 */
button {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

button:active {
    transform: scale(0.95);
}

/* ホバー時のリップル効果 */
.btn-ripple {
    position: relative;
    overflow: hidden;
}

.btn-ripple::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    transition: width 0.6s, height 0.6s, top 0.6s, left 0.6s;
    transform: translate(-50%, -50%);
}

.btn-ripple:active::after {
    width: 300px;
    height: 300px;
    top: 50%;
    left: 50%;
}

/* カードアニメーションの強化 */
.search-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateZ(0);
}

.search-card:hover {
    transform: translateY(-5px) scale(1.02) translateZ(0);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* レスポンシブ調整 */
@media (max-width: 1024px) {
    .search-section .container {
        padding-left: 2rem;
        padding-right: 2rem;
    }
    
    .search-section .lg\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    
    #particles-search {
        opacity: 0.6;
    }
    
    .floating-circle {
        opacity: 0.7;
        transform: scale(0.8);
    }
    
    .hexagon-pattern, .dot-pattern {
        opacity: 0.05;
    }
}

@media (max-width: 768px) {
    .search-section {
        padding: 3rem 0;
    }
    
    .search-section h2 {
        font-size: 2.5rem;
        line-height: 1.1;
    }
    
    .search-section .text-xl {
        font-size: 1.125rem;
    }
    
    .search-section .p-8 {
        padding: 1.5rem;
    }
    
    .search-section .lg\:p-12 {
        padding: 2rem;
    }
    
    .search-section .lg\:grid-cols-4 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    .floating-element {
        display: none;
    }
    
    #particles-search {
        opacity: 0.4;
    }
    
    .floating-circle {
        opacity: 0.4;
        transform: scale(0.6);
    }
    
    .triangle-decoration {
        display: none;
    }
}

@media (max-width: 640px) {
    .search-section .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .search-section h2 {
        font-size: 2rem;
    }
    
    .search-section .text-2xl {
        font-size: 1.5rem;
    }
    
    .search-section .px-12 {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    
    .floating-circle, .floating-accent {
        display: none;
    }
    
    #particles-search {
        opacity: 0.3;
    }
    
    .hexagon-pattern, .dot-pattern {
        display: none;
    }
}

/* アクセシビリティ対応 */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .animate-float-1, .animate-float-2, .animate-float-3, .animate-float-slow,
    .animate-spin-slow, .animate-spin-reverse, .animate-spin-slow-2, .animate-spin-very-slow,
    .animate-bounce, .animate-pulse, .animate-ping, .animate-wave {
        animation: none !important;
    }
    
    #particles-search {
        display: none !important;
    }
}

/* 印刷対応 */
@media print {
    .search-section {
        background: white;
        color: black;
    }
    
    .search-section .bg-gradient-to-br {
        background: white;
    }
    
    .search-section .shadow-lg,
    .search-section .shadow-xl,
    .search-section .shadow-2xl {
        box-shadow: none;
        border: 1px solid #d1d5db;
    }
    
    .search-section .backdrop-blur-sm,
    .search-section .backdrop-blur-md {
        backdrop-filter: none;
        background: rgba(255, 255, 255, 0.9);
    }
    
    .floating-element, .floating-circle, .floating-accent,
    #particles-search, .hexagon-pattern, .dot-pattern, .triangle-decoration {
        display: none !important;
    }
}

/* GPU加速最適化 */
.animate-float-1, .animate-float-2, .animate-float-3, .animate-float-slow,
.animate-spin-slow, .animate-spin-reverse, .animate-spin-slow-2, .animate-spin-very-slow,
.animate-wave {
    will-change: transform;
    transform: translateZ(0);
}

.search-section .transform {
    will-change: transform;
}

.search-section .transition-all {
    will-change: transform, opacity, background-color, border-color;
}

/* パーティクルキャンバスの最適化 */
#particles-search canvas {
    will-change: transform;
}

/* 高解像度ディスプレイ対応 */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    #particles-search canvas {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
}
</style>
