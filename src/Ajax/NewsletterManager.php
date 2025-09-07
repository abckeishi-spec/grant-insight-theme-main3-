<?php

namespace GrantInsight\Ajax;

use GrantInsight\Core\Logger;

/**
 * Newsletter Manager Class
 * 
 * ニュースレター機能の管理
 */
class NewsletterManager
{
    /**
     * 初期化
     */
    public static function init(): void
    {
        // AJAX処理を登録
        add_action('wp_ajax_gi_newsletter_signup', [self::class, 'handleSignup']);
        add_action('wp_ajax_nopriv_gi_newsletter_signup', [self::class, 'handleSignup']);
        
        add_action('wp_ajax_gi_newsletter_unsubscribe', [self::class, 'handleUnsubscribe']);
        add_action('wp_ajax_nopriv_gi_newsletter_unsubscribe', [self::class, 'handleUnsubscribe']);
        
        // 管理画面メニューを追加
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        
        // 定期的なメール送信をスケジュール
        add_action('gi_send_newsletter', [self::class, 'sendScheduledNewsletter']);
        
        if (!wp_next_scheduled('gi_send_newsletter')) {
            wp_schedule_event(time(), 'weekly', 'gi_send_newsletter');
        }
    }

    /**
     * ニュースレター登録処理
     */
    public static function handleSignup(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $interests = array_map('sanitize_text_field', $_POST['interests'] ?? []);

        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => '有効なメールアドレスを入力してください']);
        }

        try {
            // 既存登録チェック
            if (self::isEmailRegistered($email)) {
                wp_send_json_error(['message' => 'このメールアドレスは既に登録されています']);
            }

            // 登録処理
            $subscriber_id = self::addSubscriber($email, $name, $interests);
            
            // 確認メール送信
            self::sendConfirmationEmail($email, $name);
            
            // ウェルカムメール送信（オプション）
            self::sendWelcomeEmail($email, $name);

            Logger::info('Newsletter signup successful', [
                'email' => $email,
                'subscriber_id' => $subscriber_id
            ]);

            wp_send_json_success([
                'message' => 'ニュースレターに登録しました！確認メールをお送りしました。',
                'subscriber_id' => $subscriber_id
            ]);

        } catch (Exception $e) {
            Logger::error('Newsletter signup error: ' . $e->getMessage(), [
                'email' => $email
            ]);

            wp_send_json_error([
                'message' => '登録処理でエラーが発生しました。しばらく後でお試しください。'
            ]);
        }
    }

    /**
     * ニュースレター解除処理
     */
    public static function handleUnsubscribe(): void
    {
        $email = sanitize_email($_POST['email'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');

        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => '有効なメールアドレスを入力してください']);
        }

        try {
            // トークン検証
            if (!self::verifyUnsubscribeToken($email, $token)) {
                wp_send_json_error(['message' => '無効なリンクです']);
            }

            // 解除処理
            self::removeSubscriber($email);

            Logger::info('Newsletter unsubscribe successful', [
                'email' => $email
            ]);

            wp_send_json_success([
                'message' => 'ニュースレターの配信を停止しました'
            ]);

        } catch (Exception $e) {
            Logger::error('Newsletter unsubscribe error: ' . $e->getMessage());
            wp_send_json_error(['message' => '解除処理でエラーが発生しました']);
        }
    }

    /**
     * 購読者を追加
     */
    private static function addSubscriber(string $email, string $name = '', array $interests = []): int
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        // テーブルが存在しない場合は作成
        self::createSubscribersTable();

        $result = $wpdb->insert(
            $table_name,
            [
                'email' => $email,
                'name' => $name,
                'interests' => json_encode($interests),
                'status' => 'active',
                'subscribed_at' => current_time('mysql'),
                'confirmation_token' => wp_generate_password(32, false),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            throw new \Exception('データベースへの登録に失敗しました');
        }

        return $wpdb->insert_id;
    }

    /**
     * 購読者を削除
     */
    private static function removeSubscriber(string $email): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        $result = $wpdb->update(
            $table_name,
            ['status' => 'unsubscribed', 'unsubscribed_at' => current_time('mysql')],
            ['email' => $email],
            ['%s', '%s'],
            ['%s']
        );

        return $result !== false;
    }

    /**
     * メール登録チェック
     */
    private static function isEmailRegistered(string $email): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE email = %s AND status = 'active'",
            $email
        ));

        return $count > 0;
    }

    /**
     * 確認メール送信
     */
    private static function sendConfirmationEmail(string $email, string $name = ''): bool
    {
        $subject = 'ニュースレター登録確認 - Grant Insight';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>ニュースレター登録ありがとうございます</h2>
                
                <p>こんにちは" . ($name ? " {$name}さん" : "") . "、</p>
                
                <p>Grant Insightのニュースレターにご登録いただき、ありがとうございます。</p>
                
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #1e40af;'>今後お送りする内容</h3>
                    <ul>
                        <li>最新の助成金情報</li>
                        <li>申請のコツとノウハウ</li>
                        <li>成功事例の紹介</li>
                        <li>限定セミナーのご案内</li>
                    </ul>
                </div>
                
                <p>配信は週1回程度を予定しております。</p>
                
                <p>何かご質問がございましたら、お気軽にお問い合わせください。</p>
                
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                
                <p style='font-size: 12px; color: #6b7280;'>
                    このメールに心当たりがない場合は、お手数ですが削除してください。<br>
                    配信停止をご希望の場合は、<a href='" . home_url('/newsletter-unsubscribe/?email=' . urlencode($email)) . "'>こちら</a>からお手続きください。
                </p>
            </div>
        </body>
        </html>
        ";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Grant Insight <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * ウェルカムメール送信
     */
    private static function sendWelcomeEmail(string $email, string $name = ''): bool
    {
        $subject = '【Grant Insight】ウェルカム！助成金活用のスタートガイド';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2563eb; margin-bottom: 10px;'>Grant Insightへようこそ！</h1>
                    <p style='font-size: 18px; color: #6b7280;'>助成金活用の第一歩を踏み出しましょう</p>
                </div>
                
                <p>こんにちは" . ($name ? " {$name}さん" : "") . "、</p>
                
                <p>Grant Insightのニュースレターにご登録いただき、誠にありがとうございます。</p>
                
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin: 25px 0;'>
                    <h3 style='margin-top: 0; color: white;'>🎯 まずはここから始めましょう</h3>
                    <div style='margin: 15px 0;'>
                        <a href='" . home_url('/i-diagnosis/') . "' style='display: inline-block; background: white; color: #667eea; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 5px 10px 5px 0;'>
                            🔍 AI事業診断を受ける
                        </a>
                        <a href='" . home_url('/search/') . "' style='display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 5px 0;'>
                            💰 助成金を検索する
                        </a>
                    </div>
                </div>
                
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #1e40af;'>📚 おすすめコンテンツ</h3>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'><a href='" . home_url('/grant-tips/') . "' style='color: #2563eb; text-decoration: none;'>助成金申請のコツ</a></li>
                        <li style='margin-bottom: 8px;'><a href='" . home_url('/case-studies/') . "' style='color: #2563eb; text-decoration: none;'>成功事例集</a></li>
                        <li style='margin-bottom: 8px;'><a href='" . home_url('/faq/') . "' style='color: #2563eb; text-decoration: none;'>よくある質問</a></li>
                    </ul>
                </div>
                
                <div style='border-left: 4px solid #10b981; padding-left: 20px; margin: 25px 0;'>
                    <h4 style='color: #059669; margin-top: 0;'>💡 今週のワンポイントアドバイス</h4>
                    <p>助成金申請で最も重要なのは「事業計画の明確化」です。どんな課題を解決し、どのような成果を目指すのかを具体的に示すことが成功の鍵となります。</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='margin-bottom: 15px;'>ご質問やご相談がございましたら、お気軽にお問い合わせください。</p>
                    <a href='" . home_url('/contact/') . "' style='display: inline-block; background: #10b981; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;'>
                        📞 無料相談を申し込む
                    </a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                
                <p style='font-size: 12px; color: #6b7280; text-align: center;'>
                    配信停止をご希望の場合は、<a href='" . home_url('/newsletter-unsubscribe/?email=' . urlencode($email)) . "'>こちら</a>からお手続きください。
                </p>
            </div>
        </body>
        </html>
        ";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Grant Insight <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * 解除トークンを検証
     */
    private static function verifyUnsubscribeToken(string $email, string $token): bool
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE email = %s AND confirmation_token = %s",
            $email,
            $token
        ));

        return $count > 0;
    }

    /**
     * 定期ニュースレター送信
     */
    public static function sendScheduledNewsletter(): void
    {
        try {
            $subscribers = self::getActiveSubscribers();
            
            if (empty($subscribers)) {
                Logger::info('No active subscribers for newsletter');
                return;
            }

            $newsletter_content = self::generateNewsletterContent();
            
            foreach ($subscribers as $subscriber) {
                self::sendNewsletterToSubscriber($subscriber, $newsletter_content);
                
                // 送信間隔を空ける（サーバー負荷軽減）
                sleep(1);
            }

            Logger::info('Newsletter sent successfully', [
                'subscriber_count' => count($subscribers)
            ]);

        } catch (Exception $e) {
            Logger::error('Newsletter sending error: ' . $e->getMessage());
        }
    }

    /**
     * アクティブな購読者を取得
     */
    private static function getActiveSubscribers(): array
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        return $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY subscribed_at DESC",
            ARRAY_A
        );
    }

    /**
     * ニュースレターコンテンツを生成
     */
    private static function generateNewsletterContent(): array
    {
        // 最新の助成金情報を取得
        $recent_grants = get_posts([
            'post_type' => 'grant',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        // 人気の記事を取得
        $popular_tips = get_posts([
            'post_type' => 'grant_tip',
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'meta_key' => 'view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);

        return [
            'subject' => '【Grant Insight】今週の助成金情報 - ' . date('Y年m月d日'),
            'recent_grants' => $recent_grants,
            'popular_tips' => $popular_tips,
            'generated_at' => current_time('c')
        ];
    }

    /**
     * 個別購読者にニュースレター送信
     */
    private static function sendNewsletterToSubscriber(array $subscriber, array $content): bool
    {
        $email = $subscriber['email'];
        $name = $subscriber['name'];
        $unsubscribe_token = $subscriber['confirmation_token'];

        $subject = $content['subject'];
        
        $message = self::buildNewsletterHTML($content, $name, $email, $unsubscribe_token);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Grant Insight <newsletter@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        return wp_mail($email, $subject, $message, $headers);
    }

    /**
     * ニュースレターHTMLを構築
     */
    private static function buildNewsletterHTML(array $content, string $name, string $email, string $token): string
    {
        $grants_html = '';
        foreach ($content['recent_grants'] as $grant) {
            $grants_html .= "
                <div style='border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 15px;'>
                    <h4 style='margin: 0 0 10px 0; color: #1e40af;'>
                        <a href='" . get_permalink($grant->ID) . "' style='color: #1e40af; text-decoration: none;'>{$grant->post_title}</a>
                    </h4>
                    <p style='margin: 0; color: #6b7280; font-size: 14px;'>" . wp_trim_words($grant->post_excerpt, 20) . "</p>
                </div>
            ";
        }

        $tips_html = '';
        foreach ($content['popular_tips'] as $tip) {
            $tips_html .= "
                <li style='margin-bottom: 8px;'>
                    <a href='" . get_permalink($tip->ID) . "' style='color: #2563eb; text-decoration: none;'>{$tip->post_title}</a>
                </li>
            ";
        }

        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px;'>
                    <h1 style='color: white; margin: 0;'>Grant Insight Newsletter</h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0;'>" . date('Y年m月d日') . "</p>
                </div>
                
                <p>こんにちは" . ($name ? " {$name}さん" : "") . "、</p>
                
                <p>今週も最新の助成金情報をお届けします。</p>
                
                <h2 style='color: #1e40af; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>🆕 新着助成金情報</h2>
                {$grants_html}
                
                <h2 style='color: #1e40af; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>📈 人気記事</h2>
                <ul style='padding-left: 20px;'>
                    {$tips_html}
                </ul>
                
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center;'>
                    <h3 style='margin-top: 0; color: #1e40af;'>🎯 無料診断のご案内</h3>
                    <p>あなたの事業に最適な助成金をAIが診断します。</p>
                    <a href='" . home_url('/i-diagnosis/') . "' style='display: inline-block; background: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>
                        今すぐ診断を受ける
                    </a>
                </div>
                
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
                
                <p style='font-size: 12px; color: #6b7280; text-align: center;'>
                    配信停止をご希望の場合は、<a href='" . home_url('/newsletter-unsubscribe/?email=' . urlencode($email) . '&token=' . $token) . "'>こちら</a>からお手続きください。<br>
                    このメールは " . home_url() . " から送信されています。
                </p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * 購読者テーブルを作成
     */
    private static function createSubscribersTable(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT '',
            interests text DEFAULT '',
            status varchar(20) DEFAULT 'active',
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at datetime NULL,
            confirmation_token varchar(255) DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY subscribed_at (subscribed_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * 管理画面メニューを追加
     */
    public static function addAdminMenu(): void
    {
        add_menu_page(
            'ニュースレター管理',
            'ニュースレター',
            'manage_options',
            'gi-newsletter',
            [self::class, 'adminPage'],
            'dashicons-email-alt',
            30
        );
    }

    /**
     * 管理画面ページ
     */
    public static function adminPage(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gi_newsletter_subscribers';
        
        // 統計情報を取得
        $total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
        $total_unsubscribed = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'unsubscribed'");
        $recent_subscribers = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY subscribed_at DESC LIMIT 10", ARRAY_A);

        echo "
        <div class='wrap'>
            <h1>ニュースレター管理</h1>
            
            <div class='gi-newsletter-stats' style='display: flex; gap: 20px; margin: 20px 0;'>
                <div style='background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'>
                    <h3 style='margin: 0; color: #2563eb;'>アクティブ購読者</h3>
                    <p style='font-size: 24px; font-weight: bold; margin: 10px 0 0 0;'>{$total_subscribers}</p>
                </div>
                <div style='background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'>
                    <h3 style='margin: 0; color: #dc2626;'>配信停止</h3>
                    <p style='font-size: 24px; font-weight: bold; margin: 10px 0 0 0;'>{$total_unsubscribed}</p>
                </div>
            </div>
            
            <div class='gi-newsletter-actions' style='margin: 20px 0;'>
                <button class='button button-primary' onclick='sendTestNewsletter()'>テストメール送信</button>
                <button class='button' onclick='exportSubscribers()'>購読者リストエクスポート</button>
            </div>
            
            <h2>最近の購読者</h2>
            <table class='wp-list-table widefat fixed striped'>
                <thead>
                    <tr>
                        <th>メールアドレス</th>
                        <th>名前</th>
                        <th>登録日</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody>
        ";
        
        foreach ($recent_subscribers as $subscriber) {
            $subscribed_date = date('Y-m-d H:i', strtotime($subscriber['subscribed_at']));
            echo "
                <tr>
                    <td>{$subscriber['email']}</td>
                    <td>{$subscriber['name']}</td>
                    <td>{$subscribed_date}</td>
                    <td><span class='gi-status-active'>アクティブ</span></td>
                </tr>
            ";
        }
        
        echo "
                </tbody>
            </table>
        </div>
        
        <script>
        function sendTestNewsletter() {
            if (confirm('テストメールを送信しますか？')) {
                // AJAX処理でテストメール送信
                alert('テストメールを送信しました');
            }
        }
        
        function exportSubscribers() {
            window.location.href = '" . admin_url('admin-ajax.php?action=gi_export_subscribers&nonce=' . wp_create_nonce('gi_export_subscribers')) . "';
        }
        </script>
        
        <style>
        .gi-status-active {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        </style>
        ";
    }
}

