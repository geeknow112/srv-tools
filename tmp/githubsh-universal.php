#!/usr/bin/env php
<?php
/**
 * Universal GitHub Shell Manager
 * マイグレーションファイル生成とGitHub作業フローの自動化ツール（汎用版）
 * 
 * Usage: 
 *   php githubsh-universal.php init                    # Initialize configuration
 *   php githubsh-universal.php <issue_title> 0         # Create GitHub issue
 *   php githubsh-universal.php <todo_no> <stage_number> # Execute workflow stages
 * 
 * Examples:
 *   php githubsh-universal.php init
 *   php githubsh-universal.php "Fix database migration bug" 0
 *   php githubsh-universal.php srv-tools#101 1
 */

class UniversalGitHubShellManager {
    private $config;
    private $project_root;
    private $config_file;
    
    public function __construct() {
        $this->project_root = $this->findProjectRoot();
        $this->config_file = $this->project_root . '/.githubsh.json';
        $this->loadConfig();
    }
    
    /**
     * プロジェクトルートを検索（.gitディレクトリを基準）
     */
    private function findProjectRoot() {
        $current_dir = getcwd();
        
        while ($current_dir !== '/') {
            if (is_dir($current_dir . '/.git')) {
                return $current_dir;
            }
            $current_dir = dirname($current_dir);
        }
        
        // .gitが見つからない場合は現在のディレクトリを使用
        return getcwd();
    }
    
    /**
     * 設定ファイルを読み込み
     */
    private function loadConfig() {
        if (!file_exists($this->config_file)) {
            $this->config = $this->getDefaultConfig();
            return;
        }
        
        $config_content = file_get_contents($this->config_file);
        $this->config = json_decode($config_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in config file: " . json_last_error_msg());
        }
        
        // デフォルト値とマージ
        $this->config = array_merge($this->getDefaultConfig(), $this->config);
    }
    
    /**
     * デフォルト設定を取得
     */
    private function getDefaultConfig() {
        return [
            'project_name' => basename($this->project_root),
            'migration_path' => './migrations',
            'log_file' => './githubsh.log',
            'count_file' => './migration_count.txt',
            'gdata_file' => './gdata.php',
            'issue_template' => [
                'body' => "## 概要\n自動生成されたissueです。\n\n## 作業内容\n- [ ] 調査\n- [ ] 実装\n- [ ] テスト\n- [ ] レビュー\n\n## 備考\n作成日時: {{date}}"
            ],
            'stages' => [
                1 => ['name' => 'Preparation', 'commands' => []],
                2 => ['name' => 'Implementation', 'commands' => []],
                3 => ['name' => 'Testing', 'commands' => []],
                4 => ['name' => 'Finalization', 'commands' => []]
            ],
            'github' => [
                'owner' => '',
                'repo' => '',
                'use_gh_cli' => true
            ]
        ];
    }
    
    /**
     * 設定ファイルを保存
     */
    private function saveConfig() {
        $json = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->config_file, $json);
    }
    
    /**
     * 初期化コマンド
     */
    public function initialize() {
        echo "Initializing GitHub Shell Manager for project: " . $this->config['project_name'] . PHP_EOL;
        
        // GitHubリポジトリ情報を取得
        $this->detectGitHubInfo();
        
        // 設定ファイルを作成
        $this->saveConfig();
        
        // 必要なディレクトリを作成
        $this->createDirectories();
        
        // テンプレートファイルを作成
        $this->createTemplateFiles();
        
        echo "Initialization completed!" . PHP_EOL;
        echo "Configuration file created: " . $this->config_file . PHP_EOL;
        echo "Please edit the configuration file to customize your workflow." . PHP_EOL;
    }
    
    /**
     * GitHub情報を自動検出
     */
    private function detectGitHubInfo() {
        $remote_url = trim(shell_exec('git remote get-url origin 2>/dev/null') ?? '');
        
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\/\.]+)/', $remote_url, $matches)) {
            $this->config['github']['owner'] = $matches[1];
            $this->config['github']['repo'] = $matches[2];
            echo "Detected GitHub repository: {$matches[1]}/{$matches[2]}" . PHP_EOL;
        } else {
            echo "Could not detect GitHub repository. Please configure manually." . PHP_EOL;
        }
    }
    
    /**
     * 必要なディレクトリを作成
     */
    private function createDirectories() {
        $dirs = [
            $this->config['migration_path'],
            dirname($this->config['log_file'])
        ];
        
        foreach ($dirs as $dir) {
            if ($dir && !is_dir($dir)) {
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
$migrate = "migration" . date("Ymd") . sprintf("%03d", file_get_contents(__DIR__ . "/migration_count.txt") + 1);
$mfile = $migrate . ".go"; // or .php, .sql, etc. depending on your project
';
    }
    
    /**
     * GitHub Issue作成
     */
    public function createGitHubIssue($title) {
        $body = str_replace('{{date}}', date('Y-m-d H:i:s'), $this->config['issue_template']['body']);
        
        if ($this->config['github']['use_gh_cli']) {
            return $this->createIssueWithGhCli($title, $body);
        } else {
            throw new Exception("GitHub API integration not implemented yet. Please use GitHub CLI.");
        }
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
     * ワークフロー実行
     */
    public function executeWorkflow($todo_no, $stage) {
        $this->writeLog("Starting workflow execution for $todo_no, stage $stage");
        
        // gdata.phpを読み込み
        if (file_exists($this->config['gdata_file'])) {
            require_once $this->config['gdata_file'];
        } else {
            throw new Exception("Configuration file not found: " . $this->config['gdata_file']);
        }
        
        // ステージに応じたコマンドを実行
        $function_name = "get_cmd_$stage";
        if (!function_exists($function_name)) {
            throw new Exception("Stage $stage is not defined in configuration");
        }
        
        // 変数を準備
        $migrate = isset($migrate) ? $migrate : "migration" . date("Ymd") . "001";
        $mfile = isset($mfile) ? $mfile : $migrate . ".go";
        
        $commands = $function_name($migrate, $mfile, $todo_no);
        
        foreach ($commands as $cmd) {
            if (empty(trim($cmd))) continue;
            
            $this->writeLog("Executing: $cmd");
            echo "Executing: $cmd" . PHP_EOL;
            
            $output = [];
            $return_code = 0;
            exec($cmd . ' 2>&1', $output, $return_code);
            
            $output_str = implode("\n", $output);
            $this->writeLog("Output: $output_str");
            
            if ($return_code !== 0) {
                $error_msg = "Command failed with return code $return_code: $cmd\nOutput: $output_str";
                $this->writeLog($error_msg, 'ERROR');
                throw new Exception($error_msg);
            }
            
            echo $output_str . PHP_EOL;
        }
        
        $this->writeLog("Workflow stage $stage completed successfully");
    }
    
    /**
     * ログ出力
     */
    private function writeLog($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->config['log_file'], $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 使用方法を表示
     */
    public function showUsage() {
        echo "Universal GitHub Shell Manager" . PHP_EOL;
        echo "Usage:" . PHP_EOL;
        echo "  php githubsh-universal.php init                    # Initialize configuration" . PHP_EOL;
        echo "  php githubsh-universal.php <issue_title> 0         # Create GitHub issue" . PHP_EOL;
        echo "  php githubsh-universal.php <todo_no> <stage_number> # Execute workflow stages" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "Examples:" . PHP_EOL;
        echo "  php githubsh-universal.php init" . PHP_EOL;
        echo "  php githubsh-universal.php \"Fix database migration bug\" 0" . PHP_EOL;
        echo "  php githubsh-universal.php srv-tools#101 1" . PHP_EOL;
    }
}

// メイン処理
try {
    if ($argc < 2) {
        $manager = new UniversalGitHubShellManager();
        $manager->showUsage();
        exit(1);
    }
    
    $manager = new UniversalGitHubShellManager();
    
    $command = $argv[1];
    
    if ($command === 'init') {
        $manager->initialize();
    } elseif ($argc >= 3) {
        $todo_no = $argv[1];
        $stage = intval($argv[2]);
        
        if ($stage === 0) {
            // Stage 0: GitHub Issue作成
            $issue_title = $todo_no;
            echo "Creating GitHub issue: $issue_title" . PHP_EOL;
            
            $issue_url = $manager->createGitHubIssue($issue_title);
            echo "GitHub issue created: $issue_url" . PHP_EOL;
        } else {
            // Stage 1-4: ワークフロー実行
            $manager->executeWorkflow($todo_no, $stage);
        }
        
        echo "Process completed successfully!" . PHP_EOL;
    } else {
        $manager->showUsage();
        exit(1);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
