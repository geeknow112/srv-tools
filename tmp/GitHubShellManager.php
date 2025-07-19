<?php

class GitHubShellManager {
    private $config;
    private $todo_no;
    private $stage;
    private $execution_log = [];
    private $start_time;
    
    public function __construct($config, $todo_no, $stage) {
        $this->config = $config;
        $this->todo_no = $todo_no;
        $this->stage = $stage;
        $this->start_time = microtime(true);
        date_default_timezone_set($this->config['timezone']);
    }
    
    public function generateMigrationFile() {
        $dt = date('Ymd');
        $next_no = $this->getNextNo();
        $migrate = sprintf($this->config['migration_format'], $dt, $next_no);
        $mfile = $migrate . $this->config['file_extension'];
        
        return [$migrate, $mfile, $next_no];
    }
    
    public function execute() {
        try {
            $this->logExecution("Starting execution", "Stage {$this->stage} for {$this->todo_no}");
            
            [$migrate, $mfile, $next_no] = $this->generateMigrationFile();
            
            $commands = $this->getCommands($migrate, $mfile);
            $this->executeCommands($commands[$this->stage]);
            
            if ($this->stage == 4) {
                $this->setNextNo($next_no);
            }
            
            $this->logExecution("Completed successfully", "Stage {$this->stage} execution completed");
            $this->writeLog("Successfully executed stage {$this->stage} for {$this->todo_no}");
            
            // 実行完了後にレポートを生成
            $this->generateExecutionReport($migrate, $mfile, $next_no);
            
        } catch (Exception $e) {
            $this->logExecution("Error occurred", $e->getMessage());
            $this->writeLog("Error: " . $e->getMessage(), 'ERROR');
            
            // エラー時もレポートを生成
            $this->generateExecutionReport(null, null, null, $e);
            throw $e;
        }
    }
    
    private function logExecution($event, $details) {
        $this->execution_log[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'details' => $details
        ];
    }
    
    private function generateExecutionReport($migrate = null, $mfile = null, $next_no = null, $error = null) {
        $end_time = microtime(true);
        $execution_time = round($end_time - $this->start_time, 3);
        
        $report = [
            'execution_info' => [
                'todo_no' => $this->todo_no,
                'stage' => $this->stage,
                'start_time' => date('Y-m-d H:i:s', $this->start_time),
                'end_time' => date('Y-m-d H:i:s', $end_time),
                'execution_time_seconds' => $execution_time,
                'status' => $error ? 'FAILED' : 'SUCCESS'
            ],
            'generated_files' => [
                'migration_name' => $migrate,
                'migration_file' => $mfile,
                'counter_value' => $next_no
            ],
            'execution_log' => $this->execution_log,
            'error' => $error ? $error->getMessage() : null
        ];
        
        // JSONレポートファイル作成
        $report_file = $this->config['log_dir'] . 'execution_report_' . date('Ymd_His') . '.json';
        file_put_contents($report_file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // コンソール出力
        $this->outputReportSummary($report);
        
        // GitHub Issue作成（Stage 4完了時またはエラー時）
        if ($this->stage == 4 || $error) {
            $this->createGitHubReport($report, $report_file);
        }
        
        $this->writeLog("Execution report generated: $report_file");
    }
    
    private function outputReportSummary($report) {
        $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
        
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 EXECUTION REPORT {$status_icon}\n";
        echo str_repeat('=', 60) . "\n";
        echo "Todo: {$report['execution_info']['todo_no']}\n";
        echo "Stage: {$report['execution_info']['stage']} ({$this->getStageName($report['execution_info']['stage'])})\n";
        echo "Status: {$report['execution_info']['status']}\n";
        echo "Execution Time: {$report['execution_info']['execution_time_seconds']}s\n";
        
        if ($report['generated_files']['migration_name']) {
            echo "Migration: {$report['generated_files']['migration_name']}\n";
            echo "Counter: {$report['generated_files']['counter_value']}\n";
        }
        
        if ($report['error']) {
            echo "Error: {$report['error']}\n";
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
    
    private function createGitHubReport($report, $report_file) {
        try {
            $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
            $title = sprintf("Execution Report: %s Stage %d %s", 
                           $report['execution_info']['todo_no'], 
                           $report['execution_info']['stage'],
                           $status_icon);
            
            $body = $this->generateGitHubReportBody($report);
            
            $cmd = sprintf('gh issue create --title "%s" --body "%s"', 
                         addslashes($title), 
                         addslashes($body));
            
            $output = [];
            $return_code = 0;
            exec($cmd . ' 2>&1', $output, $return_code);
            
            if ($return_code === 0) {
                $issue_url = trim(implode("\n", $output));
                $this->writeLog("GitHub report created: $issue_url");
                echo "📋 GitHub Report: $issue_url" . PHP_EOL;
            } else {
                $this->writeLog("Failed to create GitHub report: " . implode("\n", $output), 'WARNING');
            }
        } catch (Exception $e) {
            $this->writeLog("Failed to create GitHub report: " . $e->getMessage(), 'WARNING');
        }
    }
    
    private function generateGitHubReportBody($report) {
        $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
        $stage_name = $this->getStageName($report['execution_info']['stage']);
        
        $body = "## {$status_icon} Execution Report\n\n";
        $body .= "### 📊 Execution Summary\n";
        $body .= "- **Todo**: {$report['execution_info']['todo_no']}\n";
        $body .= "- **Stage**: {$report['execution_info']['stage']} ({$stage_name})\n";
        $body .= "- **Status**: {$report['execution_info']['status']}\n";
        $body .= "- **Execution Time**: {$report['execution_info']['execution_time_seconds']}s\n";
        $body .= "- **Start Time**: {$report['execution_info']['start_time']}\n";
        $body .= "- **End Time**: {$report['execution_info']['end_time']}\n\n";
        
        if ($report['generated_files']['migration_name']) {
            $body .= "### 📁 Generated Files\n";
            $body .= "- **Migration**: {$report['generated_files']['migration_name']}\n";
            $body .= "- **File**: {$report['generated_files']['migration_file']}\n";
            $body .= "- **Counter**: {$report['generated_files']['counter_value']}\n\n";
        }
        
        if ($report['error']) {
            $body .= "### ❌ Error Details\n";
            $body .= "```\n{$report['error']}\n```\n\n";
        }
        
        $body .= "### 📝 Execution Log\n";
        foreach ($report['execution_log'] as $log) {
            if (is_array($log['details'])) {
                $details = json_encode($log['details'], JSON_PRETTY_PRINT);
            } else {
                $details = $log['details'];
            }
            $body .= "- **{$log['timestamp']}**: {$log['event']} - {$details}\n";
        }
        
        return $body;
    }
    
    private function getStageName($stage) {
        $stage_names = [
            0 => 'Issue Creation',
            1 => 'Branch Creation & File Copy',
            2 => 'File Modification',
            3 => 'Git Commit & Push',
            4 => 'Branch Cleanup & Counter Update'
        ];
        return $stage_names[$stage] ?? 'Unknown Stage';
    }
    
    private function getNextNo() {
        $count_file = $this->config['count_file'];
        if (!file_exists($count_file)) {
            file_put_contents($count_file, "1");
            return 1;
        }
        
        $count = file_get_contents($count_file);
        return intval(trim($count));
    }
    
    private function setNextNo($no) {
        $count_file = $this->config['count_file'];
        $next_no = $no + 1;
        file_put_contents($count_file, $next_no);
        $this->writeLog("Updated counter to: $next_no");
    }
    
    private function getCommands($migrate, $mfile) {
        // migrationsディレクトリ内でのファイル名を使用
        $migration_file = $mfile; // migration20250720007.go
        
        return [
            1 => get_cmd_1($migrate, $migration_file, $this->todo_no),
            2 => get_cmd_2($migrate, $migration_file, $this->todo_no),
            3 => get_cmd_3($migrate, $migration_file, $this->todo_no),
            4 => get_cmd_4($migrate, $migration_file, $this->todo_no)
        ];
    }
    
    private function executeCommands($commands) {
        if (!is_array($commands)) {
            throw new Exception("Commands must be an array");
        }
        
        foreach ($commands as $cmd) {
            $this->writeLog("Executing: $cmd");
            
            $output = [];
            $return_code = 0;
            $start_time = microtime(true);
            exec($cmd . ' 2>&1', $output, $return_code);
            $execution_time = microtime(true) - $start_time;
            
            $this->logExecution("Command executed", [
                'command' => $cmd,
                'return_code' => $return_code,
                'execution_time' => round($execution_time, 3),
                'output' => implode("\n", $output)
            ]);
            
            if ($return_code !== 0) {
                $error_msg = "Command failed: $cmd\nOutput: " . implode("\n", $output);
                throw new Exception($error_msg);
            }
            
            $this->writeLog("Command completed successfully");
            sleep(1);
        }
    }
    
    private function writeLog($message, $level = 'INFO') {
        $log_file = $this->config['log_dir'] . 'githubsh.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        echo $log_entry;
    }
}
