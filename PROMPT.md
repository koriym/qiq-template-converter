 Qiq 1.x → 3.x テンプレート変換ツール作成プロンプト

  # 目的
  Qiq 1.xから3.xへのテンプレートファイル自動変換ツールを作成してください。

  # 要件

  ## 対象ファイル
  - 拡張子: `.php`
  - 場所: テンプレートディレクトリ配下のすべてのファイル
  - 文字コード: UTF-8

  ## 変換ルール

  ### 1. 変数アクセスの変更（最重要）
  #### Qiq構文内
  - 変換前: `{{h $this->name }}`
  - 変換後: `{{h $name }}`
  - パターン: `{{` と `}}` の間の `$this->変数名` を `$変数名` に変換
  - 注意: ヘルパーメソッド呼び出し（例: `$this->h()`）は変換しない

  #### PHP構文内（<?php ?> または <?= ?>）
  - 変換前: `<?= $this->name ?>`
  - 変換後: `<?= $name ?>`
  - パターン: PHPタグ内の `$this->変数名` を `$変数名` に変換
  - 例外: `$this->h()`, `$this->a()` などのメソッド呼び出しは残す

  ### 2. メソッド呼び出し内の変数（重要）
  - 変換前: `<?= $this->h($this->name) ?>`
  - 変換後: `<?= $this->h($name) ?>`
  - 変換前: `{{h $this->name }}`
  - 変換後: `{{h $name }}`

  ### 3. 配列アクセス
  - 変換前: `$this->items[0]`
  - 変換後: `$items[0]`
  - 配列アクセスも同様に変換

  ## 変換してはいけないパターン

  1. **ヘルパーメソッド呼び出し**: `$this->h()`, `$this->a()`, `$this->u()`, `$this->c()` など
  2. **その他のメソッド**: `$this->setView()`, `$this->getView()` など
  3. **PHPのコメント内**: コメント内は変換不要

  ## 実装仕様

  ### 推奨アプローチ
  正規表現ベースのPHPスクリプト

  ### 処理フロー
  1. 指定ディレクトリ配下の`.php`ファイルを再帰的に取得
  2. 各ファイルを読み込み
  3. 以下の順序で正規表現置換を実行:
     a. Qiq構文内の `$this->変数` → `$変数`
     b. PHP構文内の `$this->変数` → `$変数`（メソッド呼び出しを除く）
  4. 変換後の内容をファイルに書き込み
  5. 変換サマリーを出力（ファイル数、変換箇所数）

  ### 正規表現パターン例

  ```php
  // Qiq構文内: {{...}} の中の $this->変数 を $変数 に
  $pattern1 = '/(\{\{[^}]*?)\$this->(\w+)([^}]*?\}\})/';
  $replacement1 = '$1$$$2$3';

  // PHP構文内: $this->変数（メソッド呼び出しでない）を $変数 に
  // 注意: $this->h( のような形式は除外
  $pattern2 = '/\$this->(\w+)(?!\s*\()/';
  $replacement2 = '$$$1';

  出力例

  Converting Qiq 1.x templates to 3.x...
  Processed: /path/to/template/User.php (3 replacements)
  Processed: /path/to/template/Error.php (2 replacements)
  Processed: /path/to/template/Index.php (5 replacements)

  Summary:
  - Files processed: 3
  - Total replacements: 10
  - Completed successfully

  オプション機能

  1. ドライランモード: 実際に変換せず、変更箇所をプレビュー
  2. バックアップ作成: 元ファイルを.bakとして保存
  3. 差分表示: 変更前後の差分を表示
  4. 除外パターン: 特定のファイル/ディレクトリを除外

  テストケース

  以下の変換が正しく動作することを確認:

  // Before
  <?php $this->var ?>
  {{h $this->name }}
  <?= $this->h($this->email) ?>
  $this->items[0]

  // After  
  <?php $var ?>
  {{h $name }}
  <?= $this->h($email) ?>
  $items[0]

  // Should NOT change
  $this->h($name)
  $this->getView()
  // Comment: $this->var

  成果物

  - convert-qiq-templates.php: 変換スクリプト
  - README.md: 使用方法とオプション説明
  - tests/: テストケース

  使用例

  php convert-qiq-templates.php /path/to/templates
  php convert-qiq-templates.php /path/to/templates --dry-run
  php convert-qiq-templates.php /path/to/templates --backup

  ---

  このプロンプトを使用すれば、Qiqテンプレート変換ツールを作成できます。Rectorよりも専用スクリプトの方が確実で保守しやすいです。


