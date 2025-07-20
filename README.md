# Universal GitHub Shell Manager v2.0

マイグレーションファイル生成とGitHub作業フローの自動化ツール（汎用版）

## 🆕 v2.0の新機能

- **変数一貫性保証**: セッション管理による全ステージでの変数統一
- **自動セッション管理**: ワークフロー実行中の状態保持
- **エラー回復機能**: 中断からの安全な再開
- **改善されたログ機能**: より詳細な実行履歴

## 特徴

- **汎用性**: 任意のプロジェクトで使用可能
- **自動設定**: プロジェクトのGitHub情報を自動検出
- **カスタマイズ可能**: プロジェクト固有のワークフローを設定
- **ステージ管理**: 4段階のワークフローステージ
- **GitHub連携**: GitHub CLI を使用したIssue・PR作成
- **セッション管理**: ワークフロー実行中の状態保持

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
- `.githubsh_session.json` - セッション管理ファイル（自動生成）

## 使用方法

### GitHub Issue作成

```bash
php githubsh.php "Fix database migration bug" 0
```

### ワークフロー実行

```bash
# Stage 1: 準備（セッション開始）
php githubsh.php project#123 1

# Stage 2: 実装（セッション継続）
php githubsh.php project#123 2

# Stage 3: テスト（セッション継続）
php githubsh.php project#123 3

# Stage 4: 完了処理（セッション終了）
php githubsh.php project#123 4
```

## v2.0の改善点

### 🔧 変数一貫性問題の解決

**v1.0の問題**:
- 各ステージでgdata.phpが再読み込みされ、変数が再計算される
- Stage 1で012を作成してもStage 2では013を参照してしまう

**v2.0の解決策**:
- セッションファイル（`.githubsh_session.json`）による状態管理
- Stage 1で決定された変数を全ステージで共有
- 中断からの安全な再開機能

### 📊 セッション管理

```json
{
    "todo_no": "srv-tools#304",
    "migrate": "migration20250720013",
    "mfile": "migration20250720013.go",
    "started_at": "2025-07-20 08:00:38",
    "current_stage": 3
}
```

### 🛡️ エラーハンドリング

- セッション不整合の検出
- 適切なエラーメッセージ
- 安全な再開機能

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

## コマンド定義（gdata.php）v2.0

各ステージで実行するコマンドを定義：

```php
<?php
/**
 * Stage 1: 準備コマンド
 * Note: $migrate, $mfile はセッションから渡される
 */
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "echo '🚀 Starting Stage 1: Preparation for $todo_no'",
        "touch migrations/$mfile",
        "echo 'Content' > migrations/$mfile",
    ];
}

/**
 * Stage 2: 実装コマンド
 * Note: 同じ $migrate, $mfile を使用
 */
function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "echo '🔧 Starting Stage 2: Implementation for $todo_no'",
        "git add migrations/$mfile",
    ];
}

// Stage 3, 4も同様...
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
```

### Node.js プロジェクト

```php
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "npm install",
        "npm run lint",
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
```

## 依存関係

- **PHP 7.4+**: スクリプト実行に必要
- **Git**: プロジェクト情報の自動検出に使用
- **GitHub CLI (gh)**: Issue・PR作成機能に必要（オプション）

## トラブルシューティング

### セッションエラー

```bash
# セッションをリセット
rm .githubsh_session.json
# Stage 1から再開
php githubsh.php project#123 1
```

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

## ライセンス

MIT License

## 貢献

プルリクエストやIssueの報告を歓迎します。

## 更新履歴

- v2.0.0: 変数一貫性問題修正、セッション管理機能追加
- v1.0.0: 初回リリース
  - 汎用化対応
  - 自動設定機能
  - インストールスクリプト追加
