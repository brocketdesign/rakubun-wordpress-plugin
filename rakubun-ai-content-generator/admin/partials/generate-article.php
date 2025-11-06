<?php
/**
 * Generate Article page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-generate-article">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>利用可能な記事生成クレジット: <strong class="credits-count"><?php echo esc_html($credits['article_credits']); ?></strong></p>
        <?php if ($credits['article_credits'] == 0): ?>
            <p class="notice notice-warning">記事生成クレジットが不足しています。<a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>">クレジットを購入</a>してください。</p>
        <?php endif; ?>
    </div>

    <div class="rakubun-form-container">
        <form id="rakubun-generate-article-form">
            <div class="rakubun-form-section">
                <h3>どんな記事を作成したいですか？</h3>
                
                <div class="rakubun-form-row-three-columns">
                    <div class="rakubun-form-column">
                        <label for="article_length"><strong>記事の長さ</strong></label>
                        <select id="article_length" name="content_length" class="rakubun-select-with-description">
                            <option value="short">短い</option>
                            <option value="medium" selected>標準</option>
                            <option value="long">長い</option>
                        </select>
                        <div id="article_length_desc" class="rakubun-option-description">
                            <p><strong style="color: #0073aa;">標準</strong></p>
                            <p>バランスの取れた深さと読みやすさで、読者を引きつけ、適切な情報を提供します。</p>
                        </div>
                    </div>

                    <div class="rakubun-form-column">
                        <label for="article_tone"><strong>トーン</strong></label>
                        <select id="article_tone" name="tone" class="rakubun-select-with-description">
                            <option value="neutral">ニュートラル</option>
                            <option value="formal">フォーマル</option>
                            <option value="trustworthy">信頼性重視</option>
                            <option value="friendly">フレンドリー</option>
                            <option value="witty">ユーモア</option>
                        </select>
                        <div id="article_tone_desc" class="rakubun-option-description">
                            <p><strong style="color: #0073aa;">ニュートラル</strong></p>
                            <p>客観的でバランスの取れた、事実に基づいたトーン。</p>
                        </div>
                    </div>

                    <div class="rakubun-form-column">
                        <label for="article_language"><strong>言語</strong></label>
                        <select id="article_language" name="language" class="regular-text">
                            <option value="ja" selected>日本語（日本）</option>
                            <option value="en">English</option>
                            <option value="zh">中文（繁體）</option>
                            <option value="es">Español</option>
                            <option value="fr">Français</option>
                            <option value="de">Deutsch</option>
                            <option value="ko">한국어</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rakubun-form-section">
                <h3>記事の内容について</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="article_prompt">記事のテーマ *</label>
                        </th>
                        <td>
                            <textarea id="article_prompt" name="prompt" rows="6" class="large-text" placeholder="例: メンタルヘルスにおける瞑想の効果について包括的な記事を書いてください" required></textarea>
                            <p class="description">生成したい記事の内容を説明してください。トピック、スタイル、重要なポイントについて具体的に記述してください。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="article_keywords">フォーカスキーワード（オプション）</label>
                        </th>
                        <td>
                            <input type="text" id="article_keywords" name="focus_keywords" class="regular-text" placeholder="例: ウェブサイト開発、WordPress チュートリアル、...">
                            <p class="description">記事の焦点となるキーワードを入力してください。複数の場合はカンマで区切ってください。AIが自動的にキーワード提案を生成することもできます。</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="rakubun-form-section">
                <h3>記事の設定</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="article_title">記事タイトル（オプション）</label>
                        </th>
                        <td>
                            <input type="text" id="article_title" name="title" class="regular-text" placeholder="空白の場合は自動生成されます">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="article_categories">カテゴリー</label>
                        </th>
                        <td>
                            <select id="article_categories" name="categories" multiple="multiple" class="regular-text" style="height: 150px;">
                                <?php
                                $categories = get_categories(array('hide_empty' => false));
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">記事に割り当てるカテゴリーを選択してください。複数選択可能です（Ctrl/Cmd キーで複数選択）。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="generate_tags">タグを生成</label>
                        </th>
                        <td>
                            <input type="checkbox" id="generate_tags" name="generate_tags" value="1" checked>
                            <label for="generate_tags">AIが記事から最大5個のタグを自動生成する</label>
                            <p class="description">チェックすると、生成された記事から関連するタグが自動的に作成され、投稿に割り当てられます。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="create_post">投稿を作成</label>
                        </th>
                        <td>
                            <input type="checkbox" id="create_post" name="create_post" value="1" checked>
                            <label for="create_post">生成されたコンテンツで下書き投稿を自動作成する</label>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary button-large" <?php echo $credits['article_credits'] == 0 ? 'disabled' : ''; ?>>
                    📝 記事を生成する（1クレジット消費）
                </button>
            </p>
        </form>

        <div id="rakubun-article-result" class="rakubun-result rakubun-success-alert" style="display:none;">
            <div class="rakubun-success-header">
                <span class="rakubun-success-icon">✓</span>
                <div class="rakubun-success-title">
                    <h3>記事が正常に生成されました！</h3>
                    <p class="rakubun-success-subtitle">下のコンテンツをコピーするか、下書き投稿として保存してください。</p>
                </div>
            </div>
            <div id="rakubun-article-title" class="generated-title" style="margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-left: 4px solid #0073aa; border-radius: 4px;"></div>
            <div id="rakubun-article-content" class="generated-content"></div>
            <div class="result-actions">
                <button type="button" class="button button-primary" onclick="rakubunCopyContent('rakubun-article-content')">📋 クリップボードにコピー</button>
                <button type="button" class="button" onclick="location.reload()">↻ 新しい記事を作成</button>
            </div>
        </div>

        <div id="rakubun-article-loading" class="rakubun-loading" style="display:none;">
            <div class="spinner is-active"></div>
            <p>記事を生成しています... しばらくお待ちください。</p>
        </div>

        <div id="rakubun-article-error" class="rakubun-error-alert" style="display:none;">
            <div class="rakubun-error-header">
                <span class="rakubun-error-icon">✕</span>
                <div class="rakubun-error-content">
                    <h3>エラーが発生しました</h3>
                    <p id="rakubun-error-message"></p>
                </div>
            </div>
        </div>
    </div>
</div>
