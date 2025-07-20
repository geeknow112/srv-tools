#!/usr/bin/env php
<?php
/**
 * Universal GitHub Shell Manager (GitHub API版)
 * 汎用化されたGitHub作業フローの自動化ツール
 * GitHub CLI の代わりに GitHub REST API を使用
 * 
 * Version: 2.1 (GitHub API対応版)
 * Author: Amazon Q
 * License: MIT
 */

// GitHub API Client を読み込み
require_once __DIR__ . '/GitHubApiClient.php';

class UniversalGitHubShellManagerAPI {
    private $config;
    private $project_root;
    private $session_file;
    private $github_client;
    
    public function __construct() {
        $this->project_root = $this->findProjectRoot();
        $this->session_file = $this->project_root . '/.githubsh_session.json';
        $this->loadConfiguration();
        $this->initializeGitHubClient();
    }
    
    /**
     * プロジェクトルートを検索
     */
    private function findProjectRoot() {
        $current_dir = getcwd();
        
        while ($current_dir !== '/') {
            if (file_exists($current_dir . '/.git')) {
                return $current_dir;
            }
            $current_dir = dirname($current_dir);
        }
        
        return getcwd();
    }
    
    /**
     * 設定を読み込み
     */
    private function loadConfiguration() {
        $config_file = $this->project_root . '/.githubsh.json';
        
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), true);
        } else {
            $this->config = $this->getDefaultConfig();
        }
        
        // 相対パスを絶対パスに変換
        foreach (['migration_path', 'log_file', 'count_file', 'gdata_file'] as $key) {
            if (isset($this->config[$key]) && !$this->isAbsolutePath($this->config[$key])) {
                $this->config[$key] = $this->project_root . '/' . ltrim($this->config[$key], './');
            }
        }
    }
    
    /**
     * GitHub API クライアントを初期化
     */
    private function initializeGitHubClient() {
        if (isset($this->config['github']['owner']) && isset($this->config['github']['repo'])) {
            try {
                $token = $this->config['github']['token'] ?? null;
                $this->github_client = new GitHubApiClient(
                    $this->config['github']['owner'],
                    $this->config['github']['repo'],
                    $token
                );
                
                // 認証テスト
                $auth_test = $this->github_client->testAuthentication();
                if (!$auth_test['success']) {
                    echo "Warning: GitHub API authentication failed: " . $auth_test['error'] . PHP_EOL;
                    echo "Please set GITHUB_TOKEN environment variable or configure token in .githubsh.json" . PHP_EOL;
                }
            } catch (Exception $e) {
                echo "Warning: Failed to initialize GitHub API client: " . $e->getMessage() . PHP_EOL;
                $this->github_client = null;
            }
        }
    }
    
    /**
     * 絶対パスかどうかを判定
     */
    private function isAbsolutePath($path) {
        return $path[0] === '/' || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Za-z]:/', $path));
    }
    
    /**
     * デフォルト設定を取得
     */
    private function getDefaultConfig() {
        $project_name = $this->detectProjectName();
        $github_info = $this->detectGitHubInfo();
        
        return [
            'project_name' => $project_name,
            'migration_path' => './migrations',
            'log_file' => './githubsh.log',
            'count_file' => './migration_count.txt',
            'gdata_file' => './gdata.php',
            'issue_template' => [
                'body' => "## 概要\n自動生成されたissueです。\n\n## 作業内容\n- [ ] 調査\n- [ ] 実装\n- [ ] テスト\n- [ ] レビュー\n\n## 備考\n作成日時: {{date}}"
            ],
            'stages' => [
                '1' => ['name' => 'Preparation', 'commands' => []],
                '2' => ['name' => 'Implementation', 'commands' => []],
                '3' => ['name' => 'Testing', 'commands' => []],
                '4' => ['name' => 'Finalization', 'commands' => []]
            ],
            'github' => [
                'owner' => $github_info['owner'] ?? 'your-username',
                'repo' => $github_info['repo'] ?? 'your-repository',
                'use_api' => true,
                'token' => null // 環境変数から取得
            ]
        ];
    }
    
    /**
     * プロジェクト名を検出
     */
    private function detectProjectName() {
        return basename($this->project_root);
    }
    
    /**
     * GitHub情報を検出
     */
    private function detectGitHubInfo() {
        $git_config = $this->project_root . '/.git/config';
        
        if (!file_exists($git_config)) {
            return [];
        }
        
        $config_content = file_get_contents($git_config);
        
        // GitHub URLを検索
        if (preg_match('/url = https:\/\/github\.com\/([^\/]+)\/([^\/\s]+)/', $config_content, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => rtrim($matches[2], '.git')
            ];
        }
        
        return [];
    }
    
    /**
     * セッション情報を保存
     */
    private function saveSession($data) {
        file_put_contents($this->session_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * セッション情報を読み込み
     */
    private function loadSession() {
        if (file_exists($this->session_file)) {
            return json_decode(file_get_contents($this->session_file), true);
        }
        return null;
    }
    
    /**
     * セッション情報をクリア
     */
    private function clearSession() {
        if (file_exists($this->session_file)) {
            unlink($this->session_file);
        }
    }
    
    /**
     * ログを書き込み
     */
    private function writeLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [INFO] $message" . PHP_EOL;
        file_put_contents($this->config['log_file'], $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 初期化処理
     */
    public function initialize() {
        echo "Initializing GitHub Shell Manager (API版) for project: " . $this->config['project_name'] . PHP_EOL;
        
        $github_info = $this->detectGitHubInfo();
        if (!empty($github_info)) {
            echo "Detected GitHub repository: {$github_info['owner']}/{$github_info['repo']}" . PHP_EOL;
            $this->config['github']['owner'] = $github_info['owner'];
            $this->config['github']['repo'] = $github_info['repo'];
        }
        
        $this->createDirectories();
        $this->createTemplateFiles();
        $this->saveConfiguration();
        
        echo "Initialization completed!" . PHP_EOL;
        echo "Configuration file created: " . $this->project_root . "/.githubsh.json" . PHP_EOL;
        echo PHP_EOL;
        echo "GitHub API Setup:" . PHP_EOL;
        echo "1. Set GITHUB_TOKEN environment variable with your personal access token" . PHP_EOL;
        echo "2. Or add 'token' field to .githubsh.json" . PHP_EOL;
        echo "3. Token needs 'repo' scope for full functionality" . PHP_EOL;
    }
    
    /**
     * 必要なディレクトリを作成
     */
    private function createDirectories() {
        $dirs = [
            dirname($this->config['migration_path']),
            dirname($this->config['log_file'])
        ];
        
        foreach ($dirs as $dir) {
            if (!empty($dir) && !is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "Created directory: $dir" . PHP_EOL;
            }
        }
    }
    
    /**
     * テンプレートファイルを作成
     */
    private function createTemplateFiles() {
        // gdata.phpテンプレートを作成
        if (!file_exists($this->config['gdata_file'])) {
            $gdata_template = $this->getGdataTemplate();
            file_put_contents($this->config['gdata_file'], $gdata_template);
            echo "Created template file: " . $this->config['gdata_file'] . PHP_EOL;
        }
        
        // migration_count.txtを初期化
        if (!file_exists($this->config['count_file'])) {
            file_put_contents($this->config['count_file'], "0");
            echo "Created count file: " . $this->config['count_file'] . PHP_EOL;
        }
    }
    
    /**
     * gdata.phpテンプレートを取得
     */
    private function getGdataTemplate() {
        return '<?php
/**
 * Project-specific configuration and command definitions
 * Generated by Universal GitHub Shell Manager (API版)
 */

/**
 * Stage 1: Preparation commands
 */
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "echo \'🚀 Starting Stage 1: Preparation for $todo_no\'",
        "echo \'📁 Creating migration file: $mfile\'",
        "touch migrations/$mfile",
        "echo \'📝 Adding template content\'",
        "echo \'package main\\n\\nimport \"fmt\"\\n\\n// Migration: $migrate\\n// Created: " . date(\'Y-m-d H:i:s\') . "\\n// Issue: $todo_no\\n\\nfunc main() {\\n\\tfmt.Println(\"Migration $migrate executed successfully\")\\n}\' > migrations/$mfile",
        "echo \'✅ Preparation completed for $todo_no\'",
    ];
}

/**
 * Stage 2: Implementation commands
 */
function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "echo \'🔧 Starting Stage 2: Implementation for $todo_no\'",
        "echo \'📊 Processing migration: $migrate\'",
        "ls -la migrations/$mfile",
        "echo \'🔍 Validating file content\'",
        "head -5 migrations/$mfile",
        "echo \'📝 Adding to git\'",
        "git add migrations/$mfile",
        "echo \'✅ Implementation completed for $todo_no\'",
    ];
}

/**
 * Stage 3: Testing and Pull Request
 */
function get_cmd_3($migrate, $mfile, $todo_no) {
    $branch_name = "feature/" . strtolower(str_replace("#", "-", $todo_no)) . "-" . $migrate;
    return [
        "echo \'🧪 Starting Stage 3: Testing and Pull Request for $todo_no\'",
        "echo \'🔍 Running tests for migration: $migrate\'",
        "echo \'📋 File validation test\'",
        "test -f migrations/$mfile && echo \'File exists ✅\' || echo \'File missing ❌\'",
        "echo \'📏 File size check\'",
        "wc -l migrations/$mfile",
        "echo \'🌿 Creating feature branch: $branch_name\'",
        "git checkout -b $branch_name",
        "echo \'💾 Committing changes\'",
        "git commit -m \'feat: Add $migrate for $todo_no\\n\\n- Created migration file: $mfile\\n- Issue: $todo_no\\n- Auto-generated by Universal GitHub Shell Manager (API版)\'",
        "echo \'📤 Pushing branch\'",
        "git push -u origin $branch_name",
        "echo \'✅ Testing completed for $todo_no\'",
    ];
}

/**
 * Stage 4: Finalization commands
 */
function get_cmd_4($migrate, $mfile, $todo_no) {
    return [
        "echo \'🏁 Starting Stage 4: Finalization for $todo_no\'",
        "echo \'📦 Finalizing migration: $migrate\'",
        "echo \'📄 Migration file: migrations/$mfile\'",
        "echo \'📊 File size: \' && wc -c migrations/$mfile",
        "echo \'🔙 Switching back to main branch\'",
        "git checkout main",
        "echo \'📝 Updating migration count\'",
        "echo \'" . (getCurrentMigrationCount() + 1) . "\' > migration_count.txt",
        "echo \'✅ Finalization completed for $todo_no\'",
    ];
}

/**
 * 現在のマイグレーション番号を取得
 */
function getCurrentMigrationCount() {
    $today = date("Ymd");
    $pattern = __DIR__ . "/migrations/migration{$today}*.go";
    $files = glob($pattern);
    
    if (empty($files)) {
        return 0;
    }
    
    $max_number = 0;
    foreach ($files as $file) {
        if (preg_match("/migration" . $today . "(\\d+)\\.go$/", basename($file), $matches)) {
            $number = intval($matches[1]);
            if ($number > $max_number) {
                $max_number = $number;
            }
        }
    }
    
    return $max_number;
}
';
    }
    
    /**
     * GitHub Issue作成
     */
    public function createIssue($title, $stage) {
        if ($stage != 0) {
            throw new Exception("Issue creation is only available for stage 0");
        }
        
        $this->writeLog("Creating GitHub issue: $title");
        
        if (!$this->github_client) {
            throw new Exception("GitHub API client not initialized. Please check your configuration and token.");
        }
        
        $body = str_replace('{{date}}', date('Y-m-d H:i:s'), $this->config['issue_template']['body']);
        
        try {
            $result = $this->github_client->createIssue($title, $body);
            echo "GitHub issue created: " . $result['url'] . PHP_EOL;
            echo "Issue number: #" . $result['number'] . PHP_EOL;
            
            $this->writeLog("Issue created successfully: #" . $result['number']);
        } catch (Exception $e) {
            throw new Exception("Failed to create GitHub issue: " . $e->getMessage());
        }
        
        echo "Process completed successfully!" . PHP_EOL;
    }
    
    /**
     * 現在のマイグレーション番号を取得
     */
    private function getCurrentMigrationCount() {
        $today = date('Ymd');
        $pattern = $this->config['migration_path'] . "/migration{$today}*.go";
        $files = glob($pattern);
        
        if (empty($files)) {
            return 0;
        }
        
        $max_number = 0;
        foreach ($files as $file) {
            if (preg_match('/migration' . $today . '(\d+)\.go$/', basename($file), $matches)) {
                $number = intval($matches[1]);
                if ($number > $max_number) {
                    $max_number = $number;
                }
            }
        }
        
        return $max_number;
    }
    
    /**
     * ワークフロー実行
     */
    public function executeWorkflow($todo_no, $stage) {
        $this->writeLog("Starting workflow execution for $todo_no, stage $stage");
        
        // セッション情報を確認
        $session = $this->loadSession();
        
        if ($stage == 1) {
            // Stage 1: 新しいセッションを開始
            $current_count = $this->getCurrentMigrationCount();
            $migrate = "migration" . date("Ymd") . sprintf("%03d", $current_count + 1);
            $mfile = $migrate . ".go";
            
            $session_data = [
                'todo_no' => $todo_no,
                'migrate' => $migrate,
                'mfile' => $mfile,
                'started_at' => date('Y-m-d H:i:s'),
                'current_stage' => 1
            ];
            
            $this->saveSession($session_data);
        } else {
            // Stage 2-4: 既存セッションを使用
            if (!$session || $session['todo_no'] !== $todo_no) {
                throw new Exception("No active session found for $todo_no. Please start with stage 1.");
            }
            
            $migrate = $session['migrate'];
            $mfile = $session['mfile'];
            $session['current_stage'] = $stage;
            $this->saveSession($session);
        }
        
        // gdata.phpを読み込み
        if (file_exists($this->config['gdata_file'])) {
            require_once $this->config['gdata_file'];
        } else {
            throw new Exception("Configuration file not found: " . $this->config['gdata_file']);
        }
        
        // ステージに対応する関数を呼び出し
        $function_name = "get_cmd_$stage";
        if (!function_exists($function_name)) {
            throw new Exception("Function $function_name not found in " . $this->config['gdata_file']);
        }
        
        $commands = $function_name($migrate, $mfile, $todo_no);
        
        // コマンドを実行
        foreach ($commands as $command) {
            echo "Executing: $command" . PHP_EOL;
            $this->writeLog("Executing: $command");
            
            $output = [];
            $return_code = 0;
            exec($command . ' 2>&1', $output, $return_code);
            
            $output_str = implode("\n", $output);
            if (!empty($output_str)) {
                echo $output_str . PHP_EOL;
                $this->writeLog("Output: $output_str");
            }
            
            if ($return_code !== 0) {
                $error_msg = "Command failed with return code $return_code: $command";
                if (!empty($output_str)) {
                    $error_msg .= "\nOutput: $output_str";
                }
                echo "Error: $error_msg" . PHP_EOL;
                $this->writeLog("Error: $error_msg");
                exit($return_code);
            }
        }
        
        // Stage 3の後にPull Request作成
        if ($stage == 3 && $this->github_client) {
            $this->createPullRequest($migrate, $mfile, $todo_no);
        }
        
        // Stage 4の後にIssue コメント追加
        if ($stage == 4 && $this->github_client) {
            $this->addIssueComment($todo_no, $migrate, $mfile);
        }
        
        $this->writeLog("Workflow stage $stage completed successfully");
        
        // Stage 4完了時にセッションをクリア
        if ($stage == 4) {
            $this->clearSession();
        }
        
        echo "Process completed successfully!" . PHP_EOL;
    }
    
    /**
     * Pull Request を作成
     */
    private function createPullRequest($migrate, $mfile, $todo_no) {
        if (!$this->github_client) {
            echo "GitHub API client not available. Skipping Pull Request creation." . PHP_EOL;
            return;
        }
        
        try {
            $branch_name = "feature/" . strtolower(str_replace('#', '-', $todo_no)) . "-" . $migrate;
            $title = "feat: $migrate for $todo_no";
            $body = "## 概要\n$todo_no の対応として $migrate を追加しました。\n\n## 変更内容\n- マイグレーションファイル: `$mfile`\n- 自動生成日時: " . date('Y-m-d H:i:s') . "\n- Universal GitHub Shell Manager: API版\n\n## 関連Issue\nCloses $todo_no";
            
            $result = $this->github_client->createPullRequest($title, $body, $branch_name);
            echo "🔄 Pull Request created: " . $result['url'] . PHP_EOL;
            echo "PR number: #" . $result['number'] . PHP_EOL;
            
            $this->writeLog("Pull Request created successfully: #" . $result['number']);
        } catch (Exception $e) {
            echo "Warning: Failed to create Pull Request: " . $e->getMessage() . PHP_EOL;
            $this->writeLog("Warning: Failed to create Pull Request: " . $e->getMessage());
        }
    }
    
    /**
     * Issue にコメントを追加
     */
    private function addIssueComment($todo_no, $migrate, $mfile) {
        if (!$this->github_client) {
            echo "GitHub API client not available. Skipping Issue comment." . PHP_EOL;
            return;
        }
        
        // Issue番号を抽出
        $issue_number = str_replace(array($this->config['project_name'] . '#', '#'), '', $todo_no);
        if (!is_numeric($issue_number)) {
            echo "Warning: Could not extract issue number from $todo_no" . PHP_EOL;
            return;
        }
        
        try {
            $comment_body = "✅ **Migration and Pull Request completed successfully**\n\n- Migration file: `migrations/$mfile`\n- Pull Request: Created and ready for review\n- Status: Ready for merge\n- Completed: " . date('Y-m-d H:i:s') . "\n- Universal GitHub Shell Manager: API版\n\n## ワークフロー実行結果\n- ✅ Stage 1: 準備完了\n- ✅ Stage 2: 実装完了\n- ✅ Stage 3: テスト＆PR作成完了\n- ✅ Stage 4: 完了処理実行\n\n**Universal GitHub Shell Manager API版 実行成功** 🎉";
            
            $result = $this->github_client->addIssueComment($issue_number, $comment_body);
            echo "💬 Issue comment added: " . $result['url'] . PHP_EOL;
            
            $this->writeLog("Issue comment added successfully to #$issue_number");
        } catch (Exception $e) {
            echo "Warning: Failed to add Issue comment: " . $e->getMessage() . PHP_EOL;
            $this->writeLog("Warning: Failed to add Issue comment: " . $e->getMessage());
        }
    }
    
    /**
     * GitHub API の状態を確認
     */
    public function checkGitHubApi() {
        if (!$this->github_client) {
            echo "❌ GitHub API client not initialized" . PHP_EOL;
            echo "Please set GITHUB_TOKEN environment variable or configure token in .githubsh.json" . PHP_EOL;
            return;
        }
        
        try {
            // 認証テスト
            $auth_test = $this->github_client->testAuthentication();
            if ($auth_test['success']) {
                echo "✅ GitHub API authentication successful" . PHP_EOL;
                echo "User: " . $auth_test['user'] . PHP_EOL;
            } else {
                echo "❌ GitHub API authentication failed: " . $auth_test['error'] . PHP_EOL;
                return;
            }
            
            // リポジトリ情報取得
            $repo_info = $this->github_client->getRepository();
            echo "✅ Repository access successful" . PHP_EOL;
            echo "Repository: " . $repo_info['full_name'] . PHP_EOL;
            echo "URL: " . $repo_info['url'] . PHP_EOL;
            
            // レート制限確認
            $rate_limit = $this->github_client->getRateLimit();
            echo "✅ Rate limit: " . $rate_limit['remaining'] . "/" . $rate_limit['limit'] . " remaining" . PHP_EOL;
            echo "Reset at: " . $rate_limit['reset'] . PHP_EOL;
            
        } catch (Exception $e) {
            echo "❌ GitHub API error: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    /**
     * 使用方法を表示
     */
    public function showUsage() {
        echo "Universal GitHub Shell Manager (GitHub API版)\n";
        echo "Version: 2.1\n\n";
        echo "Usage:\n";
        echo "  php " . basename(__FILE__) . " init                    # Initialize project\n";
        echo "  php " . basename(__FILE__) . " check                   # Check GitHub API status\n";
        echo "  php " . basename(__FILE__) . " \"title\" 0              # Create GitHub issue\n";
        echo "  php " . basename(__FILE__) . " project#123 1           # Execute stage 1\n";
        echo "  php " . basename(__FILE__) . " project#123 2           # Execute stage 2\n";
        echo "  php " . basename(__FILE__) . " project#123 3           # Execute stage 3\n";
        echo "  php " . basename(__FILE__) . " project#123 4           # Execute stage 4\n";
        echo "\nStages:\n";
        echo "  0: Create GitHub issue\n";
        echo "  1: Preparation\n";
        echo "  2: Implementation\n";
        echo "  3: Testing (+ Pull Request creation)\n";
        echo "  4: Finalization (+ Issue comment)\n";
        echo "\nGitHub API Setup:\n";
        echo "  1. Create a Personal Access Token at https://github.com/settings/tokens\n";
        echo "  2. Grant 'repo' scope for full functionality\n";
        echo "  3. Set GITHUB_TOKEN environment variable:\n";
        echo "     export GITHUB_TOKEN=your_token_here\n";
        echo "  4. Or add token to .githubsh.json:\n";
        echo "     \"github\": { \"token\": \"your_token_here\" }\n";
    }
}

// メイン処理
if ($argc < 2) {
    $manager = new UniversalGitHubShellManagerAPI();
    $manager->showUsage();
    exit(1);
}

try {
    $manager = new UniversalGitHubShellManagerAPI();
    
    if ($argv[1] === 'init') {
        $manager->initialize();
    } elseif ($argv[1] === 'check') {
        $manager->checkGitHubApi();
    } elseif ($argc >= 3) {
        $title_or_todo = $argv[1];
        $stage = intval($argv[2]);
        
        if ($stage === 0) {
            $manager->createIssue($title_or_todo, $stage);
        } else {
            $manager->executeWorkflow($title_or_todo, $stage);
        }
    } else {
        $manager->showUsage();
        exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
