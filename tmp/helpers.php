<?php

/**
 * 引数の検証
 */
function validateArguments($argv) {
    if (count($argv) < 3) {
        echo "Usage: php githubsh.php <todo_no> <stage_number>" . PHP_EOL;
        echo "  todo_no: Issue number (e.g., srv-tools#101)" . PHP_EOL;
        echo "  stage_number: 1-4 (execution stage)" . PHP_EOL;
        exit(1);
    }
    
    if (!preg_match('/^[a-zA-Z0-9#-]+$/', $argv[1])) {
        echo "Error: Invalid todo_no format" . PHP_EOL;
        exit(1);
    }
    
    if (!in_array($argv[2], ['1', '2', '3', '4'])) {
        echo "Error: stage_number must be 1, 2, 3, or 4" . PHP_EOL;
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
