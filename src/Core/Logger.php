<?php

namespace GrantInsight\Core;

/**
 * Logger Class
 * 
 * テーマ内のログ管理を行うクラス
 */
class Logger
{
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';

    private static string $log_dir;
    private static bool $initialized = false;

    /**
     * 初期化
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$log_dir = WP_CONTENT_DIR . '/logs/grant-insight/';
        
        // ログディレクトリを作成
        if (!file_exists(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
        }

        // .htaccessでログファイルへの直接アクセスを禁止
        $htaccess_file = self::$log_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all\n");
        }

        self::$initialized = true;

        // 管理画面にログビューアーを追加
        if (is_admin()) {
            add_action('admin_menu', [self::class, 'addAdminMenu']);
            add_action('wp_ajax_gi_clear_logs', [self::class, 'ajaxClearLogs']);
        }
    }

    /**
     * エラーログを記録
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * 警告ログを記録
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * 情報ログを記録
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * デバッグログを記録
     */
    public static function debug(string $message, array $context = []): void
    {
        if (WP_DEBUG) {
            self::log(self::LOG_LEVEL_DEBUG, $message, $context);
        }
    }

    /**
     * パフォーマンスログを記録
     */
    public static function performance(string $operation, float $execution_time, array $context = []): void
    {
        $context['execution_time'] = $execution_time;
        $context['memory_usage'] = memory_get_usage(true);
        $context['peak_memory'] = memory_get_peak_usage(true);
        
        $message = "Performance: {$operation} took {$execution_time}s";
        
        if ($execution_time > 1.0) {
            self::warning($message, $context);
        } else {
            self::info($message, $context);
        }
    }

    /**
     * データベースクエリログを記録
     */
    public static function queryLog(string $query, float $execution_time, array $context = []): void
    {
        $context['query'] = $query;
        $context['execution_time'] = $execution_time;
        
        $message = "DB Query took {$execution_time}s";
        
        if ($execution_time > 0.1) {
            self::warning($message, $context);
        } elseif (WP_DEBUG) {
            self::debug($message, $context);
        }
    }

    /**
     * ログを記録
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$initialized) {
            self::init();
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';

        $log_entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'request_uri' => $request_uri,
            'context' => $context
        ];

        // JSON形式でログを記録
        $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // 日付別のログファイルに記録
        $log_file = self::$log_dir . date('Y-m-d') . '.log';
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

        // 重要なエラーはWordPressのエラーログにも記録
        if ($level === self::LOG_LEVEL_ERROR) {
            error_log("Grant Insight Theme Error: {$message}");
        }
    }

    /**
     * ログファイル一覧を取得
     */
    public static function getLogFiles(): array
    {
        if (!self::$initialized) {
            self::init();
        }

        $files = glob(self::$log_dir . '*.log');
        $log_files = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $log_files[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }

        // 新しい順にソート
        usort($log_files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $log_files;
    }

    /**
     * ログファイルの内容を取得
     */
    public static function getLogContent(string $filename, int $lines = 100): array
    {
        $file_path = self::$log_dir . $filename;
        
        if (!file_exists($file_path)) {
            return [];
        }

        $content = file($file_path, FILE_IGNORE_NEW_LINES);
        $content = array_slice($content, -$lines); // 最後のN行を取得
        
        $logs = [];
        foreach ($content as $line) {
            $log_data = json_decode($line, true);
            if ($log_data) {
                $logs[] = $log_data;
            }
        }

        return array_reverse($logs); // 新しい順に並び替え
    }

    /**
     * ログ統計を取得
     */
    public static function getLogStats(): array
    {
        $log_files = self::getLogFiles();
        $total_size = 0;
        $total_entries = 0;
        $level_counts = [
            'ERROR' => 0,
            'WARNING' => 0,
            'INFO' => 0,
            'DEBUG' => 0
        ];

        foreach ($log_files as $file) {
            $total_size += $file['size'];
            
            // 今日のログファイルのエントリ数をカウント
            if ($file['filename'] === date('Y-m-d') . '.log') {
                $logs = self::getLogContent($file['filename'], 1000);
                $total_entries = count($logs);
                
                foreach ($logs as $log) {
                    $level = $log['level'] ?? 'INFO';
                    if (isset($level_counts[$level])) {
                        $level_counts[$level]++;
                    }
                }
            }
        }

        return [
            'total_files' => count($log_files),
            'total_size' => $total_size,
            'total_entries_today' => $total_entries,
            'level_counts' => $level_counts
        ];
    }

    /**
     * 古いログファイルを削除
     */
    public static function cleanupOldLogs(int $days = 30): int
    {
        $log_files = self::getLogFiles();
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        $deleted_count = 0;

        foreach ($log_files as $file) {
            if ($file['modified'] < $cutoff_time) {
                if (unlink($file['path'])) {
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }

    /**
     * 管理画面メニューを追加
     */
    public static function addAdminMenu(): void
    {
        add_management_page(
            'Grant Insight ログ',
            'Grant Insight ログ',
            'manage_options',
            'gi-logs',
            [self::class, 'renderLogViewer']
        );
    }

    /**
     * ログビューアーページを表示
     */
    public static function renderLogViewer(): void
    {
        $log_files = self::getLogFiles();
        $stats = self::getLogStats();
        $selected_file = $_GET['file'] ?? (isset($log_files[0]) ? $log_files[0]['filename'] : '');
        $logs = $selected_file ? self::getLogContent($selected_file, 200) : [];
        ?>
        <div class="wrap">
            <h1>Grant Insight ログビューアー</h1>
            
            <!-- 統計情報 -->
            <div class="gi-log-stats" style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="card">
                    <h3>今日のログ</h3>
                    <p><strong><?php echo number_format($stats['total_entries_today']); ?></strong> エントリ</p>
                </div>
                <div class="card">
                    <h3>エラー</h3>
                    <p style="color: #d63384;"><strong><?php echo number_format($stats['level_counts']['ERROR']); ?></strong> 件</p>
                </div>
                <div class="card">
                    <h3>警告</h3>
                    <p style="color: #fd7e14;"><strong><?php echo number_format($stats['level_counts']['WARNING']); ?></strong> 件</p>
                </div>
                <div class="card">
                    <h3>総ファイル数</h3>
                    <p><strong><?php echo number_format($stats['total_files']); ?></strong> ファイル</p>
                </div>
            </div>

            <!-- ファイル選択 -->
            <div class="gi-log-controls" style="margin: 20px 0;">
                <select id="log-file-select" onchange="location.href='?page=gi-logs&file=' + this.value">
                    <option value="">ログファイルを選択</option>
                    <?php foreach ($log_files as $file): ?>
                        <option value="<?php echo esc_attr($file['filename']); ?>" 
                                <?php selected($selected_file, $file['filename']); ?>>
                            <?php echo esc_html($file['filename']); ?> 
                            (<?php echo size_format($file['size']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="button" class="button" onclick="giClearLogs()">
                    ログをクリア
                </button>
                
                <button type="button" class="button" onclick="location.reload()">
                    更新
                </button>
            </div>

            <!-- ログ表示 -->
            <?php if (!empty($logs)): ?>
                <div class="gi-log-entries">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>時刻</th>
                                <th>レベル</th>
                                <th>メッセージ</th>
                                <th>ユーザー</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['timestamp']); ?></td>
                                    <td>
                                        <span class="gi-log-level gi-log-level-<?php echo strtolower($log['level']); ?>">
                                            <?php echo esc_html($log['level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                    <td><?php echo $log['user_id'] ? get_userdata($log['user_id'])->display_name : 'ゲスト'; ?></td>
                                    <td>
                                        <?php if (!empty($log['context'])): ?>
                                            <button type="button" class="button button-small" 
                                                    onclick="toggleLogContext(this)">詳細</button>
                                            <div class="gi-log-context" style="display: none; margin-top: 10px;">
                                                <pre><?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>ログエントリがありません。</p>
            <?php endif; ?>
        </div>

        <style>
        .gi-log-level {
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
        }
        .gi-log-level-error { background: #d63384; color: white; }
        .gi-log-level-warning { background: #fd7e14; color: white; }
        .gi-log-level-info { background: #0dcaf0; color: white; }
        .gi-log-level-debug { background: #6c757d; color: white; }
        .gi-log-context pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        </style>

        <script>
        function toggleLogContext(button) {
            const context = button.nextElementSibling;
            if (context.style.display === 'none') {
                context.style.display = 'block';
                button.textContent = '閉じる';
            } else {
                context.style.display = 'none';
                button.textContent = '詳細';
            }
        }

        function giClearLogs() {
            if (confirm('すべてのログファイルを削除しますか？')) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=gi_clear_logs&nonce=<?php echo wp_create_nonce('gi_logs_nonce'); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ログをクリアしました');
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

    /**
     * AJAX: ログクリア
     */
    public static function ajaxClearLogs(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_logs_nonce') || !current_user_can('manage_options')) {
            wp_die('Security check failed');
        }

        $log_files = self::getLogFiles();
        $deleted_count = 0;

        foreach ($log_files as $file) {
            if (unlink($file['path'])) {
                $deleted_count++;
            }
        }

        wp_send_json_success(['deleted_count' => $deleted_count]);
    }
}

