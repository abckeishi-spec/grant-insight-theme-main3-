<?php
/**
 * The template for displaying the footer
 * フッターファイル（AIアシスタント統合版 - Tailwind CSS Play CDN対応）
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
                },
                backgroundImage: {
                    'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                }
            }
        }
    }
</script>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php endif; ?>

    </main>


    <footer class="site-footer bg-emerald-50 text-gray-700 py-16 relative overflow-hidden">
        <!-- 背景アニメーション -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute w-64 h-64 bg-indigo-500 rounded-full -bottom-32 -left-32 mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
            <div class="absolute w-72 h-72 bg-purple-500 rounded-full -bottom-16 -right-16 mix-blend-multiply filter blur-xl opacity-70 animate-blob" style="animation-delay: 2s;"></div>
            <div class="absolute w-80 h-80 bg-pink-500 rounded-full -top-32 left-1/4 mix-blend-multiply filter blur-xl opacity-70 animate-blob" style="animation-delay: 4s;"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12">
                <!-- サイト情報 -->
                <div class="lg:col-span-2 animate-fade-in-up">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-4xl font-extrabold bg-gradient-to-r from-blue-400 via-purple-500 to-pink-500 bg-clip-text text-transparent mb-6 block hover:scale-105 transition-transform duration-200">
                        <?php bloginfo('name'); ?>
                    </a>
                    <p class="text-gray-400 leading-relaxed mb-6 text-base">
                        AIを活用した次世代の補助金・助成金プラットフォーム。あなたの事業に最適な情報を瞬時に発見し、成長を加速させます。
                    </p>
                    <div class="flex space-x-6">
                        <?php
                        $sns_urls = gi_get_sns_urls();
                        $sns_icons = [
                            'twitter' => 'fab fa-twitter',
                            'facebook' => 'fab fa-facebook-f', 
                            'linkedin' => 'fab fa-linkedin-in',
                            'instagram' => 'fab fa-instagram',
                            'youtube' => 'fab fa-youtube'
                        ];
                        ?>
                        <?php foreach ($sns_urls as $platform => $url): ?>
                            <?php if (!empty($url)): ?>
                                <a href="<?php echo esc_url($url); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="text-gray-400 hover:text-white transition-all duration-200 transform hover:scale-110 hover:-translate-y-1">
                                    <i class="<?php echo $sns_icons[$platform]; ?> text-2xl"></i>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 補助金を探す -->
                <div class="animate-fade-in-up" style="animation-delay: 0.1s;">
                    <h4 class="font-bold text-white mb-5 flex items-center text-lg">
                        <i class="fas fa-search mr-3 text-indigo-400"></i>補助金を探す
                    </h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                助成金一覧
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/?category=it')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                IT・デジタル化
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/?category=manufacturing')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                ものづくり・製造業
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/?category=startup')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                創業・起業
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/?category=employment')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                雇用・人材育成
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grants/?category=environment')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-indigo-300">
                                環境・省エネ
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- ツール・サービス -->
                <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
                    <h4 class="font-bold text-white mb-5 flex items-center text-lg">
                        <i class="fas fa-tools mr-3 text-emerald-400"></i>ツール・サービス
                    </h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li>
                            <a href="<?php echo esc_url(home_url('/tools/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-emerald-300">
                                診断ツール
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/case-studies/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-emerald-300">
                                成功事例
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/grant-tips/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-emerald-300">
                                申請のコツ
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/ai/chat/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-emerald-300">
                                AIチャット
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/experts/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-emerald-300">
                                専門家相談
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- サポート -->
                <div class="animate-fade-in-up" style="animation-delay: 0.3s;">
                    <h4 class="font-bold text-white mb-5 flex items-center text-lg">
                        <i class="fas fa-info-circle mr-3 text-purple-400"></i>サポート
                    </h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li>
                            <a href="<?php echo esc_url(home_url('/about/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-purple-300">
                                Grant Insightとは
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/faq/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-purple-300">
                                よくある質問
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-purple-300">
                                お問い合わせ
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/privacy/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-purple-300">
                                プライバシーポリシー
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/terms/')); ?>" 
                               class="hover:text-white transition-all duration-200 hover:translate-x-2 block transform hover:text-purple-300">
                                利用規約
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- フッター下部 -->
            <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center animate-fade-in-up" style="animation-delay: 0.4s;">
                <p class="text-gray-500 text-sm mb-4 md:mb-0">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
                </p>
                <div class="flex items-center space-x-6 text-sm text-gray-500">
                    <span class="flex items-center hover:text-emerald-400 transition-colors duration-200">
                        <i class="fas fa-shield-alt mr-2 text-emerald-400"></i>SSL暗号化通信
                    </span>
                    <span class="flex items-center hover:text-blue-400 transition-colors duration-200">
                        <i class="fas fa-lock mr-2 text-blue-400"></i>個人情報保護
                    </span>
                    <span class="flex items-center hover:text-purple-400 transition-colors duration-200">
                        <i class="fas fa-award mr-2 text-purple-400"></i>専門家監修
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>

</body>
</html>