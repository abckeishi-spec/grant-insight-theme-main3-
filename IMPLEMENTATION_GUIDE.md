# 🤖 助成金診断サイト Phase 1 実装ガイド

## 📋 概要
このガイドでは、Phase 1の改修内容の実装方法とテスト手順を説明します。

## 🔥 Phase 1 実装内容

### ✅ タスク1: セキュリティ・エラーハンドリングの統一化
- **ファイル**: `ajax-handlers-improved.php`
- **主な改善点**:
  - 全AJAX関数への統一的なnonce検証
  - 再帰的サニタイズ関数の実装
  - try-catch文による例外処理
  - WP_Errorの統一ハンドリング
  - エラーログ機能の実装

### ✅ タスク2: 件数表示の動的化
- **ファイル**: `grant-counts.php`
- **主な機能**:
  - カテゴリー別件数取得: `gi_get_category_count()`
  - 都道府県別件数取得: `gi_get_prefecture_count()`
  - キャッシュ機能（1時間）
  - ショートコード対応
  - AJAX API対応

### ✅ タスク3: AI診断機能のバックエンド
- **ファイル**: `ai-diagnosis.php`
- **主な機能**:
  - 診断APIエンドポイント
  - 診断履歴保存（データベース）
  - マッチングアルゴリズム
  - フォールバック機能
  - セッション管理

## 🚀 実装手順

### 1. ファイルの配置
```bash
# WordPressテーマディレクトリに以下のファイルを配置
/wp-content/themes/your-theme/
├── ajax-handlers-improved.php  # 改善版AJAX処理
├── grant-counts.php            # 件数動的取得
├── ai-diagnosis.php            # AI診断機能
└── functions-integration.php   # 統合ファイル
```

### 2. functions.phpへの統合

#### オプション A: 新規ファイルを読み込む場合
```php
// functions.php の最初の方に追加
require_once get_template_directory() . '/functions-integration.php';
```

#### オプション B: 既存のajax-handlers.phpを置き換える場合
1. 既存の`ajax-handlers.php`をバックアップ
2. `ajax-handlers-improved.php`の内容を`ajax-handlers.php`にコピー
3. `grant-counts.php`と`ai-diagnosis.php`を読み込み

```php
// functions.php に追加
require_once get_template_directory() . '/grant-counts.php';
require_once get_template_directory() . '/ai-diagnosis.php';
```

### 3. データベーステーブルの作成
テーマを再有効化するか、以下のコードを実行：
```php
// 一時的にfunctions.phpに追加して実行
if (function_exists('gi_create_diagnosis_tables')) {
    gi_create_diagnosis_tables();
}
```

## 📝 使用例

### 件数表示の動的化

#### PHPでの使用
```php
// カテゴリー別件数
$count = gi_get_category_count('it-digital');
echo "IT・デジタル: {$count}件";

// 都道府県別件数
$count = gi_get_prefecture_count('tokyo');
echo "東京都: {$count}件";

// 表示用ヘルパー
echo gi_display_grant_count('category', 'manufacturing', '%d件の助成金');
```

#### ショートコードでの使用
```html
<!-- カテゴリー別 -->
[grant_count type="category" slug="it-digital"]

<!-- 都道府県別 -->
[grant_count type="prefecture" slug="tokyo"]

<!-- 全体件数 -->
[grant_count type="total"]
```

#### JavaScriptでの使用
```javascript
// AJAX経由で件数取得
jQuery.ajax({
    url: gi_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'get_grant_counts',
        nonce: gi_ajax.nonce,
        type: 'category',
        slugs: ['it-digital', 'manufacturing', 'retail-service']
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data); // {it-digital: 125, manufacturing: 98, ...}
        }
    }
});
```

### AI診断機能の使用

#### 診断APIの呼び出し
```javascript
const answers = {
    business_type: 'corporation',
    industry: 'it',
    purpose: ['equipment', 'digitalization'],
    employees: '21-50',
    location: 'tokyo',
    budget: '500-1000',
    urgency: 'immediate'
};

jQuery.ajax({
    url: gi_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'gi_ai_diagnosis',
        nonce: gi_ajax.diagnosis_nonce,
        answers: JSON.stringify(answers)
    },
    success: function(response) {
        if (response.success) {
            console.log('マッチした助成金:', response.data.matched_grants);
            console.log('信頼度スコア:', response.data.confidence_score);
            console.log('推奨事項:', response.data.recommendations);
        }
    }
});
```

## 🧪 テスト手順

### 1. セキュリティテスト
```javascript
// Nonceなしでリクエスト（エラーになるはず）
jQuery.ajax({
    url: gi_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'gi_load_grants',
        search: 'テスト'
    },
    error: function(xhr) {
        console.log('Expected error:', xhr.responseJSON); // セキュリティエラー
    }
});
```

### 2. 件数表示テスト
```php
// テストコード
$categories = ['it-digital', 'manufacturing', 'retail-service'];
foreach ($categories as $cat) {
    $count = gi_get_category_count($cat);
    echo "{$cat}: {$count}件\n";
}

// キャッシュテスト
$start = microtime(true);
gi_get_category_count('it-digital'); // 初回（遅い）
$time1 = microtime(true) - $start;

$start = microtime(true);
gi_get_category_count('it-digital'); // 2回目（キャッシュから高速）
$time2 = microtime(true) - $start;

echo "初回: {$time1}秒, キャッシュ: {$time2}秒\n";
```

### 3. AI診断テスト
```php
// 診断履歴の確認
global $wpdb;
$table = $wpdb->prefix . 'gi_diagnosis_history';
$results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 5");
foreach ($results as $row) {
    echo "診断ID: {$row->id}, 信頼度: {$row->confidence_score}%\n";
}
```

## 🐛 トラブルシューティング

### よくある問題と解決方法

#### 1. Nonceエラーが発生する
```php
// functions.phpに以下を追加
add_action('wp_enqueue_scripts', function() {
    wp_localize_script('jquery', 'gi_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_ajax_nonce'),
        'diagnosis_nonce' => wp_create_nonce('gi_ai_diagnosis_nonce')
    ));
}, 20);
```

#### 2. 件数が0になる
```php
// タクソノミーの存在確認
$taxonomies = get_taxonomies();
var_dump($taxonomies); // grant_category, grant_prefectureが存在するか確認

// キャッシュクリア
gi_clear_grant_counts_cache();
```

#### 3. データベーステーブルが作成されない
```sql
-- 手動でテーブル作成
CREATE TABLE wp_gi_diagnosis_history (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED DEFAULT NULL,
    session_id varchar(255) DEFAULT NULL,
    answers longtext NOT NULL,
    results longtext NOT NULL,
    confidence_score float DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY session_id (session_id),
    KEY created_at (created_at)
);
```

## 📊 パフォーマンス最適化

### キャッシュ戦略
- 件数データ: 1時間キャッシュ
- 診断結果: セッション単位でキャッシュ
- 静的リソース: ブラウザキャッシュ活用

### データベース最適化
```sql
-- インデックス追加（必要に応じて）
ALTER TABLE wp_postmeta ADD INDEX idx_grant_amount (meta_key, meta_value);
ALTER TABLE wp_gi_diagnosis_history ADD INDEX idx_user_session (user_id, session_id);
```

## 🔒 セキュリティチェックリスト

- [ ] 全AJAX関数にnonce検証実装
- [ ] 入力値の完全なサニタイズ
- [ ] SQLインジェクション対策（プリペアドステートメント使用）
- [ ] XSS対策（適切なエスケープ）
- [ ] CSRF対策（nonce使用）
- [ ] 適切なエラーハンドリング
- [ ] 本番環境でのデバッグ情報非表示

## 📞 サポート

問題が発生した場合は、以下の情報を含めて報告してください：
1. WordPressバージョン
2. PHPバージョン
3. エラーログ（`wp-content/debug.log`）
4. ブラウザコンソールのエラー
5. 実行した操作の詳細

---

**Last Updated**: 2024-09-07
**Version**: 1.0.0
**Author**: Grant Insight Development Team