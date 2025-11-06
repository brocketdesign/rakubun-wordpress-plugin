# インストールガイド

Rakubun AI コンテンツジェネレーター プラグインをインストール・設定するための完全なガイドです。

## クイックスタート

以下の手順に従ってプラグインをインストール・設定してください。

### ステップ1: プラグインのダウンロードとインストール

#### 方法A: GitHubリリースページからダウンロード（推奨）

1. [Rakubun AI Content Generator リリースページ](https://github.com/brocketdesign/rakubun-wordpress-plugin/releases) にアクセス
2. 最新バージョンの **rakubun-ai-content-generator.zip** をダウンロード
3. ダウンロード完了後、以下のいずれかの方法で インストール

#### 方法B: WordPress管理画面からアップロード

1. WordPressの管理画面で **プラグイン → 新規追加** にアクセス
2. **プラグインをアップロード** をクリック
3. ダウンロードした `rakubun-ai-content-generator.zip` ファイルを選択
4. **今すぐインストール** をクリック
5. **プラグインを有効化** をクリック

#### 方法C: FTP/ファイルマネージャーでアップロード

1. ダウンロードした `rakubun-ai-content-generator` フォルダを解凍
2. FTPまたはファイルマネージャーを使用して `/wp-content/plugins/` にアップロード
3. WordPressの管理画面 **プラグイン** から「Rakubun AI Content Generator」を探す
4. **有効化** をクリック

### ステップ2: プラグイン登録

外部ダッシュボード（app.rakubun.com）での管理のため、プラグインの登録が必要です：

1. WordPressの管理画面で **AI コンテンツ → 設定** にアクセス
2. **接続ステータス** セクションを確認
3. プラグインがまだ登録されていない場合は、**プラグイン登録** ボタンをクリック
4. プラグインが自動的にダッシュボードに登録されます
5. 登録が完了したら、**API接続テスト** ボタンで接続を確認

### ステップ3: 外部ダッシュボードでの設定

プラグイン登録後、以下の設定を外部ダッシュボードで完了する必要があります：

#### OpenAI APIキーの登録

1. [Rakubun 管理ダッシュボード](https://app.rakubun.com) にログイン
2. **API 設定** → **OpenAI** セクションを開く
3. [OpenAI プラットフォーム](https://platform.openai.com/api-keys) からAPIキーを取得
   - OpenAI アカウントにログイン
   - **API キー** セクションに移動
   - **新しいシークレットキーを作成** をクリック
   - キーの名前を入力（例: "WordPress Plugin"）
   - 生成されたキーをコピー（二度と見ることができません）
4. ダッシュボードにキーを貼り付けて保存

**重要な注意事項：**
- OpenAI では有料アカウントにクレジットが必要です
- [OpenAI 料金](https://openai.com/pricing) を参照してください：
  - GPT-4: 入力トークン 1K あたり約 $0.03、出力トークン 1K あたり約 $0.06
  - DALL-E 3: 品質とサイズに応じて 1 画像あたり $0.040 ～ $0.080
- APIキーは安全に保管し、公開してはいけません

#### Stripe APIキーの登録

**テストモード設定（まず推奨）**

1. [Stripe](https://stripe.com/) でアカウントを作成
2. [Stripe テストダッシュボード](https://dashboard.stripe.com/test/dashboard) にアクセス
3. **開発者 → APIキー** に移動
4. **公開キー** をコピー（`pk_test_` で始まります）
5. **秘密キー** をクリックして表示し、コピー（`sk_test_` で始まります）
6. Rakubun ダッシュボードの **Stripe 設定** に貼り付け

**本番モード設定**

1. Stripe アカウントの確認を完了
2. Stripe ダッシュボードで **ライブモード** に切り替え
3. **開発者 → APIキー** に移動
4. **公開キー** をコピー（`pk_live_` で始まります）
5. **秘密キー** をクリックして表示し、コピー（`sk_live_` で始まります）
6. Rakubun ダッシュボードの **Stripe 設定** に貼り付け

**テスト用クレジットカード：**
- 成功: `4242 4242 4242 4242`
- 認証必須: `4000 0025 0000 3155`
- 拒否: `4000 0000 0000 9995`
- 有効期限は将来の日付、CVCは任意の3桁、郵便番号は任意でOK

### ステップ4: クレジットパッケージの設定

1. Rakubun ダッシュボードで **パッケージ設定** にアクセス
2. 記事クレジット用パッケージを設定
   - デフォルト: $5.00 で 10 記事
3. 画像クレジット用パッケージを設定
   - デフォルト: $2.00 で 20 画像
4. 必要に応じて価格をカスタマイズ
5. 設定を保存

### ステップ5: プラグインをテスト

1. **AI コンテンツ → ダッシュボード** で無料クレジットを確認
2. 記事生成をテスト：
   - **AI コンテンツ → 記事を生成** にアクセス
   - プロンプト例: 「運動の健康効果について短い記事を書いてください」
   - **記事を生成する** をクリック
3. 画像生成をテスト：
   - **AI コンテンツ → 画像を生成** にアクセス
   - プロンプト例: 「静かな夕日のビーチ」
   - **画像を生成する** をクリック

## Verification Checklist

- [ ] Plugin activated successfully
- [ ] OpenAI API key configured
- [ ] Stripe keys configured (test mode for development)
- [ ] Dashboard shows 3 article credits and 5 image credits
- [ ] Successfully generated a test article
## 確認チェックリスト

- [ ] プラグインが正常に有効化された
- [ ] プラグインがダッシュボードに登録された
- [ ] OpenAI APIキーがダッシュボードで設定された
- [ ] Stripe キーがダッシュボードで設定された（テストモード）
- [ ] ダッシュボールに無料クレジット（記事3、画像5）が表示される
- [ ] テスト記事の生成に成功
- [ ] テスト画像の生成に成功
- [ ] 生成記事が下書き投稿として作成された（有効化した場合）
- [ ] 生成画像がメディアライブラリに保存された（有効化した場合）

## よくあるセットアップの問題

### 「プラグインがダッシュボードに接続されていません」

- **AI コンテンツ → 設定** でプラグイン登録ボタンをクリック
- ページをリロードして接続を再確認
- ブラウザのキャッシュをクリア

### 「OpenAI APIキーが設定されていません」

- [Rakubun 管理ダッシュボード](https://app.rakubun.com) でAPIキーが設定されていることを確認
- キーが有効でアクティブか確認
- 余分なスペースや特殊文字がないか確認

### 「Stripe秘密キーが設定されていません」

- ダッシュボードで公開キーと秘密キーの両方が設定されているか確認
- テストモードと本番モードのキーが混在していないか確認
- キーをコピーする際に余分なスペースがないか確認

### プラグインの有効化に失敗する

- PHP バージョンが 7.4 以上であることを確認
- WordPress が 5.0 以上であることを確認
- サーバーのエラーログを確認

### データベーステーブルが作成されない

- データベースユーザーが必要なパーミッションを持っているか確認
- プラグインを無効化してから再度有効化
- MySQL バージョンが 5.6 以上であることを確認

## セキュリティ上の推奨事項

### 1. APIキーを安全に保管する

- APIキーを決してバージョン管理に含めない
- サポートチケットでキーを共有しない
- 定期的にキーをローテーション
- ダッシュボードで一元管理

### 2. テストモードから始める

- 常に Stripe テストキーでテストしてから本番へ
- すべてが正常に動作することを確認
- 小額での実際の支払いテストを実施

### 3. 使用状況を監視する

- Rakubun ダッシュボールで定期的に使用状況を確認
- OpenAI の使用ダッシュボードを監視
- 請求額が予期しない増加がないか確認
- Stripe トランザクションを監視

### 4. WordPress セキュリティ

- WordPress、プラグイン、テーマを常に最新に保つ
- 強い管理者パスワードを使用
- 管理者アクセスを信頼できるユーザーのみに制限
- 定期的なバックアップを実施

## 本番環境への移行

プラグインが完全に動作することを確認したら、本番環境へ移行できます：

### 1. Stripe キーの置換

- Stripe アカウントの確認を完了
- Stripe ダッシュボールでライブモード設定を有効化
- テストキーをライブキーに置き換え
- ダッシュボードで設定を保存

### 2. クレジット価格の調整

- 市場競争力のある価格を設定
- 初期価格でテストして必要に応じて調整
- 定期的に価格を見直す

### 3. ユーザー告知

- ユーザーにプラグイン機能について情報を提供
- クレジットシステムの説明をサイトに掲載
- サポート情報とドキュメントを用意

## サポート

問題が発生した場合：

1. [README.md](README.md) のトラブルシューティングセクションを確認
2. WordPress と PHP のエラーログを確認
3. OpenAI と Stripe のステータスページで障害確認
4. [GitHub Issues](https://github.com/brocketdesign/rakubun-wordpress-plugin/issues) で報告

## 次のステップ

インストール完了後：

1. ✅ 異なるプロンプトで複数の記事を生成してテスト
2. ✅ 様々な説明で画像生成をテスト
3. ✅ 生成したコンテンツを使用してブログ記事を作成
4. ✅ 実装に応じてクレジット価格を調整
5. ✅ 十分にテストした後、本番 Stripe キーに切り替え
6. ✅ ユーザーにプラグイン機能を周知

プラグインをご利用ありがとうございます！ 🚀

When you're ready to go live:

1. **OpenAI**: No changes needed (same API key works for production)
2. **Stripe**: 
   - Complete Stripe account verification
   - Replace test keys with live keys in Settings
   - Test with a real small payment first
3. **Pricing**: Review and adjust credit package prices if needed
4. **Documentation**: Inform users about the credit system

## Support

If you encounter issues:
1. Check the [README](README.md) for troubleshooting tips
2. Review WordPress and PHP error logs
3. Verify API keys are correct and active
4. Check OpenAI and Stripe status pages for service issues

## Next Steps

After successful installation:
- Customize pricing to match your business model
- Create user documentation for your site
- Set up monitoring for API usage and costs
- Consider adding usage limits if needed

Enjoy generating AI content! 🚀
