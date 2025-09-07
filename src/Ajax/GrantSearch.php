<?php

namespace GrantInsight\Ajax;

/**
 * Grant Search Ajax Handler
 * 
 * 助成金検索に関するAJAX処理を管理するクラス
 */
class GrantSearch
{
    /**
     * 初期化
     */
    public static function init(): void
    {
        // ログインユーザー・非ログインユーザー両方で利用可能
        add_action('wp_ajax_load_grants', [self::class, 'loadGrants']);
        add_action('wp_ajax_nopriv_load_grants', [self::class, 'loadGrants']);
        
        add_action('wp_ajax_get_search_suggestions', [self::class, 'getSearchSuggestions']);
        add_action('wp_ajax_nopriv_get_search_suggestions', [self::class, 'getSearchSuggestions']);
        
        add_action('wp_ajax_advanced_search', [self::class, 'advancedSearch']);
        add_action('wp_ajax_nopriv_advanced_search', [self::class, 'advancedSearch']);
        
        add_action('wp_ajax_grant_insight_search', [self::class, 'grantInsightSearch']);
        add_action('wp_ajax_nopriv_grant_insight_search', [self::class, 'grantInsightSearch']);
        
        add_action('wp_ajax_export_results', [self::class, 'exportResults']);
        add_action('wp_ajax_nopriv_export_results', [self::class, 'exportResults']);
        
        add_action('wp_ajax_get_related_grants', [self::class, 'getRelatedGrants']);
        add_action('wp_ajax_nopriv_get_related_grants', [self::class, 'getRelatedGrants']);
    }

    /**
     * 助成金一覧の読み込み
     */
    public static function loadGrants(): void
    {
        // ノンス検証
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 12);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $amount_min = intval($_POST['amount_min'] ?? 0);
        $amount_max = intval($_POST['amount_max'] ?? 0);
        $deadline_from = sanitize_text_field($_POST['deadline_from'] ?? '');
        $deadline_to = sanitize_text_field($_POST['deadline_to'] ?? '');

        // クエリ引数の構築
        $args = [
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [],
            'tax_query' => []
        ];

        // 検索キーワード
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // 都道府県フィルター
        if (!empty($prefecture)) {
            $args['tax_query'][] = [
                'taxonomy' => 'prefecture',
                'field' => 'slug',
                'terms' => $prefecture
            ];
        }

        // カテゴリーフィルター
        if (!empty($category)) {
            $args['tax_query'][] = [
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $category
            ];
        }

        // 助成額フィルター
        if ($amount_min > 0 || $amount_max > 0) {
            $meta_query = ['key' => 'amount_numeric', 'type' => 'NUMERIC'];
            
            if ($amount_min > 0 && $amount_max > 0) {
                $meta_query['value'] = [$amount_min, $amount_max];
                $meta_query['compare'] = 'BETWEEN';
            } elseif ($amount_min > 0) {
                $meta_query['value'] = $amount_min;
                $meta_query['compare'] = '>=';
            } elseif ($amount_max > 0) {
                $meta_query['value'] = $amount_max;
                $meta_query['compare'] = '<=';
            }
            
            $args['meta_query'][] = $meta_query;
        }

        // 締切日フィルター
        if (!empty($deadline_from) || !empty($deadline_to)) {
            $meta_query = ['key' => 'deadline', 'type' => 'DATE'];
            
            if (!empty($deadline_from) && !empty($deadline_to)) {
                $meta_query['value'] = [$deadline_from, $deadline_to];
                $meta_query['compare'] = 'BETWEEN';
            } elseif (!empty($deadline_from)) {
                $meta_query['value'] = $deadline_from;
                $meta_query['compare'] = '>=';
            } elseif (!empty($deadline_to)) {
                $meta_query['value'] = $deadline_to;
                $meta_query['compare'] = '<=';
            }
            
            $args['meta_query'][] = $meta_query;
        }

        // クエリ実行
        $query = new \WP_Query($args);
        
        $grants = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = self::formatGrantData(get_post());
            }
            wp_reset_postdata();
        }

        // レスポンス
        wp_send_json_success([
            'grants' => $grants,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ]);
    }

    /**
     * 検索候補の取得
     */
    public static function getSearchSuggestions(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $term = sanitize_text_field($_POST['term'] ?? '');
        
        if (strlen($term) < 2) {
            wp_send_json_success([]);
        }

        $suggestions = [];
        
        // 投稿タイトルから検索
        $posts = get_posts([
            'post_type' => 'grant',
            'post_status' => 'publish',
            's' => $term,
            'posts_per_page' => 5
        ]);

        foreach ($posts as $post) {
            $suggestions[] = [
                'label' => $post->post_title,
                'value' => $post->post_title,
                'type' => 'title'
            ];
        }

        wp_send_json_success($suggestions);
    }

    /**
     * 助成金データのフォーマット
     */
    private static function formatGrantData(\WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_excerpt ?: $post->post_content, 20),
            'permalink' => get_permalink($post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'gi-card-thumb'),
            'amount' => get_field('amount', $post->ID),
            'deadline' => get_field('deadline', $post->ID),
            'prefecture' => wp_get_post_terms($post->ID, 'prefecture', ['fields' => 'names']),
            'category' => wp_get_post_terms($post->ID, 'grant_category', ['fields' => 'names'])
        ];
    }

    /**
     * 高度な検索
     */
    public static function advancedSearch(): void
    {
        // 実装は loadGrants() と同様だが、より複雑な条件に対応
        self::loadGrants();
    }

    /**
     * Grant Insight検索
     */
    public static function grantInsightSearch(): void
    {
        // AI機能を使った検索（将来的な拡張用）
        self::loadGrants();
    }

    /**
     * 検索結果のエクスポート
     */
    public static function exportResults(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_die('Security check failed');
        }

        // CSVエクスポート機能の実装
        wp_send_json_success(['message' => 'エクスポート機能は準備中です']);
    }

    /**
     * 関連助成金の取得
     */
    public static function getRelatedGrants(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_die('Security check failed');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // 関連助成金の取得ロジック
        $related_grants = [];
        
        wp_send_json_success(['grants' => $related_grants]);
    }
}

