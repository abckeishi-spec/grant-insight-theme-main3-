<?php
/**
 * Template for displaying single grant posts
 * 
 * @package Grant_Insight_V4
 */

get_header(); ?>

<main id="primary" class="site-main" style="min-height: 500px;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <?php while (have_posts()) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                
                <!-- Grant Header -->
                <header class="entry-header" style="background: linear-gradient(to right, #3b82f6, #8b5cf6); color: white; padding: 40px; margin: -40px -20px 40px; border-radius: 10px;">
                    <h1 class="entry-title" style="font-size: 2.5em; margin-bottom: 20px;">
                        <?php the_title(); ?>
                    </h1>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <?php 
                        $grant_amount = get_field('max_amount');
                        if ($grant_amount): ?>
                        <div>
                            <span style="opacity: 0.9;">最大助成額</span><br>
                            <strong style="font-size: 1.5em;"><?php echo esc_html($grant_amount); ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $deadline = get_field('application_deadline');
                        if ($deadline): ?>
                        <div>
                            <span style="opacity: 0.9;">申請締切</span><br>
                            <strong style="font-size: 1.5em;"><?php echo esc_html($deadline); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </header>
                
                <!-- Grant Content -->
                <div class="entry-content" style="font-size: 1.1em; line-height: 1.8;">
                    
                    <!-- AI要約 -->
                    <?php 
                    $ai_summary = get_field('ai_summary');
                    if ($ai_summary): ?>
                    <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 20px; margin-bottom: 30px;">
                        <h2 style="color: #1e40af; margin-bottom: 10px;">🤖 AI要約</h2>
                        <div><?php echo wp_kses_post($ai_summary); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 詳細情報 -->
                    <?php 
                    $grant_details = get_field('grant_details');
                    if ($grant_details): ?>
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 30px; margin-bottom: 30px; border-radius: 8px;">
                        <h2 style="color: #111827; margin-bottom: 20px;">📖 詳細情報</h2>
                        <div><?php echo wp_kses_post($grant_details); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 対象者・条件 -->
                    <?php 
                    $eligibility = get_field('eligibility_criteria');
                    if ($eligibility): ?>
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 30px; margin-bottom: 30px; border-radius: 8px;">
                        <h2 style="color: #111827; margin-bottom: 20px;">✅ 対象者・条件</h2>
                        <div><?php echo wp_kses_post($eligibility); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 申請方法 -->
                    <?php 
                    $application_process = get_field('application_process');
                    if ($application_process): ?>
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 30px; margin-bottom: 30px; border-radius: 8px;">
                        <h2 style="color: #111827; margin-bottom: 20px;">📝 申請方法</h2>
                        <div><?php echo wp_kses_post($application_process); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 必要書類 -->
                    <?php 
                    $documents = get_field('required_documents');
                    if ($documents): ?>
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 30px; margin-bottom: 30px; border-radius: 8px;">
                        <h2 style="color: #111827; margin-bottom: 20px;">📄 必要書類</h2>
                        <div><?php echo wp_kses_post($documents); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- WordPressコンテンツ -->
                    <?php if (get_the_content()): ?>
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 30px; margin-bottom: 30px; border-radius: 8px;">
                        <h2 style="color: #111827; margin-bottom: 20px;">📌 その他の情報</h2>
                        <?php the_content(); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- アクションボタン -->
                    <div style="display: flex; gap: 20px; margin-top: 40px;">
                        <?php 
                        $official_url = get_field('official_url');
                        if ($official_url): ?>
                        <a href="<?php echo esc_url($official_url); ?>" target="_blank" 
                           style="background: #10b981; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; display: inline-block;">
                            🚀 申請サイトへ
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" 
                                style="background: #6b7280; color: white; padding: 15px 30px; border-radius: 8px; border: none; cursor: pointer;">
                            🖨️ 印刷する
                        </button>
                    </div>
                    
                </div>
                
                <!-- Grant Footer -->
                <footer class="entry-footer" style="margin-top: 60px; padding-top: 30px; border-top: 2px solid #e5e7eb;">
                    
                    <!-- カテゴリ・タグ -->
                    <div style="margin-bottom: 30px;">
                        <?php 
                        $categories = get_the_terms(get_the_ID(), 'grant_category');
                        if ($categories && !is_wp_error($categories)): ?>
                        <div style="margin-bottom: 15px;">
                            <strong>カテゴリ:</strong>
                            <?php foreach ($categories as $category): ?>
                            <a href="<?php echo esc_url(get_term_link($category)); ?>" 
                               style="background: #dbeafe; color: #1e40af; padding: 5px 15px; border-radius: 20px; text-decoration: none; margin-left: 10px;">
                                <?php echo esc_html($category->name); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $tags = get_the_terms(get_the_ID(), 'grant_tag');
                        if ($tags && !is_wp_error($tags)): ?>
                        <div>
                            <strong>タグ:</strong>
                            <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo esc_url(get_term_link($tag)); ?>" 
                               style="background: #f3f4f6; color: #374151; padding: 5px 15px; border-radius: 20px; text-decoration: none; margin-left: 10px;">
                                <?php echo esc_html($tag->name); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Navigation -->
                    <nav style="display: flex; justify-content: space-between; padding: 20px; background: #f9fafb; border-radius: 8px;">
                        <div><?php previous_post_link('%link', '← %title'); ?></div>
                        <div><?php next_post_link('%link', '%title →'); ?></div>
                    </nav>
                    
                </footer>
                
            </article>
            
        <?php endwhile; ?>
        
    </div>
</main>

<?php get_footer(); ?>