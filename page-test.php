<?php
/**
 * Test Page Template - Minimal version to debug display issues
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        
        <?php if (have_posts()) : ?>
            
            <?php while (have_posts()) : the_post(); ?>
                
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    
                    <header class="entry-header" style="margin-bottom: 30px;">
                        <h1 style="font-size: 2em; color: #333;">
                            <?php the_title(); ?>
                        </h1>
                    </header>
                    
                    <div class="entry-content" style="font-size: 1.1em; line-height: 1.6; color: #555;">
                        <?php
                        // シンプルにコンテンツを表示
                        the_content();
                        ?>
                    </div>
                    
                    <footer class="entry-footer" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                        <p style="color: #777;">
                            投稿日: <?php echo get_the_date(); ?> | 
                            投稿者: <?php the_author(); ?>
                        </p>
                    </footer>
                    
                </article>
                
            <?php endwhile; ?>
            
        <?php else : ?>
            
            <div style="text-align: center; padding: 50px;">
                <h2 style="color: #666;">コンテンツが見つかりません</h2>
                <p style="color: #999;">申し訳ございません。表示するコンテンツがありません。</p>
            </div>
            
        <?php endif; ?>
        
    </div>
</main>

<?php get_footer(); ?>