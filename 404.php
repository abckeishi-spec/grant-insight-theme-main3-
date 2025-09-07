<?php
/**
 * The template for displaying 404 pages (not found)
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<!-- Tailwind CSS は header.php で読み込み済み -->

<main id="primary" class="site-main min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4 py-16">
        <section class="error-404 not-found max-w-3xl mx-auto text-center">
            
            <!-- 404 Icon -->
            <div class="mb-8 animate-bounce">
                <svg class="w-32 h-32 mx-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Error Message -->
            <header class="page-header mb-8">
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <h2 class="text-3xl font-semibold text-gray-700 mb-4">
                    ページが見つかりません
                </h2>
                <p class="text-lg text-gray-600">
                    お探しのページは移動したか、削除された可能性があります。
                </p>
            </header>

            <!-- Search Form -->
            <div class="page-content mb-12">
                <div class="max-w-md mx-auto mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">
                        キーワードで検索してみましょう
                    </h3>
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" 
                          class="flex shadow-lg rounded-lg overflow-hidden">
                        <input type="search" 
                               name="s" 
                               placeholder="検索キーワードを入力..."
                               class="flex-1 px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" 
                                class="px-6 py-3 bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Helpful Links -->
                <div class="helpful-links">
                    <h3 class="text-xl font-semibold text-gray-700 mb-6">
                        役立つリンク
                    </h3>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="<?php echo esc_url(home_url('/')); ?>" 
                           class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg shadow hover:shadow-md transition-shadow duration-200">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            ホームページ
                        </a>
                        
                        <?php if (get_post_type_object('grant')): ?>
                        <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                           class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg shadow hover:shadow-md transition-shadow duration-200">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            助成金一覧
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(home_url('/contact/')); ?>" 
                           class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-lg shadow hover:shadow-md transition-shadow duration-200">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            お問い合わせ
                        </a>
                    </div>
                </div>

                <!-- Recent Posts -->
                <?php
                $recent_posts = wp_get_recent_posts(array(
                    'numberposts' => 3,
                    'post_status' => 'publish'
                ));
                
                if (!empty($recent_posts)): ?>
                <div class="recent-posts mt-12">
                    <h3 class="text-xl font-semibold text-gray-700 mb-6">
                        最新の投稿
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                        <?php foreach ($recent_posts as $post): ?>
                        <a href="<?php echo get_permalink($post['ID']); ?>" 
                           class="block bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 p-6">
                            <h4 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                <?php echo esc_html($post['post_title']); ?>
                            </h4>
                            <p class="text-sm text-gray-600">
                                <?php echo get_the_date('Y年n月j日', $post['ID']); ?>
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<style>
/* Animation for 404 page */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.error-404 svg {
    animation: float 3s ease-in-out infinite;
}
</style>

<?php get_footer(); ?>