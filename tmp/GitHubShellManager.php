<?php

class GitHubShellManager {
    private $config;
    private $todo_no;
    private $stage;
    
    public function __construct($config, $todo_no, $stage) {
        $this->config = $config;
        $this->todo_no = $todo_no;
        $this->stage = $stage;
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
            [$migrate, $mfile, $next_no] = $this->generateMigrationFile();
            
            $commands = $this->getCommands($migrate, $mfile);
            $this->executeCommands($commands[$this->stage]);
            
            if ($this->stage == 4) {
                $this->setNextNo($next_no);
            }
            
            $this->writeLog("Successfully executed stage {$this->stage} for {$this->todo_no}");
            
        } catch (Exception $e) {
            $this->writeLog("Error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
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
        // 元のget_cmd_1〜4関数を呼び出し
        return [
            1 => get_cmd_1($migrate, $mfile, $this->todo_no),
            2 => get_cmd_2($migrate, $mfile, $this->todo_no),
            3 => get_cmd_3($migrate, $mfile, $this->todo_no),
            4 => get_cmd_4($migrate, $mfile, $this->todo_no)
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
            exec($cmd . ' 2>&1', $output, $return_code);
            
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
