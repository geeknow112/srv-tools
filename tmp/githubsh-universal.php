#!/usr/bin/env php
<?php
/**
 * Universal GitHub Shell Manager
 * 汎用化されたGitHub作業フローの自動化ツール
 * 
 * Version: 2.0 (変数一貫性問題修正版)
 * Author: Amazon Q
 * License: MIT
 */

class UniversalGitHubShellManager {
    private $config;
    private $project_root;
    private $session_file; // セッション情報保存用
    
    public function __construct() {
        $this->project_root = $this->findProjectRoot();
        $this->session_file = $this->project_root . '/.githubsh_session.json';
        $this->loadConfiguration();
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
        
        // .gitが見つからない場合は現在のディレクトリを使用
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
                'use_gh_cli' => true
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
        echo "Initializing GitHub Shell Manager for project: " . $this->config['project_name'] . PHP_EOL;
        
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
        echo "Please edit the configuration file to customize your workflow." . PHP_EOL;
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
     * 設定を保存
     */
    private function saveConfiguration() {
        $config_file = $this->project_root . '/.githubsh.json';
        file_put_contents($config_file, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * gdata.phpテンプレートを取得
     */
    private function getGdataTemplate() {
        return '<?php
/**
 * Project-specific configuration and command definitions
 * Generated by Universal GitHub Shell Manager
 */

/**
 * Stage 1: Preparation commands
 */
function get_cmd_1($migrate, $mfile, $todo_no) {
    return [
        "echo \'Starting Stage 1: Preparation for $todo_no\'",
        // Add your preparation commands here
        // Example: "composer install",
        // Example: "npm install",
    ];
}

/**
 * Stage 2: Implementation commands
 */
function get_cmd_2($migrate, $mfile, $todo_no) {
    return [
        "echo \'Starting Stage 2: Implementation for $todo_no\'",
        // Add your implementation commands here
        // Example: "php artisan migrate",
        // Example: "npm run build",
    ];
}

/**
 * Stage 3: Testing commands
 */
function get_cmd_3($migrate, $mfile, $todo_no) {
    return [
        "echo \'Starting Stage 3: Testing for $todo_no\'",
        // Add your testing commands here
        // Example: "phpunit",
        // Example: "npm test",
    ];
}

/**
 * Stage 4: Finalization commands
 */
function get_cmd_4($migrate, $mfile, $todo_no) {
    return [
        "echo \'Starting Stage 4: Finalization for $todo_no\'",
        // Add your finalization commands here
        // Example: "git add .",
        // Example: "git commit -m \'Completed $todo_no\'",
    ];
}

// Project-specific variables
$migrate = "migration" . date("Ymd") . sprintf("%03d", intval(file_get_contents(__DIR__ . "/migration_count.txt")) + 1);
$mfile = $migrate . ".go"; // or .php, .sql, etc. depending on your project
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
        
        $body = str_replace('{{date}}', date('Y-m-d H:i:s'), $this->config['issue_template']['body']);
        
        if ($this->config['github']['use_gh_cli']) {
            $issue_url = $this->createIssueWithGhCli($title, $body);
            echo "GitHub issue created: $issue_url" . PHP_EOL;
        } else {
            echo "GitHub CLI is disabled. Please create the issue manually." . PHP_EOL;
        }
        
        echo "Process completed successfully!" . PHP_EOL;
    }
    
    /**
     * GitHub CLI でIssue作成
     */
    private function createIssueWithGhCli($title, $body) {
        $cmd = sprintf('gh issue create --title "%s" --body "%s"', 
                       addslashes($title), 
                       addslashes($body));
        
        $output = [];
        $return_code = 0;
        exec($cmd . ' 2>&1', $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Failed to create GitHub issue: " . implode("\n", $output));
        }
        
        return trim(implode("\n", $output));
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
        
        $this->writeLog("Workflow stage $stage completed successfully");
        
        // Stage 4完了時にセッションをクリア
        if ($stage == 4) {
            $this->clearSession();
        }
        
        echo "Process completed successfully!" . PHP_EOL;
    }
    
    /**
     * 使用方法を表示
     */
    public function showUsage() {
        echo "Universal GitHub Shell Manager\n";
        echo "Usage:\n";
        echo "  php " . basename(__FILE__) . " init                    # Initialize project\n";
        echo "  php " . basename(__FILE__) . " \"title\" 0              # Create GitHub issue\n";
        echo "  php " . basename(__FILE__) . " project#123 1           # Execute stage 1\n";
        echo "  php " . basename(__FILE__) . " project#123 2           # Execute stage 2\n";
        echo "  php " . basename(__FILE__) . " project#123 3           # Execute stage 3\n";
        echo "  php " . basename(__FILE__) . " project#123 4           # Execute stage 4\n";
        echo "\nStages:\n";
        echo "  0: Create GitHub issue\n";
        echo "  1: Preparation\n";
        echo "  2: Implementation\n";
        echo "  3: Testing\n";
        echo "  4: Finalization\n";
    }
}

// メイン処理
if ($argc < 2) {
    $manager = new UniversalGitHubShellManager();
    $manager->showUsage();
    exit(1);
}

try {
    $manager = new UniversalGitHubShellManager();
    
    if ($argv[1] === 'init') {
        $manager->initialize();
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
