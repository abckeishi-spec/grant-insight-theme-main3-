<?php
/**
 * Case Study Card v3 - 成功事例カード
 * 
 * 添付画像を参考にHTML構造をブラッシュアップ
 * モジュール化されたコンポーネント設計で再利用性と拡張性を向上
 */

// 必要なデータを取得
$case_id = get_the_ID();
$company_name = gi_safe_get_meta($case_id, 'company_name', '');
$industry = gi_safe_get_meta($case_id, 'industry', '');
$company_size = gi_safe_get_meta($case_id, 'company_size', '');
$roi = gi_safe_get_meta($case_id, 'roi', '');
$implementation_period = gi_safe_get_meta($case_id, 'implementation_period', '');
$success_score = gi_safe_get_meta($case_id, 'success_score', '');
$grant_amount = gi_safe_get_meta($case_id, 'grant_amount', '');
$used_grants = gi_safe_get_meta($case_id, 'used_grants', '');
$achievements = gi_safe_get_meta($case_id, 'achievements', '');
$challenges = gi_safe_get_meta($case_id, 'challenges', '');

// カテゴリとタグを取得
$categories = get_the_terms($case_id, 'case_study_category');
$tags = get_the_terms($case_id, 'case_study_tag');
?>

<article class="case-study-card" 
         data-case-id="<?php echo gi_safe_attr($case_id); ?>"
         data-industry="<?php echo gi_safe_attr($industry); ?>"
         data-roi="<?php echo gi_safe_attr($roi); ?>"
         data-success-score="<?php echo gi_safe_attr($success_score); ?>"
         itemscope 
         itemtype="https://schema.org/Article">

    <!-- カードヘッダー（パープルグラデーション背景） -->
    <header class="case-study-card-header">
        <!-- 業種・規模バッジ -->
        <div class="card-info-badges">
            <?php if ($industry) : ?>
            <span class="industry-badge">
                <span class="badge-icon">🏭</span>
                <?php echo gi_safe_escape($industry); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($company_size) : ?>
            <span class="size-badge">
                <span class="badge-icon">👥</span>
                <?php echo gi_safe_escape($company_size); ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- お気に入りボタン -->
        <button class="favorite-btn" 
                onclick="toggleCaseFavorite(<?php echo $case_id; ?>)"
                aria-label="お気に入りに追加">
            <span class="favorite-icon">♡</span>
        </button>

        <!-- 獲得額表示 -->
        <div class="amount-display">
            <span class="amount-label">獲得額</span>
            <span class="amount-value" itemprop="about">
                <?php if ($grant_amount) : ?>
                    <?php echo gi_safe_number_format($grant_amount); ?><span class="amount-unit">万円</span>
                <?php else : ?>
                    <span class="amount-na">非公開</span>
                <?php endif; ?>
            </span>
        </div>
    </header>

    <!-- カードボディ -->
    <div class="case-study-card-body">
        <!-- タイトルと会社名 -->
        <div class="card-title-section">
            <h3 class="case-study-title" itemprop="headline">
                <a href="<?php the_permalink(); ?>" class="title-link">
                    <?php the_title(); ?>
                </a>
            </h3>
            
            <?php if ($company_name) : ?>
            <div class="case-company" itemprop="author" itemscope itemtype="https://schema.org/Organization">
                <span class="company-icon">🏢</span>
                <span itemprop="name"><?php echo gi_safe_escape($company_name); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 概要 -->
        <div class="case-study-excerpt" itemprop="description">
            <?php
            $excerpt = get_the_excerpt();
            if (mb_strlen($excerpt) > 100) {
                $excerpt = mb_substr($excerpt, 0, 100) . '...';
            }
            echo gi_safe_escape($excerpt);
            ?>
        </div>

        <!-- 成果指標 -->
        <div class="case-study-metrics">
            <?php if ($roi) : ?>
            <div class="metric-item roi-metric">
                <span class="metric-label">投資効果</span>
                <div class="metric-value">
                    <span class="roi-value">ROI <?php echo gi_safe_escape($roi); ?>%</span>
                    <div class="roi-bar">
                        <div class="roi-fill" style="width: <?php echo min(100, $roi); ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($implementation_period) : ?>
            <div class="metric-item period-metric">
                <span class="metric-label">実施期間</span>
                <span class="metric-value"><?php echo gi_safe_escape($implementation_period); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 主な成果 -->
        <div class="main-results">
            <h4 class="results-title">主な成果</h4>
            <div class="results-content">
                <?php if ($achievements && is_array($achievements)) : ?>
                <ul class="achievements-list">
                    <?php foreach (array_slice($achievements, 0, 3) as $achievement) : ?>
                    <li class="achievement-item">
                        <span class="achievement-icon">✅</span>
                        <?php echo gi_safe_escape($achievement); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p class="results-text">
                    <?php
                    $content = get_the_content();
                    $content = wp_strip_all_tags($content);
                    if (mb_strlen($content) > 80) {
                        $content = mb_substr($content, 0, 80) . '...';
                    }
                    echo gi_safe_escape($content);
                    ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 使用した助成金 -->
        <?php if ($used_grants && is_array($used_grants)) : ?>
        <div class="used-grants">
            <h4 class="grants-title">活用した助成金</h4>
            <div class="grants-list">
                <?php foreach (array_slice($used_grants, 0, 2) as $grant) : ?>
                <span class="grant-tag">
                    <?php echo gi_safe_escape($grant); ?>
                </span>
                <?php endforeach; ?>
                <?php if (count($used_grants) > 2) : ?>
                <span class="grant-more">+<?php echo count($used_grants) - 2; ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- カテゴリタグ */
        <div class="case-study-tags">
            <?php if ($categories && !is_wp_error($categories)) : ?>
                <?php foreach (array_slice($categories, 0, 2) as $category) : ?>
                <span class="tag tag-category">
                    #<?php echo gi_safe_escape($category->name); ?>
                </span>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($tags && !is_wp_error($tags)) : ?>
                <?php foreach (array_slice($tags, 0, 2) as $tag) : ?>
                <span class="tag tag-feature">
                    #<?php echo gi_safe_escape($tag->name); ?>
                </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- カードフッター -->
    <footer class="case-study-card-footer">
        <div class="card-actions">
            <a href="<?php the_permalink(); ?>" 
               class="action-btn primary">
                <span class="btn-icon">👁️</span>
                事例を見る
            </a>
            
            <button class="action-btn secondary" 
                    onclick="downloadCaseStudy(<?php echo $case_id; ?>)">
                <span class="btn-icon">📥</span>
                ダウンロード
            </button>
            
            <button class="action-btn tertiary" 
                    onclick="shareCaseStudy(<?php echo $case_id; ?>)">
                <span class="btn-icon">📤</span>
            </button>
        </div>
        
        <!-- 成功度表示 -->
        <?php if ($success_score) : ?>
        <div class="success-rating">
            <span class="rating-label">成功度</span>
            <div class="rating-stars">
                <?php
                $full_stars = floor($success_score);
                for ($i = 1; $i <= 5; $i++) {
                    $class = $i <= $full_stars ? 'star-filled' : 'star-empty';
                    echo '<span class="star ' . $class . '">★</span>';
                }
                ?>
            </div>
            <span class="rating-text"><?php echo $success_score; ?>/5</span>
        </div>
        <?php endif; ?>
    </footer>

    <!-- ホバー時の追加情報 -->
    <div class="card-hover-info">
        <div class="hover-content">
            <h4 class="hover-title">詳細情報</h4>
            <ul class="hover-details">
                <?php if ($company_name) : ?>
                <li><strong>企業名:</strong> <?php echo gi_safe_escape($company_name); ?></li>
                <?php endif; ?>
                
                <?php if ($industry) : ?>
                <li><strong>業種:</strong> <?php echo gi_safe_escape($industry); ?></li>
                <?php endif; ?>
                
                <?php if ($company_size) : ?>
                <li><strong>規模:</strong> <?php echo gi_safe_escape($company_size); ?></li>
                <?php endif; ?>
                
                <?php if ($implementation_period) : ?>
                <li><strong>実施期間:</strong> <?php echo gi_safe_escape($implementation_period); ?></li>
                <?php endif; ?>
                
                <?php if ($roi) : ?>
                <li><strong>ROI:</strong> <?php echo gi_safe_escape($roi); ?>%</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</article>

<style>
/* Case Study Card v3 Styles */
.case-study-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #E5E7EB;
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.case-study-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* カードヘッダー（パープルグラデーション） */
.case-study-card-header {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
    color: white;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.case-study-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    pointer-events: none;
}

.card-info-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    position: relative;
    z-index: 1;
}

.industry-badge,
.size-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.amount-display {
    text-align: center;
    position: relative;
    z-index: 1;
}

.amount-label {
    display: block;
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 4px;
}

.amount-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}

.amount-unit {
    font-size: 16px;
    font-weight: 500;
}

.amount-na {
    font-size: 16px;
    opacity: 0.8;
}

/* カードボディ */
.case-study-card-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.case-study-title {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.3;
}

.title-link {
    color: #1F2937;
    text-decoration: none;
    transition: color 0.2s;
}

.title-link:hover {
    color: #8B5CF6;
}

.case-company {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #6B7280;
    font-weight: 500;
}

.company-icon {
    font-size: 12px;
}

.case-study-excerpt {
    color: #4B5563;
    font-size: 14px;
    line-height: 1.5;
}

.case-study-metrics {
    display: grid;
    gap: 12px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.metric-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.metric-label {
    font-size: 12px;
    color: #6B7280;
    font-weight: 500;
}

.roi-metric .metric-value {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.roi-value {
    font-size: 14px;
    font-weight: 700;
    color: #8B5CF6;
}

.roi-bar {
    width: 60px;
    height: 6px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
}

.roi-fill {
    height: 100%;
    background: linear-gradient(90deg, #8B5CF6 0%, #7C3AED 100%);
    transition: width 0.3s;
}

.period-metric .metric-value {
    font-size: 14px;
    font-weight: 600;
    color: #4B5563;
}

.main-results {
    margin-top: 8px;
}

.results-title {
    font-size: 12px;
    color: #6B7280;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.achievements-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.achievement-item {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 13px;
    color: #4B5563;
    margin-bottom: 4px;
    line-height: 1.4;
}

.achievement-icon {
    font-size: 12px;
    color: #10B981;
    margin-top: 1px;
}

.results-text {
    font-size: 13px;
    color: #4B5563;
    line-height: 1.4;
    margin: 0;
}

.used-grants {
    margin-top: 8px;
}

.grants-title {
    font-size: 12px;
    color: #6B7280;
    font-weight: 600;
    margin: 0 0 8px 0;
}

.grants-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.grant-tag {
    padding: 4px 8px;
    background: #F3E8FF;
    color: #7C3AED;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.grant-more {
    padding: 4px 8px;
    background: #E5E7EB;
    color: #6B7280;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.case-study-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.tag {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.tag-category {
    background: #FEF3C7;
    color: #D97706;
}

.tag-feature {
    background: #DBEAFE;
    color: #1D4ED8;
}

/* カードフッター */
.case-study-card-footer {
    padding: 16px 20px;
    border-top: 1px solid #E5E7EB;
    background: #F9FAFB;
}

.card-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}

.action-btn {
    flex: 1;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border: none;
}

.action-btn.primary {
    background: #8B5CF6;
    color: white;
}

.action-btn.primary:hover {
    background: #7C3AED;
    transform: translateY(-1px);
}

.action-btn.secondary {
    background: white;
    color: #8B5CF6;
    border: 1px solid #8B5CF6;
}

.action-btn.secondary:hover {
    background: #8B5CF6;
    color: white;
}

.action-btn.tertiary {
    background: #E5E7EB;
    color: #6B7280;
    min-width: 40px;
    flex: none;
}

.action-btn.tertiary:hover {
    background: #D1D5DB;
    color: #4B5563;
}

.success-rating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
}

.rating-label {
    color: #6B7280;
    font-weight: 500;
}

.rating-stars {
    display: flex;
    gap: 2px;
}

.star {
    font-size: 14px;
}

.star-filled {
    color: #F59E0B;
}

.star-empty {
    color: #D1D5DB;
}

.rating-text {
    color: #4B5563;
    font-weight: 600;
}

/* ホバー情報 */
.card-hover-info {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #E5E7EB;
    border-top: none;
    border-radius: 0 0 16px 16px;
    padding: 16px 20px;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s;
    pointer-events: none;
    z-index: 10;
}

.case-study-card:hover .card-hover-info {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.hover-title {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: #1F2937;
}

.hover-details {
    list-style: none;
    margin: 0;
    padding: 0;
}

.hover-details li {
    font-size: 12px;
    color: #6B7280;
    margin-bottom: 4px;
}

.hover-details strong {
    color: #4B5563;
}

/* レスポンシブ */
@media (max-width: 768px) {
    .case-study-card-header {
        padding: 16px;
    }
    
    .case-study-card-body {
        padding: 16px;
        gap: 12px;
    }
    
    .case-study-title {
        font-size: 16px;
    }
    
    .amount-value {
        font-size: 24px;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .action-btn.tertiary {
        min-width: auto;
        flex: 1;
    }
}
</style>

<script>
function toggleCaseFavorite(caseId) {
    // お気に入り機能の実装
    console.log('Toggling favorite for case study:', caseId);
    
    // アイコンの切り替え
    const btn = event.target.closest('.favorite-btn');
    const icon = btn.querySelector('.favorite-icon');
    
    if (icon.textContent === '♡') {
        icon.textContent = '♥';
        btn.style.background = 'rgba(239, 68, 68, 0.2)';
    } else {
        icon.textContent = '♡';
        btn.style.background = 'rgba(255, 255, 255, 0.2)';
    }
}

function downloadCaseStudy(caseId) {
    // ダウンロード機能の実装
    console.log('Downloading case study:', caseId);
}

function shareCaseStudy(caseId) {
    // シェア機能の実装
    console.log('Sharing case study:', caseId);
    
    if (navigator.share) {
        navigator.share({
            title: document.querySelector(`[data-case-id="${caseId}"] .case-study-title`).textContent,
            url: document.querySelector(`[data-case-id="${caseId}"] .title-link`).href
        });
    }
}
</script>

