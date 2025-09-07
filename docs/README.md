# Grant Insight WordPress Theme

補助金・助成金診断サイト用のWordPressテーマです。

## 🚀 クイックスタート

### 1. インストール

1. このテーマフォルダを `/wp-content/themes/` にアップロード
2. WordPress管理画面 > 外観 > テーマ から「Grant Insight」を有効化
3. functions.phpに以下を追加（既に存在する場合はスキップ）:

```php
// Phase 1 機能の読み込み
require_once get_template_directory() . '/functions-integration.php';
require_once get_template_directory() . '/quick-setup.php';
```

### 2. 必要なプラグイン

#### 必須
- **Advanced Custom Fields (ACF)** - カスタムフィールド管理
  - インストールしない場合は代替機能で動作しますが、機能が制限されます

#### 推奨
- **Contact Form 7** - お問い合わせフォーム
- **WP Super Cache** または **W3 Total Cache** - キャッシュ最適化

### 3. 初期設定

#### 自動設定される項目
- ✅ カスタム投稿タイプ（補助金、ヒント、ツール、事例）
- ✅ カスタムタクソノミー（補助金カテゴリー）
- ✅ データベーステーブル（診断履歴、エラーログ、パフォーマンス）
- ✅ 基本ページ（FAQ、AI診断、お問い合わせ）
- ✅ サンプルデータ（開発環境のみ）

#### 手動設定が必要な項目
1. **パーマリンク設定**
   - 設定 > パーマリンク > 「投稿名」を選択 > 変更を保存

2. **メニュー設定**
   - 外観 > メニュー > 新規メニュー作成
   - 必要なページを追加

3. **カスタマイザー設定**
   - 外観 > カスタマイズ
   - ヘッダー/フッターの色設定
   - ロゴ画像のアップロード

## 📁 ファイル構成

```
grant-insight-theme/
├── 📄 Core Files
│   ├── functions.php              # 既存のテーマ関数
│   ├── functions-integration.php  # Phase 1機能統合
│   ├── quick-setup.php           # 初期セットアップ
│   └── style.css                 # テーマスタイル
│
├── 📂 Phase 1 Features (16 Tasks)
│   ├── ajax-handlers-improved.php    # Task 1&7: AJAX処理
│   ├── grant-counts.php              # Task 2: 動的件数
│   ├── ai-diagnosis.php              # Task 3: AI診断
│   ├── customizer-settings.php       # Task 4: カスタマイザー
│   ├── icon-management.php           # Task 5: アイコン管理
│   ├── helpers-improved.php          # Task 6: ヘルパー関数
│   ├── safe-output-functions.php     # Task 8: 安全な出力
│   ├── data-validation.php           # Task 9: データ検証
│   ├── individual-categories.php     # Task 10: 個人向けカテゴリ
│   ├── category-display.php          # Task 11: カテゴリ表示
│   ├── search-filter-stability.php   # Task 12: 検索安定化
│   ├── error-guidance-system.php     # Task 13: エラー/ガイダンス
│   ├── responsive-accessibility.php  # Task 14: レスポンシブ
│   ├── admin-enhancements.php        # Task 15: 管理画面強化
│   └── system-optimization.php       # Task 16: システム最適化
│
├── 📂 Templates
│   ├── archive-grant.php         # 補助金一覧
│   ├── single-grant.php          # 補助金詳細
│   ├── page-faq.php             # FAQページ
│   └── page-contact.php         # お問い合わせ
│
└── 📂 Assets
    ├── js/
    │   ├── ai-diagnosis.js      # AI診断JS
    │   ├── dynamic-counts.js    # 動的件数JS
    │   └── help-system.js       # ヘルプシステムJS
    └── css/
        └── (スタイルファイル)
```

## ✨ 主要機能

### 🤖 AI診断機能
- 10問の質問で最適な補助金を提案
- 診断履歴の保存
- スコアリングアルゴリズム

使用方法:
```php
// ショートコード
[gi_ai_diagnosis]

// テンプレート
<?php echo do_shortcode('[gi_ai_diagnosis]'); ?>
```

### 🔍 高度な検索機能
- オートコンプリート
- 検索履歴保存
- ゼロ結果時の提案
- フィルター条件保存

### 📊 管理画面拡張
- カスタムダッシュボードウィジェット
- CSV インポート/エクスポート
- 一括操作機能
- 詳細な統計表示

### ⚡ パフォーマンス最適化
- データベースインデックス自動作成
- 多層キャッシングシステム
- WebP画像自動生成
- 遅延読み込み対応

### ♿ アクセシビリティ
- WCAG 2.1 Level AA準拠
- キーボードナビゲーション
- スクリーンリーダー対応
- 高コントラストモード

## 🛠️ トラブルシューティング

### ACFが無い場合のエラー
`get_field()` 関数が未定義エラーが出る場合:
- quick-setup.phpが読み込まれているか確認
- ACFプラグインをインストール

### データベーステーブルが作成されない
管理画面で以下を実行:
```php
// functions.phpに一時的に追加
add_action('admin_init', function() {
    gi_create_diagnosis_tables();
    gi_create_error_log_table();
});
```

### キャッシュ関連の問題
- 管理画面 > ツール > DB最適化 > キャッシュクリア
- または: `wp_cache_flush()` を実行

## 📝 カスタマイズ

### 新しい補助金フィールドを追加
```php
// ACF使用時
add_action('acf/init', function() {
    acf_add_local_field(array(
        'key' => 'field_custom',
        'label' => 'カスタムフィールド',
        'name' => 'custom_field',
        'type' => 'text',
        'parent' => 'group_grant_fields'
    ));
});

// ACF不使用時
add_action('add_meta_boxes', function() {
    add_meta_box(
        'custom_field',
        'カスタムフィールド',
        'render_custom_field_box',
        'grant'
    );
});
```

### カスタムウィジェットの追加
```php
// ダッシュボードウィジェット追加
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'custom_widget',
        'カスタムウィジェット',
        'render_custom_widget'
    );
});
```

## 🔒 セキュリティ

### 実装済みのセキュリティ対策
- ✅ 全AJAXエンドポイントでnonce検証
- ✅ XSS対策（出力エスケープ）
- ✅ SQLインジェクション対策（プリペアドステートメント）
- ✅ CSRF対策
- ✅ レート制限

### セキュリティベストプラクティス
```php
// 安全な出力
echo gi_safe_escape($user_input);

// 安全なURL
echo gi_safe_url($url);

// 安全な属性
echo gi_safe_attr($attribute);
```

## 📈 パフォーマンスモニタリング

管理バーに表示される情報:
- ⚡ ページ読み込み時間
- 💾 メモリ使用量
- 🔍 クエリ数

詳細は: ツール > パフォーマンス

## 🤝 サポート

### ドキュメント
- [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) - 実装ガイド
- [PHASE1_COMPLETION_REPORT.md](PHASE1_COMPLETION_REPORT.md) - 完了レポート

### 必要な環境
- WordPress: 5.0以上
- PHP: 7.4以上
- MySQL: 5.6以上
- メモリ: 128MB以上推奨

## 📄 ライセンス

このテーマはクライアント専用のカスタムテーマです。

---

## 🎯 クイックテスト

インストール後の動作確認:

1. **補助金投稿の作成**
   - 投稿 > 補助金管理 > 新規追加

2. **AI診断のテスト**
   - ページ > AI診断 を表示

3. **検索機能のテスト**
   - フロントページで補助金検索

4. **管理画面の確認**
   - ダッシュボードでウィジェット表示確認
   - ツール > DB最適化 でシステム状態確認

## 更新履歴

### Version 1.0.0 (2024-01)
- 初回リリース
- 16タスク完全実装

---

テーマの有効化後、管理画面上部に表示される「セットアップ状態」で、必要な設定が完了しているか確認してください。