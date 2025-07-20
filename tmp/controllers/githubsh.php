<?php
/**
 * GitHub Shell Manager
 * マイグレーションファイル生成とGitHub作業フローの自動化ツール
 * 
 * Usage: 
 *   php githubsh.php <issue_title> 0          # Create GitHub issue
 *   php githubsh.php <todo_no> <stage_number> # Execute workflow stages
 * 
 * Examples:
 *   php githubsh.php "Fix database migration bug" 0
 *   php githubsh.php srv-tools#101 1
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/GitHubShellManager.php';

try {
    // 引数検証
    validateArguments($argv);
    
    // 設定読み込み
    $config = require __DIR__ . '/config.php';
    
    // 必要ファイルチェック
    [$gdata, $count_file] = checkRequiredFiles($config);
    
    // 外部設定ファイル読み込み
    require $gdata;
    
    // 引数取得
    $todo_no = $argv[1];
    $stage = intval($argv[2]);
    
    writeLog("Starting GitHub Shell Manager");
    writeLog("Todo No: $todo_no, Stage: $stage");
    
    // Stage 0: GitHub Issue作成
    if ($stage === 0) {
        $issue_title = $todo_no; // Stage 0の場合、第1引数はissueタイトル
        writeLog("Creating GitHub issue: $issue_title");
        
        $issue_url = createGitHubIssue($issue_title);
        writeLog("GitHub issue created: $issue_url");
        echo "GitHub issue created: $issue_url" . PHP_EOL;
        
        writeLog("Process completed successfully!");
        echo "Process completed successfully!" . PHP_EOL;
        exit(0);
    }
    
    // Stage 1-4: 通常のワークフロー実行
    $manager = new GitHubShellManager($config, $todo_no, $stage);
    $manager->execute();
    
    writeLog("Process completed successfully!");
    echo "Process completed successfully!" . PHP_EOL;
    
} catch (Exception $e) {
    $error_msg = "Fatal Error: " . $e->getMessage();
    writeLog($error_msg, 'ERROR');
    echo $error_msg . PHP_EOL;
    exit(1);
} catch (Error $e) {
    $error_msg = "Fatal Error: " . $e->getMessage();
    writeLog($error_msg, 'ERROR');
    echo $error_msg . PHP_EOL;
    exit(1);
}

/**
 * GitHub Issue作成関数
 */
function createGitHubIssue($title) {
    $body = "## 概要\n" . 
            "自動生成されたissueです。\n\n" .
            "## 作業内容\n" .
            "- [ ] 調査\n" .
            "- [ ] 実装\n" .
            "- [ ] テスト\n" .
            "- [ ] レビュー\n\n" .
            "## 備考\n" .
            "作成日時: " . date('Y-m-d H:i:s');
    
    $cmd = sprintf('gh issue create --title "%s" --body "%s"', 
                   addslashes($title), 
                   addslashes($body));
    
    $output = [];
    $return_code = 0;
    exec($cmd . ' 2>&1', $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception("Failed to create GitHub issue: " . implode("\n", $output));
    }
    
    // GitHub CLIはissue URLを返す
    return trim(implode("\n", $output));
}

// 以下は元のコードから必要な関数を保持（gdata.phpで定義されていない場合のフォールバック）
if (!function_exists('get_cmd_1')) {
    function get_cmd_1($migrate, $mfile, $todo_no) {
        return [
            "echo 'Stage 1: $migrate for $todo_no'",
            // 元の実装に応じてコマンドを追加
        ];
    }
}

if (!function_exists('get_cmd_2')) {
    function get_cmd_2($migrate, $mfile, $todo_no) {
        return [
            "echo 'Stage 2: $migrate for $todo_no'",
            // 元の実装に応じてコマンドを追加
        ];
    }
}

if (!function_exists('get_cmd_3')) {
    function get_cmd_3($migrate, $mfile, $todo_no) {
        return [
            "echo 'Stage 3: $migrate for $todo_no'",
            // 元の実装に応じてコマンドを追加
        ];
    }
}

if (!function_exists('get_cmd_4')) {
    function get_cmd_4($migrate, $mfile, $todo_no) {
        return [
            "echo 'Stage 4: $migrate for $todo_no'",
            // 元の実装に応じてコマンドを追加
        ];
    }
}

