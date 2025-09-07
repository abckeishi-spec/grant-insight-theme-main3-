<?php

namespace GrantInsight\Helpers;

/**
 * Formatting Helper Class
 * 
 * データのフォーマットに関するヘルパー関数を管理するクラス
 */
class Formatting
{
    /**
     * 安全にメタデータを取得
     */
    public static function safeGetMeta(int $post_id, string $key, string $default = ''): string
    {
        $value = get_post_meta($post_id, $key, true);
        return !empty($value) ? $value : $default;
    }

    /**
     * 安全に属性値をエスケープ
     */
    public static function safeAttr($value): string
    {
        return esc_attr(wp_strip_all_tags($value));
    }

    /**
     * 安全にテキストをエスケープ
     */
    public static function safeEscape($value): string
    {
        return esc_html(wp_strip_all_tags($value));
    }

    /**
     * 安全に数値をフォーマット
     */
    public static function safeNumberFormat($value, int $decimals = 0): string
    {
        $number = is_numeric($value) ? floatval($value) : 0;
        return number_format($number, $decimals);
    }

    /**
     * 安全に日付をフォーマット
     */
    public static function safeDateFormat($date, string $format = 'Y-m-d'): string
    {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = is_numeric($date) ? intval($date) : strtotime($date);
        
        if ($timestamp === false || $timestamp <= 0) {
            return '';
        }
        
        return date($format, $timestamp);
    }

    /**
     * 安全にパーセンテージをフォーマット
     */
    public static function safePercentFormat($value, int $decimals = 1): string
    {
        $number = is_numeric($value) ? floatval($value) : 0;
        return number_format($number, $decimals) . '%';
    }

    /**
     * 安全にURLをフォーマット
     */
    public static function safeUrl(string $url): string
    {
        return esc_url(wp_strip_all_tags($url));
    }

    /**
     * 安全にJSONをエンコード
     */
    public static function safeJson($data): string
    {
        return wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 安全に抜粋をフォーマット
     */
    public static function safeExcerpt(string $text, int $length = 100, string $more = '...'): string
    {
        $text = wp_strip_all_tags($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $excerpt = mb_substr($text, 0, $length);
        $last_space = mb_strrpos($excerpt, ' ');
        
        if ($last_space !== false) {
            $excerpt = mb_substr($excerpt, 0, $last_space);
        }
        
        return $excerpt . $more;
    }

    /**
     * 締切日をフォーマット
     */
    public static function getFormattedDeadline(int $post_id): string
    {
        $deadline = get_field('deadline', $post_id);
        
        if (empty($deadline)) {
            return '<span class="text-gray-500">締切日未設定</span>';
        }
        
        $deadline_timestamp = strtotime($deadline);
        $current_timestamp = current_time('timestamp');
        $days_remaining = ceil(($deadline_timestamp - $current_timestamp) / DAY_IN_SECONDS);
        
        $formatted_date = date('Y年n月j日', $deadline_timestamp);
        
        if ($days_remaining < 0) {
            return '<span class="text-red-600 font-bold">締切済み (' . $formatted_date . ')</span>';
        } elseif ($days_remaining == 0) {
            return '<span class="text-red-600 font-bold">本日締切 (' . $formatted_date . ')</span>';
        } elseif ($days_remaining <= 7) {
            return '<span class="text-orange-600 font-bold">あと' . $days_remaining . '日 (' . $formatted_date . ')</span>';
        } elseif ($days_remaining <= 30) {
            return '<span class="text-yellow-600 font-semibold">あと' . $days_remaining . '日 (' . $formatted_date . ')</span>';
        } else {
            return '<span class="text-green-600">' . $formatted_date . ' (あと' . $days_remaining . '日)</span>';
        }
    }

    /**
     * 助成額を万円単位でフォーマット
     */
    public static function formatAmountMan($amount_yen, string $amount_text = ''): string
    {
        if (!empty($amount_text)) {
            return esc_html($amount_text);
        }
        
        if (!is_numeric($amount_yen) || $amount_yen <= 0) {
            return '金額未設定';
        }
        
        $amount_yen = intval($amount_yen);
        
        if ($amount_yen >= 100000000) { // 1億円以上
            $amount_oku = $amount_yen / 100000000;
            return number_format($amount_oku, 1) . '億円';
        } elseif ($amount_yen >= 10000) { // 1万円以上
            $amount_man = $amount_yen / 10000;
            return number_format($amount_man, 0) . '万円';
        } else {
            return number_format($amount_yen) . '円';
        }
    }

    /**
     * 申請ステータスをUIにマッピング
     */
    public static function mapApplicationStatusUi(string $app_status): array
    {
        $status_map = [
            'not_applied' => [
                'label' => '未申請',
                'class' => 'bg-gray-100 text-gray-800',
                'icon' => 'fas fa-clock'
            ],
            'preparing' => [
                'label' => '準備中',
                'class' => 'bg-blue-100 text-blue-800',
                'icon' => 'fas fa-edit'
            ],
            'submitted' => [
                'label' => '申請済み',
                'class' => 'bg-yellow-100 text-yellow-800',
                'icon' => 'fas fa-paper-plane'
            ],
            'under_review' => [
                'label' => '審査中',
                'class' => 'bg-purple-100 text-purple-800',
                'icon' => 'fas fa-search'
            ],
            'approved' => [
                'label' => '承認',
                'class' => 'bg-green-100 text-green-800',
                'icon' => 'fas fa-check-circle'
            ],
            'rejected' => [
                'label' => '不承認',
                'class' => 'bg-red-100 text-red-800',
                'icon' => 'fas fa-times-circle'
            ]
        ];
        
        return $status_map[$app_status] ?? $status_map['not_applied'];
    }

    /**
     * ステータスバッジを取得
     */
    public static function getStatusBadge(string $status): string
    {
        $status_config = self::mapApplicationStatusUi($status);
        
        return sprintf(
            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">
                <i class="%s mr-1"></i>
                %s
            </span>',
            $status_config['class'],
            $status_config['icon'],
            $status_config['label']
        );
    }

    /**
     * 投稿のカテゴリーを取得
     */
    public static function getPostCategories(int $post_id): array
    {
        $categories = [];
        
        // grant_category タクソノミー
        $grant_categories = wp_get_post_terms($post_id, 'grant_category');
        if (!is_wp_error($grant_categories)) {
            foreach ($grant_categories as $category) {
                $categories[] = [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'type' => 'grant_category'
                ];
            }
        }
        
        // prefecture タクソノミー
        $prefectures = wp_get_post_terms($post_id, 'prefecture');
        if (!is_wp_error($prefectures)) {
            foreach ($prefectures as $prefecture) {
                $categories[] = [
                    'name' => $prefecture->name,
                    'slug' => $prefecture->slug,
                    'type' => 'prefecture'
                ];
            }
        }
        
        return $categories;
    }
}

