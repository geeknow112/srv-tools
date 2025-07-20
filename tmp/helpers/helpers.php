<?php

/**
 * 引数の検証
 */
function validateArguments($argv) {
    if (count($argv) < 3) {
        echo "Usage:" . PHP_EOL;
        echo "  php githubsh.php <issue_title> 0          # Create GitHub issue" . PHP_EOL;
        echo "  php githubsh.php <todo_no> <stage_number> # Execute workflow stages" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "Examples:" . PHP_EOL;
        echo "  php githubsh.php \"Fix database migration bug\" 0" . PHP_EOL;
        echo "  php githubsh.php srv-tools#101 1" . PHP_EOL;
        echo "" . PHP_EOL;
        echo "Stages:" . PHP_EOL;
        echo "  0: Create GitHub issue" . PHP_EOL;
        echo "  1: Create branch and migration file" . PHP_EOL;
        echo "  2: Modify migration file" . PHP_EOL;
        echo "  3: Commit and push changes" . PHP_EOL;
        echo "  4: Cleanup branches" . PHP_EOL;
        exit(1);
    }
    
    $stage = intval($argv[2]);
    
    // Stage 0の場合は、issue titleの検証
    if ($stage === 0) {
        if (empty(trim($argv[1]))) {
            echo "Error: Issue title cannot be empty" . PHP_EOL;
            exit(1);
        }
        return; // Stage 0は他の検証をスキップ
    }
    
    // Stage 1-4の場合は、todo_no形式の検証
    if (!preg_match('/^[a-zA-Z0-9#-]+$/', $argv[1])) {
        echo "Error: Invalid todo_no format" . PHP_EOL;
        exit(1);
    }
    
    if (!in_array($stage, [1, 2, 3, 4])) {
        echo "Error: stage_number must be 0, 1, 2, 3, or 4" . PHP_EOL;
        exit(1);
    }
}

/**
 * 必要ファイルの存在チェック
 */
function checkRequiredFiles($config) {
    $gdata = $config['gdata_path'];
    $count_file = $config['count_file'];
    
    if (!file_exists($gdata)) {
        echo "Error: gdata.php not found at: $gdata" . PHP_EOL;
        exit(1);
    }
    
    if (!file_exists($count_file)) {
        echo "Warning: count.txt not found. Creating with initial value 1" . PHP_EOL;
        $migrations_dir = dirname($count_file);
        if (!is_dir($migrations_dir)) {
            mkdir($migrations_dir, 0755, true);
        }
        file_put_contents($count_file, "1");
    }
    
    return [$gdata, $count_file];
}

/**
 * ログ出力
 */
function writeLog($message, $level = 'INFO', $log_file = null) {
    if ($log_file === null) {
        $log_file = __DIR__ . '/githubsh.log';
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    echo $log_entry;
}
