<?php

namespace GrantInsight\Repositories;

use GrantInsight\Models\GrantModel;

/**
 * Grant Repository Class
 * 
 * 助成金データの取得・操作を管理するリポジトリクラス
 */
class GrantRepository
{
    /**
     * IDで助成金を取得
     */
    public function find(int $id): ?GrantModel
    {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'grant' || $post->post_status !== 'publish') {
            return null;
        }
        
        return new GrantModel($post);
    }

    /**
     * 複数の助成金を取得
     */
    public function findMany(array $ids): array
    {
        $grants = [];
        
        foreach ($ids as $id) {
            $grant = $this->find($id);
            if ($grant) {
                $grants[] = $grant;
            }
        }
        
        return $grants;
    }

    /**
     * 条件に基づいて助成金を検索
     */
    public function search(array $criteria = []): array
    {
        $args = $this->buildQueryArgs($criteria);
        $query = new \WP_Query($args);
        
        $grants = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = new GrantModel(get_post());
            }
            wp_reset_postdata();
        }
        
        return $grants;
    }

    /**
     * ページネーション付きで助成金を検索
     */
    public function searchWithPagination(array $criteria = []): array
    {
        $args = $this->buildQueryArgs($criteria);
        $query = new \WP_Query($args);
        
        $grants = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = new GrantModel(get_post());
            }
            wp_reset_postdata();
        }
        
        return [
            'grants' => $grants,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $query->get('paged') ?: 1
        ];
    }

    /**
     * 人気の助成金を取得
     */
    public function findPopular(int $limit = 5): array
    {
        return \GrantInsight\Core\Cache::get(
            "popular_grants_{$limit}",
            function() use ($limit) {
                return $this->search([
                    'posts_per_page' => $limit,
                    'meta_key' => 'views_count',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC'
                ]);
            },
            1800 // 30分キャッシュ
        );
    }

    /**
     * 最新の助成金を取得
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->search([
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
    }

    /**
     * 締切が近い助成金を取得
     */
    public function findDeadlineSoon(int $days = 30, int $limit = 10): array
    {
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime("+{$days} days"));
        
        return $this->search([
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'deadline',
                    'value' => [$today, $future_date],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ],
            'meta_key' => 'deadline',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        ]);
    }

    /**
     * 都道府県別の助成金を取得
     */
    public function findByPrefecture(string $prefecture_slug, int $limit = 10): array
    {
        return $this->search([
            'posts_per_page' => $limit,
            'tax_query' => [
                [
                    'taxonomy' => 'prefecture',
                    'field' => 'slug',
                    'terms' => $prefecture_slug
                ]
            ]
        ]);
    }

    /**
     * カテゴリー別の助成金を取得
     */
    public function findByCategory(string $category_slug, int $limit = 10): array
    {
        return $this->search([
            'posts_per_page' => $limit,
            'tax_query' => [
                [
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => $category_slug
                ]
            ]
        ]);
    }

    /**
     * 助成額範囲で助成金を取得
     */
    public function findByAmountRange(int $min_amount = 0, int $max_amount = 0, int $limit = 10): array
    {
        $meta_query = [];
        
        if ($min_amount > 0 && $max_amount > 0) {
            $meta_query[] = [
                'key' => 'amount_numeric',
                'value' => [$min_amount, $max_amount],
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            ];
        } elseif ($min_amount > 0) {
            $meta_query[] = [
                'key' => 'amount_numeric',
                'value' => $min_amount,
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        } elseif ($max_amount > 0) {
            $meta_query[] = [
                'key' => 'amount_numeric',
                'value' => $max_amount,
                'compare' => '<=',
                'type' => 'NUMERIC'
            ];
        }
        
        return $this->search([
            'posts_per_page' => $limit,
            'meta_query' => $meta_query,
            'meta_key' => 'amount_numeric',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);
    }

    /**
     * 関連する助成金を取得
     */
    public function findRelated(GrantModel $grant, int $limit = 5): array
    {
        $categories = $grant->getCategories();
        $prefectures = $grant->getPrefectures();
        
        $tax_query = ['relation' => 'OR'];
        
        // 同じカテゴリーの助成金
        if (!empty($categories)) {
            $category_slugs = array_column($categories, 'slug');
            $tax_query[] = [
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $category_slugs
            ];
        }
        
        // 同じ都道府県の助成金
        if (!empty($prefectures)) {
            $prefecture_slugs = array_column($prefectures, 'slug');
            $tax_query[] = [
                'taxonomy' => 'prefecture',
                'field' => 'slug',
                'terms' => $prefecture_slugs
            ];
        }
        
        return $this->search([
            'posts_per_page' => $limit,
            'post__not_in' => [$grant->getId()],
            'tax_query' => $tax_query,
            'orderby' => 'rand'
        ]);
    }

    /**
     * 助成金の統計情報を取得
     */
    public function getStatistics(): array
    {
        // 総数
        $total_count = wp_count_posts('grant')->publish;
        
        // 今月の新規追加数
        $this_month_count = $this->search([
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'year' => date('Y'),
                    'month' => date('n')
                ]
            ]
        ]);
        
        // 締切が近い助成金数（30日以内）
        $deadline_soon_count = count($this->findDeadlineSoon(30, -1));
        
        // 平均助成額
        $amounts = [];
        $grants_with_amount = $this->search([
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'amount_numeric',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        foreach ($grants_with_amount as $grant) {
            $amount = $grant->getAmount();
            if ($amount) {
                $amounts[] = $amount;
            }
        }
        
        $average_amount = !empty($amounts) ? array_sum($amounts) / count($amounts) : 0;
        
        return [
            'total_count' => $total_count,
            'this_month_count' => count($this_month_count),
            'deadline_soon_count' => $deadline_soon_count,
            'average_amount' => $average_amount,
            'grants_with_amount_count' => count($amounts)
        ];
    }

    /**
     * クエリ引数を構築
     */
    private function buildQueryArgs(array $criteria): array
    {
        $defaults = [
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => 1
        ];
        
        return array_merge($defaults, $criteria);
    }

    /**
     * 助成金の閲覧数を増加
     */
    public function incrementViewsCount(int $grant_id): bool
    {
        $current_views = get_post_meta($grant_id, 'views_count', true);
        $new_views = intval($current_views) + 1;
        
        return update_post_meta($grant_id, 'views_count', $new_views);
    }

    /**
     * 助成金をお気に入りに追加/削除
     */
    public function toggleFavorite(int $grant_id, int $user_id): bool
    {
        $favorites = get_user_meta($user_id, 'favorite_grants', true);
        $favorites = is_array($favorites) ? $favorites : [];
        
        if (in_array($grant_id, $favorites)) {
            // お気に入りから削除
            $favorites = array_diff($favorites, [$grant_id]);
            $is_favorite = false;
        } else {
            // お気に入りに追加
            $favorites[] = $grant_id;
            $is_favorite = true;
        }
        
        update_user_meta($user_id, 'favorite_grants', $favorites);
        
        // お気に入り数を更新
        $favorites_count = get_post_meta($grant_id, 'favorites_count', true);
        $new_count = $is_favorite ? intval($favorites_count) + 1 : max(0, intval($favorites_count) - 1);
        update_post_meta($grant_id, 'favorites_count', $new_count);
        
        return $is_favorite;
    }
}

