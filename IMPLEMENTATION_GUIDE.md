# Grant Insight Phase 1 実装ガイド

## 📋 概要
このガイドでは、Grant Insight WordPressテーマのPhase 1改修を既存のサイトに実装する手順を説明します。

## 🎯 Phase 1 改修内容

### 1. セキュリティ・エラーハンドリングの統一化
- すべてのAJAX関数にnonce検証を追加
- 入力値の再帰的サニタイズ
- try-catch文による例外処理
- WP_Error統一ハンドリング
- エラーログ記録機能

### 2. 件数表示の動的化
- カテゴリー別助成金件数の動的取得
- 都道府県別助成金件数の動的取得
- キャッシュ機能（1時間）
- JavaScriptによる自動更新
- ショートコード対応

### 3. AI診断機能のバックエンド実装
- 診断API実装
- 診断履歴保存機能
- マッチングアルゴリズム
- エラー時のフォールバック機能
- セッション管理

## 📁 ファイル構成

```
/wp-content/themes/your-theme/
├── ajax-handlers-improved.php    # 改善版AJAXハンドラー
├── helpers-improved.php          # 改善版ヘルパー関数
├── grant-counts.php              # 動的件数取得機能
├── ai-diagnosis.php              # AI診断機能
├── functions-integration.php     # 統合用ファイル
└── assets/
    └── js/
        ├── ai-diagnosis.js       # AI診断フロントエンド
        └── dynamic-counts.js     # 動的件数更新JS
```

## 🚀 実装手順

### ステップ1: ファイルのアップロード
1. 以下のファイルをテーマディレクトリにアップロード：
   - `ajax-handlers-improved.php`
   - `helpers-improved.php`
   - `grant-counts.php`
   - `ai-diagnosis.php`
   - `functions-integration.php`

2. JavaScriptファイルをアップロード：
   - `assets/js/ai-diagnosis.js`
   - `assets/js/dynamic-counts.js`

### ステップ2: functions.phpの修正
既存の`functions.php`の最後に以下を追加：

```php
// Phase 1 改修の読み込み
require_once get_template_directory() . '/functions-integration.php';
```

### ステップ3: 既存AJAXハンドラーの無効化
既存の`ajax-handlers.php`の読み込みをコメントアウト：

```php
// require_once get_template_directory() . '/ajax-handlers.php';
```

### ステップ4: データベーステーブルの作成
管理画面にアクセスして、自動的にAI診断用のテーブルが作成されることを確認。

## 🔧 使用方法

### 動的件数表示

#### HTMLでの使用例
```html
<!-- カテゴリー件数 -->
<span data-category-count="it-digital" data-format="%d件" class="animate-count">読み込み中...</span>

<!-- 都道府県件数 -->
<span data-prefecture-count="tokyo" data-format="%d件">読み込み中...</span>

<!-- 総件数 -->
<span data-total-count data-format="全%d件">読み込み中...</span>
```

#### ショートコードでの使用例
```
[grant_count type="category" slug="it-digital" format="%d件"]
[grant_count type="prefecture" slug="tokyo" format="%d件"]
[grant_count type="total" format="全%d件"]
```

### AI診断機能

#### HTML実装例
```html
<!-- 診断開始ボタン -->
<button class="ai-diagnosis-start">AI診断を開始</button>

<!-- 診断モーダル -->
<div id="ai-diagnosis-modal" style="display:none;">
    <div class="ai-diagnosis-content">
        <!-- 診断コンテンツがJavaScriptで動的に生成されます -->
    </div>
    <div class="ai-diagnosis-loading" style="display:none;">
        診断中...
    </div>
    <div class="ai-diagnosis-error"></div>
</div>
```

## 🔍 動作確認

### 1. セキュリティ機能の確認
- ブラウザの開発者ツールでNetworkタブを開く
- AJAXリクエストのレスポンスを確認
- nonceエラーが出ないことを確認

### 2. 件数表示の確認
- ページ読み込み時に件数が自動更新されることを確認
- 開発者コンソールでエラーが出ないことを確認

### 3. AI診断の確認
- 診断ボタンをクリックして診断フローが開始されることを確認
- 各ステップで回答を選択できることを確認
- 診断結果が表示されることを確認

## ⚠️ 注意事項

### キャッシュ
- 件数表示は1時間キャッシュされます
- 手動でキャッシュをクリアする場合：
  ```php
  gi_clear_grant_counts_cache();
  ```

### パフォーマンス
- 初回アクセス時は件数取得に時間がかかる場合があります
- キャッシュが効いた後は高速に動作します

### セキュリティ
- 本番環境ではWP_DEBUGをfalseに設定してください
- エラーログは定期的に確認してください

## 🐛 トラブルシューティング

### 件数が表示されない場合
1. JavaScriptコンソールでエラーを確認
2. AJAXリクエストが正常に送信されているか確認
3. nonceが正しく設定されているか確認

### AI診断が動作しない場合
1. データベーステーブルが作成されているか確認：
   ```sql
   SHOW TABLES LIKE 'wp_gi_diagnosis_history';
   ```
2. JavaScriptエラーを確認
3. PHPエラーログを確認

### パフォーマンスが遅い場合
1. オブジェクトキャッシュ（Redis/Memcached）の導入を検討
2. データベースインデックスの最適化
3. CDNの利用を検討

## 📊 管理画面での確認

管理画面にアクセスすると、以下の通知が表示されます：

✅ すべての機能が有効な場合：
> **Grant Insight Phase 1改修:** すべての機能が正常に読み込まれています。

⚠️ 一部の機能が無効な場合：
> **Grant Insight Phase 1改修:** 一部の機能が読み込まれていません。
> - セキュリティ・エラーハンドリング統一化: ✅ 有効
> - 件数表示の動的化: ✅ 有効
> - AI診断機能: ✅ 有効
> - 改善版ヘルパー関数: ✅ 有効

## 🔄 アップデート方法

### ファイルの更新
1. 新しいバージョンのファイルをアップロード
2. ブラウザキャッシュをクリア
3. WordPressのキャッシュプラグインがある場合はクリア

### データベースの更新
AI診断テーブルの構造が変更された場合：
```php
// functions.phpまたは管理画面から実行
gi_create_diagnosis_tables();
```

## 📞 サポート

問題が発生した場合は、以下の情報を含めてお問い合わせください：
- WordPressバージョン
- PHPバージョン
- エラーメッセージ（あれば）
- 実行した手順

## 📝 変更履歴

### Version 1.0.0 (2024-01-XX)
- 初回リリース
- セキュリティ強化
- 動的件数表示機能
- AI診断機能

---

**注意**: このガイドは開発環境でテスト後、本番環境に適用することを推奨します。