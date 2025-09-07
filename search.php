<?php
/**
 * The template for displaying search results pages
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<main id="primary" class="site-main min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4 py-8">
        
        <!-- Search Header -->
        <header class="page-header bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="flex items-center justify-between flex-wrap">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php if (have_posts()): ?>
                            検索結果: <span class="text-blue-600">"<?php echo esc_html(get_search_query()); ?>"</span>
                        <?php else: ?>
                            検索結果が見つかりません
                        <?php endif; ?>
                    </h1>
                    <?php if (have_posts()): ?>
                    <p class="text-gray-600">
                        <?php 
                        global $wp_query;
                        $total = $wp_query->found_posts;
                        echo sprintf('%d 件の結果が見つかりました', $total);
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- New Search Form -->
                <div class="mt-4 lg:mt-0">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" 
                          class="flex shadow-md rounded-lg overflow-hidden">
                        <input type="search" 
                               name="s" 
                               value="<?php echo esc_attr(get_search_query()); ?>"
                               placeholder="新しい検索..."
                               class="px-4 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <?php if (have_posts()): ?>
            
            <!-- Search Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-600">絞り込み:</span>
                        <?php
                        // Get post types in results
                        $post_types = array();
                        while (have_posts()) {
                            the_post();
                            $post_types[get_post_type()] = get_post_type_object(get_post_type())->labels->singular_name;
                        }
                        rewind_posts();
                        
                        if (count($post_types) > 1): ?>
                        <select class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                onchange="filterPostType(this.value)">
                            <option value="">すべて</option>
                            <?php foreach ($post_types as $type => $label): ?>
                            <option value="<?php echo esc_attr($type); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">表示順:</span>
                        <select class="px-3 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="relevance">関連度順</option>
                            <option value="date">新着順</option>
                            <option value="title">タイトル順</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Search Results Grid -->
            <div class="search-results grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="search-results">
                <?php
                /* Start the Loop */
                while (have_posts()):
                    the_post();
                    $post_type = get_post_type();
                    ?>
                    <article id="post-<?php the_ID(); ?>" 
                             <?php post_class('bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200 overflow-hidden'); ?>
                             data-post-type="<?php echo esc_attr($post_type); ?>">
                        
                        <?php if (has_post_thumbnail()): ?>
                        <div class="aspect-w-16 aspect-h-9">
                            <?php the_post_thumbnail('medium_large', array(
                                'class' => 'w-full h-48 object-cover'
                            )); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <!-- Post Type Badge -->
                            <div class="mb-3">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full 
                                    <?php
                                    echo $post_type === 'grant' ? 'bg-green-100 text-green-800' :
                                         ($post_type === 'tool' ? 'bg-purple-100 text-purple-800' :
                                         ($post_type === 'case_study' ? 'bg-blue-100 text-blue-800' :
                                         'bg-gray-100 text-gray-800'));
                                    ?>">
                                    <?php echo esc_html(get_post_type_object($post_type)->labels->singular_name); ?>
                                </span>
                            </div>
                            
                            <!-- Title with Search Term Highlight -->
                            <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-blue-600 transition-colors">
                                    <?php 
                                    $title = get_the_title();
                                    $search_term = get_search_query();
                                    // Highlight search term in title
                                    if (!empty($search_term)) {
                                        $title = preg_replace('/(' . preg_quote($search_term, '/') . ')/i', 
                                                            '<mark class="bg-yellow-200">$1</mark>', $title);
                                    }
                                    echo wp_kses_post($title);
                                    ?>
                                </a>
                            </h2>
                            
                            <!-- Excerpt with Search Term Highlight -->
                            <div class="text-gray-600 text-sm mb-4 line-clamp-3">
                                <?php 
                                $excerpt = wp_trim_words(get_the_excerpt(), 20, '...');
                                if (!empty($search_term)) {
                                    $excerpt = preg_replace('/(' . preg_quote($search_term, '/') . ')/i', 
                                                          '<mark class="bg-yellow-200">$1</mark>', $excerpt);
                                }
                                echo wp_kses_post($excerpt);
                                ?>
                            </div>
                            
                            <!-- Meta Information -->
                            <div class="flex items-center justify-between text-xs text-gray-500 pt-4 border-t">
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date(); ?>
                                </time>
                                <a href="<?php the_permalink(); ?>" 
                                   class="text-blue-600 hover:text-blue-700 font-medium">
                                    詳細を見る →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <nav class="pagination-nav mt-12">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <?php
                    the_posts_pagination(array(
                        'mid_size'  => 2,
                        'prev_text' => '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>前へ',
                        'next_text' => '次へ<svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
                    ));
                    ?>
                </div>
            </nav>

        <?php else: ?>
            
            <!-- No Results Found -->
            <div class="no-results bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="max-w-md mx-auto">
                    <!-- Icon -->
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        検索結果が見つかりませんでした
                    </h2>
                    
                    <p class="text-gray-600 mb-8">
                        「<?php echo esc_html(get_search_query()); ?>」に一致する投稿は見つかりませんでした。
                    </p>
                    
                    <!-- Search Suggestions -->
                    <div class="text-left bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-gray-700 mb-3">検索のヒント:</h3>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li>• キーワードのスペルを確認してください</li>
                            <li>• より一般的なキーワードを試してください</li>
                            <li>• キーワードの数を減らしてみてください</li>
                            <li>• 異なるキーワードを使用してみてください</li>
                        </ul>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="<?php echo esc_url(home_url('/')); ?>" 
                           class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            ホームに戻る
                        </a>
                        <?php if (get_post_type_object('grant')): ?>
                        <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                           class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            助成金一覧を見る
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<script>
// Filter posts by type
function filterPostType(type) {
    const posts = document.querySelectorAll('#search-results article');
    posts.forEach(post => {
        if (!type || post.dataset.postType === type) {
            post.style.display = 'block';
        } else {
            post.style.display = 'none';
        }
    });
}
</script>

<?php get_footer(); ?>