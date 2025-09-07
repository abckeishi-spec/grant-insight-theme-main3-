<?php
/**
 * Template for displaying grant archive with prefecture filter - PERFECT VERSION
 * Grant Insight Perfect - Functions.php v6.2対応版 - 都道府県フィルター完全修正版
 * 
 * Features:
 * - Complete prefecture filter with toggle button
 * - 47 prefectures + nationwide support
 * - Popular prefectures priority display
 * - Perfect AJAX integration
 * - Responsive design
 * - Complete error handling
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-50">
    <!-- ヒーローセクション -->
    <section class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white py-16 md:py-24">
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        <div class="relative container mx-auto px-4">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-500 rounded-full mb-6 animate-bounce-gentle">
                    <i class="fas fa-coins text-2xl text-white"></i>
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4 animate-fade-in-up">
                    助成金・補助金一覧
                </h1>
                <p class="text-xl md:text-2xl text-emerald-100 mb-8 animate-fade-in-up animation-delay-200">
                    全国の助成金・補助金情報を都道府県別に検索
                </p>
                
                <!-- 統計情報 -->
                <div class="flex flex-wrap justify-center gap-6 md:gap-12 animate-fade-in-up animation-delay-400">
                    <?php
                    $total_grants = wp_count_posts('grant')->publish;
                    $active_grants = get_posts(array(
                        'post_type' => 'grant',
                        'meta_query' => array(
                            array(
                                'key' => 'application_status',
                                'value' => 'open',
                                'compare' => '='
                            )
                        ),
                        'fields' => 'ids'
                    ));
                    $prefecture_count = wp_count_terms(array('taxonomy' => 'grant_prefecture', 'hide_empty' => false));
                    ?>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-yellow-300">
                            <?php echo gi_safe_number_format($total_grants); ?>
                        </div>
                        <div class="text-sm md:text-base text-emerald-100">件</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-green-300">
                            <?php echo gi_safe_number_format(count($active_grants)); ?>
                        </div>
                        <div class="text-sm md:text-base text-emerald-100">募集中</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-orange-300">
                            <?php echo gi_safe_number_format($prefecture_count); ?>
                        </div>
                        <div class="text-sm md:text-base text-emerald-100">都道府県</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 検索・フィルターセクション -->
    <section class="py-8 bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <!-- 検索バー -->
            <div class="mb-6">
                <div class="relative max-w-2xl mx-auto">
                    <input type="text" 
                           id="grant-search" 
                           class="w-full px-6 py-4 text-lg border-2 border-gray-200 rounded-full focus:border-emerald-500 focus:ring-4 focus:ring-emerald-200 transition-all duration-300 pr-14"
                           placeholder="キーワードを入力してください（例：IT導入補助金、設備投資支援など）">
                    <button type="button" 
                            id="search-btn"
                            class="absolute right-2 top-2 w-12 h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- 表示切り替え・並び順 -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <!-- クイックフィルター -->
                    <div class="flex gap-2">
                        <button class="quick-filter active px-4 py-2 rounded-full text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition-colors" data-filter="all">すべて</button>
                        <button class="quick-filter px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors" data-filter="active">募集中</button>
                        <button class="quick-filter px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors" data-filter="upcoming">募集予定</button>
                        <button class="quick-filter px-4 py-2 rounded-full text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors" data-filter="national">全国対応</button>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- 並び順 -->
                    <select id="sort-order" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="date_desc">新着順</option>
                        <option value="date_asc">古い順</option>
                        <option value="amount_desc">金額が高い順</option>
                        <option value="amount_asc">金額が安い順</option>
                        <option value="deadline_asc">締切が近い順</option>
                        <option value="title_asc">タイトル順</option>
                    </select>

                    <!-- 表示切り替え -->
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button id="grid-view" class="view-toggle active flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 bg-white text-emerald-600 shadow-sm">
                            <i class="fas fa-th-large"></i>
                            <span class="hidden sm:inline">グリッド</span>
                        </button>
                        <button id="list-view" class="view-toggle flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-list"></i>
                            <span class="hidden sm:inline">リスト</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- メインコンテンツ -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- サイドバー（フィルター） -->
            <aside class="lg:w-80 shrink-0">
                <div class="bg-white rounded-xl shadow-sm border p-6 sticky top-24">
                    <!-- フィルターヘッダー -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-filter text-emerald-600"></i>
                            絞り込み検索
                        </h3>
                        <button id="clear-filters" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                            クリア
                        </button>
                    </div>

                    <!-- 都道府県フィルター（完全修正版） -->
                    <div class="mb-8">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-red-600"></i>
                            対象地域
                        </h4>
                        <div id="prefecture-filter">
                            <!-- 人気都道府県（初期表示） -->
                            <div id="popular-prefectures">
                                <?php
                                $popular_prefectures = array('全国対応', '東京都', '大阪府', '愛知県', '神奈川県', '福岡県');
                                foreach ($popular_prefectures as $pref_name) {
                                    $term = get_term_by('name', $pref_name, 'grant_prefecture');
                                    if ($term && !is_wp_error($term)) :
                                ?>
                                <label class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="prefecture[]" value="<?php echo gi_safe_attr($term->slug); ?>" class="prefecture-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                        <span class="text-sm text-gray-700 group-hover:text-gray-900"><?php echo gi_safe_escape($term->name); ?></span>
                                    </div>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?php echo $term->count; ?></span>
                                </label>
                                <?php 
                                    endif;
                                }
                                ?>
                            </div>

                            <!-- 全都道府県（折りたたみ） -->
                            <div id="all-prefectures" class="hidden">
                                <?php
                                $all_prefectures = get_terms(array(
                                    'taxonomy' => 'grant_prefecture',
                                    'hide_empty' => false,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ));

                                // 人気都道府県以外を表示
                                if (!empty($all_prefectures) && !is_wp_error($all_prefectures)) {
                                    foreach ($all_prefectures as $prefecture) {
                                        if (!in_array($prefecture->name, $popular_prefectures)) :
                                ?>
                                <label class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="prefecture[]" value="<?php echo gi_safe_attr($prefecture->slug); ?>" class="prefecture-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                        <span class="text-sm text-gray-700 group-hover:text-gray-900"><?php echo gi_safe_escape($prefecture->name); ?></span>
                                    </div>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?php echo $prefecture->count; ?></span>
                                </label>
                                <?php 
                                        endif;
                                    }
                                }
                                ?>
                            </div>

                            <!-- ★★★ 都道府県展開ボタン（修正完了）★★★ -->
                            <?php if (!empty($all_prefectures) && count($all_prefectures) > 6) : ?>
                            <button id="toggle-prefectures" class="w-full mt-3 py-2 px-4 text-sm text-emerald-600 hover:text-emerald-800 border border-emerald-200 hover:border-emerald-300 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <span class="toggle-text">その他の都道府県を表示</span>
                                <i class="fas fa-chevron-down toggle-icon transition-transform duration-200"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- カテゴリフィルター -->
                    <div class="mb-8">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-tags text-green-600"></i>
                            カテゴリ
                        </h4>
                        <div id="category-filter">
                            <?php
                            // 代表カテゴリを取得
                            $categories = get_terms(array(
                                'taxonomy' => 'grant_category',
                                'hide_empty' => false,
                                'orderby' => 'count',
                                'order' => 'DESC',
                                'number' => 6
                            ));

                            $all_categories = get_terms(array(
                                'taxonomy' => 'grant_category',
                                'hide_empty' => false,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ));

                            if (!empty($categories) && !is_wp_error($categories)) :
                                // 代表カテゴリ表示（上位5個）
                                foreach (array_slice($categories, 0, 5) as $category) :
                            ?>
                            <label class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors group">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="category[]" value="<?php echo gi_safe_attr($category->slug); ?>" class="category-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900"><?php echo gi_safe_escape($category->name); ?></span>
                                </div>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?php echo $category->count; ?></span>
                            </label>
                            <?php endforeach; ?>

                            <!-- その他のカテゴリ（折りたたみ） -->
                            <?php if (!empty($all_categories) && !is_wp_error($all_categories) && count($all_categories) > 5) : ?>
                            <div id="more-categories" class="hidden">
                                <?php foreach (array_slice($all_categories, 5) as $category) : ?>
                                <label class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="category[]" value="<?php echo gi_safe_attr($category->slug); ?>" class="category-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                        <span class="text-sm text-gray-700 group-hover:text-gray-900"><?php echo gi_safe_escape($category->name); ?></span>
                                    </div>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?php echo $category->count; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <button id="toggle-categories" class="w-full mt-3 py-2 px-4 text-sm text-emerald-600 hover:text-emerald-800 border border-emerald-200 hover:border-emerald-300 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <span class="toggle-text">その他のカテゴリを表示</span>
                                <i class="fas fa-chevron-down toggle-icon transition-transform duration-200"></i>
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 金額フィルター -->
                    <div class="mb-8">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-yen-sign text-yellow-600"></i>
                            助成金額
                        </h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="radio" name="amount" value="" checked class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">すべて</span>
                            </label>
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="radio" name="amount" value="0-100" class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">100万円以下</span>
                            </label>
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="radio" name="amount" value="100-500" class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">100万円〜500万円</span>
                            </label>
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="radio" name="amount" value="500-1000" class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">500万円〜1000万円</span>
                            </label>
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="radio" name="amount" value="1000+" class="w-4 h-4 text-emerald-600 border-gray-300 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">1000万円以上</span>
                            </label>
                        </div>
                    </div>

                    <!-- ステータスフィルター -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-clock text-orange-600"></i>
                            募集状況
                        </h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="status[]" value="active" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">募集中</span>
                                <span class="ml-auto w-3 h-3 bg-green-500 rounded-full"></span>
                            </label>
                            <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="status[]" value="upcoming" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">募集予定</span>
                                <span class="ml-auto w-3 h-3 bg-yellow-500 rounded-full"></span>
                            </label>
                        </div>
                    </div>

                    <!-- フィルター統計表示 -->
                    <div class="bg-emerald-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-emerald-600" id="filter-stats-count">-</div>
                        <div class="text-sm text-emerald-700">該当する助成金</div>
                    </div>
                </div>
            </aside>

            <!-- メインコンテンツエリア -->
            <main class="flex-1">
                <!-- 検索結果ヘッダー -->
                <div id="results-header" class="mb-6 p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <span id="results-count" class="text-lg font-semibold text-emerald-900">検索中...</span>
                            <span id="results-query" class="text-sm text-emerald-700 ml-2"></span>
                        </div>
                        <div id="loading-spinner" class="hidden">
                            <i class="fas fa-spinner fa-spin text-emerald-600"></i>
                        </div>
                    </div>
                    <!-- 選択中のフィルター表示 -->
                    <div id="active-filters" class="mt-3 flex flex-wrap gap-2"></div>
                </div>

                <!-- 助成金カード表示エリア -->
                <div id="grants-container">
                    <!-- グリッド表示 -->
                    <div id="grid-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <!-- カードがここに動的に読み込まれます -->
                    </div>

                    <!-- リスト表示 -->
                    <div id="list-container" class="hidden space-y-4">
                        <!-- リストがここに動的に読み込まれます -->
                    </div>
                </div>

                <!-- ページネーション -->
                <div id="pagination-container" class="mt-12 flex justify-center">
                    <!-- ページネーションがここに表示されます -->
                </div>

                <!-- ローディング表示 -->
                <div id="main-loading" class="text-center py-12">
                    <div class="inline-flex items-center px-6 py-3 bg-white rounded-xl shadow-lg">
                        <i class="fas fa-spinner fa-spin text-3xl text-emerald-600 mr-4"></i>
                        <div>
                            <p class="text-lg font-medium text-gray-800 mb-1">助成金情報を読み込んでいます...</p>
                            <p class="text-sm text-gray-600">しばらくお待ちください</p>
                        </div>
                    </div>
                </div>

                <!-- 結果なし表示 -->
                <div id="no-results" class="hidden text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-search text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">該当する助成金が見つかりませんでした</h3>
                    <p class="text-gray-600 mb-6">検索条件を変更して再度お試しください</p>
                    <div class="flex justify-center gap-4">
                        <button id="reset-search" class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                            検索条件をリセット
                        </button>
                        <a href="<?php echo home_url('/'); ?>" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            トップページに戻る
                        </a>
                    </div>
                </div>

                <!-- エラー表示 -->
                <div id="error-display" class="hidden text-center py-12">
                    <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">エラーが発生しました</h3>
                    <p class="text-gray-600 mb-6" id="error-message">通信エラーが発生しました。しばらく時間をおいて再度お試しください。</p>
                    <button id="retry-loading" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-redo mr-2"></i>
                        再試行
                    </button>
                </div>
            </main>
        </div>
    </div>

    <!-- フローティングヘルプボタン -->
    <div class="fixed bottom-6 right-6 z-50">
        <button id="help-toggle" class="w-14 h-14 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 flex items-center justify-center">
            <i class="fas fa-question text-lg"></i>
        </button>
        
        <!-- ヘルプパネル -->
        <div id="help-panel" class="hidden absolute bottom-16 right-0 w-80 bg-white rounded-xl shadow-2xl border p-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-emerald-600"></i>
                検索のヒント
            </h4>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-start gap-2">
                    <i class="fas fa-lightbulb text-yellow-500 mt-1"></i>
                    <div>
                        <strong>キーワード検索：</strong><br>
                        「IT導入」「設備投資」「人材育成」など具体的なキーワードで検索できます
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-map-marker-alt text-red-500 mt-1"></i>
                    <div>
                        <strong>都道府県フィルター：</strong><br>
                        複数の都道府県を同時に選択可能です
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-filter text-emerald-500 mt-1"></i>
                    <div>
                        <strong>絞り込み：</strong><br>
                        金額、募集状況、カテゴリで詳細に絞り込めます
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grant Archive JavaScript - Perfect Version (都道府県フィルター完全対応)
document.addEventListener('DOMContentLoaded', function() {
    const GrantArchive = {
        currentView: 'grid',
        currentPage: 1,
        isLoading: false,
        filters: {
            search: '',
            categories: [],
            categorySlugs: [],
            prefectures: [],
            prefectureSlugs: [],
            amount: '',
            status: [],
            sort: 'date_desc'
        },

        init() {
            this.bindEvents();
            this.loadGrants();
            this.initializeHelpers();
        },

        bindEvents() {
            // 検索
            const searchInput = document.getElementById('grant-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.filters.search = e.target.value;
                    this.debounce(() => this.loadGrants(), 500)();
                });
            }

            const searchBtn = document.getElementById('search-btn');
            if (searchBtn) {
                searchBtn.addEventListener('click', () => {
                    this.loadGrants();
                });
            }

            // 表示切り替え
            const gridView = document.getElementById('grid-view');
            if (gridView) {
                gridView.addEventListener('click', () => {
                    this.switchView('grid');
                });
            }

            const listView = document.getElementById('list-view');
            if (listView) {
                listView.addEventListener('click', () => {
                    this.switchView('list');
                });
            }

            // 並び順
            const sortOrder = document.getElementById('sort-order');
            if (sortOrder) {
                sortOrder.addEventListener('change', (e) => {
                    this.filters.sort = e.target.value;
                    this.loadGrants();
                });
            }

            // クイックフィルター
            document.querySelectorAll('.quick-filter').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.quick-filter').forEach(b => {
                        b.classList.remove('active', 'bg-emerald-600', 'text-white');
                        b.classList.add('bg-gray-200', 'text-gray-700');
                    });
                    
                    e.target.classList.add('active', 'bg-emerald-600', 'text-white');
                    e.target.classList.remove('bg-gray-200', 'text-gray-700');

                    const filter = e.target.dataset.filter;
                    if (filter === 'all') {
                        this.filters.status = [];
                        this.filters.prefectures = [];
                        this.filters.prefectureSlugs = [];
                    } else if (filter === 'national') {
                        // 全国対応のslugをDOMから取得
                        let nationalSlug = '';
                        document.querySelectorAll('.prefecture-checkbox').forEach(cb => {
                            const label = cb.closest('label');
                            if (label && label.textContent.includes('全国対応')) {
                                nationalSlug = cb.value;
                                cb.checked = true;
                            } else {
                                cb.checked = false;
                            }
                        });
                        this.filters.prefectures = ['全国対応'];
                        this.filters.prefectureSlugs = nationalSlug ? [nationalSlug] : [];
                        this.filters.status = [];
                    } else {
                        this.filters.status = [filter];
                        this.filters.prefectures = [];
                        // ステータスチェックボックスの状態を更新
                        document.querySelectorAll('input[name="status[]"]').forEach(cb => {
                            cb.checked = cb.value === filter;
                        });
                    }
                    this.updateFilterDisplay();
                    this.loadGrants();
                });
            });

            // 都道府県・カテゴリ展開（修正版）
            const togglePrefectures = document.getElementById('toggle-prefectures');
            if (togglePrefectures) {
                togglePrefectures.addEventListener('click', () => {
                    this.togglePrefectures();
                });
            }

            const toggleCategories = document.getElementById('toggle-categories');
            if (toggleCategories) {
                toggleCategories.addEventListener('click', () => {
                    this.toggleCategories();
                });
            }

            // フィルターイベント
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('prefecture-checkbox')) {
                    this.updatePrefectureFilters();
                } else if (e.target.classList.contains('category-checkbox')) {
                    this.updateCategoryFilters();
                } else if (e.target.name === 'amount') {
                    this.filters.amount = e.target.value;
                    this.updateFilterDisplay();
                    this.loadGrants();
                } else if (e.target.name === 'status[]') {
                    this.updateStatusFilters();
                }
            });

            // フィルタークリア
            const clearFilters = document.getElementById('clear-filters');
            if (clearFilters) {
                clearFilters.addEventListener('click', () => {
                    this.clearFilters();
                });
            }

            // 検索リセット
            const resetSearch = document.getElementById('reset-search');
            if (resetSearch) {
                resetSearch.addEventListener('click', () => {
                    this.resetSearch();
                });
            }

            // 再試行
            const retryLoading = document.getElementById('retry-loading');
            if (retryLoading) {
                retryLoading.addEventListener('click', () => {
                    this.hideError();
                    this.loadGrants();
                });
            }

            // ヘルプトグル
            const helpToggle = document.getElementById('help-toggle');
            const helpPanel = document.getElementById('help-panel');
            if (helpToggle && helpPanel) {
                helpToggle.addEventListener('click', () => {
                    helpPanel.classList.toggle('hidden');
                });

                // パネル外クリックで閉じる
                document.addEventListener('click', (e) => {
                    if (!helpToggle.contains(e.target) && !helpPanel.contains(e.target)) {
                        helpPanel.classList.add('hidden');
                    }
                });
            }
        },

        switchView(view) {
            this.currentView = view;
            
            // ボタンの状態更新
            document.querySelectorAll('.view-toggle').forEach(btn => {
                btn.classList.remove('active', 'bg-white', 'text-emerald-600', 'shadow-sm');
                btn.classList.add('text-gray-600');
            });
            
            const activeBtn = document.getElementById(view + '-view');
            if (activeBtn) {
                activeBtn.classList.add('active', 'bg-white', 'text-emerald-600', 'shadow-sm');
                activeBtn.classList.remove('text-gray-600');
            }

            // コンテナの表示切り替え
            const gridContainer = document.getElementById('grid-container');
            const listContainer = document.getElementById('list-container');
            
            if (view === 'grid') {
                if (gridContainer) gridContainer.classList.remove('hidden');
                if (listContainer) listContainer.classList.add('hidden');
            } else {
                if (gridContainer) gridContainer.classList.add('hidden');
                if (listContainer) listContainer.classList.remove('hidden');
            }

            this.loadGrants();
        },

        // 都道府県展開トグル（修正版）
        togglePrefectures() {
            const allPrefectures = document.getElementById('all-prefectures');
            const toggleBtn = document.getElementById('toggle-prefectures');
            const toggleText = toggleBtn.querySelector('.toggle-text');
            const toggleIcon = toggleBtn.querySelector('.toggle-icon');

            if (allPrefectures && allPrefectures.classList.contains('hidden')) {
                allPrefectures.classList.remove('hidden');
                if (toggleText) toggleText.textContent = '都道府県を閉じる';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
            } else if (allPrefectures) {
                allPrefectures.classList.add('hidden');
                if (toggleText) toggleText.textContent = 'その他の都道府県を表示';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
            }
        },

        toggleCategories() {
            const moreCategories = document.getElementById('more-categories');
            const toggleBtn = document.getElementById('toggle-categories');
            const toggleText = toggleBtn.querySelector('.toggle-text');
            const toggleIcon = toggleBtn.querySelector('.toggle-icon');

            if (moreCategories && moreCategories.classList.contains('hidden')) {
                moreCategories.classList.remove('hidden');
                if (toggleText) toggleText.textContent = 'カテゴリを閉じる';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
            } else if (moreCategories) {
                moreCategories.classList.add('hidden');
                if (toggleText) toggleText.textContent = 'その他のカテゴリを表示';
                if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
            }
        },

        updatePrefectureFilters() {
            const checkboxes = document.querySelectorAll('.prefecture-checkbox:checked');
            const names = [];
            const slugs = [];
            Array.from(checkboxes).forEach(cb => {
                const label = cb.closest('label');
                const nameSpan = label ? label.querySelector('span') : null;
                names.push(nameSpan ? nameSpan.textContent.trim() : cb.value);
                slugs.push(cb.value);
            });
            this.filters.prefectures = names;
            this.filters.prefectureSlugs = slugs;
            this.updateFilterDisplay();
            this.loadGrants();
        },

        updateCategoryFilters() {
            const checkboxes = document.querySelectorAll('.category-checkbox:checked');
            const names = [];
            const slugs = [];
            Array.from(checkboxes).forEach(cb => {
                const label = cb.closest('label');
                const nameSpan = label ? label.querySelector('span') : null;
                names.push(nameSpan ? nameSpan.textContent.trim() : cb.value);
                slugs.push(cb.value);
            });
            this.filters.categories = names;
            this.filters.categorySlugs = slugs;
            this.updateFilterDisplay();
            this.loadGrants();
        },

        updateStatusFilters() {
            const checkboxes = document.querySelectorAll('input[name="status[]"]:checked');
            this.filters.status = Array.from(checkboxes).map(cb => cb.value);
            this.updateFilterDisplay();
            this.loadGrants();
        },

        updateFilterDisplay() {
            const container = document.getElementById('active-filters');
            if (!container) return;
            
            container.innerHTML = '';

            // 都道府県フィルターバッジ
            this.filters.prefectures.forEach(pref => {
                const badge = this.createFilterBadge(pref, 'prefecture', '📍');
                container.appendChild(badge);
            });

            // カテゴリフィルターバッジ
            this.filters.categories.forEach(cat => {
                const badge = this.createFilterBadge(cat, 'category', '🏷️');
                container.appendChild(badge);
            });

            // 金額フィルターバッジ
            if (this.filters.amount) {
                const amountLabels = {
                    '0-100': '100万円以下',
                    '100-500': '100万円〜500万円',
                    '500-1000': '500万円〜1000万円',
                    '1000+': '1000万円以上'
                };
                const badge = this.createFilterBadge(amountLabels[this.filters.amount], 'amount', '💰');
                container.appendChild(badge);
            }

            // ステータスフィルターバッジ
            this.filters.status.forEach(status => {
                const statusLabels = {
                    'active': '募集中',
                    'upcoming': '募集予定',
                    'closed': '募集終了'
                };
                const badge = this.createFilterBadge(statusLabels[status], 'status', '⏰');
                container.appendChild(badge);
            });
        },

        createFilterBadge(text, type, icon) {
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-800 text-sm rounded-full animate-fade-in';
            badge.innerHTML = `
                <span>${icon}</span>
                <span>${this.escapeHtml(text)}</span>
                <button class="ml-1 hover:bg-emerald-200 rounded-full w-4 h-4 flex items-center justify-center transition-colors" onclick="GrantArchive.removeFilter('${type}', '${this.escapeHtml(text)}')">
                    <i class="fas fa-times text-xs"></i>
                </button>
            `;
            return badge;
        },

        removeFilter(type, value) {
            // フィルター削除処理
            if (type === 'prefecture') {
                this.filters.prefectures = this.filters.prefectures.filter(p => p !== value);
                document.querySelectorAll('.prefecture-checkbox').forEach(cb => {
                    const label = cb.closest('label');
                    const nameSpan = label.querySelector('span');
                    const prefName = nameSpan ? nameSpan.textContent.trim() : cb.value;
                    if (prefName === value) cb.checked = false;
                });
            } else if (type === 'category') {
                this.filters.categories = this.filters.categories.filter(c => c !== value);
                document.querySelectorAll('.category-checkbox').forEach(cb => {
                    const label = cb.closest('label');
                    const nameSpan = label.querySelector('span');
                    const catName = nameSpan ? nameSpan.textContent.trim() : cb.value;
                    if (catName === value) cb.checked = false;
                });
            } else if (type === 'amount') {
                this.filters.amount = '';
                document.querySelectorAll('input[name="amount"]').forEach(rb => {
                    rb.checked = rb.value === '';
                });
            } else if (type === 'status') {
                const statusValues = {
                    '募集中': 'active',
                    '募集予定': 'upcoming',
                    '募集終了': 'closed'
                };
                const statusValue = statusValues[value];
                this.filters.status = this.filters.status.filter(s => s !== statusValue);
                document.querySelectorAll('input[name="status[]"]').forEach(cb => {
                    if (cb.value === statusValue) cb.checked = false;
                });
            }

            this.updateFilterDisplay();
            this.loadGrants();
        },

        clearFilters() {
            // フォームリセット
            const searchInput = document.getElementById('grant-search');
            if (searchInput) searchInput.value = '';
            
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('input[type="radio"]').forEach(rb => {
                rb.checked = rb.value === '';
            });

            // フィルター初期化
            this.filters = {
                search: '',
                categories: [],
                prefectures: [],
                amount: '',
                status: [],
                sort: 'date_desc'
            };

            // クイックフィルターリセット
            document.querySelectorAll('.quick-filter').forEach(btn => {
                btn.classList.remove('active', 'bg-emerald-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            
            const allFilter = document.querySelector('.quick-filter[data-filter="all"]');
            if (allFilter) {
                allFilter.classList.add('active', 'bg-emerald-600', 'text-white');
                allFilter.classList.remove('bg-gray-200', 'text-gray-700');
            }

            this.updateFilterDisplay();
            this.loadGrants();
        },

        resetSearch() {
            this.clearFilters();
            this.hideNoResults();
            this.hideError();
        },

        async loadGrants() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();
            this.hideNoResults();
            this.hideError();

            try {
                const ajaxUrl = (typeof gi_ajax !== 'undefined' && gi_ajax.ajax_url) ? gi_ajax.ajax_url : (typeof giAjax !== 'undefined' ? giAjax.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>');

                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'gi_load_grants',
                        nonce: giAjax.nonce,
                        search: this.filters.search,
                        amount: this.filters.amount,
                        sort: this.filters.sort,
                        view: this.currentView,
                        page: this.currentPage,
                        categories: JSON.stringify(this.filters.categorySlugs || []),
                        prefectures: JSON.stringify(this.filters.prefectureSlugs || []),
                        status: JSON.stringify(this.filters.status)
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    this.renderGrants(data.data);
                } else {
                    throw new Error(data.data?.message || '検索中にエラーが発生しました');
                }
            } catch (error) {
                console.error('Load grants error:', error);
                this.showError(error.message || '通信エラーが発生しました');
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        },

        renderGrants(data) {
            const { grants, found_posts, query_info } = data;
            
            // 結果数表示更新
            this.updateResultsHeader(found_posts, query_info);
            this.updateFilterStats(found_posts);

            if (!grants || grants.length === 0) {
                this.showNoResults();
                return;
            }

            // コンテナ表示
            this.showGrantsContainer();

            // カード表示
            if (this.currentView === 'grid') {
                this.renderGridView(grants);
            } else {
                this.renderListView(grants);
            }
        },

        renderGridView(grants) {
            const container = document.getElementById('grid-container');
            if (!container) return;
            
            container.innerHTML = grants.map(grant => this.createGrantCard(grant)).join('');
            this.animateCards();
        },

        renderListView(grants) {
            const container = document.getElementById('list-container');
            if (!container) return;
            
            container.innerHTML = grants.map(grant => this.createGrantListItem(grant)).join('');
            this.animateCards();
        },

        createGrantCard(grant) {
            return `
                <div class="grant-card bg-white rounded-xl shadow-sm border hover:shadow-lg transition-all duration-300 overflow-hidden animate-fade-in-up">
                    <div class="relative">
                        ${grant.thumbnail ? `
                            <img src="${this.escapeHtml(grant.thumbnail)}" alt="${this.escapeHtml(grant.title)}" class="w-full h-48 object-cover" loading="lazy">
                        ` : `
                            <div class="w-full h-48 bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                <i class="fas fa-coins text-4xl text-white"></i>
                            </div>
                        `}
                        
                        <div class="absolute top-3 left-3">
                            ${this.getStatusBadge(grant.status)}
                        </div>
                        
                        <button class="favorite-btn absolute top-3 right-3 w-8 h-8 bg-white bg-opacity-90 hover:bg-opacity-100 rounded-full flex items-center justify-center transition-all duration-200 ${grant.is_favorite ? 'text-red-500' : 'text-gray-400'}"
                                data-post-id="${grant.id}"
                                title="${grant.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'}">
                            <i class="fas fa-heart text-sm"></i>
                        </button>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-3">
                            ${grant.prefecture ? `
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full mr-2 mb-1">
                                    📍 ${this.escapeHtml(grant.prefecture)}
                                </span>
                            ` : ''}
                            
                            ${grant.main_category ? `
                                <span class="inline-block px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full mb-1">
                                    ${this.escapeHtml(grant.main_category)}
                                </span>
                            ` : ''}
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 line-clamp-2 hover:text-emerald-600 transition-colors">
                            <a href="${this.escapeHtml(grant.permalink)}" class="focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">${this.escapeHtml(grant.title)}</a>
                        </h3>
                        
                        <div class="flex items-center gap-2 mb-3">
                            <div class="text-2xl font-bold text-emerald-600">
                                ${this.escapeHtml(grant.amount)}
                            </div>
                            <span class="text-sm text-gray-500">万円</span>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                            ${this.escapeHtml(grant.excerpt)}
                        </p>
                        
                        <div class="space-y-2 mb-4 text-sm">
                            ${grant.organization ? `
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-building w-4"></i>
                                    <span>${this.escapeHtml(grant.organization)}</span>
                                </div>
                            ` : ''}
                            
                            ${grant.deadline ? `
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-calendar w-4"></i>
                                    <span>締切: ${this.escapeHtml(grant.deadline)}</span>
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="${this.escapeHtml(grant.permalink)}" 
                               class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                詳細を見る
                            </a>
                            <button class="share-btn px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-600 hover:text-gray-700 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    data-url="${this.escapeHtml(grant.permalink)}"
                                    data-title="${this.escapeHtml(grant.title)}"
                                    title="共有">
                                <i class="fas fa-share-alt text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },

        createGrantListItem(grant) {
            return `
                <div class="grant-list-item bg-white rounded-xl shadow-sm border hover:shadow-md transition-all duration-300 p-6 animate-fade-in-up">
                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="lg:w-48 lg:shrink-0">
                            ${grant.thumbnail ? `
                                <img src="${this.escapeHtml(grant.thumbnail)}" alt="${this.escapeHtml(grant.title)}" class="w-full h-32 lg:h-24 object-cover rounded-lg" loading="lazy">
                            ` : `
                                <div class="w-full h-32 lg:h-24 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-coins text-2xl text-white"></i>
                                </div>
                            `}
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3 flex-wrap">
                                        ${this.getStatusBadge(grant.status)}
                                        
                                        ${grant.prefecture ? `
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                📍 ${this.escapeHtml(grant.prefecture)}
                                            </span>
                                        ` : ''}
                                        
                                        ${grant.main_category ? `
                                            <span class="px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">
                                                ${this.escapeHtml(grant.main_category)}
                                            </span>
                                        ` : ''}
                                        
                                        <button class="favorite-btn text-gray-400 hover:text-red-500 transition-colors ${grant.is_favorite ? 'text-red-500' : ''} focus:outline-none focus:ring-2 focus:ring-red-500 rounded p-1"
                                                data-post-id="${grant.id}"
                                                title="${grant.is_favorite ? 'お気に入りから削除' : 'お気に入りに追加'}">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2 hover:text-emerald-600 transition-colors">
                                        <a href="${this.escapeHtml(grant.permalink)}" class="focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">${this.escapeHtml(grant.title)}</a>
                                    </h3>
                                    
                                    <p class="text-gray-600 mb-4 line-clamp-2">
                                        ${this.escapeHtml(grant.excerpt)}
                                    </p>
                                    
                                    <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                                        ${grant.organization ? `
                                            <div class="flex items-center gap-1">
                                                <i class="fas fa-building"></i>
                                                <span>${this.escapeHtml(grant.organization)}</span>
                                            </div>
                                        ` : ''}
                                        
                                        ${grant.deadline ? `
                                            <div class="flex items-center gap-1">
                                                <i class="fas fa-calendar"></i>
                                                <span>締切: ${this.escapeHtml(grant.deadline)}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                                
                                <div class="lg:w-48 lg:text-right">
                                    <div class="mb-4">
                                        <div class="text-3xl font-bold text-emerald-600">
                                            ${this.escapeHtml(grant.amount)}
                                            <span class="text-lg text-gray-500">万円</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex lg:flex-col gap-2">
                                        <a href="${this.escapeHtml(grant.permalink)}" 
                                           class="flex-1 lg:flex-none bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2 px-4 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            詳細を見る
                                        </a>
                                        <button class="share-btn px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-600 hover:text-gray-700 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                                data-url="${this.escapeHtml(grant.permalink)}"
                                                data-title="${this.escapeHtml(grant.title)}"
                                                title="共有">
                                            <i class="fas fa-share-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        getStatusBadge(status) {
            const badges = {
                'active': '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">募集中</span>',
                'upcoming': '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">募集予定</span>',
                'closed': '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">募集終了</span>'
            };
            return badges[status] || '';
        },

        updateResultsHeader(count, queryInfo) {
            const header = document.getElementById('results-count');
            const query = document.getElementById('results-query');
            
            if (header) {
                header.textContent = `${count || 0}件の助成金が見つかりました`;
            }
            
            if (query) {
                let queryText = [];
                if (this.filters.search) queryText.push(`「${this.filters.search}」`);
                if ((this.filters.prefectures || []).length > 0) queryText.push(`${this.filters.prefectures.join('、')}`);
                if ((this.filters.categories || []).length > 0) queryText.push(`${this.filters.categories.join('、')}`);
                
                query.textContent = queryText.length > 0 ? `${queryText.join(' / ')}の検索結果` : '';
            }
        },

        updateFilterStats(count) {
            const statsCount = document.getElementById('filter-stats-count');
            if (statsCount) {
                statsCount.textContent = count || 0;
            }
        },

        showLoading() {
            const spinner = document.getElementById('loading-spinner');
            const mainLoading = document.getElementById('main-loading');
            
            if (spinner) spinner.classList.remove('hidden');
            if (mainLoading) mainLoading.classList.remove('hidden');
        },

        hideLoading() {
            const spinner = document.getElementById('loading-spinner');
            const mainLoading = document.getElementById('main-loading');
            
            if (spinner) spinner.classList.add('hidden');
            if (mainLoading) mainLoading.classList.add('hidden');
        },

        showNoResults() {
            const grantsContainer = document.getElementById('grants-container');
            const noResults = document.getElementById('no-results');
            
            if (grantsContainer) grantsContainer.classList.add('hidden');
            if (noResults) noResults.classList.remove('hidden');
        },

        hideNoResults() {
            const grantsContainer = document.getElementById('grants-container');
            const noResults = document.getElementById('no-results');
            
            if (grantsContainer) grantsContainer.classList.remove('hidden');
            if (noResults) noResults.classList.add('hidden');
        },

        showGrantsContainer() {
            const grantsContainer = document.getElementById('grants-container');
            const noResults = document.getElementById('no-results');
            const errorDisplay = document.getElementById('error-display');
            
            if (grantsContainer) grantsContainer.classList.remove('hidden');
            if (noResults) noResults.classList.add('hidden');
            if (errorDisplay) errorDisplay.classList.add('hidden');
        },

        showError(message) {
            console.error('Grant Archive Error:', message);
            
            const grantsContainer = document.getElementById('grants-container');
            const noResults = document.getElementById('no-results');
            const errorDisplay = document.getElementById('error-display');
            const errorMsg = document.getElementById('error-message');
            
            if (grantsContainer) grantsContainer.classList.add('hidden');
            if (noResults) noResults.classList.add('hidden');
            if (errorDisplay) errorDisplay.classList.remove('hidden');
            if (errorMsg) errorMsg.textContent = message;
            
            this.updateResultsHeader(0, {});
            this.updateFilterStats(0);
        },

        hideError() {
            const errorDisplay = document.getElementById('error-display');
            if (errorDisplay) errorDisplay.classList.add('hidden');
        },

        animateCards() {
            // カードのアニメーション
            const cards = document.querySelectorAll('.grant-card, .grant-list-item');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // お気に入りボタン
            document.querySelectorAll('.favorite-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleFavorite(btn);
                });
            });

            // 共有ボタン
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.shareGrant(btn);
                });
            });
        },

        async toggleFavorite(btn) {
            const postId = btn.dataset.postId;
            
            try {
                const ajaxUrl = (typeof gi_ajax !== 'undefined' && gi_ajax.ajax_url) ? gi_ajax.ajax_url : (typeof giAjax !== 'undefined' ? giAjax.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>');

                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'gi_toggle_favorite',
                        nonce: giAjax.nonce,
                        post_id: postId
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    if (data.data.action === 'added') {
                        btn.classList.add('text-red-500');
                        btn.classList.remove('text-gray-400');
                        btn.title = 'お気に入りから削除';
                    } else {
                        btn.classList.remove('text-red-500');
                        btn.classList.add('text-gray-400');
                        btn.title = 'お気に入りに追加';
                    }
                    
                    // アニメーション効果
                    btn.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        btn.style.transform = 'scale(1)';
                    }, 200);
                } else {
                    throw new Error(data.data?.message || 'お気に入りの更新に失敗しました');
                }
            } catch (error) {
                console.error('Favorite toggle error:', error);
                // ユーザーに優しいエラー表示
                this.showToast('お気に入りの更新中にエラーが発生しました', 'error');
            }
        },

        shareGrant(btn) {
            const url = btn.dataset.url;
            const title = btn.dataset.title;
            
            if (navigator.share) {
                // Web Share API対応
                navigator.share({
                    title: title,
                    url: url
                }).catch(console.error);
            } else {
                // フォールバック: クリップボードにコピー
                navigator.clipboard.writeText(url).then(() => {
                    this.showToast('URLをクリップボードにコピーしました', 'success');
                }).catch(() => {
                    // さらなるフォールバック: 新しいウィンドウで開く
                    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`, '_blank');
                });
            }
        },

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transition-all duration-300 transform translate-x-full ${
                type === 'error' ? 'bg-red-600' : 
                type === 'success' ? 'bg-green-600' : 'bg-emerald-600'
            }`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // アニメーション
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // 自動削除
            setTimeout(() => {
                toast.style.transform = 'translateX(full)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        },

        initializeHelpers() {
            // Enterキーでの検索
            const searchInput = document.getElementById('grant-search');
            if (searchInput) {
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.loadGrants();
                    }
                });
            }

            // Escapeキーでヘルプパネルを閉じる
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const helpPanel = document.getElementById('help-panel');
                    if (helpPanel && !helpPanel.classList.contains('hidden')) {
                        helpPanel.classList.add('hidden');
                    }
                }
            });
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // グローバルに公開（フィルターバッジから呼び出すため）
    window.GrantArchive = GrantArchive;

    // 初期化
    GrantArchive.init();
});
</script>

<!-- CSSスタイル -->
<style>
/* Grant Archive Perfect Version Styles */
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

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.animate-bounce-gentle {
    animation: bounceGentle 2s ease-in-out infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounceGentle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* レスポンシブ調整 */
@media (max-width: 640px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .text-4xl { font-size: 2rem; }
    .text-5xl { font-size: 2.5rem; }
    .text-6xl { font-size: 3rem; }
    
    .lg\:w-80 {
        width: 100%;
    }
    
    .sticky {
        position: relative;
    }
}

/* フォーカス表示の改善 */
*:focus {
    outline: 2px solid #10b981;
    outline-offset: 2px;
}

/* ダークモード対応 */
/* Removed forced dark-mode overrides to maintain light emerald/teal theme. Use Tailwind 'dark' variants where needed. */
/* (dark-mode overrides removed) */
    


/* プリント対応 */
@media print {
    .fixed, .sticky {
        position: static;
    }
    
    .shadow-lg, .shadow-xl {
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }
    
    .hidden {
        display: none !important;
    }
}
</style>

<?php get_footer(); ?>