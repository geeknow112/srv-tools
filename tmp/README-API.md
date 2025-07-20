# Universal GitHub Shell Manager v2.1 (GitHub API版)

マイグレーションファイル生成とGitHub作業フローの自動化ツール（GitHub API対応版）

## 🆕 v2.1の新機能

- **GitHub API対応**: GitHub CLIが不要、REST APIを直接使用
- **トークン管理**: 環境変数または設定ファイルでのトークン管理
- **認証テスト**: API接続状況の確認機能
- **レート制限対応**: API使用状況の監視
- **エラーハンドリング強化**: より詳細なAPI エラー情報

## 特徴

- **GitHub CLI不要**: REST APIを直接使用、依存関係を削減
- **汎用性**: 任意のプロジェクトで使用可能
- **自動設定**: プロジェクトのGitHub情報を自動検出
- **セッション管理**: ワークフロー実行中の状態保持
- **完全なGitHub連携**: Issue・PR・コメント機能をAPI経由で実現

## 必要な準備

### 1. GitHub Personal Access Token の作成

1. GitHub にログインし、[Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens) にアクセス
2. "Generate new token" をクリック
3. 以下のスコープを選択：
   - `repo` (フルアクセス) - Issue・PR作成に必要
   - `workflow` (オプション) - GitHub Actions連携時に必要
4. トークンをコピーして保存

### 2. トークンの設定

#### 方法1: 環境変数（推奨）
```bash
export GITHUB_TOKEN=your_token_here
```

#### 方法2: 設定ファイル
`.githubsh.json` に追加：
```json
{
  "github": {
    "token": "your_token_here"
  }
}
```

## インストール

### ファイルをダウンロード
```bash
# GitHub API Client
curl -O https://raw.githubusercontent.com/your-repo/srv-tools/main/tmp/GitHubApiClient.php

# メインスクリプト
curl -O https://raw.githubusercontent.com/your-repo/srv-tools/main/tmp/githubsh-api.php

# 実行権限を付与
chmod +x githubsh-api.php
```

## 初期設定

プロジェクトディレクトリで初期化を実行：

```bash
php githubsh-api.php init
```

これにより以下のファイルが作成されます：
- `.githubsh.json` - 設定ファイル
- `gdata.php` - プロジェクト固有のコマンド定義
- `migrations/` - マイグレーションファイル用ディレクトリ
- `migration_count.txt` - マイグレーション番号管理
- `.githubsh_session.json` - セッション管理ファイル（自動生成）

## 使用方法

### GitHub API 接続確認

```bash
php githubsh-api.php check
```

### GitHub Issue作成

```bash
php githubsh-api.php "Fix database migration bug" 0
```

### ワークフロー実行

```bash
# Stage 1: 準備（セッション開始）
php githubsh-api.php project#123 1

# Stage 2: 実装（セッション継続）
php githubsh-api.php project#123 2

# Stage 3: テスト＆PR作成（セッション継続）
php githubsh-api.php project#123 3

# Stage 4: 完了処理（セッション終了）
php githubsh-api.php project#123 4
```

## GitHub API版の利点

### vs GitHub CLI版

| 項目 | GitHub CLI版 | GitHub API版 | 利点 |
|------|-------------|-------------|------|
| 依存関係 | GitHub CLI必要 | PHP + cURL のみ | ✅ 軽量 |
| 認証 | gh auth login | Personal Access Token | ✅ 自動化しやすい |
| エラー情報 | 限定的 | 詳細なAPI レスポンス | ✅ デバッグしやすい |
| レート制限 | 不明 | 監視可能 | ✅ 制限管理 |
| カスタマイズ | 限定的 | 完全制御 | ✅ 柔軟性 |

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
        "use_api": true,
        "token": "your_token_here"
    }
}
```

## GitHub API機能

### 実装されている機能

- ✅ Issue作成
- ✅ Issue コメント追加
- ✅ Issue クローズ
- ✅ Pull Request作成
- ✅ Pull Request状態取得
- ✅ リポジトリ情報取得
- ✅ 認証テスト
- ✅ レート制限監視

### API エンドポイント

```php
// Issue作成
POST /repos/{owner}/{repo}/issues

// Pull Request作成
POST /repos/{owner}/{repo}/pulls

// コメント追加
POST /repos/{owner}/{repo}/issues/{issue_number}/comments

// 認証テスト
GET /user

// レート制限確認
GET /rate_limit
```

## エラーハンドリング

### 一般的なエラーと対処法

#### 認証エラー
```
Error: GitHub API authentication failed: Bad credentials
```
**対処法**: トークンを確認し、正しく設定されているか確認

#### レート制限エラー
```
Error: GitHub API error: API rate limit exceeded
```
**対処法**: しばらく待ってから再実行、またはレート制限を確認

#### リポジトリアクセスエラー
```
Error: GitHub API error: Not Found
```
**対処法**: リポジトリ名とトークンの権限を確認

## トラブルシューティング

### GitHub API接続テスト

```bash
# API接続状況を確認
php githubsh-api.php check
```

### トークン権限の確認

必要な権限：
- `repo` - リポジトリへのフルアクセス
- `repo:status` - コミットステータスへのアクセス
- `public_repo` - パブリックリポジトリのみの場合

### 環境変数の確認

```bash
# トークンが設定されているか確認
echo $GITHUB_TOKEN

# 設定されていない場合
export GITHUB_TOKEN=your_token_here
```

## セキュリティ

### トークン管理のベストプラクティス

1. **環境変数を使用**: 設定ファイルにトークンを直接書かない
2. **最小権限**: 必要最小限のスコープのみ付与
3. **定期的な更新**: トークンを定期的に再生成
4. **ログ除外**: トークンがログに出力されないよう注意

### .gitignore 設定

```gitignore
# GitHub Shell Manager
.githubsh_session.json
githubsh.log
.githubsh.json  # トークンが含まれる場合
```

## パフォーマンス

### API レート制限

- **認証済み**: 5,000 requests/hour
- **未認証**: 60 requests/hour
- **検索API**: 30 requests/minute

### 最適化のヒント

1. **バッチ処理**: 複数の操作をまとめて実行
2. **キャッシュ**: 頻繁にアクセスする情報をキャッシュ
3. **条件付きリクエスト**: ETags を使用した効率的な更新確認

## 依存関係

- **PHP 7.4+**: スクリプト実行に必要
- **cURL**: HTTP リクエストに使用（通常はPHPに含まれる）
- **Git**: プロジェクト情報の自動検出に使用
- **GitHub Personal Access Token**: API認証に必要

## ライセンス

MIT License

## 貢献

プルリクエストやIssueの報告を歓迎します。

## 更新履歴

- v2.1.0: GitHub API対応版リリース
  - GitHub CLI依存を削除
  - REST API直接使用
  - 認証テスト機能追加
  - レート制限監視機能追加
- v2.0.0: 変数一貫性問題修正、セッション管理機能追加
- v1.0.0: 初回リリース

## サポート

### 問題報告
- GitHub Issues: プロジェクトのIssueページ
- API関連: GitHub API ドキュメント参照

### 参考リンク
- [GitHub REST API Documentation](https://docs.github.com/en/rest)
- [Personal Access Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)
# GitHub Actions Test Trigger - Mon Jul 21 05:50:27 JST 2025
