<?php

namespace GrantInsight\PostTypes;

/**
 * Grant Post Type Class
 * 
 * 助成金カスタム投稿タイプを管理するクラス
 * AIが理解しやすいように、設定を構造化
 */
class Grant
{
    /**
     * 投稿タイプ名
     */
    public const POST_TYPE = 'grant';

    /**
     * 初期化
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
        add_action('save_post', [self::class, 'savePost'], 20, 3);
    }

    /**
     * カスタム投稿タイプの登録
     */
    public static function register(): void
    {
        $args = self::getPostTypeArgs();
        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * 投稿タイプの設定を取得
     */
    private static function getPostTypeArgs(): array
    {
        return [
            'labels' => self::getLabels(),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'grants'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
                'revisions'
            ],
            'taxonomies' => ['grant_category', 'prefecture'],
            'show_in_nav_menus' => true,
            'can_export' => true,
        ];
    }

    /**
     * ラベルの設定を取得
     */
    private static function getLabels(): array
    {
        return [
            'name' => '助成金',
            'singular_name' => '助成金',
            'menu_name' => '助成金管理',
            'name_admin_bar' => '助成金',
            'add_new' => '新規追加',
            'add_new_item' => '新しい助成金を追加',
            'new_item' => '新しい助成金',
            'edit_item' => '助成金を編集',
            'view_item' => '助成金を表示',
            'all_items' => 'すべての助成金',
            'search_items' => '助成金を検索',
            'parent_item_colon' => '親助成金:',
            'not_found' => '助成金が見つかりませんでした。',
            'not_found_in_trash' => 'ゴミ箱に助成金が見つかりませんでした。',
            'featured_image' => 'アイキャッチ画像',
            'set_featured_image' => 'アイキャッチ画像を設定',
            'remove_featured_image' => 'アイキャッチ画像を削除',
            'use_featured_image' => 'アイキャッチ画像として使用',
            'archives' => '助成金アーカイブ',
            'insert_into_item' => '助成金に挿入',
            'uploaded_to_this_item' => 'この助成金にアップロード',
            'filter_items_list' => '助成金リストをフィルター',
            'items_list_navigation' => '助成金リストナビゲーション',
            'items_list' => '助成金リスト',
        ];
    }

    /**
     * 投稿保存時の処理
     */
    public static function savePost(int $postId, \WP_Post $post, bool $update): void
    {
        // 自動保存の場合は処理しない
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 権限チェック
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // 助成金投稿タイプでない場合は処理しない
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        // カスタムフィールドの同期処理
        self::syncCustomFields($postId);
    }

    /**
     * カスタムフィールドの同期
     */
    private static function syncCustomFields(int $postId): void
    {
        // 締切日の処理
        $deadline = get_field('deadline', $postId);
        if ($deadline) {
            $deadline_timestamp = strtotime($deadline);
            update_post_meta($postId, '_deadline_timestamp', $deadline_timestamp);
            
            // 締切まで残り日数を計算
            $days_remaining = ceil(($deadline_timestamp - time()) / DAY_IN_SECONDS);
            update_post_meta($postId, '_days_remaining', $days_remaining);
        }

        // 助成額の処理
        $amount = get_field('amount', $postId);
        if ($amount) {
            // 数値のみを抽出してソート用に保存
            $amount_numeric = (int) preg_replace('/[^0-9]/', '', $amount);
            update_post_meta($postId, '_amount_numeric', $amount_numeric);
        }
    }

    /**
     * 助成金の検索クエリを取得
     */
    public static function getSearchQuery(array $args = []): \WP_Query
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'meta_query' => [],
            'tax_query' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        return new \WP_Query($args);
    }

    /**
     * 人気の助成金を取得
     */
    public static function getPopularGrants(int $limit = 5): array
    {
        $query = new \WP_Query([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => $limit,
            'meta_key' => 'views_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ]);

        return $query->posts;
    }
}

