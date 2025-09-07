<?php
/**
 * Front Page Categories Section - Tailwind CSS Play CDN完全対応版
 * カテゴリー別助成金検索セクション - 最適化版
 * 
 * @package Grant_Insight_Perfect
 * @version 5.0-tailwind-perfect
 */
?>

<section class="py-16 lg:py-24 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <!-- セクションヘッダー -->
            <div class="text-center mb-16 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" data-animation="fadeInUp">
                <div class="inline-flex items-center gap-3 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 text-emerald-700 px-6 py-3 rounded-full text-sm font-bold mb-6 backdrop-blur-sm border border-emerald-200">
                    <i class="fas fa-th-large text-emerald-500"></i>
                    <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent font-black">カテゴリー別検索</span>
                </div>
                
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    業種・目的別
                    <span class="text-gradient bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                        助成金検索
                    </span>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    あなたの事業分野に最適な助成金・補助金を効率的に見つけましょう
                </p>
            </div>

            <!-- メインカテゴリーグリッド -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                
                <!-- IT・デジタル化 -->
                <div class="category-card group bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-blue-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.1s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-laptop-code text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-blue-700 transition-colors duration-300">IT・デジタル化</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            システム導入、DX推進、Web制作、AI・IoT活用など、デジタル化に関する助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-blue-600">主な制度:</span> IT導入補助金、DX推進補助金
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-blue-600">掲載件数:</span> 
                                <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">125件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-blue-600">最大金額:</span> 
                                <span class="text-blue-700 font-bold">3,000万円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'it', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>

                <!-- ものづくり -->
                <div class="category-card group bg-gradient-to-br from-orange-50 to-red-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-orange-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.2s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-orange-500 to-red-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-cogs text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-orange-700 transition-colors duration-300">ものづくり</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            設備導入、技術開発、生産性向上、品質改善など、製造業向けの助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-orange-600">主な制度:</span> ものづくり補助金、設備導入補助金
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-orange-600">掲載件数:</span> 
                                <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-bold">98件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-orange-600">最大金額:</span> 
                                <span class="text-orange-700 font-bold">1億円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'manufacturing', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white font-medium rounded-lg hover:from-orange-600 hover:to-red-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>

                <!-- 創業・起業 -->
                <div class="category-card group bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-green-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.3s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-rocket text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-green-700 transition-colors duration-300">創業・起業</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            新規事業立ち上げ、スタートアップ支援、事業承継など、起業家向けの助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-green-600">主な制度:</span> 創業補助金、起業家支援金
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-green-600">掲載件数:</span> 
                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold">87件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-green-600">最大金額:</span> 
                                <span class="text-green-700 font-bold">2,000万円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'startup', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>

                <!-- 小規模事業者 -->
                <div class="category-card group bg-gradient-to-br from-purple-50 to-pink-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-purple-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.4s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-store text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-purple-700 transition-colors duration-300">小規模事業者</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            販路開拓、集客力向上、事業継続、働き方改革など、小規模事業者向けの助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-purple-600">主な制度:</span> 持続化補助金、販路開拓支援金
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-purple-600">掲載件数:</span> 
                                <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-bold">156件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-purple-600">最大金額:</span> 
                                <span class="text-purple-700 font-bold">200万円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'small-business', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-medium rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>

                <!-- 環境・省エネ -->
                <div class="category-card group bg-gradient-to-br from-teal-50 to-cyan-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-teal-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.5s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-leaf text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-teal-700 transition-colors duration-300">環境・省エネ</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            省エネ設備導入、環境対策、再生可能エネルギー、脱炭素など、環境関連の助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-teal-600">主な制度:</span> 省エネ補助金、グリーン投資減税
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-teal-600">掲載件数:</span> 
                                <span class="inline-block bg-teal-100 text-teal-800 px-2 py-1 rounded-full text-xs font-bold">73件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-teal-600">最大金額:</span> 
                                <span class="text-teal-700 font-bold">5,000万円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'environment', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-600 text-white font-medium rounded-lg hover:from-teal-600 hover:to-cyan-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>

                <!-- 雇用・人材 -->
                <div class="category-card group bg-gradient-to-br from-yellow-50 to-amber-100 rounded-2xl p-8 hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-500 border border-yellow-200 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 cursor-pointer" data-animation="fadeInUp" style="animation-delay: 0.6s;">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-r from-yellow-500 to-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-users text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 group-hover:text-yellow-700 transition-colors duration-300">雇用・人材</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            人材育成、雇用創出、働き方改革、研修支援など、人材関連の助成金
                        </p>
                        <div class="space-y-2 mb-6">
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-yellow-600">主な制度:</span> 雇用調整助成金、人材開発支援助成金
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-yellow-600">掲載件数:</span> 
                                <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-bold">94件</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                <span class="font-medium text-yellow-600">最大金額:</span> 
                                <span class="text-yellow-700 font-bold">1,000万円</span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('category', 'employment', get_post_type_archive_link('grant'))); ?>" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-yellow-500 to-amber-600 text-white font-medium rounded-lg hover:from-yellow-600 hover:to-amber-700 transition-all duration-300 transform hover:scale-105 group-hover:shadow-lg">
                            <i class="fas fa-search mr-2"></i>
                            詳細を見る
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 地域別検索セクション -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-8 lg:p-12 mb-16 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" data-animation="fadeInUp" style="animation-delay: 0.7s;">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-3 bg-gradient-to-r from-gray-500/10 to-slate-500/10 text-gray-700 px-6 py-3 rounded-full text-sm font-bold mb-4">
                        <i class="fas fa-map-marker-alt text-gray-600"></i>
                        <span class="font-black">地域別検索</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">都道府県別助成金検索</h3>
                    <p class="text-gray-600">
                        お住まいの地域特有の助成金・補助金もご確認いただけます
                    </p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php
                    $regions = array(
                        'tokyo' => array('name' => '東京都', 'count' => '156'),
                        'osaka' => array('name' => '大阪府', 'count' => '98'), 
                        'kanagawa' => array('name' => '神奈川県', 'count' => '87'),
                        'aichi' => array('name' => '愛知県', 'count' => '76'),
                        'fukuoka' => array('name' => '福岡県', 'count' => '65'),
                        'hokkaido' => array('name' => '北海道', 'count' => '54'),
                        'sendai' => array('name' => '宮城県', 'count' => '43'),
                        'hiroshima' => array('name' => '広島県', 'count' => '38'),
                        'shizuoka' => array('name' => '静岡県', 'count' => '32'),
                        'kyoto' => array('name' => '京都府', 'count' => '29'),
                        'hyogo' => array('name' => '兵庫県', 'count' => '41'),
                        'chiba' => array('name' => '千葉県', 'count' => '37')
                    );
                    
                    foreach ($regions as $region_code => $region_data) :
                    ?>
                        <a href="<?php echo esc_url(add_query_arg('region', $region_code, get_post_type_archive_link('grant'))); ?>" 
                           class="region-link group bg-white hover:bg-emerald-50 border border-gray-200 hover:border-emerald-300 rounded-lg p-4 text-center transition-all duration-300 transform hover:scale-105 hover:shadow-md">
                            <div class="text-sm font-medium text-gray-700 group-hover:text-emerald-600 mb-1">
                                <?php echo esc_html($region_data['name']); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <span class="inline-block bg-gray-100 group-hover:bg-emerald-100 text-gray-600 group-hover:text-emerald-700 px-2 py-1 rounded-full font-bold">
                                    <?php echo esc_html($region_data['count']); ?>件
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-8">
                    <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-600 to-slate-700 text-white font-medium rounded-lg hover:from-gray-700 hover:to-slate-800 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        すべての地域を見る
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <!-- カテゴリー統計・実績セクション -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" data-animation="fadeInUp" style="animation-delay: 0.8s;">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">カテゴリー別統計・実績</h3>
                    <p class="text-gray-600">数字が証明する信頼と実績</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center p-4 bg-emerald-50 rounded-xl">
                        <div class="text-3xl font-bold text-emerald-600 mb-2">633件</div>
                        <div class="text-sm text-gray-600">総掲載件数</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-xl">
                        <div class="text-3xl font-bold text-blue-600 mb-2">47都道府県</div>
                        <div class="text-sm text-gray-600">対応地域</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-xl">
                        <div class="text-3xl font-bold text-orange-600 mb-2">毎日更新</div>
                        <div class="text-sm text-gray-600">情報更新頻度</div>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-xl">
                        <div class="text-3xl font-bold text-purple-600 mb-2">95%</div>
                        <div class="text-sm text-gray-600">採択率</div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="text-center mt-8">
                    <a href="#grant-search" 
                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold text-lg rounded-xl hover:from-emerald-700 hover:to-teal-700 transform transition-all duration-300 hover:scale-105 shadow-xl hover:shadow-2xl">
                        <i class="fas fa-rocket mr-3"></i>
                        今すぐカテゴリー検索を始める
                        <i class="fas fa-arrow-right ml-3"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tailwind CSS Play CDN対応 Custom Styles -->
<style>
/* Categories Section Enhancement */
.category-card {
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.category-card:hover::before {
    left: 100%;
}

/* Region link hover animation */
.region-link {
    position: relative;
    overflow: hidden;
}

.region-link::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(16, 185, 129, 0.1);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: all 0.3s ease;
}

.region-link:hover::after {
    width: 100%;
    height: 100%;
}

/* Statistics animation */
.statistics-card {
    transition: all 0.3s ease;
}

.statistics-card:hover {
    transform: translateY(-4px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .category-card {
        padding: 1.5rem;
    }
    
    .w-20.h-20 {
        width: 4rem;
        height: 4rem;
    }
    
    .text-3xl {
        font-size: 2rem;
    }
}

@media (max-width: 640px) {
    .grid.grid-cols-2.md\\:grid-cols-4.lg\\:grid-cols-6 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
    }
    
    .region-link {
        padding: 0.75rem;
    }
}
</style>

<!-- Tailwind CSS Play CDN対応 JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tailwind Scroll Animation System
    const initTailwindCategoriesAnimations = () => {
        const animatedElements = document.querySelectorAll('.animate-on-scroll');
        
        if ('IntersectionObserver' in window && animatedElements.length > 0) {
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const animationType = element.dataset.animation || 'fadeInUp';
                        
                        // Tailwind animation classes
                        switch(animationType) {
                            case 'fadeInUp':
                                element.classList.remove('opacity-0', 'translate-y-8');
                                element.classList.add('opacity-100', 'translate-y-0');
                                break;
                        }
                        
                        animationObserver.unobserve(element);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(el => {
                animationObserver.observe(el);
            });
        }
    };

    // Category Card Interactions
    const initCategoryCardInteractions = () => {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('shadow-2xl', 'border-opacity-50');
            });
            
            card.addEventListener('mouseleave', function() {
                this.classList.remove('shadow-2xl', 'border-opacity-50');
            });
            
            card.addEventListener('click', function() {
                const link = this.querySelector('a');
                if (link) {
                    link.click();
                }
            });
        });
    };

    // Region Link Analytics
    const initRegionLinkTracking = () => {
        const regionLinks = document.querySelectorAll('.region-link');
        
        regionLinks.forEach(link => {
            link.addEventListener('click', function() {
                const regionName = this.querySelector('div').textContent;
                console.log('地域クリック:', regionName);
                
                // Google Analytics tracking (if available)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'region_search',
                        event_label: regionName,
                        value: 1
                    });
                }
            });
        });
    };

    // Statistics Counter Animation
    const initStatisticsAnimation = () => {
        const statNumbers = document.querySelectorAll('.statistics-card .text-3xl');
        
        if ('IntersectionObserver' in window && statNumbers.length > 0) {
            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const text = element.textContent;
                        const number = parseInt(text.replace(/[^\d]/g, ''));
                        
                        if (!isNaN(number) && number > 0) {
                            animateStatNumber(element, number, text);
                        }
                        
                        statsObserver.unobserve(element);
                    }
                });
            }, {
                threshold: 0.5
            });

            statNumbers.forEach(stat => {
                statsObserver.observe(stat);
            });
        }
    };

    // Animate statistic numbers
    const animateStatNumber = (element, target, originalText) => {
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
                element.textContent = originalText;
            } else {
                const currentNumber = Math.floor(current);
                element.textContent = originalText.replace(/\d+/, currentNumber);
            }
        }, 16);
    };

    // Initialize all components
    initTailwindCategoriesAnimations();
    initCategoryCardInteractions();
    initRegionLinkTracking();
    initStatisticsAnimation();
    
    // Debug log
    console.log('🎨 Grant Insight Categories Section - 最適化版 Loaded');
    console.log('📊 Categories: 6カテゴリー + 地域検索 + 統計情報');
    console.log('🎯 Features: カードインタラクション + アニメーション + 追跡機能');
});
</script>
