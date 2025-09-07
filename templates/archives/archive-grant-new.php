<?php
/**
 * Archive Grant Template (AI Optimized Version)
 * 
 * 新しいアーキテクチャを使用した助成金アーカイブテンプレート
 * - リポジトリパターンでデータ取得
 * - コンポーネントベースでUI構築
 * - クラスベースでロジック管理
 */

get_header();

// リポジトリを使用してデータを取得
$grantRepository = new \GrantInsight\Repositories\GrantRepository();

// 検索条件の構築
$search_criteria = [
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1
];

// URLパラメータから検索条件を追加
if (!empty($_GET['search'])) {
    $search_criteria['s'] = sanitize_text_field($_GET['search']);
}

if (!empty($_GET['prefecture'])) {
    $search_criteria['tax_query'][] = [
        'taxonomy' => 'prefecture',
        'field' => 'slug',
        'terms' => sanitize_text_field($_GET['prefecture'])
    ];
}

if (!empty($_GET['category'])) {
    $search_criteria['tax_query'][] = [
        'taxonomy' => 'grant_category',
        'field' => 'slug',
        'terms' => sanitize_text_field($_GET['category'])
    ];
}

// データを取得
$search_results = $grantRepository->searchWithPagination($search_criteria);
$grants = $search_results['grants'];
$total_count = $search_results['total'];
$max_pages = $search_results['pages'];
$current_page = $search_results['current_page'];

// 人気の助成金も取得
$popular_grants = $grantRepository->findPopular(5);
?>

<main class="main-content py-8 lg:py-12">
    <div class="container mx-auto px-4">
        
        <!-- ページヘッダー -->
        <div class="page-header mb-8">
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                助成金一覧
            </h1>
            
            <?php if ($total_count > 0): ?>
                <p class="text-gray-600">
                    <?php echo number_format($total_count); ?>件の助成金が見つかりました
                </p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- メインコンテンツ -->
            <div class="lg:col-span-3">
                
                <!-- 検索フォーム -->
                <div class="search-section mb-8">
                    <?php gi_component('forms/search-form', [
                        'show_filters' => true,
                        'css_class' => 'bg-white rounded-lg shadow-lg p-6'
                    ]); ?>
                </div>

                <!-- 助成金一覧 -->
                <div class="grants-grid">
                    <?php if (!empty($grants)): ?>
                        
                        <!-- 結果ヘッダー -->
                        <div class="results-header flex justify-between items-center mb-6">
                            <div class="results-info">
                                <span class="text-sm text-gray-600">
                                    <?php echo number_format(($current_page - 1) * 12 + 1); ?>-<?php echo number_format(min($current_page * 12, $total_count)); ?>件目 
                                    / <?php echo number_format($total_count); ?>件中
                                </span>
                            </div>
                            
                            <div class="view-toggle">
                                <!-- ビュー切り替えボタン（将来的な拡張用） -->
                            </div>
                        </div>

                        <!-- グリッド表示 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                            <?php foreach ($grants as $grant): ?>
                                <?php gi_component('cards/grant-card', [
                                    'grant' => $grant,
                                    'size' => 'medium',
                                    'show_excerpt' => true
                                ]); ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- ページネーション -->
                        <?php if ($max_pages > 1): ?>
                            <div class="pagination-wrapper">
                                <?php
                                echo paginate_links([
                                    'total' => $max_pages,
                                    'current' => $current_page,
                                    'format' => '?paged=%#%',
                                    'prev_text' => '<i class="fas fa-chevron-left"></i> 前へ',
                                    'next_text' => '次へ <i class="fas fa-chevron-right"></i>',
                                    'class' => 'pagination flex justify-center space-x-2'
                                ]);
                                ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        
                        <!-- 結果なし -->
                        <div class="no-results text-center py-12">
                            <div class="max-w-md mx-auto">
                                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-700 mb-2">
                                    助成金が見つかりませんでした
                                </h3>
                                <p class="text-gray-600 mb-6">
                                    検索条件を変更して再度お試しください。
                                </p>
                                
                                <?php gi_component('ui/button', [
                                    'text' => '検索条件をリセット',
                                    'url' => get_post_type_archive_link('grant'),
                                    'type' => 'primary',
                                    'icon' => 'fas fa-redo'
                                ]); ?>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>

            <!-- サイドバー -->
            <div class="lg:col-span-1">
                <div class="sidebar space-y-6">
                    
                    <!-- 人気の助成金 -->
                    <?php if (!empty($popular_grants)): ?>
                        <div class="widget popular-grants bg-white rounded-lg shadow-lg p-6">
                            <h3 class="widget-title text-lg font-bold text-gray-900 mb-4">
                                <i class="fas fa-fire text-orange-500 mr-2"></i>
                                人気の助成金
                            </h3>
                            
                            <div class="space-y-4">
                                <?php foreach ($popular_grants as $popular_grant): ?>
                                    <?php gi_component('cards/grant-card', [
                                        'grant' => $popular_grant,
                                        'size' => 'small',
                                        'show_excerpt' => false,
                                        'css_class' => 'border border-gray-200'
                                    ]); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 締切が近い助成金 -->
                    <?php
                    $deadline_soon_grants = $grantRepository->findDeadlineSoon(30, 5);
                    if (!empty($deadline_soon_grants)):
                    ?>
                        <div class="widget deadline-soon bg-white rounded-lg shadow-lg p-6">
                            <h3 class="widget-title text-lg font-bold text-gray-900 mb-4">
                                <i class="fas fa-clock text-red-500 mr-2"></i>
                                締切が近い助成金
                            </h3>
                            
                            <div class="space-y-4">
                                <?php foreach ($deadline_soon_grants as $deadline_grant): ?>
                                    <?php gi_component('cards/grant-card', [
                                        'grant' => $deadline_grant,
                                        'size' => 'small',
                                        'show_excerpt' => false,
                                        'css_class' => 'border border-red-200'
                                    ]); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 統計情報 -->
                    <?php
                    $statistics = $grantRepository->getStatistics();
                    ?>
                    <div class="widget statistics bg-white rounded-lg shadow-lg p-6">
                        <h3 class="widget-title text-lg font-bold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                            統計情報
                        </h3>
                        
                        <div class="stats-grid space-y-3">
                            <div class="stat-item flex justify-between">
                                <span class="text-gray-600">総助成金数</span>
                                <span class="font-semibold"><?php echo number_format($statistics['total_count']); ?>件</span>
                            </div>
                            
                            <div class="stat-item flex justify-between">
                                <span class="text-gray-600">今月の新規</span>
                                <span class="font-semibold text-green-600"><?php echo number_format($statistics['this_month_count']); ?>件</span>
                            </div>
                            
                            <div class="stat-item flex justify-between">
                                <span class="text-gray-600">締切間近</span>
                                <span class="font-semibold text-red-600"><?php echo number_format($statistics['deadline_soon_count']); ?>件</span>
                            </div>
                            
                            <?php if ($statistics['average_amount'] > 0): ?>
                                <div class="stat-item flex justify-between">
                                    <span class="text-gray-600">平均助成額</span>
                                    <span class="font-semibold"><?php echo \GrantInsight\Helpers\Formatting::formatAmountMan($statistics['average_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>

