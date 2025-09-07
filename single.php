<?php
/**
 * The template for displaying all single posts
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<main id="primary" class="site-main" style="min-height: 500px;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <?php
        while (have_posts()) :
            the_post();
            ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                
                <!-- Post Header -->
                <header class="entry-header" style="margin-bottom: 30px;">
                    <h1 class="entry-title" style="font-size: 2.5em; margin-bottom: 10px;">
                        <?php the_title(); ?>
                    </h1>
                    
                    <div class="entry-meta" style="color: #666; font-size: 0.9em;">
                        <span>投稿日: <?php echo get_the_date(); ?></span> | 
                        <span>投稿者: <?php the_author(); ?></span> | 
                        <span>カテゴリ: <?php the_category(', '); ?></span>
                    </div>
                </header>
                
                <!-- Featured Image -->
                <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail" style="margin-bottom: 30px;">
                    <?php the_post_thumbnail('large', array('style' => 'width: 100%; height: auto;')); ?>
                </div>
                <?php endif; ?>
                
                <!-- Post Content -->
                <div class="entry-content" style="font-size: 1.1em; line-height: 1.8;">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">ページ: ',
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
                
                <!-- Post Footer -->
                <footer class="entry-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <?php if (has_tag()) : ?>
                    <div class="tags-links" style="margin-bottom: 20px;">
                        <strong>タグ:</strong> <?php the_tags('', ', ', ''); ?>
                    </div>
                    <?php endif; ?>
                </footer>
                
            </article>
            
            <!-- Post Navigation -->
            <nav class="post-navigation" style="margin-top: 40px; padding: 20px; background: #f5f5f5;">
                <div style="display: flex; justify-content: space-between;">
                    <div><?php previous_post_link('%link', '← %title'); ?></div>
                    <div><?php next_post_link('%link', '%title →'); ?></div>
                </div>
            </nav>
            
            <!-- Comments -->
            <?php
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
            ?>
            
        <?php endwhile; ?>
        
    </div>
</main>

<?php get_footer(); ?>