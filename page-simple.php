<?php
/**
 * Simplified Page Template - for debugging
 */

get_header(); ?>

<div style="max-width: 800px; margin: 50px auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            
            <h1 style="font-size: 2em; margin-bottom: 20px; color: #333;">
                <?php the_title(); ?>
            </h1>
            
            <div style="font-size: 1.1em; line-height: 1.8; color: #444;">
                <?php the_content(); ?>
            </div>
            
        <?php endwhile; ?>
    <?php else : ?>
        
        <h2 style="text-align: center; color: #999;">ページが見つかりません</h2>
        
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>