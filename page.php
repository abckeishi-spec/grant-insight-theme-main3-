<?php
/**
 * The template for displaying all pages
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
                
                <!-- Page Header -->
                <header class="entry-header" style="margin-bottom: 30px; text-align: center;">
                    <h1 class="entry-title" style="font-size: 2.5em; margin-bottom: 10px;">
                        <?php the_title(); ?>
                    </h1>
                </header>
                
                <!-- Featured Image -->
                <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail" style="margin-bottom: 30px;">
                    <?php the_post_thumbnail('large', array('style' => 'width: 100%; height: auto;')); ?>
                </div>
                <?php endif; ?>
                
                <!-- Page Content -->
                <div class="entry-content" style="font-size: 1.1em; line-height: 1.8;">
                    <?php
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">ページ: ',
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
                
                <!-- Page Footer -->
                <?php if (get_edit_post_link()) : ?>
                <footer class="entry-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <?php
                    edit_post_link(
                        sprintf(
                            wp_kses(
                                '編集 <span class="screen-reader-text">%s</span>',
                                array(
                                    'span' => array(
                                        'class' => array(),
                                    ),
                                )
                            ),
                            get_the_title()
                        ),
                        '<span class="edit-link">',
                        '</span>'
                    );
                    ?>
                </footer>
                <?php endif; ?>
                
            </article>
            
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