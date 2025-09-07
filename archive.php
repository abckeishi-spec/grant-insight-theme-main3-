<?php
/**
 * The template for displaying archive pages
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<main id="primary" class="site-main">
    <div class="container mx-auto px-4 py-8">
        
        <?php if ( have_posts() ) : ?>

            <header class="page-header mb-8">
                <h1 class="page-title text-3xl font-bold">
                    <?php the_archive_title(); ?>
                </h1>
                <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
            </header>

            <div class="posts-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                /* Start the Loop */
                while ( have_posts() ) :
                    the_post();
                    
                    // Try to load template part, with fallback
                    if ( locate_template( 'template-parts/cards/grant-card-v3.php' ) ) {
                        get_template_part( 'template-parts/cards/grant-card-v3' );
                    } else {
                        // Fallback content
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded-lg shadow-md p-6'); ?>>
                            <h2 class="entry-title text-xl font-semibold mb-2">
                                <a href="<?php the_permalink(); ?>" rel="bookmark">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <div class="entry-summary">
                                <?php the_excerpt(); ?>
                            </div>
                            <div class="entry-meta text-sm text-gray-600 mt-4">
                                <?php echo get_the_date(); ?>
                            </div>
                        </article>
                        <?php
                    }
                    
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => __( '前へ', 'grant-insight' ),
                'next_text' => __( '次へ', 'grant-insight' ),
            ) );
            ?>

        <?php else : ?>

            <section class="no-results not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php _e( '何も見つかりませんでした', 'grant-insight' ); ?></h1>
                </header>
                <div class="page-content">
                    <p><?php _e( 'お探しのコンテンツは見つかりませんでした。検索をお試しください。', 'grant-insight' ); ?></p>
                    <?php get_search_form(); ?>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
?>