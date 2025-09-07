<?php
/**
 * The template for displaying the footer
 * フッターファイル（修正版 - リンク切れ解消）
 */

// 必要なヘルパー関数を定義
if (!function_exists('gi_get_sns_urls')) {
    function gi_get_sns_urls() {
        return [
            'twitter' => get_theme_mod('sns_twitter_url', ''),
            'facebook' => get_theme_mod('sns_facebook_url', ''),
            'linkedin' => get_theme_mod('sns_linkedin_url', ''),
            'instagram' => get_theme_mod('sns_instagram_url', ''),
            'youtube' => get_theme_mod('sns_youtube_url', '')
        ];
    }
}
?>

<!-- Tailwind CSS Play CDNの読み込み（ページのhead部分に配置） -->
<?php if (!wp_script_is('tailwind-cdn', 'enqueued')): ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                animation: {
                    'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    'blob': 'blob 7s infinite',
                    'fade-in-up': 'fadeInUp 0.6s ease-out',
                    'bounce-gentle': 'bounceGentle 2s ease-in-out infinite'
                },
                keyframes: {
                    blob: {
                        '0%': {
                            transform: 'translate(0px, 0px) scale(1)'
                        },
                        '33%': {
                            transform: 'translate(30px, -50px) scale(1.1)'
                        },
                        '66%': {
                            transform: 'translate(-20px, 20px) scale(0.9)'
                        },
                        '100%': {
                            transform: 'translate(0px, 0px) scale(1)'
                        }
                    },
                    fadeInUp: {
                        '0%': {
                            opacity: '0',
                            transform: 'translateY(30px)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'translateY(0)'
                        }
                    },
                    bounceGentle: {
                        '0%, 100%': {
                            transform: 'translateY(-5%)',
                            animationTimingFunction: 'cubic-bezier(0.8, 0, 1, 1)'
                        },
                        '50%': {
                            transform: 'translateY(0)',
                            animationTimingFunction: 'cubic-bezier(0, 0, 0.2, 1)'
                        }
                    }
                }
            }
        }
    }
</script>
<?php endif; ?>

</main><!-- #main -->

<!-- フッター -->
<footer class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white relative overflow-hidden">
    <!-- 背景装飾 -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative">
        <!-- メインフッターコンテンツ -->
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- ブランド情報 -->
                <div class="lg:col-span-1">
                    <div class="mb-6">
                        <?php if (has_custom_logo()) : ?>
                            <div class="mb-4">
                                <?php the_custom_logo(); ?>
                            </div>
                        <?php else : ?>
                            <h3 class="text-2xl font-bold bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent">
                                <?php bloginfo('name'); ?>
                            </h3>
                        <?php endif; ?>
                        
                        <p class="text-slate-300 leading-relaxed">
                            <?php 
                            $description = get_bloginfo('description');
                            echo $description ? esc_html($description) : '助成金・補助金情報を効率的に検索・管理できるプラットフォーム';
                            ?>
                        </p>
                    </div>
                    
                    <!-- SNSリンク -->
                    <?php 
                    $sns_urls = gi_get_sns_urls();
                    $has_sns = array_filter($sns_urls);
                    if ($has_sns): ?>
                    <div class="flex space-x-4">
                        <?php foreach ($sns_urls as $platform => $url): ?>
                            <?php if ($url): ?>
                                <a href="<?php echo esc_url($url); ?>" 
                                   class="w-10 h-10 bg-slate-700 hover:bg-emerald-600 rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
                                   target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-<?php echo esc_attr($platform); ?> text-sm"></i>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 助成金情報 -->
                <div>
                    <h4 class="text-lg font-semibold mb-6 flex items-center">
                        <i class="fas fa-coins mr-3 text-emerald-400"></i>助成金情報
                    </h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                助成金一覧
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg('grant_category', 'it', get_post_type_archive_link('grant'))); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                IT・デジタル
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg('grant_category', 'manufacturing', get_post_type_archive_link('grant'))); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                製造業
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg('grant_category', 'startup', get_post_type_archive_link('grant'))); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                スタートアップ
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg('grant_category', 'environment', get_post_type_archive_link('grant'))); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                環境・エネルギー
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- ツール・サービス -->
                <div>
                    <h4 class="text-lg font-semibold mb-6 flex items-center">
                        <i class="fas fa-tools mr-3 text-emerald-400"></i>ツール・サービス
                    </h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="<?php echo esc_url(get_post_type_archive_link('tool')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                診断ツール
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(get_post_type_archive_link('case_study')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                成功事例
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/search/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                高度検索
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                お問い合わせ
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- サポート情報 -->
                <div>
                    <h4 class="text-lg font-semibold mb-6 flex items-center">
                        <i class="fas fa-life-ring mr-3 text-emerald-400"></i>サポート
                    </h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="<?php echo esc_url(home_url('/about/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                サービスについて
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/faq/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                よくある質問
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/privacy/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                プライバシーポリシー
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/terms/')); ?>" 
                               class="text-slate-300 hover:text-emerald-400 transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right mr-2 text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                                利用規約
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ボトムバー -->
        <div class="border-t border-slate-700">
            <div class="container mx-auto px-4 py-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-slate-400 text-sm mb-4 md:mb-0">
                        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
                    </div>
                    
                    <div class="flex items-center space-x-6 text-sm">
                        <a href="<?php echo esc_url(home_url('/sitemap/')); ?>" 
                           class="text-slate-400 hover:text-emerald-400 transition-colors duration-300">
                            サイトマップ
                        </a>
                        <a href="<?php echo esc_url(home_url('/contact/')); ?>" 
                           class="text-slate-400 hover:text-emerald-400 transition-colors duration-300">
                            お問い合わせ
                        </a>
                        <div class="text-slate-500">
                            Version <?php echo defined('GI_THEME_VERSION') ? GI_THEME_VERSION : '1.0'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<!-- パフォーマンス監視スクリプト -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ページ読み込み時間の測定
    if (window.performance && window.performance.timing) {
        const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
        if (loadTime > 0) {
            console.log('Page load time:', loadTime + 'ms');
        }
    }
    
    // エラーハンドリング
    window.addEventListener('error', function(e) {
        console.error('JavaScript error:', e.error);
    });
});
</script>

</body>
</html>

