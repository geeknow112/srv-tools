<?php

class GitHubShellManager {
    private $config;
    private $todo_no;
    private $stage;
    private $execution_log = [];
    private $start_time;
    private $original_issue_number = null;
    
    public function __construct($config, $todo_no, $stage) {
        $this->config = $config;
        $this->todo_no = $todo_no;
        $this->stage = $stage;
        $this->start_time = microtime(true);
        date_default_timezone_set($this->config['timezone']);
        
        // 元のIssue番号を抽出
        if (preg_match('/srv-tools#(\d+)/', $todo_no, $matches)) {
            $this->original_issue_number = $matches[1];
        }
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
            $this->logExecution("実行開始", "ステージ {$this->stage} for {$this->todo_no}");
            
            [$migrate, $mfile, $next_no] = $this->generateMigrationFile();
            
            $commands = $this->getCommands($migrate, $mfile);
            $this->executeCommands($commands[$this->stage]);
            
            if ($this->stage == 4) {
                $this->setNextNo($next_no);
            }
            
            $this->logExecution("正常完了", "ステージ {$this->stage} の実行が完了しました");
            $this->writeLog("ステージ {$this->stage} for {$this->todo_no} が正常に完了しました");
            
            // 実行完了後にレポートを生成（成功時も含む）
            $this->generateExecutionReport($migrate, $mfile, $next_no);
            
        } catch (Exception $e) {
            $this->logExecution("エラー発生", $e->getMessage());
            $this->writeLog("エラー: " . $e->getMessage(), 'ERROR');
            
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
                'status' => $error ? 'FAILED' : 'SUCCESS',
                'original_issue' => $this->original_issue_number
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
        
        // GitHub Issue作成（Stage 4完了時は必ず、その他はエラー時のみ）
        if ($this->stage == 4 || $error) {
            $this->createGitHubReport($report, $report_file);
            
            // Stage 4成功時は元のIssueをClose
            if ($this->stage == 4 && !$error && $this->original_issue_number) {
                $this->closeOriginalIssue();
            }
        }
        
        $this->writeLog("実行レポートを生成しました: $report_file");
    }
    
    private function outputReportSummary($report) {
        $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
        
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 実行レポート {$status_icon}\n";
        echo str_repeat('=', 60) . "\n";
        echo "タスク: {$report['execution_info']['todo_no']}\n";
        echo "ステージ: {$report['execution_info']['stage']} ({$this->getStageName($report['execution_info']['stage'])})\n";
        echo "ステータス: {$report['execution_info']['status']}\n";
        echo "実行時間: {$report['execution_info']['execution_time_seconds']}秒\n";
        
        if ($report['generated_files']['migration_name']) {
            echo "マイグレーション: {$report['generated_files']['migration_name']}\n";
            echo "カウンター: {$report['generated_files']['counter_value']}\n";
        }
        
        if ($report['error']) {
            echo "エラー: {$report['error']}\n";
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
    
    private function createGitHubReport($report, $report_file) {
        try {
            $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
            $status_text = $report['execution_info']['status'] === 'SUCCESS' ? '完了' : 'エラー';
            
            $title = sprintf("実行レポート: %s ステージ%d %s %s", 
                           $report['execution_info']['todo_no'], 
                           $report['execution_info']['stage'],
                           $status_text,
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
                $this->writeLog("GitHubレポートを作成しました: $issue_url");
                echo "📋 GitHubレポート: $issue_url" . PHP_EOL;
            } else {
                $this->writeLog("GitHubレポートの作成に失敗しました: " . implode("\n", $output), 'WARNING');
            }
        } catch (Exception $e) {
            $this->writeLog("GitHubレポートの作成に失敗しました: " . $e->getMessage(), 'WARNING');
        }
    }
    
    private function closeOriginalIssue() {
        if (!$this->original_issue_number) {
            return;
        }
        
        try {
            $close_comment = sprintf("🎉 **ステージ4完了 - タスク完了**\n\n" .
                                   "タスク `%s` のすべてのステージが正常に完了しました。\n\n" .
                                   "## ✅ 完了した作業\n" .
                                   "- ステージ1: ブランチ作成とファイル配置\n" .
                                   "- ステージ2: ファイル修正\n" .
                                   "- ステージ3: Git操作とPR作成\n" .
                                   "- ステージ4: マージ確認、ブランチクリーンアップ、カウンター更新\n\n" .
                                   "このIssueを自動的にクローズします。", 
                                   $this->todo_no);
            
            // コメント追加
            $comment_cmd = sprintf('gh issue comment %d --body "%s"', 
                                 $this->original_issue_number, 
                                 addslashes($close_comment));
            exec($comment_cmd . ' 2>&1');
            
            // Issue クローズ
            $close_cmd = sprintf('gh issue close %d', $this->original_issue_number);
            $output = [];
            $return_code = 0;
            exec($close_cmd . ' 2>&1', $output, $return_code);
            
            if ($return_code === 0) {
                $this->writeLog("元のIssue #{$this->original_issue_number} をクローズしました");
                echo "🔒 元のIssue #{$this->original_issue_number} をクローズしました" . PHP_EOL;
            } else {
                $this->writeLog("元のIssueのクローズに失敗しました: " . implode("\n", $output), 'WARNING');
            }
        } catch (Exception $e) {
            $this->writeLog("元のIssueのクローズに失敗しました: " . $e->getMessage(), 'WARNING');
        }
    }
    
    private function generateGitHubReportBody($report) {
        $status_icon = $report['execution_info']['status'] === 'SUCCESS' ? '✅' : '❌';
        $status_text = $report['execution_info']['status'] === 'SUCCESS' ? '正常完了' : 'エラー';
        $stage_name = $this->getStageName($report['execution_info']['stage']);
        
        $body = "## {$status_icon} 実行レポート - {$status_text}\n\n";
        $body .= "### 📊 実行サマリー\n";
        $body .= "- **タスク**: {$report['execution_info']['todo_no']}\n";
        $body .= "- **ステージ**: {$report['execution_info']['stage']} ({$stage_name})\n";
        $body .= "- **ステータス**: {$report['execution_info']['status']}\n";
        $body .= "- **実行時間**: {$report['execution_info']['execution_time_seconds']}秒\n";
        $body .= "- **開始時刻**: {$report['execution_info']['start_time']}\n";
        $body .= "- **終了時刻**: {$report['execution_info']['end_time']}\n";
        
        if ($report['execution_info']['original_issue']) {
            $body .= "- **元のIssue**: #{$report['execution_info']['original_issue']}\n";
        }
        $body .= "\n";
        
        if ($report['generated_files']['migration_name']) {
            $body .= "### 📁 生成されたファイル\n";
            $body .= "- **マイグレーション**: {$report['generated_files']['migration_name']}\n";
            $body .= "- **ファイル**: {$report['generated_files']['migration_file']}\n";
            $body .= "- **カウンター**: {$report['generated_files']['counter_value']}\n\n";
        }
        
        if ($report['error']) {
            $body .= "### ❌ エラー詳細\n";
            $body .= "```\n{$report['error']}\n```\n\n";
        }
        
        $body .= "### 📝 実行ログ\n";
        foreach ($report['execution_log'] as $log) {
            if (is_array($log['details'])) {
                $details = json_encode($log['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $details = $log['details'];
            }
            $body .= "- **{$log['timestamp']}**: {$log['event']} - {$details}\n";
        }
        
        return $body;
    }
    
    private function getStageName($stage) {
        $stage_names = [
            0 => 'Issue作成',
            1 => 'ブランチ作成・ファイル配置',
            2 => 'ファイル修正',
            3 => 'Git操作・プッシュ',
            4 => 'ブランチクリーンアップ・カウンター更新'
        ];
        return $stage_names[$stage] ?? '不明なステージ';
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
        $this->writeLog("カウンターを更新しました: $next_no");
    }
    
    private function getCommands($migrate, $mfile) {
        // migrationsディレクトリ内でのファイル名を使用
        $migration_file = $mfile; // migration20250720010.go
        
        return [
            1 => get_cmd_1($migrate, $migration_file, $this->todo_no),
            2 => get_cmd_2($migrate, $migration_file, $this->todo_no),
            3 => get_cmd_3($migrate, $migration_file, $this->todo_no),
            4 => get_cmd_4($migrate, $migration_file, $this->todo_no)
        ];
    }
    
    private function executeCommands($commands) {
        if (!is_array($commands)) {
            throw new Exception("コマンドは配列である必要があります");
        }
        
        foreach ($commands as $cmd) {
            $this->writeLog("実行中: $cmd");
            
            $output = [];
            $return_code = 0;
            $start_time = microtime(true);
            exec($cmd . ' 2>&1', $output, $return_code);
            $execution_time = microtime(true) - $start_time;
            
            $this->logExecution("コマンド実行", [
                'command' => $cmd,
                'return_code' => $return_code,
                'execution_time' => round($execution_time, 3),
                'output' => implode("\n", $output)
            ]);
            
            if ($return_code !== 0) {
                $error_msg = "コマンドが失敗しました: $cmd\n出力: " . implode("\n", $output);
                throw new Exception($error_msg);
            }
            
            $this->writeLog("コマンドが正常に完了しました");
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
