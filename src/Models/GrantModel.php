<?php

namespace GrantInsight\Models;

/**
 * Grant Model Class
 * 
 * 助成金データを管理するモデルクラス
 */
class GrantModel
{
    private \WP_Post $post;
    private array $meta_cache = [];
    private array $terms_cache = [];

    public function __construct(\WP_Post $post)
    {
        $this->post = $post;
    }

    /**
     * 投稿IDを取得
     */
    public function getId(): int
    {
        return $this->post->ID;
    }

    /**
     * タイトルを取得
     */
    public function getTitle(): string
    {
        return $this->post->post_title;
    }

    /**
     * 内容を取得
     */
    public function getContent(): string
    {
        return apply_filters('the_content', $this->post->post_content);
    }

    /**
     * 抜粋を取得
     */
    public function getExcerpt(int $length = 100): string
    {
        $excerpt = !empty($this->post->post_excerpt) 
            ? $this->post->post_excerpt 
            : $this->post->post_content;
            
        return wp_trim_words(strip_tags($excerpt), $length);
    }

    /**
     * パーマリンクを取得
     */
    public function getPermalink(): string
    {
        return get_permalink($this->post->ID);
    }

    /**
     * サムネイル画像URLを取得
     */
    public function getThumbnailUrl(string $size = 'gi-card-thumb'): string
    {
        $thumbnail = get_the_post_thumbnail_url($this->post->ID, $size);
        return $thumbnail ?: '';
    }

    /**
     * 助成額を取得
     */
    public function getAmount(): ?int
    {
        $amount = $this->getMeta('amount');
        return is_numeric($amount) ? intval($amount) : null;
    }

    /**
     * フォーマットされた助成額を取得
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->getAmount();
        $amount_text = $this->getMeta('amount_text');
        
        if (!empty($amount_text)) {
            return $amount_text;
        }
        
        if ($amount === null) {
            return '金額未設定';
        }
        
        return \GrantInsight\Helpers\Formatting::formatAmountMan($amount);
    }

    /**
     * 締切日を取得
     */
    public function getDeadline(): ?string
    {
        return $this->getMeta('deadline') ?: null;
    }

    /**
     * フォーマットされた締切日を取得
     */
    public function getFormattedDeadline(): string
    {
        return \GrantInsight\Helpers\Formatting::getFormattedDeadline($this->post->ID);
    }

    /**
     * 締切までの残り日数を取得
     */
    public function getDaysRemaining(): ?int
    {
        $deadline = $this->getDeadline();
        if (!$deadline) {
            return null;
        }
        
        $deadline_timestamp = strtotime($deadline);
        $current_timestamp = current_time('timestamp');
        
        return ceil(($deadline_timestamp - $current_timestamp) / DAY_IN_SECONDS);
    }

    /**
     * 都道府県を取得
     */
    public function getPrefectures(): array
    {
        return $this->getTerms('prefecture');
    }

    /**
     * カテゴリーを取得
     */
    public function getCategories(): array
    {
        return $this->getTerms('grant_category');
    }

    /**
     * 申請ステータスを取得
     */
    public function getApplicationStatus(): string
    {
        return $this->getMeta('application_status') ?: 'not_applied';
    }

    /**
     * フォーマットされた申請ステータスを取得
     */
    public function getFormattedApplicationStatus(): array
    {
        return \GrantInsight\Helpers\Formatting::mapApplicationStatusUi($this->getApplicationStatus());
    }

    /**
     * 申請URL を取得
     */
    public function getApplicationUrl(): string
    {
        return $this->getMeta('application_url') ?: '';
    }

    /**
     * 担当機関を取得
     */
    public function getOrganization(): string
    {
        return $this->getMeta('organization') ?: '';
    }

    /**
     * 対象者を取得
     */
    public function getTargetAudience(): string
    {
        return $this->getMeta('target_audience') ?: '';
    }

    /**
     * 必要書類を取得
     */
    public function getRequiredDocuments(): array
    {
        $documents = $this->getMeta('required_documents');
        return is_array($documents) ? $documents : [];
    }

    /**
     * 人気度（閲覧数）を取得
     */
    public function getViewsCount(): int
    {
        $views = $this->getMeta('views_count');
        return is_numeric($views) ? intval($views) : 0;
    }

    /**
     * お気に入り数を取得
     */
    public function getFavoritesCount(): int
    {
        $favorites = $this->getMeta('favorites_count');
        return is_numeric($favorites) ? intval($favorites) : 0;
    }

    /**
     * 公開日を取得
     */
    public function getPublishedDate(string $format = 'Y-m-d'): string
    {
        return date($format, strtotime($this->post->post_date));
    }

    /**
     * 更新日を取得
     */
    public function getModifiedDate(string $format = 'Y-m-d'): string
    {
        return date($format, strtotime($this->post->post_modified));
    }

    /**
     * 配列形式でデータを取得
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'excerpt' => $this->getExcerpt(),
            'permalink' => $this->getPermalink(),
            'thumbnail_url' => $this->getThumbnailUrl(),
            'amount' => $this->getAmount(),
            'formatted_amount' => $this->getFormattedAmount(),
            'deadline' => $this->getDeadline(),
            'formatted_deadline' => $this->getFormattedDeadline(),
            'days_remaining' => $this->getDaysRemaining(),
            'prefectures' => $this->getPrefectures(),
            'categories' => $this->getCategories(),
            'application_status' => $this->getApplicationStatus(),
            'application_url' => $this->getApplicationUrl(),
            'organization' => $this->getOrganization(),
            'target_audience' => $this->getTargetAudience(),
            'views_count' => $this->getViewsCount(),
            'favorites_count' => $this->getFavoritesCount(),
            'published_date' => $this->getPublishedDate(),
            'modified_date' => $this->getModifiedDate()
        ];
    }

    /**
     * メタデータを取得（キャッシュ付き）
     */
    private function getMeta(string $key): string
    {
        if (!isset($this->meta_cache[$key])) {
            $this->meta_cache[$key] = get_field($key, $this->post->ID) ?: '';
        }
        
        return $this->meta_cache[$key];
    }

    /**
     * タクソノミーを取得（キャッシュ付き）
     */
    private function getTerms(string $taxonomy): array
    {
        if (!isset($this->terms_cache[$taxonomy])) {
            $terms = wp_get_post_terms($this->post->ID, $taxonomy);
            
            if (is_wp_error($terms)) {
                $this->terms_cache[$taxonomy] = [];
            } else {
                $this->terms_cache[$taxonomy] = array_map(function($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    ];
                }, $terms);
            }
        }
        
        return $this->terms_cache[$taxonomy];
    }
}

