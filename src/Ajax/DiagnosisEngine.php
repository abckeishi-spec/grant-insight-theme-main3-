<?php

namespace GrantInsight\Ajax;

use GrantInsight\Core\Logger;
use GrantInsight\Repositories\GrantRepository;

/**
 * AI Diagnosis Engine Class
 * 
 * 事業診断機能のバックエンド処理
 */
class DiagnosisEngine
{
    private static GrantRepository $grantRepository;

    /**
     * 初期化
     */
    public static function init(): void
    {
        self::$grantRepository = new GrantRepository();
        
        // AJAX処理を登録
        add_action('wp_ajax_gi_business_diagnosis', [self::class, 'handleDiagnosisRequest']);
        add_action('wp_ajax_nopriv_gi_business_diagnosis', [self::class, 'handleDiagnosisRequest']);
        
        add_action('wp_ajax_gi_save_diagnosis_result', [self::class, 'saveDiagnosisResult']);
        add_action('wp_ajax_nopriv_gi_save_diagnosis_result', [self::class, 'saveDiagnosisResult']);
    }

    /**
     * 診断リクエストを処理
     */
    public static function handleDiagnosisRequest(): void
    {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $answers = $_POST['answers'] ?? [];
        
        if (empty($answers) || !is_array($answers)) {
            wp_send_json_error(['message' => '診断データが不正です']);
        }

        try {
            // 診断結果を生成
            $diagnosis_result = self::generateDiagnosis($answers);
            
            // 推奨助成金を取得
            $recommended_grants = self::getRecommendedGrants($answers);
            
            // 診断レポートを生成
            $report = self::generateDiagnosisReport($answers, $diagnosis_result, $recommended_grants);

            // パフォーマンスログ
            Logger::info('Business diagnosis completed', [
                'answers_count' => count($answers),
                'recommended_grants_count' => count($recommended_grants)
            ]);

            wp_send_json_success([
                'diagnosis' => $diagnosis_result,
                'recommended_grants' => $recommended_grants,
                'report' => $report,
                'timestamp' => current_time('c')
            ]);

        } catch (Exception $e) {
            Logger::error('Business diagnosis error: ' . $e->getMessage(), [
                'answers' => $answers,
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => '診断処理でエラーが発生しました。しばらく後でお試しください。'
            ]);
        }
    }

    /**
     * 診断結果を生成
     */
    private static function generateDiagnosis(array $answers): array
    {
        $business_type = $answers['business_type'] ?? '';
        $company_size = $answers['company_size'] ?? '';
        $industry = $answers['industry'] ?? '';
        $funding_purpose = $answers['funding_purpose'] ?? '';
        $budget_range = $answers['budget_range'] ?? '';
        $timeline = $answers['timeline'] ?? '';
        $experience = $answers['experience'] ?? '';

        // 診断スコアを計算
        $scores = self::calculateDiagnosisScores($answers);
        
        // 診断結果テキストを生成
        $diagnosis_text = self::generateDiagnosisText($answers, $scores);
        
        // 成功確率を計算
        $success_probability = self::calculateSuccessProbability($answers, $scores);
        
        // 推奨アクションを生成
        $recommended_actions = self::generateRecommendedActions($answers, $scores);

        return [
            'business_profile' => [
                'type' => $business_type,
                'size' => $company_size,
                'industry' => $industry,
                'experience_level' => $experience
            ],
            'funding_analysis' => [
                'purpose' => $funding_purpose,
                'budget_range' => $budget_range,
                'timeline' => $timeline,
                'feasibility_score' => $scores['feasibility']
            ],
            'diagnosis_text' => $diagnosis_text,
            'success_probability' => $success_probability,
            'scores' => $scores,
            'recommended_actions' => $recommended_actions,
            'strengths' => self::identifyStrengths($answers, $scores),
            'improvement_areas' => self::identifyImprovementAreas($answers, $scores)
        ];
    }

    /**
     * 診断スコアを計算
     */
    private static function calculateDiagnosisScores(array $answers): array
    {
        $scores = [
            'readiness' => 0,      // 準備度
            'feasibility' => 0,    // 実現可能性
            'competitiveness' => 0, // 競争力
            'sustainability' => 0   // 持続可能性
        ];

        // 事業タイプによるスコア調整
        $business_type_scores = [
            'startup' => ['readiness' => 60, 'feasibility' => 70, 'competitiveness' => 80, 'sustainability' => 60],
            'existing' => ['readiness' => 80, 'feasibility' => 85, 'competitiveness' => 70, 'sustainability' => 85],
            'expansion' => ['readiness' => 90, 'feasibility' => 80, 'competitiveness' => 75, 'sustainability' => 80]
        ];

        $base_scores = $business_type_scores[$answers['business_type']] ?? $business_type_scores['existing'];
        
        // 企業規模による調整
        $size_multipliers = [
            'individual' => 0.8,
            'small' => 1.0,
            'medium' => 1.1,
            'large' => 0.9
        ];
        
        $size_multiplier = $size_multipliers[$answers['company_size']] ?? 1.0;
        
        // 経験による調整
        $experience_multipliers = [
            'none' => 0.7,
            'some' => 0.9,
            'experienced' => 1.2,
            'expert' => 1.3
        ];
        
        $experience_multiplier = $experience_multipliers[$answers['experience']] ?? 1.0;
        
        // 最終スコア計算
        foreach ($scores as $key => $value) {
            $scores[$key] = min(100, max(0, $base_scores[$key] * $size_multiplier * $experience_multiplier));
        }

        return $scores;
    }

    /**
     * 診断テキストを生成
     */
    private static function generateDiagnosisText(array $answers, array $scores): string
    {
        $business_type = $answers['business_type'] ?? '';
        $industry = $answers['industry'] ?? '';
        $avg_score = array_sum($scores) / count($scores);

        $diagnosis_templates = [
            'high' => "あなたの事業は助成金獲得に非常に適した状況にあります。特に{industry}分野での{business_type}事業として、高い競争力を持っています。現在の準備状況と事業計画の質を考慮すると、複数の助成金制度への申請が可能です。",
            'medium' => "あなたの事業は助成金獲得の可能性を秘めています。{industry}分野での{business_type}事業として、いくつかの改善点はありますが、適切な準備を行うことで成功確率を大幅に向上させることができます。",
            'low' => "現在の状況では助成金獲得にはいくつかの課題があります。しかし、{industry}分野での{business_type}事業として、戦略的なアプローチと十分な準備により、将来的な獲得可能性を高めることができます。"
        ];

        $level = $avg_score >= 80 ? 'high' : ($avg_score >= 60 ? 'medium' : 'low');
        
        $business_type_names = [
            'startup' => '新規事業',
            'existing' => '既存事業',
            'expansion' => '事業拡大'
        ];
        
        $industry_names = [
            'manufacturing' => '製造業',
            'it' => 'IT・情報通信業',
            'service' => 'サービス業',
            'retail' => '小売業',
            'healthcare' => '医療・福祉',
            'education' => '教育',
            'agriculture' => '農業',
            'construction' => '建設業',
            'other' => 'その他'
        ];

        $template = $diagnosis_templates[$level];
        $template = str_replace('{industry}', $industry_names[$industry] ?? $industry, $template);
        $template = str_replace('{business_type}', $business_type_names[$business_type] ?? $business_type, $template);

        return $template;
    }

    /**
     * 成功確率を計算
     */
    private static function calculateSuccessProbability(array $answers, array $scores): float
    {
        $base_probability = array_sum($scores) / count($scores);
        
        // 業界による調整
        $industry_adjustments = [
            'it' => 1.2,
            'manufacturing' => 1.1,
            'healthcare' => 1.15,
            'education' => 1.1,
            'agriculture' => 1.25,
            'service' => 0.95,
            'retail' => 0.9,
            'construction' => 1.0,
            'other' => 1.0
        ];
        
        $industry_multiplier = $industry_adjustments[$answers['industry']] ?? 1.0;
        
        // 予算規模による調整
        $budget_adjustments = [
            'under_1m' => 1.1,
            '1m_5m' => 1.0,
            '5m_10m' => 0.9,
            '10m_50m' => 0.8,
            'over_50m' => 0.7
        ];
        
        $budget_multiplier = $budget_adjustments[$answers['budget_range']] ?? 1.0;
        
        $final_probability = $base_probability * $industry_multiplier * $budget_multiplier;
        
        return min(95, max(5, $final_probability));
    }

    /**
     * 推奨アクションを生成
     */
    private static function generateRecommendedActions(array $answers, array $scores): array
    {
        $actions = [];
        
        if ($scores['readiness'] < 70) {
            $actions[] = [
                'priority' => 'high',
                'category' => '準備強化',
                'title' => '事業計画書の充実',
                'description' => '助成金申請に必要な詳細な事業計画書を作成し、財務計画を明確化してください。'
            ];
        }
        
        if ($scores['feasibility'] < 70) {
            $actions[] = [
                'priority' => 'high',
                'category' => '実現可能性向上',
                'title' => '市場調査の実施',
                'description' => 'ターゲット市場の詳細な分析と競合調査を行い、事業の実現可能性を高めてください。'
            ];
        }
        
        if ($scores['competitiveness'] < 70) {
            $actions[] = [
                'priority' => 'medium',
                'category' => '競争力強化',
                'title' => '差別化要素の明確化',
                'description' => '競合他社との差別化ポイントを明確にし、独自性をアピールできる要素を強化してください。'
            ];
        }
        
        if ($answers['experience'] === 'none') {
            $actions[] = [
                'priority' => 'medium',
                'category' => '専門知識習得',
                'title' => '助成金申請の学習',
                'description' => '助成金申請のノウハウを学習し、専門家のサポートを受けることを検討してください。'
            ];
        }
        
        return $actions;
    }

    /**
     * 強みを特定
     */
    private static function identifyStrengths(array $answers, array $scores): array
    {
        $strengths = [];
        
        if ($scores['readiness'] >= 80) {
            $strengths[] = '事業準備が十分に整っている';
        }
        
        if ($scores['feasibility'] >= 80) {
            $strengths[] = '事業の実現可能性が高い';
        }
        
        if ($scores['competitiveness'] >= 80) {
            $strengths[] = '市場での競争力がある';
        }
        
        if ($answers['experience'] === 'experienced' || $answers['experience'] === 'expert') {
            $strengths[] = '助成金申請の経験が豊富';
        }
        
        if ($answers['company_size'] === 'medium' || $answers['company_size'] === 'large') {
            $strengths[] = '組織体制が整っている';
        }
        
        return $strengths;
    }

    /**
     * 改善領域を特定
     */
    private static function identifyImprovementAreas(array $answers, array $scores): array
    {
        $areas = [];
        
        if ($scores['readiness'] < 70) {
            $areas[] = [
                'area' => '事業準備',
                'suggestion' => '事業計画の詳細化と必要書類の準備'
            ];
        }
        
        if ($scores['feasibility'] < 70) {
            $areas[] = [
                'area' => '実現可能性',
                'suggestion' => '市場分析と財務計画の見直し'
            ];
        }
        
        if ($scores['competitiveness'] < 70) {
            $areas[] = [
                'area' => '競争力',
                'suggestion' => '差別化戦略の強化と独自性の明確化'
            ];
        }
        
        return $areas;
    }

    /**
     * 推奨助成金を取得
     */
    private static function getRecommendedGrants(array $answers): array
    {
        $industry = $answers['industry'] ?? '';
        $business_type = $answers['business_type'] ?? '';
        $budget_range = $answers['budget_range'] ?? '';
        $funding_purpose = $answers['funding_purpose'] ?? '';

        // 検索条件を構築
        $search_args = [
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'meta_query' => []
        ];

        // 業界に基づくタクソノミー検索
        if (!empty($industry)) {
            $search_args['tax_query'] = [
                [
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => self::mapIndustryToCategory($industry)
                ]
            ];
        }

        // 予算範囲に基づく検索
        if (!empty($budget_range)) {
            $budget_meta_query = self::getBudgetMetaQuery($budget_range);
            if ($budget_meta_query) {
                $search_args['meta_query'][] = $budget_meta_query;
            }
        }

        $grants = self::$grantRepository->search($search_args);
        $formatted_grants = [];

        foreach ($grants as $grant) {
            $match_score = self::calculateGrantMatchScore($grant, $answers);
            
            $formatted_grants[] = [
                'id' => $grant['id'],
                'title' => $grant['title'],
                'excerpt' => wp_trim_words($grant['excerpt'], 30),
                'max_amount' => get_field('max_amount', $grant['id']) ?: '要確認',
                'deadline' => get_field('deadline_text', $grant['id']) ?: '随時',
                'url' => get_permalink($grant['id']),
                'match_score' => $match_score,
                'match_reasons' => self::getMatchReasons($grant, $answers)
            ];
        }

        // マッチスコア順にソート
        usort($formatted_grants, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return array_slice($formatted_grants, 0, 3);
    }

    /**
     * 業界をカテゴリにマッピング
     */
    private static function mapIndustryToCategory(string $industry): string
    {
        $mapping = [
            'it' => 'it-dx',
            'manufacturing' => 'equipment',
            'healthcare' => 'research',
            'education' => 'training',
            'agriculture' => 'agriculture',
            'service' => 'service',
            'retail' => 'retail',
            'construction' => 'construction'
        ];

        return $mapping[$industry] ?? 'other';
    }

    /**
     * 予算範囲のメタクエリを取得
     */
    private static function getBudgetMetaQuery(string $budget_range): ?array
    {
        $budget_ranges = [
            'under_1m' => ['min' => 0, 'max' => 1000000],
            '1m_5m' => ['min' => 1000000, 'max' => 5000000],
            '5m_10m' => ['min' => 5000000, 'max' => 10000000],
            '10m_50m' => ['min' => 10000000, 'max' => 50000000],
            'over_50m' => ['min' => 50000000, 'max' => PHP_INT_MAX]
        ];

        if (!isset($budget_ranges[$budget_range])) {
            return null;
        }

        $range = $budget_ranges[$budget_range];
        
        return [
            'key' => 'max_amount_numeric',
            'value' => [$range['min'], $range['max']],
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC'
        ];
    }

    /**
     * 助成金のマッチスコアを計算
     */
    private static function calculateGrantMatchScore(array $grant, array $answers): float
    {
        $score = 0;
        
        // 業界マッチング
        $grant_categories = wp_get_post_terms($grant['id'], 'grant_category');
        $expected_category = self::mapIndustryToCategory($answers['industry'] ?? '');
        
        foreach ($grant_categories as $category) {
            if ($category->slug === $expected_category) {
                $score += 30;
                break;
            }
        }
        
        // 予算マッチング
        $grant_amount = get_field('max_amount_numeric', $grant['id']) ?: 0;
        $budget_match = self::calculateBudgetMatch($grant_amount, $answers['budget_range'] ?? '');
        $score += $budget_match * 25;
        
        // 事業タイプマッチング
        $business_type_score = self::calculateBusinessTypeMatch($grant, $answers['business_type'] ?? '');
        $score += $business_type_score * 20;
        
        // 申請難易度
        $difficulty_score = self::calculateDifficultyMatch($grant, $answers);
        $score += $difficulty_score * 15;
        
        // 締切の近さ（近すぎず遠すぎない）
        $deadline_score = self::calculateDeadlineScore($grant);
        $score += $deadline_score * 10;
        
        return min(100, max(0, $score));
    }

    /**
     * 予算マッチングを計算
     */
    private static function calculateBudgetMatch(int $grant_amount, string $budget_range): float
    {
        if ($grant_amount === 0) return 0.5;
        
        $budget_ranges = [
            'under_1m' => 500000,
            '1m_5m' => 3000000,
            '5m_10m' => 7500000,
            '10m_50m' => 30000000,
            'over_50m' => 100000000
        ];
        
        $target_amount = $budget_ranges[$budget_range] ?? 3000000;
        
        $ratio = min($grant_amount, $target_amount) / max($grant_amount, $target_amount);
        
        return $ratio;
    }

    /**
     * 事業タイプマッチングを計算
     */
    private static function calculateBusinessTypeMatch(array $grant, string $business_type): float
    {
        // 助成金の対象事業タイプを判定（簡易版）
        $grant_title = strtolower($grant['title']);
        $grant_content = strtolower($grant['content']);
        
        $type_keywords = [
            'startup' => ['創業', 'スタートアップ', '新規', '起業'],
            'existing' => ['既存', '改善', '効率化', '生産性'],
            'expansion' => ['拡大', '展開', '成長', '規模']
        ];
        
        $keywords = $type_keywords[$business_type] ?? [];
        $match_count = 0;
        
        foreach ($keywords as $keyword) {
            if (strpos($grant_title, $keyword) !== false || strpos($grant_content, $keyword) !== false) {
                $match_count++;
            }
        }
        
        return min(1.0, $match_count / count($keywords));
    }

    /**
     * 難易度マッチングを計算
     */
    private static function calculateDifficultyMatch(array $grant, array $answers): float
    {
        $experience = $answers['experience'] ?? 'none';
        $company_size = $answers['company_size'] ?? 'small';
        
        // 経験レベルによる適性
        $experience_scores = [
            'none' => 0.3,
            'some' => 0.6,
            'experienced' => 0.9,
            'expert' => 1.0
        ];
        
        // 企業規模による適性
        $size_scores = [
            'individual' => 0.7,
            'small' => 1.0,
            'medium' => 0.9,
            'large' => 0.8
        ];
        
        $experience_score = $experience_scores[$experience] ?? 0.5;
        $size_score = $size_scores[$company_size] ?? 1.0;
        
        return ($experience_score + $size_score) / 2;
    }

    /**
     * 締切スコアを計算
     */
    private static function calculateDeadlineScore(array $grant): float
    {
        $deadline_date = get_field('deadline_date', $grant['id']);
        
        if (!$deadline_date) return 0.5; // 締切不明の場合
        
        $deadline_timestamp = strtotime($deadline_date);
        $current_timestamp = current_time('timestamp');
        $days_until_deadline = ($deadline_timestamp - $current_timestamp) / (24 * 60 * 60);
        
        if ($days_until_deadline < 30) return 0.2; // 締切が近すぎる
        if ($days_until_deadline > 365) return 0.3; // 締切が遠すぎる
        if ($days_until_deadline >= 60 && $days_until_deadline <= 180) return 1.0; // 理想的
        
        return 0.7; // その他
    }

    /**
     * マッチ理由を取得
     */
    private static function getMatchReasons(array $grant, array $answers): array
    {
        $reasons = [];
        
        // 業界マッチング
        $grant_categories = wp_get_post_terms($grant['id'], 'grant_category');
        $expected_category = self::mapIndustryToCategory($answers['industry'] ?? '');
        
        foreach ($grant_categories as $category) {
            if ($category->slug === $expected_category) {
                $reasons[] = '業界が一致しています';
                break;
            }
        }
        
        // 予算マッチング
        $grant_amount = get_field('max_amount_numeric', $grant['id']) ?: 0;
        if ($grant_amount > 0) {
            $budget_match = self::calculateBudgetMatch($grant_amount, $answers['budget_range'] ?? '');
            if ($budget_match > 0.7) {
                $reasons[] = '予算規模が適合しています';
            }
        }
        
        // 事業タイプマッチング
        $business_type_score = self::calculateBusinessTypeMatch($grant, $answers['business_type'] ?? '');
        if ($business_type_score > 0.5) {
            $reasons[] = '事業タイプが適合しています';
        }
        
        return $reasons;
    }

    /**
     * 診断レポートを生成
     */
    private static function generateDiagnosisReport(array $answers, array $diagnosis, array $grants): array
    {
        return [
            'summary' => [
                'total_score' => array_sum($diagnosis['scores']) / count($diagnosis['scores']),
                'success_probability' => $diagnosis['success_probability'],
                'recommended_grants_count' => count($grants),
                'top_strength' => !empty($diagnosis['strengths']) ? $diagnosis['strengths'][0] : '特になし',
                'priority_improvement' => !empty($diagnosis['improvement_areas']) ? $diagnosis['improvement_areas'][0]['area'] : '特になし'
            ],
            'detailed_analysis' => [
                'business_profile' => $diagnosis['business_profile'],
                'funding_analysis' => $diagnosis['funding_analysis'],
                'scores_breakdown' => $diagnosis['scores'],
                'action_plan' => $diagnosis['recommended_actions']
            ],
            'grant_recommendations' => $grants
        ];
    }

    /**
     * 診断結果を保存
     */
    public static function saveDiagnosisResult(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $diagnosis_data = $_POST['diagnosis_data'] ?? [];
        $user_email = sanitize_email($_POST['user_email'] ?? '');

        if (empty($diagnosis_data)) {
            wp_send_json_error(['message' => '診断データが不正です']);
        }

        try {
            // 診断結果をデータベースに保存
            $result_id = wp_insert_post([
                'post_type' => 'diagnosis_result',
                'post_status' => 'private',
                'post_title' => '診断結果 - ' . current_time('Y-m-d H:i:s'),
                'post_content' => json_encode($diagnosis_data),
                'meta_input' => [
                    'user_email' => $user_email,
                    'diagnosis_date' => current_time('Y-m-d H:i:s'),
                    'diagnosis_scores' => $diagnosis_data['scores'] ?? [],
                    'success_probability' => $diagnosis_data['success_probability'] ?? 0
                ]
            ]);

            if (is_wp_error($result_id)) {
                throw new \Exception('診断結果の保存に失敗しました');
            }

            Logger::info('Diagnosis result saved', [
                'result_id' => $result_id,
                'user_email' => $user_email
            ]);

            wp_send_json_success([
                'message' => '診断結果を保存しました',
                'result_id' => $result_id
            ]);

        } catch (Exception $e) {
            Logger::error('Diagnosis result save error: ' . $e->getMessage());
            wp_send_json_error(['message' => '診断結果の保存に失敗しました']);
        }
    }
}

