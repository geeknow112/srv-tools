#!/usr/bin/env php
<?php
/**
 * Universal GitHub Shell Manager (API版) テストスクリプト
 * 
 * Version: 1.0
 * Author: Amazon Q
 * License: MIT
 */

require_once __DIR__ . '/GitHubApiClient.php';

class GitHubApiTest {
    private $test_results = [];
    private $github_client;
    private $test_repo_owner;
    private $test_repo_name;
    
    public function __construct() {
        $this->test_repo_owner = 'geeknow112'; // テスト用リポジトリ
        $this->test_repo_name = 'srv-tools';
        
        echo "🧪 Universal GitHub Shell Manager (API版) テスト開始\n";
        echo "テスト対象: {$this->test_repo_owner}/{$this->test_repo_name}\n\n";
    }
    
    /**
     * テスト実行
     */
    public function runTests() {
        $this->testGitHubApiClientInitialization();
        $this->testAuthentication();
        $this->testRepositoryAccess();
        $this->testRateLimit();
        $this->testIssueOperations();
        $this->testPullRequestOperations();
        
        $this->printResults();
    }
    
    /**
     * GitHub API クライアント初期化テスト
     */
    private function testGitHubApiClientInitialization() {
        echo "📋 Test 1: GitHub API Client 初期化\n";
        
        try {
            $this->github_client = new GitHubApiClient(
                $this->test_repo_owner,
                $this->test_repo_name
            );
            
            $this->addResult('GitHub API Client 初期化', true, 'クライアント初期化成功');
        } catch (Exception $e) {
            $this->addResult('GitHub API Client 初期化', false, $e->getMessage());
            echo "❌ GitHub API Client の初期化に失敗しました。GITHUB_TOKEN環境変数を設定してください。\n\n";
            return;
        }
        
        echo "✅ GitHub API Client 初期化成功\n\n";
    }
    
    /**
     * 認証テスト
     */
    private function testAuthentication() {
        echo "📋 Test 2: GitHub API 認証\n";
        
        if (!$this->github_client) {
            $this->addResult('GitHub API 認証', false, 'クライアント未初期化');
            return;
        }
        
        try {
            $auth_result = $this->github_client->testAuthentication();
            
            if ($auth_result['success']) {
                $this->addResult('GitHub API 認証', true, "ユーザー: {$auth_result['user']}");
                echo "✅ 認証成功: {$auth_result['user']}\n";
            } else {
                $this->addResult('GitHub API 認証', false, $auth_result['error']);
                echo "❌ 認証失敗: {$auth_result['error']}\n";
            }
        } catch (Exception $e) {
            $this->addResult('GitHub API 認証', false, $e->getMessage());
            echo "❌ 認証エラー: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * リポジトリアクセステスト
     */
    private function testRepositoryAccess() {
        echo "📋 Test 3: リポジトリアクセス\n";
        
        if (!$this->github_client) {
            $this->addResult('リポジトリアクセス', false, 'クライアント未初期化');
            return;
        }
        
        try {
            $repo_info = $this->github_client->getRepository();
            
            $this->addResult('リポジトリアクセス', true, "リポジトリ: {$repo_info['full_name']}");
            echo "✅ リポジトリアクセス成功\n";
            echo "   - 名前: {$repo_info['name']}\n";
            echo "   - フルネーム: {$repo_info['full_name']}\n";
            echo "   - URL: {$repo_info['url']}\n";
            echo "   - デフォルトブランチ: {$repo_info['default_branch']}\n";
        } catch (Exception $e) {
            $this->addResult('リポジトリアクセス', false, $e->getMessage());
            echo "❌ リポジトリアクセスエラー: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * レート制限テスト
     */
    private function testRateLimit() {
        echo "📋 Test 4: レート制限確認\n";
        
        if (!$this->github_client) {
            $this->addResult('レート制限確認', false, 'クライアント未初期化');
            return;
        }
        
        try {
            $rate_limit = $this->github_client->getRateLimit();
            
            $this->addResult('レート制限確認', true, "残り: {$rate_limit['remaining']}/{$rate_limit['limit']}");
            echo "✅ レート制限確認成功\n";
            echo "   - 制限: {$rate_limit['limit']} requests/hour\n";
            echo "   - 残り: {$rate_limit['remaining']} requests\n";
            echo "   - リセット: {$rate_limit['reset']}\n";
            
            if ($rate_limit['remaining'] < 100) {
                echo "⚠️  警告: API制限の残りが少なくなっています\n";
            }
        } catch (Exception $e) {
            $this->addResult('レート制限確認', false, $e->getMessage());
            echo "❌ レート制限確認エラー: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
    
    /**
     * Issue操作テスト（読み取り専用）
     */
    private function testIssueOperations() {
        echo "📋 Test 5: Issue操作（読み取りテスト）\n";
        
        if (!$this->github_client) {
            $this->addResult('Issue操作', false, 'クライアント未初期化');
            return;
        }
        
        try {
            // 既存のIssueを取得してテスト（Issue #1があると仮定）
            $issue_info = $this->github_client->getIssue(1);
            
            $this->addResult('Issue取得', true, "Issue #1: {$issue_info['title']}");
            echo "✅ Issue取得成功\n";
            echo "   - 番号: #{$issue_info['number']}\n";
            echo "   - タイトル: {$issue_info['title']}\n";
            echo "   - 状態: {$issue_info['state']}\n";
            echo "   - URL: {$issue_info['url']}\n";
        } catch (Exception $e) {
            $this->addResult('Issue取得', false, $e->getMessage());
            echo "❌ Issue取得エラー: {$e->getMessage()}\n";
            echo "   (Issue #1が存在しない可能性があります)\n";
        }
        
        echo "\n";
    }
    
    /**
     * Pull Request操作テスト（読み取り専用）
     */
    private function testPullRequestOperations() {
        echo "📋 Test 6: Pull Request操作（読み取りテスト）\n";
        
        if (!$this->github_client) {
            $this->addResult('Pull Request操作', false, 'クライアント未初期化');
            return;
        }
        
        try {
            // 最近のPRを取得してテスト（PR #1があると仮定）
            $pr_info = $this->github_client->getPullRequest(1);
            
            $this->addResult('Pull Request取得', true, "PR #1: 状態 {$pr_info['state']}");
            echo "✅ Pull Request取得成功\n";
            echo "   - 番号: #{$pr_info['number']}\n";
            echo "   - 状態: {$pr_info['state']}\n";
            echo "   - マージ済み: " . ($pr_info['merged'] ? 'Yes' : 'No') . "\n";
            echo "   - URL: {$pr_info['url']}\n";
        } catch (Exception $e) {
            $this->addResult('Pull Request取得', false, $e->getMessage());
            echo "❌ Pull Request取得エラー: {$e->getMessage()}\n";
            echo "   (PR #1が存在しない可能性があります)\n";
        }
        
        echo "\n";
    }
    
    /**
     * テスト結果を追加
     */
    private function addResult($test_name, $success, $message) {
        $this->test_results[] = [
            'name' => $test_name,
            'success' => $success,
            'message' => $message
        ];
    }
    
    /**
     * テスト結果を表示
     */
    private function printResults() {
        echo "📊 テスト結果サマリー\n";
        echo str_repeat("=", 50) . "\n";
        
        $total_tests = count($this->test_results);
        $passed_tests = 0;
        
        foreach ($this->test_results as $result) {
            $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-30s %s\n", $result['name'], $status);
            echo sprintf("%-30s %s\n", '', $result['message']);
            echo "\n";
            
            if ($result['success']) {
                $passed_tests++;
            }
        }
        
        echo str_repeat("=", 50) . "\n";
        echo sprintf("総テスト数: %d\n", $total_tests);
        echo sprintf("成功: %d\n", $passed_tests);
        echo sprintf("失敗: %d\n", $total_tests - $passed_tests);
        echo sprintf("成功率: %.1f%%\n", ($passed_tests / $total_tests) * 100);
        
        if ($passed_tests === $total_tests) {
            echo "\n🎉 すべてのテストが成功しました！\n";
            echo "Universal GitHub Shell Manager (API版) は正常に動作します。\n";
        } else {
            echo "\n⚠️  一部のテストが失敗しました。\n";
            echo "設定やトークンの権限を確認してください。\n";
        }
        
        echo "\n📋 次のステップ:\n";
        echo "1. php githubsh-api.php init     # プロジェクト初期化\n";
        echo "2. php githubsh-api.php check    # API接続確認\n";
        echo "3. php githubsh-api.php \"Test\" 0 # Issue作成テスト\n";
    }
}

// テスト実行
try {
    $test = new GitHubApiTest();
    $test->runTests();
} catch (Exception $e) {
    echo "❌ テスト実行エラー: " . $e->getMessage() . "\n";
    exit(1);
}
