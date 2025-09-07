<?php

namespace GrantInsight\Ajax;

use GrantInsight\Core\Logger;
use GrantInsight\Repositories\GrantRepository;

/**
 * Grant Analyzer Class
 * 
 * 助成金比較・分析ツールのバックエンド処理
 */
class GrantAnalyzer
{
    private static GrantRepository $grantRepository;

    /**
     * 初期化
     */
    public static function init(): void
    {
        self::$grantRepository = new GrantRepository();
        
        // AJAX処理を登録
        add_action('wp_ajax_gi_get_grants_for_analysis', [self::class, 'getGrantsForAnalysis']);
        add_action('wp_ajax_nopriv_gi_get_grants_for_analysis', [self::class, 'getGrantsForAnalysis']);
        
        add_action('wp_ajax_gi_analyze_grants', [self::class, 'analyzeGrants']);
        add_action('wp_ajax_nopriv_gi_analyze_grants', [self::class, 'analyzeGrants']);
        
        add_action('wp_ajax_gi_calculate_success_probability', [self::class, 'calculateSuccessProbability']);
        add_action('wp_ajax_nopriv_gi_calculate_success_probability', [self::class, 'calculateSuccessProbability']);
        
        add_action('wp_ajax_gi_calculate_roi', [self::class, 'calculateRoi']);
        add_action('wp_ajax_nopriv_gi_calculate_roi', [self::class, 'calculateRoi']);
    }

    /**
     * 分析用助成金データを取得
     */
    public static function getGrantsForAnalysis(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $limit = intval($_POST['limit'] ?? 20);

        try {
            $args = [
                'posts_per_page' => $limit,
                'post_status' => 'publish',
                's' => $search_term
            ];

            if (!empty($category)) {
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'grant_category',
                        'field' => 'slug',
                        'terms' => $category
                    ]
                ];
            }

            $grants = self::$grantRepository->search($args);
            $formatted_grants = [];

            foreach ($grants as $grant) {
                $formatted_grants[] = [
                    'id' => $grant['id'],
                    'title' => $grant['title'],
                    'amount' => get_field('max_amount_numeric', $grant['id']) ?: 0,
                    'amount_display' => get_field('max_amount', $grant['id']) ?: '未設定',
                    'deadline' => get_field('deadline_date', $grant['id']) ?: '',
                    'deadline_display' => get_field('deadline_text', $grant['id']) ?: '未設定',
                    'category' => $grant['category'],
                    'status' => get_field('application_status', $grant['id']) ?: 'unknown',
                    'difficulty' => self::calculateDifficulty($grant['id']),
                    'success_rate' => self::estimateSuccessRate($grant['id']),
                    'description' => wp_trim_words($grant['excerpt'], 30)
                ];
            }

            wp_send_json_success([
                'grants' => $formatted_grants,
                'total' => count($formatted_grants)
            ]);

        } catch (Exception $e) {
            Logger::error('Grant analysis data fetch error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'データの取得に失敗しました']);
        }
    }

    /**
     * 助成金を分析
     */
    public static function analyzeGrants(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $grant_ids = array_map('intval', $_POST['grant_ids'] ?? []);
        
        if (empty($grant_ids)) {
            wp_send_json_error(['message' => '分析する助成金を選択してください']);
        }

        try {
            $analysis_data = [];
            $comparison_data = [];

            foreach ($grant_ids as $grant_id) {
                $grant_data = self::getGrantAnalysisData($grant_id);
                $analysis_data[] = $grant_data;
                
                // 比較用データ
                $comparison_data[] = [
                    'label' => $grant_data['title'],
                    'amount' => $grant_data['amount'],
                    'success_rate' => $grant_data['success_rate'],
                    'difficulty' => $grant_data['difficulty_score'],
                    'roi_potential' => $grant_data['roi_potential']
                ];
            }

            // 統計情報を計算
            $statistics = self::calculateStatistics($analysis_data);
            
            // 推奨順位を計算
            $recommendations = self::calculateRecommendations($analysis_data);

            wp_send_json_success([
                'analysis' => $analysis_data,
                'comparison' => $comparison_data,
                'statistics' => $statistics,
                'recommendations' => $recommendations,
                'chart_data' => self::generateChartData($comparison_data)
            ]);

        } catch (Exception $e) {
            Logger::error('Grant analysis error: ' . $e->getMessage());
            wp_send_json_error(['message' => '分析処理に失敗しました']);
        }
    }

    /**
     * 成功確率を計算
     */
    public static function calculateSuccessProbability(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $company_size = sanitize_text_field($_POST['company_size'] ?? '');
        $industry = sanitize_text_field($_POST['industry'] ?? '');
        $experience = intval($_POST['experience'] ?? 0);
        $budget = intval($_POST['budget'] ?? 0);
        $grant_id = intval($_POST['grant_id'] ?? 0);

        try {
            $base_probability = self::estimateSuccessRate($grant_id);
            
            // 企業規模による調整
            $size_multiplier = match($company_size) {
                'startup' => 0.8,
                'small' => 1.0,
                'medium' => 1.1,
                'large' => 0.9,
                default => 1.0
            };

            // 経験による調整
            $experience_multiplier = min(1.5, 1.0 + ($experience * 0.1));
            
            // 予算による調整
            $grant_amount = get_field('max_amount_numeric', $grant_id) ?: 1000000;
            $budget_ratio = $budget / $grant_amount;
            $budget_multiplier = $budget_ratio > 0.5 ? 1.2 : ($budget_ratio > 0.2 ? 1.0 : 0.8);

            $final_probability = $base_probability * $size_multiplier * $experience_multiplier * $budget_multiplier;
            $final_probability = min(95, max(5, $final_probability)); // 5-95%の範囲に制限

            $advice = self::generateSuccessAdvice($final_probability, $company_size, $experience);

            wp_send_json_success([
                'probability' => round($final_probability, 1),
                'factors' => [
                    'base' => $base_probability,
                    'company_size' => $size_multiplier,
                    'experience' => $experience_multiplier,
                    'budget' => $budget_multiplier
                ],
                'advice' => $advice
            ]);

        } catch (Exception $e) {
            Logger::error('Success probability calculation error: ' . $e->getMessage());
            wp_send_json_error(['message' => '成功確率の計算に失敗しました']);
        }
    }

    /**
     * ROIを計算
     */
    public static function calculateRoi(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $grant_amount = floatval($_POST['grant_amount'] ?? 0);
        $project_cost = floatval($_POST['project_cost'] ?? 0);
        $expected_revenue = floatval($_POST['expected_revenue'] ?? 0);
        $time_period = intval($_POST['time_period'] ?? 12);

        try {
            if ($project_cost <= 0) {
                wp_send_json_error(['message' => 'プロジェクト費用を入力してください']);
            }

            $net_investment = $project_cost - $grant_amount;
            $roi_percentage = (($expected_revenue - $project_cost) / $project_cost) * 100;
            $payback_period = $net_investment > 0 ? ($net_investment / ($expected_revenue / $time_period)) : 0;
            
            // リスク調整ROI
            $risk_factor = self::calculateRiskFactor($grant_amount, $project_cost, $time_period);
            $risk_adjusted_roi = $roi_percentage * (1 - $risk_factor);

            $analysis = [
                'roi_percentage' => round($roi_percentage, 2),
                'risk_adjusted_roi' => round($risk_adjusted_roi, 2),
                'net_investment' => $net_investment,
                'payback_period' => round($payback_period, 1),
                'grant_coverage' => round(($grant_amount / $project_cost) * 100, 1),
                'risk_factor' => round($risk_factor * 100, 1),
                'recommendation' => self::generateRoiRecommendation($roi_percentage, $risk_adjusted_roi, $payback_period)
            ];

            wp_send_json_success($analysis);

        } catch (Exception $e) {
            Logger::error('ROI calculation error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'ROI計算に失敗しました']);
        }
    }

    /**
     * 助成金の分析データを取得
     */
    private static function getGrantAnalysisData(int $grant_id): array
    {
        $post = get_post($grant_id);
        
        return [
            'id' => $grant_id,
            'title' => $post->post_title,
            'amount' => get_field('max_amount_numeric', $grant_id) ?: 0,
            'amount_display' => get_field('max_amount', $grant_id) ?: '未設定',
            'deadline' => get_field('deadline_date', $grant_id) ?: '',
            'success_rate' => self::estimateSuccessRate($grant_id),
            'difficulty_score' => self::calculateDifficulty($grant_id),
            'roi_potential' => self::calculateRoiPotential($grant_id),
            'category' => wp_get_post_terms($grant_id, 'grant_category')[0]->name ?? '未分類',
            'status' => get_field('application_status', $grant_id) ?: 'unknown'
        ];
    }

    /**
     * 成功率を推定
     */
    private static function estimateSuccessRate(int $grant_id): float
    {
        // 基本成功率（助成金の種類や過去のデータに基づく）
        $base_rate = 30.0; // デフォルト30%
        
        // カテゴリによる調整
        $terms = wp_get_post_terms($grant_id, 'grant_category');
        if (!empty($terms)) {
            $category_rates = [
                'it-dx' => 35.0,
                'equipment' => 25.0,
                'training' => 40.0,
                'research' => 20.0,
                'startup' => 15.0
            ];
            $base_rate = $category_rates[$terms[0]->slug] ?? $base_rate;
        }
        
        // 金額による調整（高額ほど競争が激しい）
        $amount = get_field('max_amount_numeric', $grant_id) ?: 0;
        if ($amount > 10000000) { // 1000万円以上
            $base_rate *= 0.7;
        } elseif ($amount > 5000000) { // 500万円以上
            $base_rate *= 0.8;
        } elseif ($amount < 1000000) { // 100万円未満
            $base_rate *= 1.2;
        }
        
        return min(80, max(10, $base_rate));
    }

    /**
     * 難易度を計算
     */
    private static function calculateDifficulty(int $grant_id): string
    {
        $score = 0;
        
        // 金額による難易度
        $amount = get_field('max_amount_numeric', $grant_id) ?: 0;
        if ($amount > 10000000) $score += 3;
        elseif ($amount > 5000000) $score += 2;
        else $score += 1;
        
        // カテゴリによる難易度
        $terms = wp_get_post_terms($grant_id, 'grant_category');
        if (!empty($terms)) {
            $category_difficulty = [
                'research' => 3,
                'startup' => 3,
                'equipment' => 2,
                'it-dx' => 2,
                'training' => 1
            ];
            $score += $category_difficulty[$terms[0]->slug] ?? 2;
        }
        
        if ($score <= 3) return '易しい';
        elseif ($score <= 5) return '普通';
        else return '難しい';
    }

    /**
     * ROIポテンシャルを計算
     */
    private static function calculateRoiPotential(int $grant_id): float
    {
        $amount = get_field('max_amount_numeric', $grant_id) ?: 0;
        $success_rate = self::estimateSuccessRate($grant_id);
        
        // 簡易的なROIポテンシャル計算
        return ($amount * $success_rate / 100) / 1000000; // 百万円単位
    }

    /**
     * 統計情報を計算
     */
    private static function calculateStatistics(array $analysis_data): array
    {
        $amounts = array_column($analysis_data, 'amount');
        $success_rates = array_column($analysis_data, 'success_rate');
        
        return [
            'total_grants' => count($analysis_data),
            'total_amount' => array_sum($amounts),
            'average_amount' => count($amounts) > 0 ? array_sum($amounts) / count($amounts) : 0,
            'max_amount' => count($amounts) > 0 ? max($amounts) : 0,
            'min_amount' => count($amounts) > 0 ? min($amounts) : 0,
            'average_success_rate' => count($success_rates) > 0 ? array_sum($success_rates) / count($success_rates) : 0,
            'best_success_rate' => count($success_rates) > 0 ? max($success_rates) : 0
        ];
    }

    /**
     * 推奨順位を計算
     */
    private static function calculateRecommendations(array $analysis_data): array
    {
        // スコアリング（成功率 × 金額 × ROIポテンシャル）
        foreach ($analysis_data as &$grant) {
            $grant['score'] = ($grant['success_rate'] / 100) * ($grant['amount'] / 1000000) * $grant['roi_potential'];
        }
        
        // スコア順にソート
        usort($analysis_data, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($analysis_data, 0, 3); // 上位3件を推奨
    }

    /**
     * チャートデータを生成
     */
    private static function generateChartData(array $comparison_data): array
    {
        return [
            'labels' => array_column($comparison_data, 'label'),
            'amounts' => array_column($comparison_data, 'amount'),
            'success_rates' => array_column($comparison_data, 'success_rate'),
            'difficulty_scores' => array_column($comparison_data, 'difficulty'),
            'roi_potentials' => array_column($comparison_data, 'roi_potential')
        ];
    }

    /**
     * 成功アドバイスを生成
     */
    private static function generateSuccessAdvice(float $probability, string $company_size, int $experience): array
    {
        $advice = [];
        
        if ($probability < 30) {
            $advice[] = '成功確率が低めです。申請書の質を高めることが重要です。';
            $advice[] = '専門家のサポートを受けることを検討してください。';
        } elseif ($probability < 60) {
            $advice[] = '標準的な成功確率です。しっかりとした準備が必要です。';
            $advice[] = '事業計画の具体性を高めることが重要です。';
        } else {
            $advice[] = '高い成功確率が期待できます。';
            $advice[] = '現在の準備状況を維持して申請を進めてください。';
        }
        
        if ($experience < 2) {
            $advice[] = '助成金申請の経験を積むことで成功確率が向上します。';
        }
        
        return $advice;
    }

    /**
     * ROI推奨を生成
     */
    private static function generateRoiRecommendation(float $roi, float $risk_adjusted_roi, float $payback_period): string
    {
        if ($risk_adjusted_roi > 50) {
            return '非常に魅力的な投資です。積極的に検討することをお勧めします。';
        } elseif ($risk_adjusted_roi > 20) {
            return '良好なROIが期待できます。リスクを考慮して検討してください。';
        } elseif ($risk_adjusted_roi > 0) {
            return 'プラスのリターンが期待できますが、慎重に検討してください。';
        } else {
            return 'リスクが高い投資です。計画の見直しを検討してください。';
        }
    }

    /**
     * リスク要因を計算
     */
    private static function calculateRiskFactor(float $grant_amount, float $project_cost, int $time_period): float
    {
        $risk = 0.1; // ベースリスク10%
        
        // プロジェクト規模によるリスク
        if ($project_cost > 50000000) $risk += 0.1; // 5000万円以上
        elseif ($project_cost > 10000000) $risk += 0.05; // 1000万円以上
        
        // 期間によるリスク
        if ($time_period > 36) $risk += 0.1; // 3年以上
        elseif ($time_period > 24) $risk += 0.05; // 2年以上
        
        // 助成金依存度によるリスク
        $dependency = $grant_amount / $project_cost;
        if ($dependency > 0.8) $risk += 0.15; // 80%以上依存
        elseif ($dependency > 0.5) $risk += 0.1; // 50%以上依存
        
        return min(0.5, $risk); // 最大50%
    }
}

