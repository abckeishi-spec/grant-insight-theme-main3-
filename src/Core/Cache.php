<?php

namespace GrantInsight\Core;

/**
 * Cache Management Class
 * 
 * WordPressのキャッシュ機能を活用した高度なキャッシュシステム
 */
class Cache
{
    const CACHE_GROUP = 'grant_insight';
    const DEFAULT_EXPIRATION = 3600; // 1時間
    
    /**
     * キャッシュキーのプレフィックス
     */
    private static string $prefix = 'gi_';

    /**
     * データを取得（キャッシュ優先）
     */
    public static function get(string $key, callable $callback = null, int $expiration = self::DEFAULT_EXPIRATION)
    {
        $cache_key = self::buildKey($key);
        $cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);

        // キャッシュヒット
        if (false !== $cached_data) {
            return $cached_data;
        }

        // キャッシュミス - コールバックでデータを生成
        if ($callback && is_callable($callback)) {
            $data = $callback();
            self::set($key, $data, $expiration);
            return $data;
        }

        return false;
    }

    /**
     * データをキャッシュに保存
     */
    public static function set(string $key, $data, int $expiration = self::DEFAULT_EXPIRATION): bool
    {
        $cache_key = self::buildKey($key);
        return wp_cache_set($cache_key, $data, self::CACHE_GROUP, $expiration);
    }

    /**
     * キャッシュを削除
     */
    public static function delete(string $key): bool
    {
        $cache_key = self::buildKey($key);
        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * パターンに基づいてキャッシュを削除
     */
    public static function deleteByPattern(string $pattern): int
    {
        global $wp_object_cache;
        $deleted_count = 0;

        if (isset($wp_object_cache->cache[self::CACHE_GROUP])) {
            $cache_group = $wp_object_cache->cache[self::CACHE_GROUP];
            
            foreach (array_keys($cache_group) as $cache_key) {
                if (fnmatch(self::buildKey($pattern), $cache_key)) {
                    wp_cache_delete($cache_key, self::CACHE_GROUP);
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }

    /**
     * グループ全体のキャッシュをクリア
     */
    public static function flush(): bool
    {
        return wp_cache_flush_group(self::CACHE_GROUP);
    }

    /**
     * 助成金関連のキャッシュをクリア
     */
    public static function clearGrantCaches(int $grant_id = null): void
    {
        // 全体統計のキャッシュをクリア
        self::delete('grant_statistics');
        
        // 人気の助成金キャッシュをクリア
        self::deleteByPattern('popular_grants_*');
        
        // 締切が近い助成金キャッシュをクリア
        self::deleteByPattern('deadline_soon_*');
        
        // 最新の助成金キャッシュをクリア
        self::deleteByPattern('latest_grants_*');
        
        // 特定の助成金のキャッシュをクリア
        if ($grant_id) {
            self::delete("grant_{$grant_id}");
            self::deleteByPattern("related_grants_{$grant_id}_*");
        }
        
        // 検索結果のキャッシュをクリア
        self::deleteByPattern('search_*');
    }

    /**
     * タクソノミー関連のキャッシュをクリア
     */
    public static function clearTaxonomyCaches(string $taxonomy = null): void
    {
        if ($taxonomy) {
            self::deleteByPattern("taxonomy_{$taxonomy}_*");
        } else {
            self::deleteByPattern('taxonomy_*');
        }
    }

    /**
     * キャッシュキーを構築
     */
    private static function buildKey(string $key): string
    {
        return self::$prefix . $key;
    }

    /**
     * キャッシュ統計情報を取得
     */
    public static function getStats(): array
    {
        global $wp_object_cache;
        
        $stats = [
            'total_keys' => 0,
            'group_keys' => 0,
            'memory_usage' => 0
        ];

        if (isset($wp_object_cache->cache)) {
            $stats['total_keys'] = array_sum(array_map('count', $wp_object_cache->cache));
            
            if (isset($wp_object_cache->cache[self::CACHE_GROUP])) {
                $stats['group_keys'] = count($wp_object_cache->cache[self::CACHE_GROUP]);
                $stats['memory_usage'] = strlen(serialize($wp_object_cache->cache[self::CACHE_GROUP]));
            }
        }

        return $stats;
    }

    /**
     * 初期化処理
     */
    public static function init(): void
    {
        // 投稿の保存・更新時にキャッシュをクリア
        add_action('save_post', [self::class, 'onPostSave'], 10, 2);
        add_action('delete_post', [self::class, 'onPostDelete']);
        
        // タクソノミーの更新時にキャッシュをクリア
        add_action('created_term', [self::class, 'onTermChange'], 10, 3);
        add_action('edited_term', [self::class, 'onTermChange'], 10, 3);
        add_action('delete_term', [self::class, 'onTermChange'], 10, 3);
        
        // 管理画面にキャッシュ統計を表示
        if (is_admin()) {
            add_action('wp_dashboard_setup', [self::class, 'addDashboardWidget']);
        }
    }

    /**
     * 投稿保存時のキャッシュクリア
     */
    public static function onPostSave(int $post_id, \WP_Post $post): void
    {
        if ($post->post_type === 'grant') {
            self::clearGrantCaches($post_id);
        }
    }

    /**
     * 投稿削除時のキャッシュクリア
     */
    public static function onPostDelete(int $post_id): void
    {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'grant') {
            self::clearGrantCaches($post_id);
        }
    }

    /**
     * タクソノミー変更時のキャッシュクリア
     */
    public static function onTermChange(int $term_id, int $tt_id, string $taxonomy): void
    {
        if (in_array($taxonomy, ['prefecture', 'grant_category'])) {
            self::clearTaxonomyCaches($taxonomy);
            self::clearGrantCaches(); // 関連する助成金キャッシュもクリア
        }
    }

    /**
     * ダッシュボードウィジェットを追加
     */
    public static function addDashboardWidget(): void
    {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'gi_cache_stats',
                'Grant Insight キャッシュ統計',
                [self::class, 'renderDashboardWidget']
            );
        }
    }

    /**
     * ダッシュボードウィジェットの表示
     */
    public static function renderDashboardWidget(): void
    {
        $stats = self::getStats();
        ?>
        <div class="gi-cache-stats">
            <p><strong>総キャッシュキー数:</strong> <?php echo number_format($stats['total_keys']); ?></p>
            <p><strong>Grant Insightキー数:</strong> <?php echo number_format($stats['group_keys']); ?></p>
            <p><strong>メモリ使用量:</strong> <?php echo size_format($stats['memory_usage']); ?></p>
            
            <p class="submit">
                <a href="<?php echo admin_url('admin.php?page=gi-cache-management'); ?>" class="button">
                    キャッシュ管理
                </a>
                <button type="button" class="button button-secondary" onclick="giClearCache()">
                    キャッシュクリア
                </button>
            </p>
        </div>
        
        <script>
        function giClearCache() {
            if (confirm('すべてのキャッシュをクリアしますか？')) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=gi_clear_cache&nonce=<?php echo wp_create_nonce('gi_cache_nonce'); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('キャッシュをクリアしました');
                        location.reload();
                    } else {
                        alert('エラーが発生しました');
                    }
                });
            }
        }
        </script>
        <?php
    }
}

