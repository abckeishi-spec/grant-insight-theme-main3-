<?php
/**
 * Grant Insight Missing Functions Implementation
 * 未定義関数の実装
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 未定義関数実装クラス
 */
class GI_Missing_Functions {
    
    /**
     * 初期化
     */
    public static function init() {
        // 関数の存在チェックと実装
        self::implement_missing_functions();
    }
    
    /**
     * 未定義関数の実装
     */
    private static function implement_missing_functions() {
        // gi_safe_excerpt関数の実装
        if (!function_exists('gi_safe_excerpt')) {
            function gi_safe_excerpt($text, $length = 100, $more = '...') {
                return GI_Missing_Functions::safe_excerpt($text, $length, $more);
            }
        }
        
        // gi_get_formatted_deadline関数の実装
        if (!function_exists('gi_get_formatted_deadline')) {
            function gi_get_formatted_deadline($deadline, $format = 'Y年m月d日') {
                return GI_Missing_Functions::get_formatted_deadline($deadline, $format);
            }
        }
        
        // gi_format_amount関数の実装
        if (!function_exists('gi_format_amount')) {
            function gi_format_amount($amount, $unit = '円') {
                return GI_Missing_Functions::format_amount($amount, $unit);
            }
        }
        
        // gi_get_difficulty_label関数の実装
        if (!function_exists('gi_get_difficulty_label')) {
            function gi_get_difficulty_label($difficulty) {
                return GI_Missing_Functions::get_difficulty_label($difficulty);
            }
        }
        
        // gi_calculate_days_remaining関数の実装
        if (!function_exists('gi_calculate_days_remaining')) {
            function gi_calculate_days_remaining($deadline) {
                return GI_Missing_Functions::calculate_days_remaining($deadline);
            }
        }
        
        // gi_get_grant_status関数の実装
        if (!function_exists('gi_get_grant_status')) {
            function gi_get_grant_status($post_id = null) {
                return GI_Missing_Functions::get_grant_status($post_id);
            }
        }
        
        // gi_is_application_period関数の実装
        if (!function_exists('gi_is_application_period')) {
            function gi_is_application_period($post_id = null) {
                return GI_Missing_Functions::is_application_period($post_id);
            }
        }
        
        // gi_get_related_grants関数の実装
        if (!function_exists('gi_get_related_grants')) {
            function gi_get_related_grants($post_id = null, $limit = 5) {
                return GI_Missing_Functions::get_related_grants($post_id, $limit);
            }
        }
        
        // gi_render_breadcrumb関数の実装
        if (!function_exists('gi_render_breadcrumb')) {
            function gi_render_breadcrumb() {
                return GI_Missing_Functions::render_breadcrumb();
            }
        }
        
        // gi_get_social_share_links関数の実装
        if (!function_exists('gi_get_social_share_links')) {
            function gi_get_social_share_links($post_id = null) {
                return GI_Missing_Functions::get_social_share_links($post_id);
            }
        }
    }
    
    /**
     * 安全な抜粋生成
     */
    public static function safe_excerpt($text, $length = 100, $more = '...') {
        if (empty($text)) {
            return '';
        }
        
        // HTMLタグを除去
        $text = wp_strip_all_tags($text);
        
        // 改行を空白に変換
        $text = preg_replace('/\s+/', ' ', $text);
        
        // 文字数制限
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . $more;
        }
        
        return trim($text);
    }
    
    /**
     * 期限の整形表示
     */
    public static function get_formatted_deadline($deadline, $format = 'Y年m月d日') {
        if (empty($deadline)) {
            return '期限未設定';
        }
        
        // 日付文字列を正規化
        $timestamp = strtotime($deadline);
        
        if ($timestamp === false) {
            return '無効な日付';
        }
        
        // 現在日時との比較
        $now = time();
        $days_diff = floor(($timestamp - $now) / (24 * 60 * 60));
        
        $formatted_date = date($format, $timestamp);
        
        if ($days_diff < 0) {
            return $formatted_date . ' (終了)';
        } elseif ($days_diff == 0) {
            return $formatted_date . ' (本日まで)';
        } elseif ($days_diff <= 7) {
            return $formatted_date . ' (あと' . $days_diff . '日)';
        } else {
            return $formatted_date;
        }
    }
    
    /**
     * 金額の整形表示
     */
    public static function format_amount($amount, $unit = '円') {
        if (!is_numeric($amount) || $amount <= 0) {
            return '金額未設定';
        }
        
        $amount = (float)$amount;
        
        if ($amount >= 100000000) { // 1億以上
            return number_format($amount / 100000000, 1) . '億' . $unit;
        } elseif ($amount >= 10000) { // 1万以上
            return number_format($amount / 10000, 0) . '万' . $unit;
        } else {
            return number_format($amount) . $unit;
        }
    }
    
    /**
     * 難易度ラベルの取得
     */
    public static function get_difficulty_label($difficulty) {
        $labels = array(
            'easy' => '易しい',
            'medium' => '普通',
            'hard' => '難しい',
            'beginner' => '初心者向け',
            'intermediate' => '中級者向け',
            'advanced' => '上級者向け'
        );
        
        return isset($labels[$difficulty]) ? $labels[$difficulty] : '未設定';
    }
    
    /**
     * 残り日数の計算
     */
    public static function calculate_days_remaining($deadline) {
        if (empty($deadline)) {
            return null;
        }
        
        $timestamp = strtotime($deadline);
        
        if ($timestamp === false) {
            return null;
        }
        
        $now = time();
        $days_diff = floor(($timestamp - $now) / (24 * 60 * 60));
        
        return $days_diff;
    }
    
    /**
     * 助成金のステータス取得
     */
    public static function get_grant_status($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $deadline = get_field('deadline', $post_id);
        $application_start = get_field('application_period_start', $post_id);
        $application_end = get_field('application_period_end', $post_id);
        
        $now = time();
        
        // 申請期間の確認
        if (!empty($application_start)) {
            $start_timestamp = strtotime($application_start);
            if ($start_timestamp && $now < $start_timestamp) {
                return array(
                    'status' => 'upcoming',
                    'label' => '申請開始前',
                    'class' => 'status-upcoming'
                );
            }
        }
        
        if (!empty($application_end)) {
            $end_timestamp = strtotime($application_end);
            if ($end_timestamp && $now > $end_timestamp) {
                return array(
                    'status' => 'closed',
                    'label' => '申請終了',
                    'class' => 'status-closed'
                );
            }
        }
        
        // 期限の確認
        if (!empty($deadline)) {
            $deadline_timestamp = strtotime($deadline);
            if ($deadline_timestamp) {
                $days_remaining = floor(($deadline_timestamp - $now) / (24 * 60 * 60));
                
                if ($days_remaining < 0) {
                    return array(
                        'status' => 'expired',
                        'label' => '期限切れ',
                        'class' => 'status-expired'
                    );
                } elseif ($days_remaining <= 7) {
                    return array(
                        'status' => 'urgent',
                        'label' => '申請期限間近',
                        'class' => 'status-urgent'
                    );
                }
            }
        }
        
        return array(
            'status' => 'active',
            'label' => '申請受付中',
            'class' => 'status-active'
        );
    }
    
    /**
     * 申請期間中かどうかの判定
     */
    public static function is_application_period($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $status = self::get_grant_status($post_id);
        
        return in_array($status['status'], array('active', 'urgent'));
    }
    
    /**
     * 関連助成金の取得
     */
    public static function get_related_grants($post_id = null, $limit = 5) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        // 現在の投稿のカテゴリを取得
        $categories = get_the_terms($post_id, 'grant_category');
        $prefectures = get_the_terms($post_id, 'prefecture');
        
        $tax_query = array('relation' => 'OR');
        
        // カテゴリでの関連性
        if ($categories && !is_wp_error($categories)) {
            $category_ids = wp_list_pluck($categories, 'term_id');
            $tax_query[] = array(
                'taxonomy' => 'grant_category',
                'field' => 'term_id',
                'terms' => $category_ids,
                'operator' => 'IN'
            );
        }
        
        // 都道府県での関連性
        if ($prefectures && !is_wp_error($prefectures)) {
            $prefecture_ids = wp_list_pluck($prefectures, 'term_id');
            $tax_query[] = array(
                'taxonomy' => 'prefecture',
                'field' => 'term_id',
                'terms' => $prefecture_ids,
                'operator' => 'IN'
            );
        }
        
        $args = array(
            'post_type' => 'grant',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'post_status' => 'publish',
            'orderby' => 'rand',
            'tax_query' => $tax_query
        );
        
        return get_posts($args);
    }
    
    /**
     * パンくずリストの生成
     */
    public static function render_breadcrumb() {
        if (is_front_page()) {
            return '';
        }
        
        $breadcrumb = array();
        $breadcrumb[] = '<a href="' . home_url('/') . '">ホーム</a>';
        
        if (is_single()) {
            $post_type = get_post_type();
            
            switch ($post_type) {
                case 'grant':
                    $breadcrumb[] = '<a href="' . get_post_type_archive_link('grant') . '">助成金一覧</a>';
                    break;
                case 'tool':
                    $breadcrumb[] = '<a href="' . get_post_type_archive_link('tool') . '">診断ツール</a>';
                    break;
                case 'case_study':
                    $breadcrumb[] = '<a href="' . get_post_type_archive_link('case_study') . '">成功事例</a>';
                    break;
            }
            
            $breadcrumb[] = '<span>' . get_the_title() . '</span>';
            
        } elseif (is_post_type_archive()) {
            $post_type_obj = get_queried_object();
            $breadcrumb[] = '<span>' . $post_type_obj->labels->name . '</span>';
            
        } elseif (is_tax()) {
            $term = get_queried_object();
            $post_type = 'grant'; // デフォルト
            
            if ($term->taxonomy === 'grant_category' || $term->taxonomy === 'prefecture') {
                $post_type = 'grant';
            } elseif ($term->taxonomy === 'tool_category') {
                $post_type = 'tool';
            }
            
            $breadcrumb[] = '<a href="' . get_post_type_archive_link($post_type) . '">' . get_post_type_object($post_type)->labels->name . '</a>';
            $breadcrumb[] = '<span>' . $term->name . '</span>';
            
        } elseif (is_search()) {
            $breadcrumb[] = '<span>検索結果: ' . get_search_query() . '</span>';
            
        } elseif (is_404()) {
            $breadcrumb[] = '<span>ページが見つかりません</span>';
        }
        
        if (!empty($breadcrumb)) {
            return '<nav class="breadcrumb" aria-label="パンくずリスト">' . implode(' &gt; ', $breadcrumb) . '</nav>';
        }
        
        return '';
    }
    
    /**
     * ソーシャルシェアリンクの生成
     */
    public static function get_social_share_links($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $title = get_the_title($post_id);
        $url = get_permalink($post_id);
        $excerpt = get_the_excerpt($post_id);
        
        $encoded_title = urlencode($title);
        $encoded_url = urlencode($url);
        $encoded_text = urlencode($excerpt);
        
        $links = array(
            'twitter' => array(
                'url' => "https://twitter.com/intent/tweet?text={$encoded_title}&url={$encoded_url}",
                'label' => 'Twitterでシェア',
                'icon' => 'twitter'
            ),
            'facebook' => array(
                'url' => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
                'label' => 'Facebookでシェア',
                'icon' => 'facebook'
            ),
            'line' => array(
                'url' => "https://social-plugins.line.me/lineit/share?url={$encoded_url}",
                'label' => 'LINEでシェア',
                'icon' => 'line'
            ),
            'copy' => array(
                'url' => $url,
                'label' => 'URLをコピー',
                'icon' => 'copy'
            )
        );
        
        return $links;
    }
    
    /**
     * 投稿の閲覧数を取得・更新
     */
    public static function get_post_views($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $views = get_post_meta($post_id, '_gi_post_views', true);
        return $views ? intval($views) : 0;
    }
    
    /**
     * 投稿の閲覧数を増加
     */
    public static function increment_post_views($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $views = self::get_post_views($post_id);
        $views++;
        
        update_post_meta($post_id, '_gi_post_views', $views);
        
        return $views;
    }
    
    /**
     * 人気の投稿を取得
     */
    public static function get_popular_posts($post_type = 'grant', $limit = 5, $period_days = 30) {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_key' => '_gi_post_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => $period_days . ' days ago'
                )
            )
        );
        
        return get_posts($args);
    }
}

// 未定義関数の初期化
if (!function_exists('gi_init_missing_functions')) {
    function gi_init_missing_functions() {
        GI_Missing_Functions::init();
    }
    add_action('init', 'gi_init_missing_functions', 1);
}

// 投稿閲覧時の閲覧数増加
if (!function_exists('gi_track_post_views')) {
    function gi_track_post_views() {
        if (is_single() && !is_admin() && !current_user_can('edit_posts')) {
            GI_Missing_Functions::increment_post_views();
        }
    }
    add_action('wp_head', 'gi_track_post_views');
}

