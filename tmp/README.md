# Universal GitHub Shell Manager

マイグレーションファイル生成とGitHub作業フローの自動化ツール（汎用版）

## 特徴

- **汎用性**: 任意のプロジェクトで使用可能
- **自動設定**: プロジェクトのGitHub情報を自動検出
- **カスタマイズ可能**: プロジェクト固有のワークフローを設定
- **ステージ管理**: 4段階のワークフローステージ
- **GitHub連携**: GitHub CLI を使用したIssue作成

## インストール

### 方法1: ローカルインストール（推奨）

```bash
# 現在のディレクトリにインストール
./install.sh

# 特定のディレクトリにインストール
./install.sh /path/to/your/project
```

### 方法2: グローバルインストール

```bash
# システム全体で使用可能にする（sudo権限が必要）
./install.sh --global
```

## 初期設定

プロジェクトディレクトリで初期化を実行：

```bash
php githubsh.php init
```

これにより以下のファイルが作成されます：
- `.githubsh.json` - 設定ファイル
- `gdata.php` - プロジェクト固有のコマンド定義
- `migrations/` - マイグレーションファイル用ディレクトリ
- `migration_count.txt` - マイグレーション番号管理

## 使用方法

### GitHub Issue作成

```bash
php githubsh.php "Fix database migration bug" 0
```

### ワークフロー実行

```bash
# Stage 1: 準備
php githubsh.php project#123 1

# Stage 2: 実装
php githubsh.php project#123 2

# Stage 3: テスト
php githubsh.php project#123 3

# Stage 4: 完了処理
php githubsh.php project#123 4
```

## 設定ファイル（.githubsh.json）

```json
{
    "project_name": "your-project",
    "migration_path": "./migrations",
    "log_file": "./githubsh.log",
    "count_file": "./migration_count.txt",
    "gdata_file": "./gdata.php",
    "issue_template": {
        "body": "## 概要\n自動生成されたissueです。\n\n## 作業内容\n- [ ] 調査\n- [ ] 実装\n- [ ] テスト\n- [ ] レビュー\n\n## 備考\n作成日時: {{date}}"
    },
    "stages": {
        "1": {"name": "Preparation", "commands": []},
        "2": {"name": "Implementation", "commands": []},
        "3": {"name": "Testing", "commands": []},
        "4": {"name": "Finalization", "commands": []}
    },
    "github": {
        "owner": "your-username",
        "repo": "your-repository",
        "use_gh_cli": true
    }
}
```

## コマンド定義（gdata.php）

各ステージで実行するコマンドを定義：

```php
<?php
/**
 * Stage 1: 準備コマンド
 */
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "echo 'Starting Stage 1: Preparation for $todo_no'",
        "composer install",
        "npm install",
    ];
}

/**
 * Stage 2: 実装コマンド
 */
function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "echo 'Starting Stage 2: Implementation for $todo_no'",
        "php artisan migrate",
        "npm run build",
    ];
}

/**
 * Stage 3: テストコマンド
 */
function get_cmd_3($migrate, $mfile, $todo_no) {
    return [
        "echo 'Starting Stage 3: Testing for $todo_no'",
        "phpunit",
        "npm test",
    ];
}

/**
 * Stage 4: 完了処理コマンド
 */
function get_cmd_4($migrate, $mfile, $todo_no) {
    return [
        "echo 'Starting Stage 4: Finalization for $todo_no'",
        "git add .",
        "git commit -m 'Completed $todo_no'",
        "git push",
    ];
}
```

## プロジェクト例

### Laravel プロジェクト

```php
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "composer install",
        "php artisan migrate:make $migrate",
    ];
}

function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "php artisan migrate",
        "php artisan db:seed",
    ];
}
```

### Node.js プロジェクト

```php
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "npm install",
        "npm run lint",
    ];
}

function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "npm run build",
        "npm run start:dev",
    ];
}
```

### Go プロジェクト

```php
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "go mod tidy",
        "go fmt ./...",
    ];
}

function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "go build",
        "go run main.go",
    ];
}
```

## 依存関係

- **PHP 7.4+**: スクリプト実行に必要
- **Git**: プロジェクト情報の自動検出に使用
- **GitHub CLI (gh)**: Issue作成機能に必要（オプション）

## トラブルシューティング

### GitHub CLI が見つからない

```bash
# GitHub CLI をインストール
# macOS
brew install gh

# Ubuntu/Debian
sudo apt install gh

# Windows
winget install GitHub.cli
```

### 権限エラー

```bash
# スクリプトに実行権限を付与
chmod +x githubsh.php
```

### 設定ファイルが見つからない

```bash
# 初期化を再実行
php githubsh.php init
```

## ライセンス

MIT License

## 貢献

プルリクエストやIssueの報告を歓迎します。

## 更新履歴

- v1.0.0: 初回リリース
  - 汎用化対応
  - 自動設定機能
  - インストールスクリプト追加
